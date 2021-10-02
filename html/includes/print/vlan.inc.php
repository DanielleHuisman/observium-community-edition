<?php

function get_vlans($vars)
{

  $vlans = array();

  $vls = dbFetchRows("SELECT * FROM `vlans`");
  foreach ($vls as $vlan) {
    if (safe_empty($vlan['vlan_name'])) {
      // Empty vlan name
      $vlan['vlan_name'] = 'Vlan ' . $vlan['vlan_vlan'];
    }
    $vlans[$vlan['vlan_vlan']]['names'][$vlan['vlan_name']]++;
    $vlans[$vlan['vlan_vlan']]['counts']['devices']  = 0;
    $vlans[$vlan['vlan_vlan']]['counts']['ports_tagged']    = 0;
    $vlans[$vlan['vlan_vlan']]['counts']['ports_untagged'] = 0;
  }

  $otherports = dbFetchRows("SELECT `ifVlan`, COUNT(`ifVlan`) AS `count` FROM `ports` GROUP BY `ifVlan`");
  foreach ($otherports as $otherport) {
    if ($otherport['ifVlan'] == "") {
      continue;
    }

    $vlans[$otherport['ifVlan']]['counts']['ports_untagged'] = $otherport['count'];
  }

  $dev_counts = dbFetchRows("SELECT `vlan_vlan`, COUNT(`vlan_vlan`) AS `count` FROM `vlans` GROUP BY `vlan_vlan`");
  foreach ($dev_counts as $dev_count) {
    $vlans[$dev_count['vlan_vlan']]['counts']['devices'] = $dev_count['count'];
  }

  $port_counts = dbFetchRows("SELECT `vlan`, COUNT(`vlan`) AS `count` FROM `ports_vlans` GROUP BY `vlan`");
  foreach ($port_counts as $port_count) {
    if (!isset($vlans[$port_count['vlan']])) {
      print_error("Unknown VLAN ID '" . $port_count['vlan'] . "' with " . $port_count['count'] . " ports count.");
      //print_debug("Unknown VLAN ID '".$port_count['vlan']."' with ".$port_count['count']." ports count.");
      continue;
    }
    $vlans[$port_count['vlan']]['counts']['ports_tagged'] = $port_count['count'];
  }

  $mac_counts = dbFetchRows("SELECT `vlan_id`, COUNT(DISTINCT(`mac_address`)) AS `count` FROM `vlans_fdb` GROUP BY `vlan_id`");
  foreach ($mac_counts as $mac_count) {
    if (!isset($vlans[$mac_count['vlan_id']])) {
      //print_error("Unknown VLAN ID '".$mac_count['vlan_id']."' with ".$mac_count['count']." mac count.");
      print_debug("Unknown VLAN ID '" . $mac_count['vlan_id'] . "' with " . $mac_count['count'] . " mac count.");
      continue;
    }
    $vlans[$mac_count['vlan_id']]['counts']['macs'] = $mac_count['count'];
  }

  ksort($vlans);

  return $vlans;

}