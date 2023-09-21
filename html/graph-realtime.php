<?php
/**
 * Observium
 *
 *   This file is included with Observium. It was originally part of m0n0wall <http://www.m0n0.ch/wall/>
 *
 * @package    observium
 * @subpackage graphing
 * @author     T. Lechat <dev@lechat.org>, Manuel Kasper <mk@neon1.net>, Jonathan Watt <jwatt@jwatt.org>
 * @copyright  2004-2006 T. Lechat <dev@lechat.org>, Manuel Kasper <mk@neon1.net>, Jonathan Watt <jwatt@jwatt.org>
 * @license    BSD
 *
 */

include_once("../includes/observium.inc.php");

if (!$config['web_iframe'] && is_iframe() &&
    !http_match_referer('!/device/device=\d+/tab=port/.*?/view=realtime/!')) {
    //bdump($_SERVER['HTTP_SEC_FETCH_DEST']);
    //bdump($_SERVER['HTTP_REFERER']); //'HTTP_SEC_FETCH_SITE' => 'same-origin'
    print_error_permission("Not allowed to run in a iframe!");
    die();
}

include($config['html_dir'] . "/includes/authenticate.inc.php");

// Push $_GET into $vars to be compatible with web interface naming
$vars = get_vars('GET');

if (is_numeric($vars['id']) && ($config['allow_unauth_graphs'] || port_permitted($vars['id'])))
{
    $port   = get_port_by_id_cache($vars['id']);
    $device = device_by_id_cache($port['device_id']);
    $title  = generate_device_link($device);
    $title .= " :: Port  " . generate_port_link($port);
    $auth   = TRUE;
} else {
    // not authenticated
    die("Unauthenticated");
}

header("Content-type: image/svg+xml");

/********** HTTP GET Based Conf ***********/
$ifnum  = $port['ifIndex'];             // BSD / SNMP interface name / number
$ifname = escape_html($port['port_label']);  //Interface name that will be showed on top right of graph
//$hostname = short_hostname($device['hostname']);
$hostname = escape_html(device_name($device, TRUE));

if ($vars['title']) { $ifname = escape_html($vars['title']); }

/********* Other conf *******/
$scale_type = "follow"; // Autoscale default setup : "up" = only increase scale; "follow" = increase and decrease scale according to current graphed datas
$nb_plot    = 240;      // NB plot in graph

// Refresh time Interval
$time_interval = is_numeric($vars['interval']) ? $vars['interval'] : 1;

$fetch_link = "data.php?id=" . $vars['id'];
if (OBS_DEBUG) {
    $fetch_link .= '&amp;debug=yes';
}

// SVG attributes
$attribs['axis']            = 'fill="black" stroke="black"';
$attribs['in']              = 'fill="green" font-family="Tahoma, Verdana, Arial, Helvetica, sans-serif" font-size="7"';
$attribs['out']             = 'fill="blue" font-family="Tahoma, Verdana, Arial, Helvetica, sans-serif" font-size="7"';
$attribs['graph_in']        = 'fill="none" stroke="green" stroke-opacity="0.8"';
$attribs['graph_out']       = 'fill="none" stroke="blue" stroke-opacity="0.8"';
$attribs['legend']          = 'fill="black" font-family="Tahoma, Verdana, Arial, Helvetica, sans-serif" font-size="4"';
$attribs['graphname']       = 'fill="#435370" font-family="Tahoma, Verdana, Arial, Helvetica, sans-serif" font-size="9"';
$attribs['hostname']        = 'fill="#435370" font-family="Tahoma, Verdana, Arial, Helvetica, sans-serif" font-size="6"';
$attribs['grid_txt']        = 'fill="gray" font-family="Tahoma, Verdana, Arial, Helvetica, sans-serif" font-size="6"';
$attribs['grid']            = 'stroke="gray" stroke-opacity="0.5"';
$attribs['switch_unit']     = 'fill="#435370" font-family="Tahoma, Verdana, Arial, Helvetica, sans-serif" font-size="4" text-decoration="underline"';
$attribs['switch_scale']    = 'fill="#435370" font-family="Tahoma, Verdana, Arial, Helvetica, sans-serif" font-size="4" text-decoration="underline"';
$attribs['error']           = 'fill="blue" font-family="Arial" font-size="4"';
$attribs['collect_initial'] = 'fill="gray" font-family="Tahoma, Verdana, Arial, Helvetica, sans-serif" font-size="4"';

//Error text if we cannot fetch data : depends on which method is used
$error_text = "Can't get data about port $ifnum";

$height = 125; // SVG internal height : do not modify
$width  = 300; // SVG internal width : do not modify

/********* Graph DATA **************/
print('<?xml version="1.0" encoding="iso-8859-1"?>' . PHP_EOL);
?>

