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

echo generate_box_open();

$dirs = [];

if (isset($port_attribs['sros_egress_queues'])) {
    $dirs['Egress'] = 'Egress';
}
if (isset($port_attribs['sros_ingress_queues'])) {
    $dirs['Ingress'] = 'Ingress';
}

$graphs = ['FwdInProfOcts'  => 'Forwarded In-Profile Traffic',
           'FwdOutProfOcts' => 'Forwarded Out-Profile Traffic',
           'FwdInProfPkts'  => 'Forwarded In-Profile Packets',
           'FwdOutProfPkts' => 'Forwarded Out-Profile Packets',
           'DroInProfOcts'  => 'Dropped In-Profile Traffic',
           'DroOutProfOcts' => 'Dropped Out-Profile Traffic',
           'DroInProfPkts'  => 'Dropped In-Profile Packets',
           'DroOutProfPkts' => 'Dropped Out-Profile Packets'];


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

?>

    <table class="table table-striped  table-condensed">

        <?php

        if ($vars['subview'] == 'queues') {

            foreach (['ingress', 'egress'] as $dir) {
                $queues = json_decode(get_entity_attrib('port', $port['port_id'], 'sros_' . $dir . '_queues'));
                foreach ($queues as $queue) {

                    if (isset($config['sros_queues'][$dir]['labels'][$queue])) {
                        $label = $config['sros_queues'][$dir]['labels'][$queue] . ' (' . $queue . ')';
                    } else {
                        $label = 'Queue ' . $queue;
                    }

                    echo('<tr><td>');
                    echo('<h3>' . ucfirst($dir) . ' ' . $label . '</h3>');
                    foreach (['bits', 'pkts'] as $metric) {
                        $graph_array['type']   = "port_sros_queue";
                        $graph_array['queue']  = $queue;
                        $graph_array['dir']    = $dir;
                        $graph_array['metric'] = $metric;
                        print_graph_row_port($graph_array, $port);
                        unset($graph_array['queue']);
                        unset($graph_array['dir']);
                        unset($graph_array['metric']);
                    }
                    echo('</td></tr>');
                }
            }

        } elseif ($vars['subview'] == 'overview') {


            foreach ($dirs as $dir) {
                foreach ($graphs as $type => $text) {
                    echo('<tr><td>');
                    echo('<h3>' . $dir . ' ' . $text . '</h3>');
                    $graph_array['type'] = "port_sros_" . $dir . $type;
                    print_graph_row_port($graph_array, $port);
                    echo('</td></tr>');
                }
            }

        }

        ?>

    </table>

<?php

