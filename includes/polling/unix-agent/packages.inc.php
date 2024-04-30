<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Init to avoid PHP warnings
$pkgs_id    = [];
$pkgs_db_id = [];

// RPM
if (!safe_empty($agent_data['rpm'])) {
    echo("\nRPM Packages: ");
    // Build array of existing packages
    $manager = "rpm";

    foreach (dbFetchRows("SELECT * FROM `packages` WHERE `device_id` = ?", [$device['device_id']]) as $pkg_db) {
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['id']     = $pkg_db['pkg_id'];
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['status'] = $pkg_db['status'];
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['size']   = $pkg_db['size'];
        $pkgs_db_id[$pkg_db['pkg_id']]['text']                                                                         = $pkg_db['manager'] . "-" . $pkg_db['name'] . "-" . $pkg_db['arch'] . "-" . $pkg_db['version'] . "-" . $pkg_db['build'];
        $pkgs_db_id[$pkg_db['pkg_id']]['manager']                                                                      = $pkg_db['manager'];
        $pkgs_db_id[$pkg_db['pkg_id']]['name']                                                                         = $pkg_db['name'];
        $pkgs_db_id[$pkg_db['pkg_id']]['arch']                                                                         = $pkg_db['arch'];
        $pkgs_db_id[$pkg_db['pkg_id']]['version']                                                                      = $pkg_db['version'];
        $pkgs_db_id[$pkg_db['pkg_id']]['build']                                                                        = $pkg_db['build'];
    }

    foreach (explode("\n", $agent_data['rpm']) as $package) {
        [$name, $pversion, $build, $arch, $size] = explode(" ", $package);
        $pkgs[$manager][$name][$arch][$pversion][$build]['manager'] = $manager;
        $pkgs[$manager][$name][$arch][$pversion][$build]['name']    = $name;
        $pkgs[$manager][$name][$arch][$pversion][$build]['arch']    = $arch;
        $pkgs[$manager][$name][$arch][$pversion][$build]['version'] = $pversion;
        $pkgs[$manager][$name][$arch][$pversion][$build]['build']   = $build;
        $pkgs[$manager][$name][$arch][$pversion][$build]['size']    = $size;
        $pkgs[$manager][$name][$arch][$pversion][$build]['status']  = '1';
        $text                                                       = $manager . "-" . $name . "-" . $arch . "-" . $pversion . "-" . $build;
        $pkgs_id[]                                                  = $pkgs[$manager][$name][$arch][$pversion][$build];
    }
}

// DPKG
if (!safe_empty($agent_data['dpkg'])) {
    echo("\nDEB Packages: ");
    // Build array of existing packages
    $manager = "deb";

    foreach (dbFetchRows("SELECT * FROM `packages` WHERE `device_id` = ?", [$device['device_id']]) as $pkg_db) {
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['id']     = $pkg_db['pkg_id'];
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['status'] = $pkg_db['status'];
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['size']   = $pkg_db['size'];
        $pkgs_db_id[$pkg_db['pkg_id']]['text']                                                                         = $pkg_db['manager'] . "-" . $pkg_db['name'] . "-" . $pkg_db['arch'] . "-" . $pkg_db['version'] . "-" . $pkg_db['build'];
        $pkgs_db_id[$pkg_db['pkg_id']]['manager']                                                                      = $pkg_db['manager'];
        $pkgs_db_id[$pkg_db['pkg_id']]['name']                                                                         = $pkg_db['name'];
        $pkgs_db_id[$pkg_db['pkg_id']]['arch']                                                                         = $pkg_db['arch'];
        $pkgs_db_id[$pkg_db['pkg_id']]['version']                                                                      = $pkg_db['version'];
        $pkgs_db_id[$pkg_db['pkg_id']]['build']                                                                        = $pkg_db['build'];
    }

    foreach (explode("\n", $agent_data['dpkg']) as $package) {
        [$name, $pversion, $arch, $size] = explode(" ", $package);
        $build                                                      = "";
        $pkgs[$manager][$name][$arch][$pversion][$build]['manager'] = $manager;
        $pkgs[$manager][$name][$arch][$pversion][$build]['name']    = $name;
        $pkgs[$manager][$name][$arch][$pversion][$build]['arch']    = $arch;
        $pkgs[$manager][$name][$arch][$pversion][$build]['version'] = $pversion;
        $pkgs[$manager][$name][$arch][$pversion][$build]['build']   = $build;
        $pkgs[$manager][$name][$arch][$pversion][$build]['size']    = (int)$size * 1024;
        $pkgs[$manager][$name][$arch][$pversion][$build]['status']  = '1';
        $text                                                       = $manager . "-" . $name . "-" . $arch . "-" . $pversion . "-" . $build;
        $pkgs_id[]                                                  = $pkgs[$manager][$name][$arch][$pversion][$build];
    }
}

