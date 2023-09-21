<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */


/*
 * Don't do this here, this is inside a widget!
 *
register_html_resource('css', 'leaflet.css');
register_html_resource('js', 'leaflet.js');

$ua = detect_browser();
if ($ua['browser'] == 'MSIE' ||
    ($ua['browser'] == 'Firefox' && version_compare($ua['version'], '61', '<'))) // Also for FF ESR60 and older
{
  register_html_resource('js', 'js/compat/bluebird.min.js');
  register_html_resource('js', 'js/compat/fetch.js');
}
register_html_resource('js', 'leaflet-realtime.js');
register_html_resource('css', 'MarkerCluster.css');
register_html_resource('css', 'MarkerCluster.Default.css');

//register_html_resource('js', 'leaflet.markercluster.js');
//register_html_resource('js', 'leaflet.featuregroup.subgroup');

//register_html_resource('js', '/geo.php');

*/

/* old
// [lat, lng], zoom
if (is_numeric($config['frontpage']['map']['zoom']) &&
    is_numeric($config['frontpage']['map']['center']['lat']) &&
    is_numeric($config['frontpage']['map']['center']['lng']))
{
  // Manual zoom & map center
  $leaflet_init   = '[' . $config['frontpage']['map']['center']['lat'] . ', ' .
                    $config['frontpage']['map']['center']['lng'] . '], ' .
                    $config['frontpage']['map']['zoom'];
  $leaflet_bounds = '';
}
else
{
  // Auto zoom
  $leaflet_init   = '[0, -0], 2';
  $leaflet_bounds = 'map'.$mod['widget_id'].'.fitBounds(realtime.getBounds(), { padding: [30, 30] });';
}

switch ($config['frontpage']['map']['tiles'])
{
  case 'esri-worldgraycanvas':
    $leaflet_url  = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}';
    $leaflet_copy = 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ';
    break;

  case 'opentopomap':
    $leaflet_url = 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png';
    $leaflet_copy
                 = 'Map data: &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)';
    break;

  case 'osm-mapnik':
    $leaflet_url  = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    $leaflet_copy = '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>';
    break;

  case 'nasa-night':
    $leaflet_url    = 'https://map1.vis.earthdata.nasa.gov/wmts-webmerc/VIIRS_CityLights_2012/default//GoogleMapsCompatible_Level8/{z}/{y}/{x}.jpg';
    $leaflet_copy   = 'Imagery provided by GIBS, operated by <a href="https://earthdata.nasa.gov">ESDIS</a>, funding by NASA/HQ.';
    $leaflet_format = 'jpg';
    break;

  case 'wikimedia':
    $leaflet_url  = 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png';
    $leaflet_copy = '<a href="https://wikimediafoundation.org/wiki/Maps_Terms_of_Use">Wikimedia</a>';
    break;

  case 'carto-base-dark':
  case 'carto-base-light':
  case 'carto-base-auto':
  default:

    if($config['frontpage']['map']['tiles'] == "carto-base-dark") {
        $leaflet_variant = "dark_all";
    }  elseif ($config['frontpage']['map']['tiles'] == "carto-base-light") {
        $leaflet_variant = "light_all";
    } else {
        $leaflet_variant = ($config['themes'][$_SESSION['theme']]['type'] == 'dark' ? "dark_all" : "light_all");
    }

    $leaflet_url     = is_ssl() ? 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/' . $leaflet_variant . '/{z}/{x}/{y}.png' :
      'http://{s}.basemaps.cartocdn.com/' . $leaflet_variant . '/{z}/{x}/{y}.png';

    $leaflet_hqurl = is_ssl() ? 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/' . $leaflet_variant . '/{z}/{x}/{y}@2x.png' :
      'http://{s}.basemaps.cartocdn.com/' . $leaflet_variant . '/{z}/{x}/{y}@2x.png';


    $leaflet_copy = 'Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, ' .
                    '&copy; <a href="https://carto.com/attributions">CARTO</a>';
    break;
}

?>

    <script type="text/javascript">
        var icons = {
            ok: L.icon({
                iconUrl: 'images/svg/ok.svg',
                popupAnchor: [0, 16],
                iconSize: [<?php echo $config['frontpage']['map']['okmarkersize']; ?>, <?php echo $config['frontpage']['map']['okmarkersize']; ?>] // size of the icon
            }),

            alert: L.icon({
                iconUrl: 'images/svg/high_priority.svg',
                popupAnchor: [0, 12],
                iconSize: [<?php echo $config['frontpage']['map']['alertmarkersize']; ?>, <?php echo $config['frontpage']['map']['alertmarkersize']; ?>] // size of the icon
            })
        };

        var url = 'geojson.php?<?php http_build_query($vars['geojson_query']); ?>';

        var map<?php echo $mod['widget_id']; ?> = L.map('map<?php echo $mod['widget_id']; ?>'),
            realtime = L.realtime({
                url: url,
                method: 'POST',
                crossOrigin: true,
                type: 'json',
                getFeatureId: function (feature) {
                    return feature.properties.id + feature.properties.state;
                }
            }, {
                interval: 10 * 1000,

                onEachFeature: function (feature, layer) {

                    if (feature.properties && feature.properties.popupContent) {
                        layer.bindPopup(feature.properties.popupContent, {closeButton: false, offset: L.point(0, -20)});
                        layer.on('mouseover', function () {
                            layer.openPopup();
                        });
                        layer.on('mouseout', function () {
                            layer.closePopup();
                        });
                    }

                    layer.on('click', function () {
                        window.open(feature.properties.url, "_self");
                    });


                    if (feature.properties.state === "up") {
                        layer.setIcon(icons['ok']);
                    }
                    else {
                        layer.setIcon(icons['alert']);
                    }
                },

                updateFeature: function (feature, layer) {

                    if (!layer) {
                        return;
                    }

                    if (feature.properties && feature.properties.popupContent) {
                        layer.bindPopup(feature.properties.popupContent, {closeButton: false, offset: L.point(0, -20)});
                        layer.on('mouseover', function () {
                            layer.openPopup();
                        });
                        layer.on('mouseout', function () {
                            layer.closePopup();
                        });
                    }

                    layer.on('click', function () {
                        window.open(feature.properties.url, "_self");
                    });


                    if (feature.properties.state === "up") {
                        layer.setIcon(icons['ok']);
                    }
                    else {
                        layer.setIcon(icons['alert']);
                    }

                    return layer;
                }

            }).addTo(map<?php echo $mod['widget_id']; ?>);

        map<?php echo $mod['widget_id']; ?>.setView(<?php echo $leaflet_init; ?>);

        // disable scroll wheel by default, toggle by click on map
        map<?php echo $mod['widget_id']; ?>.scrollWheelZoom.disable();
        map<?php echo $mod['widget_id']; ?>.on('click', function () {
            if (map<?php echo $mod['widget_id']; ?>.scrollWheelZoom.enabled()) {
                map<?php echo $mod['widget_id']; ?>.scrollWheelZoom.disable();
            } else {
                map<?php echo $mod['widget_id']; ?>.scrollWheelZoom.enable();
            }
        });

        map<?php echo $mod['widget_id']; ?>.on('mouseover', function () {
            //console.log('STOPPING');
            realtime.stop();
            //console.log(realtime.isRunning());
        });

        map<?php echo $mod['widget_id']; ?>.on('mouseout', function () {
            //console.log('STARTING');
            realtime.start();
            //console.log(realtime.isRunning());
        });

        <?php if (isset($leaflet_hqurl))
        {
          echo "var tileUrl = (L.Browser.retina ? '$leaflet_hqurl' : '$leaflet_url');";
        }
        else
        {
          echo "var tileUrl = '$leaflet_url';";
        } ?>
        var layer = L.tileLayer(tileUrl, {
            detectRetina: true,
            tilematrixset: 'GoogleMapsCompatible_Level',
          <?php if (isset($leaflet_format)) {
            echo "format: '" . $leaflet_format . "',";
          } ?>
            attribution: '<?php echo $leaflet_copy; ?>'
        }).addTo(map<?php echo $mod['widget_id']; ?>);


        realtime.on('update', function () {

           if(realtime.isRunning())
           {
           <?php  echo $leaflet_bounds; ?>
            //map.fitBounds(realtime.getBounds(), {maxZoom: 3});
            //console.log('REBOUNDING');
           }
        });


    </script>

<?php

*/

