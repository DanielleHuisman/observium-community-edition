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

if (!safe_empty($app_data['memory'])) {
  $memory_used        = $app_data['memory']['used'] - $app_data['memory']['cache'];
  $memory_used_perc   = round(float_div($memory_used, $app_data['memory']['total']) * 100, 2);
  $memory_cached_perc = round(float_div($app_data['memory']['cache'], $app_data['memory']['total']) * 100, 2);
  $memory_free        = $app_data['memory']['total'] - $app_data['memory']['used'];
  $memory_free_perc   = round(float_div($memory_free, $app_data['memory']['total']) * 100, 2);

  $graph_array           = [];
  $graph_array['height'] = "100";
  $graph_array['width']  = "512";
  $graph_array['to']     = get_time();
  $graph_array['id']     = $app['app_id'];
  $graph_array['type']   = 'application_mssql_memory_usage';
  $graph_array['from']   = get_time('day');
  $graph_array['legend'] = "no";
  $graph                 = generate_graph_tag($graph_array);

  $link_array         = $graph_array;
  $link_array['page'] = "graphs";
  unset($link_array['height'], $link_array['width'], $link_array['legend']);
  $link = generate_url($link_array);

  $overlib_content = generate_overlib_content($graph_array, $app['app_instance'] . " - Memory Usage");

  $percentage_bar = [];
  //$percentage_bar['border']  = "#EA8F00";
  $percentage_bar['border']  = "#E25A00";
  $percentage_bar['bg']      = "#f0f0f0";
  $percentage_bar['width']   = "100%";
  $percentage_bar['text']    = $memory_free_perc . "%";
  $percentage_bar['text_c']  = "#E25A00";
  $percentage_bar['bars'][0] = ['percent' => $memory_used_perc, 'colour' => '#EE9955', 'text' => $memory_used_perc . '%'];
  $percentage_bar['bars'][1] = ['percent' => $memory_cached_perc, 'colour' => '#f0e0a0', 'text' => ''];

  echo(overlib_link($link, $graph, $overlib_content, NULL));

  ?>
  <div class="box box-solid">
    <div class="title"><i class="<?php echo $config['icon']['mempool']; ?>"></i> Memory</div>
    <div class="content">
      <table width="100%" class="table table-striped table-condensed-more ">
        <tr>
          <td colspan="7">
            <?php echo(percentage_bar($percentage_bar)); ?>
          </td>
        </tr>
        <tr class="small">
          <td><i style="font-size: 7px; line-height: 7px; background-color: #EE9955; border: 1px #aaa solid;">&nbsp;&nbsp;&nbsp;</i> Used</td>
          <td><?php echo(format_bytes($memory_used) . ' (' . $memory_used_perc . '%)'); ?></td>
          <td><i style="font-size: 7px; line-height: 7px; background-color: #f0e0a0; border: 1px #aaa solid;">&nbsp;&nbsp;&nbsp;</i> Cached</td>
          <td><?php echo(format_bytes($app_data['memory']['cache']) . ' (' . $memory_cached_perc . '%)'); ?></td>
          <td><i style="font-size: 7px; line-height: 7px; background-color: #f0f0f0; border: 1px #aaa solid;">&nbsp;&nbsp;&nbsp;</i> Free</td>
          <td><?php echo(format_bytes($memory_free) . ' (' . $memory_free_perc . '%)'); ?></td>
        </tr>
      </table>
    </div>
  </div>
  <?php
}

unset($percentage_bar, $graph_array, $overlib_content, $graph, $link, $link_array);

// EOF
