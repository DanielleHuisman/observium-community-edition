<?php

if ($_SESSION['userlevel'] <= 5) {
    print_error_permission();
    return;
}

$dirs = ['ingress', 'egress'];

$navbar['brand'] = "SROS CoS Queues";
$navbar['class'] = "navbar-narrow";

foreach ($dirs as $dir) {
    if (!isset($vars['dir'])) {
        $vars['dir'] = $dir;
    }
    $navbar['options'][$dir] = ['url'  => generate_url($vars, ['dir' => $dir]),
                                'text' => nicecase($dir)
    ];
    if ($vars['dir'] == $dir) {
        $navbar['options'][$dir]['class'] = 'active';
    }
}

$rows = dbFetchRows("SELECT * FROM `entity_attribs` WHERE `attrib_type` = 'sros_" . $vars['dir'] . "_queues'");

foreach ($rows as $row) {
    $queue_list = json_decode($row['attrib_value']);

    foreach ($queue_list as $queue_entry) {
        $queues[$queue_entry] = $queue_entry;
    }
}

foreach ($queues as $queue) {
    if (!isset($vars['queue'])) {
        $vars['queue'] = $queue;
    }

    if (isset($config['sros_queues'][$vars['dir']]['labels'][$queue])) {
        $label = $config['sros_queues'][$vars['dir']]['labels'][$queue] . ' (' . $queue . ')';
    } else {
        $label = 'Queue ' . $queue;
    }

    $navbar['options']['queue']['suboptions'][$queue]['text'] = $label;
    $navbar['options']['queue']['suboptions'][$queue]['url']  = generate_url($vars, ['queue' => $queue]);

    if ($vars['queue'] == $queue) {
        $navbar['options']['queue']['suboptions'][$queue]['class'] = 'active';
        $navbar['options']['queue']['text']                        = $label;
    }

}

print_navbar($navbar);
unset($navbar);

$graphs = ['FwdInProfOcts'  => 'Forwarded In-Profile Traffic',
           'FwdOutProfOcts' => 'Forwarded Out-Profile Traffic',
           'FwdInProfPkts'  => 'Forwarded In-Profile Packets',
           'FwdOutProfPkts' => 'Forwarded Out-Profile Packets',
           'DroInProfOcts'  => 'Dropped In-Profile Traffic',
           'DroOutProfOcts' => 'Dropped Out-Profile Traffic',
           'DroInProfPkts'  => 'Dropped In-Profile Packets',
           'DroOutProfPkts' => 'Dropped Out-Profile Packets'];

echo generate_box_open();
echo('<table class="table table-condensed table-striped table-hover ">');


foreach ($graphs as $type => $text) {

    $graph_array           = $vars;
    $graph_array['type']   = "global_sros_queues";
    $graph_array['ds']     = $type;
    $graph_array['legend'] = 'no';

    echo('<tr><td>');
    echo '<h3>' . $text . '</h3>';
    print_graph_row($graph_array);
    echo('</td></tr>');


}

echo('</table>');
echo generate_box_close();

?>
