<?php

if ($_SESSION['userlevel'] <= 5) {
    print_error_permission();
    return;
}

$dirs = ['ingress', 'egress'];

$navbar['brand'] = "Juniper CoS Queues";
$navbar['class'] = "navbar-narrow";

$rows = dbFetchRows("SELECT * FROM `entity_attribs` WHERE `attrib_type` = 'jnx_cos_queues'");

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

    if (isset($config['jnx_cos_queues'][$vars['dir']]['labels'][$queue])) {
        $label = $config['jnx_cos_queues'][$vars['dir']]['labels'][$queue] . ' (' . $queue . ')';
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

$graphs = ['QedBytes'          => 'Queued Bits',
           'QedPkts'           => 'Queued Packets',
           'TailDropPkts'      => 'Tail Dropped Packets',
           'TotalRedDropPkts'  => 'RED Dropped Packets',
           'TotalRedDropBytes' => 'RED Dropped Bits',
];

echo generate_box_open();
echo('<table class="table table-condensed table-striped table-hover ">');


foreach ($graphs as $type => $text) {

    $graph_array           = $vars;
    $graph_array['type']   = "global_jnx_cos_queues";
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
