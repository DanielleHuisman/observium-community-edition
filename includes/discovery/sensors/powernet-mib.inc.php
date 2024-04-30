<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

if (match_oid_num($device['sysObjectID'], '.1.3.6.1.4.1.(5528|52674)')) {
    // NetBotz v4/v5
    return;
}

$mib = 'PowerNet-MIB';

#### UPS #############################################################################################

$scale     = 0.1;    // Default scale
$scale_min = 1 / 60; // Scale for minutes
$inputs    = snmp_get_oid($device, 'upsPhaseNumInputs.0', 'PowerNet-MIB');
$outputs   = snmp_get_oid($device, 'upsPhaseNumOutputs.0', 'PowerNet-MIB');

echo('Caching OIDs: ');
$cache['apc'] = [];

// Check if we have values for these, if not, try other code paths below.
if ($inputs || $outputs) {
    foreach (['upsPhaseInputTable', 'upsPhaseOutputTable', 'upsPhaseInputPhaseTable', 'upsPhaseOutputPhaseTable'] as $table) {
        echo("$table ");
        $cache['apc'] = snmpwalk_cache_oid($device, $table, $cache['apc'], 'PowerNet-MIB');
    }
    print_debug_vars($cache['apc']);

    // Process each input, per phase
    for ($i = 1; $i <= $inputs; $i++) {
        $name   = snmp_hexstring($cache['apc'][$i]['upsPhaseInputName']);
        $phases = $cache['apc'][$i]['upsPhaseNumInputPhases'];
        $tindex = $cache['apc'][$i]['upsPhaseInputTableIndex'];
        $itype  = $cache['apc'][$i]['upsPhaseInputType'];

        if ($itype === 'bypass' || empty($name)) {
            // Override "Input 2" in case of bypass.
            $name = $itype === 'main' ? 'Input' : nicecase($itype);
        }

        for ($p = 1; $p <= $phases; $p++) {
            // FIXME. I not know why was used this index before.. I keep compat with it
            $pindex = isset($cache['apc']["$tindex.1.$p"]) ? "$tindex.1.$p" : "$tindex.$p";

            $descr = "$name Phase $p";

            $oid   = ".1.3.6.1.4.1.318.1.1.1.9.2.3.1.6.$pindex";
            $value = $cache['apc'][$pindex]['upsPhaseInputCurrent'];

            if ($value != '' && $value != -1) {
                discover_sensor('current', $device, $oid, "upsPhaseInputCurrent.$pindex", 'apc', $descr, $scale, $value);
            }

            $oid_name = 'upsPhaseInputPower';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.2.3.1.9.$pindex";
            //$type     = $mib . '-' . $oid_name;
            $value = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
                discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $pindex, NULL, $descr, 1, $value);
            }

            $oid_name = 'upsPhaseInputApparentPower';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.2.3.1.12.$pindex";
            //$type     = $mib . '-' . $oid_name;
            $value = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
                discover_sensor_ng($device, 'apower', $mib, $oid_name, $oid_num, $pindex, NULL, $descr, 1000, $value);
            }

            $oid_name = 'upsPhaseInputPowerFactor';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.2.3.1.13.$pindex";
            //$type     = $mib . '-' . $oid_name;
            $value = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
                discover_sensor_ng($device, 'powerfactor', $mib, $oid_name, $oid_num, $pindex, NULL, $descr, 0.01, $value);
            }

            $oid   = ".1.3.6.1.4.1.318.1.1.1.9.2.3.1.3.$pindex";
            $value = $cache['apc'][$pindex]['upsPhaseInputVoltage'];

            if ($value != '' && $value != -1) {
                discover_sensor('voltage', $device, $oid, "upsPhaseInputVoltage.$pindex", 'apc', $descr, 1, $value);
            }

            $oid_name = 'upsPhaseInputVoltagePN';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.2.3.1.14.$pindex";
            //$type     = $mib . '-' . $oid_name;
            $value = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
                discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $pindex, NULL, $descr . " to Neutral", 1, $value);
            }
        }

        // Frequency is reported only once per input
        $descr = $name;
        $index = "upsPhaseInputFrequency.$tindex";
        $oid   = ".1.3.6.1.4.1.318.1.1.1.9.2.2.1.4.$tindex";
        $value = $cache['apc'][$i]['upsPhaseInputFrequency'];

        if ($value != '' && $value != -1) {
            discover_sensor('frequency', $device, $oid, $index, 'apc', $descr, $scale, $value);
        }
    }

    // Process each output, per phase
    for ($o = 1; $o <= $outputs; $o++) {
        $name = "Output";
        if ($outputs > 1) {
            $name .= " $o";
        } // Output doesn't have a name in the MIB, add number if >1
        $phases = $cache['apc'][$o]['upsPhaseNumOutputPhases'];
        $tindex = $cache['apc'][$o]['upsPhaseOutputTableIndex'];

        for ($p = 1; $p <= $phases; $p++) {
            // FIXME. I not know why was used this index before.. I keep compat with it
            $pindex = isset($cache['apc']["$tindex.1.$p"]) ? "$tindex.1.$p" : "$tindex.$p";

            $descr = "$name Phase $p";

            $oid   = ".1.3.6.1.4.1.318.1.1.1.9.3.3.1.4.$pindex";
            $value = $cache['apc'][$pindex]['upsPhaseOutputCurrent'];

            if ($value != '' && $value != -1) {
                discover_sensor('current', $device, $oid, "upsPhaseOutputCurrent.$pindex", 'apc', $descr, $scale, $value);
            }

            $oid   = ".1.3.6.1.4.1.318.1.1.1.9.3.3.1.3.$pindex";
            $value = $cache['apc'][$pindex]['upsPhaseOutputVoltage'];

            if ($value != '' && $value != -1) {
                discover_sensor('voltage', $device, $oid, "upsPhaseOutputVoltage.$pindex", 'apc', $descr, 1, $value);
            }

            $oid_name = 'upsPhaseOutputPercentPower';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.3.3.1.16.$pindex";
            $type     = $mib . '-' . $oid_name;
            $value    = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
                $options = ['rename_rrd_full' => 'capacity-apc-upsPhaseOutputPercentPower.%index%'];
                discover_sensor('load', $device, $oid_num, $pindex, $type, "$descr Load", 1, $value, $options);
            }

            $oid_name = 'upsPhaseOutputLoad';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.3.3.1.7.$pindex";
            $type     = $mib . '-' . $oid_name;
            $value    = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
                discover_sensor('apower', $device, $oid_num, $pindex, $type, $descr, 1, $value);
            }

            $oid_name = 'upsPhaseOutputPower';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.3.3.1.13.$pindex";
            //$type     = $mib . '-' . $oid_name;
            $value = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
                discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $pindex, NULL, $descr, 1, $value);
            }

            $oid_name = 'upsPhaseOutputPowerFactor';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.3.3.1.19.$pindex";
            //$type     = $mib . '-' . $oid_name;
            $value = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
                discover_sensor_ng($device, 'powerfactor', $mib, $oid_name, $oid_num, $pindex, NULL, $descr, 0.01, $value);
            }

            $oid_name = 'upsPhaseOutputVoltagePN';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.3.3.1.22.$pindex";
            //$type     = $mib . '-' . $oid_name;
            $value = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
                discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $pindex, NULL, $descr . " to Neutral", 1, $value);
            }

            /*
            $oid_name = 'upsPhaseOutputPercentLoad';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.3.3.1.10.$pindex";
            $type     = $mib . '-' . $oid_name;
            $value    = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
              discover_sensor('load', $device, $oid_num, $pindex, $type, "$descr Load", 1, $value);
            }
            */

            // New Oid: upsPhaseOutputEnergyUsage kWh
            $oid_name = 'upsPhaseOutputEnergyUsage';
            $oid_num  = ".1.3.6.1.4.1.318.1.1.1.9.3.3.1.23.$pindex";
            $value = $cache['apc'][$pindex][$oid_name];

            if ($value != '' && $value != -1) {
                discover_counter($device, 'energy', $mib, $oid_name, $oid_num, $pindex, $descr, 1000, $value);
            }
        }

        // Frequency is reported only once per output
        $descr = $name;
        $oid   = ".1.3.6.1.4.1.318.1.1.1.9.3.2.1.4.$tindex";
        $value = $cache['apc'][$o]['upsPhaseOutputFrequency'];

        if ($value != '' && $value != -1) {
            discover_sensor('frequency', $device, $oid, "upsPhaseOutputFrequency.$tindex", 'apc', $descr, $scale, $value);
        }
    }

} else {

    // Try older UPS tables: "HighPrec" table first, with fallback to "Adv".
    foreach (["upsHighPrecInput", "upsHighPrecOutput", "upsAdvInput", "upsAdvOutput"] as $table) {
        echo("$table ");
        $cache['apc'] = snmpwalk_cache_oid($device, $table, $cache['apc'], "PowerNet-MIB");
    }

    foreach ($cache['apc'] as $index => $entry) {
        if (isset($entry['upsHighPrecInputLineVoltage']) || isset($entry['upsHighPrecInputFrequency'])) {
            /*
            $oid   = ".1.3.6.1.4.1.318.1.1.1.3.3.1.$index";
            $descr = "Input";
            $value = $entry['upsHighPrecInputLineVoltage'];

            if ($value != '' && $value != -1)
            {
              discover_sensor('voltage', $device, $oid, "upsHighPrecInputLineVoltage.$index", 'apc', $descr, $scale, $value);
            }
            */

            $oid   = ".1.3.6.1.4.1.318.1.1.1.3.3.4.$index";
            $descr = "Input";
            $value = $entry['upsHighPrecInputFrequency'];

            if ($value != '' && $value != -1) {
                discover_sensor('frequency', $device, $oid, "upsHighPrecInputFrequency.$index", 'apc', $descr, $scale, $value);
            }

            $oid   = ".1.3.6.1.4.1.318.1.1.1.4.3.1.$index";
            $descr = "Output";
            $value = $entry['upsHighPrecOutputVoltage'];

            if ($value != '' && $value != -1) {
                discover_sensor('voltage', $device, $oid, "upsHighPrecOutputVoltage.$index", 'apc', $descr, $scale, $value);
            }

            $oid   = ".1.3.6.1.4.1.318.1.1.1.4.3.4.$index";
            $descr = "Output";
            $value = $entry['upsHighPrecOutputCurrent'];

            if ($value != '' && $value != -1) {
                discover_sensor('current', $device, $oid, "upsHighPrecOutputCurrent.$index", 'apc', $descr, $scale, $value);
            }

            $oid   = ".1.3.6.1.4.1.318.1.1.1.4.3.2.$index";
            $descr = "Output";
            $value = $entry['upsHighPrecOutputFrequency'];

            if ($value != '' && $value != -1) {
                discover_sensor('frequency', $device, $oid, "upsHighPrecOutputFrequency.$index", 'apc', $descr, $scale, $value);
            }

            /*
            $oid   = ".1.3.6.1.4.1.318.1.1.1.4.3.3.$index";
            $descr = "Output Load";
            $value = $entry['upsHighPrecOutputLoad'];

            if ($value != '' && $value != -1) {
              $limits = array('limit_high' => 85, 'limit_high_warn' => 70);
              discover_sensor('capacity', $device, $oid, "upsHighPrecOutputLoad.$index", 'apc', $descr, $scale, $value, $limits);
            }
            */

            $oid   = ".1.3.6.1.4.1.318.1.1.1.4.3.6.$index";
            $descr = "Output Energy";
            $value = $entry['upsHighPrecOutputEnergyUsage'];

            if ($value != '' && $value != -1) {
                discover_counter($device, 'energy', $mib, 'upsHighPrecOutputEnergyUsage', $oid, $index, $descr, 10, $value);
            }
        } elseif (isset($entry['upsAdvInputLineVoltage']) || isset($entry['upsAdvInputFrequency'])) {
            // Fallback to lower precision table if HighPrec table is not available and Adv table is.
            /*
            $oid   = ".1.3.6.1.4.1.318.1.1.1.3.2.1.$index";
            $descr = "Input";
            $value = $entry['upsAdvInputLineVoltage'];

            if ($value != '' && $value != -1) {
              discover_sensor('voltage', $device, $oid, "upsAdvInputLineVoltage.$index", 'apc', $descr, 1, $value);
            }
            */

            $oid   = ".1.3.6.1.4.1.318.1.1.1.3.2.4.$index";
            $descr = "Input";
            $value = $entry['upsAdvInputFrequency'];

            if ($value != '' && $value != -1) {
                discover_sensor('frequency', $device, $oid, "upsAdvInputFrequency.$index", 'apc', $descr, 1, $value);
            }

            $oid   = ".1.3.6.1.4.1.318.1.1.1.4.2.1.$index";
            $value = $entry['upsAdvOutputVoltage'];
            $descr = "Output";

            if ($value != '' && $value != -1) {
                discover_sensor('voltage', $device, $oid, "upsAdvOutputVoltage.$index", 'apc', $descr, 1, $value);
            }

            $oid   = ".1.3.6.1.4.1.318.1.1.1.4.2.4.$index";
            $descr = "Output";
            $value = $entry['upsAdvOutputCurrent'];

            if ($value != '' && $value != -1) {
                discover_sensor('current', $device, $oid, "upsAdvOutputCurrent.$index", 'apc', $descr, 1, $value);
            }

            $oid   = ".1.3.6.1.4.1.318.1.1.1.4.2.2.$index";
            $descr = "Output";
            $value = $entry['upsAdvOutputFrequency'];

            if ($value != '' && $value != -1) {
                discover_sensor('frequency', $device, $oid, "upsAdvOutputFrequency.$index", 'apc', $descr, 1, $value);
            }

            /*
            $oid   = ".1.3.6.1.4.1.318.1.1.1.4.2.3.$index";
            $descr = "Output Load";
            $value = $entry['upsAdvOutputLoad'];

            if ($value != '' && $value != -1) {
              $limits = array('limit_high' => 85, 'limit_high_warn' => 70);
              discover_sensor('capacity', $device, $oid, "upsAdvOutputLoad.$index", 'apc', $descr, 1, $value, $limits);
            }
            */

            $oid   = ".1.3.6.1.4.1.318.1.1.1.4.2.12.$index";
            $descr = "Output Energy";
            $value = $entry['upsAdvOutputEnergyUsage'];

            if ($value != '' && $value != -1) {
                discover_counter($device, 'energy', $mib, 'upsHighPrecOutputEnergyUsage', $oid, $index, $descr, 1000, $value);
            }
        }

        if ($entry['upsAdvInputLineFailCause']) {
            $descr = "Last InputLine Fail Cause";
            $oid   = ".1.3.6.1.4.1.318.1.1.1.3.2.5.$index";
            // This does not reset after the failure is over, so we won't collect it for the time being.
            //discover_status($device, $oid, "upsAdvInputLineFailCause.$index", 'powernet-upsadvinputfail-state', $descr, $entry['upsAdvInputLineFailCause'], array('entPhysicalClass' => 'other'));
        }
    }
}

