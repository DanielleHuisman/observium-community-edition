<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

ini_set('allow_url_fopen', 0);

include_once("../includes/observium.inc.php");

if (!$config['web_iframe'] && is_iframe()) {
    print_error_permission("Not allowed to run in a iframe!");
    die();
}

include($config['html_dir'] . "/includes/authenticate.inc.php");

if ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
    if (!$_SESSION['authenticated']) {
        // not authenticated
        die("Unauthenticated");
    }
}
require_once($config['html_dir'] . "/includes/jpgraph/src/jpgraph.php");
require_once($config['html_dir'] . "/includes/jpgraph/src/jpgraph_line.php");
require_once($config['html_dir'] . "/includes/jpgraph/src/jpgraph_bar.php");
require_once($config['html_dir'] . "/includes/jpgraph/src/jpgraph_utils.inc.php");
require_once($config['html_dir'] . "/includes/jpgraph/src/jpgraph_date.php");

$vars = get_vars('GET');

if (is_numeric($vars['bill_id'])) {
    if ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
        if (bill_permitted($vars['bill_id'])) {
            $bill_id = $vars['bill_id'];
        } else {
            echo("Unauthorised Access Prohibited.");
            exit;
        }
    } else {
        $bill_id = $vars['bill_id'];
    }
} else {
    echo("Unauthorised Access Prohibited.");
    exit;
}

// Workaround for JPGraph 3.5 on Ubuntu per 0015246
if (!function_exists('imageantialias')) {
    function imageantialias($image, $enabled)
    {
        return FALSE;
    }
}

$start = $vars['from'];
$end   = $vars['to'];
$xsize = (is_numeric($vars['x']) ? $vars['x'] : "800");
$ysize = (is_numeric($vars['y']) ? $vars['y'] : "250");
//$count        = (is_numeric($_GET['count']) ? $_GET['count'] : "0" );
//$type         = (isset($_GET['type']) ? $_GET['type'] : "date" );
//$dur          = $end - $start;
//$datefrom     = date('Ymthis', $start);
//$dateto       = date('Ymthis', $end);
$imgtype    = $vars['type'] ?? "historical";
$imgbill    = $vars['imgbill'] ?? FALSE;
$yaxistitle = "Bytes";

$in_data      = [];
$out_data     = [];
$tot_data     = [];
$allow_data   = [];
$ave_data     = [];
$overuse_data = [];
$ticklabels   = [];

if ($imgtype === "historical") {
    $i = "0";

    foreach (dbFetchRows("SELECT * FROM `bill_history` WHERE `bill_id` = ? ORDER BY `bill_datefrom` DESC LIMIT 12", [$bill_id]) as $data) {
        $datefrom      = strftime("%e %b %Y", strtotime($data['bill_datefrom']));
        $dateto        = strftime("%e %b %Y", strtotime($data['bill_dateto']));
        $datelabel     = $datefrom . "\n" . $dateto;
        $traf['in']    = $data['traf_in'];
        $traf['out']   = $data['traf_out'];
        $traf['total'] = $data['traf_total'];

        if ($data['bill_type'] === "Quota") {
            $traf['allowed'] = $data['bill_allowed'];
            $traf['overuse'] = $data['bill_overuse'];
        } else {
            $traf['allowed'] = "0";
            $traf['overuse'] = "0";
        }

        $ticklabels[] = $datelabel;
        $in_data[]    = $traf['in'];
        $out_data[] = $traf['out'];
        $tot_data[] = $traf['total'];
        $allow_data[] = $traf['allowed'];
        $overuse_data[] = $traf['overuse'];
        $i++;
        //print_vars($data);
    }

    if ($i < 12) {
        $y = 12 - $i;
        for ($x = 0; $x < $y; $x++) {
            $allowed   = (($x == "0") ? $traf['allowed'] : "0");
            $in_data[] = "0";
            $out_data[] = "0";
            $tot_data[] = "0";
            $allow_data[] = $allowed;
            $overuse_data[] = "0";
            $ticklabels[] = "";
        }
    }
    $yaxistitle = "Gigabytes";
    $graph_name = "Historical bandwidth over the last 12 billing periods";
} else {
    $data    = [];
    $average = 0;
    if ($imgtype === "day") {
        foreach (dbFetchRows("SELECT DISTINCT UNIX_TIMESTAMP(timestamp) as timestamp, SUM(delta) as traf_total, SUM(in_delta) as traf_in, SUM(out_delta) as traf_out FROM bill_data WHERE `bill_id` = ? AND `timestamp` >= FROM_UNIXTIME(?) AND `timestamp` <= FROM_UNIXTIME(?) GROUP BY DATE(timestamp) ORDER BY timestamp ASC", [$bill_id, $start, $end]) as $data) {
            $traf['in']    = $data['traf_in'] ?? 0;
            $traf['out']   = $data['traf_out'] ?? 0;
            $traf['total'] = $data['traf_total'] ?? 0;
            $datelabel     = strftime("%e\n%b", $data['timestamp']);

            $ticklabels[] = $datelabel;
            $in_data[]    = $traf['in'];
            $out_data[] = $traf['out'];
            $tot_data[] = $traf['total'];
            $average += $data['traf_total'];
        }
        $ave_count = safe_count($tot_data);
        if ($imgbill) {
            $days = strftime("%e", date($end - $start)) - $ave_count - 1;
            for ($x = 0; $x < $days; $x++) {
                $ticklabels[] = "";
                $in_data[]    = 0;
                $out_data[] = 0;
                $tot_data[] = 0;
            }
        }
    } elseif ($imgtype === "hour") {
        foreach (dbFetchRows("SELECT DISTINCT UNIX_TIMESTAMP(timestamp) as timestamp, SUM(delta) as traf_total, SUM(in_delta) as traf_in, SUM(out_delta) as traf_out FROM bill_data WHERE `bill_id` = ? AND `timestamp` >= FROM_UNIXTIME(?) AND `timestamp` <= FROM_UNIXTIME(?) GROUP BY HOUR(timestamp) ORDER BY timestamp ASC", [$bill_id, $start, $end]) as $data) {
            $traf['in']    = $data['traf_in'] ?? 0;
            $traf['out']   = $data['traf_out'] ?? 0;
            $traf['total'] = $data['traf_total'] ?? 0;
            $datelabel     = strftime("%H:%M", $data['timestamp']);

            $ticklabels[] = $datelabel;
            $in_data[]    = $traf['in'];
            $out_data[] = $traf['out'];
            $tot_data[] = $traf['total'];
            $average += $data['traf_total'];
        }
        $ave_count = safe_count($tot_data);
    }

    $decimal = 0;
    $average = float_div($average, $ave_count);
    for ($x = 0, $x_max = safe_count($tot_data); $x <= $x_max; $x++) {
        $ave_data[] = $average;
    }
    $graph_name = date('M j g:ia', $start) . " - " . date('M j g:ia', $end);
}