// This is run for all "packages" and is common to RPM/DEB/etc
$pkg_multi_insert = [];
$pkg_multi_update = [];
foreach ($pkgs_id as $pkg) {
    $name     = $pkg['name'];
    $pversion = $pkg['version'];
    $build    = $pkg['build'];
    $arch     = $pkg['arch'];
    $size     = $pkg['size'];

    #echo(str_pad($name, 20)." ".str_pad($pversion, 10)." ".str_pad($build, 10)." ".$arch."\n");
    #echo($name." ");

    if (is_array($pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']])) {
        // FIXME - packages_history table
        $id         = $pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]['id'];
        $pkg_update = [];
        if ($pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]['status'] != '1') {
            $pkg_update['status'] = '1';
        }
        if ($pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]['size'] != $size) {
            $pkg_update['size'] = $size;
        }
        if (!empty($pkg_update)) {
            $pkg_multi_update[] = ['pkg_id' => $id, 'version' => $pkg['version'], 'build' => $pkg['build'], 'status' => '1', 'size' => $size];
            //dbUpdate($pkg_update, 'packages', '`pkg_id` = ?', array($id));
            echo("u");
        } else {
            echo(".");
        }
        unset($pkgs_db_id[$id]);
    } else {
        if (safe_count($pkgs[$manager][$name][$arch], 1) > 10 || safe_count($pkgs_db[$manager][$name][$arch], 1) === 0) {
            // dbInsert(array('device_id' => $device['device_id'], 'name' => $name, 'manager' => $manager,
            //              'status' => 1, 'version' => $pversion, 'build' => $build, 'arch' => $arch, 'size' => $size), 'packages');
            $pkg_multi_insert[] = ['device_id' => $device['device_id'], 'name' => $name, 'manager' => $manager,
                                   'status'    => 1, 'version' => $pversion, 'build' => $build, 'arch' => $arch, 'size' => $size];
            if ($build != "") {
                $dbuild = '-' . $build;
            } else {
                $dbuild = '';
            }
            echo("+" . $name . "-" . $pversion . $dbuild . "-" . $arch);
            log_event('Package installed: ' . $name . ' (' . $arch . ') version ' . $pversion . $dbuild, $device, 'package');
        } elseif (safe_count($pkgs_db[$manager][$name][$arch], 1)) {
            $pkg_c = dbFetchRow("SELECT * FROM `packages` WHERE `device_id` = ? AND `manager` = ? AND `name` = ? and `arch` = ? ORDER BY version DESC, build DESC", [$device['device_id'], $manager, $name, $arch]);
            if ($pkg_c['build'] != "") {
                $pkg_c_dbuild = '-' . $pkg_c['build'];
            } else {
                $pkg_c_dbuild = '';
            }
            echo("U(" . $pkg_c['name'] . "-" . $pkg_c['version'] . $pkg_c_dbuild . "|" . $name . "-" . $pversion . $dbuild . ")");
            $pkg_multi_update[] = ['pkg_id' => $pkg_c['pkg_id'], 'version' => $pversion, 'build' => $build, 'status' => '1', 'size' => $size];
            //dbUpdate($pkg_update, 'packages', '`pkg_id` = ?', array($pkg_c['pkg_id']));
            log_event('Package updated: ' . $name . ' (' . $arch . ') from ' . $pkg_c['version'] . $pkg_c_dbuild . ' to ' . $pversion . $dbuild, $device, 'package');
            unset($pkgs_db_id[$pkg_c['pkg_id']]);
        }
    }
    unset($pkg_update);
}

// Multi insert/update
if (!empty($pkg_multi_insert)) {
    dbInsertMulti($pkg_multi_insert, 'packages');
}
if (!empty($pkg_multi_update)) {
    dbUpdateMulti($pkg_multi_update, 'packages');
}

// Packages
if (!safe_empty($pkgs_db_id)) {
    foreach ($pkgs_db_id as $id => $pkg) {
        //dbDelete('packages', "`pkg_id` =  ?", array( $id ));
        echo("-" . $pkg['text']);
        log_event('Package removed: ' . $pkg['name'] . ' ' . $pkg['arch'] . ' ' . $pkg['version'] . ($pkg['build'] != '' ? "-" . $pkg['build'] : ''), $device, 'package');
    }
    // Multi delete
    dbDelete('packages', generate_query_values(array_keys($pkgs_db_id), 'pkg_id'));
}

echo(PHP_EOL);

unset($pkg, $pkgs_db_id, $pkg_c, $pkgs, $pkgs_db, $pkg_multi_insert, $pkg_multi_update);

// EOF
