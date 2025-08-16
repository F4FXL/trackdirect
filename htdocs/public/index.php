<?php require "../includes/bootstrap.php"; 
$safe_GET = sanitize_get($_GET);
$mapType = in_array(strtolower($safe_GET['maptype']), ["roadmap", "terrain", "satellite"]) ? strtolower($safe_GET['maptype']) : "roadmap";
$time = in_array((int)$safe_GET['time'], [10, 30, 60, 180, 360]) ? $safe_GET['time'] : 60;
$grayscale = $safe_GET['grayscale'] == 1 ? 1 : 0;
$imperialunits = $safe_GET['imperialUnits'] == 1 || isImperialUnitUser() ? 1 : 0;
$phg = in_array((int)$safe_GET['phg'], [0, 1, 2]) ? (int)$safe_GET['phg'] : 0;
$rng = in_array((int)$safe_GET['rng'], [0, 1, 2]) ? (int)$safe_GET['rng'] : 0;
$mapapi = in_array($safe_GET['mapapi'], ['google', 'leaflet']) ? $safe_GET['mapapi'] : 'leaflet';
$hidenotmoving = $safe_GET['hidenotmoving'] == 1 ? 1 : 0;
$hideinternet = $safe_GET['hideinternet'] == 1 ? 1 : 0;

