<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

/*
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslIndex.1 = INTEGER: 1
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslLinkStatus.1 = STRING: "Up"
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslTc.1 = STRING: "G.993.2_Annex_K_PTM"
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslUsAttenuation.1 = INTEGER: 0
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslDsAttenuation.1 = INTEGER: 290
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslUsNoiseMargin.1 = INTEGER: 57
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslDsNoiseMargin.1 = INTEGER: 67
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslUsCurrRate.1 = INTEGER: 5788
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslDsCurrRate.1 = INTEGER: 14972
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslModulationType.1 = STRING: "G.993.2_Annex_B"
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslUsMaxTheorRate.1 = INTEGER: 5844000
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslDsMaxTheorRate.1 = INTEGER: 14983760
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslUsTotBytes.1 = INTEGER: 0
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslDsTotBytes.1 = INTEGER: 0
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslNeTotCrcErr.1 = INTEGER: 3043
 enterprises.aethra.atosnt.dsl.xdsl.xdslTable.xdslEntry.xdslNeShowtimeCrcErr.1 = INTEGER: 3043

.1.3.6.1.4.1.7745.5.3.1.1.1.1.1 = INTEGER: 1

 */

$table_defs['AETHRA-MIB']['xdsl'] = array (
  'table'         => 'xdsl',
  'numeric'       => '.1.3.6.1.4.1.7745.5.3.1.1.1',
  'call_function' => 'snmp_walk',
  'mib'           => 'AETHRA-MIB',
  'descr'         => 'Aethra xDSL Statistics',
  'graphs'        => array('xdsl_attenuation', 'xdsl_noisemargin', 'xdsl_rate', 'xdsl_bits', 'xdsl_errors'),
  'ds_rename'     => array('xdsl' => ''),
  'index'         => '1',
  'oids'          => array(
    'xdslUsAttenuation'    =>  array('numeric' => '4',  'multiplier' => '0.1',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'xdslDsAttenuation'    =>  array('numeric' => '5',  'multiplier' => '0.1',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'xdslUsNoiseMargin'    =>  array('numeric' => '6',  'multiplier' => '0.1',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'xdslDsNoiseMargin'    =>  array('numeric' => '7',  'multiplier' => '0.1',  'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'xdslUsCurrRate'       =>  array('numeric' => '8',  'multiplier' => '1000', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'xdslDsCurrRate'       =>  array('numeric' => '9',  'multiplier' => '1000', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'xdslUsMaxTheorRate'   =>  array('numeric' => '11', 'multiplier' => '1000', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'xdslDsMaxTheorRate'   =>  array('numeric' => '12', 'multiplier' => '1000', 'descr' => '', 'ds_type' => 'GAUGE', 'ds_min' => '0'),
    'xdslUsTotBytes'       =>  array('numeric' => '13', 'descr' => '', 'ds_type' => 'COUNTER', 'ds_min' => '0'),
    'xdslDsTotBytes'       =>  array('numeric' => '14', 'descr' => '', 'ds_type' => 'COUNTER', 'ds_min' => '0'),
    'xdslNeTotCrcErr'      =>  array('numeric' => '15', 'descr' => '', 'ds_type' => 'COUNTER', 'ds_min' => '0')
  )
);