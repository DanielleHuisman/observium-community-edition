#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     ircbot
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// FIXME. Who still use this bot? It's most likely doesn't work.

chdir(dirname($argv[0]));

# Disable annoying messages... well... all messages actually :)
error_reporting(0);
$debug = 0;

include("includes/observium.inc.php");
include_once("includes/polling/functions.inc.php");
include_once("includes/discovery/functions.inc.php");
include_once("Net/SmartIRC.php");

# Redirect to /dev/null or logfile if you aren't using screen to keep tabs
echo "Observium Bot Starting ...\n";
echo "\n";
echo "Timestamp         Command\n";
echo "----------------- ------- \n";

class observiumbot
{

///
# HELP Function
///
    function help_info(&$irc, &$data)
    {
        $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, "Commands: !help, !log, !status, !version, !down, !port, !device, !listdevices, !fdb");

        echo date("m-d-y H:i:s ");
        echo "HELP\n";
    }

///
# VERSION Function
///
    function version_info(&$irc, &$data)
    {
        global $config;

        $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, OBSERVIUM_PRODUCT . " " . OBSERVIUM_VERSION);

        echo date("m-d-y H:i:s ");
        echo "VERSION\t\t" . OBSERVIUM_VERSION . "\n";
    }

///
# LOG Function
///
    function log_info(&$irc, &$data)
    {
        global $config;

        $entry     = dbFetchRow("SELECT `event_id`,`device_id`,`timestamp`,`message`,`type` FROM `eventlog` ORDER BY `event_id` DESC LIMIT 1");
        $device_id = $entry['device_id'];
        $device    = dbFetchRow("SELECT `hostname` FROM `devices` WHERE `device_id` = ?", [$device_id]);

        $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, $entry['event_id'] . " " . $device['hostname'] . " " . $entry['timestamp'] . " " . $entry['message'] . " " . $entry['type']);

        echo date("m-d-y H:i:s ");
        echo "LOG\n";
    }

///
# DOWN Function
///
    function down_info(&$irc, &$data)
    {
        global $config;

        foreach (dbFetchRows("SELECT * FROM `devices` WHERE status = ?", [0]) as $device) {
            $message .= $sep . $device['hostname'];
            $sep     = ", ";
        }
        if (!($message)) {
            $message = "0 host down";
        }
        $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, $message);

        echo date("m-d-y H:i:s ");
        echo "DOWN\n";
    }

///
# DEVICE Function
///
    function device_info(&$irc, &$data)
    {
        global $config;

        $hostname = $data -> messageex[1];

        $device = dbFetchRow("SELECT * FROM `devices` WHERE `hostname` = ?", [$hostname]);

        if (!$device) {
            $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, "Error: Bad or Missing hostname, use .listdevices to show all devices.");
        } else {
            if ($device['status'] == 1) {
                $status = "Up " . format_uptime($device['uptime'] . " ");
            } else {
                $status = "Down ";
            }
            if ($device['ignore']) {
                $status = "*Ignored*";
            }
            if ($device['disabled']) {
                $status = "*Disabled*";
            }

            $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, $device['os'] . " " . $device['version'] . " " .
                                                                     $device['features'] . " " . $status);

            echo date("m-d-y H:i:s ");
            echo "DEVICE\t\t" . $device['hostname'] . "\n";
        }
    }

///
# PORT Function
///
    function port_info(&$irc, &$data)
    {
        global $config;

        $hostname = $data -> messageex[1];
        $ifname   = $data -> messageex[2];

        $device = dbFetchRow("SELECT * FROM `devices` WHERE `hostname` = ?", [$hostname]);

        $sql = "SELECT *, `ports`.`port_id` as `port_id`";
        $sql .= " FROM  `ports`";
        //$sql .= " LEFT JOIN  `ports-state` ON  `ports`.port_id =  `ports-state`.port_id";
        $sql .= " WHERE ports.`ifName` = ? OR ports.`ifDescr` = ? AND ports.device_id = ?";

        $port = dbFetchRow($sql, [$ifname, $ifname, $device['device_id']]);

        $bps_in  = format_bps($port['ifInOctets_rate']);
        $bps_out = format_bps($port['ifOutOctets_rate']);
        $pps_in  = format_bi($port['ifInUcastPkts_rate']);
        $pps_out = format_bi($port['ifOutUcastPkts_rate']);

        $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, $port['ifAdminStatus'] . "/" . $port['ifOperStatus'] . " " .
                                                                 $bps_in . " > bps > " . $bps_out . " | " . $pps_in . "pps > PPS > " . $pps_out . "pps");

        echo date("m-d-y H:i:s ");
        echo "PORT\t\t\t" . $hostname . "\t" . $ifname . "\n";
    }

///
# LISTPORTS Function
///
    function list_ports(&$irc, &$data)
    {
        global $config;

        unset ($message);

        $hostname = $data -> messageex[1];

        $device = dbFetchRow("SELECT * FROM `devices` WHERE `hostname` = ?", [$hostname]);
        $ports  = dbFetchRows("SELECT * FROM `ports` WHERE device_id = ?", [$device['device_id']]);

        foreach ($ports as $port) {
            $message .= $sep . $port['ifDescr'];
            $sep     = ", ";
        }
        $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, $message);

        echo date("m-d-y H:i:s ");
        echo "LISTPORTS\t\t\t" . $hostname . "\n";

    }

///
# LISTDEVICES Function
///
    function list_devices(&$irc, &$data)
    {
        $message = '';

        foreach (dbFetchRows("SELECT `hostname` FROM `devices`") as $device) {
            $message .= $sep . $device['hostname'];
            $sep     = ", ";
        }

        $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, $message);
        unset($sep);

        echo date("m-d-y H:i:s ");
        echo "LISTDEVICES\n";
    }

