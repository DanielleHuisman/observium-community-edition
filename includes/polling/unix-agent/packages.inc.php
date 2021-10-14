<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Init to avoid PHP warnings
$pkgs_id    = array();
$pkgs_db_id = array();

// RPM
if (!safe_empty($agent_data['rpm'])) {
  echo("\nRPM Packages: ");
  // Build array of existing packages
  $manager = "rpm";

  $pkgs_db_db = dbFetchRows("SELECT * FROM `packages` WHERE `device_id` = ?", array($device['device_id']));
  foreach ($pkgs_db_db as $pkg_db)
  {
    $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['id'] = $pkg_db['pkg_id'];
    $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['status'] = $pkg_db['status'];
    $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['size'] = $pkg_db['size'];
    $pkgs_db_id[$pkg_db['pkg_id']]['text'] = $pkg_db['manager'] ."-".$pkg_db['name']."-".$pkg_db['arch']."-".$pkg_db['version']."-".$pkg_db['build'];
    $pkgs_db_id[$pkg_db['pkg_id']]['manager'] = $pkg_db['manager'];
    $pkgs_db_id[$pkg_db['pkg_id']]['name']    = $pkg_db['name'];
    $pkgs_db_id[$pkg_db['pkg_id']]['arch']    = $pkg_db['arch'];
    $pkgs_db_id[$pkg_db['pkg_id']]['version'] = $pkg_db['version'];
    $pkgs_db_id[$pkg_db['pkg_id']]['build']   = $pkg_db['build'];
  }

  foreach (explode("\n", $agent_data['rpm']) as $package)
  {
    list($name, $pversion, $build, $arch, $size) = explode(" ", $package);
    $pkgs[$manager][$name][$arch][$pversion][$build]['manager'] = $manager;
    $pkgs[$manager][$name][$arch][$pversion][$build]['name']    = $name;
    $pkgs[$manager][$name][$arch][$pversion][$build]['arch']    = $arch;
    $pkgs[$manager][$name][$arch][$pversion][$build]['version'] = $pversion;
    $pkgs[$manager][$name][$arch][$pversion][$build]['build']   = $build;
    $pkgs[$manager][$name][$arch][$pversion][$build]['size']    = $size;
    $pkgs[$manager][$name][$arch][$pversion][$build]['status']  = '1';
    $text = $manager."-".$name."-".$arch."-".$pversion."-".$build;
    $pkgs_id[] = $pkgs[$manager][$name][$arch][$pversion][$build];
  }
}

// DPKG
if (!empty($agent_data['dpkg']))
{
  echo("\nDEB Packages: ");
  // Build array of existing packages
  $manager = "deb";

  $pkgs_db_db = dbFetchRows("SELECT * FROM `packages` WHERE `device_id` = ?", array($device['device_id']));
  foreach ($pkgs_db_db as $pkg_db)
  {
    $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['id'] = $pkg_db['pkg_id'];
    $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['status'] = $pkg_db['status'];
    $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['size'] = $pkg_db['size'];
    $pkgs_db_id[$pkg_db['pkg_id']]['text'] = $pkg_db['manager'] ."-".$pkg_db['name']."-".$pkg_db['arch']."-".$pkg_db['version']."-".$pkg_db['build'];
    $pkgs_db_id[$pkg_db['pkg_id']]['manager'] = $pkg_db['manager'];
    $pkgs_db_id[$pkg_db['pkg_id']]['name']    = $pkg_db['name'];
    $pkgs_db_id[$pkg_db['pkg_id']]['arch']    = $pkg_db['arch'];
    $pkgs_db_id[$pkg_db['pkg_id']]['version'] = $pkg_db['version'];
    $pkgs_db_id[$pkg_db['pkg_id']]['build']   = $pkg_db['build'];
  }

  foreach (explode("\n", $agent_data['dpkg']) as $package)
  {
    list($name, $pversion, $arch, $size) = explode(" ", $package);
    $build = "";
    $pkgs[$manager][$name][$arch][$pversion][$build]['manager'] = $manager;
    $pkgs[$manager][$name][$arch][$pversion][$build]['name']    = $name;
    $pkgs[$manager][$name][$arch][$pversion][$build]['arch']    = $arch;
    $pkgs[$manager][$name][$arch][$pversion][$build]['version'] = $pversion;
    $pkgs[$manager][$name][$arch][$pversion][$build]['build']   = $build;
    $pkgs[$manager][$name][$arch][$pversion][$build]['size']    = $size * 1024;
    $pkgs[$manager][$name][$arch][$pversion][$build]['status']  = '1';
    $text = $manager."-".$name."-".$arch."-".$pversion."-".$build;
    $pkgs_id[] = $pkgs[$manager][$name][$arch][$pversion][$build];
  }
}

