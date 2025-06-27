jQuery(document).ready(function ($) {
  $('#td-modal-content-nojs').remove();
  if ($('#td-modal-content').text().trim() == '') {
    $('#td-modal').hide();
  } else {
    var title = $('#td-modal-content title').text();
    if (title != '') {
      $("#td-modal-title").text(title);
    }
  }
});

// Init local time presentation
jQuery(document).ready(function ($) {
  var locale = window.navigator.userLanguage || window.navigator.language;
  moment.locale(locale);
});

// Switch between regular topnav and topnav adapted for mobile
function toggleTopNav() {
  var x = document.getElementById("tdTopnav");
  if (x.className === "topnav") {
    x.className += " responsive";
  } else {
    x.className = "topnav";
  }
}

// If an external website shows map in iframe, hide all menu's
if (!inIframe()) {
  $("#tdTopnav").hide();
}

// Set correct time length option to active
jQuery(document).ready(function ($) {
  // document.querySelectorAll('.tdTopnavTimelengthDefault').forEach(toggleCheckBox);
});

// Open all internal url's in dialog
function loadView(url) {
  var view = url.split('/').pop().split("?")[0];
  if (view != '') {
    var requestUrl = '/views/' + url.split('/').pop();
    $("#td-modal-content").html('<img src="/images/spinner.gif" style="max-width: 100%; max-height: 100px; margin-top: 40px; margin-left: auto; margin-right: auto; display: block;"/>');
    $("#td-modal-title").text('');
    $("#td-modal").show();
    $("#td-modal-content").load(requestUrl, {'modal': true},
      function() {
        history.replaceState(null, "", requestUrl);
        var title = $('#td-modal-content title').text();
        $("#td-modal-title").text(title);

        $("#td-modal-content .tdlink").unbind('click').bind('click', function(e) {
          loadView(this.href);
          e.preventDefault();
        });
      }
    );
  }
}
jQuery(document).ready(function ($) {
  $(".tdlink").bind('click', function(e) {
    loadView(this.href);
    e.preventDefault();
  });
});

// Handle dialog close
jQuery(document).ready(function ($) {
  $("#td-modal-close").bind('click', function(e) {
    $('#td-modal').hide();
    let url = new URL(window.location);
    url.pathname = ""; //we are closing a view, clear the path
    url.searchParams.delete("id"); //cleanup id, if any
    history.replaceState(null, "", url.toString().replace("%2C", ","));
  });
});

// Open station dialog if user clicked on station name
jQuery(document).ready(function ($) {
  trackdirect.addListener("station-name-clicked", function (data) {
    let url = new URL(window.location);
    url.searchParams.set("id", data.station_id);
    url.pathname = "/views/overview.php";
    loadView(url.toString().replace("%2C", ","));
  });
});

// Update url when user moves map
jQuery(document).ready(function ($) {
  trackdirect.addListener("position-request-sent", function (data) {
      if ($("#td-modal").is(":hidden")) {
        let url = new URL(window.location);

        var newLat = Math.round(data.center.lat * 10000) / 10000;
        var newLng = Math.round(data.center.lng * 10000) / 10000;
        var newZoom = data.zoom;

        url.searchParams.set('center',  + newLat + "," + newLng);
        url.searchParams.set('zoom', newZoom);

        window.history.replaceState({}, '', url.toString().replace("%2C", ","));//dirty hack to ensure we have , instead of %2C in the URL
    }
  });
});

// Handle filter response
jQuery(document).ready(function ($) {
  trackdirect.addListener("filter-changed", function (packets) {
    if (packets.length == 0) {
      // We are not filtering any more.
      $("#right-container-filtered").hide();

      // Time travel is stopped when filtering is stopped
      $("#right-container-timetravel").hide();

      // Reset tail length to default when filtering is stopped
      $(".dropdown-content-checkbox-only-filtering").addClass("dropdown-content-checkbox-hidden");
      toggleCheckBox(document.getElementById('tdTopnavTimelengthDefault'));
      
    } else {
      var counts = {};
      for (var i = 0; i < packets.length; i++) {
        // Note that if related is set to 1, it is included since it is related to the station we are filtering on
        if (packets[i].related == 0) {
          counts[packets[i]["station_name"]] =
            1 + (counts[packets[i]["station_name"]] || 0);
        }
      }
      $("#right-container-filtered-content").html(
        "Filtering on " + Object.keys(counts).length + " station(s)"
      );
      $("#right-container-filtered").show();
      $(".dropdown-content-checkbox-only-filtering").removeClass("dropdown-content-checkbox-hidden");
    }
  });
});


