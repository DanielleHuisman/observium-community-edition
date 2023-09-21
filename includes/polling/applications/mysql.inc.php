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

if (!empty($agent_data['app']['mysql'])) {
    $app_id = discover_app($device, 'mysql');

    $map = [];
    foreach (explode("\n", $agent_data['app']['mysql']) as $str) {
        [$key, $value] = explode(":", $str);
        $map[$key] = trim($value);
    }

    // General Stats
    $mapping = [
        // DS => Agent field
        'IDBLBSe' => 'cr',
        'IBLFh'   => 'ct',
        'IBLWn'   => 'cu',
        'IBLWn'   => 'cu',
        'SRows'   => 'ck',
        'SRange'  => 'cj',
        'SMPs'    => 'ci',
        'SScan'   => 'cl',
        'IBIRd'   => 'ai',
        'IBIWr'   => 'aj',
        'IBILg'   => 'ak',
        'IBIFSc'  => 'ah',
        'IDBRDd'  => 'b2',
        'IDBRId'  => 'b0',
        'IDBRRd'  => 'b3',
        'IDBRUd'  => 'b1',
        'IBRd'    => 'ae',
        'IBCd'    => 'af',
        'IBWr'    => 'ag',
        'TLIe'    => 'b5',
        'TLWd'    => 'b4',
        'IBPse'   => 'aa',
        'IBPDBp'  => 'ac',
        'IBPFe'   => 'ab',
        'IBPMps'  => 'ad',
        'TOC'     => 'bc',
        'OFs'     => 'b7',
        'OTs'     => 'b8',
        'OdTs'    => 'b9',
        'IBSRs'   => 'ay',
        'IBSWs'   => 'ax',
        'IBOWs'   => 'az',
        'QCs'     => 'c1',
        'QCeFy'   => 'bu',
        'MaCs'    => 'bl',
        'MUCs'    => 'bf',
        'ACs'     => 'bd',
        'AdCs'    => 'be',
        'TCd'     => 'bi',
        'Cs'      => 'bn',
        'IBTNx'   => 'a5',
        'KRRs'    => 'a0',
        'KRs'     => 'a1',
        'KWR'     => 'a2',
        'KWs'     => 'a3',
        'QCQICe'  => 'bz',
        'QCHs'    => 'bv',
        'QCIs'    => 'bw',
        'QCNCd'   => 'by',
        'QCLMPs'  => 'bx',
        'CTMPDTs' => 'cn',
        'CTMPTs'  => 'cm',
        'CTMPFs'  => 'co',
        'IBIIs'   => 'au',
        'IBIMRd'  => 'av',
        'IBIMs'   => 'aw',
        'IBILog'  => 'al',
        'IBISc'   => 'am',
        'IBIFLg'  => 'an',
        'IBFBl'   => 'aq',
        'IBIIAo'  => 'ap',
        'IBIAd'   => 'as',
        'IBIAe'   => 'at',
        'SFJn'    => 'cd',
        'SFRJn'   => 'ce',
        'SRe'     => 'cf',
        'SRCk'    => 'cg',
        'SSn'     => 'ch',
        'SQs'     => 'b6',
        'BRd'     => 'cq',
        'BSt'     => 'cp',
        'CDe'     => 'c6',
        'CIt'     => 'c4',
        'CISt'    => 'ca',
        'CLd'     => 'c8',
        'CRe'     => 'c7',
        'CRSt'    => 'cc',
        'CSt'     => 'c5',
        'CUe'     => 'c3',
        'CUMi'    => 'c9',
    ];

    $values = [];
    foreach ($mapping as $key => $value) {
        $values[$key] = $map[$value];
    }

    rrdtool_update_ng($device, 'mysql', $values, $app_id);

    // Process state statistics

    // Derr, not sure what the key part of the array is for, apart from some documentation, as d* is passed from agent into RRD.
    $mapping_status = [
        // Something something => RRD DS & Agent field
        'State_closing_tables'       => 'd2',
        'State_copying_to_tmp_table' => 'd3',
        'State_end'                  => 'd4',
        'State_freeing_items'        => 'd5',
        'State_init'                 => 'd6',
        'State_locked'               => 'd7',
        'State_login'                => 'd8',
        'State_preparing'            => 'd9',
        'State_reading_from_net'     => 'da',
        'State_sending_data'         => 'db',
        'State_sorting_result'       => 'dc',
        'State_statistics'           => 'dd',
        'State_updating'             => 'de',
        'State_writing_to_net'       => 'df',
        'State_none'                 => 'dg',
        'State_other'                => 'dh',
    ];

    $valuesb = [];
    foreach ($mapping_status as $key => $value) {
        $valuesb[$value] = $map[$value];
    }

    update_application($app_id, array_merge($values, $valuesb));

    rrdtool_update_ng($device, 'mysql-status', $valuesb, $app_id);
}

// EOF
