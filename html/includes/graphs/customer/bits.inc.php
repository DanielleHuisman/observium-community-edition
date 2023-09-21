<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Generate a list of ports and then call the multi_bits grapher to generate from the list
$rrd_list = [];
foreach (dbFetchRows("SELECT * FROM `ports` AS I, `devices` AS D WHERE `port_descr_type` = 'cust' AND `port_descr_descr` = ? AND D.device_id = I.device_id", [$vars['id']]) as $port) {
    $rrd_filename = get_port_rrdfilename($port, NULL, TRUE);
    if (rrd_is_file($rrd_filename)) {
        $rrd_list[] = ['filename'  => $rrd_filename,
                       'descr'     => $port['hostname'] . "-" . $port['ifDescr'],
                       'descr_in'  => device_name($port, TRUE),
                       'descr_out' => short_ifname($port['port_label'], NULL, FALSE)]; // Options sets for skip htmlentities
    }
}

$units       = 'b';
$total_units = 'B';
$colours_in  = 'greens';
$multiplier  = "8";
$colours_out = 'blues';

$nototal = 1;

$ds_in  = "INOCTETS";
$ds_out = "OUTOCTETS";

include($config['html_dir'] . "/includes/graphs/generic_multi_bits_separated.inc.php");

// EOF