function get_leaflet_init_and_bounds($config)
{
    if (is_numeric($config['frontpage']['map']['zoom']) &&
        is_numeric($config['frontpage']['map']['center']['lat']) &&
        is_numeric($config['frontpage']['map']['center']['lng'])) {
        $leaflet_init   = '[' . $config['frontpage']['map']['center']['lat'] . ', ' . $config['frontpage']['map']['center']['lng'] . '], ' . $config['frontpage']['map']['zoom'];
        $leaflet_bounds = '';
    } else {
        $leaflet_init   = '[0, -0], 2';
        $leaflet_bounds = 'map' . $mod['widget_id'] . '.fitBounds(realtime.getBounds(), { padding: [30, 30] });';
    }
    return [$leaflet_init, $leaflet_bounds];
}


function get_ssl_prefixed_url($url)
{
    return is_ssl() ? str_replace('http://', 'https://', $url) : $url;
}

function get_map_tiles_config($config)
{
    $tile_configs = [
      'esri-worldgraycanvas' => [
        'url'         => 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
        'attribution' => 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ'
      ],
      'opentopomap'          => [
        'url'         => 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        'attribution' => 'Map data: &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
      ],
      'osm-mapnik'           => [
        'url'         => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      ],
      'nasa-night'           => [
        'url'         => 'https://map1.vis.earthdata.nasa.gov/wmts-webmerc/VIIRS_CityLights_2012/default//GoogleMapsCompatible_Level8/{z}/{y}/{x}.jpg',
        'attribution' => 'Imagery provided by GIBS, operated by <a href="https://earthdata.nasa.gov">ESDIS</a>, funding by NASA/HQ.',
        'format'      => 'jpg',
      ],
      'wikimedia'            => [
        'url'         => 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png',
        'attribution' => '<a href="https://wikimediafoundation.org/wiki/Maps_Terms_of_Use">Wikimedia</a>',
      ]
    ];

    $tiles = $config['frontpage']['map']['tiles'];
    if (!isset($tile_configs[$tiles])) {
        $tiles = 'carto-base-auto';
    }

    if ($tiles === 'carto-base-auto') {
        $tiles = ($config['themes'][$_SESSION['theme']]['type'] === 'dark') ? "carto-base-dark" : "carto-base-light";
    }

    if (isset($tile_configs[$tiles])) {
        return $tile_configs[$tiles];
    }

    // Fallback to carto-base-dark or carto-base-light
    $leaflet_variant = ($tiles === "carto-base-dark") ? "dark_all" : "light_all";
    $url_base        = 'http://{s}.basemaps.cartocdn.com/' . $leaflet_variant . '/{z}/{x}/{y}';
    $url             = get_ssl_prefixed_url($url_base . '.png');
    $hqurl           = get_ssl_prefixed_url($url_base . '@2x.png');

    return [
      'url'         => $url,
      'hqurl'       => $hqurl,
      'attribution' => 'Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://carto.com/attributions">CARTO</a>'
    ];
}