// Try UPS battery tables: "HighPrec" table first, with fallback to "Adv".
$cache['apc'] = [];

// upsHighPrecBatteryCapacity.0 = 994
// upsHighPrecBatteryTemperature.0 = 270
// upsHighPrecBatteryActualVoltage.0 = 820
foreach (["upsHighPrecBattery", "upsAdvBattery", "upsBasicBattery"] as $table) {
    echo("$table ");
    $cache['apc'] = snmpwalk_cache_oid($device, $table, $cache['apc'], "PowerNet-MIB");
    if ($table === 'upsAdvBattery' && !snmp_status()) {
        break;
    } // Do not query BasicBattery if Adv empty
}

foreach ($cache['apc'] as $index => $entry) {
    $descr = "Battery";

    if ($entry['upsHighPrecBatteryTemperature'] && $entry['upsHighPrecBatteryTemperature'] != -1) {
        $oid   = ".1.3.6.1.4.1.318.1.1.1.2.3.2.$index";
        $value = $entry['upsHighPrecBatteryTemperature'];

        discover_sensor('temperature', $device, $oid, "upsHighPrecBatteryTemperature.$index", 'apc', $descr, $scale, $value);
    } elseif ($entry['upsAdvBatteryTemperature'] && $entry['upsAdvBatteryTemperature'] != -1) {
        $oid   = ".1.3.6.1.4.1.318.1.1.1.2.2.2.$index";
        $value = $entry['upsAdvBatteryTemperature'];

        discover_sensor('temperature', $device, $oid, "upsAdvBatteryTemperature.$index", 'apc', $descr, 1, $value);
    }

    /*
    $descr = "Battery Nominal Voltage";

    if ($entry['upsHighPrecBatteryNominalVoltage'] && $entry['upsHighPrecBatteryNominalVoltage'] != -1) {
      $oid   = ".1.3.6.1.4.1.318.1.1.1.2.3.3.$index";
      $value = $entry['upsHighPrecBatteryNominalVoltage'];
      discover_sensor('voltage', $device, $oid, "upsHighPrecBatteryNominalVoltage.$index", 'apc', $descr, $scale, $value);
    } elseif ($entry['upsAdvBatteryNominalVoltage'] && $entry['upsAdvBatteryNominalVoltage'] != -1) {
      $oid   = ".1.3.6.1.4.1.318.1.1.1.2.2.7.$index";
      $value = $entry['upsAdvBatteryNominalVoltage'];
      discover_sensor('voltage', $device, $oid, "upsAdvBatteryNominalVoltage.$index", 'apc', $descr, 1, $value);
    }

    $descr = "Battery Actual Voltage";

    if ($entry['upsHighPrecBatteryActualVoltage'] && $entry['upsHighPrecBatteryActualVoltage'] != -1)
    {
      $oid   = ".1.3.6.1.4.1.318.1.1.1.2.3.4.$index";
      $value = $entry['upsHighPrecBatteryActualVoltage'];
      discover_sensor('voltage', $device, $oid, "upsHighPrecBatteryActualVoltage.$index", 'apc', $descr, $scale, $value);
    }
    elseif ($entry['upsAdvBatteryActualVoltage'] && $entry['upsAdvBatteryActualVoltage'] != -1)
    {
      $oid   = ".1.3.6.1.4.1.318.1.1.1.2.2.8.$index";
      $value = $entry['upsAdvBatteryActualVoltage'];
      discover_sensor('voltage', $device, $oid, "upsAdvBatteryActualVoltage.$index", 'apc', $descr, 1, $value);
    }
    */

    $descr = "Battery";

    if ($entry['upsHighPrecBatteryCurrent'] && $entry['upsHighPrecBatteryCurrent'] != -1) {
        $oid   = ".1.3.6.1.4.1.318.1.1.1.2.3.5.$index";
        $value = $entry['upsHighPrecBatteryCurrent'];

        discover_sensor('current', $device, $oid, "upsHighPrecBatteryCurrent.$index", 'apc', $descr, $scale, $value);
    } elseif ($entry['upsAdvBatteryCurrent'] && $entry['upsAdvBatteryCurrent'] != -1) {
        $oid   = ".1.3.6.1.4.1.318.1.1.1.2.2.9.$index";
        $value = $entry['upsAdvBatteryCurrent'];

        discover_sensor('current', $device, $oid, "upsAdvBatteryCurrent.$index", 'apc', $descr, 1, $value);
    }

    $descr = "Total DC";

    if ($entry['upsHighPrecTotalDCCurrent'] && $entry['upsHighPrecTotalDCCurrent'] != -1) {
        $oid   = ".1.3.6.1.4.1.318.1.1.1.2.3.6.$index";
        $value = $entry['upsHighPrecTotalDCCurrent'];

        discover_sensor('current', $device, $oid, "upsHighPrecTotalDCCurrent.$index", 'apc', $descr, $scale, $value);
    } elseif ($entry['upsAdvTotalDCCurrent'] && $entry['upsAdvTotalDCCurrent'] != -1) {
        $oid   = ".1.3.6.1.4.1.318.1.1.1.2.2.10.$index";
        $value = $entry['upsAdvTotalDCCurrent'];

        discover_sensor('current', $device, $oid, "upsAdvTotalDCCurrent.$index", 'apc', $descr, 1, $value);
    }

    /*
    $descr = "Battery Capacity";

    if ($entry['upsHighPrecBatteryCapacity'] && $entry['upsHighPrecBatteryCapacity'] != -1)
    {
      $oid    = ".1.3.6.1.4.1.318.1.1.1.2.3.1.$index";
      $value  = $entry['upsHighPrecBatteryCapacity'];
      $limits = array('limit_low' => 15, 'limit_low_warn' => 30);
      discover_sensor('capacity', $device, $oid, "upsHighPrecBatteryCapacity.$index", 'apc', $descr, $scale, $value, $limits);
    }
    elseif ($entry['upsAdvBatteryCapacity'] && $entry['upsAdvBatteryCapacity'] != -1)
    {
      $oid   = ".1.3.6.1.4.1.318.1.1.1.2.2.1.$index";
      $value = $entry['upsAdvBatteryCapacity'];
      $limits = array('limit_low' => 15, 'limit_low_warn' => 30);
      discover_sensor('capacity', $device, $oid, "upsAdvBatteryCapacity.$index", 'apc', $descr, 1, $value, $limits);
    }
    */

    /*
    $descr = "Battery Runtime Remaining";

    if ($entry['upsAdvBatteryRunTimeRemaining'])
    {
      // Runtime stores data in minutes
      $oid       = ".1.3.6.1.4.1.318.1.1.1.2.2.3.$index";
      $value     = timeticks_to_sec($entry['upsAdvBatteryRunTimeRemaining']);
      $limit_low = snmp_get_oid($device, "upsAdvConfigLowBatteryRunTime.$index", "PowerNet-MIB");
      $limit_low = timeticks_to_sec($limit_low);
      $limits    = array('limit_low' => (is_numeric($limit_low) ? $limit_low * $scale_min : 2));

      discover_sensor('runtime', $device, $oid, "upsAdvBatteryRunTimeRemaining.$index", 'apc', $descr, $scale_min, $value, $limits);
    }
    */

    $descr = "Battery Status";
    if ($entry['upsBasicBatteryStatus']) {
        $oid = ".1.3.6.1.4.1.318.1.1.1.2.1.1.$index";

        discover_status_ng($device, $mib, 'upsBasicBatteryStatus', $oid, $index, 'powernet-upsbattery-state', $descr, $entry['upsBasicBatteryStatus'], ['entPhysicalClass' => 'battery']);
    }

    $descr = "Battery Replace";
    if ($entry['upsAdvBatteryReplaceIndicator']) {
        $oid = ".1.3.6.1.4.1.318.1.1.1.2.2.4.$index";
        if ($entry['upsBasicBatteryLastReplaceDate']) {
            $descr .= ' (last ' . reformat_us_date($entry['upsBasicBatteryLastReplaceDate']) . ')';
        }

        //discover_status($device, $oid, "upsAdvBatteryReplaceIndicator.$index", 'powernet-upsbatteryreplace-state', $descr, $entry['upsAdvBatteryReplaceIndicator'], [ 'entPhysicalClass' => 'battery' ]);
        discover_status_ng($device, $mib, 'upsAdvBatteryReplaceIndicator', $oid, $index, 'powernet-upsbatteryreplace-state', $descr, $entry['upsAdvBatteryReplaceIndicator'], ['entPhysicalClass' => 'battery']);
    }
}

// State sensors

// PowerNet-MIB::upsAdvTestDiagnosticSchedule.0 = INTEGER: biweekly(2)
// PowerNet-MIB::upsAdvTestDiagnostics.0 = INTEGER: noTestDiagnostics(1)
// PowerNet-MIB::upsAdvTestDiagnosticsResults.0 = INTEGER: ok(1)
// PowerNet-MIB::upsAdvTestLastDiagnosticsDate.0 = STRING: "05/27/2015"

$cache['apc'] = snmp_get_multi_oid($device, 'upsAdvTestDiagnosticSchedule.0 upsAdvTestDiagnosticsResults.0 upsAdvTestLastDiagnosticsDate.0', [], 'PowerNet-MIB');

if (isset($cache['apc'][0]) && $cache['apc'][0]['upsAdvTestDiagnosticSchedule'] !== 'never') {
    $oid   = ".1.3.6.1.4.1.318.1.1.1.7.2.3.0";
    $descr = "Diagnostics Results";
    if ($cache['apc'][0]['upsAdvTestLastDiagnosticsDate']) {
        $descr .= ' (last ' . reformat_us_date($cache['apc'][0]['upsAdvTestLastDiagnosticsDate']) . ')';
    }

    discover_status($device, $oid, "upsAdvTestDiagnosticsResults.0", 'powernet-upstest-state', $descr, $cache['apc'][0]['upsAdvTestDiagnosticsResults'], ['entPhysicalClass' => 'other']);
}

/* PowerNet-MIB::upsBasicOutputStatus.0 = INTEGER: onLine(2)

$value = snmp_get_oid($device, "upsBasicOutputStatus.0", "PowerNet-MIB");

if ($value !== '') {
  $oid = ".1.3.6.1.4.1.318.1.1.1.4.1.1.0";
  $descr = "Output Status";

  discover_status($device, $oid, "upsBasicOutputStatus.0", 'powernet-upsbasicoutput-state', $descr, $value, array('entPhysicalClass' => 'power'));
}
*/

#### ATS #############################################################################################

