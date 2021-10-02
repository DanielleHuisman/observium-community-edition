<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

// Keep this os detect here!
// for detect as last turn (when other definitions not detected)

if ($os) { return; }

if (str_starts($sysObjectID, '.1.3.6.1.4.1.8072.3.2.10') ||
    str_starts($sysDescr, 'Linux')) {
  $os = 'linux';
}

if ($os === 'linux') {
  // Now network based checks
  if (str_starts($sysObjectId, array('.1.3.6.1.4.1.10002.1', '.1.3.6.1.4.1.41112.1.4')) ||
      str_contains_array(snmp_getnext_oid($device, 'dot11manufacturerName', 'IEEE802dot11-MIB'), 'Ubiquiti')) {
    $os   = 'airos';
    $data = snmp_getnext_oid($device, 'dot11manufacturerProductName', 'IEEE802dot11-MIB');
    if (str_contains_array($data, 'UAP')) {
      $os = 'unifi';
    } elseif (snmp_get_oid($device, 'fwVersion.1', 'UBNT-AirFIBER-MIB') != '') {
      $os = 'airos-af';
    }
  } elseif ($sysObjectID === '.1.3.6.1.4.1.8072.3.2.10' &&
            snmp_get_oid($device, '.1.3.6.1.4.1.9839.1.2.0') > 0) {
    // NOTE! This is very hard hack for detect some devices connected to Carel pCOweb
    // carel-denco.snmprec:1.3.6.1.4.1.9839.2.1.2.6.0|2|0
    // carel-dimplex.snmprec:1.3.6.1.4.1.9839.2.1.2.6.0|2|-9999
    // carel-crac1.snmprec:1.3.6.1.4.1.9839.2.1.2.6.0|2|425
    // carel-crac2.snmprec:1.3.6.1.4.1.9839.2.1.2.6.0|2|462
    $carel_test = snmp_get_oid($device, '.1.3.6.1.4.1.9839.2.1.2.6.0');
    if ($carel_test > 0 && strlen($carel_test) > 1) {
      $os = 'pcoweb-crac';
      return;
    }

    // carel-denco.snmprec:1.3.6.1.4.1.9839.2.1.2.16.0|2|500
    // carel-dimplex.snmprec:1.3.6.1.4.1.9839.2.1.2.16.0|2|0
    // carel-crac1.snmprec:1.3.6.1.4.1.9839.2.1.2.16.0|2|0
    // carel-crac2.snmprec:1.3.6.1.4.1.9839.2.1.2.16.0|2|0
    $carel_test = snmp_get_oid($device, '.1.3.6.1.4.1.9839.2.1.2.16.0');
    if ($carel_test > 0 && strlen($carel_test) > 1) {
      $os = 'pcoweb-denco';
      return;
    }

    // FIXME. This is also incorrect, but we not have other test units
    // carel-denco.snmprec:1.3.6.1.4.1.9839.2.1.2.3.0|2|180
    // carel-dimplex.snmprec:1.3.6.1.4.1.9839.2.1.2.3.0|2|529
    // carel-crac1.snmprec:1.3.6.1.4.1.9839.2.1.2.3.0|2|0
    // carel-crac2.snmprec:1.3.6.1.4.1.9839.2.1.2.3.0|2|0
    $os = 'pcoweb-chiller';
  }
  // OLD incorrect test
  //elseif (is_numeric(trim(snmp_get($device, 'temp-mand.0', '-OqvU', 'UNCDZ-MIB'))))                { $os = 'pcoweb-chiller'; }
  //elseif (is_numeric(trim(snmp_get($device, 'roomTemp.0', '-OqvU', 'CAREL-ug40cdz-MIB'))))         { $os = 'pcoweb-crac'; }
}

// EOF
