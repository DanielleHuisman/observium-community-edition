<?php
/**
 * visjs map for Observium
 *
 *   This file is a network overview widget for observium based on visjs.
 *
 * @author    Jens Brueckner <Discord: JTC#3678>
 * @copyright 'map.inc.php'	(C) 2022 Jens Brueckner 
 * @copyright 'Observium'	(C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

// observium includes
include('../includes/sql-config.inc.php');
include_once($config['html_dir'] . '/includes/functions.inc.php');
include_once($config['html_dir'] . '/includes/authenticate.inc.php');

/// FIXME. @mike review -
/// required simplify db queries and code style

// check if authenticated and user has global view ability
if ($_SESSION['userlevel'] >= 5) {

	// register javascript to this file
	register_html_resource('js', 'vis-network.min.js');

	// build navbar
	$navbar = array('brand' => "Network Map", 'class' => "navbar-narrow", 'style' => 'width: 100%;');

	// 'Port Labels' navbar menu
	$navbar['options']['port_labels']['text']	= 'Port Labels';
	$navbar['options']['port_labels']['id'] 	= 'toggle_labels';
	$navbar['options']['port_labels']['icon'] 	= $config['icon']['cef'];

	// 'Devices' navbar submenu
	$navbar['options']['devices']['text']  	= 'Devices';
	$navbar['options']['devices']['class'] 	= 'dropdown-scrollable';
	$navbar['options']['devices']['id'] 	= 'map-devices';
	$navbar['options']['devices']['icon'] 	= $config['icon']['devices'];

	// 'Locations' navbar submenu
	if ($config['show_locations']) {
		// save all locations into a array
		$locations = get_locations();

		// 'Locations' navbar menu
		$navbar['options']['locations']['text']  = 'Locations';

		// when the user selected a location: put this location into the navbar
		if ( isset($vars['location']) ) {
			$navbar['options']['location']['text'] = '('.var_decode($vars['location']['0']).')';
			$navbar['options']['locations']['class'] = 'active';
		} // endif

		$navbar['options']['locations']['class'] = 'dropdown-scrollable';
		$navbar['options']['locations']['icon'] = $config['icon']['location'];
		$navbar['options']['locations']['url'] = generate_url(array('page' => 'map'));

		// all locations into the navbar
		foreach ($locations as $location) {
			// if location is empty, substitute by OBS_VAR_UNSET as empty location parameter would be ignored
			$location_name = ($location === '' ? OBS_VAR_UNSET : escape_html($location));
			$location_url =  var_encode($location_name);

			$navbar['options']['locations']['suboptions'][$location_name]['text'] = $location_name;
			$navbar['options']['locations']['suboptions'][$location_name]['icon'] = $config['icon']['location'];
			$navbar['options']['locations']['suboptions'][$location_name]['url'] = generate_url(array('page' => 'map', 'location' => $location_url));
		} // endforeach
	} // endif

	// 'Groups' navbar submenu

	// save all groups into a array
	$groups = get_groups_by_type();

	$navbar['options']['groups']['text']  = 'Groups';
	$navbar['options']['groups']['class'] = 'dropdown-scrollable';
	$navbar['options']['groups']['icon'] = $config['icon']['group'];
	$navbar['options']['groups']['url'] = generate_url(array('page' => 'map'));

	// all groups into the navbar
	foreach($groups['device'] AS $group_id => $group) {
	
		// when the user selected a group: put this group into the navbar
		if( $vars['group_id'] == $group_id ) {
			$navbar['options']['groups']['text'] .= ' ('.$group['group_name'].')';
			$navbar['options']['groups']['class'] = 'active';
		} // endif

		$navbar['options']['groups']['suboptions'][$group_id]['text'] = $group['group_name'];
		$navbar['options']['groups']['suboptions'][$group_id]['url']  = generate_url($vars, ['group_id' => $group_id, 'device_id' => NULL]);
	} // endforeach

	// define options for visjs
	$options = '{
			configure: {
				enabled: false,
				showButton: false,
			},
			nodes: {
				shapeProperties: {
						interpolation: false,
				},
			},
			edges: {
				smooth: { 
					enabled: true,
					type: "dynamic",
					roundness: 0,
				},
				font: { 
					color: "#343434",
					strokeColor: "#ffffff",
				},
			},
			layout: {
				improvedLayout: false,
			},
			physics: {
				enabled: true,
				maxVelocity: 146,
				solver: "forceAtlas2Based",
				timestep: 0.35,
				stabilization: { 
					enabled: false, 
					iterations: 950, 
					updateInterval: 25,
					onlyDynamicEdges: false, 
					fit: true 
				},
				forceAtlas2Based: {
					gravitationalConstant: -30,
					centralGravity: 0.002,
					springLength: 225,
					springConstant: 0.025,
					avoidOverlap: 0,
				},
				barnesHut: {
					gravitationalConstant: -8000,
					centralGravity: 0.3,
					springLength: 225,
					springConstant: 0.04,
					damping: 0.09,
					avoidOverlap: 0.5,
				},
			},
			interaction: {
				tooltipDelay: 3600000,
				hideEdgesOnDrag: false,
				hideEdgesOnZoom: false,
				selectConnectedEdges: true,
				navigationButtons: true,
			},
			manipulation: {
				enabled: false,
				addNode: false,
				deleteNode: false,
				initiallyActive: false,	
			}
        };';

	// pre define some later used arrays
	$links = [];
	$ports = [];
	$devices = [];
	$devices_by_id = [];
	$link_seen = [];

	// get neighbours with port data from sql
	// note: only ports with operational status 'up', neighbour say's active and it's NOT a LAG (bc we want physical links)
	$ports_sql = '	SELECT 
						`neighbours`.`active` as `active`,
						`neighbours`.`neighbour_id` as `neighbour_id`, 
						`neighbours`.`device_id` as `local_device_id`, 
						`neighbours`.`port_id` as `local_port_id`, 
						`neighbours`.`remote_port_id` as `remote_port_id`, 
						`neighbours`.`remote_hostname` as `remote_hostname`,
						`neighbours`.`remote_port` as `remote_port`, 
						`d1`.`hostname` as `local_hostname`, 
						`p1`.`ifName` as `local_ifname`, 
						`p1`.`ifSpeed` as `local_ifspeed`, 
						`p1`.`ifOperStatus` as `local_ifstatus`, 
						`p1`.`ifType` as `localiftype`, 
						`p1`.`ifVlan` as `local_ifvlan`,
						`p1`.`ifInOctets_perc` as `local_ifInOctets_perc`,
						`p1`.`ifOutOctets_perc` as `local_ifOutOctets_perc`,
						`p2`.`device_id` as `remote_device_id`,
						`p2`.`ifName` as `remote_ifname`, 
						`p2`.`ifSpeed` as `remote_ifspeed`, 
						`p2`.`ifOperStatus` as `remote_ifstatus`, 
						`p2`.`ifType` as `remote_iftype`, 
						`p2`.`ifVlan` as `remote_ifvlan`
					FROM neighbours
					JOIN devices d1 ON `neighbours`.`device_id` = `d1`.`device_id`
					JOIN ports p1 on `neighbours`.`port_id` = `p1`.`port_id`
					JOIN ports p2 on `neighbours`.`remote_port_id` = `p2`.`port_id`
					WHERE 
						(`d1`.`device_id` <> `neighbours`.`neighbour_id`) AND
						(`active` = ?);
				';
	$ports_sql_params = [  '1'];
	$ports = dbFetchRows($ports_sql, $ports_sql_params);

	// foreach all links
	foreach ($ports as $link) {

		// check if a device is choosen by the dropdown and save only these device links (depth = 1)
		if( isset($vars['device']) ) {	
			// check if the port is connected to the choosen device
			if (! ($link['local_device_id'] === $vars['device']) || ($link['remote_device_id'] === $vars['device']) ) {
				continue;
			} // endif
		} // endif

		// check if the link is a loop on the same device (e.g. internal mgmt links)
		if ($link['local_device_id'] == $link['remote_device_id']) {
			continue;
		} // endif

		// define link port side from $ports array
		$link_by_id_local = $link['local_port_id'] . ':' . $link['remote_port_id'];
		$link_by_id_remote = $link['remote_port_id'] . ':' . $link['local_port_id'];

		// define link colors for activity indication
		$link_style_unused = '#000000'; // light grey
		$link_style_normal = '#a9a9a9'; // dark grey
		$link_style_warn   = '#FFFF00'; // yellow
		$link_style_alert = '#FB7E81'; // light red

		// ToDo : using something like weathermap scale
		/*
		SCALE DEFAULT 0    0    192 192 192  
		SCALE DEFAULT 0    1    255 255 250  
		SCALE DEFAULT 1    10   140   0 255  
		SCALE DEFAULT 10   25    32  32 255  
		SCALE DEFAULT 25   40     0 192 255  
		SCALE DEFAULT 40   55     0 240   0  
		SCALE DEFAULT 55   70   240 240   0  
		SCALE DEFAULT 70   85   255 192   0  
		SCALE DEFAULT 85   95   255   0 144  
		SCALE DEFAULT 95   100  163   1   1
		*/

		// check if the link is already in the links array for visjs or has another side
		if (! array_key_exists($link_by_id_local, $link_seen) &&
			! array_key_exists($link_by_id_remote, $link_seen) ) {

			// define link speed width
			$link_speed = $link['local_ifspeed'] / 1000 / 1000;
			if ($link_speed > 500000) {
				$width = 0.5;
			} else {
				$width = round(0.14 * ($link_speed ** 0.30));
			} // endif

			// define the link color depends on traffic
			if ($link['local_ifInOctets_perc'] < 50 || $link['local_ifOutOctets_perc'] < 50) {
				$link_style = $link_style_normal;
			} elseif ($link['local_ifInOctets_perc'] > 50 || $link['local_ifOutOctets_perc'] > 50) {
				$link_style = $link_style_warn;
			} elseif ($link['local_ifInOctets_perc'] > 90 || $link['local_ifOutOctets_perc'] > 90) {
				$link_style = $link_style_alert;
			} else {
				$link_style = $link_style_unused;
			} // endif

			// define the link color if the link is on both sides down
			if ( $link['local_ifstatus'] == 'down' && $link['remote_ifstatus'] == 'down' ) {
				$link_style = $link_style_unused;
			}

			// Mike: FIXME. What hell is this? 
			// Jens: It's an ongoing multidimensional array merge. No FIXME needed.
			$links[] = array_merge_recursive(
				[
					'from'			=> $link['local_device_id'],
					'to'			=> $link['remote_device_id'],
					'localPortId' 	=> $link['local_port_id'],
					'remotePortId' 	=> $link['remote_port_id'],
					'label'			=> $link['local_ifname'] . " <> " . $link['remote_ifname'],
					'width'			=> $width,
					'color' 		=> $link_style,
				]
			);
		} // endif

		$link_seen[$link_by_id_local] = 1;
		$link_seen[$link_by_id_remote] = 1;
	} // endforeach

	// get device data with groups from sql : only switches and firewalls
	$device_sql = '	SELECT 
						`devices`.`device_id` AS `device_id`, 
						`devices`.`hostname` AS `hostname`, 
						`devices`.`type` AS `type`, 
						`devices`.`disabled` AS `disabled`, 
						`devices`.`location` AS `location`,
						`devices`.`status` AS `status`
					FROM devices
					WHERE (`type` = ? OR `type` = ?) 
					ORDER BY `hostname` ASC;
				';		
	$device_params = [ 'network', 'firewall' ];
	$devices = dbFetchRows($device_sql, $device_params);

	// define node colors for status
	$node_style_normal = '#97C2FC';
	$node_style_down   = '#FB7E81';
	$node_style_disabled = '#919191';

	// foreach all devices
	foreach ($devices as $device) {
		// define local_device_id from $devices array
		$local_device_id = $device['device_id'];

		// define local_device_location from $devices array
		$local_device_location = $device['location'];

		// define local device_status from $devices array
		$device_status = $device['status'];

		// define local device_hostname as short_hostname from $devices array
		$device_hostname = short_hostname($device['hostname']);

		// when the user selected a location: check if the device is member in this location or don't put it into the array by continue the foreach
		if ( isset($vars['location']) ) {
			$location_choose = var_decode($vars['location']);
			if( !( in_array($local_device_location, $location_choose) ) ) {
				continue;
			} // endif
		} // endif
		
		// check if the device got any link
		if ( !(array_search($local_device_id, array_column($ports, 'local_device_id'))) && !(array_search($local_device_id, array_column($ports, 'remote_device_id'))) ) {
			// check if the user did NOT pressed the 'show all' button
			if ( !isset($vars['showall']) ) {
				// enable the 'show all' button and continue to not display the device without link
				$menu_showall_enable = true;
				continue;
			} else {
				// disable the 'show all' button if the 'show all' button is pressed and do not skip : display all network devices on the map
				$menu_showall_enable = false;
			} // endif
		} // endif

		// get the group membership from the database
		$group_sql = "	SELECT
							`group_id`
						FROM `group_table`
						WHERE (`entity_type` = 'device' AND `entity_id` = ?);
					";
		$group_params = [ $local_device_id ];
		$device_groups = dbFetchRows($group_sql, $group_params);

		// when the user selected a group: check if the device is member in this group or don't put it into the array by continue the foreach
		if ( isset($vars['group_id']) ) {
			// check if the device has any group or continue
			if ( sizeof($device_groups) == 0 ) {
				continue;
			} else {
				foreach ($device_groups as $device_group_ids => $device_group_id) {
					if( !( $vars['group_id'] == $device_group_id['group_id'] ) ) {
						continue 2; // ( skip outer loop )
					} // endif
				} // endforeach
			}
		} // endif

		// insert the device for filtering in the $navbar dropdown
		$navbar['options']['devices']['suboptions'][$local_device_id]['text'] = $device_hostname;
		$navbar['options']['devices']['suboptions'][$local_device_id]['link_opts'] = 'data-deviceid=' . $local_device_id;

		// define the node color depends on device status
		if ($device['disabled']) {
			$node_style = $node_style_disabled;
		} elseif ($device_status == '0') {
			$node_style = $node_style_down;
		} else {
			$node_style = $node_style_normal;
		} // endif

		// check if the device is already in the device_by_id array for visjs or add it to the nodes
		if (! array_key_exists($local_device_id, $devices_by_id)) {
			$devices_by_id[$local_device_id] = [
				'id'    => $local_device_id,
				'label' => $device_hostname,
				'shape' => 'image',
				'image'	=> 'img/router.png',
				'url'   => 'device/device=' . $local_device_id,
				'color' => $node_style
			];
		} // endif
	} // endforeach

	// json encode the links array
	$links = json_encode($links, JSON_UNESCAPED_UNICODE);

	// json encode the devices_by_id array
	$devices_by_id = json_encode(array_values($devices_by_id), JSON_UNESCAPED_UNICODE);

	// export network map data
	$navbar['options_right']['export'] = array('text' => 'Export Data', 'url' => 'map/export');

	// show all devices (default: only devices with links), but ONLY when there are hidden devices
	if ( $menu_showall_enable ) {
		$navbar['options_right']['hiddennodes'] = array('text' => 'Show all', 'url' => 'map/showall');
	}

	// re-arrange button and enable node_cache usage (in JS / local browser storage) only when no location or group is choosen
	if( !isset($vars['location']) && !isset($vars['group_id']) && !isset($vars['showall']) ) {
		$navbar['options_right']['re-arrange'] = array('text' => 'Re-Arrange', 'id' => 're-arrange');
		$enable_node_cache = true;
	} // endit

	// 'reload' page button
	$navbar['options_right']['reset'] = array('text' => 'Reload Page', 'url' => generate_url(array('page' => 'map')));

	// for debugging purpose
	if ( isset($vars['export']) ) {

		// define the tempfile path and name
		$map_tmp_path = tempnam(sys_get_temp_dir(), 'map_debug_data-');

		// open the temp file
		$map_tmp_file = fopen($map_tmp_path, 'w') or die('Export not possible.');

		// combine all data for the export
		$map_debug_data = print_r($ports, true) . PHP_EOL . print_r($devices, true) . PHP_EOL . $links . PHP_EOL . $devices_by_id;

		// write the export data to the tempfile
		fwrite($map_tmp_file, $map_debug_data);

		// close the temp file data stream
		fclose($map_tmp_file);

		// set a new header on the page for the debug file
		header('Content-Description: File Transfer');
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename=mapdebug.txt');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($map_tmp_path));

		// clear the previous output to get a clean file without html from observium
		ob_clean(); flush();
	
		// download the file
		readfile($map_tmp_path);
	
		// remove the file
		unlink($map_tmp_path);

		// end the script here
		exit;
	} // endif

	// check if the final arrays are empty > display error
	if ( (empty(json_decode($devices_by_id, true))) || (empty(json_decode($links, true))) ) { 
		print_error('No map to display, maybe you are not running autodiscovery or no devices are linked. Download the debug file <a href="'. generate_url(array('page' => 'map')) .'export">here</a>.');
		if ( isset($vars['group_id']) || isset($vars['location']) ) {
			print_warning(' <a href="'. generate_url(array('page' => 'map')) .'">Reload Page</a> ' );
		}
		exit;
	} // endif
} else {
	// not authorized.
	// ToDo: More error handling ?
	print_error('You are not authorized!');
	exit;
} // endif
?>
<html>
<head>
<!-- <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script> -->
<!-- https://github.com/visjs/vis-network -->
    <style type="text/css">
		#wrapper {
			display: flex;
			position: relative;
            width: 100%;
            height: calc(100vh - 192px);
        }
        #map {
			position: absolute;
            width: 100%;
            height: 100%;
			border: 1px solid lightgray;
        }
		#loadingText {
			display: flex;
			height: 100%;
			width: 100%;
			justify-content: center;
			align-items: center;
			display: -webkit-box;
			display: -webkit-flex;
			display: -moz-box;
			display: -ms-flexbox;
			display: flex;
			-webkit-flex-align: center;
			-ms-flex-align: center;
			-webkit-align-items: center;
			font-size: 22px;
			color: #000000;
		}

		div.vis-tooltip {
			background: none;
            border: none;
            box-shadow: none;
			white-space: initial;
			word-wrap: break-word;
			min-width: 700px;
			max-width: 750px;
		}

		div.vis-network div.vis-navigation div.vis-button.vis-up, 
		div.vis-network div.vis-navigation div.vis-button.vis-down, 
		div.vis-network div.vis-navigation div.vis-button.vis-left, 
		div.vis-network div.vis-navigation div.vis-button.vis-right, 
		div.vis-network div.vis-navigation div.vis-button.vis-zoomIn, 
		div.vis-network div.vis-navigation div.vis-button.vis-zoomOut, 
		div.vis-network div.vis-navigation div.vis-button.vis-zoomExtends {
			background-image: none !important;
		}

		div.vis-network div.vis-navigation div.vis-button:hover {
			box-shadow: none !important;
		}

		.vis-button:after {
			font-size: 2em;
			color: gray;
		}

		.vis-button:hover:after {
			font-size: 2em;
			color: lightgray;
		}

		.vis-button.vis-up:after {
			content: "\2191";
		}

		.vis-button.vis-down:after {
			content: "\2193";
		}

		.vis-button.vis-left:after {
			content: "\2190";
		}

		.vis-button.vis-right:after {
			content: "\2192";
		}

		.vis-button.vis-zoomIn:after {
			content: "\002B";
		}

		.vis-button.vis-zoomOut:after {
			content: "\002D";
		}

		.vis-button.vis-zoomExtends:after {
			content: "\003D";
		}
    </style>
