<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

/**
 * @var array $device
 * @var array $poll_device
 */

// Force rediscover os if os definition changed
if (!isset($config['os'][$device['os']])) {
    print_debug('OS name change detected, forced os rediscover.');
    force_discovery($device, 'os');
}

// List of variables/params parsed from sysDescr or snmp for each os
$os_metatypes = [ 'serial', 'version', 'hardware', 'type', 'vendor', 'features',
                  'asset_tag', 'ra_url_http', 'kernel', 'arch', 'distro', 'distro_ver' ];
$os_values    = [];

// Find OS-specific SNMP data via OID regex: serial number, version number, hardware description, features, asset tag
// $config['os'][$device['os']]['sysDescr_regex']
// $config['os'][$device['os']]['sysName_regex']
foreach (poll_device_sys_regex($device, $os_metatypes, $os_values, $poll_device) as $metatype => $value) {
    $$metatype = $value; // Set metatype variable
}

// Cache hardware/version/serial info from ENTITY-MIB (if possible use inventory module data)
if (isset($config['os'][$device['os']]['entPhysical']) && is_device_mib($device, 'ENTITY-MIB')) {
    $entPhysical_def = $config['os'][$device['os']]['entPhysical'];

    // Get entPhysical tables for some OS and OS groups
    if (is_module_enabled($device, 'inventory', 'discovery')) {
        $entPhysicalContainedIn = $entPhysical_def['containedin'] ?? '0';
        $entPhysical            = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalContainedIn` = ? AND `deleted` IS NULL', [$device['device_id'], $entPhysicalContainedIn]);
        if (isset($entPhysical_def['class']) && (empty($entPhysical) || $entPhysical['entPhysicalClass'] === 'stack')) {
            $entPhysical = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalClass` = ? AND `deleted` IS NULL ORDER BY `entPhysicalIndex` ASC', [$device['device_id'], $entPhysical_def['class']]);
        }
    } else {
        $oids = isset($entPhysical_def['oids']) ? (array)$entPhysical_def['oids'] : [];
        foreach ($entPhysical_def as $metatype => $def) {
            if (isset($def['oid']) && !isset($os_values[$metatype])) {
                $oids[] = $def['oid'];
                // Append extra oid(s)
                if (isset($def['oid_extra'])) {
                    $oids[] = $def['oid_extra'];
                }
            }
        }
        $data        = snmp_get_multi_oid($device, $oids, [], snmp_mib_entity_vendortype($device, 'ENTITY-MIB'));
        $entPhysical = array_shift($data);
    }
    print_debug_vars($entPhysical, 1);

    foreach ($os_metatypes as $metatype) {
        // skip undefined metatype or already found metatype
        if (!isset($entPhysical_def[$metatype]) || isset($os_values[$metatype])) {
            continue;
        }
        $entry = $entPhysical_def[$metatype];

        [ $oid, $index ] = explode('.', $entry['oid'], 2);

        // skip empty oids
        if (!isset($entPhysical[$oid]) || safe_empty($entPhysical[$oid])) {
            continue;
        }

        $value = $entPhysical[$oid];

        // Append extra value, ie lenovo-cnos
        if (isset($entry['oid_extra'])) {
            $oid_extra = explode('.', $entry['oid_extra'], 2)[0];
            if ($entPhysical[$oid_extra] != $value && !safe_empty($entPhysical[$oid_extra])) {
                $value .= ' (' . $entPhysical[$oid_extra] . ')';
            }
        }

        // Field found (no SNMP error), perform optional transformations.
        if (isset($entry['transform'])) {
            // Just simplify definition entry (unify with others)
            $entry['transformations'] = $entry['transform'];
        }

        if (isset($entry['transformations'])) {
            $value = string_transform($value, $entry['transformations']);
        } elseif ($oid === 'entPhysicalDescr') {
            $value = rewrite_entity_name($value);
        }

        // finally set os value
        if (is_valid_param($value, $metatype)) {
            $os_values[$metatype] = $value;

            $$metatype = $os_values[$metatype]; // Set metatype variable
            print_debug("Added OS param from ENTITY-MIB: '$metatype' = '" . $os_values[$metatype] . "'");
        }
    }
}

// Find MIB-specific SNMP data via OID fetch: serial number, version number, hardware description, features, asset tag
foreach (poll_device_mib_metatypes($device, $os_metatypes, $os_values) as $metatype => $value) {
    $$metatype = $value; // Set metatype variable
}

print_debug_vars($os_values, 1);

// Include OS-specific poller code, if available
if (is_file($config['install_dir'] . "/includes/polling/os/" . $device['os'] . ".inc.php")) {
    print_cli_data("OS Poller", 'OS', 2);
    // OS Specific
    include($config['install_dir'] . "/includes/polling/os/" . $device['os'] . ".inc.php");
} elseif ($device['os_group'] && is_file($config['install_dir'] . "/includes/polling/os/" . $device['os_group'] . ".inc.php")) {
    // OS Group-specific code as fallback, if OS-specific code does not exist
    print_cli_data("OS Poller", 'Group', 2);

    include($config['install_dir'] . "/includes/polling/os/" . $device['os_group'] . ".inc.php");
} else {
    print_cli_data("OS Poller", '%rGeneric%w', 2);
}

