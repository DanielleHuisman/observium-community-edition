<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

$graph_data = [];

$classes = ['primary', 'success', 'danger', 'warning', 'info', 'suppressed'];
$colours = ['0a5f7f', '4d9221', 'd9534f', 'F0AD4E', '4BB1CF', '740074'];

$i = 0;

if (isset($mod['vars']) && is_array($mod['vars']) && count($mod['vars'])) {
    $options = $mod['vars'];
} else {
    if (isset($config['frontpage']['portpercent']['options'])) {
        $options = $config['frontpage']['portpercent']['options'];
        unset($config['frontpage']['portpercent']['options']);
    }
    $options['groups'] = $config['frontpage']['portpercent'];
}

//add up totals in/out for each type, put it in an array.
$totals_array = [];
foreach ($options['groups'] as $type => $data) {

    $totalInOctets  = 0;
    $totalOutOctets = 0;

    if (!isset($colours[$i])) {
        $i = 0;
    }

    //fetch ports in group using existing observium functioon
    foreach (get_group_entities($data['group'], 'port') as $port_id) {
        $octets         = dbFetchRow("SELECT `ifInOctets_rate`, `ifOutOctets_rate` FROM `ports` WHERE `port_id` = ?", [$port_id]);
        $totalInOctets  += $octets['ifInOctets_rate'];
        $totalOutOctets += $octets['ifOutOctets_rate'];
    }
    $totals_array[$type]["in"]  = $totalInOctets * 8;
    $totals_array[$type]["out"] = $totalOutOctets * 8;

    $port_ids[$type][] = $port_id;

    $graph_data[] = ['group_id' => $data['group'],
                     'descr'    => $type,
                     'colour'   => $colours[$i]];

    $i++;

}

// total things up
$totalIn  = 0;
$totalOut = 0;
foreach ($totals_array as $type => $dir) {
    $totalIn  = $totalIn + $dir['in'];
    $totalOut = $totalOut + $dir['out'];
}


$percentage_bar           = [];
$percentage_bar['border'] = "#EEE";
$percentage_bar['bg']     = "#f0f0f0";
$percentage_bar['width']  = "100%";
//$percentage_bar['text']    = $avai_perc."%";
//$percentage_bar['text_c']  = "#E25A00";

$percentage_bar_out = $percentage_bar;

// do the real work
$percentIn  = "";
$percentOut = "";

$legend = '<table class="table table-condensed-more">';

$i = 0;

$table_min_height = 84; // Height of the three in/out/total bars
$table_row_height = 26; // Height of single row
$table_padding    = 8;  // Total vertical padding

$table_height = ($table_row_height * count($totals_array)) + $table_padding;

if ($table_height < $table_min_height) {
    $table_height = $table_min_height;
}

foreach ($totals_array as $type => $dir) {
    $percentIn  = float_div($dir["in"], $totalIn) * 100;
    $percentOut = float_div($dir["out"], $totalOut) * 100;
    $percent    = float_div(($dir["in"] + $dir["out"]), ($totalIn + $totalOut)) * 100;

    if (!isset($colours[$i])) {
        $i = 0;
    }

    $color = $config['graph_colours']['mixed'][$i];
    $class = $classes[$i];

    $bars_in  .= '  <div class="progress-bar progress-bar-' . $class . '" style="width: ' . $percentIn . '%"><span class="sr-only">' . round($percentIn) . '%' . '</span></div>';
    $bars_out .= '  <div class="progress-bar progress-bar-' . $class . '" style="width: ' . $percentOut . '%"><span class="sr-only">' . round($percentOut) . '%' . '</span></div>';
    $bars     .= '  <div class="progress-bar progress-bar-' . $class . '" style="width: ' . $percent . '%"><span class="sr-only">' . round($percent) . '%' . '</span></div>';

    $i++;

    $legend .= '<tr><td><span class="label label-' . $class . '">' . $type . '</span></td><td><i class="icon-circle-arrow-down green"></i> <small><b>' . format_si($dir['in']) . 'bps</b></small></td>
                <td><i class="icon-circle-arrow-up" style="color: #323b7c;"></i> <small><b>' . format_si($dir['out']) . 'bps</b></small></td></tr>';

}

$legend .= '</table>';