</head>
<body>

	<?php print_navbar($navbar); ?>
	<div id="wrapper">
		<div id="loadingText">Loading... 0%</div>
		<div id="map"></div>
	</div>

	<script type="text/javascript">

	// get the device popup
		async function fetch_device_popup(device_id) {
			const container = document.createElement('div');
			let response = await fetch('/ajax/entity_popup.php', {
                method: 'POST',
                body: JSON.stringify({
                    entity_type: 'device',
                    entity_id: device_id,
                }),
                headers: {
                    'Content-Type': 'application/json',
                },
            });
			let responseText = await response.text();
			
			container.innerHTML = '<div class="qtip-bootstrap box box-solid"><div class="box-body">' + responseText + '</div></div>';
			return container;
		} // endfunction
		
	// get the link popup
		async function fetch_link_title(local_port_id, remote_port_id) {
            const container = document.createElement('div');
            let response = await fetch('/ajax/entity_popup.php', {
                method: 'POST',
                body: JSON.stringify({
                    entity_type: "link",
                    entity_id_a: local_port_id,
                    entity_id_b: remote_port_id,
                }),
                headers: {
                    'Content-Type': 'application/json',
                },
            });
            let responseText = await response.text();

            container.innerHTML = '<div class="qtip-bootstrap box box-solid"><div class="box-body">' + responseText + '</div></div>';
            return container;
        } // endfunction

	// create an array with nodes
		var nodes = new vis.DataSet(
			<?php echo $devices_by_id; ?>
		);
		
	// create an array with edges
		var edges = new vis.DataSet(
			<?php echo $links; ?>
		);
		
	// provide the data in the vis format
		var data = {
			nodes: nodes,
			edges: edges
		};
		
	// define required variables
		var rearrange_button = document.getElementById('re-arrange');
		
	// define performance measurement variables
		const start = 0;
		const duration = 0;
		
	// define node cache option
		var enable_node_cache = <?php echo json_encode($enable_node_cache); ?>

	// create a network
		var container = document.getElementById('map');	
		var options =  <?php echo $options; ?>
		
	// define variable for hidden labels : make it toggle
		var hiddenEdgeTextOptions = {
			edges: {
				font: {
					// Set the colors to transparent
					color: 'transparent',
					strokeColor: 'transparent'
				}
			}
		};

	// check if the node positions is local stored and insert it before the initialization starts
	// only when node_cache is enabled
		if ( localStorage.getItem('map') !== null && enable_node_cache === true) {
			console.log('loading from cache..');
			
			// get node position from cache
			const nodePositions = JSON.parse(localStorage.getItem('map'));
			
			// update node position in the dataset
			nodePositions.forEach( nodePosition => data.nodes.update(nodePosition) )
			
			// when nodes already present change option to run physics only for the edges
			//options.physics.stabilization.onlyDynamicEdges = true;
		}; // endif

	// initialize network
		var network = new vis.Network(container, data, options);
		
	// run physics once to space out the nodes
		network.stabilize();

	// re-arrange the map if available
		if ( rearrange_button !== null && rearrange_button !== 'undefined' ) {
			rearrange_button.addEventListener('click', initGraph)
		} // endif
		
	// delete cached node position and reload the page
		function initGraph() {
			localStorage.clear('map');
			location.reload();
		}; // endfunction
		
	// save the map layout local
		function saveGraph() {
			network.storePositions()
			const nodePositions = data.nodes.map(({ id, x, y }) => ({ id, x, y }));

			localStorage.setItem('map', JSON.stringify(nodePositions));

			if ( localStorage.getItem('map') !== null ) {
				console.log('autosave done');
			} else {
				console.log('autosave failed');
			};
		}; // endfunction

	// limit zooming
		let MIN_ZOOM = 0.2
		let MAX_ZOOM = 10.0
		let lastZoomPosition = {x:0, y:0}

		network.on('zoom',function(params){
			let scale = network.getScale()
			if(scale <= MIN_ZOOM )
			{
				network.moveTo({
					position: lastZoomPosition,
					scale: MIN_ZOOM
				});
			}
			else if(scale >= MAX_ZOOM ){
				network.moveTo({
					position: lastZoomPosition,
					scale: MAX_ZOOM,
			   });
			 }
			 else{
				lastZoomPosition = network.getViewPosition()
			 }
		}); // endfunction

		network.on('dragEnd',function(params){
			lastZoomPosition = network.getViewPosition()
		}); // endfunction

	// node url double click event
		network.on('doubleClick', function (params) {
			if (params.nodes.length === 1) {
				var node = nodes.get(params.nodes[0]);
				if(node.url != "") {
					window.open(node.url, '_blank');
				} // endif
			} // endif
		});	 // endfunction
		
	// load node details on click
		network.on('selectNode', function (params) {
			var id = params.nodes[0];
			var node = nodes.get(id);
			if (node) {
				(async() => {
					response = await fetch_device_popup(node.id);
						// update the title part from the node
						nodes.update({id:node.id, title:response});
						 // check if you clicked on a node; if so, display the title (if any) in a popup
						network.interactionHandler._checkShowPopup(params.pointer.DOM);
				})();
            } // endif
		}); // endfunction
		
	// load link details on click
		network.on('selectEdge', function (params) {
			var id = params.edges[0];
			var edge = edges.get(id);
			if (edge) {
				(async() => {
					response = await fetch_link_title(edge.localPortId, edge.remotePortId);
						// update the title part from the edge
						edges.update({id:edge.id, title:response});
						// check if you clicked on a edge; if so, display the title (if any) in a popup
						network.interactionHandler._checkShowPopup(params.pointer.DOM);
				})();
            } // endif
		}); // endfunction
		
	// when the canvas generation starts: loading function, necessary especially for big maps
		network.on('stabilizationProgress', function (params) {
			// start performance measurement
			const start = performance.now();
			
			// show loading text
			document.getElementById('loadingText').style.opacity = 1;

			var maxWidth = 100;
			var minWidth = 0;
			var widthFactor = params.iterations / params.total;
			var width = Math.max(minWidth, maxWidth * widthFactor);
			document.getElementById('loadingText').innerHTML = 'Loading... ' + Math.round(widthFactor * 100) + '%';
		}); // endfunction

	// when the canvas is finished:
		network.on('stabilizationIterationsDone', function () {
			// hide loading text
			document.getElementById('loadingText').style.opacity = 0;
			
			// stop performance measurement
			const duration = performance.now() - start;
			console.log('loading time: ' + duration / 1000);

			// save current layout if local cache is empty
			if ( localStorage.getItem('map') === null && enable_node_cache === true ) {
				console.log('saving map to local cache...');
				saveGraph();
			} // endif
		}); // endfunction

	// zoom into the selected device from dropdown
		$( document ).ready( function() {			
			$('.dropdown').each(function (key, dropdown) {
				var $dropdown = $(dropdown);
				$dropdown.find('.dropdown-menu a').on('click', function () {
					var nodeId = ($(this).data('deviceid'));
					network.focus( nodeId, { scale: 1.0 } );
				});
			});
		}); // endfunction

	// show or hide labels : default false

		var displayLabels = false;
		network.setOptions(hiddenEdgeTextOptions);

		document.getElementById('toggle_labels').onclick = function() {
			if(displayLabels){
				// apply options for hidden edge text
				// this will override the existing options for text color
				// This does not clear other options (e.g. node.color)
				network.setOptions(hiddenEdgeTextOptions);
				displayLabels = false;
			} else {
				// apply standard options
				network.setOptions(options);
				displayLabels = true;
			}
		};

	</script>
</body>
</html>

<?php

// clean up
unset($navbar); 

// EOF