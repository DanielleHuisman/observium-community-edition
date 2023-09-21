<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/** @var array $duplicates */
//r($duplicates);

// Display devices as a list in detailed format

$header = ['state-marker' => '',
           '',
           ['hostname' => 'Hostname', 'domain' => 'Domain', 'location' => 'Location'],
           '',
           ['os' => 'Operating System', 'hardware' => 'Hardware Platform'],
           ['uptime' => 'Uptime', 'sysName' => 'sysName']];

//r($table_header);

foreach ($duplicates as $case => $entries) {
    switch ($case) {
        case 'hostname':
            $title = 'Same Hostname (' . $entries[0]['hostname'] . ')';
            break;
        case 'ip_snmp':
            $title = 'Same IP (' . $entries[0]['ip'] . ') and SNMP port (' . $entries[0]['snmp_port'] . '). Different SNMP community or auth!';
            break;
        case 'ip_snmp_v1':
            $title = 'Same IP (' . $entries[0]['ip'] . ':' . $entries[0]['snmp_port'] . ') and SNMP v1 community!';
            break;
        case 'ip_snmp_v2c':
            $title = 'Same IP (' . $entries[0]['ip'] . ':' . $entries[0]['snmp_port'] . ') and SNMP v2c community!';
            break;
        case 'ip_snmp_v3':
            $title = 'Same IP (' . $entries[0]['ip'] . ':' . $entries[0]['snmp_port'] . ') and SNMP v3 auth!';
            break;
    }
    echo generate_box_open(['title' => $title, 'icon' => count($entries), 'header-border' => TRUE]);

    echo '
  <table class="table table-hover table-striped table-condensed">
  <thead>
    <tr>
      <th class="state-marker"></th>
      <th></th>
      <th>Device / Location</th>
      <th>Hardware / Features</th>
      <th>Operating System</th>
      <th>Uptime / sysName</th>
    </tr>
  </thead>';

    //echo generate_table_header($header, $vars);

    $vars['view'] = 'basic';
    foreach ($entries as $dup) {
        if (device_permitted($dup['device_id'])) {
            print_device_row($dup, $vars, ['tab' => 'edit', 'section' => 'duplicates']);
        }
    }

    echo('
      </table>');

    echo generate_box_close();
}

// EOF