[$leaflet_init, $leaflet_bounds] = get_leaflet_init_and_bounds($config);
$map_tiles = get_map_tiles_config($config);

//FIXME. Urgent! need escaping!
$vars['geojson_query_str'] = isset($vars['geojson_query']) ? http_build_query($vars['geojson_query']) : '';

?>

<script type="text/javascript">
    function initMap(widgetId) {
        const icons = {
            ok: L.icon({
                iconUrl: 'images/svg/ok.svg',
                popupAnchor: [0, 16],
                iconSize: [<?php echo $config['frontpage']['map']['okmarkersize']; ?>, <?php echo $config['frontpage']['map']['okmarkersize']; ?>]
            }),
            alert: L.icon({
                iconUrl: 'images/svg/high_priority.svg',
                popupAnchor: [0, 12],
                iconSize: [<?php echo $config['frontpage']['map']['alertmarkersize']; ?>, <?php echo $config['frontpage']['map']['alertmarkersize']; ?>]
            })
        };

        const url = 'ajax/geojson.php?<?php echo $vars['geojson_query_str']; ?>';

        const map = L.map(`map${widgetId}`);
        const realtime = createRealtimeLayer(url).addTo(map);

        map.setView(<?php echo $leaflet_init; ?>);
        map.scrollWheelZoom.disable();
        map.on('click', () => map.scrollWheelZoom.toggle());

        map.on('mouseover', () => realtime.stop());
        map.on('mouseout', () => realtime.start());

        const tileUrl = <?php echo(isset($map_tiles['hqurl']) ? "L.Browser.retina ? '{$map_tiles['hqurl']}' : '{$map_tiles['url']}'" : "'{$map_tiles['url']}'"); ?>;
        L.tileLayer(tileUrl, {
            detectRetina: true,
            tilematrixset: 'GoogleMapsCompatible_Level',
            attribution: '<?php echo $map_tiles['attribution']; ?>'
        }).addTo(map);

        realtime.on('update', () => {
            if (realtime.isRunning()) {
                <?php echo $leaflet_bounds; ?>
            }
        });

        function createRealtimeLayer(url) {
            const onEachFeature = (feature, layer) => {
                layer.on({
                    mouseover: showTooltip,
                    mouseout: hideTooltip
                });
                layer.on('click', () => window.open(feature.properties.url, "_self"));
                layer.setIcon(icons[feature.properties.state === "up" ? 'ok' : 'alert']);
            };

            const updateFeature = (feature, layer) => {
                if (!layer) {
                    return;
                }
                onEachFeature(feature, layer);
                return layer;
            };

            return L.realtime({
                url: url,
                method: 'POST',
                crossOrigin: true,
                type: 'json',
                getFeatureId: feature => feature.properties.id + feature.properties.state
            }, {
                interval: 10 * 1000,
                onEachFeature: onEachFeature,
                updateFeature: updateFeature
            });
        }
    }

    // Function to show the tooltip on 'mouseover'
    function showTooltip(e) {
        var layer = e.target;
        var lat = layer.feature.geometry.coordinates[1]; // Get latitude from GeoJSON
        var lon = layer.feature.geometry.coordinates[0]; // Get longitude from GeoJSON

        // Fetch the external HTML content using AJAX
        $.ajax({
            url: 'ajax/entity_popup.php', // Replace with your AJAX endpoint
            data: {
                entity_type: 'latlon',
                lat: lat,
                lon: lon
            },
            success: function (response) {
                // Create and show the qTip2 tooltip with the fetched HTML content
                $('body').qtip({
                    content: {
                        text: response
                    },
                    show: {
                        event: false, // Don't show on a regular event
                        ready: true // Show immediately upon creation
                    },
                    hide: {
                        event: 'mouseout'
                    },
                    style: {
                        classes: 'qtip-bootstrap',
                    },
                    position: {
                        target: [e.originalEvent.pageX, e.originalEvent.pageY],
                        adjust: {
                            x: 10, // Horizontal offset
                            y: 10 // Vertical offset
                        }
                    },
                    events: {
                        hidden: function (event, api) {
                            api.destroy(true); // Destroy the tooltip when hidden
                        }
                    }
                });
            },
            error: function (xhr, status, error) {
                console.log('Error fetching tooltip content:', error);
            }
        });
    }

    // Function to hide the tooltip on 'mouseout'
    function hideTooltip(e) {
        var layer = e.target;

        // Hide the qTip2 tooltip
        $(layer).qtip('hide');
    }

    initMap('<?php echo $mod['widget_id']; ?>');
</script>

