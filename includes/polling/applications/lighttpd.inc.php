<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

/*
echo "totalaccesses:$totalaccesses"
echo "totalkbytes:$totalkbytes"
echo "uptime:$uptime"
echo "busyservers:$busyservers"
echo "idleservers:$idleservers"
echo "connectionsp:$connectionsp"
echo "connectionsC:$connectionsC"
echo "connectionsE:$connectionsE"
echo "connectionsk:$connectionsk"
echo "connectionsr:$connectionsr"
echo "connectionsR:$connectionsR"
echo "connectionsW:$connectionsW"
echo "connectionsh:$connectionsh"
echo "connectionsq:$connectionsq"
echo "connectionsQ:$connectionsQ"
echo "connectionss:$connectionss"
echo "connectionsS:$connectionsS"
*/

if (!empty($agent_data['app']['lighttpd'])) {
    $app_id = discover_app($device, 'lighttpd');

    foreach (explode("\n", $agent_data['app']['lighttpd']) as $line) {
        [$key, $val] = explode(":", $line);
        $data[trim($key)] = intval(trim($val));
    }

    update_application($app_id, $data);
    rrdtool_update_ng($device, 'lighttpd', $data, $app_id);

    unset($app_id, $line, $data);
}

// EOF