<svg width="100%" height="100%" viewBox="0 0 <?php echo("$width $height") ?>" preserveAspectRatio="none" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" onload="init(evt)">
  <g id="graph">
    <rect id="bg" x1="0" y1="0" width="100%" height="100%" fill="white"/>
    <line id="axis_x" x1="0" y1="0" x2="0" y2="100%" <?php echo ($attribs['axis']) ?>/>
    <line id="axis_y" x1="0" y1="100%" x2="100%" y2="100%" <?php echo($attribs['axis']) ?>/>
    <path id="graph_out" d="M0 <?php echo($height) ?> L 0 <?php echo($height); ?>" <?php echo($attribs['graph_out']) ?>/>
    <path id="graph_in"  d="M0 <?php echo($height) ?> L 0 <?php echo($height); ?>" <?php echo($attribs['graph_in']) ?>/>
    <path id="grid" d="M0 <?php echo($height / 4) ?> L <?php echo($width) ?> <?php echo($height / 4) ?> M0 <?php echo($height / 4 * 2) ?> L <?php echo($width) ?> <?php echo($height / 4 * 2) ?> M0 <?php echo ($height / 4 * 3) ?> L <?php echo($width . ' ' . ($height / 4 * 3)) ?>" <?php echo($attribs['grid'])?>/>
    <text id="grid_txt1" x="<?php echo($width) ?>" y="<?php echo($height / 4) ?>" <?php echo($attribs['grid_txt']) ?> text-anchor="end"> </text>
    <text id="grid_txt2" x="<?php echo($width) ?>" y="<?php echo($height / 4 * 2) ?>" <?php echo($attribs['grid_txt']) ?> text-anchor="end"> </text>
    <text id="grid_txt3" x="<?php echo($width) ?>" y="<?php echo($height / 4 * 3) ?>" <?php echo($attribs['grid_txt']) ?> text-anchor="end"> </text>
    <text id="graph_in_lbl" x="5" y="8" <?php echo($attribs['in']) ?>>In</text>
    <text id="graph_out_lbl" x="5" y="16" <?php echo($attribs['out']) ?>>Out</text>
    <text id="graph_in_txt" x="20" y="8" <?php echo($attribs['in']) ?>> </text>
    <text id="graph_out_txt" x="20" y="16" <?php echo($attribs['out']) ?>> </text>
    <text id="ifname" x="<?php echo($width - 2) ?>" y="8" <?php echo($attribs['graphname']) ?> text-anchor="end"><?php echo($ifname) ?></text>
    <text id="hostname" x="<?php echo($width - 2) ?>" y="14" <?php echo($attribs['hostname']) ?> text-anchor="end"><?php echo($hostname) ?></text>
    <text id="switch_unit" x="<?php echo($width * 0.55) ?>" y="5" <?php echo($attribs['switch_unit']) ?>>Switch to bytes/s</text>
    <text id="switch_scale" x="<?php echo($width * 0.55) ?>" y="11" <?php echo($attribs['switch_scale']) ?>>AutoScale (<?php echo($scale_type) ?>)</text>
    <text id="datetime" x="<?php echo($width * 0.33) ?>" y="5" <?php echo($attribs['legend']) ?>> </text>
    <text id="graphlast" x="<?php echo($width * 0.55) ?>" y="17" <?php echo($attribs['legend']) ?>>Graph shows last <?php echo($time_interval * $nb_plot) ?> seconds</text>
    <polygon id="axis_arrow_x" <?php echo($attribs['axis']) ?> points="<?php echo($width . "," . $height) ?> <?php echo(($width - 2) . "," . ($height - 2)) ?> <?php echo(($width - 2) . "," . $height) ?>"/>
    <text id="error" x="<?php echo($width * 0.5) ?>" y="<?php echo($height * 0.5) ?>" visibility="hidden" <?php echo($attribs['error']) ?> text-anchor="middle"><?php echo($error_text) ?></text>
    <text id="collect_initial" x="<?php echo($width * 0.5) ?>" y="<?php echo($height * 0.5) ?>" visibility="hidden" <?php echo($attribs['collect_initial']) ?> text-anchor="middle">Collecting initial data, please wait...</text>
  </g>
  <script type="text/ecmascript">
    <![CDATA[

/**
 * getURL is a proprietary Adobe function, but it's simplicity has made it very
 * popular. If getURL is undefined we spin our own by wrapping XMLHttpRequest.
 */
if (typeof getURL == 'undefined') {
    getURL = function(url, callback) {
        if (!url)
            throw 'No URL for getURL';

        try {
            if (typeof callback.operationComplete == 'function')
                callback = callback.operationComplete;
        } catch (e) {
        }
        if (typeof callback != 'function')
            throw 'No callback function for getURL';

        var http_request = null;
        if (typeof XMLHttpRequest != 'undefined') {
            http_request = new XMLHttpRequest();
        } else if (typeof ActiveXObject != 'undefined') {
            try {
                http_request = new ActiveXObject('Msxml2.XMLHTTP');
            } catch (e) {
                try {
                    http_request = new ActiveXObject('Microsoft.XMLHTTP');
                } catch (e) {
                }
            }
        }
        if (!http_request)
            throw 'Both getURL and XMLHttpRequest are undefined';

        http_request.onreadystatechange = function() {
            if (http_request.readyState === 4) {
                callback({ success: true,
                           content: http_request.responseText,
                           contentType: http_request.getResponseHeader("Content-Type")
                });
            }
        }
        http_request.open('GET', url, true);
        http_request.send(null);
    }
}

var SVGDoc = null;
var last_ifin = 0;
var last_ifout = 0;
var last_ugmt = 0;
var max = 0;
var plot_in = [];
var plot_out = [];

var max_num_points = <?php echo($nb_plot) ?>;  // maximum number of plot data points
var step = <?php echo($width) ?> / max_num_points ;
var unit = 'bits';
var scale_type = '<?php echo($scale_type) ?>';

function init(evt) {
    SVGDoc = evt.target.ownerDocument;
    SVGDoc.getElementById("switch_unit").addEventListener("mousedown", switch_unit, false);
    SVGDoc.getElementById("switch_scale").addEventListener("mousedown", switch_scale, false);

    fetch_data();
}

function switch_unit(event) {
    SVGDoc.getElementById('switch_unit').firstChild.data = 'Switch to ' + unit + '/s';
    unit = (unit === 'bits') ? 'bytes' : 'bits';
}

function switch_scale(event) {
    scale_type = (scale_type === 'up') ? 'follow' : 'up';
    SVGDoc.getElementById('switch_scale').firstChild.data = 'AutoScale (' + scale_type + ')';
}

function fetch_data() {
    getURL('<?php echo($fetch_link) ?>', plot_data);
}

function plot_data(obj) {
    // Show datetimelegend
    var now = new Date();
    //var datetime = (now.getMonth()+1) + "/" + now.getDate() + "/" + now.getFullYear() + ' ' +
    //  LZ(now.getHours()) + ":" + LZ(now.getMinutes()) + ":" + LZ(now.getSeconds());

    datetime = now.toLocaleString();
    //datetime = now.toISOString();

    SVGDoc.getElementById('datetime').firstChild.data = datetime;

    if (!obj.success)
        return handle_error();  // getURL failed to get data

    var t = obj.content.split("|");
    var ugmt = parseFloat(t[0]);  // ugmt is an unixtimestamp style
    var ifin = parseInt(t[1]);    // number of bytes received by the interface
    var ifout = parseInt(t[2]);   // number of bytes sent by the interface
    var scale;

    if (!isNumber(ifin) || !isNumber(ifout))
        return handle_error();

    var diff_ugmt = ugmt - last_ugmt;
    var diff_ifin;
    if (ifin >= last_ifin) {
        diff_ifin = ifin - last_ifin;
    } else {
        var max = (last_ifin > Math.pow(2, 32)) ? Math.pow(2, 64) : Math.pow(2, 32);
        diff_ifin = max - last_ifin + ifin;
    }
    var diff_ifout;
    if (ifout >= last_ifout) {
        diff_ifout = ifout - last_ifout;
    } else {
        var max = (last_ifout > Math.pow(2, 32)) ? Math.pow(2, 64) : Math.pow(2, 32);
        diff_ifout = max - last_ifout + ifout;
    }

    if (diff_ugmt === 0)
        diff_ugmt = 1;  /* avoid division by zero */

    last_ugmt = ugmt;
    last_ifin = ifin;
    last_ifout = ifout;

    switch (plot_in.length) {
        case 0:
            SVGDoc.getElementById("collect_initial").setAttributeNS(null, 'visibility', 'visible');
            plot_in[0] = diff_ifin / diff_ugmt;
            plot_out[0] = diff_ifout / diff_ugmt;
            setTimeout('fetch_data()',<?php echo(1000 * $time_interval) ?>);
            return;
        case 1:
            SVGDoc.getElementById("collect_initial").setAttributeNS(null, 'visibility', 'hidden');
            break;
        case max_num_points:
            // shift plot to left if the maximum number of plot points has been reached
            var i = 0;
            while (i < max_num_points) {
                plot_in[i] = plot_in[i + 1];
                plot_out[i] = plot_out[++i];
            }
            plot_in.length--;
            plot_out.length--;
    }

    plot_in[plot_in.length] = diff_ifin / diff_ugmt;
    plot_out[plot_out.length]= diff_ifout / diff_ugmt;
    var index_plot = plot_in.length - 1;

    SVGDoc.getElementById('graph_in_txt').firstChild.data = formatSpeed(plot_in[index_plot], unit);
    SVGDoc.getElementById('graph_out_txt').firstChild.data = formatSpeed(plot_out[index_plot], unit);

    /* determine peak for sensible scaling */
    if (scale_type === 'up') {
        if (plot_in[index_plot] > max)
            max = plot_in[index_plot];
        if (plot_out[index_plot] > max)
            max = plot_out[index_plot];
    } else if (scale_type === 'follow') {
        i = 0;
        max = 0;
        while (i < plot_in.length) {
            if (plot_in[i] > max)
                max = plot_in[i];
            if (plot_out[i] > max)
                max = plot_out[i];
            i++;
        }
    }

    var rmax;  // max, rounded up

    if (unit === 'bits') {
        /* round up max, such that
           100 kbps -> 200 kbps -> 400 kbps -> 800 kbps -> 1 Mbps -> 2 Mbps -> ... */
        rmax = 12500;
        i = 0;
        while (max > rmax) {
            i++;
            if (i && (i % 4 === 0))
                rmax *= 1.25;
            else
                rmax *= 2;
        }
    } else {
        /* round up max, such that
           10 KB/s -> 20 KB/s -> 40 KB/s -> 80 KB/s -> 100 KB/s -> 200 KB/s -> 400 KB/s -> 800 KB/s -> 1 MB/s ... */
        rmax = 10240;
        i = 0;
        while (max > rmax) {
            i++;
            if (i && (i % 4 === 0))
                rmax *= 1.25;
            else
                rmax *= 2;

        if (i === 8)
            rmax *= 1.024;
        }
    }

    scale = <?php echo($height) ?> / rmax;

    /* change labels accordingly */
    SVGDoc.getElementById('grid_txt1').firstChild.data = formatSpeed(3 * rmax / 4, unit);
    SVGDoc.getElementById('grid_txt2').firstChild.data = formatSpeed(2 * rmax / 4, unit);
    SVGDoc.getElementById('grid_txt3').firstChild.data = formatSpeed(rmax / 4, unit);

    var path_in = "M 0 " + (<?php echo($height) ?> - (plot_in[0] * scale));
    var path_out = "M 0 " + (<?php echo($height) ?> - (plot_out[0] * scale));
    for (i = 1; i < plot_in.length; i++) {
        var x = step * i;
        var y_in = <?php echo($height) ?> - (plot_in[i] * scale);
        var y_out = <?php echo($height) ?> - (plot_out[i] * scale);
        path_in += " L" + x + " " + y_in;
        path_out += " L" + x + " " + y_out;
    }

    SVGDoc.getElementById('error').setAttributeNS(null, 'visibility', 'hidden');
    SVGDoc.getElementById('graph_in').setAttributeNS(null, 'd', path_in);
    SVGDoc.getElementById('graph_out').setAttributeNS(null, 'd', path_out);

    setTimeout('fetch_data()', <?php echo(1000 * $time_interval) ?>);
}

function handle_error() {
    SVGDoc.getElementById("error").setAttributeNS(null, 'visibility', 'visible');
    setTimeout('fetch_data()', <?php echo(1000 * $time_interval) ?>);
}

function isNumber(a) {
    return typeof a == 'number' && isFinite(a);
}

function formatSpeed(speed, unit) {
    if (unit === 'bits')
        return formatSpeedBits(speed);
    if (unit === 'bytes')
        return formatSpeedBytes(speed);
}

function formatSpeedBits(speed) {
    // format speed in bits/sec, input: bytes/sec
    if (speed < 125000)
        return Math.round(speed / 125) + " Kbps";
    if (speed < 125000000)
        return Math.round(speed / 1250)/100 + " Mbps";
    if (speed < 125000000000)
        return Math.round(speed / 1250000)/100 + " Gbps";
    // else
    return Math.round(speed / 125000000000)/100 + " Tbps";  /* wow! */
}

function formatSpeedBytes(speed) {
    // format speed in bytes/sec, input:  bytes/sec
    if (speed < 1048576)
        return Math.round(speed / 10.24)/100 + " KB/s";
    if (speed < 1073741824)
        return Math.round(speed / 10485.76)/100 + " MB/s";
    if (speed < 1099511627776)
        return Math.round(speed / 10737418.24)/100 + " GB/s";
    // else
    return Math.round(speed / 10995116277.76)/100 + " TB/s";  /* wow! */
}

function LZ(x) {
    return (x < 0 || x > 9 ? "" : "0") + x;
}

    ]]>
  </script>
</svg>
