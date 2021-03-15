<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

// Include all discovery modules

$include_dir = "includes/discovery/storage";
include("includes/include-dir-mib.inc.php");


foreach (get_device_mibs_permitted($device) as $mib)
{
   if (is_array($config['mibs'][$mib]['storage']))
   {
      echo("$mib ");
      foreach ($config['mibs'][$mib]['storage'] as $entry_name => $entry)
      {
         $entry['found'] = FALSE;

         // Init Precision (scale)/total/used/free
         $used  = NULL;
         $total = NULL;
         $free  = NULL;
         $perc  = NULL;
         $options = [];
         if (isset($entry['scale']) && is_numeric($entry['scale']) && $entry['scale'])
         {
            $scale = $entry['scale'];
         } else {
            $scale = 1;
         }

            /////////////////////
            // Table Discovery //
            /////////////////////

            /* FIXME. Partially changed, need rewrite to common style
            // If the type is table, walk the table!
            if ($entry['type'] == "table")
            {
               $entry['oids'][$entry_name] = $entry_name;
            }
            else
            {
               // Type is not table, so we have to walk each OID individually
               foreach (array('oid_total', 'oid_total', 'oid_free', 'oid_perc', 'oid_descr') as $oid)
               {
                  if (isset($entry[$oid]))
                  {
                     $entry['oids'][$oid] = $entry[$oid];
                  }
               }
            }
            // FIXME - cache this outside the storage array and then just array_merge it in. Descr OIDs are probably shared a lot
            // FIXME - Allow different MIBs for OIDs. Best done by prefixing with MIB and parsing it out?

            if (isset($entry['extra_oids']))
            {
               foreach ((array)$entry['extra_oids'] as $oid)
               {
                  $entry['oids'][$oid] = $oid;
               }
            }

            foreach ($entry['oids'] as $oid)
            {
               $storage_array = snmpwalk_cache_oid($device, $oid, $storage_array, $mib);
            }
            */
            $table_oids = [ 'oid_total', 'oid_used', 'oid_free', 'oid_perc', 'oid_descr',
                            'oid_scale', 'oid_unit', 'oid_extra',
                            //'oid_limit_low', 'oid_limit_low_warn', 'oid_limit_high_warn', 'oid_limit_high',
                            //'oid_limit_nominal', 'oid_limit_delta_warn', 'oid_limit_delta', 'oid_limit_scale'
            ];
            $storage_array = discover_fetch_oids($device, $mib, $entry, $table_oids);

            // FIXME - generify description generation code and just pass it template and OID array.

            $i = 1; // Used in descr as %i%
            $storage_count = count($storage_array);
            foreach ($storage_array as $index => $storage_entry)
            {
               $oid_num = $entry['oid_num'] . '.' . $index;

               // Generate storage description
               $storage_entry['i'] = $i;
               $storage_entry['index'] = $index;
               $descr = entity_descr_definition('storage', $entry, $storage_entry, $storage_count);

               // Convert strings '3.40 TB' to value
               // See QNAP NAS-MIB or HIK-DEVICE-MIB
               $unit = isset($entry['unit']) ? $entry['unit'] : NULL;

               // Fetch used, total, free and percentage values, if OIDs are defined for them
               if ($entry['oid_used'] != '')
               {
                  $used = snmp_fix_numeric($storage_entry[$entry['oid_used']], $unit);
               }
               if ($entry['oid_free'] != '')
               {
                  $free = snmp_fix_numeric($storage_entry[$entry['oid_free']], $unit);
               }
               if ($entry['oid_perc'] != '')
               {
                  $perc = snmp_fix_numeric($storage_entry[$entry['oid_perc']]);
               }

               // Prefer hardcoded total over SNMP OIDs
               if ($entry['total'] != '')
               {
                  $total = $entry['total'];
               }
               elseif ($entry['oid_total'] != '')
               {
                  $total = snmp_fix_numeric($storage_entry[$entry['oid_total']], $unit);
               }

               // Extrapolate all values from the ones we have.
               $storage = calculate_mempool_properties($scale, $used, $total, $free, $perc, $options);

               print_debug_vars(array($scale, $used, $total, $free, $perc, $options));
               print_debug_vars($storage_entry);
               print_debug_vars($storage);

               print_debug_vars(array(is_numeric($storage['used']), is_numeric($storage['total'])));

               // If we have valid used and total, discover the storage
               if (is_numeric($storage['used']) && is_numeric($storage['total']))
               {
                  discover_storage($valid['storage'], $device, $index, $entry_name, $mib, $descr, $scale, $storage['total'], $storage['used']); // FIXME storage_hc = ??
                  $entry['found'] = TRUE;
               }
               $i++;
            }
         }
      }
}














if (OBS_DEBUG && count($valid['storage'])) { print_vars($valid['storage']); }

// Remove storage which weren't redetected here
$query = 'SELECT * FROM `storage` WHERE `device_id` = ?';

foreach (dbFetchRows($query, array($device['device_id'])) as $test_storage)
{
  $storage_index = $test_storage['storage_index'];
  $storage_mib   = $test_storage['storage_mib'];
  $storage_descr = $test_storage['storage_descr'];
  print_debug($storage_index . " -> " . $storage_mib);

  if (!$valid['storage'][$storage_mib][$storage_index])
  {
    $GLOBALS['module_stats']['storage']['deleted']++; //echo('-');
    dbDelete('storage', 'storage_id = ?', array($test_storage['storage_id']));
    log_event("Storage removed: index $storage_index, mib $storage_mib, descr $storage_descr", $device, 'storage', $test_storage['storage_id']);
  }
}

$GLOBALS['module_stats'][$module]['status'] = count($valid[$module]);
if (OBS_DEBUG && $GLOBALS['module_stats'][$module]['status']) { print_vars($valid[$module]); }

// EOF
