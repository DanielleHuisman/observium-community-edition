<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// These are all fixed index processors, converted from script to definition. They need renaming due to this.

foreach (dbFetchRows("SELECT * FROM `processors` LEFT JOIN `devices` USING(`device_id`)") as $entry)
{
  switch ($entry['processor_type'])
  {
    case 'powerconnect':
      rename_rrd($entry, 'processor-powerconnect-0.rrd', 'processor-agentSwitchCpuProcessTotalUtilization-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'fortigate-fixed':
      rename_rrd($entry, 'processor-fortigate-fixed-0.rrd', 'processor-fgSysCpuUsage-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'asyncos-cpu':
      rename_rrd($entry, 'processor-asyncos-cpu-0.rrd', 'processor-perCentCPUUtilization-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'adtran-aoscpu':
      rename_rrd($entry, 'processor-adtran-aoscpu-0.rrd', 'processor-adGenAOS5MinCpuUtil-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case '':
      rename_rrd($entry, 'processor--0.rrd', 'processor--0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'dlink-fixed':
      rename_rrd($entry, 'processor-dlink-fixed-0.rrd', 'processor-agentCPUutilizationIn5min-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'acme':
      rename_rrd($entry, 'processor-acme-0.rrd', 'processor-apSysCPUUtil-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'at-sysinfo-mib':
      rename_rrd($entry, 'processor-at-sysinfo-mib-0.rrd', 'processor-cpuUtilisationAvgLast5Minutes-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'ubiquiti-fixed':
      rename_rrd($entry, 'processor-ubiquiti-fixed-0.rrd', 'processor-loadValue-0.rrd'); // Should be 2 but code currently does not support it
      force_discovery($entry);
      echo('.');
      break;
    case 'procurve-fixed':
      rename_rrd($entry, 'processor-procurve-fixed-0.rrd', 'processor-hpSwitchCpuStat-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'firebox-fixed':
      rename_rrd($entry, 'processor-firebox-fixed-0.rrd', 'processor-wgSystemCpuUtil5-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'radlan':
      rename_rrd($entry, 'processor-radlan-0.rrd', 'processor-rlCpuUtilDuringLast5Minutes-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'AIRESPACE-SWITCHING-MIB':
      rename_rrd($entry, 'processor-AIRESPACE-SWITCHING-MIB-0.rrd', 'processor-agentCurrentCPUUtilization-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'seos':
      rename_rrd($entry, 'processor-seos-0.rrd', 'processor-rbnCpuMeterFiveMinuteAvg-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'cpu':
      // Sigh, dumb name, needs extra check
      if ($entry['os'] == 'trapeze')
      {
        rename_rrd($entry, 'processor-cpu-0.rrd', 'processor-trpzSysCpuLastMinuteLoad-0.rrd');
        echo('.');
      }
      force_discovery($entry);
      break;
    case 'peakflow-sp-mib':
      rename_rrd($entry, 'processor-peakflow-sp-mib-0.rrd', 'processor-deviceCpuLoadAvg5min-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'sonicwall-firewall-ip-statistics-mib':
      rename_rrd($entry, 'processor-sonicwall-firewall-ip-statistics-mib-0.rrd', 'processor-sonicCurrentCPUUtil-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'nos':
      rename_rrd($entry, 'processor-nos-0.rrd', 'processor-swCpuUsage-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'timetra-system-mib':
      rename_rrd($entry, 'processor-timetra-system-mib-0.rrd', 'processor-sgiCpuUsage-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'screenos':
      rename_rrd($entry, 'processor-screenos-0.rrd', 'processor-nsResCpuLast5Min-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'juniperive':
      rename_rrd($entry, 'processor-juniperive-0.rrd', 'processor-iveCpuUtil-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
    case 'gbnplatformoam-mib_cpuidle':
      rename_rrd($entry, 'processor-gbnplatformoam-mib_cpuidle-0.rrd', 'processor-cpuIdle-0.rrd');
      force_discovery($entry);
      echo('.');
      break;
  }
}

// EOF
