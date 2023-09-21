<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// First attempt at radio polling. Could do with some improvement perhaps

// Getting Radios

$radios_snmp = snmpwalk_cache_oid($device, 'RuckusRadioNumber', [], 'RUCKUS-RADIO-MIB');
if ($GLOBALS['snmp_status']) {
    $radios_snmp = snmpwalk_cache_oid($device, 'ruckusRadioStatsTable', [], 'RUCKUS-RADIO-MIB');
    if (OBS_DEBUG > 1) {
        print_vars($radios_snmp);
    }
}

$polled = time();

// Goes through the SNMP radio data
foreach ($radios_snmp as $radio_number => $radio) {

    $radio['polled']        = $polled;
    $radio['radio_number']  = $radio_number;
    $radio['radio_ap']      = 0; // Hardcoded since the AP is self.
    $radio['radio_clients'] = $radio['ruckusRadioStatsNumSta'];

    if (OBS_DEBUG && count($radio)) {
        print_vars($radio);
    }

    // FIXME -- This is Ruckus only and subject to change. RRD files may be wiped as we modify this format to fit everything.

    $dses = ['assoc_fail_rate'   => ['oid' => 'ruckusRadioStatsAssocFailRate', 'type' => 'gauge'],
             'auth_success_rate' => ['oid' => 'ruckusRadioStatsAssocSuccessRate', 'type' => 'gauge'],
             'auth_fail_rate'    => ['oid' => 'ruckusRadioStatsAuthFailRate', 'type' => 'gauge'],
             'max_stations'      => ['oid' => 'ruckusRadioStatsMaxSta', 'type' => 'gauge'],
             'assoc_fail'        => ['oid' => 'ruckusRadioStatsNumAssocFail'],
             'assoc_req'         => ['oid' => 'ruckusRadioStatsNumAssocReq'],
             'assoc_resp'        => ['oid' => 'ruckusRadioStatsNumAssocResp'],
             'assoc_success'     => ['oid' => 'ruckusRadioStatsNumAssocSuccess'],
             'auth_fail'         => ['oid' => 'ruckusRadioStatsNumAuthFail'],
             'auth_req'          => ['oid' => 'ruckusRadioStatsNumAuthReq'],
             'auth_resp'         => ['oid' => 'ruckusRadioStatsNumAuthResp'],
             'auth_stations'     => ['oid' => 'ruckusRadioStatsNumAuthSta', 'type' => 'gauge'],
             'auth_success'      => ['oid' => 'ruckusRadioStatsNumAuthSuccess'],
             'num_stations'      => ['oid' => 'ruckusRadioStatsNumSta', 'type' => 'gauge'],
             'resource_util'     => ['oid' => 'ruckusRadioStatsResourceUtil', 'type' => 'gauge'],
             'rx_bytes'          => ['oid' => 'ruckusRadioStatsRxBytes'],
             'rx_decrypt_crcerr' => ['oid' => 'ruckusRadioStatsRxDecryptCRCError'],
             'rx_errors'         => ['oid' => 'ruckusRadioStatsRxErrors'],
             'rx_frames'         => ['oid' => 'ruckusRadioStatsRxFrames'],
             'rx_mic_error'      => ['oid' => 'ruckusRadioStatsRxMICError'],
             'rx_wep_fail'       => ['oid' => 'ruckusRadioStatsRxWEPFail'],
             'tx_bytes'          => ['oid' => 'ruckusRadioStatsTxBytes'],
             'tx_frames'         => ['oid' => 'ruckusRadioStatsTxFrames'],
             'total_airtime'     => ['oid' => 'ruckusRadioStatsTotalAirtime'],
             'total_assoc_time'  => ['oid' => 'ruckusRadioStatsTotalAssocTime'],
             'busy_airtime'      => ['oid' => 'ruckusRadioStatsBusyAirtime']
    ];

    $rrd_file   = 'wifi-radio-' . $radio['radio_ap'] . '-' . $radio['radio_number'] . '.rrd';
    $rrd_update = 'N';
    $rrd_create = '';

    foreach ($dses as $ds => $ds_data) {

        $oid = $ds_data['oid'];

        $radio[$ds] = $radio[$oid];

        if ($ds_data['type'] == 'gauge') {
            $rrd_create .= ' DS:' . $ds . ':GAUGE:600:U:100000000000';
        } else {
            $rrd_create .= ' DS:' . $ds . ':COUNTER:600:U:100000000000';
        }

        if (is_numeric($radio[$oid])) {
            $rrd_update .= ':' . $radio[$oid];
        } else {
            $rrd_update .= ':U';
        }
    }

    rrdtool_create($device, $rrd_file, $rrd_create);
    rrdtool_update($device, $rrd_file, $rrd_update);

    $radio_db = $GLOBALS['cache']['wifi_radios'][$radio['radio_ap']][$radio['radio_number']];

    if ($radio_db['polled'] > 1) {
        $radio['poll_period']  = $radio['polled'] - $radio_db['polled'];
        $radio['rx_bits_diff'] = $radio['rx_bits'] - $radio_db['rx_bits'];
        $radio['tx_bits_diff'] = $radio['tx_bits'] - $radio_db['tx_bits'];
        $radio['rx_bits_rate'] = $radio['rx_bits_diff'] / $radio['poll_period'];
        $radio['tx_bits_rate'] = $radio['tx_bits_diff'] / $radio['poll_period'];
    }

    $fields = ['num_clients', 'tx_bytes', 'rx_bytes', 'tx_bytes_rate', 'rx_bytes_rate', 'polled'];

    foreach ($fields as $field) {
        // FIXME. I not found where this array used for wifi
        // var $update_array used to track devices fields changes
        if ($radio_db[$field] != $radio[$field]) {
            $update_radio[$field] = $radio[$field];
        }
    }

    print_r($radio);
}

unset($radios_snmp);

// EOF
