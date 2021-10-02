<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

/**
 * @var $config
 * @var $device
 * @var $permit_tabs
 */

if (!isset($vars['view']) ) { $vars['view'] = "graphs"; }

if ($permit_tabs['ports']) {
  $sql  = "SELECT *, `ports`.`port_id` AS `port_id`";
  $sql .= " FROM  `ports`";
  //$sql .= " LEFT JOIN  `ports-state` USING(`port_id`)";
  $sql .= " WHERE `ports`.`port_id` = ?";

  $port = dbFetchRow($sql, array($vars['port']));

  $port_attribs = get_entity_attribs('port', $port['port_id']);

  $port_details = 1;

  if ($port['ifPhysAddress']) { $mac = (string)$port['ifPhysAddress']; }

  $color = "black";
  if      ($port['ifAdminStatus'] === "down") {
    $status = "<span class='grey'>Disabled</span>";
  } elseif ($port['ifAdminStatus'] === "up") {
    if ($port['ifOperStatus'] === "down" ||
        $port['ifOperStatus'] === "lowerLayerDown") { $status = "<span class='red'>Enabled / Disconnected</span>"; }
    else                                            { $status = "<span class='green'>Enabled / Connected</span>"; }
  }

  $i = 1;
  $show_all = 1;

  echo generate_box_open();
  echo '<table class="table table-hover table-striped table-condensed">';
  print_port_row($port, array_merge($vars, array('view' => 'details')));
  echo '</table>';
  echo generate_box_close();

  // Start Navbar

  $link_array = array('page'    => 'device',
                      'device'  => $device['device_id'],
                      'tab'     => 'port',
                      'port'    => $port['port_id']);

  $navbar['options']['graphs']['text']    = 'Graphs';
  $navbar['options']['realtime']['text']  = 'Real time';   // FIXME CONDITIONAL

  //if (dbFetchCell("SELECT COUNT(*) FROM `sensors` WHERE `measured_class` = 'port' AND `measured_entity` = ? and `device_id` = ?", array($port['port_id'], $device['device_id'])))
  if (dbExist('sensors', '`measured_class` = ? AND `measured_entity` = ? and `device_id` = ?', array('port', $port['port_id'], $device['device_id']))) {
    $navbar['options']['sensors']['text'] = 'Sensors';
  }

  //if (dbFetchCell('SELECT COUNT(*) FROM `ip_mac` WHERE `port_id` = ?', array($port['port_id'])))
  if (dbExist('ip_mac', '`port_id` = ?', array($port['port_id'])))
  {
    $navbar['options']['arp']['text']     = 'ARP/NDP Table';
  }

  //if (dbFetchCell("SELECT COUNT(*) FROM `vlans_fdb` WHERE `port_id` = ?", array($port['port_id'])))
  if (dbExist('vlans_fdb', '`port_id` = ?', array($port['port_id'])))
  {
    $navbar['options']['fdb']['text']    = 'FDB Table';
  }

  //if (dbFetchCell("SELECT COUNT(*) FROM `ports_cbqos` WHERE `port_id` = ?", array($port['port_id'])))
  if (dbExist('ports_cbqos', '`port_id` = ?', array($port['port_id'])))
  {
    $navbar['options']['cbqos']['text']    = 'CBQoS';
  }

  if (isset($port_attribs['jnx_cos_queues']))
  {
    $navbar['options']['jnx_cos_queues']['text']    = 'CoS Queues';
  }

  if (isset($port_attribs['sros_egress_queues']) || isset($port_attribs['sros_ingress_queues']))
  {
    $navbar['options']['sros_queues']['text']    = 'CoS Queues';
  }

  $navbar['options']['alerts']['text']    = 'Alerts';
  $navbar['options']['alertlog']['text']  = 'Alert Log';

  $navbar['options']['events']['text']   = 'Eventlog';

  //if (dbFetchCell("SELECT COUNT(*) FROM `ports_adsl` WHERE `port_id` = ?", array($port['port_id'])))
  if (dbExist('ports_adsl', '`port_id` = ?', array($port['port_id'])))
  {
    $navbar['options']['adsl']['text'] = 'ADSL';
  }

  /* PAGP removed
  if (dbFetchCell('SELECT COUNT(*) FROM `ports` WHERE `pagpGroupIfIndex` = ? and `device_id` = ?', array($port['ifIndex'], $device['device_id'])))
  {
    $navbar['options']['pagp']['text'] = 'PAgP';
  }
  */

  //if (dbFetchCell('SELECT COUNT(*) FROM `ports_vlans` WHERE `port_id` = ? and `device_id` = ?', array($port['port_id'], $device['device_id'])))
  if (dbExist('ports_vlans', '`port_id` = ? and `device_id` = ?', array($port['port_id'], $device['device_id'])))
  {
    $navbar['options']['vlans']['text'] = 'VLANs';
  }

  if (dbFetchCell('SELECT count(*) FROM mac_accounting WHERE port_id = ?', array($port['port_id'])) > '0')
  {
    $navbar['options']['macaccounting']['text'] = 'MAC Accounting';
  }

  //if (dbFetchCell('SELECT COUNT(*) FROM juniAtmVp WHERE port_id = ?', array($port['port_id'])) > '0')
  if (dbExist('juniAtmVp', '`port_id` = ?', array($port['port_id'])))
  {

    // FIXME ATM VPs
    // FIXME URLs BROKEN

    $navbar['options']['atm-vp']['text'] = 'ATM VPs';

    $graphs = array('bits', 'packets', 'cells', 'errors');
    foreach ($graphs as $type)
    {
      if ($vars['view'] === "atm-vp" && $vars['graph'] == $type) { $navbar['options']['atm-vp']['suboptions'][$type]['class'] = "active"; }
      $navbar['options']['atm-vp']['suboptions'][$type]['text'] = ucfirst($type);
      $navbar['options']['atm-vp']['suboptions'][$type]['url']  = generate_url($link_array,array('view'=>'atm-vc','graph'=>$type));
    }
  }

  if (OBSERVIUM_EDITION !== 'community' && $_SESSION['userlevel'] == '10' && $config['enable_billing']) {
    $navbar['options_right']['bills'] = array('text' => 'Create Bill', 'icon' => $config['icon']['billing'], 'url' => generate_url(array('page' => 'bills', 'view' => 'add', 'port' => $port['port_id'])));
  }

  if ($_SESSION['userlevel'] == '10' )
  {
    $navbar['options_right']['data']['text'] = 'Data';
    $navbar['options_right']['data']['icon'] = $config['icon']['config'];
    $navbar['options_right']['data']['url'] = generate_url($link_array,array('view'=>'data'));
  }

  foreach ($navbar['options'] as $option => $array)
  {
    if ($vars['view'] == $option) { $navbar['options'][$option]['class'] .= " active"; }
    $navbar['options'][$option]['url'] = generate_url($link_array,array('view'=>$option));
  }

  $navbar['class'] = "navbar-narrow";
  $navbar['brand'] = "Port";

  print_navbar($navbar);
  unset($navbar);

  include($config['html_dir'] . '/pages/device/port/'.$vars['view'].'.inc.php');
} else {
  print_error('<h3>Invalid device/port combination</h3>
               The port/device combination was invalid. Please retype and try again.');
}

// EOF
