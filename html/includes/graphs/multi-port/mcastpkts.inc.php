<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) Adam Armstrong
 *
 */


$units       = '';
$unit_text   = 'Packets/sec';
$total_units = '';
$colours_in  = 'purples';
$multiplier  = "1";
$colours_out = 'oranges';


$ds_in  = "INMULTICASTPKTS";
$ds_out = "OUTMULTICASTPKTS";

$nototal = 1;

$i = 1;

$rrd_list = [];

foreach ($vars['id'] as $ifid) {
    if (strstr($ifid, "!")) {
        $rrd_inverted[$i] = TRUE;
        $ifid             = str_replace("!", "", $ifid);
    }

    $port = dbFetchRow("SELECT * FROM `ports` AS I, devices AS D WHERE I.port_id = ? AND I.device_id = D.device_id", [$ifid]);
    humanize_port($port);
    $rrd_file = get_port_rrdfilename($port, NULL, TRUE);
    if (rrd_is_file($rrd_file)) {
        $rrd_list[$i]['filename']  = $rrd_file;
        $rrd_list[$i]['descr']     = $port['hostname'];
        $rrd_list[$i]['descr_out'] = $port['port_label'];
        $rrd_list[$i]['ds_in']     = $ds_in;
        $rrd_list[$i]['ds_out']    = $ds_out;
        $i++;
    }
}


include($config['html_dir'] . "/includes/graphs/generic_multi_separated.inc.php");

// EOF
