<?php
// Observium DB Port Datasource.

// TARGET obs_port:device_id|hostname:ifIndex|ifAlias
// We prefer device_id to survive hostname changes and we prefer ifIndex to survive irritating add/removals.
// ifAlias is provided for those who prefer. Probably won't be used officially, though.

class WeatherMapDataSource_obsdb extends WeatherMapDataSource
{

    function Init(&$map)
    {
        if (!function_exists("dbFetchRow")) {
            return FALSE;
        }
        return (TRUE);
    }

    // Return TRUE if we can handle this target string
    function Recognise($targetstring)
    {
        if (preg_match("/^obs_port:([^:]+):([^:]+)$/", $targetstring, $matches) || preg_match("/^obs_port:([^:]+)$/", $targetstring, $matches)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function ReadData($targetstring, &$map, &$item)
    {
        $data[IN]  = NULL;
        $data[OUT] = NULL;
        $data_time = 0;

        if (preg_match("/^obs_port:([^:]+):([^:]+)$/", $targetstring, $matches) || preg_match("/^obs_port:([^:]+)$/", $targetstring, $matches)) {

            // Get device from Observium DB by device_id or by hostname
            if (count($matches) == 2) {
                $device = device_by_id_cache(get_device_id_by_port_id($matches[1]));
            } elseif (is_numeric($matches[1])) {
                $device = device_by_id_cache($matches[1]);
            } else {
                $device = device_by_name($matches[1]);
            }

            if (is_array($device)) {
                if (count($matches) == 2) {
                    $port = get_port_by_id_cache($matches[1]);

                    if($port['device_id'] != $device['device_id'])
                    {
                        unset($port);
                        wm_warn("Port didn't match device.");
                    }

                } else {
                    if (is_numeric($matches[2])) {
                        $port = get_port_by_ifIndex($device['device_id'], $matches[2]);
                    } else {
                        $port = get_port_by_ifAlias($device['device_id'], $matches[2]);
                    }
                }

                if (is_array($port)) {
                    $data[IN]  = $port['ifInOctets_rate'] * 8;
                    $data[OUT] = $port['ifOutOctets_rate'] * 8;
                    $data_time = $port['poll_time'];

                    $item -> add_note("Errors Rate", $port['ifErrors_rate']);
                    $item -> add_note("PPS In Rate", $port['ifInNUcastPkts_rate'] + $port['ifInUcastPkts_rate']);
                    $item -> add_note("PPS Out Rate", $port['ifOutNUcastPkts_rate'] + $port['ifOutUcastPkts_rate']);

                } else {
                    wm_warn("ObsPortDB: Couldn't find port");
                }
            } else {
                wm_warn("ObsPortDB: Couldn't find device");
            }
        }

        wm_debug("ObsPortDB ReadData: Returning (" . ($data[IN] === NULL ? 'NULL' : $data[IN]) . "," . ($data[OUT] === NULL ? 'NULL' : $data[IN]) . ",$data_time)\n");

        return ([$data[IN], $data[OUT], $data_time]);
    }
}

// vim:ts=4:sw=4:
?>