if ($inputs = snmp_get_oid($device, "atsNumInputs.0", "PowerNet-MIB")) {
    echo(' ');
    $cache['apc'] = [];
    echo("atsInputTable ");
    $cache['apc']['input'] = snmpwalk_cache_oid($device, 'atsInputTable', [], 'PowerNet-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    echo("atsInputPhaseTable");
    $cache['apc']['phase'] = snmpwalk_cache_oid($device, 'atsInputPhaseTable', [], 'PowerNet-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    print_debug_vars($cache['apc']);

    foreach ($cache['apc']['input'] as $index => $entry) {
        $descr = 'Input ' . $entry['atsInputName'];

        // Input Frequency
        $oid_name = 'atsInputFrequency';
        $oid_num  = '.1.3.6.1.4.1.318.1.1.8.5.3.2.1.4.' . $index;
        $value    = $entry[$oid_name];

        //if ($value != '0' && $value != -1)
        if ($value != -1) {
            $options = ['rename_rrd' => 'apc-atsInputFrequency.%index%'];
            // PowerNet-MIB::atsIdentNominalLineFrequency.0 = INTEGER: 60
            // PowerNet-MIB::atsConfigFrequencyDeviation.0 = INTEGER: five(5)
            if ($limit_nominal = snmp_cache_oid($device, "atsIdentNominalLineFrequency.0", "PowerNet-MIB")) {
                $limit_alert           = snmp_cache_oid($device, "atsConfigFrequencyDeviation.0", "PowerNet-MIB", NULL, OBS_SNMP_ALL_ENUM);
                $options['limit_high'] = $limit_nominal + $limit_alert;
                $options['limit_low']  = $limit_nominal - $limit_alert;
            } else {
                $options['limit_low'] = 0;
            }
            //discover_sensor('frequency', $device, $oid, "atsInputFrequency.$index", 'apc', $descr, 1, $value);
            discover_sensor_ng($device, 'frequency', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }
    }

    foreach ($cache['apc']['phase'] as $index => $entry) {
        [$input, $phase] = explode('.', $index);
        if (isset($cache['apc']['input'][$input])) {
            $entry = array_merge($entry, $cache['apc']['input'][$input]);
        }

        $descr = 'Input ' . $entry['atsInputName'];
        if ($entry['atsNumInputPhases'] > 1) {
            $descr .= " (Phase $phase)";
        }

        // Input Voltage
        $oid_name = 'atsInputVoltage';
        $oid_num  = '.1.3.6.1.4.1.318.1.1.8.5.3.3.1.3.' . $index;
        $value    = $entry[$oid_name];

        if ($value != -1) {
            $options = ['rename_rrd' => 'apc-atsInputVoltage.%index%'];
            // PowerNet-MIB::atsConfigTransferVoltageRange.0 = INTEGER: wide(1)
            // PowerNet-MIB::atsConfigLineVRMS.0 = INTEGER: 208
            // PowerNet-MIB::atsConfigLineVRMSNarrowLimit.0 = INTEGER: 15
            // PowerNet-MIB::atsConfigLineVRMSMediumLimit.0 = INTEGER: 22
            // PowerNet-MIB::atsConfigLineVRMSWideLimit.0 = INTEGER: 30
            if ($limit_nominal = snmp_cache_oid($device, "atsConfigLineVRMS.0", "PowerNet-MIB")) {
                $limit_range = snmp_cache_oid($device, "atsConfigTransferVoltageRange.0", "PowerNet-MIB");
                if ($limit_range === 'wide') {
                    $limit_alert   = snmp_cache_oid($device, "atsConfigLineVRMSWideLimit.0", "PowerNet-MIB");
                    $limit_warning = snmp_cache_oid($device, "atsConfigLineVRMSMediumLimit.0", "PowerNet-MIB");
                } elseif ($limit_range === 'medium') {
                    $limit_alert   = snmp_cache_oid($device, "atsConfigLineVRMSMediumLimit.0", "PowerNet-MIB");
                    $limit_warning = snmp_cache_oid($device, "atsConfigLineVRMSNarrowLimit.0", "PowerNet-MIB");
                } else {
                    $limit_alert   = snmp_cache_oid($device, "atsConfigLineVRMSNarrowLimit.0", "PowerNet-MIB");
                    $limit_warning = (int)($limit_alert * 0.7);
                }
                $options['limit_high']      = $limit_nominal + $limit_alert;
                $options['limit_low']       = $limit_nominal - $limit_alert;
                $options['limit_high_warn'] = $limit_nominal + $limit_warning;
                $options['limit_low_warn']  = $limit_nominal - $limit_warning;

            } else {
                $options['limit_low'] = 0;
            }

            //discover_sensor('voltage', $device, $oid_num, $oid_name.$dot_index, 'apc', $descr, 1, $value);
            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }

        // Input Current
        $oid_name = 'atsInputCurrent';
        $oid_num  = '.1.3.6.1.4.1.318.1.1.8.5.3.3.1.6.' . $index;
        $value    = $entry[$oid_name];

        if ($value != -1) {
            $options = ['rename_rrd'      => 'apc-atsInputCurrent.%index%',
                        'limit_high'      => snmp_cache_oid($device, "atsConfigPhaseOverLoadThreshold.$phase", "PowerNet-MIB"),
                        //'limit_low'       => snmp_cache_oid($device, "atsConfigPhaseLowLoadThreshold.$phase",      "PowerNet-MIB"),
                        'limit_high_warn' => snmp_cache_oid($device, "atsConfigPhaseNearOverLoadThreshold.$phase", "PowerNet-MIB")];
            //discover_sensor('current', $device, $oid_num, $oid_name.$dot_index, 'apc', $descr, 1, $value);
            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }

        // Input Power
        $oid_name = 'atsInputPower';
        $oid_num  = '.1.3.6.1.4.1.318.1.1.8.5.3.3.1.9.' . $index;
        $value    = $entry[$oid_name];

        if ($value != -1) {
            $options = ['rename_rrd' => 'apc-atsInputPower.%index%'];

            //discover_sensor('power', $device, $oid_num, $oid_name.$dot_index, 'apc', $descr, 1, $value);
            discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }
    }
}

if ($outputs = snmp_get_oid($device, "atsNumOutputs.0", "PowerNet-MIB")) {
    echo(' ');
    $cache['apc'] = [];
    echo("atsOutputTable ");
    $cache['apc']['output'] = snmpwalk_cache_oid($device, 'atsOutputTable', [], 'PowerNet-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    echo("atsOutputPhaseTable");
    $cache['apc']['phase'] = snmpwalk_cache_oid($device, 'atsOutputPhaseTable', [], 'PowerNet-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    print_debug_vars($cache['apc']);

    foreach ($cache['apc']['output'] as $index => $entry) {
        $descr = 'Output';
        if ($outputs > 1) {
            $descr .= ' ' . $index;
        }

        // Output Frequency
        $oid_name = 'atsOutputFrequency';
        $oid_num  = '.1.3.6.1.4.1.318.1.1.8.5.4.2.1.4.' . $index;
        $value    = $entry[$oid_name];

        //if ($value != '0' && $value != -1)
        if ($value != -1) {
            $options = ['rename_rrd' => 'apc-atsOutputFrequency.%index%'];
            // PowerNet-MIB::atsIdentNominalLineFrequency.0 = INTEGER: 60
            // PowerNet-MIB::atsConfigFrequencyDeviation.0 = INTEGER: five(5)
            if ($limit_nominal = snmp_cache_oid($device, "atsIdentNominalLineFrequency.0", "PowerNet-MIB")) {
                $limit_alert           = snmp_cache_oid($device, "atsConfigFrequencyDeviation.0", "PowerNet-MIB", NULL, OBS_SNMP_ALL_ENUM);
                $options['limit_high'] = $limit_nominal + $limit_alert;
                $options['limit_low']  = $limit_nominal - $limit_alert;
            } else {
                $options['limit_low'] = 0;
            }
            //discover_sensor('frequency', $device, $oid, "atsOutputFrequency.$index", 'apc', $descr, 1, $value);
            discover_sensor_ng($device, 'frequency', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }
    }

    foreach ($cache['apc']['phase'] as $index => $entry) {
        [$output, $phase] = explode('.', $index);
        if (isset($cache['apc']['output'][$output])) {
            $entry = array_merge($entry, $cache['apc']['output'][$output]);
        }

        $descr = 'Output';
        if ($outputs > 1) {
            $descr .= ' ' . $output;
        }
        if ($entry['atsNumOutputPhases'] > 1) {
            $descr .= " (Phase $phase)";
        }

        // Output Voltage
        $oid_name = 'atsOutputVoltage';
        $oid_num  = '.1.3.6.1.4.1.318.1.1.8.5.4.3.1.3.' . $index;
        $value    = $entry[$oid_name];

        if ($value != -1) {
            $options = ['rename_rrd' => 'apc-atsOutputVoltage.%index%'];
            // PowerNet-MIB::atsConfigTransferVoltageRange.0 = INTEGER: wide(1)
            // PowerNet-MIB::atsConfigLineVRMS.0 = INTEGER: 208
            // PowerNet-MIB::atsConfigLineVRMSNarrowLimit.0 = INTEGER: 15
            // PowerNet-MIB::atsConfigLineVRMSMediumLimit.0 = INTEGER: 22
            // PowerNet-MIB::atsConfigLineVRMSWideLimit.0 = INTEGER: 30
            if ($limit_nominal = snmp_cache_oid($device, "atsConfigLineVRMS.0", "PowerNet-MIB")) {
                $limit_range = snmp_cache_oid($device, "atsConfigTransferVoltageRange.0", "PowerNet-MIB");
                if ($limit_range === 'wide') {
                    $limit_alert   = snmp_cache_oid($device, "atsConfigLineVRMSWideLimit.0", "PowerNet-MIB");
                    $limit_warning = snmp_cache_oid($device, "atsConfigLineVRMSMediumLimit.0", "PowerNet-MIB");
                } elseif ($limit_range === 'medium') {
                    $limit_alert   = snmp_cache_oid($device, "atsConfigLineVRMSMediumLimit.0", "PowerNet-MIB");
                    $limit_warning = snmp_cache_oid($device, "atsConfigLineVRMSNarrowLimit.0", "PowerNet-MIB");
                } else {
                    $limit_alert   = snmp_cache_oid($device, "atsConfigLineVRMSNarrowLimit.0", "PowerNet-MIB");
                    $limit_warning = (int)($limit_alert * 0.7);
                }
                $options['limit_high']      = $limit_nominal + $limit_alert;
                $options['limit_low']       = $limit_nominal - $limit_alert;
                $options['limit_high_warn'] = $limit_nominal + $limit_warning;
                $options['limit_low_warn']  = $limit_nominal - $limit_warning;

            } else {
                $options['limit_low'] = 0;
            }

            //discover_sensor('voltage', $device, $oid, "atsOutputVoltage.$index.1.1", 'apc', $descr, 1, $value);
            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }

        // Output Current
        $oid_name = 'atsOutputCurrent';
        $oid_num  = '.1.3.6.1.4.1.318.1.1.8.5.4.3.1.4.' . $index;
        $value    = $entry[$oid_name];

        if ($value != -1) {
            $options = ['rename_rrd'      => 'apc-atsOutputCurrent.%index%',
                        'limit_high'      => snmp_cache_oid($device, "atsConfigPhaseOverLoadThreshold.$phase", "PowerNet-MIB"),
                        //'limit_low'       => snmp_cache_oid($device, "atsConfigPhaseLowLoadThreshold.$phase",      "PowerNet-MIB"),
                        'limit_high_warn' => snmp_cache_oid($device, "atsConfigPhaseNearOverLoadThreshold.$phase", "PowerNet-MIB")];
            //discover_sensor('current', $device, $oid, "atsOutputCurrent.$index.1.1", 'apc', $descr, $scale, $value, $limits);
            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, 0.1, $value, $options);
        }

        // Output Power
        $oid_name = 'atsOutputPower';
        $oid_num  = '.1.3.6.1.4.1.318.1.1.8.5.4.3.1.13.' . $index;
        $value    = $entry[$oid_name];

        if ($value != -1) {
            $options = ['rename_rrd' => 'apc-atsOutputPower.%index%'];

            //discover_sensor('power', $device, $oid, "atsOutputPower.$index.1.1", 'apc', $descr, 1, $value);
            discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }

        // Output Load
        // [atsOutputLoad] => 364 (VA)
        // [atsOutputPercentLoad] => 9 (%)
        // [atsOutputPercentPower] => 9 (%)
        $oid_name = 'atsOutputLoad';
        $oid_num  = '.1.3.6.1.4.1.318.1.1.8.5.4.3.1.7.' . $index;
        $value    = $entry[$oid_name];

        if ($value != -1) {
            discover_sensor_ng($device, 'apower', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value);
        }

        $oid_name = 'atsOutputPercentLoad';
        $oid_num  = '.1.3.6.1.4.1.318.1.1.8.5.4.3.1.10.' . $index;
        $value    = $entry[$oid_name];

        if ($value != -1) {
            discover_sensor_ng($device, 'load', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value);
        }
    }
}

#### PDU #############################################################################################

$outlets = snmp_get_oid($device, "rPDUIdentDeviceNumOutlets.0", "PowerNet-MIB");
$banks   = snmp_get_oid($device, "rPDULoadDevNumBanks.0", "PowerNet-MIB");
$loadDev = snmpwalk_cache_oid($device, "rPDULoadDevice", [], "PowerNet-MIB");

// Check if we have values for these, if not, try other code paths below.
if ($outlets) {
    echo(' ');

    // FIXME unused
    # v2 firmware: first bank is total
    # v3 firmware: last bank is total
    # v5 firmware: looks like first bank is total
    $baseversion = 2;
    if (preg_match('/^[vV](\d+)/', $device['version'], $matches) && $matches[1] > 2 && $matches[1] <= 6) {
        $baseversion = $matches[1];
    }
    // if     (stristr($device['version'], 'v3') == TRUE) { $baseversion = 3; }
    // elseif (stristr($device['version'], 'v4') == TRUE) { $baseversion = 4; }
    // elseif (stristr($device['version'], 'v5') == TRUE) { $baseversion = 5; }
    // elseif (stristr($device['version'], 'v6') == TRUE) { $baseversion = 6; }

    $cache['apc'] = snmpwalk_cache_oid($device, "rPDU2DeviceStatusTable", [], "PowerNet-MIB");

    $units = safe_count($cache['apc']); // Set this for use later

    // Try rPDU2 tree, as this supports slaving (rPDU2 devices also do rPDU table)
    if (safe_count($cache['apc'])) {
        foreach ($cache['apc'] as $index => $entry) {
            if ($units > 1) {
                // Multiple chained PDUs, prepend unit number to description
                $unit = 'Unit ' . $entry['rPDU2DeviceStatusModule'] . ' ';
            } else {
                // No prepend needed for single unit
                $unit = '';
            }

            // PowerNet-MIB::rPDU2DeviceStatusPowerSupply1Status.1 = normal
            // PowerNet-MIB::rPDU2DeviceStatusPowerSupply1Status.2 = normal
            // PowerNet-MIB::rPDU2DeviceStatusPowerSupply2Status.1 = notInstalled
            // PowerNet-MIB::rPDU2DeviceStatusPowerSupply2Status.2 = normal
            $descr = $unit . "Power Supply 1";
            $oid   = ".1.3.6.1.4.1.318.1.1.26.4.3.1.13.$index";
            $value = $entry['rPDU2DeviceStatusPowerSupply1Status'];
            discover_status($device, $oid, "rPDU2DeviceStatusPowerSupply1Status.$index", 'powernet-rpdu2supply-state', $descr, $value, ['entPhysicalClass' => 'power']);

            $descr = $unit . "Power Supply 2";
            $oid   = ".1.3.6.1.4.1.318.1.1.26.4.3.1.14.$index";
            $value = $entry['rPDU2DeviceStatusPowerSupply2Status'];
            discover_status($device, $oid, "rPDU2DeviceStatusPowerSupply2Status.$index", 'powernet-rpdu2supply-state', $descr, $value, ['entPhysicalClass' => 'power']);

            // PowerNet-MIB::rPDU2DeviceStatusPowerSupplyAlarm.1 = INTEGER: normal(1)
            $descr = $unit . "Power Supply Alarm";
            $oid   = ".1.3.6.1.4.1.318.1.1.26.4.3.1.12.$index";
            $value = $entry['rPDU2DeviceStatusPowerSupplyAlarm'];
            discover_status($device, $oid, "rPDU2DeviceStatusPowerSupplyAlarm.$index", 'powernet-rpdu2supplyalarm-state', $descr, $value, ['entPhysicalClass' => 'power']);

            // PowerNet-MIB::rPDU2DeviceStatusPower.1 = INTEGER: 185
            $descr = $unit . "Output";
            $oid   = ".1.3.6.1.4.1.318.1.1.26.4.3.1.5.$index";
            $value = $entry['rPDU2DeviceStatusPower'];

            if ($value != 0 && $value != -1 && $loadDev[0]['rPDULoadDevNumPhases'] > 1) {
                // Device Output if more than 1 Phase
                discover_sensor('power', $device, $oid, "rPDU2DeviceStatusPower.$index", 'apc', $descr, 10, $value, ['entPhysicalClass' => 'power']);
            }

            // PowerNet-MIB::rPDU2DeviceStatusApparentPower.1 = INTEGER: 198
            $descr = $unit . "Output";
            $oid   = ".1.3.6.1.4.1.318.1.1.26.4.3.1.16.$index";
            $value = $entry['rPDU2DeviceStatusApparentPower'];

            if ($value != 0 && $value != -1) {
                discover_sensor('apower', $device, $oid, "rPDU2DeviceStatusApparentPower.$index", 'apc', $descr, 10, $value, ['entPhysicalClass' => 'power']);
            }

            // rPDU2DeviceStatusPowerFactor.1 = INTEGER: 93
            $descr = $unit . "Power Factor";
            $oid   = ".1.3.6.1.4.1.318.1.1.26.4.3.1.17.$index";
            $value = $entry['rPDU2DeviceStatusPowerFactor'];
            if ($value != 0 && $value != -1) {
                discover_sensor_ng($device, 'powerfactor', $mib, 'rPDU2DeviceStatusPowerFactor', $oid, $index, NULL, $descr, 0.01, $value);
            }

            // rPDU2DeviceStatusEnergy.1 = INTEGER: 170982
            // rPDU2DeviceStatusEnergyStartTime.1 = "05/30/2011 00:12:17"
            $descr = $unit . "Energy (since " . reformat_us_date($entry['rPDU2DeviceStatusEnergyStartTime']) . ")";
            $oid   = ".1.3.6.1.4.1.318.1.1.26.4.3.1.9.$index";
            $value = $entry['rPDU2DeviceStatusEnergy'];
            if ($value != 0 && $value != -1) {
                discover_counter($device, 'energy', $mib, 'rPDU2DeviceStatusEnergy', $oid, $index, $descr, 100, $value);
            }
        }

        // rPDU2PhaseConfigIndex.1 = INTEGER: 1
        // rPDU2PhaseConfigModule.1 = INTEGER: 1
        // rPDU2PhaseConfigNumber.1 = INTEGER: 1
        // rPDU2PhaseConfigOverloadRestriction.1 = INTEGER: notSupported(4)
        // rPDU2PhaseConfigLowLoadCurrentThreshold.1 = INTEGER: 0
        // rPDU2PhaseConfigNearOverloadCurrentThreshold.1 = INTEGER: 26
        // rPDU2PhaseConfigOverloadCurrentThreshold.1 = INTEGER: 32
        // rPDU2PhasePropertiesIndex.1 = INTEGER: 1
        // rPDU2PhasePropertiesModule.1 = INTEGER: 1
        // rPDU2PhasePropertiesNumber.1 = INTEGER: 1
        // rPDU2PhaseStatusIndex.1 = INTEGER: 1
        // rPDU2PhaseStatusModule.1 = INTEGER: 1
        // rPDU2PhaseStatusNumber.1 = INTEGER: 1
        // rPDU2PhaseStatusLoadState.1 = INTEGER: normal(2)
        // rPDU2PhaseStatusCurrent.1 = INTEGER: 28
        // rPDU2PhaseStatusVoltage.1 = INTEGER: 228
        // rPDU2PhaseStatusPower.1 = INTEGER: 57
        $phasetable = [];
        foreach (["rPDU2PhaseStatusTable", "rPDU2PhaseConfigTable"] as $table) {
            echo("$table ");
            // FIXME, not sure, that here required numeric index, seems as the remains of old snmp code with caching (added in r4685)
            $phasetable = snmpwalk_cache_oid($device, $table, $phasetable, "PowerNet-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
        }

        if (safe_count($phasetable)) {

            foreach ($phasetable as $index => $entry) {
                $oid    = ".1.3.6.1.4.1.318.1.1.26.6.3.1.5.$index";
                $value  = $entry['rPDU2PhaseStatusCurrent'];
                $limits = ['limit_high'      => $entry['rPDU2PhaseConfigOverloadCurrentThreshold'],
                           'limit_high_warn' => $entry['rPDU2PhaseConfigNearOverloadCurrentThreshold']];
                $phase  = $entry['rPDU2PhaseStatusNumber'];
                $unit   = $entry['rPDU2PhaseStatusModule'];

                if ($loadDev[0]['rPDULoadDevNumPhases'] != 1) {
                    // Multiple phases
                    if ($units > 1) {
                        // Multiple chained PDUs
                        $descr = "Unit $unit Phase $phase";
                    } else {
                        $descr = "Phase $phase";
                    }
                } else {
                    // Single phase
                    if ($units > 1) {
                        // Multiple chained PDUs
                        $descr = "Unit $unit Ouput";
                    } else {
                        $descr = "Output";
                    }
                }

                if ($value != '' && $value != -1) {
                    discover_sensor('current', $device, $oid, "rPDU2PhaseStatusCurrent.$index", 'apc', $descr, $scale, $value, $limits);
                }

                $oid   = ".1.3.6.1.4.1.318.1.1.26.6.3.1.6.$index";
                $value = $entry['rPDU2PhaseStatusVoltage'];

                if ($value != '' && $value != -1) {
                    discover_sensor('voltage', $device, $oid, "rPDU2PhaseStatusVoltage.$index", 'apc', $descr, 1, $value);
                }

                $oid   = ".1.3.6.1.4.1.318.1.1.26.6.3.1.7.$index";
                $value = $entry['rPDU2PhaseStatusPower'];

                if ($value != '' && $value != -1) {
                    discover_sensor('power', $device, $oid, "rPDU2PhaseStatusPower.$index", 'apc', $descr, 10, $value);
                }

                $oid   = ".1.3.6.1.4.1.318.1.1.26.6.3.1.9.$index";
                $value = $entry['rPDU2PhaseStatusPowerFactor'];

                if ($value != -1) {
                    discover_sensor_ng($device, 'powerfactor', $mib, 'rPDU2PhaseStatusPowerFactor', $oid, $index, NULL, $descr, 0.01, $value);
                }

                // rPDU2PhaseStatusLoadState.1 = INTEGER: normal(2)
                $oid   = ".1.3.6.1.4.1.318.1.1.26.6.3.1.4.$index";
                $value = $entry['rPDU2PhaseStatusLoadState'];
                discover_status($device, $oid, "rPDU2PhaseStatusLoadState.$index", 'powernet-rpdu2statusload-state', "$descr Load", $value);
            }
        }

        if ($banks > 1) {
            echo("rPDU2BankStatusTable ");
            //rPDU2BankStatusIndex.1 = 1
            //rPDU2BankStatusIndex.2 = 2
            //rPDU2BankStatusModule.1 = 1
            //rPDU2BankStatusModule.2 = 1
            //rPDU2BankStatusNumber.1 = 1
            //rPDU2BankStatusNumber.2 = 2
            //rPDU2BankStatusLoadState.1 = normal
            //rPDU2BankStatusLoadState.2 = normal
            //rPDU2BankStatusCurrent.1 = 7
            //rPDU2BankStatusCurrent.2 = 27
            //rPDU2BankConfigLowLoadCurrentThreshold.1 = INTEGER: 0
            //rPDU2BankConfigLowLoadCurrentThreshold.2 = INTEGER: 0
            //rPDU2BankConfigNearOverloadCurrentThreshold.1 = INTEGER: 13
            //rPDU2BankConfigNearOverloadCurrentThreshold.2 = INTEGER: 13
            //rPDU2BankConfigOverloadCurrentThreshold.1 = INTEGER: 16
            //rPDU2BankConfigOverloadCurrentThreshold.2 = INTEGER: 16
            $bank_oids = snmpwalk_cache_oid($device, 'rPDU2BankStatusTable', [], "PowerNet-MIB");
            $bank_oids = snmpwalk_cache_oid($device, 'rPDU2BankConfigTable', $bank_oids, "PowerNet-MIB");

            foreach ($bank_oids as $index => $entry) {
                $bank  = $entry['rPDU2BankStatusNumber'];
                $descr = "Bank $bank";

                $oid   = ".1.3.6.1.4.1.318.1.1.26.8.3.1.5.$index";
                $value = $entry['rPDU2BankStatusCurrent'];
                if ($value != '' && $value != -1) {
                    $options = ['limit_high'      => $entry['rPDU2BankConfigOverloadCurrentThreshold'],
                                'limit_high_warn' => $entry['rPDU2BankConfigNearOverloadCurrentThreshold']];
                    discover_sensor('current', $device, $oid, "rPDU2BankStatusCurrent.$index", 'apc', $descr, $scale, $value, $options);
                }

                $oid   = ".1.3.6.1.4.1.318.1.1.26.8.3.1.4.$index";
                $value = $entry['rPDU2BankStatusLoadState'];
                discover_status($device, $oid, "rPDU2BankStatusLoadState.$index", 'powernet-rpdu2statusload-state', "$descr Load", $value);
            }
        }

    } else {

        /* Fall back to rPDU tree only if no rPDU2 data is available */

        // PowerNet-MIB::rPDUPowerSupply1Status.0 = INTEGER: powerSupplyOneOk(1)
        // PowerNet-MIB::rPDUPowerSupply2Status.0 = INTEGER: powerSupplyTwoNotPresent(3)
        // PowerNet-MIB::rPDUPowerSupplyAlarm.0 = INTEGER: allAvailablePowerSuppliesOK(1) -- FIXME not used right now
        $cache['apc'] = snmp_get_multi_oid($device, 'rPDUPowerSupply1Status.0 rPDUPowerSupply2Status.0', [], 'PowerNet-MIB');
        if (isset($cache['apc'][0])) {
            // FIXME ugly code. Just hardcode as above instead of weird index and unit calculations.
            $index = 0;
            foreach ($cache['apc'][0] as $key => $value) {
                $unit  = 'rPDUPowerSupply1Status' == $key ? 1 : 2;
                $type  = 'powernet-rpdusupply' . $unit . '-state';
                $descr = 'Power Supply ' . $unit;
                $oid   = ".1.3.6.1.4.1.318.1.1.12.4.1.$unit.$index";

                discover_status($device, $oid, "$key.$index", $type, $descr, $value, ['entPhysicalClass' => 'power']);
            }
        }

        //rPDULoadStatusIndex.1 = 1
        //rPDULoadStatusIndex.2 = 2
        //rPDULoadStatusIndex.3 = 3
        //rPDULoadStatusLoad.1 = 114
        //rPDULoadStatusLoad.2 = 58
        //rPDULoadStatusLoad.3 = 58
        //rPDULoadStatusLoadState.1 = phaseLoadNormal
        //rPDULoadStatusLoadState.2 = phaseLoadNormal
        //rPDULoadStatusLoadState.3 = phaseLoadNormal
        //rPDULoadStatusPhaseNumber.1 = 1
        //rPDULoadStatusPhaseNumber.2 = 1
        //rPDULoadStatusPhaseNumber.3 = 1
        //rPDULoadStatusBankNumber.1 = 0
        //rPDULoadStatusBankNumber.2 = 1
        //rPDULoadStatusBankNumber.3 = 2
        foreach (["rPDUStatusPhaseTable", "rPDULoadStatus", "rPDULoadPhaseConfig"] as $table) {
            echo("$table ");
            // FIXME, not sure, that here required numeric index, seems as the remains of old snmp code with caching (added in r4685)
            $cache['apc'] = snmpwalk_cache_oid($device, $table, $cache['apc'], "PowerNet-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
        }

        foreach ($cache['apc'] as $index => $entry) {
            $oid    = ".1.3.6.1.4.1.318.1.1.12.2.3.1.1.2.$index";
            $value  = $entry['rPDULoadStatusLoad'];
            $limits = ['limit_high'      => $entry['rPDULoadPhaseConfigOverloadThreshold'],
                       'limit_high_warn' => $entry['rPDULoadPhaseConfigNearOverloadThreshold']];
            $bank   = $entry['rPDULoadStatusBankNumber'];
            $phase  = $entry['rPDUStatusPhaseNumber'];

            if (!$banks) {
                // No bank support on device
                if ($loadDev[0]['rPDULoadDevNumPhases'] != 1) {
                    $descr = "Phase $phase";
                } else {
                    $descr = "Output";
                }
            } else {
                // Bank support. Not sure that depends on $baseversion
                // http://jira.observium.org/browse/OBSERVIUM-772
                if ($bank == '0') {
                    $bank = "Total";
                }
                $descr = "Bank $bank";
            }

            if ($value != '' && $value != -1) {
                discover_sensor('current', $device, $oid, "rPDULoadStatusLoad.$index", 'apc', $descr, $scale, $value, $limits);
            }

            // [rPDUStatusPhaseState] => phaseLoadNormal
            // [rPDULoadStatusLoadState] => phaseLoadNormal
            // [rPDULoadPhaseConfigAlarm] => noLoadAlarm
        }

        if ($banks > 1) {
            echo("rPDUStatusBankState ");
            //rPDUStatusBankIndex.1 = INTEGER: 1
            //rPDUStatusBankIndex.2 = INTEGER: 2
            //rPDUStatusBankNumber.1 = INTEGER: 1
            //rPDUStatusBankNumber.2 = INTEGER: 2
            //rPDUStatusBankState.1 = INTEGER: bankLoadNormal(1)
            //rPDUStatusBankState.2 = INTEGER: bankLoadNormal(1)
            $bank_oids = snmpwalk_cache_oid($device, 'rPDUStatusBankState', [], "PowerNet-MIB");

            foreach ($bank_oids as $index => $entry) {
                $bank  = $index;
                $descr = "Bank $bank";

                $oid   = ".1.3.6.1.4.1.318.1.1.12.5.2.1.3.$index";
                $value = $entry['rPDUStatusBankState'];
                discover_status($device, $oid, "rPDUStatusBankState.$index", 'powernet-rpdustatusload-state', "$descr Load", $value);
            }
        }
    }

    // PowerNet-MIB::rPDUIdentDeviceLinetoLineVoltage.0 = INTEGER: 400
    // PowerNet-MIB::rPDUIdentDevicePowerWatts.0 = INTEGER: 807
    // PowerNet-MIB::rPDUIdentDevicePowerFactor.0 = INTEGER: 1000
    // PowerNet-MIB::rPDUIdentDevicePowerVA.0 = INTEGER: 807 - no VA sensor type yet

    //PowerNet-MIB::rPDUIdentDeviceLinetoLineVoltage.0 = INTEGER: -1
    //PowerNet-MIB::rPDUIdentDevicePowerWatts.0 = INTEGER: 290
    //PowerNet-MIB::rPDUIdentDevicePowerFactor.0 = INTEGER: -1
    //PowerNet-MIB::rPDUIdentDevicePowerVA.0 = INTEGER: 360

    $oids = snmp_get_multi_oid($device, 'rPDUIdentDeviceLinetoLineVoltage.0 rPDUIdentDevicePowerWatts.0 rPDUIdentDevicePowerFactor.0 rPDUIdentDevicePowerVA.0', [], 'PowerNet-MIB');
    foreach ($oids as $index => $entry) {
        $descr = "Input";

        /// NOTE. rPDUIdentDeviceLinetoLineVoltage - is not actual voltage from device.
        //DESCRIPTION
        //   "Getting/Setting this OID will return/set the Line to Line Voltage.
        //    This OID defaults to the nominal input line voltage in volts AC.
        //    This setting is used to calculate total power and must be configured for best accuracy.
        //    This OID does not apply to AP86XX, AP88XX, or AP89XX SKUs.
        $oid   = ".1.3.6.1.4.1.318.1.1.12.1.15.$index";
        $value = $entry['rPDUIdentDeviceLinetoLineVoltage'];

        if ($value != -1) {
            discover_sensor('voltage', $device, $oid, "rPDUIdentDeviceLinetoLineVoltage.$index", 'apc', 'Line-to-Line', 1, $value);
        }

        $oid   = ".1.3.6.1.4.1.318.1.1.12.1.16.$index";
        $value = $entry['rPDUIdentDevicePowerWatts'];

        if ($value != -1 && !isset($valid['sensor']['power']['apc']['rPDU2PhaseStatusPower.1'])) {
            discover_sensor('power', $device, $oid, "rPDUIdentDevicePowerWatts.$index", 'apc', $descr, 1, $value);
        }

        $oid   = ".1.3.6.1.4.1.318.1.1.12.1.17.$index";
        $value = $entry['rPDUIdentDevicePowerFactor'];

        if ($value != -1) {
            discover_sensor('powerfactor', $device, $oid, "rPDUIdentDevicePowerFactor.$index", 'apc', $descr, 0.001, $value);
        }

        $oid   = ".1.3.6.1.4.1.318.1.1.12.1.18.$index";
        $value = $entry['rPDUIdentDevicePowerVA'];

        if ($value != -1) {
            discover_sensor('apower', $device, $oid, "rPDUIdentDevicePowerVA.$index", 'apc', $descr, 1, $value);
        }
    }

    // FIXME METERED PDU CODE BELOW IS COMPLETELY UNTESTED
    echo("rPDU2OutletMeteredStatusTable ");
    // FIXME, not sure, that here required numeric index, seems as the remains of old snmp code with caching (added in r4685)
    $cache['apc'] = snmpwalk_cache_oid($device, 'rPDU2OutletMeteredStatusTable', [], "PowerNet-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

    foreach ($cache['apc'] as $index => $entry) {
        $oid    = ".1.3.6.1.4.1.318.1.1.26.9.4.3.1.6.$index";
        $value  = $entry['rPDU2OutletMeteredStatusCurrent'];
        $limits = ['limit_high'      => $entry['rPDU2OutletMeteredConfigOverloadCurrentThreshold'],
                   'limit_low'       => $entry['rPDU2OutletMeteredConfigLowLoadCurrentThreshold'],
                   'limit_high_warn' => $entry['rPDU2OutletMeteredConfigNearOverloadCurrentThreshold']];
        $descr  = "Outlet " . $index . " - " . $entry['rPDU2OutletMeteredStatusName'];

        if ($value != '' && $value != -1) {
            discover_sensor('current', $device, $oid, "rPDU2OutletMeteredStatusCurrent.$index", 'apc', $descr, $scale, $value, $limits);
        }

        $oid   = ".1.3.6.1.4.1.318.1.1.26.9.4.3.1.7.$index";
        $value = $entry['rPDU2OutletMeteredStatusPower'];

        if ($value != '' && $value != -1) {
            discover_sensor('power', $device, $oid, "rPDU2OutletMeteredStatusPower.$index", 'apc', $descr, 1, $value);
        }

        // Not currently supported: kWh reading: rPDU2OutletMeteredStatusEnergy - "A user resettable energy meter measuring Rack PDU load energy consumption in tenths of kilowatt-hours"
    }
}

#### MODULAR DISTRIBUTION SYSTEM #####################################################################

// FIXME This section needs a rewrite, but I can't find a device -TL

echo(' ');

$oids = snmp_walk($device, "isxModularDistSysVoltageLtoN", "-OsqnU", "PowerNet-MIB");
if ($oids) {
    echo(" Voltage In ");
    foreach (explode("\n", $oids) as $data) {
        [$oid, $value] = explode(' ', $data);
        $split_oid = explode('.', $oid);
        $phase     = $split_oid[safe_count($split_oid) - 1];
        $index     = "LtoN:" . $phase;
        $descr     = "Phase $phase Line to Neutral";

        discover_sensor('voltage', $device, $oid, $index, 'apc', $descr, $scale, $value);
    }
}

$oids = snmp_walk($device, "isxModularDistModuleBreakerCurrent", "-OsqnU", "PowerNet-MIB");
if ($oids) {
    echo(" Modular APC Out ");
    foreach (explode("\n", $oids) as $data) {
        $data = trim($data);
        if ($data) {
            [$oid, $value] = explode(' ', $data);
            $split_oid = explode('.', $oid);
            $phase     = $split_oid[safe_count($split_oid) - 1];
            $breaker   = $split_oid[safe_count($split_oid) - 2];
            $index     = str_pad($breaker, 2, "0", STR_PAD_LEFT) . "-" . $phase;
            $descr     = "Breaker $breaker Phase $phase";
            discover_sensor('current', $device, $oid, $index, 'apc', $descr, $scale, $value);
        }
    }

    $oids = snmp_walk($device, "isxModularDistSysCurrentAmps", "-OsqnU", "PowerNet-MIB");
    foreach (explode("\n", $oids) as $data) {
        $data = trim($data);
        if ($data) {
            [$oid, $value] = explode(' ', $data);
            $split_oid = explode('.', $oid);
            $phase     = $split_oid[safe_count($split_oid) - 1];
            $index     = ".$phase";
            $descr     = "Phase $phase overall";
            discover_sensor('current', $device, $oid, $index, 'apc', $descr, $scale, $value);
        }
    }
}

#### ENVIRONMENTAL ###################################################################################

echo(' ');

echo("emsProbeStatusTable ");
$cache['apc'] = snmpwalk_cache_oid($device, 'emsProbeStatusTable', [], "PowerNet-MIB");
$temp_units   = snmp_get_oid($device, "emsStatusSysTempUnits.0", "PowerNet-MIB");

foreach ($cache['apc'] as $index => $entry) {
    $descr = $entry['emsProbeStatusProbeName'];

    $status = $entry['emsProbeStatusProbeCommStatus'];
    if ($status != 'commsEstablished') {
        continue;
    }

    // Humidity
    $value   = $entry['emsProbeStatusProbeHumidity'];
    $oid     = ".1.3.6.1.4.1.318.1.1.10.3.13.1.1.6.$index";
    $options = ['limit_high'      => $entry['emsProbeStatusProbeMaxHumidityThresh'],
                'limit_low'       => $entry['emsProbeStatusProbeMinHumidityThresh'],
                'limit_high_warn' => $entry['emsProbeStatusProbeHighHumidityThresh'],
                'limit_low_warn'  => $entry['emsProbeStatusProbeLowHumidityThresh']];

    if ($value != '' && $value > 0) { // Humidity = 0 or -1 -> Sensor not available
        //discover_sensor('humidity', $device, $oid, "emsProbeStatusProbeHumidity.$index", 'apc', $descr, 1, $value, $limits);
        $options['rename_rrd'] = 'apc-emsProbeStatusProbeHumidity.%index%';
        discover_sensor_ng($device, 'humidity', $mib, 'emsProbeStatusProbeHumidity', $oid, $index, NULL, $descr, 1, $value, $options);
    }

    // Temperature
    $value   = $entry['emsProbeStatusProbeTemperature'];
    $oid     = ".1.3.6.1.4.1.318.1.1.10.3.13.1.1.3.$index";
    $options = ['limit_high'      => $entry['emsProbeStatusProbeMaxTempThresh'],
                'limit_low'       => $entry['emsProbeStatusProbeMinTempThresh'],
                'limit_high_warn' => $entry['emsProbeStatusProbeHighTempThresh'],
                'limit_low_warn'  => $entry['emsProbeStatusProbeLowTempThresh']];

    if ($value != '' && $value != -1) { // Temperature = -1 -> Sensor not available
        $scale_temp = 1;
        if ($temp_units == 'fahrenheit') {
            $options['sensor_unit'] = 'F';
        } else {
            $options['sensor_unit'] = 'C';
        }

        //discover_sensor('temperature', $device, $oid, "emsProbeStatusProbeTemperature.$index", 'apc', $descr, $scale_temp, $value, $options);
        $options['rename_rrd'] = 'apc-emsProbeStatusProbeTemperature.%index%';
        discover_sensor_ng($device, 'temperature', $mib, 'emsProbeStatusProbeTemperature', $oid, $index, NULL, $descr, $scale_temp, $value, $options);
    }
}

$cache['apc'] = [];

// emConfigProbesTable may also be used? Perhaps on older devices? Not on mine...
foreach (["iemConfigProbesTable", "iemStatusProbesTable"] as $table) {
    echo("$table ");
    $cache['apc'] = snmpwalk_cache_oid($device, $table, $cache['apc'], "PowerNet-MIB");
}

foreach ($cache['apc'] as $index => $entry) {
    $descr      = $entry['iemStatusProbeName'];
    $temp_units = $entry['iemStatusProbeTempUnits'];

    $status = $entry['iemStatusProbeStatus'];
    if ($status != 'connected') {
        continue;
    } // Skip unconnected sensors entirely

    // Humidity
    $value   = $entry['iemStatusProbeCurrentHumid'];
    $oid     = ".1.3.6.1.4.1.318.1.1.10.2.3.2.1.6.$index";
    $options = ['limit_high'      => $entry['iemConfigProbeMaxHumidThreshold'],
                'limit_low'       => $entry['iemConfigProbeMinHumidThreshold'],
                'limit_high_warn' => $entry['iemConfigProbeHighHumidThreshold'],
                'limit_low_warn'  => $entry['iemConfigProbeLowHumidThreshold']];

    if ($value != '' && $value > 0) { // Humidity = 0 or -1 -> Sensor not available
        //discover_sensor('humidity', $device, $oid, "iemStatusProbeCurrentHumid.$index", 'apc', $descr, 1, $value, $limits);
        $options['rename_rrd'] = 'apc-iemStatusProbeCurrentHumid.%index%';
        discover_sensor_ng($device, 'humidity', $mib, 'iemStatusProbeCurrentHumid', $oid, $index, NULL, $descr, 1, $value, $options);
        $iem_sensors['humidity'][] = $descr; // Store for later use in uio code below
    }

    // Temperature
    $value   = $entry['iemStatusProbeCurrentTemp'];
    $oid     = ".1.3.6.1.4.1.318.1.1.10.2.3.2.1.4.$index";
    $options = ['limit_high'      => $entry['iemConfigProbeMaxTempThreshold'],
                'limit_low'       => $entry['iemConfigProbeMinTempThreshold'],
                'limit_high_warn' => $entry['iemConfigProbeHighTempThreshold'],
                'limit_low_warn'  => $entry['iemConfigProbeLowTempThreshold']];

    if ($value != '' && $value > 0) { // Temperature = -1 -> Sensor not available
        $scale_temp = 1;
        if ($temp_units == 'fahrenheit') {
            $options['sensor_unit'] = 'F';
        } else {
            $options['sensor_unit'] = 'C';
        }

        //discover_sensor('temperature', $device, $oid, "iemStatusProbeCurrentTemp.$index", 'apc', $descr, $scale_temp, $value, $options);
        $options['rename_rrd'] = 'apc-iemStatusProbeCurrentTemp.%index%';
        discover_sensor_ng($device, 'temperature', $mib, 'iemStatusProbeCurrentTemp', $oid, $index, NULL, $descr, $scale_temp, $value, $options);
        $iem_sensors['temperature'][] = $descr; // Store for later use in uio code below
    }
}

// Universal I/O sensors

// Apparently on newer cards (maybe a bug?) only the first UIO port's sensor is sent in the iem table above.
// Both UIO ports are exported through the uioSensorStatusTable. However, we don't get threshold information
// in this table, so we use the iem[Config|Status]ProbesTable table if we can, then add any missing sensors
// we find below through the uioSensorStatusTable by checking against the contents of the $iem_sensors array.

// PowerNet-MIB::uioSensorStatusPortID.1.1 = INTEGER: 1
// PowerNet-MIB::uioSensorStatusPortID.2.1 = INTEGER: 2
// PowerNet-MIB::uioSensorStatusSensorID.1.1 = INTEGER: 1
// PowerNet-MIB::uioSensorStatusSensorID.2.1 = INTEGER: 1
// PowerNet-MIB::uioSensorStatusSensorName.1.1 = STRING: "UPS"
// PowerNet-MIB::uioSensorStatusSensorName.2.1 = STRING: "Rack"
// PowerNet-MIB::uioSensorStatusSensorLocation.1.1 = STRING: "Port 1"
// PowerNet-MIB::uioSensorStatusSensorLocation.2.1 = STRING: "Port 2"
// PowerNet-MIB::uioSensorStatusTemperatureDegC.1.1 = INTEGER: 27
// PowerNet-MIB::uioSensorStatusTemperatureDegC.2.1 = INTEGER: 22
// PowerNet-MIB::uioSensorStatusHumidity.1.1 = INTEGER: -1
// PowerNet-MIB::uioSensorStatusHumidity.2.1 = INTEGER: 49
// PowerNet-MIB::uioSensorStatusViolationStatus.1.1 = INTEGER: 0
// PowerNet-MIB::uioSensorStatusViolationStatus.2.1 = INTEGER: 0
// PowerNet-MIB::uioSensorStatusAlarmStatus.1.1 = INTEGER: uioNormal(1)
// PowerNet-MIB::uioSensorStatusAlarmStatus.2.1 = INTEGER: uioNormal(1)
// PowerNet-MIB::uioSensorStatusCommStatus.1.1 = INTEGER: commsOK(2)
// PowerNet-MIB::uioSensorStatusCommStatus.2.1 = INTEGER: commsOK(2)

echo(' ');

echo("uioSensorStatusTable ");
$cache['apc'] = snmpwalk_cache_oid($device, 'uioSensorStatusTable', [], "PowerNet-MIB");

foreach ($cache['apc'] as $index => $entry) {
    $descr = $entry['uioSensorStatusSensorName'];

    $status = $entry['uioSensorStatusCommStatus'];
    if ($status !== 'commsOK') {
        continue;
    } // Skip unconnected sensors entirely

    // Humidity
    $value = $entry['uioSensorStatusHumidity'];
    $oid   = ".1.3.6.1.4.1.318.1.1.25.1.2.1.7.$index";
    // No thresholds in the uio MIB table :(

    if ($value != '' && $value > 0) { // Humidity = 0 or -1 -> Sensor not available
        // Skip if already discovered through iem
        if (!in_array($descr, (array)$iem_sensors['humidity'])) {
            //discover_sensor('humidity', $device, $oid, "uioSensorStatusHumidity.$index", 'apc', $descr, 1, $value);
            $options = ['rename_rrd' => 'apc-uioSensorStatusHumidity.%index%'];
            discover_sensor_ng($device, 'humidity', $mib, 'uioSensorStatusHumidity', $oid, $index, NULL, $descr, 1, $value, $options);
        } else {
            print_debug("Sensor was already found through iem table, skipping uio");
        }
    }

    // Temperature
    $value = $entry['uioSensorStatusTemperatureDegC'];
    $oid   = ".1.3.6.1.4.1.318.1.1.25.1.2.1.6.$index";
    // No thresholds in the uio MIB table :(

    if ($value != '' && $value != -1) { // Temperature = -1 -> Sensor not available
        // Skip if already discovered through iem
        if (!in_array($descr, (array)$iem_sensors['temperature'])) {
            //discover_sensor('temperature', $device, $oid, "uioSensorStatusTemperatureDegC.$index", 'apc', $descr, 1, $value);
            $options = ['rename_rrd' => 'apc-uioSensorStatusTemperatureDegC.%index%'];
            discover_sensor_ng($device, 'temperature', $mib, 'uioSensorStatusTemperatureDegC', $oid, $index, NULL, $descr, 1, $value, $options);
        } else {
            print_debug("Sensor was already found through iem table, skipping uio");
        }
    }

    // FIXME we could add the state sensors here too (ViolationStatus, AlarmStatus)
}

unset($iem_sensors);                                            // Unset variable used by iem/uio deduplication code

echo(' ');

echo("rPDU2SensorTempHumidityStatusTable ");

// Environmental monitoring on rPDU2
$cache['apc'] = snmpwalk_cache_oid($device, "rPDU2SensorTempHumidityConfigTable", [], "PowerNet-MIB");
$cache['apc'] = snmpwalk_cache_oid($device, "rPDU2SensorTempHumidityStatusTable", $cache['apc'], "PowerNet-MIB");

foreach ($cache['apc'] as $index => $entry) {
    $descr = $entry['rPDU2SensorTempHumidityStatusName'];

    // Humidity
    $value   = $entry['rPDU2SensorTempHumidityStatusRelativeHumidity'];
    $oid     = ".1.3.6.1.4.1.318.1.1.26.10.2.2.1.10.$index";
    $options = ['limit_low'      => $entry['rPDU2SensorTempHumidityConfigHumidityMinThresh'],
                'limit_low_warn' => $entry['rPDU2SensorTempHumidityConfigHumidityLowThresh']];

    if ($value != '' && $value != -1 && $entry['rPDU2SensorTempHumidityStatusHumidityStatus'] != 'notPresent') {
        //discover_sensor('humidity', $device, $oid, "rPDU2SensorTempHumidityStatusRelativeHumidity.$index", 'apc', $descr, 1, $value, $limits);
        $options['rename_rrd'] = 'apc-rPDU2SensorTempHumidityStatusRelativeHumidity.%index%';
        discover_sensor_ng($device, 'humidity', $mib, 'rPDU2SensorTempHumidityStatusRelativeHumidity', $oid, $index, NULL, $descr, 1, $value, $options);
    }

    // Temperature
    $value   = $entry['rPDU2SensorTempHumidityStatusTempC'];
    $oid     = ".1.3.6.1.4.1.318.1.1.26.10.2.2.1.8.$index";
    $options = ['limit_high'      => $entry['rPDU2SensorTempHumidityConfigTempMaxThreshC'],
                'limit_high_warn' => $entry['rPDU2SensorTempHumidityConfigTempHighThreshC']];

    if ($value != '' && $value != -1) {
        //discover_sensor('temperature', $device, $oid, "rPDU2SensorTempHumidityStatusTempC.$index", 'apc', $descr, $scale, $value, $limits);
        $options['rename_rrd'] = 'apc-rPDU2SensorTempHumidityStatusTempC.%index%';
        discover_sensor_ng($device, 'temperature', $mib, 'rPDU2SensorTempHumidityStatusTempC', $oid, $index, NULL, $descr, $scale, $value, $options);
    }
}

#### NETBOTZ #########################################################################################

echo(' ');

// PowerNet-MIB::memSensorsStatusSensorNumber.0.7 = INTEGER: 7
// PowerNet-MIB::memSensorsStatusSensorName.0.7 = STRING: "Server Room"
// PowerNet-MIB::memSensorsTemperature.0.7 = INTEGER: 69
// PowerNet-MIB::memSensorsHumidity.0.7 = INTEGER: 55

echo("memSensorsStatusTable ");
$cache['apc'] = snmpwalk_cache_oid($device, 'memSensorsStatusTable', [], "PowerNet-MIB");
$temp_units   = snmp_get_oid($device, "memSensorsStatusSysTempUnits.0", "PowerNet-MIB");

foreach ($cache['apc'] as $index => $entry) {
    $descr = $entry['memSensorsStatusSensorName'];

    $oid   = ".1.3.6.1.4.1.318.1.1.10.4.2.3.1.5.$index";
    $value = $entry['memSensorsTemperature'];

    [, $ems_index] = explode('.', $index);

    // Exclude already added sensor from emsProbeStatusTable
    if ($value != -1 && !isset($valid['sensor']['temperature']['PowerNet-MIB-emsProbeStatusProbeTemperature'][$ems_index])) {
        $scale_temp = 1;
        if ($temp_units === 'fahrenheit') {
            $options['sensor_unit'] = 'F';
        } else {
            $options['sensor_unit'] = 'C';
        }

        discover_sensor('temperature', $device, $oid, "memSensorsTemperature.$index", 'apc', $descr, $scale_temp, $value, $options);
    }

    $oid   = ".1.3.6.1.4.1.318.1.1.10.4.2.3.1.6.$index";
    $value = $entry['memSensorsHumidity'];

    // Exclude already added sensor from emsProbeStatusTable
    if ($value > 0 && !isset($valid['sensor']['humidity']['PowerNet-MIB-emsProbeStatusProbeHumidity'][$ems_index])) {
        discover_sensor('humidity', $device, $oid, "memSensorsHumidity.$index", 'apc', $descr, 1, $value);
    }
}

#### INROW CHILLER ###################################################################################

// Build array to cope with APC using different OID trees for different device series.
// $inrow array main key is the sysObjectId.0 of the device.

$inrow = [];

$inrow['airIRRC100Series']['group']['status']['index']    = "airIRRCGroupStatus";
$inrow['airIRRC100Series']['group']['status']['oid']      = ".1.3.6.1.4.1.318.1.1.13.3.2.1.1";
$inrow['airIRRC100Series']['group']['setpoints']['index'] = "airIRRCGroupSetpoints";
$inrow['airIRRC100Series']['group']['setpoints']['oid']   = ".1.3.6.1.4.1.318.1.1.13.3.2.1.2";
$inrow['airIRRC100Series']['unit']['status']['index']     = "airIRRCUnitStatus";
$inrow['airIRRC100Series']['unit']['status']['oid']       = ".1.3.6.1.4.1.318.1.1.13.3.2.2.2";
$inrow['airIRRC100Series']['unit']['thresholds']['index'] = "airIRRCUnitThresholds";

$inrow['airIRRP100Series']['group']['status']['index']    = "airIRRP100GroupStatus";
$inrow['airIRRP100Series']['group']['status']['oid']      = ".1.3.6.1.4.1.318.1.1.13.3.3.1.1.1";
$inrow['airIRRP100Series']['group']['setpoints']['index'] = "airIRRP100GroupSetpoints";
$inrow['airIRRP100Series']['group']['setpoints']['oid']   = ".1.3.6.1.4.1.318.1.1.13.3.3.1.1.2";
$inrow['airIRRP100Series']['unit']['status']['index']     = "airIRRP100UnitStatus";
$inrow['airIRRP100Series']['unit']['status']['oid']       = ".1.3.6.1.4.1.318.1.1.13.3.3.1.2.2";
$inrow['airIRRP100Series']['unit']['thresholds']['index'] = "airIRRP100UnitThresholds";

$inrow['airIRRP500Series']['group']['status']['index']    = "airIRRP500GroupStatus";
$inrow['airIRRP500Series']['group']['status']['oid']      = ".1.3.6.1.4.1.318.1.1.13.3.3.2.1.1";
$inrow['airIRRP500Series']['group']['setpoints']['index'] = "airIRRP500GroupSetpoints";
$inrow['airIRRP500Series']['group']['setpoints']['oid']   = ".1.3.6.1.4.1.318.1.1.13.3.3.2.1.2";
$inrow['airIRRP500Series']['unit']['status']['index']     = "airIRRP500UnitStatus";
$inrow['airIRRP500Series']['unit']['status']['oid']       = ".1.3.6.1.4.1.318.1.1.13.3.3.2.2.2";
$inrow['airIRRP500Series']['unit']['thresholds']['index'] = "airIRRP500UnitThresholds";

$inrow['airIRSC100Series']['group']['status']['index']    = "airIRSCGroupStatus";
$inrow['airIRSC100Series']['group']['status']['oid']      = ".1.3.6.1.4.1.318.1.1.13.3.4.2.1";
$inrow['airIRSC100Series']['group']['setpoints']['index'] = "airIRSCGroupSetpoints";
$inrow['airIRSC100Series']['group']['setpoints']['oid']   = ".1.3.6.1.4.1.318.1.1.13.3.4.2.2";
$inrow['airIRSC100Series']['unit']['status']['index']     = "airIRSCUnitStatus";
$inrow['airIRSC100Series']['unit']['status']['oid']       = ".1.3.6.1.4.1.318.1.1.13.3.4.1.2";
$inrow['airIRSC100Series']['unit']['thresholds']['index'] = "airIRSCUnitThresholds";

$inrow['airIRRD100Series']['group']['status']['index']    = "airIRG2GroupStatus";
$inrow['airIRRD100Series']['group']['status']['oid']      = ".1.3.6.1.4.1.318.1.1.13.4.2.1";
$inrow['airIRRD100Series']['group']['setpoints']['index'] = "airIRG2GroupSetpoints";
$inrow['airIRRD100Series']['group']['setpoints']['oid']   = ".1.3.6.1.4.1.318.1.1.13.4.2.2";
$inrow['airIRRD100Series']['unit']['status']['index']     = "airIRG2RDT2Status";
$inrow['airIRRD100Series']['unit']['status']['oid']       = ".1.3.6.1.4.1.318.1.1.13.4.5.2.1";
$inrow['airIRRD100Series']['unit']['thresholds']['index'] = "airIRG2RDT2Thresholds";

//$type = snmp_get_oid($device, "sysObjectID.0", "PowerNet-MIB"); // Get the APC InRow model
$type = snmp_translate($device['sysObjectID'], 'PowerNet-MIB'); // Get the APC InRow model

if (array_key_exists($type, $inrow)) { // Check if the device is a supported APC InRow model as specifed above
    // APC InRow, Group Statistics
    echo($inrow[$type]['group']['status']['index'] . ' ');
    $cache['apc'] = snmpwalk_cache_oid($device, $inrow[$type]['group']['status']['index'], [], "PowerNet-MIB");
    echo($inrow[$type]['group']['setpoints']['index'] . ' ');
    $cache['apc'] = snmpwalk_cache_oid($device, $inrow[$type]['group']['setpoints']['index'], $cache['apc'], "PowerNet-MIB");

    foreach ($cache['apc'] as $index => $entry) {
        // airIRxxGroupStatusCoolOutput.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.1.x]
        $descr = "Group Cooling Output";
        //$oid   = $inrow[$type]['group']['status']['oid'] . ".1." . $index;
        $name  = $inrow[$type]['group']['status']['index'] . "CoolOutput";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('power', $device, $oid, "$name.$index", 'apc', $descr, 100, $value);
        }

        // airIRxxGroupStatusCoolDemand.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.2.x]
        $descr = "Group Cooling Demand";
        //$oid   = $inrow[$type]['group']['status']['oid'] . ".2." . $index;
        $name  = $inrow[$type]['group']['status']['index'] . "CoolDemand";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('power', $device, $oid, "$name.$index", 'apc', $descr, 100, $value);
        }

        // airIRxxGroupStatusAirFlowUS.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.3.x]
        $descr = "Group Air Flow";
        //$oid   = $inrow[$type]['group']['status']['oid'] . ".3." . $index;
        $name  = $inrow[$type]['group']['status']['index'] . "AirFlowUS";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            //$options = ['sensor_unit' => 'CFM']; // cubic feet per minute

            discover_sensor('airflow', $device, $oid, "$name.$index", 'apc', $descr, 1, $value, $options);
        }

        // airIRxxGroupStatusMaxRackInletTempMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.6.x]
        $descr = "Group Maximum Rack Inlet Temperature";
        //$oid   = $inrow[$type]['group']['status']['oid'] . ".6." . $index;
        $name  = $inrow[$type]['group']['status']['index'] . "MaxRackInletTempMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value);
        }

        // airIRxxGroupStatusMinRackInletTempMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.8.x]
        $descr = "Group Minimum Rack Inlet Temperature";
        //$oid   = $inrow[$type]['group']['status']['oid'] . ".8." . $index;
        $name  = $inrow[$type]['group']['status']['index'] . "MinRackInletTempMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value);
        }

        // airIRxxGroupStatusMaxReturnAirTempMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.10.x]
        $descr = "Group Maximum Return Air Temperature";
        //$oid   = $inrow[$type]['group']['status']['oid'] . ".10." . $index;
        $name  = $inrow[$type]['group']['status']['index'] . "MaxReturnAirTempMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value);
        }

        // airIRxxGroupStatusMinReturnAirTempMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.12.x]
        $descr = "Group Minimum Return Air Temperature";
        //$oid   = $inrow[$type]['group']['status']['oid'] . ".12." . $index;
        $name  = $inrow[$type]['group']['status']['index'] . "MinReturnAirTempMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value);
        }

        // airIRxxGroupSetpointsCoolMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.x.2.x]
        $descr = "Group Cooling Setpoint";
        //$oid   = $inrow[$type]['group']['setpoints']['oid'] . ".2." . $index;
        $name  = $inrow[$type]['group']['setpoints']['index'] . "CoolMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value);
        }

        // airIRxxGroupSetpointsSupplyAirMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.x.4.x]
        $descr = "Group Supply Setpoint";
        //$oid   = $inrow[$type]['group']['setpoints']['oid'] . ".4." . $index;
        $name  = $inrow[$type]['group']['setpoints']['index'] . "SupplyAirMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value);
        }
    }

    echo(' ');

    // APC InRow, Unit Statistics
    echo($inrow[$type]['unit']['status']['index'] . ' ');
    $cache['apc'] = snmpwalk_cache_oid($device, $inrow[$type]['unit']['status']['index'], [], "PowerNet-MIB");
    echo($inrow[$type]['unit']['thresholds']['index'] . ' ');
    $cache['apc'] = snmpwalk_cache_oid($device, $inrow[$type]['unit']['thresholds']['index'], $cache['apc'], "PowerNet-MIB");

    foreach ($cache['apc'] as $index => $entry) {
        // If there are multiple units found, use the unit number as description prefix
        $unit = safe_count($cache['apc']) != 1 ? "Unit " . ($index + 1) : "Unit";

        // airIRxxUnitStatusCoolOutput.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.2.x]
        $descr = $unit . " Cooling Output";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".2." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "CoolOutput";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('power', $device, $oid, "$name.$index", 'apc', $descr, 100, $value);
        }

        // airIRxxUnitStatusCoolDemand.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.3.x]
        $descr = $unit . " Cooling Demand";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".3." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "CoolDemand";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('power', $device, $oid, "$name.$index", 'apc', $descr, 100, $value);
        }

        // airIRxxUnitStatusAirFlowUS.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.4.x]
        $descr = $unit . " Air Flow";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".4." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "AirFlowUS";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('airflow', $device, $oid, "$name.$index", 'apc', $descr, 1, $value, $options);
        }

        // airIRxxUnitStatusRackInletTempMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.7.x]
        $descr = $unit . " Rack Inlet Temperature";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".7." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "RackInletTempMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];
        $limit = ['limit_high' => $entry[$inrow[$type]['unit']['thresholds']['index'] . 'RackInletHighTempMetric'] * 0.1];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value, $limit);
        }

        // airIRxxUnitStatusSupplyAirTempMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.9.x]
        // airIRRP100UnitStatusSupplyAirTempMetric.x [.1.3.6.1.4.1.318.1.1.13.3.3.1.2.2.5]
        $descr = $unit . " Supply Air Temperature";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".9." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "SupplyAirTempMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];
        $limit = ['limit_high' => $entry[$inrow[$type]['unit']['thresholds']['index'] . 'SupplyAirHighTempMetric'] * 0.1];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value, $limit);
        }

        // airIRxxUnitStatusReturnAirTempMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.11.x]
        $descr = $unit . " Return Air Temperature";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".11." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "ReturnAirTempMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];
        $limit = ['limit_high' => $entry[$inrow[$type]['unit']['thresholds']['index'] . 'ReturnAirHighTempMetric'] * 0.1];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value, $limit);
        }

        // airIRxxUnitStatusSuctionTempMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.13.x]
        $descr = $unit . " Suction Temperature";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".13." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "SuctionTempMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value);
        }

        // airIRxxUnitStatusFilterDPMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.13.x]
        $descr = $unit . " Air Filter Pressure";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".13." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "FilterDPMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('pressure', $device, $oid, "$name.$index", 'apc', $descr, 1, $value);
        }

        // airIRxxUnitStatusContainmtDPMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.15.x]
        $descr = $unit . " Containment Pressure";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".15." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "ContainmtDPMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('pressure', $device, $oid, "$name.$index", 'apc', $descr, 1, $value);
        }

        // airIRxxUnitStatusEnteringFluidTemperatureMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.24.x]
        $descr = $unit . " Entering Fluid Temperature";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".24." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "EnteringFluidTemperatureMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];
        $limit = ['limit_high' => $entry[$inrow[$type]['unit']['thresholds']['index'] . 'EnteringFluidHighTempMetric'] * 0.1];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value, $limit);
        }

        // airIRxxUnitStatusLeavingFluidTemperatureMetric.x [.1.3.6.1.4.1.318.1.1.13.x.x.x.26.x]
        $descr = $unit . " Leaving Fluid Temperature";
        //$oid   = $inrow[$type]['unit']['status']['oid'] . ".26." . $index;
        $name  = $inrow[$type]['unit']['status']['index'] . "LeavingFluidTemperatureMetric";
        $oid   = snmp_translate($name, 'PowerNet-MIB') . '.' . $index;
        $value = $entry[$name];

        if ($value != -1) {
            discover_sensor('temperature', $device, $oid, "$name.$index", 'apc', $descr, 0.1, $value);
        }
    }
}

