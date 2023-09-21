<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$box_args = ['title' => 'Memory',
             'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'mempool']),
             'icon'  => $config['icon']['mempool'],
];

echo generate_box_open($box_args);

$mem_used_total = $device_state['ucd_mem']['mem_total'] - $device_state['ucd_mem']['mem_avail'];
if (isset($device_state['ucd_mem']['mem_used'])) {
    //r($device_state['ucd_mem']);
    $mem_used = $device_state['ucd_mem']['mem_used'];
} else {
    $mem_used = $mem_used_total - ($device_state['ucd_mem']['mem_cached'] + $device_state['ucd_mem']['mem_buffer']);
}

$used_perc       = round(float_div($mem_used, $device_state['ucd_mem']['mem_total']) * 100);
$used_perc_total = round(float_div($mem_used_total, $device_state['ucd_mem']['mem_total']) * 100);
$cach_perc       = round(float_div($device_state['ucd_mem']['mem_cached'], $device_state['ucd_mem']['mem_total']) * 100);
$buff_perc       = round(float_div($device_state['ucd_mem']['mem_buffer'], $device_state['ucd_mem']['mem_total']) * 100);
$avai_perc       = round(float_div($device_state['ucd_mem']['mem_avail'], $device_state['ucd_mem']['mem_total']) * 100);

$graph_array           = [];
$graph_array['height'] = "100";
$graph_array['width']  = "509";
$graph_array['to']     = get_time();
$graph_array['device'] = $device['device_id'];
$graph_array['type']   = 'device_ucd_memory';
$graph_array['from']   = get_time('day');
$graph_array['legend'] = "no";
$graph                 = generate_graph_tag($graph_array);

$link_array         = $graph_array;
$link_array['page'] = "graphs";
unset($link_array['height'], $link_array['width']);
$link = generate_url($link_array);

$graph_array['width'] = "210";
$overlib_content      = generate_overlib_content($graph_array, $device['hostname'] . " - Memory Usage");

// echo(overlib_link($link, $graph, $overlib_content, NULL));

$percentage_bar            = [];
$percentage_bar['border']  = "#E25A00";
$percentage_bar['bg']      = "#f0f0f0";
$percentage_bar['width']   = "100%";
$percentage_bar['text']    = $avai_perc . "%";
$percentage_bar['text_c']  = "#E25A00";
$percentage_bar['bars'][0] = ['percent' => $used_perc, 'colour' => '#FFAA66', 'text' => $used_perc_total . '%'];
$percentage_bar['bars'][1] = ['percent' => $buff_perc, 'colour' => '#cc0000', 'text' => ''];
$percentage_bar['bars'][2] = ['percent' => $cach_perc, 'colour' => '#f0e0a0', 'text' => ''];

$swap_used      = $device_state['ucd_mem']['swap_total'] - $device_state['ucd_mem']['swap_avail'];
$swap_perc      = round(float_div($swap_used, $device_state['ucd_mem']['swap_total']) * 100);
$swap_free_perc = 100 - $swap_perc;

