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

$cbqos_ports = dbFetchRows("SELECT `device_id`,`policy_index`,`object_index` FROM `ports_cbqos`;");
$ds_rename   = array('PrePolicyPkt', 'PrePolicyByte', 'PostPolicyByte', 'DropPkt', 'DropByte', 'NoBufDropPkt');

if (count($cbqos_ports))
{
  echo 'Converting RRD ds names for CBQoS graphs: ';

  foreach ($cbqos_ports as $cbqos)
  {
    $device = device_by_id_cache($cbqos['device_id']);
    foreach ($ds_rename as $newname)
    {
      $oldname = $newname . '64';
      $index   = $cbqos['policy_index'] . '-' . $cbqos['object_index'];
      $status  = rrdtool_rename_ds($device, "cbqos-$index", $oldname, $newname);
      if ($newname == 'PrePolicyPkt' && $status === FALSE)
      {
        // break loop if DS already correct
        break;
      }
    }
    if ($status) { echo('.'); }
  }
}

unset($status, $cbqos_ports, $cbqos, $ds_rename);

// EOF
