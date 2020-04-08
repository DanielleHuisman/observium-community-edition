<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 * (c) 2018 Luis Balbinot <hades.himself AT gmail.com>
 *
 */

// MPLS-MIB

if (!is_array($lsps_cache))
{
   $lsps_cache = snmpwalk_cache_oid($device, "mplsLspInfoEntry", NULL, "MPLS-MIB", mib_dirs('juniper'));
   $polled     = time();
   if (OBS_DEBUG && count($lsps_cache))
   {
      print_vars($lsps_cache);
   }
}

$entry                     = $lsps_cache[$lsp['lsp_index']];
$lsp['lsp_octets']         = $entry['mplsLspInfoOctets'];
$lsp['lsp_packets']        = $entry['mplsLspInfoPackets'];
$lsp['lsp_bandwidth']      = $entry['mplsPathInfoBandwidth'] * 1000; // Convert kbps to bps for standardization
$lsp['lsp_state']          = $entry['mplsLspInfoState'];
$lsp['lsp_uptime']         = timeticks_to_sec($entry['mplsLspInfoTimeUp']);
$lsp['lsp_total_uptime']   = timeticks_to_sec($entry['mplsLspInfoAge']);
$lsp['lsp_primary_uptime'] = timeticks_to_sec($entry['mplsLspInfoPrimaryTimeUp']);
$lsp['lsp_transitions']    = $entry['mplsLspInfoTransitions'];
$lsp['lsp_path_changes']   = $entry['mplsLspInfoPathChanges'];

unset ($entry);

// EOF