?>

    <table class="table table-striped">

        <tr>
            <td colspan="2"><?php echo(overlib_link($link, $graph, $overlib_content, NULL)); ?></td>
        </tr>

        <tr>
            <td class="entity">RAM</td>
            <td style="width: 90%;"><?php echo(percentage_bar($percentage_bar)); ?></td>
        </tr>

        <tr class="small">
            <td colspan="2">
                <div class="row" style="margin-left: 5px;">
                    <div class="col-sm-4"><i style="font-size: 7px; line-height: 7px; background-color: #FFAA66; border: 1px #aaa solid;">&nbsp;&nbsp;&nbsp;</i>
                        <strong>Used:</strong> <?php echo(format_bytes($mem_used * 1024) . ' (' . $used_perc . '%)'); ?></div>
                    <div class="col-sm-4"><i style="font-size: 7px; line-height: 7px; background-color: #cc0000; border: 1px #aaa solid;">&nbsp;&nbsp;&nbsp;</i>
                        <strong>Buffers:</strong> <?php echo(format_bytes($device_state['ucd_mem']['mem_buffer'] * 1024) . ' (' . $buff_perc . '%)'); ?></div>
                    <div class="col-sm-4"><i style="font-size: 7px; line-height: 7px; background-color: #f0e0a0; border: 1px #aaa solid;">&nbsp;&nbsp;&nbsp;</i>
                        <strong>Cached:</strong> <?php echo(format_bytes($device_state['ucd_mem']['mem_cached'] * 1024) . ' (' . $cach_perc . '%)'); ?></div>
                    <div class="col-sm-4"><i style="font-size: 7px; line-height: 7px; background-color: #ddd;    border: 1px #aaa solid;">&nbsp;&nbsp;&nbsp;</i>
                        <strong>Free:</strong> <?php echo(format_bytes($device_state['ucd_mem']['mem_avail'] * 1024) . ' (' . $avai_perc . '%)'); ?></div>
                    <div class="col-sm-4"><i style="font-size: 7px; line-height: 7px; background-color: #ddd;    border: 1px #fff solid;">&nbsp;&nbsp;&nbsp;</i>
                        <strong>Total:</strong> <?php echo(format_bytes($device_state['ucd_mem']['mem_total'] * 1024)); ?></div>
                    <div class="col-sm-4"><i style="font-size: 7px; line-height: 7px; background-color: #356AA0; border: 1px #aaa solid;">&nbsp;&nbsp;&nbsp;</i>
                        <strong>Swap:</strong> <?php echo(format_bytes($swap_used * 1024) . ' (' . $swap_perc . '%)'); ?></div>
                </div>
            </td>
        </tr>

        <?php

        /**
         *
         * $swap_used = $device_state['ucd_mem']['swap_total'] - $device_state['ucd_mem']['swap_avail'];
         * $swap_perc = round(float_div($swap_used, $device_state['ucd_mem']['swap_total']) * 100);
         * $swap_free_perc = 100 - $swap_perc;
         *
         * $background = get_percentage_colours('40');
         *
         * $percentage_bar            = array();
         * $percentage_bar['border']  = "#356AA0";
         * $percentage_bar['bg']      = "#f0f0f0";
         * $percentage_bar['width']   = "100%";
         * $percentage_bar['text']    = $swap_free_perc."%";
         * $percentage_bar['text_c']  = "#356AA0";
         * $percentage_bar['bars'][0] = array('percent' => $swap_perc, 'colour' => '#356AA0', 'text' => $swap_perc.'%');
         * ?>
         * <tr>
         * <td class="entity">Swap</td>
         * <td><?php echo(percentage_bar($percentage_bar)); ?></td>
         * </tr>
         *
         * <tr class="small">
         * <td colspan="2">
         * <div class="row" style="margin-left: 5px;">
         * <div class="col-sm-4"><i style="font-size: 7px; line-height: 7px; background-color: #356AA0; border: 1px #aaa solid;">&nbsp;&nbsp;&nbsp;</i>
         * <strong>Used:</strong>  <?php echo(formatStorage($swap_used * 1024).' ('.$swap_perc.'%)'); ?></div>
         * <div class="col-sm-4"><i style="font-size: 7px; line-height: 7px; background-color: #ddd;    border: 1px #aaa solid;">&nbsp;&nbsp;&nbsp;</i>
         * <strong>Free:</strong>  <?php echo(formatStorage($device_state['ucd_mem']['swap_avail'] * 1024).' ('.$swap_free_perc.'%)'); ?></div>
         * <div class="col-sm-4"><i style="font-size: 7px; line-height: 7px; background-color: #ddd;    border: 1px #fff solid;">&nbsp;&nbsp;&nbsp;</i>
         * <strong>Total:</strong> <?php echo(formatStorage($device_state['ucd_mem']['swap_total'] * 1024)); ?></div>
         * </div>
         * </td>
         * </tr>
         */

        ?>

    </table>

<?php

echo generate_box_close();

// EOF
