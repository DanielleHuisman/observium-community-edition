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

// Include all discovery modules

$include_dir = "includes/discovery/mempools";
include("includes/include-dir-mib.inc.php");

// Detect mempools by simple MIB-based discovery :
// FIXME - this should also be extended to understand multiple entries in a table, and take descr from an OID but this is all I need right now :)
foreach (get_device_mibs_permitted($device) as $mib)
{
   if (is_array($config['mibs'][$mib]['mempool']))
   {
      echo("$mib ");
      foreach ($config['mibs'][$mib]['mempool'] as $entry_name => $entry)
      {
         if (discovery_check_if_type_exist($valid, $entry, 'mempool')) { continue; }

         $entry['found'] = FALSE;

         // Init Precision (scale)/total/used/free
         $used  = NULL;
         $total = NULL;
         $free  = NULL;
         $perc  = NULL;
         if (isset($entry['scale']) && is_numeric($entry['scale']) && $entry['scale'])
         {
            $scale = $entry['scale'];
         } else {
            $scale = 1;
         }

         if ($entry['type'] == 'table' || !isset($entry['type']))
         {

            /////////////////////
            // Table Discovery //
            /////////////////////

            // If the type is table, walk the table!
            if ($entry['type'] == "table")
            {
               $entry['oids'][$entry_name] = $entry_name;
               $entry['table'] = $entry_name;
            } else {
               // Type is not table, so we have to walk each OID individually
               foreach(array('oid_used', 'oid_total', 'oid_free', 'oid_perc', 'oid_descr') as $oid)
               {
                  if (isset($entry[$oid])) { $entry['oids'][$oid] = $entry[$oid]; }
               }
            }
            // FIXME - cache this outside the mempools array and then just array_merge it in. Descr OIDs are probably shared a lot

            if (isset($entry['extra_oids']))
            {
               foreach((array)$entry['extra_oids'] as $oid) { $entry['oids'][$oid] = $oid; }
            }

            // Fetch table or Oids
            $table_oids = array('oid_used', 'oid_total', 'oid_free', 'oid_perc', 'oid_descr', 'extra_oids');
            $mempools_array = discover_fetch_oids($device, $mib, $entry, $table_oids);
            // foreach ($entry['oids'] as $oid)
            // {
            //    $mempools_array = snmpwalk_cache_oid($device, $oid, $mempools_array, $mib);
            // }

            // FIXME - generify description generation code and just pass it template and OID array.

            $i = 1; // Used in descr as %i%
            $mempools_count = count($mempools_array);
            foreach ($mempools_array as $index => $mempool_entry)
            {
               $oid_num = $entry['oid_num'] . '.' . $index;

               // Generate mempool description
               $mempool_entry['i'] = $i;
               $mempool_entry['index'] = $index;
               $descr = entity_descr_definition('mempool', $entry, $mempool_entry, $mempools_count);

               // Fetch used, total, free and percentage values, if OIDs are defined for them
               if ($entry['oid_used'] != '')      { $used = snmp_fix_numeric($mempool_entry[$entry['oid_used']]); }
               if ($entry['oid_free'] != '')      { $free = snmp_fix_numeric($mempool_entry[$entry['oid_free']]); }
               if ($entry['oid_perc'] != '')      { $perc = snmp_fix_numeric($mempool_entry[$entry['oid_perc']]); }

               // Prefer hardcoded total over SNMP OIDs
               if     ($entry['total'] != '')     { $total = $entry['total']; }
               elseif ($entry['oid_total'] != '') { $total = snmp_fix_numeric($mempool_entry[$entry['oid_total']]); }

               // Extrapolate all values from the ones we have.
               $mempool = calculate_mempool_properties($scale, $used, $total, $free, $perc, $options);

               print_debug_vars(array($scale, $used, $total, $free, $perc, $options));
               print_debug_vars($mempool_entry);
               print_debug_vars($mempool);

               print_debug_vars(array(is_numeric($mempool['used']),is_numeric($mempool['total'])));

               // If we have valid used and total, discover the mempool
               if (is_numeric($mempool['used']) && is_numeric($mempool['total']))
               {
                  //print_r(array($valid['mempool'], $device, $index, $mib, $descr, $scale, $mempool['total'], $mempool['used'], $index, array('table' => $entry_name)));

                  discover_mempool($valid['mempool'], $device, $index, $mib, $descr, $scale, $mempool['total'], $mempool['used'], $index, array('table' => $entry_name)); // FIXME mempool_hc = ??
                  $entry['found'] = TRUE;
               }
               $i++;
            }

         } else {

            //////////////////
            // Static mempool //
            /////////////////

            $index = 0; // FIXME. Need use same indexes style as in sensors
            $mempool_entry = array('index' => $index);

            if (isset($entry['oid_descr']) && $entry['oid_descr'])
            {
               // Get description from specified OID
               $mempool_entry[$entry['oid_descr']] = snmp_get_oid($device, $entry['oid_descr'], $mib);
            }
            $descr = entity_descr_definition('mempool', $entry, $mempool_entry);

            // Fetch used, total, free and percentage values, if OIDs are defined for them
            if ($entry['oid_used'] != '')
            {
               $used = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_used'], $mib));
            }

            // Prefer hardcoded total over SNMP OIDs
            if ($entry['total'] != '')
            {
               $total = $entry['total'];
            } else {
               // No hardcoded total, fetch OID if defined
               if ($entry['oid_total'] != '')
               {
                  $total = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_total'], $mib));
               }
            }

            if ($entry['oid_free'] != '')
            {
               $free = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_free'], $mib));
            }

            if ($entry['oid_perc'] != '')
            {
               $perc = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_perc'], $mib));
            }

            $mempool = calculate_mempool_properties($scale, $used, $total, $free, $perc, $entry);

            // If we have valid used and total, discover the mempool
            if (is_numeric($mempool['used']) && is_numeric($mempool['total']))
            {
               // Rename RRD if requested
               if (isset($entry['rename_rrd']))
               {
                  $old_rrd = 'mempool-' . $entry['rename_rrd'];
                  $new_rrd = 'mempool-' . $mib_lower . '-' . $index;
                  rename_rrd($device, $old_rrd, $new_rrd);
                  unset($old_rrd, $new_rrd);
               }

               discover_mempool($valid['mempool'], $device, $index, $mib, $descr, $scale, $mempool['total'], $mempool['used'], $index, array('table' => $entry_name)); // FIXME mempool_hc = ??
               $entry['found'] = TRUE;
            }
         }

         unset($mempools_array, $mempool, $dot_index, $descr, $i); // Clean up
         if (isset($entry['stop_if_found']) && $entry['stop_if_found'] && $entry['found'])
         {
            break;
         } // Stop loop if mempool found
      }
   }
}

// Remove memory pools which weren't redetected here
foreach (dbFetchRows('SELECT * FROM `mempools` WHERE `device_id` = ?', array($device['device_id'])) as $test_mempool)
{
   $mempool_index = $test_mempool['mempool_index'];
   $mempool_mib   = $test_mempool['mempool_mib'];
   $mempool_descr = $test_mempool['mempool_descr'];
   print_debug($mempool_index . " -> " . $mempool_mib);

   if (!$valid['mempool'][$mempool_mib][$mempool_index])
   {
      $GLOBALS['module_stats'][$module]['deleted']++; //echo('-');
      dbDelete('mempools', '`mempool_id` = ?', array($test_mempool['mempool_id']));
      log_event("Memory pool removed: mib $mempool_mib, index $mempool_index, descr $mempool_descr", $device, 'mempool', $test_mempool['mempool_id']);
   }
}

$GLOBALS['module_stats'][$module]['status'] = count($valid['mempool']);
if (OBS_DEBUG && $GLOBALS['module_stats'][$module]['status'])
{
   print_vars($valid['mempool']);
}

// EOF
