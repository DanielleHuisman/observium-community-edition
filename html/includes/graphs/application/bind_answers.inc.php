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

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$colours      = "mixed";
$nototal      = (($width < 224) ? 1 : 0);
$unit_text    = "Count";
$rrd_filename = get_rrd_path($device, "app-bind-" . $app['app_id'] . "-ns-stats.rrd");

$array = [
  'Response'     => ['descr' => "Responses sent", 'colour' => '999999'],
  'QrySuccess'   => ['descr' => "Successful answers", 'colour' => '33cc33'],
  'QryAuthAns'   => ['descr' => "Authoritative answer", 'colour' => '009900'],
  'QryNoauthAns' => ['descr' => "Non-authoritative answer", 'colour' => '336633'],
  'QryReferral'  => ['descr' => "Referral answer", 'colour' => '996633'],
  'QryNxrrset'   => ['descr' => "Empty answers", 'colour' => '36393d'],
  'QrySERVFAIL'  => ['descr' => "SERVFAIL answer", 'colour' => 'ff3333'],
  'QryFORMERR'   => ['descr' => "FORMERR answer", 'colour' => 'ffcccc'],
  'QryNXDOMAIN'  => ['descr' => "NXDOMAIN answers", 'colour' => 'ff33ff'],
  'QryDropped'   => ['descr' => "Dropped queries", 'colour' => '666666'],
  'QryFailure'   => ['descr' => "Failed queries", 'colour' => 'ff0000'],
  'XfrReqDone'   => ['descr' => "Transfers completed", 'colour' => '6666ff'],
];
$i     = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $data['colour'];
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