function setMapType(value) {
    trackdirect.setMapType(value)
    const url = new URL(window.location);
    if(value != 'roadmap')
      url.searchParams.set('maptype', value);
    else
      url.searchParams.delete('maptype');
    window.history.pushState({}, '', url);
    setGrayscaleMode();
}

function setGrayscaleMode(enabled) {
  // Cherche tous les conteneurs de tiles providers
  var allTileContainers = document.querySelectorAll('.grayscale-tiles, .grayscale-tiles-dummy');

  if (enabled === undefined) {
      // check if we have grayscale in url
      let url = new URL(window.location);
      if(url.searchParams.get('grayscale')) {
        enabled = url.searchParams.get('grayscale') == '1';
      }
      else {
        // Détecte l'état courant : s'il y a au moins un .grayscale-tiles, c'est en gris, sinon couleur
        var isGray = document.querySelector('.grayscale-tiles') !== null;
        enabled = !isGray;
      }
  }

  allTileContainers.forEach(function(container) {
      if (enabled) {
          container.classList.remove('grayscale-tiles-dummy');
          container.classList.add('grayscale-tiles');
      } else {
          container.classList.remove('grayscale-tiles');
          container.classList.add('grayscale-tiles-dummy');
      }
  });

  // Gestion du paramètre d'URL
  var url = new URL(window.location);
  if (enabled) {
      url.searchParams.set('grayscale', '1');
  } else {
      url.searchParams.delete('grayscale');
  }
  window.history.replaceState({}, '', url);
}

// Handle click on checkbox links
jQuery(document).ready(function ($) {
  document.querySelectorAll('.toggle-checkbox').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      toggleCheckBox(anchor, '.toggle-checkbox', 'fa-square', 'fa-check-square');
    });
  });

  document.querySelectorAll('.toggle-checkbox-eye').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      toggleCheckBox(anchor, '.toggle-checkbox-eye', 'fa-eye', 'fa-eye-slash');
    });
  });
});

function toggleCheckBox(anchor, cls, unchecked, checked) {
  const icon = anchor.querySelector('i');
  const group = anchor.dataset.group;

  if (group) {
    // Mode radio (une seule case cochée par groupe)
    document.querySelectorAll(cls + '[data-group="' + group + '"]').forEach(other => {
      const iconOther = other.querySelector('i');
      iconOther.classList.remove(checked);
      iconOther.classList.add(unchecked);
    });
    // Toujours cocher la case cliquée
    icon.classList.remove(unchecked);
    icon.classList.add(checked);
  } else {
    // Mode case isolée (toggle classique)
    icon.classList.toggle(unchecked);
    icon.classList.toggle(checked);
  }
}

function setTimeLength(time)
{
  trackdirect.setTimeLength(time);
  var url = new URL(window.location);
  url.searchParams.set('time', time);
  window.history.replaceState({}, '', url);
}

function toggleImperialUnits()
{
  trackdirect.toggleImperialUnits();
  var url = new URL(window.location);
  if(trackdirect.isImperialUnits())
    url.searchParams.set('imperialUnits', 1);
  else
    url.searchParams.delete('imperialUnits');
  window.history.replaceState({}, '', url);
}

function toggleCircles(anchor, rng = false)
{
  let state = 0;
  if(rng) {
    trackdirect.toggleRNGCircles();
    state = trackdirect.getRNGCirclesState();
  }
  else {
    trackdirect.togglePHGCircles();
    state = trackdirect.getPHGCirclesState();
  }
  var url = new URL(window.location);

  if(state != 0) {
    url.searchParams.set(rng ? 'rng' : 'phg', state);
  }
  else {
    url.searchParams.delete(rng ? 'rng' : 'phg');
  }

  window.history.replaceState({}, '', url);

  const icon = anchor.querySelector('i');
  icon.classList.remove('fas')
  icon.classList.remove('far')
  icon.classList.remove('fa-eye');
  icon.classList.remove('fa-eye-slash');

  switch(state)
  {
    case 0:
      icon.classList.add('far');
      icon.classList.add('fa-eye-slash');
      break;
    case 1:
      icon.classList.add('far');
      icon.classList.add('fa-eye');
      break;
    case 2:
      icon.classList.add('fas');
      icon.classList.add('fa-eye');
      break;
  }
}