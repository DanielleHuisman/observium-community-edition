<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) Adam Armstrong
 *
 */

if (!is_array($vars['id'])) {
    $vars['id'] = [$vars['id']];
}

$i = 0;

$sql = 'SELECT `ports`.*, `devices`.`hostname` FROM `ports` LEFT JOIN `devices` USING (`device_id`) WHERE ' .
       generate_query_values($vars['id'], 'ports.port_id');
foreach (dbFetchRows($sql) as $port) {
    //$port    = dbFetchRow("SELECT * FROM `ports` AS I, devices AS D WHERE I.port_id = ? AND I.device_id = D.device_id", [$ifid]);
    $rrdfile = get_port_rrdfilename($port, "ipv6-octets", TRUE);
    if (rrd_is_file($rrdfile)) {
        //humanize_port($port);
        $rrd_list[$i]['filename']  = $rrdfile;
        $rrd_list[$i]['descr']     = $port['hostname'] . " " . $port['ifDescr'];
        $rrd_list[$i]['descr_in']  = $port['hostname'];
        $rrd_list[$i]['descr_out'] = $port['port_label_short']; // Options sets for skip htmlentities
        $i++;
    }
}

$units       = 'b';
$total_units = 'B';
$colours_in  = 'greens';
$multiplier  = "8";
$colours_out = 'blues';

$ds_in  = "InOctets";
$ds_out = "OutOctets";

include($config['html_dir'] . "/includes/graphs/generic_multi_bits_separated.inc.php");

// EOF