unset($type, $inrow);

#### NEW GENERATION INROW CHILLER ####################################################################

// APC took a different approach here, with generic sensor descr/value in a table.
// According to documentation, it looks like the OIDs are hard linked to the sensor type,
// but I don't think we should rely on this to be true in the future as well.
// We map the units to sensor types through the following array.
// This does make it harder to link limits/setpoints to values though. :[

$apc_unit_map = [
  'C'   => 'temperature', // also dewpoint by descr
  'F'   => '', // Ignored, we use C instead
  '%RH' => 'humidity',
  'A'   => 'currency',
  'CFM' => 'airflow',
  'GPM' => 'waterflow', // Gallons per minute
  'kW'  => 'power',
  'W'   => 'power',
  '%'   => 'capacity',
  'Pa'  => 'pressure',
  'bar' => '', // Ignored, we use psi instead
  'psi' => 'pressure',
  'L/s' => 'waterflow',
  'rpm' => 'fanspeed',
  'Hz'  => 'frequency',
  'kWh' => 'energy', // counter
  'hr'  => 'lifetime', // Hours
  //'weeks' => 'lifetime', // Currently not supported
];

// PowerNet-MIB::coolingUnitStatusAnalogDescription.1.11 = STRING: "Rack (Average) Temperature"
// PowerNet-MIB::coolingUnitStatusAnalogValue.1.11 = INTEGER: 215
// PowerNet-MIB::coolingUnitStatusAnalogUnits.1.11 = STRING: "C"
// PowerNet-MIB::coolingUnitStatusAnalogScale.1.11 = INTEGER: 10

