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

/* Detection for JDSU OEM Erbium Dotted Fibre Amplifiers */

// This Oids not have Named Oids in current known MIBs,
// But this should be NSCRTV-HFCEMS-OPTICALAMPLIFIER-MIB

// NSCRTV-ROOT::oaIdent.8.0 = INTEGER: 3
// NSCRTV-ROOT::oaIdent.9.0 = INTEGER: 35
// NSCRTV-ROOT::oaIdent.10.0 = INTEGER: -15
// NSCRTV-ROOT::oaIdent.11.0 = INTEGER: 175
$oid   = ".1.3.6.1.4.1.17409.1.11.9.0";
$value = snmp_get_oid($device, $oid);

if (is_numeric($value) && $value != 0) //&& !isset($valid['sensor']['dbm']['NSCRTV-HFCEMS-OPTICALAMPLIFIER-MIB-oaInputOpticalPower']))
{
    discover_sensor('dbm', $device, $oid, 1, 'nscrtv-input-a-amplifier', 'Input on Optical Amplifier Input A', 0.1, $value);
}

$oid   = ".1.3.6.1.4.1.17409.1.11.10.0";
$value = snmp_get_oid($device, $oid);

if (is_numeric($value) && $value != 0) //&& !isset($valid['sensor']['dbm']['NSCRTV-HFCEMS-OPTICALAMPLIFIER-MIB-oaOutputOpticalPower']))
{
    discover_sensor('dbm', $device, $oid, 1, 'nscrtv-input-b-amplifier', 'Input on Optical Amplifier Input B', 0.1, $value);
}

// EOF
