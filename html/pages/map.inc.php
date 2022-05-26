<?php
/**
 * visjs map for observoum
 *
 *   This file is a network overview widget for observium based on visjs.
 *
 * @author                      Jens Brueckner <Discord: JTC#3678>
 * @copyright 'map.inc.php'	(C) 2022 Jens Brueckner
 * @copyright 'Observium'	(C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */
 
// observium includes
include("../includes/sql-config.inc.php");
include_once($config['html_dir'] . "/includes/functions.inc.php");
include_once($config['html_dir'] . "/includes/authenticate.inc.php");

// check if authenticated and user has global view ability
if($_SESSION['userlevel'] > 5) {

	// ToDo: not functional yet
	// export function for debugging purpose
	function export($array, $filename = "export.csv") {
		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'";');

		// open the "output" stream
		// see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
		$f = fopen('php://output', 'w');

		fputcsv($array);
	}

	// register javascript to this file
	register_html_resource('js', 'vis-network.min.js');

	// define options for visjs
	$options = '{
          nodes: nodes,
          edges: edges,
        };
        var options = {
          nodes: {
			shape: "dot",
            size: 16
          },
          physics: {
            forceAtlas2Based: {
              gravitationalConstant: -25,
              centralGravity: 0.005,
              springLength: 225,
              springConstant: 0.18,
            },
            maxVelocity: 50,
            solver: "forceAtlas2Based",
            timestep: 0.35,
            stabilization: { iterations: 850, fit: true },
          },
        };';

	// pre define arrays
	$links = [];
	$ports = [];
	$devices = [];
	$devices_by_id = [];
	$link_seen = [];

	// get neighbours and port data from sql
	// note: only ports with operational status 'up'
	$portssql = '	SELECT 
						neighbours.neighbour_id as neighbour_id, 
						neighbours.device_id as local_device_id, 
						neighbours.port_id as local_port_id, 
						neighbours.remote_port_id as remote_port_id, 
						neighbours.remote_hostname as remote_hostname,
						neighbours.remote_port as remote_port, 
						d1.hostname as local_hostname, 
						d2.device_id as remote_device_id,
						p1.ifName as local_ifname, 
						p1.ifSpeed as local_ifspeed, 
						p1.ifOperStatus as local_ifstatus, 
						p1.ifType as localiftype, 
						p1.ifVlan as local_ifvlan,
						p2.ifName as remote_ifname, 
						p2.ifSpeed as remote_ifspeed, 
						p2.ifOperStatus as remote_ifstatus, 
						p2.ifType as remote_iftype, 
						p2.ifVlan as remote_ifvlan
					FROM neighbours
					JOIN devices d1 ON neighbours.device_id = d1.device_id
					JOIN devices d2 ON d2.sysname = neighbours.remote_hostname COLLATE utf8_general_ci
					JOIN ports p1 on neighbours.port_id = p1.port_id
					JOIN ports p2 on neighbours.remote_port_id = p2.port_id
					WHERE 
						d1.device_id <> neighbours.neighbour_id AND
						(p1.ifOperStatus = "up" AND p2.ifOperStatus = "up");
				';
	$ports = dbFetchRows($portssql);

	// get device data from sql : only switches and firewalls
	$devicessql = 'SELECT device_id as device_id, hostname as device_label, type as device_type, status as device_status FROM devices WHERE type="network" OR type="firewall"';
	$devices = dbFetchRows($devicessql);

	// define node styles
	$node_style_normal = "#97C2FC";
	$node_style_down = "#FB7E81";
	
	// foreach all devices
	foreach ($devices as $device ) {
		// define local_device_id from $devices array
		$local_device_id = $device["device_id"];
		// define local device_status from $devices array
		$device_status = $device["device_status"];

		if ( $device_status == "0" ) {
			$node_style = $node_style_down;
		} else {
			$node_style = $node_style_normal;
		}

		// check if the device is already in the device_by_id array for visjs
		if (! array_key_exists($local_device_id, $devices_by_id)) {
			$devices_by_id[$local_device_id] = [
				'id'=>$local_device_id,
				'label'=>$device["device_label"],
				'shape'=>'box',
				'image'=>'weathermap/images/Aruba-Switch.png',
				'url' => "device/device=".$local_device_id,
				'color' =>$node_style,
				$node_style
			];
		} // endif
		
	} //endforeach

	// foreach all links
	foreach ($ports as $link) {
		
		// first check if the link is a loop on the same device (e.g. mgmt links)
		if ($link['local_device_id'] == $link['remote_device_id']) {
			continue;
		}

		// define link port side from $ports array
		$link_by_id_local = $link['local_port_id'] . ':' . $link['remote_port_id'];
		$link_by_id_remote = $link['remote_port_id'] . ':' . $link['local_port_id'];

		// check if the link is already in the links array for visjs or has another side
		if (! array_key_exists($link_by_id_local, $link_seen) &&
			! array_key_exists($link_by_id_remote, $link_seen) ) {

			// define link speed width
			$linkspeed = $link['local_ifspeed'] / 1000 / 1000;
			if ($linkspeed > 500000) {
				$width = 0.5;
			} else {
				$width = round(0.11 * pow($linkspeed, 0.30));
			}

			// define links details to seperate variables
			$linkid = $link["local_port_id"];
			$linkname = $link["local_hostname"].'-'.$link["remote_hostname"];
			$linktype = $link["remote_iftype"];
			$linkstatus = $link["local_ifstatus"];

			// check if allowed vlans is empty (maybe bc it's part from a lag)
			if($link["local_ifvlan"]) {
				$linkvlans = $link["local_ifvlan"];
			} else {
				$linkvlans = "none";
			}
			$linkspeed = formatRates($link["local_ifspeed"]);
			$leftnode = $link["local_hostname"];
			$leftport = $link["local_ifname"];
			$rightnode = $link["remote_hostname"];
			$rightport = $link["remote_ifname"];

			// building link details
			$title = '#!!htmlTitle("
			<div class="box-body no-padding">

			<table class="table table-striped table-rounded table-condensed">
			<tr>
				<td>Link ID:</td>
				<td><b>'.$linkid.'</b></td>
			</tr>
			<tr>
				<td>Link Name:</td>
				<td>'.$linkname.'</td>
			</tr>
			<tr>
				<td>Link Type:</td>
				<td>'.$linktype.'</td>
			</tr>
			<tr>
				<td>Link Status:</td>
				<td>'.$linkstatus.'</td>
			</tr>
			<tr>
				<td>Allowed VLANs:</td>
				<td>'.$linkvlans.'</td>
			</tr>
			<tr>
				<td>Link Speed:</td>
				<td>'.$linkspeed.'</td>
			</tr>
			<tr>
				<td>Left Node:</td>
				<td>'.$leftnode.'</td>
			</tr>
			<tr>
				<td>Left Port:</td>
				<td>'.$leftport.'</td>
			</tr>
			<tr>
				<td>Right Node:</td>
				<td>'.$rightnode.'</td>
			</tr>
			<tr>
				<td>Right Port:</td>
				<td>'.$rightport.'</td>
			</tr>
			</table>
			</div>
			<div style="width: 840px">
				<br />
				Traffic:
				<br />
				<img src=\'graph.php?height=100&width=512&id='.$link["local_port_id"].'&type=port_bits&legend=no&from="-1d"\' style="max-width: 50%; width: auto; vertical-align: top;" alt=""/>
				<img src=\'graph.php?height=100&width=512&id='.$link["local_port_id"].'&type=port_bits&legend=no&from=-7d&to=-1d\' style="max-width: 50%; width: auto; vertical-align: top;" alt=""/>
				<br />
				Ethernet Errors:
				<br />
				<img src=\'graph.php?height=100&width=512&id='.$link["local_port_id"].'&type=port_etherlike&legend=no&from="-1d"\' style="max-width: 50%; width: auto; vertical-align: top;" alt=""/>
				<img src=\'graph.php?height=100&width=512&id='.$link["local_port_id"].'&type=port_etherlike&legend=no&from=-7d&to=-1d\' style="max-width: 50%; width: auto; vertical-align: top;" alt=""/>
			</div>
			")!!#';

			$links[] = array_merge(
				[
					'from'=>$link["local_device_id"],
					'to'=>$link["remote_device_id"],
					'title'=>$title,
					'width'=>$width,
				]
			);

		} // endif

		$link_seen[$link_by_id_local] = 1;
		$link_seen[$link_by_id_remote] = 1;
	} // endforeach

	// post cleanup for $links array to decode the js title
	$links = json_encode($links, JSON_UNESCAPED_UNICODE);
	$links = str_replace('"#!!','',$links);
	$links = str_replace('!!#"','',$links);
	$links = str_replace('(\"','("',$links);
	$links = str_replace('\")','")',$links);
	
	// ToDo: not functional yet
	// Check if url:?export exists for debugging purpose
	if( isset($_GET['export']) ) {
		//export($links);
	}

	// check if the arrays are empty > display error
	if ( (count($devices) < 1) && (count($ports) < 1) ) { 
		print_error("No map to display, maybe you aren't running autodiscovery or no devices are linked.");
		exit;
	}

} else {

	// Not Authenticated. Print login.
	// ToDo: Error Handling
	echo "You are not authenticated!";
	exit;

} // endif
?>
<html>
<head>
<!-- <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script> -->
    <style type="text/css">
		#wrapper {
			display: flex;
            width: 100%;
            height: calc(100vh - 150px);
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
			font-size: 14px;
			line-height: 20px;
			color: #333333;
			width: 860px;
			min-width: 128px;
			padding: 1px;
			background: #fff;
				background-clip: border-box;
			border: 1px solid rgba(0, 0, 0, 0.15);
			-webkit-border-radius: 3px;
			-moz-border-radius: 3px;
			border-radius: 3px;
			box-shadow: 0 0 2px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.24);
			-webkit-background-clip: padding-box;
			-moz-background-clip: padding;
			background-clip: padding-box;
		}
    </style>
