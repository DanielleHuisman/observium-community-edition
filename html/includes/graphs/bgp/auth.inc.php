<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!is_intnum($vars['id'])) {
    return;
}

$data = dbFetchRow("SELECT * FROM `bgpPeers` WHERE `bgpPeer_id` = ?", [$vars['id']]);

if (is_numeric($data['device_id']) && ($auth || device_permitted($data['device_id']))) {
    $device = device_by_id_cache($data['device_id']);

    $auth = TRUE;

    $graph_title = device_name($device, TRUE);
    $graph_title .= " :: AS" . ($config['web_show_bgp_asdot'] ? bgp_asplain_to_asdot($data['bgpPeerRemoteAs']) : $data['bgpPeerRemoteAs']);
    if (!safe_empty($data['astext'])) {
        $graph_title .= ' (' . truncate($data['astext']) . ')';
    }
}

// EOF
