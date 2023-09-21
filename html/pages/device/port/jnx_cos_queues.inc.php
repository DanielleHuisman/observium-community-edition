<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */


foreach (json_decode($attribs['jnx_cos_queues'], TRUE) as $data) {
    if (isset($data['queue'])) {
        $queues[$data['queue']] = $data;
    }
}

$navbar['class'] = "navbar-narrow";
$navbar['brand'] = "CoS Queues";

foreach (['overview' => 'Overview', 'queues' => 'Per-Queue'] as $subview => $text) {
    if (!isset($vars['subview'])) {
        $vars['subview'] = $subview;
    }
    if ($vars['subview'] == $subview) {
        $navbar['options'][$subview]['class'] = "active";
    }
    $navbar['options'][$subview]['url']  = generate_url($vars, ['subview' => $subview]);
    $navbar['options'][$subview]['text'] = $text;
}

print_navbar($navbar);

echo generate_box_open();

?>

    <table class="table table-striped  table-condensed">

        <?php

        if ($vars['subview'] == 'queues') {

            $port_queues = json_decode(get_entity_attrib('port', $port['port_id'], 'jnx_cos_queues'), TRUE);
            foreach ($port_queues as $queue) {

                $text = $queue;
                if (isset($queues[$queue]['name'])) {
                    $text .= ' - ' . $queues[$queue]['name'];
                }
                if (isset($queues[$queue]['prio'])) {
                    $text .= ' (' . $queues[$queue]['prio'] . ')';
                }

                echo('<tr><td>');
                echo('<h3>' . ucfirst($dir) . ' Queue ' . $text . '</h3>');

                foreach (['bits', 'pkts'] as $metric) {
                    $graph_array['type']   = "port_jnx_cos_queue";
                    $graph_array['queue']  = $queue;
                    $graph_array['metric'] = $metric;
                    print_graph_row_port($graph_array, $port);
                    unset($graph_array['queue']);
                    unset($graph_array['metric']);
                }
                echo('</td></tr>');
            }

        } elseif ($vars['subview'] == 'overview') {


            $graphs = ['QedBytes'          => 'Queued Bits',
                       'QedPkts'           => 'Queued Packets',
                       'TailDropPkts'      => 'Tail Dropped Packets',
                       'TotalRedDropPkts'  => 'RED Dropped Packets',
                       'TotalRedDropBytes' => 'RED Dropped Bits',
            ];


            foreach ($graphs as $graphtype => $text) {
                echo '<tr><td>';
                echo '<h3>' . $text . '</h3>';
                $graph_array['type'] = 'port_jnx_' . $graphtype;
                print_graph_row_port($graph_array, $port);
                echo '</td></tr>';
            }

        }

        ?>

    </table>

<?php

echo generate_box_close();