// This is run for all "packages" and is common to RPM/DEB/etc
foreach ($pkgs_id as $pkg)
{
  $name    = $pkg['name'];
  $pversion = $pkg['version'];
  $build   = $pkg['build'];
  $arch    = $pkg['arch'];
  $size    = $pkg['size'];

  #echo(str_pad($name, 20)." ".str_pad($pversion, 10)." ".str_pad($build, 10)." ".$arch."\n");
  #echo($name." ");

  if (is_array($pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]))
  {
    // FIXME - packages_history table
    $id = $pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]['id'];
    if ($pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]['status'] != '1')
    {
      $pkg_update['status']  = '1';
    }
    if ($pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]['size'] != $size)
    {
      $pkg_update['size']  = $size;
    }
    if (!empty($pkg_update))
    {
     dbUpdate($pkg_update, 'packages', '`pkg_id` = ?', array($id));
      echo("u");
    } else {
      echo(".");
    }
    unset($pkgs_db_id[$id]);
  } else {
    if (safe_count($pkgs[$manager][$name][$arch], 1) > 10 || safe_count($pkgs_db[$manager][$name][$arch], 1) === 0) {
      dbInsert(array('device_id' => $device['device_id'], 'name' => $name, 'manager' => $manager,
                   'status' => 1, 'version' => $pversion, 'build' => $build, 'arch' => $arch, 'size' => $size), 'packages');
      if ($build != "") { $dbuild = '-' . $build; } else { $dbuild = ''; }
      echo("+".$name."-".$pversion.$dbuild."-".$arch);
      log_event('Package installed: '.$name.' ('.$arch.') version '.$pversion.$dbuild, $device, 'package');
    } elseif (safe_count($pkgs_db[$manager][$name][$arch], 1)) {
      $pkg_c = dbFetchRow("SELECT * FROM `packages` WHERE `device_id` = ? AND `manager` = ? AND `name` = ? and `arch` = ? ORDER BY version DESC, build DESC", array($device['device_id'], $manager, $name, $arch));
      if ($pkg_c['build'] != "") { $pkg_c_dbuild = '-'.$pkg_c['build']; } else { $pkg_c_dbuild = ''; }
      echo("U(".$pkg_c['name']."-".$pkg_c['version'].$pkg_c_dbuild."|".$name."-".$pversion.$dbuild.")");
      $pkg_update = array('version' => $pversion, 'build' => $build, 'status' => '1', 'size' => $size);
      dbUpdate($pkg_update, 'packages', '`pkg_id` = ?', array($pkg_c['pkg_id']));
      log_event('Package updated: '.$name.' ('.$arch.') from '.$pkg_c['version'].$pkg_c_dbuild .' to '.$pversion.$dbuild, $device, 'package');
      unset($pkgs_db_id[$pkg_c['pkg_id']]);
    }
  }
  unset($pkg_update);
}

// Packages
foreach ($pkgs_db_id as $id => $pkg)
{
  dbDelete('packages', "`pkg_id` =  ?", array($id));
  echo("-".$pkg['text']);
  log_event('Package removed: '.$pkg['name'].' '.$pkg['arch'].' '.$pkg['version']. ($pkg['build'] != '' ? "-".$pkg['build'] : ''), $device, 'package');
}

echo(PHP_EOL);

unset($pkg, $pkgs_db_id, $pkg_c, $pkgs, $pkgs_db, $pkgs_db_db);

// EOF