// Unknown Units:
// PowerNet-MIB::coolingUnitStatusAnalogTableIndex.1.23 = INTEGER: 23
// PowerNet-MIB::coolingUnitStatusAnalogDescription.1.23 = STRING: "Air Filter Pressure"
// PowerNet-MIB::coolingUnitStatusAnalogValue.1.23 = INTEGER: 2
// PowerNet-MIB::coolingUnitStatusAnalogUnits.1.23 = STRING: "\\\"WC"
// PowerNet-MIB::coolingUnitStatusAnalogScale.1.23 = INTEGER: 100

echo("coolingUnitStatusAnalogTable ");
$cache['apc'] = snmpwalk_cache_oid($device, 'coolingUnitStatusAnalogTable', [], "PowerNet-MIB");
print_debug_vars($cache['apc']);

foreach ($cache['apc'] as $index => $entry) {
    if ($apc_unit_map[$entry['coolingUnitStatusAnalogUnits']] && isset($entry['coolingUnitStatusAnalogValue'])) {
        // Proceed if we can map this to a local sensor type

        if (!is_numeric($entry['coolingUnitStatusAnalogScale']) || $entry['coolingUnitStatusAnalogScale'] == 0) {
            $entry['coolingUnitStatusAnalogScale'] = 1;
        }

        $descr    = $entry['coolingUnitStatusAnalogDescription'];
        $oid      = ".1.3.6.1.4.1.318.1.1.27.1.4.1.2.1.3.$index";
        $oid_name = 'coolingUnitStatusAnalogValue';
        $value    = $entry[$oid_name];
        $options  = [];

        // sensor class
        $class = $apc_unit_map[$entry['coolingUnitStatusAnalogUnits']];
        if ($class === 'temperature' && str_icontains_array($descr, 'Dew')) {
            $class = 'dewpoint';
        } elseif ($class === 'capacity' && str_icontains_array($descr, 'Speed')) {
            $class = 'load';
        } elseif (empty($class)) {
            // Skip unused classes
            continue;
        }

        // scale
        if ($entry['coolingUnitStatusAnalogUnits'] === 'kW' ||
            $entry['coolingUnitStatusAnalogUnits'] === 'kWh') {
            $scale = 1000 / $entry['coolingUnitStatusAnalogScale'];
        } elseif ($entry['coolingUnitStatusAnalogUnits'] === 'hr') {
            $scale = 3600 / $entry['coolingUnitStatusAnalogScale'];
        } elseif ($entry['coolingUnitStatusAnalogUnits'] === 'weeks') {
            $scale = 604800 / $entry['coolingUnitStatusAnalogScale'];
        } else {
            $scale = 1 / $entry['coolingUnitStatusAnalogScale'];
        }

        // Append unit conversion for airflow and waterflow
        if (in_array($entry['coolingUnitStatusAnalogUnits'], ['GPM', 'psi'])) {
            $options['sensor_unit'] = $entry['coolingUnitStatusAnalogUnits'];
        }

        if (in_array($class, ['energy', 'lifetime'])) {
            if ($value == 0) {
                continue;
            }
            discover_counter($device, $class, $mib, $oid_name, $oid, $index, $descr, $scale, $value, $options);
        } else {
            if (str_starts($descr, ['Minimum', 'Maximum'])) {
                continue;
            }
            discover_sensor($class, $device, $oid, "coolingUnitStatusAnalogValue.$index", 'apc', $descr, $scale, $value, $options);
        }
    }
}

