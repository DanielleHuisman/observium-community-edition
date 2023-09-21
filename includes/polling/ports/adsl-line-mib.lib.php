<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// ADSL-LINE-MIB functions

// Example snmpwalk with units
// "Interval" oids have been filtered out
# adslLineCoding.1 = dmt
# adslLineType.1 = fastOrInterleaved
# adslLineSpecific.1 = zeroDotZero
# adslLineConfProfile.1 = "qwer"
# adslAtucInvSerialNumber.1 = "IES-1000 AAM1008-61"
# adslAtucInvVendorID.1 = "4"
# adslAtucInvVersionNumber.1 = "0"
# adslAtucCurrSnrMgn.1 = 150 tenth dB
# adslAtucCurrAtn.1 = 20 tenth dB
# adslAtucCurrStatus.1 = "00 00 "
# adslAtucCurrOutputPwr.1 = 100 tenth dBm
# adslAtucCurrAttainableRate.1 = 10272000 bps
# adslAturInvVendorID.1 = "0"
# adslAturInvVersionNumber.1 = "0"
# adslAturCurrSnrMgn.1 = 210 tenth dB
# adslAturCurrAtn.1 = 20 tenth dB
# adslAturCurrStatus.1 = "00 00 "
# adslAturCurrOutputPwr.1 = 0 tenth dBm
# adslAturCurrAttainableRate.1 = 1056000 bps
# adslAtucChanInterleaveDelay.1 = 6 milli-seconds
# adslAtucChanCurrTxRate.1 = 8064000 bps
# adslAtucChanPrevTxRate.1 = 0 bps
# adslAturChanInterleaveDelay.1 = 9 milli-seconds
# adslAturChanCurrTxRate.1 = 512000 bps
# adslAturChanPrevTxRate.1 = 0 bps
# adslAtucPerfLofs.1 = 0
# adslAtucPerfLoss.1 = 0
# adslAtucPerfLols.1 = 0
# adslAtucPerfLprs.1 = 0
# adslAtucPerfESs.1 = 0
# adslAtucPerfInits.1 = 1
# adslAtucPerfValidIntervals.1 = 0
# adslAtucPerfInvalidIntervals.1 = 0
# adslAturPerfLoss.1 = 0 seconds
# adslAturPerfESs.1 = 0 seconds
# adslAturPerfValidIntervals.1 = 0
# adslAturPerfInvalidIntervals.1 = 0

