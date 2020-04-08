<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$mib = 'MPLS-MIB';

$lsps = snmpwalk_cache_oid($device, "mplsLspInfoEntry", NULL, $mib, mib_dirs('juniper'));
if (OBS_DEBUG && count($lsps))
{
   print_vars($lsps);
}

if (count($lsps))
{
   foreach ($lsps as $index => $lsp)
   {
      $proto          = "RSVP-TE";
      $name           = trim($lsp['mplsLspInfoName'], "."); // name has trailing \0's that are converted to dots by snmp
      $state          = $lsp['mplsLspInfoState'];
      $uptime         = timeticks_to_sec($lsp['mplsLspInfoTimeUp']);
      $total_uptime   = timeticks_to_sec($lsp['mplsLspInfoAge']);
      $primary_uptime = timeticks_to_sec($lsp['mplsLspInfoPrimaryTimeUp']);
      $bandwidth      = $lsp['mplsPathInfoBandwidth'] * 1000; // Convert kbps to bps for standardization
      $transitions    = $lsp['mplsLspInfoTransitions'];
      $path_changes   = $lsp['mplsLspInfoPathChanges'];

      // using the 'Aggr' instances to avoid gaps on frenquently resignalled LSPs
      $octets  = $lsp['mplsLspInfoAggrOctets'];
      $packets = $lsp['mplsLspInfoAggrPackets'];
      discover_lsp($valid['lsp'], $device, $index, $mib, $name, $state, $uptime, $total_uptime,
         $primary_uptime, $proto, $transitions, $path_changes, $bandwidth, $octets, $packets, time());
   }
}

unset($lsps);

// EOF