echo(' ');

// PowerNet-MIB::coolingUnitExtendedAnalogDescription.1.1 = STRING: "Chilled Water Valve Position"
// PowerNet-MIB::coolingUnitExtendedAnalogValue.1.1 = INTEGER: 21
// PowerNet-MIB::coolingUnitExtendedAnalogUnits.1.1 = STRING: "%"
// PowerNet-MIB::coolingUnitExtendedAnalogScale.1.1 = INTEGER: 1

echo("coolingUnitExtendedAnalogTable ");
$cache['apc'] = snmpwalk_cache_oid($device, 'coolingUnitExtendedAnalogTable', [], "PowerNet-MIB");
print_debug_vars($cache['apc']);

foreach ($cache['apc'] as $index => $entry) {
    if ($apc_unit_map[$entry['coolingUnitExtendedAnalogUnits']] && isset($entry['coolingUnitExtendedAnalogValue'])) {
        // Proceed if we can map this to a local sensor type

        if (!is_numeric($entry['coolingUnitExtendedAnalogScale']) || $entry['coolingUnitExtendedAnalogScale'] == 0) {
            $entry['coolingUnitExtendedAnalogScale'] = 1;
        }

        $descr    = $entry['coolingUnitExtendedAnalogDescription'];
        $oid      = ".1.3.6.1.4.1.318.1.1.27.1.6.1.2.1.3.$index";
        $oid_name = 'coolingUnitExtendedAnalogValue';
        $value    = $entry[$oid_name];
        $options  = [];

        // sensor class
        $class = $apc_unit_map[$entry['coolingUnitExtendedAnalogUnits']];
        if ($class === 'temperature' && str_icontains_array($descr, 'Dew')) {
            $class = 'dewpoint';
        } elseif ($class === 'capacity' && str_icontains_array($descr, 'Speed')) {
            $class = 'load';
        } elseif (empty($class)) {
            // Skip unused classes
            continue;
        }

        // scale
        if ($entry['coolingUnitExtendedAnalogUnits'] === 'kW' ||
            $entry['coolingUnitExtendedAnalogUnits'] === 'kWh') {
            $scale = 1000 / $entry['coolingUnitExtendedAnalogScale'];
        } elseif ($entry['coolingUnitExtendedAnalogUnits'] === 'hr') {
            $scale = 3600 / $entry['coolingUnitExtendedAnalogScale'];
        } elseif ($entry['coolingUnitExtendedAnalogUnits'] === 'weeks') {
            $scale = 604800 / $entry['coolingUnitExtendedAnalogScale'];
        } else {
            $scale = 1 / $entry['coolingUnitExtendedAnalogScale'];
        }

        // Append unit conversion for airflow and waterflow
        if (in_array($entry['coolingUnitExtendedAnalogUnits'], ['GPM', 'psi'])) {
            $options['sensor_unit'] = $entry['coolingUnitExtendedAnalogUnits'];
        }

        if (!in_array($class, ['energy', 'lifetime'])) {
            discover_sensor($class, $device, $oid, "coolingUnitExtendedAnalogValue.$index", 'apc', $descr, $scale, $value, $options);
        }
    }
}