function process_port_adsl(&$this_port, $device, $port)
{
    // Check to make sure Port data is cached.
    if (!isset($this_port['adslLineCoding'])) {
        return;
    }

    // Used below for StatsD only
    $adsl_oids = [
      'adslAtucCurrSnrMgn', 'adslAtucCurrAtn', 'adslAtucCurrOutputPwr', 'adslAtucCurrAttainableRate', 'adslAtucChanCurrTxRate',
      'adslAturCurrSnrMgn', 'adslAturCurrAtn', 'adslAturCurrOutputPwr', 'adslAturCurrAttainableRate', 'adslAturChanCurrTxRate',
      'adslAtucPerfLofs', 'adslAtucPerfLoss', 'adslAtucPerfLprs', 'adslAtucPerfESs', 'adslAtucPerfInits',
      'adslAturPerfLofs', 'adslAturPerfLoss', 'adslAturPerfLprs', 'adslAturPerfESs',
      'adslAtucChanCorrectedBlks', 'adslAtucChanUncorrectBlks',
      'adslAturChanCorrectedBlks', 'adslAturChanUncorrectBlks'
    ];

    $adsl_db_oids = [
      'adslLineCoding', 'adslLineType',
      'adslAtucInvVendorID', 'adslAtucInvVersionNumber', 'adslAtucCurrSnrMgn', 'adslAtucCurrAtn', 'adslAtucCurrOutputPwr', 'adslAtucCurrAttainableRate',
      'adslAturInvSerialNumber', 'adslAturInvVendorID', 'adslAturInvVersionNumber',
      'adslAtucChanCurrTxRate', 'adslAturChanCurrTxRate', 'adslAturCurrSnrMgn', 'adslAturCurrAtn', 'adslAturCurrOutputPwr', 'adslAturCurrAttainableRate'
    ];

    $adsl_tenth_oids = ['adslAtucCurrSnrMgn', 'adslAtucCurrAtn', 'adslAtucCurrOutputPwr', 'adslAturCurrSnrMgn', 'adslAturCurrAtn', 'adslAturCurrOutputPwr'];

    foreach ($adsl_tenth_oids as $oid) {
        if (isset($this_port[$oid])) {
            $this_port[$oid] = $this_port[$oid] / 10;
        }
    }

    if (!dbExist('ports_adsl', '`port_id` = ?', [$port['port_id']])) {
        dbInsert(['device_id' => $device['device_id'], 'port_id' => $port['port_id']], 'ports_adsl');
    }
    $adsl_port = dbFetchRow('SELECT * FROM `ports_adsl` WHERE `port_id` = ?', [$port['port_id']]);

    $adsl_update = [];
    foreach ($adsl_db_oids as $oid) {
        if ($adsl_port[$oid] != $this_port[$oid]) {
            $adsl_update[$oid] = $this_port[$oid];
        }
    }
    if (count($adsl_update)) {
        $adsl_update['port_adsl_updated'] = ['NOW()'];
        dbUpdate($adsl_update, 'ports_adsl', '`port_id` = ?', [$port['port_id']]);
    }

    if ($this_port['adslAtucCurrSnrMgn'] > "1280") {
        $this_port['adslAtucCurrSnrMgn'] = "U";
    }
    if ($this_port['adslAturCurrSnrMgn'] > "1280") {
        $this_port['adslAturCurrSnrMgn'] = "U";
    }

    rrdtool_update_ng($device, 'port-adsl', [
      'AtucCurrSnrMgn'      => $this_port['adslAtucCurrSnrMgn'],
      'AtucCurrAtn'         => $this_port['adslAtucCurrAtn'],
      'AtucCurrOutputPwr'   => $this_port['adslAtucCurrOutputPwr'],
      'AtucCurrAttainableR' => $this_port['adslAtucCurrAttainableR'],
      'AtucChanCurrTxRate'  => $this_port['adslAtucChanCurrTxRate'],
      'AturCurrSnrMgn'      => $this_port['adslAturCurrSnrMgn'],
      'AturCurrAtn'         => $this_port['adslAturCurrAtn'],
      'AturCurrOutputPwr'   => $this_port['adslAturCurrOutputPwr'],
      'AturCurrAttainableR' => $this_port['adslAturCurrAttainableR'],
      'AturChanCurrTxRate'  => $this_port['adslAturChanCurrTxRate'],
      'AtucPerfLofs'        => $this_port['adslAtucPerfLofs'],
      'AtucPerfLoss'        => $this_port['adslAtucPerfLoss'],
      'AtucPerfLprs'        => $this_port['adslAtucPerfLprs'],
      'AtucPerfESs'         => $this_port['adslAtucPerfESs'],
      'AtucPerfInits'       => $this_port['adslAtucPerfInits'],
      'AturPerfLofs'        => $this_port['adslAturPerfLofs'],
      'AturPerfLoss'        => $this_port['adslAturPerfLoss'],
      'AturPerfLprs'        => $this_port['adslAturPerfLprs'],
      'AturPerfESs'         => $this_port['adslAturPerfESs'],
      'AtucChanCorrectedBl' => $this_port['adslAtucChanCorrectedBl'],
      'AtucChanUncorrectBl' => $this_port['adslAtucChanUncorrectBl'],
      'AturChanCorrectedBl' => $this_port['adslAturChanCorrectedBl'],
      'AturChanUncorrectBl' => $this_port['adslAturChanUncorrectBl'],
    ],                get_port_rrdindex($port));

    if ($GLOBALS['config']['statsd']['enable']) {
        foreach ($adsl_oids as $oid) {
            // Update StatsD/Carbon
            StatsD ::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'port' . '.' . $port['ifIndex'] . '.' . $oid, $this_port[$oid]);
        }
    }

    //echo("ADSL (".$this_port['adslLineCoding']."/".formatRates($this_port['adslAtucChanCurrTxRate'])."/".formatRates($this_port['adslAturChanCurrTxRate']).")");
}

// EOF
