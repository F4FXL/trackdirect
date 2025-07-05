// ---- Standalone MapboxProvider (pour usage direct, sans leaflet-geosearch) ----
function MapboxProvider(options) {
    this.options = options || {};
    this.endpoint = 'https://api.mapbox.com/geocoding/v5/mapbox.places/';
    this.accessToken = (this.options.params && this.options.params.access_token) || '';
    this.params = Object.assign({
        limit: 5,
        language: 'en'
    }, (this.options.params || {}));
}

MapboxProvider.prototype.search = function ({ query }) {
    var self = this;
    return new Promise(function (resolve, reject) {
        if (!query || !self.accessToken) return resolve([]);
        var url = self.endpoint + encodeURIComponent(query) + '.json?access_token=' + encodeURIComponent(self.accessToken);
        // Ajoute les autres params
        Object.keys(self.params).forEach(function (k) {
            if (k !== "access_token") url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(self.params[k]);
        });
        fetch(url)
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                // Conversion au format leaflet-geosearch
                var results = (data.features || []).map(function (f) {
                    return {
                        x: f.center[0],
                        y: f.center[1],
                        label: f.place_name,
                        bounds: f.bbox && f.bbox.length === 4 ?
                            [[f.bbox[1], f.bbox[0]], [f.bbox[3], f.bbox[2]]] :
                            [[f.center[1], f.center[0]], [f.center[1], f.center[0]]],
                        raw: f
                    };
                });
                resolve(results);
            })
            .catch(function (e) { reject(e); });
    });
};

function MapboxProvider6(options) {
    this.options = options || {};
    this.endpoint = 'https://api.mapbox.com/search/geocoding/v6/forward';
    this.accessToken = (this.options.params && this.options.params.access_token) || '';
    this.params = Object.assign({
        limit: 5,
        language: ['en']
    }, (this.options.params || {}));
}

MapboxProvider6.prototype.search = function({ query }) {
    var self = this;
    return new Promise(function(resolve, reject) {
        if (!query || !self.accessToken) return resolve([]);

        var url = self.endpoint + '?access_token=' + encodeURIComponent(self.accessToken);

        var body = {
            q: query,
            limit: self.params.limit,
            language: self.params.language
        };

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
            // Les résultats sont dans data.features (similaire à v5)
            var results = (data.features || []).map(function(f) {
                // Les coordonnées sont dans f.geometry.coordinates [lon, lat]
                var coords = f.geometry && f.geometry.coordinates ? f.geometry.coordinates : [0, 0];
                // La bbox est dans f.bbox [w, s, e, n] (optionnelle)
                var bounds = f.bbox && f.bbox.length === 4
                    ? [ [f.bbox[1], f.bbox[0]], [f.bbox[3], f.bbox[2]] ]
                    : [ [coords[1], coords[0]], [coords[1], coords[0]] ];
                return {
                    x: coords[0],
                    y: coords[1],
                    label: f.place_formatted || f.place_name,
                    bounds: bounds,
                    raw: f
                };
            });
            resolve(results);
        })
        .catch(function(e) { reject(e); });
    });
};


$(function () {
    var provider = /*new MapboxProvider({
        params: { access_token: 'pk.eyJ1IjoiZjRmeGwiLCJhIjoiY2xzOHlkYnl1MDFheTJpcDU2YmppcnM1eiJ9.xNr8LX7iiO-1isgYn5HPNw' }
    });*/new window.GeoSearch.OpenStreetMapProvider();
    var $input = $('#geosearch-address');
    var $results = $('#geosearch-autocomplete-results');
    var debounceTimer = null;
    var resultsCache = [];

    function showResults(results) {
        resultsCache = results;
        if (results.length === 0) {
            $results.hide();
            return;
        }
        var html = '';
        $.each(results, function (i, r) {
            html += '<li data-index="' + i + '" data-lat="' + r.y + '" data-lon="' + r.x + '">' + r.label + '</li>';
        });
        $results.html(html).show();
        // Par défaut, aucune sélection
        $results.find('li').removeClass('selected');
    }

    $input.on('input', function () {
        clearTimeout(debounceTimer);
        var query = $(this).val();
        if (query.length < 3) {
            $results.hide();
            return;
        }
        debounceTimer = setTimeout(function () {
            provider.search({ query: query }).then(showResults);
        }, 300);
    });

    // Gestion clavier
    $input.on('keydown', function (e) {
        if (!$results.is(':visible')) return;
        var $items = $results.find('li');
        if ($items.length === 0) return;

        var $selected = $items.filter('.selected');
        var index = $selected.length ? $items.index($selected) : -1;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            var next = (index + 1) % $items.length;
            $items.removeClass('selected').eq(next).addClass('selected');
            // Scroll auto
            $results.scrollTop($items.eq(next).position().top + $results.scrollTop() - 4);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            var prev = (index - 1 + $items.length) % $items.length;
            $items.removeClass('selected').eq(prev).addClass('selected');
            $results.scrollTop($items.eq(prev).position().top + $results.scrollTop() - 4);
        } else if (e.key === 'Enter') {
            if (index >= 0) {
                e.preventDefault();
                $items.eq(index).click();
            }
        }
    });

    $results.on('mouseenter', 'li', function () {
        $results.find('li').removeClass('selected');
        $(this).addClass('selected');
    });

    $results.on('mouseleave', 'li', function () {
        $(this).removeClass('selected');
    });

    $results.on('click', 'li', function () {
        var $li = $(this);
        var lat = Number($li.data('lat'));
        var lon = Number($li.data('lon'));
        var index = $li.data('index');
        var result = resultsCache[index];
        $input.val($li.text());

        if (
            window.trackdirect._map &&
            Array.isArray(result.bounds) &&
            result.bounds.length === 2 &&
            Array.isArray(result.bounds[0]) &&
            Array.isArray(result.bounds[1])
        ) {
            var leafletBounds = L.latLngBounds(
                [Number(result.bounds[0][0]), Number(result.bounds[0][1])], // SW
                [Number(result.bounds[1][0]), Number(result.bounds[1][1])]  // NE
            );
            window.trackdirect._map.fitBounds(leafletBounds, { maxZoom: 18, animate: true });
        } else if (window.trackdirect._map) {
            window.trackdirect._map.setView([lat, lon], 16);
        }

        $results.hide();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.geosearch-autocomplete-container').length) {
            $results.hide();
        }
    });
});