unset($apc_unit_map);

echo(' ');

// PowerNet-MIB::coolingUnitStatusDiscreteDescription.1.1 = STRING: "Operating Mode"
// PowerNet-MIB::coolingUnitStatusDiscreteDescription.1.2 = STRING: "Active Flow Control Status"
// PowerNet-MIB::coolingUnitStatusDiscreteValueAsString.1.1 = STRING: "On"
// PowerNet-MIB::coolingUnitStatusDiscreteValueAsString.1.2 = STRING: "NA"
// PowerNet-MIB::coolingUnitStatusDiscreteValueAsInteger.1.1 = INTEGER: 1
// PowerNet-MIB::coolingUnitStatusDiscreteValueAsInteger.1.2 = INTEGER: 3
// PowerNet-MIB::coolingUnitStatusDiscreteIntegerReferenceKey.1.1 = STRING: "Standby(0),On(1),Idle(2),Maintenance(3)"
// PowerNet-MIB::coolingUnitStatusDiscreteIntegerReferenceKey.1.2 = STRING: "Under(0),Okay(1),Over(2),NA(3)"

$apc_discrete_map = [
  "Open(0),Closed(1)"                                                  => 'powernet-cooling-input-state',
  "Abnormal(0),Normal(1)"                                              => 'powernet-cooling-output-state',
  "Primary (0),Secondary(1)"                                           => 'powernet-cooling-powersource-state',
  "Undefined(0),Standard(1),HighTemp(2)"                               => 'powernet-cooling-unittype-state',
  "Standby(0),On(1),Idle(2),Maintenance(3)"                            => 'powernet-cooling-opmode-state',
  "Under(0),Okay(1),Over(2),NA(3)"                                     => 'powernet-cooling-flowcontrol-state',
  'Under(0),Okay(1),Over(2),NA(3),NA(4)'                               => 'powernet-cooling-flowcontrol-state',
  'Unknown(0),Init(1),Off(2),Standby(3),Idle(4),Delaying(5),Active(6)' => 'powernet-cooling-mode-state',
  'No Leak(0),Leak Detected(1)'                                        => 'powernet-cooling-leak-state',
];