$box_args                    = ['title'         => 'Traffic Comparison',
                                'header-border' => TRUE,
                                'padding'       => FALSE,
];
$box_args['header-controls'] = ['controls' => ['tooltip' => [//'icon'   => $config['icon']['info'],
                                                             'anchor' => TRUE,
                                                             'text'   => (($options['graph_format'] == "multi" || $options['graph_format'] == "multi_bare") ? '<span class="label">Day</span><span class="label">Week</span><span class="label">Month</span><span class="label">Year</span>' : '<span class="label">48 Hours</span>'),
                                                             'class'  => 'tooltip-from-element',
                                                             //'url'    => '#',
                                                             'data'   => 'data-tooltip-id="tooltip-help-conditions"']]];


echo generate_box_open($box_args);

?>
<div id="tooltip-help-conditions" style="display: none;">
    <h3><?php if ($options['graph_format'] == "multi" || $options['graph_format'] == "multi_bare") {
            echo 'Graph periods: day, week, month, year';
        } else {
            echo 'Graph period is 48 hours';
        } ?></h3>
</div>

<table class="table table-condensed">

    <?php

    if ($options['graph_format'] != "none") {

        $graph_array = ['type'     => 'multi-port_groups_bits',
                        'width'    => 1239,
                        'height'   => 89,
                        'legend'   => 'no',
                        'from'     => get_time('twoday'),
                        'to'       => get_time('now'),
                        'perc_agg' => TRUE,
                        'data'     => var_encode($graph_data),
                        //                             'width'  => '305'
        ];

        echo '<tr><td colspan=3>';

        // Calculate height available for graph
        if (isset($width)) {
            $graph_array['height'] = $height - (82 + 0 + $table_height);
        } else {
            $graph_array['height'] = 100;
        }


        switch ($options['graph_format']) {
            case 'single':
                $graph_array['width']    = 1148;
                $graph_array['draw_all'] = 'yes';

                if (isset($width)) {
                    $graph_array['width'] = ($width - 10 - 76);
                    if ($graph_array['width'] > 350) {
                        $graph_array['width'] -= 6;
                    }
                }

                echo generate_graph_tag($graph_array);
                break;

            // FIXME - This logic should probably be functionalised, and the cuttoff to switch font should be a variable.

            case 'multi':
                $graph_array['draw_all'] = 'yes';
                unset($graph_array['width']);
                if (isset($width)) {
                    $graph_array['width'] = round(($width - 19) / 4);
                    if ($graph_array['width'] > 350) {
                        $graph_array['width'] -= 82;
                    } else {
                        $graph_array['width'] -= 76;
                    }
                }
                print_graph_row($graph_array);
                break;

            case 'multi_bare':
                $graph_array['width']      = 305;
                $graph_array['graph_only'] = 'yes';
                if (isset($width)) {
                    $graph_array['width'] = ($width - 19) / 4;
                }
                $graph_array['height'] += 39;
                print_graph_row($graph_array);
                break;

            case 'single_bare':
            default:
                $graph_array['graph_only'] = 'yes';
                if (isset($width)) {
                    $graph_array['width'] = ($width - 10);
                }
                $graph_array['height'] += 39;

                echo(generate_graph_tag($graph_array));
                break;
        }

        echo '</td></tr>';

    }

    if (!isset($options['legend_width'])) {
        $options['legend_width'] = "220";
    }

    ?>

    <table class="table table-condensed" style="background-color: #ffffff00;">
        <tr>
            <td rowspan="3" width="<?php echo $options['legend_width']; ?>"><?php echo $legend; ?></td>
            <th width="40"><span class="label label-success"><i class="icon-circle-arrow-down"></i> In</span></th>
            <td>
                <div class="progress" style="margin-bottom: 0;">
                    <?php echo $bars_in; ?>
                </div>
            </td>
        </tr>
        <tr>
            <th><span class="label label-primary"><i class="icon-circle-arrow-up"></i> Out</span></th>
            <td>
                <div class="progress" style="margin-bottom: 0;">
                    <?php echo $bars_out; ?>
                </div>
            </td>
        </tr>
        <tr>
            <th><span class="label"><i class="icon-refresh"></i> Total</span></th>
            <td>
                <div class="progress" style="margin-bottom: 0;">
                    <?php echo $bars; ?>
                </div>
            </td>
        </tr>

    </table>

    <?php echo generate_box_close(); ?>
