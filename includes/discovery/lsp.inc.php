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

// Include all discovery modules
$include_dir = "includes/discovery/lsp";
include("includes/include-dir-mib.inc.php");

if (OBS_DEBUG && count($valid['lsp']))
{
   print_vars($valid['lsp']);
}

// Remove lsps which weren't redetected here
$query = 'SELECT * FROM `lsp` WHERE `device_id` = ?';

foreach (dbFetchRows($query, array($device['device_id'])) as $test_lsp)
{
   $lsp_index = $test_lsp['lsp_index'];
   $lsp_mib   = $test_lsp['lsp_mib'];
   $lsp_name  = $test_lsp['lsp_name'];
   print_debug($lsp_index . " -> " . $lsp_mib);

   if (!$valid['lsp'][$lsp_mib][$lsp_index])
   {
      $GLOBALS['module_stats']['lsp']['deleted']++;
      dbDelete('lsp', 'lsp_id = ?', array($test_lsp['lsp_id']));
      log_event("LSP removed: index $lsp_index, mib $lsp_mib, name $lsp_name", $device, 'lsp', $test_lsp['lsp_id']);
   }
}

$GLOBALS['module_stats'][$module]['status'] = safe_count($valid[$module]);
if (OBS_DEBUG && $GLOBALS['module_stats'][$module]['status'])
{
   print_vars($valid[$module]);
}

// EOF
