<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     ajax
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */


echo '<div class="box box-solid do-not-update">';

$vars = $mod['vars'];

if (!isset($vars['type'])) {

    echo '<div style="position: relative;  top: 50%;  transform: perspective(1px) translateY(-50%); width: 100%; text-align: center;">
            <btn class="btn btn-primary" onclick="configWidget(' . $mod['widget_id'] . ')"><i class="icon-signal"/> &nbsp; Select Graph</btn>
          </div>';

    exit();
}

if (isset($vars['timestamp_from']) && preg_match(OBS_PATTERN_TIMESTAMP, $vars['timestamp_from'])) {
    $vars['from'] = strtotime($vars['timestamp_from']);
    unset($vars['timestamp_from']);
}
if (isset($vars['timestamp_to']) && preg_match(OBS_PATTERN_TIMESTAMP, $vars['timestamp_to'])) {
    $vars['to'] = strtotime($vars['timestamp_to']);
    unset($vars['timestamp_to']);
}

preg_match('/^(?P<type>[a-z0-9A-Z-]+)_(?P<subtype>.+)/', $vars['type'], $graphtype);

if (OBS_DEBUG) {
    print_vars($graphtype);
}

$type    = $graphtype['type'];
$subtype = $graphtype['subtype'];

if (is_numeric($vars['device'])) {
    $device = device_by_id_cache($vars['device']);
} elseif (!empty($vars['device'])) {
    $device = device_by_name($vars['device']);
} elseif ($type === "device" && is_numeric($vars['id'])) {
    $device = device_by_id_cache($vars['id']);
}

$preserve_id = $vars['id'];

if (is_file($config['html_dir'] . "/includes/graphs/" . $type . "/auth.inc.php")) {
    include($config['html_dir'] . "/includes/graphs/" . $type . "/auth.inc.php");
}

$vars['id'] = $preserve_id;

if (!$auth) {
    print_error_permission();
    return;
}

if (isset($config['entities'][$type])) {
    $entity = get_entity_by_id_cache($type, $vars['id']);
    entity_rewrite($type, $entity);
}

if ($type === 'bgp') {
    $entity = get_entity_by_id_cache('bgp_peer', $vars['id']);
    entity_rewrite('bgp_peer', $entity);
}

$graph_array = $vars;

$graph_array['width']  = $width - 76 + 14;                            // RRD graphs are 75px wider than request value
$graph_array['height'] = $height - 34;                                //68;                          // RRD graphs are taller than request value

if ($graph_array['width'] > 350) {
    $graph_array['width'] -= 6;
} // RRD graphs > 350px are 6 px wider because of larger legend font
if ($graph_array['width'] > 350) {
    $graph_array['height'] -= 6;
} // RRD graphs > 350px are 6 px taller because of larger legend font

//$title_div = 'top:0px; left: 0px; padding: 4px; border-top-left-radius: 4px; border: 1px solid #e5e5e5; border-left: none; border-top: none; background-color: rgba(255, 255,255, 0.75); ';
$title_div = 'widget-title';

if ($height < 100) {
    $graph_array['height']     = $height;
    $graph_array['width']      = $width;
    $graph_array['graph_only'] = 'yes';

    //$title_div = 'top:5px; left: 5px; padding: none; border-radius: 2px; border: 1px solid #e5e5e5; background: rgba(255, 255, 255, 0.7);';
    $title_div = 'widget-title-small';

} else {
    $graph_array['draw_all'] = 'yes';
}
$t_len = $vars['width'] / 10;

$subtype_text = (isset($config['graph_types'][$type][$subtype]) ? $config['graph_types'][$type][$subtype]['descr'] : nicecase($subtype));

if (isset($graph_array['title'])) {
    $title = $graph_array['title'];
    unset($graph_array['title']);
} else {
    if ($type === 'global') {
        $title = "Global :: " . $subtype_text;
    } elseif (isset($vars['group_id']) && is_numeric($vars['group_id'])) {
        $title = "Group :: " . $group['group_name'] . " :: " . $subtype_text;
    } elseif (str_contains($type, "multi")) {
        $count = safe_count($graph_array['id']);
        $title = $count . ' ' . nicecase(str_replace("multi-", '', $type)) . ' :: ' . $subtype_text;
    } else {
        $title = device_name($device, $t_len / 2 - 2) . ($type === "device" ? ' :: ' : ' :: ' . truncate($entity['entity_shortname'], 32) . ' :: ') . $subtype_text;
    }
}

$graph_array['rigid_height'] = 'yes';
$graph_array['class']        = 'image-refresh';

$graph = generate_graph_tag($graph_array, TRUE);

$link_array         = $graph_array;
$link_array['page'] = "graphs";
unset($link_array['graph_only']);
unset($link_array['rigid_height']);
unset($link_array['height'], $link_array['width']);
$link = generate_url($link_array);

echo '  <div class="hover-hide ' . $title_div . '" style="z-index: 900; position: absolute; overflow: hidden;" class="widget-title"><h4 class="box-title">' .
     '' . escape_html($title) . '</h4>' .
     '</div>' . PHP_EOL;

echo '  <div class="box-content" style="overflow: hidden">';
echo '<div style="height:100%; overflow:hidden; width: 110%;">';
echo '<a href="' . $link . '">' . $graph['img_tag'] . '</a>';
echo '</div>';
echo '  </div>';
echo '</div>';