// Create the graph. These two calls are always required
$graph = new Graph($xsize, $ysize, $graph_name);
$graph -> img -> SetImgFormat("png");

#$graph->SetScale("textlin",0,0,$start,$end);

$graph -> SetScale("textlin");
#$graph->title->Set("$graph_name");
$graph -> title -> SetFont(FF_FONT2, FS_BOLD, 10);
$graph -> SetMarginColor("white");
$graph -> SetFrame(FALSE);
$graph -> SetMargin("75", "30", "30", "65");
$graph -> legend -> SetFont(FF_FONT1, FS_NORMAL);
$graph -> legend -> SetLayout(LEGEND_HOR);
$graph -> legend -> Pos("0.52", "0.91", "center");

$graph -> xaxis -> SetFont(FF_FONT1, FS_BOLD);
$graph -> xaxis -> SetPos('min');
$graph -> xaxis -> SetTitleMargin(30);
$graph -> xaxis -> SetTickLabels($ticklabels);
$graph -> xgrid -> Show(TRUE, TRUE);
$graph -> xgrid -> SetColor('#e0e0e0', '#efefef');

$graph -> yaxis -> SetFont(FF_FONT1);
$graph -> yaxis -> SetTitleMargin(50);
$graph -> yaxis -> title -> SetFont(FF_FONT1, FS_NORMAL, 10);
$graph -> yaxis -> title -> Set("Bytes Transferred");
$graph -> yaxis -> SetLabelFormatCallback('format_bytes_billing');
$graph -> ygrid -> SetFill(TRUE, '#EFEFEF@0.5', '#FFFFFF@0.5');

// Create the bar plots
$barplot_tot = new BarPlot($tot_data);
$barplot_tot -> SetLegend("Traffic total");
$barplot_tot -> SetColor('darkgray');
$barplot_tot -> SetFillColor('lightgray@0.4');
$barplot_tot -> value -> Show();
$barplot_tot -> value -> SetFormatCallback('format_bytes_billing_short');

$barplot_in = new BarPlot($in_data);
$barplot_in -> SetLegend("Traffic In");
$barplot_in -> SetColor('#' . $config['graph_colours']['greens'][1]);
$barplot_in -> SetFillColor('#' . $config['graph_colours']['greens'][0]);
$barplot_in -> SetWeight(1);

$barplot_out = new BarPlot($out_data);
$barplot_out -> SetLegend("Traffic Out");
$barplot_out -> SetColor('#' . $config['graph_colours']['blues'][0]);
$barplot_out -> SetFillColor('#' . $config['graph_colours']['blues'][1]);
$barplot_out -> SetWeight(1);

if ($imgtype === "historical") {
    $barplot_over = new BarPlot($overuse_data);
    $barplot_over -> SetLegend("Traffic Overusage");
    $barplot_over -> SetColor('darkred');
    $barplot_over -> SetFillColor('lightred@0.4');
    $barplot_over -> SetWeight(1);

    $lineplot_allow = new LinePlot($allow_data);
    $lineplot_allow -> SetLegend("Traffic Allowed");
    $lineplot_allow -> SetColor('black');
    $lineplot_allow -> SetWeight(1);

    $gbplot = new GroupBarPlot([$barplot_in, $barplot_tot, $barplot_out, $barplot_over]);
} else {
    $lineplot_allow = new LinePlot($ave_data);
    //$lineplot_allow->SetLegend("Average per ".$imgtype);
    $lineplot_allow -> SetLegend("Average");
    $lineplot_allow -> SetColor('black');
    $lineplot_allow -> SetWeight(1);

    $gbplot = new GroupBarPlot([$barplot_in, $barplot_tot, $barplot_out]);
}

$graph -> Add($gbplot);
$graph -> Add($lineplot_allow);

// Display the graph
$graph -> Stroke();

// EOF
