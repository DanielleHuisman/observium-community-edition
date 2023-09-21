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

$netscaler_devices = dbFetchRows("SELECT * FROM `devices` WHERE `os` = 'netscaler';");
$ds_rename = array(
  // 19chars
  'ErrRetransmitGiveUp', 'TotClientConnOpened', 'TotClientConnClosed', 'CurClientConnClosin',
  'CurServerConnEstabl', 'CurClientConnOpenin', 'CurClientConnEstabl', 'CurServerConnClosin',
  'TotServerConnOpened', 'TotServerConnClosed', 'CurServerConnOpenin',
  'TotZomCltConnFlushe', 'TotZomSvrConnFlushe', 'TotZomAcHalfCloseCl', 'TotZomAcHalfCloseSv',
  'TotZomHalfOpenCltCo', 'TotZomHalfOpenSvrCo', 'TotZomPsHalfCloseCl', 'TotZomPsHalfCloseSr',
  'ErrCookiePktSeqReje', 'ErrCookiePktSigReje', 'ErrCookiePktSeqDrop', 'ErrCookiePktMssReje',
  'ErrSynDroppedConges', 'ErrFastRetransmissi', 'ErrFirstRetransmiss', 'ErrSecondRetransmis',
  'ErrThirdRetransmiss', 'ErrForthRetransmiss', 'ErrFifthRetransmiss', 'ErrSixthRetransmiss',
  'ErrSeventhRetransmi', 'ErrPartialRetrasmit',
);

if (count($netscaler_devices))
{
  echo ' Converting RRD ds names for Netscaler TCP graphs: ';

  foreach ($netscaler_devices as $device)
  {
    foreach ($ds_rename as $newname)
    {
      $oldname = substr($newname, 0, 18);
      $status = rrdtool_rename_ds($device, 'netscaler-stats-tcp.rrd', $oldname, $newname); // rename 18chars -> 19chars
      if ($newname == 'ErrRetransmitGiveUp' && $status === FALSE)
      {
        // break loop if DS already correct
        break;
      }
    }
    if ($status) { echo('.'); }
  }
}

unset($status, $netscaler_devices, $ds_rename);

// EOF