echo("coolingUnitStatusDiscreteTable ");
$cache['apc'] = snmpwalk_cache_oid($device, 'coolingUnitStatusDiscreteTable', [], "PowerNet-MIB");
print_debug_vars($cache['apc']);

foreach ($cache['apc'] as $index => $entry) {

    // If we have a state mapped, add status, if not, well... help.
    if ($apc_discrete_map[$entry['coolingUnitStatusDiscreteIntegerReferenceKey']]) {
        $descr    = $entry['coolingUnitStatusDiscreteDescription'];
        $oid      = ".1.3.6.1.4.1.318.1.1.27.1.4.2.2.1.4.$index";
        $type     = $apc_discrete_map[$entry['coolingUnitStatusDiscreteIntegerReferenceKey']];
        $oid_name = 'coolingUnitStatusDiscreteValueAsInteger';
        $value    = $entry[$oid_name];

        //discover_status($device, $oid, "coolingUnitStatusDiscreteValueAsInteger.$index", $apc_discrete_map[$entry['coolingUnitStatusDiscreteIntegerReferenceKey']], $descr, 1, $value);
        discover_status_ng($device, $mib, $oid_name, $oid, $index, $type, $descr, $value);
    }
}

echo(' ');

// PowerNet-MIB::coolingUnitExtendedDiscreteDescription.1.1 = STRING: "Standby Input State"
// PowerNet-MIB::coolingUnitExtendedDiscreteDescription.1.2 = STRING: "Output 1 State"
// PowerNet-MIB::coolingUnitExtendedDiscreteValueAsString.1.1 = STRING: "Open"
// PowerNet-MIB::coolingUnitExtendedDiscreteValueAsString.1.2 = STRING: "Normal"
// PowerNet-MIB::coolingUnitExtendedDiscreteValueAsInteger.1.1 = INTEGER: 0
// PowerNet-MIB::coolingUnitExtendedDiscreteValueAsInteger.1.2 = INTEGER: 1
// PowerNet-MIB::coolingUnitExtendedDiscreteIntegerReferenceKey.1.1 = STRING: "Open(0),Closed(1)"
// PowerNet-MIB::coolingUnitExtendedDiscreteIntegerReferenceKey.1.2 = STRING: "Abnormal(0),Normal(1)"
// PowerNet-MIB::coolingUnitExtendedDiscreteIntegerReferenceKey.1.6 = STRING: "Primary (0),Secondary(1)"
// PowerNet-MIB::coolingUnitExtendedDiscreteIntegerReferenceKey.1.7 = STRING: "Undefined(0),Standard(1),HighTemp(2)"

echo("coolingUnitExtendedDiscreteTable ");
$cache['apc'] = snmpwalk_cache_oid($device, 'coolingUnitExtendedDiscreteTable', [], "PowerNet-MIB");
print_debug_vars($cache['apc']);

foreach ($cache['apc'] as $index => $entry) {
    // If we have a state mapped, add status, if not, well... help.
    if ($apc_discrete_map[$entry['coolingUnitExtendedDiscreteIntegerReferenceKey']]) {

        $descr    = $entry['coolingUnitExtendedDiscreteDescription'];
        $oid      = ".1.3.6.1.4.1.318.1.1.27.1.6.2.2.1.4.$index";
        $type     = $apc_discrete_map[$entry['coolingUnitExtendedDiscreteIntegerReferenceKey']];
        $oid_name = 'coolingUnitExtendedDiscreteValueAsInteger';
        $value    = $entry[$oid_name];

        //discover_status($device, $oid, "coolingUnitExtendedDiscreteValueAsInteger.$index", $apc_discrete_map[$entry['coolingUnitExtendedDiscreteIntegerReferenceKey']], $descr, 1, $value);
        discover_status_ng($device, $mib, $oid_name, $oid, $index, $type, $descr, $value);
    }
}

unset($apc_discrete_map);

#### Legacy mUpsEnvironment Sensors (AP9312TH) #######################################################

echo(' ');

//mUpsEnvironAmbientTemperature.0 = 24
//mUpsEnvironRelativeHumidity.0 = 25
//mUpsEnvironAmbientTemperature2.0 = 0
//mUpsEnvironRelativeHumidity2.0 = 255
$cache['apc'] = snmp_get_multi_oid($device, "mUpsEnvironAmbientTemperature.0 mUpsEnvironRelativeHumidity.0 mUpsEnvironAmbientTemperature2.0 mUpsEnvironRelativeHumidity2.0", [], "PowerNet-MIB");

foreach ($cache['apc'] as $index => $entry) {
    if (is_numeric($entry['mUpsEnvironAmbientTemperature']) &&
        !isset($valid['sensor']['temperature']['PowerNet-MIB-emsProbeStatusProbeTemperature'][1])) {
        $descr = "Probe 1 Temperature";
        $oid   = ".1.3.6.1.4.1.318.1.1.2.1.1.$index";
        $value = $entry['mUpsEnvironAmbientTemperature'];

        discover_sensor('temperature', $device, $oid, "mUpsEnvironAmbientTemperature.$index", 'apc', $descr, 1, $value);
    }

    if (is_numeric($entry['mUpsEnvironRelativeHumidity']) &&
        !isset($valid['sensor']['humidity']['PowerNet-MIB-emsProbeStatusProbeHumidity'][1])) {
        $descr = "Probe 1 Humidity";
        $oid   = ".1.3.6.1.4.1.318.1.1.2.1.2.$index";
        $value = $entry['mUpsEnvironRelativeHumidity'];

        discover_sensor('humidity', $device, $oid, "mUpsEnvironRelativeHumidity.$index", 'apc', $descr, 1, $value);
    }

    if ($entry['mUpsEnvironAmbientTemperature2'] != 0 && $entry['mUpsEnvironRelativeHumidity2'] != 255) {
        if (is_numeric($entry['mUpsEnvironAmbientTemperature2']) &&
            !isset($valid['sensor']['temperature']['PowerNet-MIB-emsProbeStatusProbeTemperature'][2])) {
            $descr = "Probe 2 Temperature";
            $oid   = ".1.3.6.1.4.1.318.1.1.2.1.3.$index";
            $value = $entry['mUpsEnvironAmbientTemperature2'];

            discover_sensor('temperature', $device, $oid, "mUpsEnvironAmbientTemperature2.$index", 'apc', $descr, 1, $value);
        }

        if (is_numeric($entry['mUpsEnvironRelativeHumidity2']) &&
            !isset($valid['sensor']['humidity']['PowerNet-MIB-emsProbeStatusProbeHumidity'][2])) {
            $descr = "Probe 2 Humidity";
            $oid   = ".1.3.6.1.4.1.318.1.1.2.1.4.$index";
            $value = $entry['mUpsEnvironRelativeHumidity2'];

            discover_sensor('humidity', $device, $oid, "mUpsEnvironRelativeHumidity2.$index", 'apc', $descr, 1, $value);
        }
    }
}

echo("mUpsContactTable ");
$cache['apc'] = snmpwalk_cache_oid($device, 'mUpsContactTable', $cache['apc'], "PowerNet-MIB");

foreach ($cache['apc'] as $index => $entry) {
    if ($entry['monitoringStatus'] == "enabled") {
        $descr = $entry['description'];
        $oid   = ".1.3.6.1.4.1.318.1.1.2.2.2.1.5.$index";
        $value = $entry['currentStatus'];

        discover_status($device, $oid, "currentStatus.$index", 'powernet-mupscontact-state', $descr, $value, ['entPhysicalClass' => 'other']);
    }
}

#### NETBOTZ PX ACCESS CONTROL #######################################################################

// accessPXIdentProductNumber.0 = STRING: "AP9361"
// accessPXIdentHardwareRev.0 = STRING: "04"
// accessPXIdentDateOfManufacture.0 = STRING: "04/29/2010"
// accessPXIdentSerialNumber.0 = STRING: "QA1018180304"
// accessPXConfigCardReaderEnableDisableAction.0 = INTEGER: enable(2)
// accessPXConfigAutoRelockTime.0 = INTEGER: 60
// accessPXConfigCardFormat.0 = INTEGER: hidStd26(1)
// accessPXConfigBeaconName.0 = STRING: "Beacon Name"
// accessPXConfigBeaconLocation.0 = STRING: "Beacon Location"
// accessPXConfigBeaconAction.0 = INTEGER: disconnectedReadOnly(4)
// accessPXStatusBeaconName.0 = STRING: "Beacon Name"
// accessPXStatusBeaconLocation.0 = STRING: "Beacon Location"
// accessPXStatusBeaconCurrentState.0 = INTEGER: disconnected(4)

echo("accessPX ");
$cache['apc'] = snmpwalk_cache_oid($device, 'accessPX', [], "PowerNet-MIB");

foreach ($cache['apc'] as $index => $entry) {
    // accessPXIdentAlarmStatus.0 = INTEGER: 3
    if ($entry['accessPXIdentAlarmStatus']) {
        $descr = 'Access PX Alarm Status';
        $oid   = ".1.3.6.1.4.1.318.1.1.20.1.1.1.$index";

        discover_status($device, $oid, "accessPXIdentAlarmStatus.$index", 'powernet-accesspx-state', $descr, $entry['accessPXIdentAlarmStatus']);
    }

    // accessPXConfigFrontDoorLockControl.0 = INTEGER: lock(2)
    // accessPXConfigFrontDoorMaxOpenTime.0 = INTEGER: 10
    // accessPXStatusFrontDoorLock.0 = INTEGER: locked(2)
    // accessPXStatusFrontDoor.0 = INTEGER: closed(2)
    // accessPXStatusFrontDoorHandle.0 = INTEGER: closed(2)
    // accessPXStatusFrontDoorMaxOpenTime.0 = INTEGER: 10
    // accessPXStatusFrontDoorAlarmStatus.0 = INTEGER: 1

    if ($entry['accessPXStatusFrontDoorLock']) {
        $descr = 'Front Door Lock';
        $oid   = ".1.3.6.1.4.1.318.1.1.20.1.4.1.$index";

        discover_status($device, $oid, "accessPXStatusFrontDoorLock.$index", 'powernet-door-lock-state', $descr, $entry['accessPXStatusFrontDoorLock']);
    }

    if ($entry['accessPXStatusFrontDoor']) {
        $descr = 'Front Door';
        $oid   = ".1.3.6.1.4.1.318.1.1.20.1.4.2.$index";

        discover_status($device, $oid, "accessPXStatusFrontDoor.$index", 'powernet-door-state', $descr, $entry['accessPXStatusFrontDoor']);
    }

    if ($entry['accessPXStatusFrontDoorHandle']) {
        $descr = 'Front Door Handle';
        $oid   = ".1.3.6.1.4.1.318.1.1.20.1.4.3.$index";

        discover_status($device, $oid, "accessPXStatusFrontDoorHandle.$index", 'powernet-door-state', $descr, $entry['accessPXStatusFrontDoorHandle']);
    }

    if ($entry['accessPXStatusFrontDoorAlarmStatus']) {
        $descr = 'Front Door Alarm Status';
        $oid   = ".1.3.6.1.4.1.318.1.1.20.1.4.5.$index";

        discover_status($device, $oid, "accessPXStatusFrontDoorAlarmStatus.$index", 'powernet-door-alarm-state', $descr, $entry['accessPXStatusFrontDoorAlarmStatus']);
    }

    // accessPXConfigRearDoorLockControl.0 = INTEGER: lock(2)
    // accessPXConfigRearDoorMaxOpenTime.0 = INTEGER: 10
    // accessPXStatusRearDoorLock.0 = INTEGER: locked(2)
    // accessPXStatusRearDoor.0 = INTEGER: closed(2)
    // accessPXStatusRearDoorHandle.0 = INTEGER: closed(2)
    // accessPXStatusRearDoorMaxOpenTime.0 = INTEGER: 10
    // accessPXStatusRearDoorAlarmStatus.0 = INTEGER: 1

    if ($entry['accessPXStatusRearDoorLock']) {
        $descr = 'Rear Door Lock';
        $oid   = ".1.3.6.1.4.1.318.1.1.20.1.6.1.$index";

        discover_status($device, $oid, "accessPXStatusRearDoorLock.$index", 'powernet-door-lock-state', $descr, $entry['accessPXStatusRearDoorLock']);
    }

    if ($entry['accessPXStatusRearDoor']) {
        $descr = 'Rear Door';
        $oid   = ".1.3.6.1.4.1.318.1.1.20.1.6.2.$index";

        discover_status($device, $oid, "accessPXStatusRearDoor.$index", 'powernet-door-state', $descr, $entry['accessPXStatusRearDoor']);
    }

    if ($entry['accessPXStatusRearDoorHandle']) {
        $descr = 'Rear Door Handle';
        $oid   = ".1.3.6.1.4.1.318.1.1.20.1.6.3.$index";

        discover_status($device, $oid, "accessPXStatusRearDoorHandle.$index", 'powernet-door-state', $descr, $entry['accessPXStatusRearDoorHandle']);
    }

    if ($entry['accessPXStatusRearDoorAlarmStatus']) {
        $descr = 'Rear Door Alarm Status';
        $oid   = ".1.3.6.1.4.1.318.1.1.20.1.6.5.$index";

        discover_status($device, $oid, "accessPXStatusRearDoorAlarmStatus.$index", 'powernet-door-alarm-state', $descr, $entry['accessPXStatusRearDoorAlarmStatus']);
    }
}

// EOF
