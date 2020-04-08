<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     map
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

register_html_resource('css', 'leaflet.css');
register_html_resource('js', 'leaflet.js');
register_html_resource('js', 'leaflet-realtime.js');
register_html_resource('css', 'MarkerCluster.css');
register_html_resource('css', 'MarkerCluster.Default.css');

//register_html_resource('js', 'leaflet.markercluster.js');
//register_html_resource('js', 'leaflet.featuregroup.subgroup');

//register_html_resource('js', '/geo.php');

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
  $leaflet_bounds = 'map.fitBounds(realtime.getBounds(), { padding: [30, 30] });';
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
  default:
    $leaflet_variant = ($config['frontpage']['map']['tiles'] == "carto-base-dark" ? "dark_all" : "light_all");
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

        var map = L.map('map'),
            realtime = L.realtime({
                url: 'geojson.php',
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

            }).addTo(map);

        map.setView(<?php echo $leaflet_init; ?>);

<?php
//  echo $leaflet_bounds;
 ?>

        /* disable scroll wheel by default, toggle by click on map */
        map.scrollWheelZoom.disable();
        map.on('click', function () {
            if (map.scrollWheelZoom.enabled()) {
                map.scrollWheelZoom.disable();
            } else {
                map.scrollWheelZoom.enable();
            }
        });

        map.on('mouseover', function () {
            //console.log('STOPPING');
            realtime.stop();
            //console.log(realtime.isRunning());
        });

        map.on('mouseout', function () {
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
        }).addTo(map);


        realtime.on('update', function () {

           if(realtime.isRunning())
           {
           <?php  echo $leaflet_bounds; ?>
            //map.fitBounds(realtime.getBounds(), {maxZoom: 3});
            //console.log('REBOUNDING');
           };
        });


    </script>

<?php

// EOF