$timetravel = "0";
$timetravelday = "0";
$timetravelhour = "0";
if(isValidDateInRange($safe_GET['timetravel'], (int)getConfig('database', 'days_to_save_position_data'))) {
    $timetravel = "moment('" . $safe_GET['timetravel'] . "', 'YYYY-MM-DD HH:mm').unix()";
    $items = explode(' ', $safe_GET['timetravel']);
    $timetravelday = $items[0];
    $timetravelhour = $items[1];
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title><?php echo getWebsiteConfig('title'); ?></title>

        <!-- Mobile meta -->
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name="mobile-web-app-capable" content="yes">

        <!-- No Sleep from https://github.com/richtr/NoSleep.js -->
        <script src="/js/NoSleep.min.js"></script>
        <script language="Javascript">
            var sleepLockEnabled = false;
            var noSleep = new NoSleep();
        </script>

        <!-- JS libs used by this website (not a dependency for the track direct js lib) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/mobile-detect/1.4.5/mobile-detect.min.js" integrity="sha512-1vJtouuOb2tPm+Jh7EnT2VeiCoWv0d7UQ8SGl/2CoOU+bkxhxSX4gDjmdjmbX4OjbsbCBN+Gytj4RGrjV3BLkQ==" crossorigin="anonymous"></script>
        <script type="text/javascript" src="//www.gstatic.com/charts/loader.js"></script>

        <!-- Stylesheets used by this website (not a dependency for the track direct js lib) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css" integrity="sha512-HK5fgLBL+xu6dm/Ii3z4xhlSUyZgTT9tuc/hSrtw6uzJOvgRr2a9jyxxT1ely+B+xFAmJKVSTbpM/CuL7qxO8w==" crossorigin="anonymous" />

        <!-- Track Direct js dependencies -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js" integrity="sha512-jGsMH83oKe9asCpkOVkBnUrDDTp8wl+adkB2D+//JtlxO4SrLoJdhbOysIFQJloQFD+C4Fl1rMsQZF76JjV0eQ==" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment-with-locales.min.js" integrity="sha512-LGXaggshOkD/at6PFNcp2V2unf9LzFq6LE+sChH7ceMTDP0g2kn6Vxwgg7wkPP7AAtX+lmPqPdxB47A0Nz0cMQ==" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/autolinker/3.14.2/Autolinker.min.js" integrity="sha512-qyoXjTIJ69k6Ik7CxNVKFAsAibo8vW/s3WV3mBzvXz6Gq0yGup/UsdZBDqFwkRuevQaF2g7qhD3E4Fs+OwS4hw==" crossorigin="anonymous"></script>
        <script src="/js/convex-hull.js" crossorigin="anonymous"></script>


        <!-- Map api javascripts and related dependencies -->
        <?php if ($mapapi == 'google') : ?>
            <?php if (getWebsiteConfig('google_key') != null) : ?>
                <script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=<?php echo getWebsiteConfig('google_key'); ?>&libraries=visualization,geometry"></script>
            <?php else : ?>
                <script type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=visualization,geometry"></script>
            <?php endif; ?>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/OverlappingMarkerSpiderfier/1.0.3/oms.min.js" integrity="sha512-/3oZy+rGpR6XGen3u37AEGv+inHpohYcJupz421+PcvNWHq2ujx0s1QcVYEiSHVt/SkHPHOlMFn5WDBb/YbE+g==" crossorigin="anonymous"></script>

        <?php elseif ($mapapi == 'leaflet' || $mapapi == 'leaflet-vector'): ?>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" integrity="sha512-h9FcoyWjHcOcmEVkxOfTLnmZFWIH0iZhZT1H2TbOq55xssQGEJHEaIm+PgoUaZbRvQTNTluNOEfb1ZRy6D3BOw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" integrity="sha512-puJW3E/qXDqYp9IfhAI54BJEaWIfloJ7JWs7OeD5i6ruC9JZL1gERT1wjtwXFlh7CjE7ZJ+/vcRZRkIYIb6p4g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

            <?php if ($mapapi == 'leaflet-vector'): ?>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mapbox-gl/1.13.1/mapbox-gl.min.css" />
                <script src="https://cdnjs.cloudflare.com/ajax/libs/mapbox-gl/1.13.1/mapbox-gl.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/mapbox-gl-leaflet/0.0.15/leaflet-mapbox-gl.min.js"></script>
            <?php endif; ?>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-providers/1.11.0/leaflet-providers.min.js" integrity="sha512-TO+Wd5hbpDsACTmvzSqAZL83jMQCXGRFNoS4WZxcxrlJBTdgMYaT7g5uX49C5+Kbuxzlg2A+TFJ6UqdsXuOKLw==" crossorigin="anonymous"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.heat/0.2.0/leaflet-heat.js" integrity="sha512-KhIBJeCI4oTEeqOmRi2gDJ7m+JARImhUYgXWiOTIp9qqySpFUAJs09erGKem4E5IPuxxSTjavuurvBitBmwE0w==" crossorigin="anonymous"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/OverlappingMarkerSpiderfier-Leaflet/0.2.6/oms.min.js" integrity="sha512-V8RRDnS4BZXrat3GIpnWx+XNYBHQGdK6nKOzMpX4R0hz9SPWt7fltGmmyGzUkVFZUQODO1rE+SWYJJkw3SYMhg==" crossorigin="anonymous"></script>

            <!-- address search -->
            <script src="https://cdn.jsdelivr.net/npm/leaflet-geosearch@4.2.0/dist/bundle.min.js"></script>
            <link rel="stylesheet" href="/css/geosearch.css"/>
            <script src="/js/geosearch.js"></script>
        <?php endif; ?>

        <!-- Track Direct jslib -->
        <script type="text/javascript" src="/js/trackdirect.min.js"></script>

        <!-- sidebar stuff -->
        <!-- sidebar from https://github.com/locr-company/sidebar-v2/commit/15b24e81ba7f794e2d84514df5fea7922d985341 -->
        <script src="/js/leaflet-sidebar.min.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="/css/leaflet-sidebar.min.css">


        <script type="text/javascript" src="/js/main.js"></script>
        <link rel="stylesheet" href="/css/main.css">
        <link rel="stylesheet" href="/css/tweak-leaflet.css">
        <link rel="stylesheet" href="/css/leaflet-sidebar-tweak.css">
        <script>
            $(document).ready(function() {

                google.charts.load('current', {'packages':['corechart', 'timeline']});

                var options = {};
                options['isMobile'] = false;
                options['useImperialUnit'] = <?php echo (isImperialUnitUser() ? 'true': 'false'); ?>;
                options['coverageDataUrl'] = '/data/coverage.php';
                options['coveragePercentile'] = <?php echo (getWebsiteConfig('coverage_percentile') ?? "95"); ?>;

                var md = new MobileDetect(window.navigator.userAgent);
                if (md.mobile() !== null) {
                    options['isMobile'] = true;
                }

                options['time'] =       "<?php echo $time; ?>";        // How many minutes of history to show
                options['center'] =     "<?php echo $safe_GET['center'] ?? '' ?>";      // Position to center on (for example "46.52108,14.63379")
                options['zoom'] =       "<?php echo $safe_GET['zoom'] ?? '' ?>";        // Zoom level
                options['timetravel'] = <?php echo $timetravel?>;  // Unix timestamp to travel to
                options['maptype'] =    "<?php echo $mapType ?>";     // May be "roadmap", "terrain" or "satellite"
                options['mid'] =        "<?php echo $safe_GET['mid'] ?? '' ?>";         // Render map from "Google My Maps" (requires https)
                options['useImperialUnit'] = <?php echo $imperialunits == 1 ? 1:0 ?>;

                options['filters'] = {};
                options['filters']['sid'] = "<?php echo $safe_GET['sid'] ?? '' ?>";         // Station id to filter on
                options['filters']['sname'] = "<?php echo strtoupper($safe_GET['sname'] ?? '') ?>";     // Station name to filter on
                options['filters']['sidlist'] = "<?php echo $safe_GET['sidlist'] ?? '' ?>";     // Station id list to filter on (colon separated)
                options['filters']['snamelist'] = "<?php echo strtoupper($safe_GET['snamelist'] ?? '') ?>"; // Station name list to filter on (colon separated)

                // Tell jslib which html element to use to show connection status and mouse coordinates
                options['statusContainerElementId'] = 'status-container';
                options['coordinatesContainerElementId'] = 'coordinate-container';

                <?php if (isSourceIdUsed(5)) : ?>
                    // Adapt settings for OGN data
                    options['symbolsToScale'] = [[88,47],[94,null]];
                    options['defaultMinZoomForMarkerTail'] = 11;
                    options['defaultMinZoomForMarkers'] = 9;
                    options['defaultTimeLength'] = 10;
                <?php else : ?>
                    options['defaultTimeLength'] = 60; // In minutes
                <?php endif; ?>

                // Set this setting to false if you want to stop animations
                options['animate'] = true;

                // Use Stockholm as default position (will be used if we fail to fetch location from ip-location service)
                options['defaultLatitude'] = '59.30928';
                options['defaultLongitude'] = '18.08830';

                // Tip: request position from some ip->location service (https://freegeoip.app/json and https://ipapi.co/json is two examples)
                $.getJSON('https://ipapi.co/json', function(data) {
                    if (data.latitude && data.longitude) {
                        options['defaultLatitude'] = data.latitude;
                        options['defaultLongitude'] = data.longitude;
                    }
                }).fail(function() {
                    console.log('Failed to fetch location, using default location');
                }).always(function() {
                    <?php if ($mapapi == 'leaflet-vector') : ?>
                        options['mapboxGLStyle'] = "https://api.maptiler.com/maps/bright/style.json?optimize=true&key=<?php echo getWebsiteConfig('maptiler_key'); ?>";
                        options['mapboxGLAttribution'] = 'Map &copy; <a href="https://www.maptiler.com">MapTiler</a>, OpenStreetMap contributors';
                    <?php endif; ?>

                    <?php if ($mapapi == 'leaflet') : ?>
                        // We are using Leaflet -- read about leaflet-providers and select your favorite maps
                        // https://leaflet-extras.github.io/leaflet-providers/preview/

                        // Make sure to read the license requirements for each provider before launching a public website
                        // https://wiki.openstreetmap.org/wiki/Tile_servers

                        // Many providers require a map api key or similar, the following is an example for HERE
                        //L.TileLayer.Provider.providers['HERE'].options['app_id'] = '<?php echo getWebsiteConfig('here_app_id'); ?>';
                        //L.TileLayer.Provider.providers['HERE'].options['app_code'] = '<?php echo getWebsiteConfig('here_app_code'); ?>';

                        L.TileLayer.Provider.providers['IGN'] = {
							url: "https://data.geopf.fr/wmts?" +
								"&REQUEST=GetTile&SERVICE=WMTS&VERSION=1.0.0" +
								"&STYLE=normal" +
								"&TILEMATRIXSET=PM" +
								"&FORMAT=image/jpeg"+
								"&LAYER=ORTHOIMAGERY.ORTHOPHOTOS"+
							"&TILEMATRIX={z}" +
								"&TILEROW={y}" +
								"&TILECOL={x}",
							options:{
								minZoom : 0,
								maxZoom : 18,
										attribution : "IGN-F/Geoportail",
								tileSize : 256 // les tuiles du Géooportail font 256x256px
							}
						}

                        L.TileLayer.Provider.providers['FXLMap'] = {
		                	url: 'https://{s}.f4fxl.org/tile/{z}/{x}/{y}.png',
				            options: {
					            minZoom: 0, 
					            maxZoom: 25,
					            subdomains: ["map1","map2","map3","map4"],
					            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community' + 
					            '<br>&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
							}
						}

                        options['supportedMapTypes'] = {};
                        options['supportedMapTypes']['roadmap'] = "<?php echo getWebsiteConfig('leaflet_raster_tile_roadmap'); ?>";
                        options['supportedMapTypes']['terrain'] = "<?php echo getWebsiteConfig('leaflet_raster_tile_terrain'); ?>";
                        options['supportedMapTypes']['satellite'] = "<?php echo getWebsiteConfig('leaflet_raster_tile_satellite'); ?>";

                        // Make sure all tiles are stylable
                        Object.values(L.TileLayer.Provider.providers).forEach(function(provider) {
                            provider.options = provider.options || {};
                            if (provider.options.className) {
                                provider.options.className += '<?php echo $grayscale == 1 ? 'grayscale-tiles' : 'grayscale-tiles-dummy' ?>';
                            } else {
                                provider.options.className = '<?php echo $grayscale == 1 ? 'grayscale-tiles' : 'grayscale-tiles-dummy' ?>';;
                            }
                        });
                        window.sidebar = null;
                        // add sidebar
                        trackdirect.addListener("map-created", function() {
                            map = trackdirect._map
                            const sidebarOptions = {
                                    position: 'left'
                            };
                            window.sidebar = L.control.sidebar('sidebar', sidebarOptions).addTo(map);
                        });

                        // handle grayscale, PHG, RNG
                        trackdirect.addListener("map-created", function() {
                            //set grayscale once leaflet has been loaded
                            setGrayscaleMode(<?php echo $grayscale == 1 ? 'true' : 'false'; ?>);
                            // set PHG from URL
                            trackdirect.setPHGCirclesState(<?php echo $phg; ?>);
                            // set RNG from URL
                            trackdirect.setRNGCirclesState(<?php echo $rng; ?>);
                            // set stationary from URL
                            trackdirect.setStationaryPositionsState(<?php echo $hidenotmoving == 1? 'false' : 'true'?>);
                            // set internet from URL
                            trackdirect.setInternetPositionsState(<?php echo $hideinternet == 1? 'false' : 'true'?>);
                        });

                    <?php endif; ?>

                    // host is used to create url to /heatmaps and /images (leave empty to use same host as website)
                    options['host'] = "";
                    var supportsWebSockets = 'WebSocket' in window || 'MozWebSocket' in window;
                    if (supportsWebSockets) {
                        <?php if (getWebsiteConfig('websocket_url') != null) : ?>
                             var wsServerUrl = "<?php echo getWebsiteConfig('websocket_url'); ?>";
                        <?php else : ?>
                            var wsServerUrl = '';
                            if (window.location.protocol == 'https:') {
                                wsServerUrl += 'wss://' + window.location.host;
                            } else {
                                wsServerUrl += 'ws://' + window.location.host;
                            }
                            wsServerUrl += '/ws';
                        <?php endif; ?>
                        var mapElementId = 'map-container';

                        trackdirect.init(wsServerUrl, mapElementId, options);
                    } else {
                        alert('This service require HTML 5 features to be able to feed you APRS data in real-time. Please upgrade your browser.');
                    }
                });
            });
        </script>
    </head>
    <body>


        <div id="sidebar" class="sidebar collapsed">
            <!-- Nav tabs -->
            <div class="sidebar-tabs">
                <ul role="tablist">
                    <!-- <li><a href="#home" role="tab"><i class="fa fa-bars"></i></a></li> -->
                    <li><a href="#sb-map-location" role="tab" title="Go to specific location on map"><i class="fas fa-map-marker-alt"></i></a></li>
                    <li><a href="#sb-time-options" role="tab" title="Select history duration shown on map"><i class="fas fa-clock"></i></a></li>
                    <li><a href="#sb-map-options" role="tab" title="Switch between map types and change map appearance"><i class="fas fa-map"></i></a></li>
                    <li><a href="#sb-show-hide" role="tab" title="Show hide items on the map"><i class="far fa-eye"></i></a></li>
                    <li><a href="#sb-search" role="tab" title="Search for a station"><i class="fas fa-search"></i></a></li>
                    <li><a href="#sb-filtering" role="tab" title="Filtering" class="icon-badge"><i class="fas fa-filter"><span id="td-filters-count" class="badge">3</span></i></a></li>
                    <li><a href="#sb-other" role="tab" title="Other"><i class="fas fa-ellipsis-h"></i></a></li>
                </ul>

                <ul role="tablist">
                    <li><a href="/views/about.php" role="tab" class="tdlink"><i class="far fa-question-circle"></i></a></li>
                    <li><a href="#sb-settings" role="tab"><i class="fa fa-cog"></i></a></li>
                </ul>
            </div>

            <!-- Tab panes -->
            <div class="sidebar-content">
                <div class="sidebar-pane" id="sb-map-location">
                    <h1 class="sidebar-header">Navigate on map</h1>
                    <div class="sidebar-close" role="button"><i class="fas fa-times"></i></a></div>
                    <p><br><a  href=""
                        id="goto-my-location"
                        onclick="
                            window.sidebar.close();
                            if (location.protocol != 'https:') {
                                trackdirect.setCenter(); // Will go to default position
                            } else {
                                trackdirect.setMapLocationByGeoLocation(
                                    function(errorMsg) {
                                        var msg = 'We failed to determine your current location by using HTML 5 Geolocation functionality';
                                        if (typeof errorMsg !== 'undefined' && errorMsg != '') {
                                            msg += ' (' + errorMsg + ')';
                                        }
                                        msg += '.';
                                        alert(msg);
                                    },
                                    function() {},
                                    5000
                                );
                            }
                            return false;"
                        title="Go to my current position">
                        <i class="fa-crosshairs fa"></i>&nbsp;&nbsp;Go to my location
                        </a></p>
                        <h2>Locator</h2>
                        <p>Go to a specific QRA Locator (2,6, 8 and 10 digits are supported)</p><p><input type="text" id="qra-locator" style="width: 100%" placeholder="QRA Locator"></p>
                        <h2>Address</h2>
                        <p> 
                            <div class="geosearch-autocomplete-container">
                                <p>Search for an address, city ...</p>
                                <input type="text" name="geosearch-address" id="geosearch-address" autocomplete="off" placeholder="Address, city name, state ...." style="width: 100%">
                                <ul id="geosearch-autocomplete-results" style="width: 100%"></ul>
                            </div>
                        </p>
                </div>
                <div class="sidebar-pane" id="sb-time-options">
                    <h1 class="sidebar-header">Time options</h1>
                    <div class="sidebar-close" role="button"><i class="fas fa-times"></i></a></div>
                    <h2>Tail length</h2>
                    <?php
                        if (isSourceIdUsed(5)) : ?>
                            <p><a id="time-10" role="checkbox" <?php echo $time == 10 ? 'id="tdTopnavTimelengthDefault"' : ''?> href="javascript:void(0);" onclick="trackdirect.setTimeLength(10);" data-group="time-checkbox" class="toggle-checkbox">  <i class="far <?php echo $time == 10 ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;10 minutes</a></p>
                        <? else : ?>
                            <p><a id="time-10" role="checkbox" <?php echo $time == 10 ? 'id="tdTopnavTimelengthDefault"' : ''?> href="javascript:void(0);" onclick="trackdirect.setTimeLength(10);" data-group="time-checkbox" class="toggle-checkbox"><i class="far <?php echo $time == 10 ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;10 minutes</a></p>
                    <?php endif; ?>

                    <p><a id="time-30" role="checkbox" <?php echo $time == 30 ? 'id="tdTopnavTimelengthDefault"' : ''?> href="javascript:void(0);" onclick="trackdirect.setTimeLength(30);" data-group="time-checkbox" class="toggle-checkbox "><i class="far <?php echo $time == 30 ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;30 minutes</a></p>

                    <?php if (isSourceIdUsed(5)) : ?>
                    <p><a id="time-60" role="checkbox" <?php echo $time == 60 ? 'id="tdTopnavTimelengthDefault"' : ''?> href="javascript:void(0);" onclick="trackdirect.setTimeLength(60);"  data-group="time-checkbox" class="toggle-checkbox"><i class="far <?php echo $time == 60 ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;1 hour</a></p>
                    <?php else : ?>
                    <p><a id="time-60" role="checkbox" <?php echo $time == 60 ? 'id="tdTopnavTimelengthDefault"' : ''?> href="javascript:void(0);" onclick="trackdirect.setTimeLength(60);" data-group="time-checkbox" class="toggle-checkbox" ><i class="far <?php echo $time == 60 ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;1 hour</a></p>
                    <?php endif;?>

                    <p><a id="time-180"  role="checkbox" href="javascript:void(0);" onclick="trackdirect.setTimeLength(60 *  3);" data-group="time-checkbox" class="toggle-checkbox"><i class="far <?php echo $time == 180 ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;3 hours</a></p>
                    <p><a id="time-360"  role="checkbox" href="javascript:void(0);" onclick="trackdirect.setTimeLength(60 *  6);" data-group="time-checkbox" class="toggle-checkbox"><i class="far <?php echo $time == 360 ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;6 hours</a></p>
                    <p><a id="time-720"  role="checkbox" href="javascript:void(0);" onclick="trackdirect.setTimeLength(60 * 12);" data-group="time-checkbox" class="toggle-checkbox dropdown-content-checkbox-only-filtering dropdown-content-checkbox-hidden"><i class="far fa-square"></i>&nbsp;&nbsp;12 hours</a></p>
                    <p><a id="time-1440" role="checkbox" href="javascript:void(0);" onclick="trackdirect.setTimeLength(60 * 24);" data-group="time-checkbox" class="toggle-checkbox dropdown-content-checkbox-only-filtering dropdown-content-checkbox-hidden"><i class="far fa-square"></i>&nbsp;&nbsp;1 day</a></p>
                    <p><a id="time-2880" role="checkbox" href="javascript:void(0);" onclick="trackdirect.setTimeLength(60 * 48);" data-group="time-checkbox" class="toggle-checkbox dropdown-content-checkbox-only-filtering dropdown-content-checkbox-hidden"><i class="far fa-square"></i>&nbsp;&nbsp;2 days</a></p>
                    <p><a id="time-4320" role="checkbox" href="javascript:void(0);" onclick="trackdirect.setTimeLength(60 * 72);" data-group="time-checkbox" class="toggle-checkbox dropdown-content-checkbox-only-filtering dropdown-content-checkbox-hidden"><i class="far fa-square"></i>&nbsp;&nbsp;3 days</a></p>
                    <h2>Time travel</h2>
                    <?php if (!isAllowedToShowOlderData()) : ?>
                        <div style="text-align: center;">
                            <p style="max-width: 800px; display: inline-block; color: red;">
                                The time travel feature that allows you to see the map as it looked like an earlier date is disabled on this website.
                            </p>
                        </div>
                    <?php else : ?>
                        <p>Select date and time to show map data for (enter time for your locale time zone). The regular time length select box can still be used to select how old data that should be shown (relative to selected date and time).</p>
                        <p>*Note that the heatmap will still based on data from the latest hour (not the selected date and time).</p>
                        <p>Date and time:</p>

                        <form id="timetravel-form">
                            <select id="timetravel-date" class="timetravel-select form-control">
                                <option value="0"<?php echo $timetravelday == "0" ? " selected": "" ?>>Select date</option>
                                <?php
                                        $numberofdays = (int)getConfig('database', 'days_to_save_position_data');
                                        for($i=0; $i < $numberofdays; $i++) :
                                            $date = date('Y-m-d', strtotime("-$i days")); ?>
                                            <option value="<?php echo $date; ?>"<?php echo $date == $timetravelday ? " selected" : "" ?>><?php echo $date; ?></option>
                                <?php
                                        endfor;
                                ?>
                            </select>

                            <select id="timetravel-time" class="timetravel-select form-control">
                                <option value="0"<?php echo $timetravelday == "0" ? " selected": "" ?>>Select time</option>
                                <?php
                                    for($i = 0; $i < 24; $i++) {
                                        $value = str_pad($i, 2, "0", STR_PAD_LEFT) . ":00";
                                        $selected = ($value == $timetravelhour) ? ' selected' : ''; ?>
                                        <option value="<?= $value ?>"<?= $selected ?>><?= $value ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                            <input type="submit"
                                value="Ok"
                                onclick="
                                    if ($('#timetravel-date').val() != '0' && $('#timetravel-time').val() != '0') {
                                        trackdirect.setTimeLength(60, false);
                                        var ts = moment($('#timetravel-date').val() + ' ' + $('#timetravel-time').val(), 'YYYY-MM-DD HH:mm').unix();
                                        trackdirect.setTimeTravelTimestamp(ts);
                                        $('#right-container-timetravel-content').html('Time travel to ' + $('#timetravel-date').val() + ' ' + $('#timetravel-time').val());
                                        $('#right-container-timetravel').show();
                                    } else {
                                        trackdirect.setTimeTravelTimestamp(0, true);
                                        $('#right-container-timetravel').hide();
                                    }
                                    $('#modal-timetravel').hide();
                                    return false;"/>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="sidebar-pane" id="sb-map-options">
                    <h1 class="sidebar-header">Map Options</h1>
                    <div class="sidebar-close" role="button"><i class="fas fa-times"></i></a></div>
                    <h2>Type</h2>
                    <p><a role="checkbox" href="javascript:void(0);" onclick="setMapType('roadmap');" data-group="map-type-checkbox" class="toggle-checkbox"><i class="far <?php echo $mapType == "roadmap" ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;Roadmap</a></p>
                    <p><a role="checkbox" href="javascript:void(0);" onclick="setMapType('terrain');" data-group="map-type-checkbox" class="toggle-checkbox"><i class="far <?php echo $mapType == "terrain" ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;Terrain / Outdoors</a></p>
                    <?php if (getWebsiteConfig('leaflet_raster_tile_satellite') != null) : ?>
                    <p><a role="checkbox" href="javascript:void(0);" onclick="setMapType('satellite');" data-group="map-type-checkbox" class="toggle-checkbox"><i class="far <?php echo $mapType == "satellite" ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;Satellite</a></p>
                    <?php endif; ?>
                    <h2>Appearance</h2>
                    <p><a role="checkbox" href="javascript:void(0);" onclick="setGrayscaleMode();" class="toggle-checkbox"><i class="far <?php echo $grayscale == 1 ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;Grayscale Map</a></p>
                </div>
                <div class="sidebar-pane" id="sb-show-hide">
                    <h1 class="sidebar-header">Show / Hide Items on Map</h1>
                    <div class="sidebar-close" role="button"><i class="fas fa-times"></i></a></div>
                    <?php if (isSourceIdUsed(1)) : ?>
                        <h2>Circles</h2>
                        <p><a role="checkbox" href="javascript:void(0);" onclick="toggleCircles(this, false);" class="toggle-checkbox-eye-no-auto"><i class="<?php echo ['far fa-eye-slash', 'far fa-eye', 'fas fa-eye'][$phg]; ?>"></i></i>&nbsp;&nbsp;PHG</a></p>
                        <p><a role="checkbox" href="javascript:void(0);" onclick="toggleCircles(this, true);"  class="toggle-checkbox-eye-no-auto"><i class="<?php echo ['far fa-eye-slash', 'far fa-eye', 'fas fa-eye'][$rng]; ?>"></i></i>&nbsp;&nbsp;Range</a></p>
                        <h2>Station types</h2>
                        <p><a role="checkbox" href="javascript:void(0);" onclick="toggleStationaryStations();"  class="toggle-checkbox-eye"><i class="far <?php echo $hidenotmoving == 0 ? "fa-eye" : "fa-eye-slash"; ?>"></i></i>&nbsp;&nbsp;Not moving stations</a></p>
                        <p><a role="checkbox" href="javascript:void(0);" onclick="toggleInternetStations();"  class="toggle-checkbox-eye"><i class="far <?php echo $hideinternet == 0 ? "fa-eye" : "fa-eye-slash"; ?>"></i></i>&nbsp;&nbsp;Internet stations</a></p>
                    <?php endif; ?>
                </div>
                <div class="sidebar-pane" id="sb-search">
                    <h1 class="sidebar-header">Search for Stations</h1>
                    <div class="sidebar-close" role="button"><i class="fas fa-times"></i></a></div>
                    <p>Type a station name and hit enter or use the advanced search below.</p><p><input type="text" id="station-search" style="width: 100%" placeholder="Search..."></p>
                    <hr>
                    <p><a href="/views/search.php" class="tdlink tdlink-sb"><i class="fas fa-search-plus"></i>&nbsp;&nbsp;Advanced Search</a></p>
                </div>
                <div class="sidebar-pane" id="sb-filtering">
                    <h1 class="sidebar-header">Filtering</h1>
                    <div class="sidebar-close" role="button"><i class="fas fa-times"></i></a></div>
                    <div id="td-sidebar-filtering"></div>
                </div>
                <div class="sidebar-pane" id="sb-other">
                    <h1 class="sidebar-header">Other</h1>
                    <div class="sidebar-close" role="button"><i class="fas fa-times"></i></a></div>
                    <p><a href="/views/latest.php" class="tdlink tdlink-sb" title="Show all received packets live"><i class="fas fa-broadcast-tower"></i></i>&nbsp;&nbsp;Latest Packets</a><p>
                </div>
                <div class="sidebar-pane" id="sb-settings">
                    <h1 class="sidebar-header">Settings</h1>
                    <div class="sidebar-close" role="button"><i class="fas fa-times"></i></a></div>
                    <p><a role="checkbox" href="javascript:void(0);" onclick="toggleImperialUnits();" class="toggle-checkbox"><i class="far <?php echo $imperialunits == 1 ? "fa-check-square" : "fa-square"?>"></i>&nbsp;&nbsp;Imperial Units</a></p>
                    <p><a role="checkbox" href="javascript:void(0);" onclick="  if(sleepLockEnabled) {
                                                                                    sleepLockEnabled = false;
                                                                                    noSleep.disable();
                                                                                }
                                                                                else {
                                                                                    sleepLockEnabled = true;
                                                                                    noSleep.enable();
                                                                                }" 
                        class="toggle-checkbox"><i class="far fa-square"></i>&nbsp;&nbsp;Keep screen on</a></p>
                </div>
            </div>
        </div>

        <div id="map-container" class="sidebar-map"></div>

        <div id="right-container">
            <!-- <div id="right-container-info">
                <div  id="status-container"></div>
            </div> -->
            <!-- <div id="coordinate-container">
                <span  id="coordinate-container-content"></span>
            </div> -->
            <div id="right-container-filtered">
                <span id="right-container-filtered-content"></span>
                <a href="#" onclick="trackdirect.filterOnStationId([]); return false;">reset</a>
            </div>

            <div id="right-container-timetravel">
                <span id="right-container-timetravel-content"></span>
                <a href="#" onclick="trackdirect.setTimeTravelTimestamp(0); $('#right-container-timetravel').hide(); return false;">reset</a>
            </div>
        </div>


        <div id="td-modal" class="modal">
            <div class="modal-long-content">
                <div class="modal-content-header">
                    <span class="modal-close" id="td-modal-close">&times;</span>
                    <span class="modal-title" id="td-modal-title"><?php echo getWebsiteConfig('title'); ?></h2>
                </div>
                <div class="modal-content-body">
                    <div id="td-modal-content">
                        <?php $view = getView($safe_GET['view']); ?>
                        <?php if ($view) : ?>
                            <?php include($view); ?>
                        <?php else: ?>
                            <div id="td-modal-content-nojs">
                                <?php include(ROOT . '/public/views/about.php'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="bottominfo-container">
            <div id="coordinate-container"></div>
            <div id="status-container"></div>
        </div>
    </body>
</html>