</head>
<body>
	<div id="wrapper">
		<div id="loadingText">Loading... 0%</div>
		<div id="map"></div>
	</div>

	<script type="text/javascript">
		// html parsing for link details
		function htmlTitle(html) {
			const container = document.createElement("div");
			container.innerHTML = html;
			return container;
		}

		// create an array with nodes
		var nodes = new vis.DataSet(
			<?php echo json_encode(array_values($devices_by_id)); ?>
		);
		
		// create an array with edges
		var edges = new vis.DataSet(
			<?php echo $links; ?>
		)

		// provide the data in the vis format
		var data = {
			nodes: nodes,
			edges: edges
		};

		// create a network
		var container = document.getElementById('map');	
		
		var options =  <?php echo $options; ?>

		// initialize your network!
		var network = new vis.Network(container, data, options);
		
		// Run physics once to space out the nodes.
		//network.stabilize();

		// node url double click event
		network.on("doubleClick", function (params) {
			if (params.nodes.length === 1) {
				var node = nodes.get(params.nodes[0]);
				if(node.url != null) {
					window.open(node.url, '_blank');
				}
			}
		});	
		
		// loading function, necessary especially for big maps
		network.on("stabilizationProgress", function (params) {
			document.getElementById('loadingText').style.opacity = 1;

			var maxWidth = 1024;
			var minWidth = 20;
			var widthFactor = params.iterations / params.total;
			var width = Math.max(minWidth, maxWidth * widthFactor);

			document.getElementById('loadingText').innerHTML = 'Loading... ' + Math.round(widthFactor * 100) + '%';
		});

		network.on("stabilizationIterationsDone", function () {
			document.getElementById('loadingText').style.opacity = 0;

			setTimeout(function () {
				document.getElementById("loadingText").style.display = "none";
			}, 500);
		});
	</script>
</body>
</html>
