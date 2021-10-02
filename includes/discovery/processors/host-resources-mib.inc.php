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

$hrDevice_oids = array('hrDevice', 'hrProcessorLoad');
$hrDevice_array = [];
foreach ($hrDevice_oids as $oid) {
  $hrDevice_array = snmpwalk_cache_oid($device, $oid, $hrDevice_array, 'HOST-RESOURCES-MIB:HOST-RESOURCES-TYPES');
}

$hr_cpus = 0;
$hr_total = 0;

if (safe_count($hrDevice_array)) {
  foreach ($hrDevice_array as $index => $entry) {
    if (!is_numeric($entry['hrProcessorLoad'])) { continue; }

    if (!isset($entry['hrDeviceType'])) {
      $entry['hrDeviceType']  = 'hrDeviceProcessor';
      $entry['hrDeviceIndex'] = $index;
    } elseif ($entry['hrDeviceType'] === 'hrDeviceOther' &&
              preg_match('/^cpu\d+:/', $entry['hrDeviceDescr'])) {
      // Workaround bsnmpd reporting CPUs as hrDeviceOther (FY FreeBSD.)
      $entry['hrDeviceType'] = 'hrDeviceProcessor';
    }

    if ($entry['hrDeviceType'] === 'hrDeviceProcessor') {
      $hrDeviceIndex = $entry['hrDeviceIndex'];

      $usage_oid = ".1.3.6.1.2.1.25.3.3.1.2.$index";
      $usage = $entry['hrProcessorLoad'];

      // What is this for? I have forgotten. What has : in its hrDeviceDescr?
      // Set description to that found in hrDeviceDescr, first part only if containing a :
      $descr_array = explode(':',$entry['hrDeviceDescr']);
      if ($descr_array['1']) { $descr = $descr_array['1']; } else { $descr = $descr_array['0']; }

      // Workaround to set fake description for Mikrotik and other who don't populate hrDeviceDescr
      if (empty($entry['hrDeviceDescr'])) { $descr = 'Processor'; }

      $descr = rewrite_entity_name($descr);

      if ($device['os'] === 'arista_eos' && $index == 1) { continue; }

      if ($descr !== 'An electronic chip that makes the computer work.') {
        discover_processor($valid['processor'], $device, $usage_oid, $index, 'hr', $descr, 1, $usage, NULL, $hrDeviceIndex);
        $hr_cpus++;
        $hr_total += $usage;
      }
      unset($old_rrd,$new_rrd,$descr,$entry,$usage_oid,$index,$usage,$hrDeviceIndex,$descr_array);
    }
    unset($entry);
  }

  if ($hr_cpus) {
    $hr_total = $hr_total / $hr_cpus;
    discover_processor($valid['processor'], $device, 1, 1, 'hr-average', 'Average', 1, $usage);
    //$ucd_count = @dbFetchCell("SELECT COUNT(*) FROM `processors` WHERE `device_id` = ? AND `processor_type` = 'ucd-old'", array($device['device_id']));
    //if ($ucd_count)
    if (dbExist('processors', '`device_id` = ? AND `processor_type` = ?', array($device['device_id'], 'ucd-old'))) {
      $GLOBALS['module_stats']['processors']['deleted']++; //echo('-');
      dbDelete('processors', "`device_id` = ? AND `processor_type` = ?", array($device['device_id'], 'ucd-old')); // Heh, this is because UCD-SNMP-MIB run earlier
    }
  }

  unset($hrDevice_oids, $hrDevice_array, $oid);
}

// EOF
