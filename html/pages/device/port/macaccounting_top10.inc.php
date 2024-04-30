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

if (!isset($vars['sort'])) {
    $vars['sort'] = "in";
}
if (!isset($vars['period'])) {
    $vars['period'] = "day";
}
$graph_width = 949;
$thumb_width = 120;
//$from        = "-" . $vars['period'];
$from        = get_time($vars['period']);

echo '<div class="row">';
echo '  <div class="col-md-2">';

$graph_array['id']     = $vars['port'];
$graph_array['type']   = 'port_mac_acc_total';
$graph_array['stat']   = $vars['stat'];
$graph_array['sort']   = $vars['sort'];
$graph_array['height'] = "60";
$graph_array['width']  = 170;
$graph_array['legend'] = "no";
$graph_array['to']     = get_time();
$graph_array['from']   = $from;

$variants = [
    'Bits'          => [ 'stat' => 'bits', 'sort' => $vars['sort'] ],
    'Packets'       => [ 'stat' => 'pkts', 'sort' => $vars['sort'] ],
    'Top Input'     => [ 'stat' => $vars['stat'], 'sort' => 'in' ],
    'Top Output'    => [ 'stat' => $vars['stat'], 'sort' => 'out' ],
    'Top Aggregate' => [ 'stat' => $vars['stat'], 'sort' => 'both' ]
];

foreach ($variants as $text => $variant) {

    $graph_array = array_merge($graph_array, $variant);

    $link_array           = $vars;
    $link_array['period'] = $vars['period'];
    $link_array           = array_merge($link_array, $variant);
    $link                 = generate_url($link_array);

    echo '    <div class="box box-solid">';
    echo '<div class="box-header with-border"><h3 class="box-title">' . $text . '</h3></div>';
    echo '    <div class="box-body">';
    echo '<a href="' . $link . '">';
    echo generate_graph_tag($graph_array);
    echo '</a>';
    echo '    </div>';
    echo '    </div>';

}

echo '  </div>';

echo '  <div class="col-md-10">';
echo '    <div class="box box-solid" style="padding-bottom: 10px;">';

$thumb_array = ['sixhour' => '6 Hours',
                'day'     => '24 Hours',
                'twoday'  => '48 Hours',
                'week'    => 'One Week',
                //'twoweek' => 'Two Weeks',
                'month'   => 'One Month',
                //'twomonth' => 'Two Months',
                'year'    => 'One Year',
                'twoyear' => 'Two Years'
];

$graph_array['id']     = $vars['port'];
$graph_array['type']   = 'port_mac_acc_total';
$graph_array['stat']   = $vars['stat'];
$graph_array['height'] = "60";
$graph_array['width']  = $thumb_width;
$graph_array['legend'] = "no";
$graph_array['to']     = get_time();
$graph_array['sort']   = $vars['sort'];


echo('<table style="width: 100%; background: transparent;"><tr>');

foreach ($thumb_array as $period => $text) {
    $graph_array['from'] = get_time($period);

    $link_array           = $vars;
    $link_array['period'] = $period;
    $link                 = generate_url($link_array);

    echo('<td style="text-align: center;">');
    echo('<h3 class="box-title">' . $text . '</h3>');
    echo('<a href="' . $link . '">');
    echo(generate_graph_tag($graph_array));
    echo('</a>');
    echo('</td>');

}

echo('</tr></table>');

echo '    </div>';
echo '  </div>';


unset($graph_array['legend']);
$graph_array['height'] = "300";
$graph_array['id']     = $vars['port'];
$graph_array['width']  = $graph_width;
$graph_array['type']   = 'port_mac_acc_total';
$graph_array['stat']   = $vars['stat'];
$graph_array['sort']   = $vars['sort'];
$graph_array['from']   = $from;
$graph_array['to']     = get_time();

echo '  <div class="col-md-10">';
echo '    <div class="box box-solid">';

echo('<div class="box box-solid">');
echo(generate_graph_tag($graph_array));
echo("</div>");

echo '    </div>';
echo '  </div>';

echo '</div>';

// EOF
