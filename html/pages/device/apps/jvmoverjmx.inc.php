<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2014, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     applications
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// defines tabs
$app_sections = [
  'memory'  => 'Memory',
  'threads' => 'Threads',
  'classes' => 'Classes',
  'gc'      => 'Garbage Collector',
  'system'  => 'System Information',
];

// defines graphs in memory tab
$app_graphs['memory'] = [
  'jvmoverjmx_memory_summary' => 'Memory Summary',
  'jvmoverjmx_heap'           => 'Heap',
  'jvmoverjmx_nonheap'        => 'Non Heap',
  'jvmoverjmx_eden'           => 'Eden Space',
  'jvmoverjmx_perm'           => 'Permanent Generation',
  'jvmoverjmx_old'            => 'Old Generation',
];

// defines graphs in threads tab
$app_graphs['threads'] = [
  'jvmoverjmx_threads' => 'Threads',
];

$app_graphs['classes'] = [
  'jvmoverjmx_classes' => 'Classes',
];

$app_graphs['gc'] = [
  'jvmoverjmx_gc_young_time'  => 'GC Young Gen Collection Time',
  'jvmoverjmx_gc_old_time'    => 'GC Old Gen Collection Time',
  'jvmoverjmx_gc_young_count' => 'GC Young Collection Count',
  'jvmoverjmx_gc_old_count'   => 'GC Old Collection Count',
];

$app_graphs['system'] = [
  'jvmoverjmx_system_uptime' => 'Uptime',
];

// EOF