///
# STATUS Function
///
    function status_info(&$irc, &$data)
    {
        $statustype = $data -> messageex[1];

        if ($statustype === "dev") {
            $devcount = array_pop(dbFetchRow("SELECT count(*) FROM devices"));
            $devup    = array_pop(dbFetchRow("SELECT count(*) FROM devices  WHERE status = '1' AND `ignore` = '0'"));
            $devdown  = array_pop(dbFetchRow("SELECT count(*) FROM devices WHERE status = '0' AND `ignore` = '0'"));
            $devign   = array_pop(dbFetchRow("SELECT count(*) FROM devices WHERE `ignore` = '1'"));
            $devdis   = array_pop(dbFetchRow("SELECT count(*) FROM devices WHERE `disabled` = '1'"));
            $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, "Devices: " . $devcount . " (" . $devup . " up, " . $devdown . " down, " . $devign . " ignored, " . $devdis . " disabled" . ")");
        } elseif ($statustype === "prt") {
            $prtcount = array_pop(dbFetchRow("SELECT count(*) FROM ports"));
            $prtup    = array_pop(dbFetchRow("SELECT count(*) FROM ports AS I, devices AS D  WHERE I.ifOperStatus = 'up' AND I.ignore = '0' AND I.device_id = D.device_id AND D.ignore = '0'"));
            $prtdown  = array_pop(dbFetchRow("SELECT count(*) FROM ports AS I, devices AS D WHERE I.ifOperStatus = 'down' AND I.ifAdminStatus = 'up' AND I.ignore = '0' AND D.device_id = I.device_id AND D.ignore = '0'"));
            $prtsht   = array_pop(dbFetchRow("SELECT count(*) FROM ports AS I, devices AS D WHERE I.ifAdminStatus = 'down' AND I.ignore = '0' AND D.device_id = I.device_id AND D.ignore = '0'"));
            $prtign   = array_pop(dbFetchRow("SELECT count(*) FROM ports AS I, devices AS D WHERE D.device_id = I.device_id AND (I.ignore = '1' OR D.ignore = '1')"));
            $prterr   = array_pop(dbFetchRow("SELECT count(*) FROM ports AS I, devices AS D WHERE D.device_id = I.device_id AND (I.ignore = '0' OR D.ignore = '0') AND (I.ifInErrors_delta > '0' OR I.ifOutErrors_delta > '0')"));
            $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, "Ports: " . $prtcount . " (" . $prtup . " up, " . $prtdown . " down, " . $prtign . " ignored, " . $prtsht . " shutdown" . ")");
        } else {
            $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, "Error: STATUS requires one of the following <dev prt srv>");
        }

        echo date("m-d-y H:i:s ");
        echo "STATUS\t\t$statustype\n";
    }

    function fdb_info(&$irc, &$data)
    {
        $hostname = $data -> messageex[1];
        if (count($data -> messageex) >= 3) {
            $ifname = $data -> messageex[2];
        } else {
            $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, "Error: Missing port name");
        }

        $device = dbFetchRow("SELECT * FROM `devices` WHERE `hostname` = ?", [$hostname]);

        if (!$device) {
            $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, "Error: Bad or Missing hostname, use .listdevices to show all devices.");
        } else {
            $sql = "SELECT `vlans_fdb`.mac_address AS mac_address, GROUP_CONCAT(`vlans_fdb`.vlan_id SEPARATOR '|') AS vlan_id FROM `vlans_fdb`";
            $sql .= " LEFT JOIN `ports` ON `ports`.port_id = `vlans_fdb`.port_id AND ports.device_id = `vlans_fdb`.device_id";
            $sql .= " WHERE (ports.`ifName` = ? OR ports.`ifDescr` = ?) AND `vlans_fdb`.device_id = ?";
            $sql .= " GROUP BY mac_address";

            $fdb = dbFetchRows($sql, [$ifname, $ifname, $device['device_id']]);

            foreach ($fdb as $mac) {
                $message .= $sep . format_mac($mac["mac_address"]) . "(vlans [$mac[vlan_id]])";
                $sep     = ", ";
            }
            $irc -> message(SMARTIRC_TYPE_CHANNEL, $data -> channel, $message);
            echo "FDB\t\t$hostname\t$ifname\n";
        }
    }
}

$bot = new observiumbot();
$irc = new Net_SmartIRC();

if (OBS_DEBUG) {
    $irc -> setDebug(SMARTIRC_DEBUG_ALL);
}

$irc -> registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!listdevices', $bot, 'list_devices');
$irc -> registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!listports', $bot, 'list_ports');
$irc -> registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!device', $bot, 'device_info');
$irc -> registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!port', $bot, 'port_info');
$irc -> registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!down', $bot, 'down_info');
$irc -> registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!version', $bot, 'version_info');
$irc -> registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!status', $bot, 'status_info');
$irc -> registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!fdb', $bot, 'fdb_info');
$irc -> registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!log', $bot, 'log_info');
$irc -> registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!help', $bot, 'help_info');

if ($config['irc_ssl']) {
    $irc -> connect('ssl://' . $config['irc_host'], $config['irc_port']);
} else {
    $irc -> connect($config['irc_host'], $config['irc_port']);
}
if (safe_empty($config['irc_nick'])) {
    // Set default nick
    $config['irc_nick'] = "Observium" . random_int(1, 99999);
}
$irc -> login($config['irc_nick'], 'Observium Bot', 0, $config['irc_nick']);

if (!safe_empty($config['irc_chankey'])) {
    $irc -> join($config['irc_chan'], $config['irc_chankey']);
} else {
    $irc -> join($config['irc_chan']);
}

$irc -> listen();
$irc -> disconnect();

// EOF