// Now recheck and set empty os params from os/group by regex/definitions values
foreach ($os_values as $metatype => $value) {
    if (!isset($$metatype) || $$metatype == '' || str_istarts($$metatype, 'generic')) {
        print_debug("Re-added OS param from sysDescr parse or SNMP definition walk: '$metatype' = '$value'");
        $$metatype = $value;
    }
}

// Set hardware by model definition if it's exist
if (empty($hardware) && $hw = get_model_param($device, 'hardware', $poll_device['sysObjectID'])) {
    $hardware = $hw;
}

// Unified vendor name
if ($vendor) {
    // Vendor fetched from sysDescr, Oid or os poller
    $vendor = rewrite_vendor($vendor);
} elseif ($vn = get_model_param($device, 'vendor', $poll_device['sysObjectID'])) {
    // Model specific
    $vendor = rewrite_vendor($vn);
} elseif (isset($config['os'][$device['os']]['vendor'])) {
    // Use os defined vendor name
    $vendor = rewrite_vendor($config['os'][$device['os']]['vendor']);
}

// If vendor and hardware not empty, remove excessive vendor part from beginning of hardware
// I.e.: "Xerox Phaser 8560" -> "Phaser 8560"
if ($vendor && $hardware && preg_match('/^' . preg_quote($vendor, '/') . '/i', $hardware)) {
    $hardware = preg_replace('/^' . preg_quote($vendor, '/') . '\s*/i', '', $hardware);
}

// Fields notified in event log
$update_fields = ['version', 'features', 'hardware', 'vendor', 'serial', 'kernel', 'distro', 'distro_ver', 'arch', 'asset_tag'];

// Log changed variables
foreach ($update_fields as $field) {
    if (isset($$field)) {
        $$field = snmp_fix_string($$field);
    } // Fix unprintable chars

    if ((isset($$field) || strlen($device[$field])) && $$field != $device[$field]) {
        $update_array[$field] = $$field;
        //log_event(nicecase($field)." changed: '".$device[$field]."' -> '".$update_array[$field]."'", $device, 'device', $device['device_id']);
    }
}

// Here additional fields, change only if not set already
$update_fields[] = 'type';
if (isset($attribs['override_type'])) {
    // already set by web device config
    $type = $device['type'];
} elseif (isset($type, $config['devicetypes'][$type])) {
    if ($type != $device['type']) {
        $update_array['type'] = $type;
    }
} elseif (isset($config['os'][$device['os']]['type'])) {
    $type = $config['os'][$device['os']]['type'];
    if ($config['os'][$device['os']]['type'] != $device['type']) {
        $update_array['type'] = $type;
    }
} else {
    // default unknown
    $type = 'unknown';
    if ($type != $device['type']) {
        $update_array['type'] = $type;
    }
}

$update_fields[] = 'icon';
if (isset($attribs['override_icon'])) {
    // already set by web device config
    $icon = $device['icon'];
} elseif (isset($icon)) {
    if ($icon != $device['icon']) {
        $update_array['icon'] = $icon;
    }
} elseif (isset($config['os'][$device['os']]['icon'])) {
    $icon = $config['os'][$device['os']]['icon'];
    if ($device['icon'] && $config['os'][$device['os']]['icon'] != $device['icon']) {
        $update_array['icon'] = $icon;
    }
} else {
    // default empty
    $icon = '';
    if ($icon != $device['icon']) {
        $update_array['icon'] = $icon;
    }
}

// Log changed variables
foreach ($update_fields as $field) {
    if ($update_array[$field]) {
        log_event(nicecase($field) . " changed: '" . $device[$field] . "' -> '" . $update_array[$field] . "'", $device, 'device', $device['device_id']);
    }
}

// Extra metrics for @previous compare
$alert_metrics['device_version']    = $version;
$alert_metrics['device_distro_ver'] = $distro_ver;

print_cli_data("Type", ($config['devicetypes'][$type]['text'] ?: "%b<empty>%n"));
print_cli_data("Vendor", ($vendor ?: "%b<empty>%n"));
print_cli_data("Hardware", ($hardware ?: "%b<empty>%n"));
print_cli_data("Version", ($version ?: "%b<empty>%n"));
print_cli_data("Features", ($features ?: "%b<empty>%n"));
print_cli_data("Serial", ($serial ?: "%b<empty>%n"));
print_cli_data("Asset", ($asset_tag ?: "%b<empty>%n"));

// Remote access URLs are stored as a device attribute instead of a database field
foreach (['ra_url_http'] as $ra_url) {
    if (isset($$ra_url)) {
        set_dev_attrib($device, $ra_url, $$ra_url);
        print_cli_data("Management URL", $ra_url_http);
    } elseif (isset($attribs[$ra_url])) {
        del_dev_attrib($device, $ra_url);
    }
}

/*
foreach (array('type', 'icon') as $field)
{
  if (!isset($$field) || isset($attribs['override_'.$field]) || $$field == $device[$field]) { continue; }

  if ($device[$field] == "unknown" || empty($device[$field]) || $field == 'icon')
  {
    $update_array[$field] = $$field;
    log_event(nicecase($field)." changed: '".$device[$field]."' -> '".$update_array[$field]."'", $device, 'device', $device['device_id']);
  }
}
*/

echo(PHP_EOL);
foreach ($os_additional_info as $header => $entries) {
    print_cli_heading($header, 3);
    foreach ($entries as $field => $entry) {
        print_cli_data($field, $entry, 3);
    }
    echo(PHP_EOL);
}

unset($entPhysical, $oids, $hw, $os_additional_info, $entry);

// EOF
