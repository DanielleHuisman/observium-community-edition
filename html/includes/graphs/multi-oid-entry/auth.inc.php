<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// FIXME - expand $vars['data']['groups'] for auth. For now only allow for >5
// FIXME - do special handling of descriptions if all oid are identical or all devices are identical
//         remove device/oid from descr if all identical
//         arrange device/oid into aligned columns if the graph is wide enough

if (!is_array($vars['id'])) {
    $vars['id'] = [$vars['id']];
}

$is_permitted = FALSE;

$oids     = [];
$rrd_list = [];

foreach ($vars['id'] as $oid_entry_id) {

    $sql = "SELECT *";
    $sql .= " FROM  `oids_entries`";
    $sql .= " LEFT JOIN `oids` USING(`oid_id`)";
    $sql .= " LEFT JOIN `devices` USING(`device_id`)";
    $sql .= " WHERE `oid_entry_id` = ?";

    $oid = dbFetchRow($sql, [$oid_entry_id]);
    if (is_numeric($oid['device_id']) && ($auth || device_permitted($oid['device_id']))) {
        $oids[]       = $oid;
        $is_permitted = TRUE;

        $rrd_file = get_rrd_path($oid, "oid-" . $oid['oid'] . "-" . $oid['oid_type'] . ".rrd");
        if (rrd_is_file($rrd_file, TRUE)) {
            $rrd_list[] = ['filename' => $rrd_file,
                           'descr'    => $oid['hostname'] . ' ' . $oid['oid_name'],
                           'ds'       => 'value'];
        }

    } else {
        // Bail on first rejection
        $is_permitted = FALSE;
    }
}

if ($auth || $is_permitted || $_SESSION['userlevel'] >= 5) {
    $title_array   = [];
    $title_array[] = ['text' => 'Multiple OIDs'];
    $title_array[] = ['text' => safe_count($vars['id']) . ' Entries'];
    $auth          = TRUE;
}

unset($is_permitted);

// EOF