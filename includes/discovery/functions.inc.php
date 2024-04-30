<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

function autodiscovery_device($hostname, $remote_ip = NULL, $protocol = NULL, $remote_platform = '', $device = NULL, $port = [])
{
    global $config;

    // We really have too small cases, where need use $source,
    // $protocol can used for detect it
    // FIXME. Make function get_protocol_source($protocol)
    switch (strtolower($protocol)) {
        case 'bgp':
        case 'ospf':
        case 'libvirt':
        case 'proxmox':
        case 'vmware':
            $source = strtolower($protocol);
            break;

        case 'cdp':
        case 'lldp':
        case 'isdp':
        case 'mndp':
        case 'amap':
        default:
            $source = 'xdp';
    }
    if (!$protocol) {
        $protocol = strtoupper($source);
    }

    // Check if source is enabled for autodiscovery
    if (!$config['autodiscovery'][$source]) {
        print_debug("Autodiscovery for protocol $protocol ($source) disabled.");
        return FALSE;
    }

    // All sources except XDP passed IP address instead hostname
    $orig_hostname = $hostname;
    $ip_key        = safe_ip_hostname_key($hostname, $remote_ip);

    $flags = OBS_DNS_ALL;

    print_cli_data("Trying to discover host", "$hostname ($remote_ip) through $protocol ($source)", 3);

    if ($source === 'xdp' && is_bad_xdp($hostname, $remote_platform)) {
        $insert = [
            //'poller_id'        => $config['poller_id'],
            'device_id' => $device['device_id'],
            //'remote_hostname'  => $hostname,
            'remote_ip' => $remote_ip,
            //'remote_device_id' => NULL,
            'protocol'  => $protocol,
            //'last_reason'      => 'no_dns' // 'ok','no_xdp','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'
        ];
        set_autodiscovery($hostname, 'no_xdp', $insert);
        return FALSE;
    }

    // By first detect hostname is IP or domain name (IPv4/6 == 4/6, hostname == FALSE)
    $ip_version = get_ip_version($hostname);
    if ($ip_version) {
        // Hostname is IPv4/IPv6
        $use_ip   = TRUE;
        $hostname = ip_compress($hostname);
        $ip       = $hostname;
    } else {
        $use_ip = FALSE;

        $remote_ip_type = get_ip_type($remote_ip);
        if ($remote_ip_type === 'unspecified' && is_valid_hostname($hostname, TRUE) &&
            $ip_dns = gethostbyname6($hostname, $flags)) {
            // When discovery protocol return valid FQDN hostname but invalid IP (0.0.0.0), try use dns IP
            // See: https://jira.observium.org/browse/OBS-4117
            $remote_ip      = $ip_dns;
            $remote_ip_type = get_ip_type($remote_ip);
        }
        if ($remote_ip_type && $remote_ip_type === 'unspecified') { // 0.0.0.0, ::
            // In case when passed valid hostname and invalid IP, do not autodiscovery anyway
            print_debug("$hostname passed with invalid IP ($remote_ip), not permitted for autodiscovery.");
            $insert = [
                //'poller_id'        => $config['poller_id'],
                'device_id' => $device['device_id'],
                //'remote_hostname'  => $hostname,
                'remote_ip' => $remote_ip,
                //'remote_device_id' => NULL,
                'protocol'  => $protocol,
                //'last_reason'      => 'no_dns'
            ];
            set_autodiscovery($hostname, 'no_ip_permit', $insert);
            return FALSE;
        }

        // Add "mydomain" configuration if this resolves, converts switch1 -> switch1.mydomain.com
        if (!empty($config['mydomain']) && is_domain_resolves($hostname . '.' . $config['mydomain'], $flags)) {
            $hostname .= '.' . $config['mydomain'];
        }

        // Determine v4 vs v6
        $ip = gethostbyname6($hostname, $flags);
        if ($ip) {
            // DNS correct, but not same as discovered by protocol
            if ($remote_ip_type && $remote_ip != $ip) {
                if (str_contains($ip, ':') || !match_network($ip, $config['autodiscovery']['ip_nets'])) {
                    // Force autodiscovery by IP only if hostname resolved to IPv6
                    print_debug("Host $hostname resolved as $ip, but not same as discovered by protocol $remote_ip. Try autodiscover by IP.");
                    $use_ip = TRUE;
                    $ip     = $remote_ip;
                } else {
                    // NOTE. Possible case when remote hostname resolved to loopback interface,
                    // but remote protocol reports IP from physical interface.. Currently just notify this case
                    print_debug("Host $hostname resolved as $ip, but not same as discovered by protocol $remote_ip.");
                }
            } else {
                print_debug("Host $hostname resolved as $ip");
            }
        } elseif ($remote_ip_type) {
            // No DNS records
            print_debug("Host $hostname not resolved, try autodiscover by IP.");
            $use_ip = TRUE;
            $ip     = $remote_ip;
        } else {
            print_debug("Host $hostname not resolved, autodiscovery fails.");
            $insert = [
                //'poller_id'        => $config['poller_id'],
                'device_id' => $device['device_id'],
                //'remote_hostname'  => $hostname,
                //'remote_ip'        => $ip,
                //'remote_device_id' => NULL,
                'protocol'  => $protocol,
                //'last_reason'      => 'no_dns'
            ];
            set_autodiscovery($hostname, 'no_dns', $insert);
            return FALSE;
        }
    }

    $ip_version = get_ip_version($ip);
    if (isset($config['autodiscovery']['ignore_ip_types']) &&
        !in_array(get_ip_type($ip), $config['autodiscovery']['ignore_ip_types'])) {
        print_debug("IP $ip ($hostname) not permitted inside \$config['autodiscovery']['ignore_ip_types'] in config.");
        $insert = [
            //'poller_id'        => $config['poller_id'],
            'device_id' => $device['device_id'],
            //'remote_hostname'  => $hostname,
            'remote_ip' => $ip,
            //'remote_device_id' => NULL,
            'protocol'  => $protocol,
            //'last_reason'      => 'no_dns'
        ];
        set_autodiscovery($hostname, 'no_ip_permit', $insert);
        return FALSE;
    }
    if (!match_network($ip, $config['autodiscovery']['ip_nets'])) {
        print_debug("IP $ip ($hostname) not permitted inside \$config['autodiscovery']['ip_nets'] in config.");
        $insert = [
            //'poller_id'        => $config['poller_id'],
            'device_id' => $device['device_id'],
            //'remote_hostname'  => $hostname,
            'remote_ip' => $ip,
            //'remote_device_id' => NULL,
            'protocol'  => $protocol,
            //'last_reason'      => 'no_dns'
        ];
        set_autodiscovery($hostname, 'no_ip_permit', $insert);
        return FALSE;
    }

    if ($ip_version == 6) {
        $flags ^= OBS_DNS_A; // Exclude IPv4
    }

    print_debug("Host $hostname ($ip) founded inside configured nets, trying to add:");

    // By first check if pingable
    if (isset($config['autodiscovery']['ping_skip']) && $config['autodiscovery']['ping_skip']) {
        $flags |= OBS_PING_SKIP; // Add skip pings flag
    }
    $pingable = is_pingable($ip, $flags);
    if (!$pingable) {
        print_debug("Host $hostname not pingable. You can try set in config: \$config['autodiscovery']['ping_skip'] = TRUE;");
        $insert = [
            //'poller_id'        => $config['poller_id'],
            'device_id' => $device['device_id'],
            //'remote_hostname'  => $hostname,
            'remote_ip' => $ip,
            //'remote_device_id' => NULL,
            'protocol'  => $protocol,
            //'last_reason'      => 'no_dns'
        ];
        set_autodiscovery($hostname, 'no_ping', $insert);
        return FALSE;
    }

    // Check if device duplicated by IP
    $ip_hex = binary_to_hex(inet_pton($ip));
    $db     = dbFetchRow('SELECT `hostname` FROM `ipv' . $ip_version . '_addresses`
                   LEFT JOIN `devices` USING(`device_id`)
                   WHERE `disabled` = 0 AND `ipv' . $ip_version . '_binary` = ? LIMIT 1', [[$ip_hex]]);
    if ($db) {
        print_debug('Already have device ' . $db['hostname'] . " with IP $ip");
        $insert = [
            //'poller_id'        => $config['poller_id'],
            'device_id' => $device['device_id'],
            //'remote_hostname'  => $hostname,
            'remote_ip' => $ip,
            //'remote_device_id' => NULL,
            'protocol'  => $protocol,
            //'last_reason'      => 'no_dns' // 'ok','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'
        ];
        set_autodiscovery($hostname, 'duplicated', $insert);
        return FALSE;
    }

    // Detect snmp transport, net-snmp needs udp6 for ipv6
    $snmp_transport = ($ip_version == 4 ? 'udp' : 'udp6');
    $snmp_port      = 161;

    // Detect snmp auth
    $new_device = detect_device_snmpauth($ip, $snmp_port, $snmp_transport);
    if (!$new_device) {
        print_debug("Host $hostname not snmpable by known snmp auth params.");
        $insert = [
            //'poller_id'        => $config['poller_id'],
            'device_id' => $device['device_id'],
            //'remote_hostname'  => $hostname,
            'remote_ip' => $ip,
            //'remote_device_id' => NULL,
            'protocol'  => $protocol,
            //'last_reason'      => 'no_dns' // 'ok','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'
        ];
        set_autodiscovery($hostname, 'no_snmp', $insert);
        return FALSE;
    }

    if ($use_ip) {
        $check_hostname = FALSE; // When hostname detected by sysName or by PTR, recheck that is allowed by config
        // Detect FQDN hostname
        // by sysName
        $snmphost = snmp_get_oid($new_device, 'sysName.0', 'SNMPv2-MIB');
        if ($snmphost) {
            // Add "mydomain" configuration if this resolves, converts switch1 -> switch1.mydomain.com
            if (!empty($config['mydomain']) && is_domain_resolves($snmphost . '.' . $config['mydomain'], $flags)) {
                $snmphost .= '.' . $config['mydomain'];
            }
            $snmp_ip = gethostbyname6($snmphost, $flags);
        }

        if ($snmp_ip == $ip) {
            $hostname       = $snmphost;
            $check_hostname = TRUE;
        } else {
            // by PTR
            $ptr = gethostbyaddr6($ip);
            if ($ptr) {
                // Add "mydomain" configuration if this resolves, converts switch1 -> switch1.mydomain.com
                if (!empty($config['mydomain']) && is_domain_resolves($ptr . '.' . $config['mydomain'], $flags)) {
                    $ptr .= '.' . $config['mydomain'];
                }
                $ptr_ip = gethostbyname6($ptr, $flags);
            }

            if ($ptr && $ptr_ip == $ip) {
                $hostname       = $ptr;
                $check_hostname = TRUE;
            } elseif ($config['autodiscovery']['require_hostname']) {
                print_debug("Device IP $ip does not seem to have FQDN.");
                $insert = [
                    //'poller_id'        => $config['poller_id'],
                    'device_id' => $device['device_id'],
                    //'remote_hostname'  => $hostname,
                    'remote_ip' => $ip,
                    //'remote_device_id' => NULL,
                    'protocol'  => $protocol,
                    //'last_reason'      => 'no_dns' // 'ok','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'
                ];
                set_autodiscovery($hostname, 'no_fqdn', $insert);
                return FALSE;
            } else {
                // Hostname as IP string
                $hostname = ip_compress($ip); // Always use compressed IPv6 name
            }
        }
        print_debug("Device IP $ip linked to FQDN name: $hostname");

        // When hostname detected by sysName or by PTR, recheck that is allowed by config (only hostnames)
        if ($check_hostname && is_bad_xdp($hostname)) {
            $insert = [
                //'poller_id'        => $config['poller_id'],
                'device_id' => $device['device_id'],
                //'remote_hostname'  => $hostname,
                'remote_ip' => $remote_ip,
                //'remote_device_id' => NULL,
                'protocol'  => $protocol,
                //'last_reason'      => 'no_dns' // 'ok','no_xdp','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'
            ];
            set_autodiscovery($hostname, 'no_xdp', $insert);
            return FALSE;
        }
    }

    $new_device['hostname'] = $hostname;
    // Check if we already have same device
    if (check_device_duplicated($new_device)) {
        // When detected duplicate device, this mean it already SNMPable and not need check next auth!
        print_debug("Already have device $hostname with IP $ip");
        $insert = [
            //'poller_id'        => $config['poller_id'],
            'device_id' => $device['device_id'],
            //'remote_hostname'  => $hostname,
            'remote_ip' => $ip,
            //'remote_device_id' => NULL,
            'protocol'  => $protocol,
            //'last_reason'      => 'no_dns' // 'ok','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'
        ];
        set_autodiscovery($hostname, 'duplicated', $insert);
        return FALSE;
    }

    // Add new device to db
    $snmp = [
      'community' => $new_device['snmp_community'],
      'version'   => $new_device['snmp_version'],
      'port'      => $new_device['snmp_port'],
      'transport' => $new_device['snmp_transport']
    ];
    if ($new_device['snmp_version'] === 'v3') {
        $snmp['snmp_authlevel']  = $new_device['snmp_authlevel'];
        $snmp['snmp_authname']   = $new_device['snmp_authname'];
        $snmp['snmp_authpass']   = $new_device['snmp_authpass'];
        $snmp['snmp_authalgo']   = $new_device['snmp_authalgo'];
        $snmp['snmp_cryptopass'] = $new_device['snmp_cryptopass'];
        $snmp['snmp_cryptoalgo'] = $new_device['snmp_cryptoalgo'];
    }
    //$remote_device_id = createHost($new_device['hostname'], $new_device['snmp_community'], $new_device['snmp_version'], $new_device['snmp_port'], $new_device['snmp_transport'], $snmp);
    $remote_device_id = create_device($new_device['hostname'], $snmp);

    if ($remote_device_id) {
        if (is_flag_set(OBS_PING_SKIP, $flags)) {
            set_entity_attrib('device', $remote_device_id, 'ping_skip', 1);
        }
        //$remote_device = device_by_id_cache($remote_device_id, 1);

        if ($port) {
            humanize_port($port);
            log_event("Device autodiscovered through $protocol on " . $device['hostname'] . " (port " . $port['port_label'] . ")", $remote_device_id, 'port', $port['port_id']);
        } else {
            log_event("Device autodiscovered through $protocol on " . $device['hostname'], $remote_device_id, $protocol);
        }

        $insert = [
            //'poller_id'        => $config['poller_id'],
            'device_id'        => $device['device_id'],
            //'remote_hostname'  => $hostname,
            'remote_ip'        => $ip,
            'remote_device_id' => $remote_device_id,
            'protocol'         => $protocol,
            //'last_reason'      => 'no_dns' // 'ok','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'
        ];
        set_autodiscovery($hostname, 'ok', $insert);
        return $remote_device_id;
    } else {

        $insert = [
            //'poller_id'        => $config['poller_id'],
            'device_id' => $device['device_id'],
            //'remote_hostname'  => $hostname,
            'remote_ip' => $ip,
            //'remote_device_id' => NULL,
            'protocol'  => $protocol,
            //'last_reason'      => 'no_dns' // 'ok','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'
        ];
        set_autodiscovery($hostname, 'no_db', $insert);
    }

    return FALSE;
}

function set_autodiscovery($hostname, $reason = 'unknown', $options = [])
{
    global $cache;

    if (isset($options['remote_address'])) {
        $options['remote_ip'] = $options['remote_address'];
    }
    $ip_key = safe_ip_hostname_key($hostname, $options['remote_ip']);

    // Cache autodiscovery entry
    $db_entry = get_autodiscovery_entry($hostname, $options['remote_ip'], $options['device_id']);

    $insert = [
      'poller_id'       => $GLOBALS['config']['poller_id'],
      //'device_id'        => $device['device_id'],
      'remote_hostname' => $hostname,
      //'remote_ip'        => $ip,
      //'remote_device_id' => NULL,
      //'protocol'         => $protocol,
      'last_reason'     => $reason
    ];
    foreach (['device_id', 'remote_ip', 'remote_device_id', 'protocol'] as $param) {
        if (strlen($options[$param])) {
            // normalize ip address
            if ($param === 'remote_ip') {
                $options[$param] = ip_compress($options[$param]);
            }
            $insert[$param] = $options[$param];
        }
    }

    // Set cache key, for use in check_autodiscovery()
    //$cache['autodiscovery_remote_device_id'][$hostname][$ip_key] = $insert['remote_device_id'];

    $db_update = [];
    //$db_params = [ 'device_id', 'remote_ip', 'remote_device_id', 'protocol', 'last_reason' ];
    $db_params = ['device_id', 'remote_ip', 'remote_device_id', 'last_reason']; // do not change protocol when update

    if ($reason === 'ok') {
        $db_params[] = 'protocol';
    }
    // BGP and OSPF (and others not XDP protocols not pass hostname, do not update it)
    $hostname_update = !in_array(strtolower($insert['protocol']), ['bgp', 'ospf']);

    if (is_array($db_entry)) {
        // already discovered
        $db_id = $db_entry['autodiscovery_id'];
        foreach ($db_params as $param) {
            // Skip hostname update for non-xdp protocols
            if ($param === 'remote_hostname' && !$hostname_update) {
                continue;
            }

            if ($db_entry[$param] != $insert[$param] && strlen($insert[$param])) {
                $db_update[$param] = $insert[$param];
            }
        }

        if (!count($db_update)) {
            // not changed, but force update for increase last_checked
            dbUpdate(['last_checked' => ['CURRENT_TIMESTAMP()']], 'autodiscovery', '`autodiscovery_id` = ?', [$db_id]);

            // Clear cache entry
            unset($cache['autodiscovery'][$hostname][$ip_key]);

            return $db_id;
        }
    }

    /*
    if (isset($cache['autodiscovery'][$hostname][$ip_key]))
    {
      // already discovered
      $db_entry = $cache['autodiscovery'][$hostname][$ip_key];
      $db_id = $db_entry['autodiscovery_id'];
      foreach ($db_params as $param)
      {
        if ($db_entry[$param] != $insert[$param] && strlen($insert[$param]))
        {
          $db_update[$param] = $insert[$param];
        }
      }

      if (!count($db_update))
      {
        // not changed, but force update for increase last_checked
        dbUpdate([ 'last_checked' => [ 'CURRENT_TIMESTAMP()' ] ], 'autodiscovery', '`autodiscovery_id` = ?', [ $db_id ]);

        // Clear cache entry
        unset($cache['autodiscovery'][$hostname][$ip_key]);

        return $db_id;
      }
    }
    elseif (isset($cache['autodiscovery'][$hostname]['__']) && $ip_key != '__')
    {
      // already discovered, but without ip
      $db_entry = $cache['autodiscovery'][$hostname]['__'];
      $db_id = $db_entry['autodiscovery_id'];
      foreach ($db_params as $param)
      {
        if ($db_entry[$param] != $insert[$param] && strlen($insert[$param]))
        {
          $db_update[$param] = $insert[$param];
        }
      }
    }
    elseif ($ip_key != '__' && isset($cache['autodiscovery_ip'][$ip_key]))
    {
      // FIXME. Host already discovered by IP, but we not check it this..
      print_debug("DEVEL. Host already discovered by IP, but we not check it this..");
    }
    elseif ($hostname_ip_version = get_ip_version($hostname))
    {
      // FIXME. Try if hostname passed as IP
      $hostname = ip_compress($hostname);
      if (isset($cache['autodiscovery_ip'][$hostname]))
      {
        print_debug("DEVEL. Try if hostname passed as IP..");
      }
    }
    */

    if (count($db_update)) {
        dbUpdate($db_update, 'autodiscovery', '`autodiscovery_id` = ?', [$db_id]);
        print_debug("AUTODISCOVERY UPDATED");

        // Clear cache entry
        unset($cache['autodiscovery'][$hostname][$ip_key]);
    } else {
        $insert['added'] = ['NOW()'];
        $db_id           = dbInsert($insert, 'autodiscovery');
        print_debug("AUTODISCOVERY INSERTED");
    }

    return $db_id;
}

function check_autodiscovery($hostname, $ip = NULL)
{
    global $config, $cache;

    if (get_ip_type($ip) === 'unspecified' && is_valid_hostname($hostname, TRUE) &&
        $ip_dns = gethostbyname6($hostname)) {
        // When discovery protocol return valid FQDN hostname but invalid IP (0.0.0.0), try use dns IP
        // See: https://jira.observium.org/browse/OBS-4117
        $ip = $ip_dns;
    }

    $ip_key = safe_ip_hostname_key($hostname, $ip);

    // Invalid hostname && IP
    $valid_hostname = is_valid_hostname($hostname);
    if (!$valid_hostname && $ip_key === '__') {
        print_debug("Invalid hostname $hostname and empty IP, skipped.");
        return FALSE;
    }

    // Cache autodiscovery entry
    if (!isset($cache['autodiscovery'][$hostname][$ip_key])) {
        if (!$valid_hostname && $ip_key === '__') {
            $cache['autodiscovery'][$hostname][$ip_key] = NULL;
            return NULL;
        }

        $sql    = 'SELECT `autodiscovery`.*, UNIX_TIMESTAMP(`last_checked`) AS `last_checked_unixtime` FROM `autodiscovery` WHERE `poller_id` = ? ';
        $params = [$GLOBALS['config']['poller_id']];
        if ($ip == $hostname || !$valid_hostname) {
            // print_vars($ip);
            // print_vars($ip_key);
            // print_vars($hostname);
            // Search by IP
            $sql      .= 'AND `remote_ip` = ?';
            $params[] = $ip;
        } elseif ($ip_key === '__') {
            // Undefined IP
            $sql      .= 'AND `remote_hostname` = ? AND (`remote_ip` IS NULL OR `remote_ip` IN (?, ?))';
            $params[] = $hostname;
            $params[] = '0.0.0.0';
            $params[] = '::';
        } else {
            // Search by $hostname/$ip
            $sql      .= 'AND `remote_hostname` = ? AND `remote_ip` = ?';
            $params[] = $hostname;
            $params[] = $ip;
        }

        if ($entry = dbFetchRow($sql, $params)) {
            $cache['autodiscovery'][$hostname][$ip_key] = $entry;
        }
    }

    if (isset($cache['autodiscovery'][$hostname][$ip_key])) {
        // already discovered
        $db_entry = $cache['autodiscovery'][$hostname][$ip_key];
        //$remote_device_id = $db_entry['remote_device_id'];
        print_debug("AUTODISCOVERY DEVEL: hostname & ip DB found");
    } elseif (isset($cache['autodiscovery'][$hostname]['__']) && $ip_key !== '__') {
        // already discovered, but without ip
        $db_entry = $cache['autodiscovery'][$hostname]['__'];
        //$remote_device_id = $db_entry['remote_device_id'];
        print_debug("AUTODISCOVERY DEVEL: hostname DB found");
    }
    // if (isset($cache['autodiscovery_remote_device_id'][$hostname]) &&
    //     array_key_exists($ip_key, $cache['autodiscovery_remote_device_id'][$hostname]))
    // {
    //   print_debug("Hostname $hostname ($ip) already checked by autodiscovery.");
    //   return $cache['autodiscovery_remote_device_id'][$hostname][$ip_key];
    // }

    if ($db_entry) {
        print_debug_vars($db_entry);
        switch ($db_entry['last_reason']) {
            // 'ok','no_xdp','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'

            case 'ok':
                // Already added device, no need for discovery, also checked by get_autodiscovery_device_id()
                print_debug("Remote device already discovered, no need for discovery again.");
                return FALSE;

            case 'duplicated':
                print_debug("Remote device passed mostly checks, but detected as duplicated.");
                return FALSE;

            //case 'no_db':
            //  break;
            default:
                // All other reasons check last_checked_unixtime not more than 24 hours
                $interval = get_time() - $db_entry['last_checked_unixtime'];
                if ($interval <= $config['autodiscovery']['recheck_interval']) {
                    $interval         = format_uptime($interval);
                    $recheck_interval = format_uptime($config['autodiscovery']['recheck_interval']);
                    print_debug("Remote device checked $interval ago (less than $recheck_interval)");
                    //return FALSE;
                }
        }
    }

    return TRUE;
}

function get_autodiscovery_entry($hostname, $ip = NULL, $exclude_device_id = NULL)
{
    global $cache;

    $ip_key = safe_ip_hostname_key($hostname, $ip);

    if (!isset($cache['autodiscovery'][$hostname][$ip_key])) {
        $valid_hostname = is_valid_hostname($hostname);
        if (!$valid_hostname && $ip_key === '__') {
            $cache['autodiscovery'][$hostname][$ip_key] = NULL;
            return NULL;
        }

        $sql    = 'SELECT `autodiscovery`.*, UNIX_TIMESTAMP(`last_checked`) AS `last_checked_unixtime` FROM `autodiscovery` WHERE `poller_id` = ?';
        $params = [$GLOBALS['config']['poller_id']];
        if ($ip == $hostname || !$valid_hostname) {
            // Search by IP
            // print_vars($ip);
            // print_vars($ip_key);
            // print_vars($hostname);
            $sql      .= ' AND `remote_ip` = ?';
            $params[] = $ip;
        } elseif ($ip_key === '__') {
            // Undefined IP
            $sql      .= ' AND `remote_hostname` = ? AND (`remote_ip` IS NULL OR `remote_ip` IN (?, ?))';
            $params[] = $hostname;
            $params[] = '0.0.0.0';
            $params[] = '::';
        } else {
            // Search by $hostname/$ip
            $sql      .= ' AND `remote_hostname` = ? AND `remote_ip` = ?';
            $params[] = $hostname;
            $params[] = $ip;
        }
        // Exclude local device
        if (is_numeric($exclude_device_id)) {
            $sql      .= ' AND (`remote_device_id` IS NULL OR `remote_device_id` != ?)';
            $params[] = $exclude_device_id;
        }

        if ($entry = dbFetchRow($sql, $params)) {
            $cache['autodiscovery'][$hostname][$ip_key] = $entry;
        }
    }

    return $cache['autodiscovery'][$hostname][$ip_key];
}

// Note return numeric device_id if already found, if not found: FALSE (for cached results) or NULL for not cached
function get_autodiscovery_device_id($device, $hostname, $ip = NULL, $mac = NULL) {
    global $cache;

    $ip_type = get_ip_type($ip);
    if ($ip_type === 'unspecified' && is_valid_hostname($hostname, TRUE) &&
        $ip_dns = gethostbyname6($hostname)) {
        // When discovery protocol return valid FQDN hostname but invalid IP (0.0.0.0), try use dns IP
        // See: https://jira.observium.org/browse/OBS-4117
        $ip      = $ip_dns;
        $ip_type = get_ip_type($ip);
    }
    $ip_key = safe_ip_hostname_key($hostname, $ip);

    // Check if cached
    if (isset($cache['autodiscovery_remote_device_id'][$hostname]) &&
        array_key_exists($ip_key, $cache['autodiscovery_remote_device_id'][$hostname])) {
        print_debug("AUTODISCOVERY DEVEL: remote_device_id from cache");
        // Set to false, for prevent caching with NULL
        if (empty($cache['autodiscovery_remote_device_id'][$hostname][$ip_key])) {
            return FALSE;
        }
        return $cache['autodiscovery_remote_device_id'][$hostname][$ip_key];
    }

    // Always check by mac for private IPs
    $check_mac = $ip_type === 'private' && !safe_empty($mac);

    // Check previous autodiscovery rounds as mostly correct!
    if (!$check_mac) {
        print_debug("Autodiscovery skipped for Private IPs [$ip] and checks by MAC address [$mac].");
        //$sql = 'SELECT `remote_device_id` FROM `autodiscovery` WHERE `remote_hostname` = ? AND `remote_ip` = ? AND `last_reason` = ?';
        //$remote_device_id = dbFetchCell($sql, [ $hostname, $ip, 'ok' ]);
        $autodiscovery_entry = get_autodiscovery_entry($hostname, $ip, $device['device_id']);
        if (isset($autodiscovery_entry['last_reason']) && $autodiscovery_entry['last_reason'] === 'ok') {
            $remote_device_id = $autodiscovery_entry['remote_device_id'];
        }
    }

    // Try to find remote host by remote chassis mac address from DB
    if (!$remote_device_id) {
        $remote_device_id = get_device_id_by_mac($mac, $device['device_id']);
    }

    // We can also use IP address to find remote device.
    if (!$remote_device_id && $ip_type && !in_array($ip_type, ['unspecified', 'loopback'])) { // 'link-local' ?

        //$remote_device_id = dbFetchCell("SELECT `device_id` FROM `ports` LEFT JOIN `ipv4_addresses` on `ports`.`port_id`=`ipv4_addresses`.`port_id` WHERE `deleted` = '0' AND `ipv4_address` = ? LIMIT 1;", array($entry['mtxrNeighborIpAddress']));
        $peer_where = generate_query_values_and($device['device_id'], 'device_id', '!=');     // Additional filter for exclude self IPs
        // Fetch all devices with peer IP and filter by UP
        if ($ids = get_entity_ids_ip_by_network('device', $ip, $peer_where)) {
            $remote_device_id = $ids[0];
            if (count($ids) > 1) {
                // If multiple same IPs found, get first NOT disabled or down
                foreach ($ids as $id) {
                    $tmp_device = device_by_id_cache($id);
                    if (!$tmp_device['disabled'] && $tmp_device['status']) {
                        $remote_device_id = $id;
                        break;
                    }
                }
            } elseif ($check_mac) {
                // Do not set remote device by private ip when really should be found by MAC address
                $remote_device_id = NULL;
            }
        }
    }

    // Check if device already exist by hostname
    if (!$remote_device_id) {
        $remote_device_id = get_device_id_by_hostname($hostname);
        // If hostname is FQDN, also try by sysName
        $hostname_valid = is_valid_hostname($hostname, TRUE);
        if (!$remote_device_id && $hostname_valid) {
            $remote_device_id = dbFetchCell("SELECT `device_id` FROM `devices` WHERE `sysName` = ? AND `disabled` = ?", [$hostname, 0]);
        }
        // Last chance if hostname not FQDN and not an default sysname
        if (!$remote_device_id && !$hostname_valid && strlen($hostname) > 6 &&
            !in_array(strtolower($hostname), $GLOBALS['config']['devices']['ignore_sysname'], TRUE)) {
            $remote_devices = dbFetchRows("SELECT * FROM `devices` WHERE `sysName` = ? AND `disabled` = ?", [$hostname, 0]);
            $remote_count   = safe_count($remote_devices);
            if ($remote_count === 1) {
                // Use only unique sysName!
                $remote_device_id = $remote_devices[0]['device_id'];
            } elseif ($remote_count > 1) {
                print_debug("Founded multiple devices with not unique sysName:\n");
                print_debug_vars($remote_devices);
            }
        }
    }

    // This is dumb, but I not remember if all functions return null :/
    if ($remote_device_id === FALSE) {
        $remote_device_id = NULL;
    }

    if ($remote_device_id) {
        // Founded remote device in local DB
        $insert = [
            //'poller_id'        => $config['poller_id'],
            'device_id'        => $device['device_id'],
            //'remote_hostname'  => $hostname,
            'remote_ip'        => $ip,
            'remote_device_id' => $remote_device_id,
            'protocol'         => 'XDP',
            //'last_reason'      => 'no_dns' // 'ok','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'
        ];
        set_autodiscovery($hostname, 'ok', $insert);
    }

    $cache['autodiscovery_remote_device_id'][$hostname][$ip_key] = $remote_device_id;
    return $remote_device_id;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function discover_device($device, $options = NULL)
{
    global $config, $valid, $cache_discovery, $discovered_devices;

    // Initialise variables
    $valid             = []; // Reset $valid array
    $cache_discovery   = []; // Specific discovery cache for exchange snmpwalk data between modules (memory/storage/sensors/etc)
    $attribs           = get_entity_attribs('device', $device['device_id']);
    $device['attribs'] = $attribs; // better here for access inside functions.
    $device_start      = utime();  // Start counting device poll time

    // Check if device discovery already running
    $pid_info = check_process_run($device);
    if ($pid_info) {
        // Process ID exist in DB
        print_message("%rAnother " . $pid_info['process_name'] . " process (PID: " . $pid_info['PID'] . ", UID: " . $pid_info['UID'] . ", STARTED: " . $pid_info['STARTED'] . ") already running for device " . $device['hostname'] . " (" . $device['device_id'] . ").%n", 'color');
        return FALSE;
    }
    add_process_info($device); // Store process info

    print_cli_heading($device['hostname'] . " [" . $device['device_id'] . "]", 1);

    $detect_os = TRUE; // Set TRUE or FALSE for module 'os' (exclude double os detection)
    if ($device['os'] === 'generic' || (isset($options['h']) && $options['h'] === 'new')) { // verify if OS has changed
        $detect_os    = FALSE;
        $os_changed = check_device_os_changed($device);

        // Set device sysObjectID when device just added (required for some cases before other discovery/polling)
        $sysObjectID = snmp_cache_sysObjectID($device);
        if ($device['sysObjectID'] != $sysObjectID) {
            dbUpdate(['sysObjectID' => $sysObjectID], 'devices', '`device_id` = ?', [ $device['device_id'] ]);
            $device['sysObjectID'] = $sysObjectID;
        }
    } elseif (is_null($device['sysObjectID'])) {
        // Set device sysObjectID when device just added (required for some cases before other discovery/polling)
        $sysObjectID           = snmp_cache_sysObjectID($device);
        $device['sysObjectID'] = $sysObjectID;
    }

    if (OBS_DEBUG > 1) {
        // Cache cleanup for new device
        $device_discovery_cache_keys = array_keys($GLOBALS['cache']);
        print_vars($device_discovery_cache_keys);
        // Show full list permitted MIBs
        print_vars(get_device_mibs_permitted($device));
    }

    print_cli_data("OS Type", $device['os'], 1);

    if ($config['os'][$device['os']]['group']) {
        $device['os_group'] = $config['os'][$device['os']]['group'];
        print_cli_data("OS Group", $device['os_group'], 1);
    }

    print_cli_data("SNMP Version", $device['snmp_version'], 1);

    print_cli_data("Last discovery", $device['last_discovered'], 1);
    print_cli_data("Last duration", $device['last_discovered_timetaken'] . " seconds", 1);

    echo(PHP_EOL);

    $modules = get_discovery_modules($device, $options, $modules_forced);

    foreach ($modules as $module => $module_status) {

        if (!(in_array($module, $modules_forced) || is_module_enabled($device, $module))) {
            continue;
        }
        //if ($attribs['discover_'.$module] || ( $module_status && !isset($attribs['discover_'.$module])))

        $m_start                          = utime();
        $GLOBALS['module_stats'][$module] = [];
        $valid[$module]                   = [];

        print_cli_heading("Module Start: %R" . $module);

        include("includes/discovery/$module.inc.php");

        $GLOBALS['module_stats'][$module]['time'] = elapsed_time($m_start, 4);
        print_module_stats($device, $module);
        echo(PHP_EOL);
        //print_cli_heading("Module End: %R".$module."");
    }

    // Modules enabled stats:
    $modules_stat = $GLOBALS['cache']['devices']['discovery_modules'][$device['device_id']];

    if (safe_count($modules_stat['excluded'])) {
        print_cli_data("Modules Excluded", implode(", ", $modules_stat['excluded']), 1);
    }
    if (safe_count($modules_stat['disabled'])) {
        print_cli_data("Modules Disabled", implode(", ", $modules_stat['disabled']), 1);
    }
    if (safe_count($modules_stat['enabled'])) {
        print_cli_data("Modules Enabled", implode(", ", $modules_stat['enabled']), 1);
    }

    // Set type to a predefined type for the OS if it's not already set
    if ($device['type'] === "unknown" || $device['type'] == "") {
        if ($config['os'][$device['os']]['type']) {
            $device['type'] = $config['os'][$device['os']]['type'];
        }
    }

    $device_end  = utime();
    $device_run  = $device_end - $device_start;
    $device_time = round($device_run, 4);

    $update_array = ['last_discovered' => ['NOW()'], 'type' => $device['type'], 'last_discovered_timetaken' => $device_time, 'force_discovery' => 0];

    // Store device stats and history data (only) if we're not doing a single-module poll
    if (!$options['m']) {
        // Fetch previous device state (do not use $device array here, for exclude update history collisions)
        $old_device_state = dbFetchCell('SELECT `device_state` FROM `devices` WHERE `device_id` = ?;', [$device['device_id']]);
        $old_device_state = safe_unserialize($old_device_state);

        // Add first entry
        $discovery_history = [(int)$device_start => $device_time]; // start => duration
        // Adds and keeps not more than 100 last entries
        if (isset($old_device_state['discovery_history'])) {
            print_debug_vars($old_device_state['discovery_history']);
            $discovery_history = array_slice($discovery_history + $old_device_state['discovery_history'], 0, 100, TRUE);
        }
        print_debug_vars($discovery_history);

        // Keep per module perf stats
        $discovery_mod_perf = [];
        foreach ($GLOBALS['module_stats'] as $module => $entry) {
            $discovery_mod_perf[$module] = $entry['time'];
        }

        $device_state                       = $old_device_state; // Keep old devices state (from poller)
        $device_state['discovery_history']  = $discovery_history;
        $device_state['discovery_mod_perf'] = $discovery_mod_perf;

        unset($discovery_history, $old_device_state);

        $update_array['device_state'] = serialize($device_state);

        // Not worth putting discovery data into rrd. it's not done every 5 mins :)
    }

    dbUpdate($update_array, 'devices', '`device_id` = ?', [$device['device_id']]);

    // Clean force discovery
    if (isset($attribs['force_discovery_modules'])) {
        del_entity_attrib('device', $device['device_id'], 'force_discovery_modules');
    }

    print_cli_heading($device['hostname'] . " [" . $device['device_id'] . "] completed discovery modules at " . date("Y-m-d H:i:s"), 1);

    print_cli_data("Discovery time", $device_time . " seconds", 1);

    echo(PHP_EOL);
    $discovered_devices++;

    // Clean
    if (OBS_DEBUG > 1) {
        $device_discovery_cache_keys = array_diff(array_keys($GLOBALS['cache']), $device_discovery_cache_keys);
        print_vars($device_discovery_cache_keys);
        //print_vars($GLOBALS['cache']);
    }
    del_process_info($device); // Remove process info
    // Used by ENTITY-MIB
    unset($cache_discovery, $GLOBALS['cache']['snmp']);
    // Per-Pable / per-OID cache
    unset($GLOBALS['cache_snmp'][$device['device_id']]);
}

function get_discovery_modules($device, $options = NULL, &$modules_forced = []) {
    global $config;

    $modules = [];

    // Either only run the modules specified on the commandline, or run all modules in config.
    $modules_forced = [];
    if ($options['m']) {
        foreach (explode(",", $options['m']) as $module) {
            if (!isset($config['discovery_modules'][$module])) {
                continue;
            } // unknown module

            $modules[$module] = TRUE;
            $modules_forced[] = $module;
        }
    } elseif ($device['force_discovery'] && $options['h'] === 'new' && isset($device['attribs']['force_discovery_modules'])) {
        // Forced discovery-specific modules
        foreach (safe_json_decode($device['attribs']['force_discovery_modules']) as $module) {
            $modules[$module] = TRUE;
        }
        log_event('Forced discovery module(s): ' . implode(', ', array_keys($modules)), $device, 'device', $device['device_id'], 'debug');
    } else {
        $modules = $config['discovery_modules'];
    }

    // Use os specific modules order
    //print_vars($modules);
    if (isset($config['os'][$device['os']]['discovery_order'])) {
        //print_vars($config['os'][$device['os']]['discovery_order']);
        foreach ($config['os'][$device['os']]['discovery_order'] as $module => $module_order) {
            if (array_key_exists($module, $modules)) {
                $module_status = $modules[$module];
                switch ($module_order) {
                    case 'last':
                        // add to end of modules list
                        unset($modules[$module]);
                        $modules[$module] = $module_status;
                        break;
                    case 'first':
                        // add to begin of a module list, but not before os/system
                        $new_modules = [];
                        if ($modules['os']) {
                            $new_modules['os'] = $modules['os'];
                            unset($modules['os']);
                        }
                        if ($modules['system']) {
                            $new_modules['system'] = $modules['system'];
                            unset($modules['system']);
                        }
                        $new_modules[$module] = $module_status;
                        unset($modules[$module]);
                        $modules = $new_modules + $modules;
                        break;
                    default:
                        // add into specific place (after module name in $module_order)
                        // yes, this is hard and magic
                        if (array_key_exists($module_order, $modules)) {
                            unset($modules[$module]);
                            $new_modules = [];
                            foreach ($modules as $new_module => $new_status) {
                                array_shift($modules);
                                $new_modules[$new_module] = $new_status;
                                if ($new_module == $module_order) {
                                    $new_modules[$module] = $module_status;
                                    break;
                                }
                            }
                            $modules = array_merge($new_modules, (array)$modules);
                        }
                }
            }
        }
        //print_vars($modules);
    }

    return $modules;
}

// TESTME needs unit testing
// FIXME don't pass valid, use this as a global variable
/**
 * Discover a new virtual machine on a device
 *
 * This function adds a virtual machine to a device, if it does not already exist.
 * Data on the VM is updated if it has changed, and an event is logged with regards to the changes.
 * If the VM has a valid hostname, Observium attempts to discover this as a new device (calling discover_new_device).
 *
 * Valid array keys for the $options array: type, id, name, os, memory (in bytes), cpucount, status, source (= snmp, agent, etc)
 *
 * @param array &$valid
 * @param array  $device
 * @param array  $options
 */
function discover_virtual_machine(&$valid, $device, $options = [])
{
    print_debug('Discover VM: ' . $options['type'] . '/' . $options['source'] . ' (' . $options['id'] . ') ' . $options['name'] . ' CPU: ' . $options['cpucount'] . ' RAM: ' . $options['memory'] . ' Status: ' . $options['status']);

    $options['protocol'] = $options['protocol'] ?? $options['type'];

    //if (dbFetchCell("SELECT COUNT(`vm_id`) FROM `vminfo` WHERE `device_id` = ? AND `vm_name` = ? AND `vm_type` = ? AND `vm_source` = ?",
    if (!dbExist('vminfo', '`device_id` = ? AND `vm_name` = ? AND `vm_type` = ? AND `vm_source` = ?',
                 [$device['device_id'], $options['name'], $options['type'], $options['source']])) {
        $vm_insert = ['device_id'   => $device['device_id'],
                      'vm_type'     => $options['type'],
                      'vm_uuid'     => $options['id'],
                      'vm_name'     => $options['name'],
                      'vm_guestos'  => $options['os'],
                      'vm_memory'   => $options['memory'] / 1024 / 1024,
                      'vm_cpucount' => $options['cpucount'],
                      'vm_state'    => $options['status'],
                      'vm_source'   => $options['source']];
        $vm_id     = dbInsert($vm_insert, 'vminfo');
        echo('+');
        log_event("Virtual Machine added: " . $options['name'] . ' (' . format_bi($options['memory']) . 'B RAM, ' . $options['cpucount'] . ' CPU)', $device, 'virtualmachine', $vm_id);

        if (is_valid_hostname($options['name']) && in_array($options['status'], ['running', 'powered on', 'poweredOn'])) {
            // Try to discover this VM as a new device, if it's actually running. discover_new_device() will check for valid hostname, duplicates, etc.
            // Libvirt, Proxmox (= QEMU-powered) return "running"; VMWare returns "powered on" (or "poweredOn" in older versions).
            autodiscovery_device($options['name'], NULL, $options['protocol'], $options['os'], $device);
        }
    } else {
        $vm = dbFetchRow("SELECT * FROM `vminfo` WHERE `device_id` = ? AND `vm_uuid` = ? AND `vm_type` = ?", [$device['device_id'], $options['id'], $options['type']]);
        if ($vm['vm_state'] != $options['status'] || $vm['vm_name'] != $options['name'] ||
            $vm['vm_cpucount'] != $options['cpucount'] || $vm['vm_guestos'] != $options['os'] ||
            $vm['vm_memory'] != $options['memory'] / 1024 / 1024) {
            $update = [
              'vm_state'    => $options['status'],
              'vm_guestos'  => $options['os'],
              'vm_name'     => $options['name'],
              'vm_memory'   => $options['memory'] / 1024 / 1024,
              'vm_cpucount' => $options['cpucount']
            ];
            dbUpdate($update, 'vminfo', "device_id = ? AND vm_type = ? AND vm_uuid = ? AND vm_source = ?", [$device['device_id'], $options['type'], $options['id'], $options['source']]);
            echo('U');
            /// FIXME eventlog changed fields
        } else {
            echo('.');
        }
    }

    $valid['vm'][$options['type']][(string)$options['id']] = 1;
}

/**
 * Discover a new application on a device
 *
 * This function returns an app_id for the application code to use.
 * If the app+instance combination already exists in the database, the current id will be returned.
 * If not, a new row will be created and the newly created id will be returned.
 *
 * @param array  $device
 * @param string $type
 * @param string $instance
 *
 * @return integer
 */
function discover_app($device, $type, $instance = NULL)
{
    if ($instance == NULL) {
        $app_data = dbFetchRow("SELECT * FROM `applications` WHERE `device_id` = ? AND `app_type` = ? AND `app_instance` IS NULL", [$device['device_id'], $type]);
    } else {
        $app_data = dbFetchRow("SELECT * FROM `applications` WHERE `device_id` = ? AND `app_type` = ? AND `app_instance` = ?", [$device['device_id'], $type, $instance]);
    }

    if ($app_data == FALSE) {
        $app_insert = ['device_id' => $device['device_id'], 'app_type' => $type, 'app_instance' => ($instance == NULL ? ['NULL'] : $instance)];
        echo('+');
        return dbInsert($app_insert, 'applications');
    } else {
        echo('.');
        return $app_data['app_id'];
    }
}

/**
 * Fetch table or oids inside sensor/status definition discovery
 *
 * @param array  $device     Device array
 * @param string $mib        MIB name
 * @param array  $def        Definition entry
 * @param array  $table_oids List of required Oids to fetch
 *
 * @return array
 */
function discover_fetch_oids($device, $mib, $def, $table_oids) {

    // Individual indexes (not sure how unify for all entities)
    $entity_get  = isset($GLOBALS['config']['mibs'][$mib]['sensors_walk']) && !$GLOBALS['config']['mibs'][$mib]['sensors_walk'];
    $indexes_get = isset($def['indexes']) && ($entity_get || $def['type'] === 'static' || $def['type'] === 'indexes'); // type static for processors
    $oids_walk   = (isset($def['table_walk']) && !$def['table_walk']) || !isset($def['table']);

    if ($indexes_get) {
        // Get separate Oids by snmpget for cases, when device have troubles with walking (ie FIREBRICK-MIB)

        $array = fetch_def_oids_indexes($device, $mib, $def, $table_oids);

    } elseif ($oids_walk) {
        // Walk individual OIDs separately

        $array = fetch_def_oids_walks($device, $mib, $def, $table_oids);

    } else {
        // Walk the whole table
        $flags = get_def_snmp_flags($def);
        $array = snmp_cache_table($device, $def['table'], [], $mib, NULL, $flags);
        // Append oid_extra
        if (isset($def['oid_extra']) && in_array('oid_extra', $table_oids, TRUE)) {
            foreach ((array)$def['oid_extra'] as $get_oid) {
                $array = snmp_cache_table($device, $get_oid, $array, $mib, NULL, $flags);
            }
        }
    }

    // Populate entPhysical using the value of supplied oid_entPhysicalIndex.
    // Will populate with values from entPhysicalContainedIn if entPhysical_parent set.
    fetch_def_oids_entphysical($device, $def, $array);

    print_debug_vars($array);
    return $array;
}

function get_def_snmp_flags($def) {
    // SNMP flags
    $flags = OBS_SNMP_ALL_NUMERIC_INDEX | OBS_SNMP_DISPLAY_HINT | OBS_SNMP_CONCAT;
    // Allow custom definition flags
    if (isset($def['snmp_flags'])) {
        if (is_flag_set(OBS_SNMP_TABLE, $def['snmp_flags'])) {
            // If table output is used, exclude numeric index
            $flags ^= OBS_SNMP_NUMERIC_INDEX;
        }
        return $flags | $def['snmp_flags'];
    }
    // Force UTF decode?
    //$flags = $flags | OBS_SNMP_HEX | OBS_DECODE_UTF8;

    return $flags;
}

function fetch_def_oids_indexes($device, $mib, $def, $table_oids) {
    // Get separate Oids by snmpget for cases, when device have troubles with walking (ie FIREBRICK-MIB)

    // Create a list of required Oids
    $get_oids = [];
    foreach ($table_oids as $table_oid) {
        if (isset($def[$table_oid])) {
            // See WIPIPE-MIB
            $oids_tmp = ($table_oid === 'oids') ? array_keys($def[$table_oid]) : (array)$def[$table_oid];
            //$get_oids = array_merge($get_oids, $oids_tmp);
            $get_oids[] = $oids_tmp;
        }
    }
    $get_oids = array_merge([], ...$get_oids);
    unset($oids_tmp);

    // Get individual Oids
    $i = 0;
    $array = [];
    foreach (array_keys($def['indexes']) as $index) {
        $get_oid = implode(".$index ", $get_oids) . ".$index"; // Generate multi get list of indexed oids
        $array   = snmp_get_multi_oid($device, $get_oid, $array, $mib, NULL, get_def_snmp_flags($def));

        if ($i === 0 && !snmp_status()) {
            break;
        } // break on first index loop of incorrect answer
        $i++;
    }

    return $array;
}

function fetch_def_oids_walks($device, $mib, $def, $table_oids) {
    // Walk individual OIDs separately

    $flags = get_def_snmp_flags($def);
    $array = [];
    foreach ($table_oids as $table_oid) {
        if (isset($def[$table_oid])) {
            $oids_tmp = ($table_oid === 'oids') ? array_keys($def[$table_oid]) : (array)$def[$table_oid];
            foreach ($oids_tmp as $get_oid) {
                // Do not walk limit/unit/scale oids with passed indexes (they should be fetched by get)
                if (str_contains_array($table_oid, [ 'limit', 'unit', 'scale', 'total', 'count' ]) && str_contains($get_oid, '.')) {
                    continue;
                }

                $array = snmp_cache_table($device, $get_oid, $array, $mib, NULL, $flags);
                //print_vars($array);
            }
        }
    }

    return $array;
}

function fetch_def_oids_entphysical($device, $def, &$array) {

    if (!isset($def['oid_entPhysicalIndex']) && !isset($def['entPhysicalIndex'])) {
        return;
    }

    $oids_tmp = [];

    // Fetch entPhysicalIndex by defined oid or just use tag, mostly common is %index%
    $use_oid = isset($def['oid_entPhysicalIndex']);

    foreach ($array as $index => $array_tmp) {
        // Get entPhysicalIndex
        if ($use_oid) {
            $entPhysicalIndex = $array_tmp[$def['oid_entPhysicalIndex']] ?? NULL;
        } else {
            $array_tmp['index'] = $index;
            $entPhysicalIndex   = array_tag_replace($array_tmp, $def['entPhysicalIndex']);
        }

        if (is_numeric($entPhysicalIndex)) {
            if (isset($def['entPhysical_parent']) && $def['entPhysical_parent']) {
                $oidlist = [ 'entPhysicalContainedIn' ];
            } else {
                $oidlist = [ 'entPhysicalContainedIn', 'entPhysicalDescr', 'entPhysicalAlias', 'entPhysicalName' ];
            }

            foreach ($oidlist as $oid_tmp) {
                $oids_tmp[] = $oid_tmp . '.' . $entPhysicalIndex;
            }
        }
    }

    $entPhysicalTable = snmp_get_multi_oid($device, $oids_tmp, [], 'ENTITY-MIB');

    foreach ($array as $index => $array_tmp) {
        // Get entPhysicalIndex
        if ($use_oid) {
            $entPhysicalIndex = $array_tmp[$def['oid_entPhysicalIndex']] ?? NULL;
        } else {
            $array_tmp['index'] = $index;
            $entPhysicalIndex   = array_tag_replace($array_tmp, $def['entPhysicalIndex']);
        }

        if (is_array($entPhysicalTable[$entPhysicalIndex])) {
            $array[$index] = array_merge($array_tmp, $entPhysicalTable[$entPhysicalIndex]);
        }
    }

    if (isset($def['entPhysical_parent']) && $def['entPhysical_parent']) {
        $oids_tmp = [];

        foreach ($entPhysicalTable as $entPhysicalIndex => $entPhysicalEntry) {
            foreach (['entPhysicalDescr', 'entPhysicalAlias', 'entPhysicalName'] as $oid_tmp) {
                $oids_tmp[] = $oid_tmp . '.' . $entPhysicalEntry['entPhysicalContainedIn'];
            }
        }
        $entPhysicalTable = snmp_get_multi_oid($device, $oids_tmp, $entPhysicalTable, 'ENTITY-MIB');

        foreach ($array as $index => $array_tmp) {
            if (is_array($entPhysicalTable[$array_tmp['entPhysicalContainedIn']])) {
                $array[$index] = array_merge($array_tmp, $entPhysicalTable[$array_tmp['entPhysicalContainedIn']]);
            }
        }
    }
}

function fetch_oids_lldp_addr($device, &$lldp_array) {
    if (empty($lldp_array)) {
        return;
    }

    // lldpRemManAddrTable
    // Case 1:
    // LLDP-MIB::lldpRemManAddrSubtype.120.30001.1582.1.4.10.133.3.10 = INTEGER: ipV4(1)
    // LLDP-MIB::lldpRemManAddr.120.30001.1582.1.4.10.133.3.10 = Hex-STRING: 0A 85 03 0A
    // LLDP-MIB::lldpRemManAddrIfSubtype.120.30001.1582.1.4.10.133.3.10 = INTEGER: ifIndex(2)
    // LLDP-MIB::lldpRemManAddrIfId.120.30001.1582.1.4.10.133.3.10 = INTEGER: 2009
    // LLDP-MIB::lldpRemManAddrOID.120.30001.1582.1.4.10.133.3.10 = OID: SNMPv2-SMI::zeroDotZero.0
    // Case 2:
    // LLDP-MIB::lldpRemManAddrIfSubtype.1173570000.129.2.1.4.10.0.10.5 = INTEGER: unknown(1)
    // LLDP-MIB::lldpRemManAddrIfSubtype.1173834000.4.6.0.6.132.181.156.89.235.128 = INTEGER: unknown(1)
    // LLDP-MIB::lldpRemManAddrIfId.1173570000.129.2.1.4.10.0.10.5 = INTEGER: 0
    // LLDP-MIB::lldpRemManAddrIfId.1173834000.4.6.0.6.132.181.156.89.235.128 = INTEGER: 0
    // LLDP-MIB::lldpRemManAddrOID.1173570000.129.2.1.4.10.0.10.5 = OID: SNMPv2-SMI::enterprises.14823.2.2.1.2.1.1
    // LLDP-MIB::lldpRemManAddrOID.1173834000.4.6.0.6.132.181.156.89.235.128 = OID: SNMPv2-SMI::enterprises.14823.2.2.1.2.1.7
    // Case 3:
    // LLDP-MIB::lldpRemManAddrIfId.0.2.1.1.4.10.137.41.4 = INTEGER: 0
    // LLDP-MIB::lldpRemManAddrIfId.0.3.1.1.4.10.137.41.57 = INTEGER: 2009
    // LLDP-MIB::lldpRemManAddrIfId.0.47.1.1.4.10.137.41.19 = INTEGER: 2009
    // LLDP-MIB::lldpRemManAddrIfId.0.49.3.1.4.10.129.2.171 = INTEGER: 34
    // LLDP-MIB::lldpRemManAddrIfId.0.49.3.2.16.42.2.32.40.255.0.0.0.0.0.0.1.0.0.1.113 = INTEGER: 34
    // LLDP-MIB::lldpRemManAddrIfId.0.49.4.1.4.10.129.2.171 = INTEGER: 19
    // LLDP-MIB::lldpRemManAddrIfId.0.49.4.2.16.42.2.32.40.255.0.0.0.0.0.0.1.0.0.1.113 = INTEGER: 19
    // LLDP-MIB::lldpRemManAddrIfId.0.53.2.1.4.10.129.2.171 = INTEGER: 23
    // LLDP-MIB::lldpRemManAddrIfId.0.53.2.2.16.42.2.32.40.255.0.0.0.0.0.0.1.0.0.1.113 = INTEGER: 23
    // Case 4:
    // LLDP-MIB::lldpRemManAddrSubtype.22 = INTEGER: ipV4(1)
    // LLDP-MIB::lldpRemManAddrSubtype.86 = INTEGER: ipV6(2)
    // LLDP-MIB::lldpRemManAddr.22 = STRING: "10.31.0.2"
    // LLDP-MIB::lldpRemManAddr.86 = STRING: "fe80::d6ca:6dff:fe8e:8b3f"
    // Case 5 (multiple IP addresses):
    // lldpRemManAddrIfId.31300.2.2.1.4.192.168.13.1 = 5
    // lldpRemManAddrIfId.31300.2.2.2.16.32.1.4.112.0.40.11.253.0.0.0.0.0.0.0.0 = 5
    // lldpRemManAddrIfId.31300.2.2.2.16.254.128.0.0.0.0.0.0.198.173.52.255.254.216.108.126 = 5
    $lldp_addr = snmpwalk_cache_oid($device, 'lldpRemManAddrIfId', [], "LLDP-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    $lldp_addr = snmpwalk_cache_oid($device, 'lldpRemManAddr', $lldp_addr, "LLDP-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

    foreach ($lldp_addr as $index => $entry) {
        if (isset($lldp_array[$index])) {
            $lldp_array[$index] = array_merge($lldp_array[$index], $entry);
            if (isset($entry['lldpRemManAddr'])) {
                $addr = hex2ip($entry['lldpRemManAddr']);
                $lldp_array[$index]['lldpRemMan'][$addr] = $entry; // For multiple entries
            }
            continue;
        }
        $index_array = explode('.', $index);
        // LLDP index
        $lldpRemTimeMark     = array_shift($index_array);
        $lldpRemLocalPortNum = array_shift($index_array);
        $lldpRemIndex        = array_shift($index_array);
        $lldp_index          = "$lldpRemTimeMark.$lldpRemLocalPortNum.$lldpRemIndex";
        if (!isset($lldp_array[$lldp_index])) {
            continue;
        }
        $lldp_array[$lldp_index] = array_merge($lldp_array[$lldp_index], $entry);

        // Already exist Oid, just merge
        // if (isset($entry['lldpRemManAddr'])) {
        //   continue;
        // }

        // Convert from index part
        $lldpAddressFamily = array_shift($index_array);
        if ($lldpAddressFamily == 1 && safe_count($index_array) === 4) {
            // Incorrect indexes
            // lldpRemManAddrIfId.7800.28.1.1.10.99.0.10 = 2
            // lldpRemManAddrIfId.32900.4.1.2.42.14.17.6 = 2
            $len = 4;
        } else {
            $len = array_shift($index_array);
        }
        $addr = implode('.', $index_array);
        if (isset($entry['lldpRemManAddr'])) {
            // Already exist Oid, just merge
            $addr = hex2ip($entry['lldpRemManAddr']);
            //continue;
        } elseif ($lldpAddressFamily == 1 || $len == 4) {
            // IPv4, ie: 4.10.129.2.171
            $lldp_array[$lldp_index]['lldpRemManAddr'] = $addr;
        } elseif ($lldpAddressFamily == 2 || $len == 16) {
            // IPv6, ie: 16.42.2.32.40.255.0.0.0.0.0.0.1.0.0.1.113
            $addr = snmp2ipv6($addr);
            $lldp_array[$lldp_index]['lldpRemManAddr'] = $addr;
        } elseif ($lldpAddressFamily == 0 && $len == 6) {
            // Hrm, I really not know what is this, ie, seems as MAC address:
            // 6 132.181.156.89.235.128 84:B5:9C:59:EB:80
            continue;
        }
        $lldp_array[$lldp_index]['lldpRemMan'][$addr] = $entry; // For multiple entries
    }

    // Clean IP, select best
    foreach ($lldp_array as $lldp_index => $lldp) {
        if (!isset($lldp['lldpRemMan'])) {
            continue;
        }
        $lldp['lldpRemMan'] = array_keys($lldp['lldpRemMan']);
        if (safe_count($lldp['lldpRemMan']) > 1) {
            // Multiple IP addresses.. detect best?
            foreach ($lldp['lldpRemMan'] as $addr) {
                $addr_version = get_ip_version($addr);
                $addr_type = get_ip_type($addr);
                if (in_array($addr_type, [ 'unspecified', 'loopback', 'reserved', 'multicast' ])) {
                    continue;
                }
                if ($addr_version == 6 && $addr_type === 'link-local') {
                    continue;
                }
                if ($addr_type === 'unicast') {
                    // Prefer IPv4/IPv6 unicast
                    $lldp_array[$lldp_index]['lldpRemManAddr'] = $addr;
                    break;
                }
                if ($addr_version == 4) {
                    // Than prefer IPv4
                    $lldp_array[$lldp_index]['lldpRemManAddr'] = $addr;
                    break;
                }
                $lldp_array[$lldp_index]['lldpRemManAddr'] = $addr;
            }
            print_debug("Multiple remote IP addresses detect, selected: $addr");
            print_debug_vars($lldp);
        }
        $lldp_array[$lldp_index]['lldpRemMan'] = $lldp['lldpRemMan'];
        if (isset($lldp_array[$lldp_index]['lldpRemManAddr'])) {
            $lldp_array[$lldp_index]['lldpRemManAddr'] = hex2ip($lldp_array[$lldp_index]['lldpRemManAddr']);
        }
    }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function check_valid_virtual_machines($device, $valid, $source)
{
    $entries = dbFetchRows("SELECT * FROM `vminfo` WHERE `device_id` = ? AND `vm_source` = ?", [$device['device_id'], $source]);

    if (is_array($entries) && count($entries)) {
        foreach ($entries as $entry) {
            $id   = $entry['vm_uuid'];
            $type = $entry['vm_type'];
            if (!$valid['vm'][$type][(string)$id]) {
                echo("-");
                print_debug("Virtual Machine deleted: $id -> $type");
                dbDelete('vminfo', "`vm_id` = ?", [$entry['vm_id']]);
                log_event("Virtual Machine deleted: " . $entry['name'] . " " . $entry['vm_type'] . " " . $entry['vm_uuid'], $device, 'virtualmachine', $entry['vm_uuid']);
            } else {
                echo('.');
            }
        }
    }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function discover_juniAtmVp(&$valid, $port_id, $vp_id, $vp_descr)
{
    global $config;

    //if (dbFetchCell("SELECT COUNT(*) FROM `juniAtmVp` WHERE `port_id` = ? AND `vp_id` = ?", array($port_id, $vp_id)) == "0")
    if (!dbExist('juniAtmVp', '`port_id` = ? AND `vp_id` = ?', [$port_id, $vp_id])) {
        $inserted = dbInsert(['port_id' => $port_id, 'vp_id' => $vp_id, 'vp_descr' => $vp_descr], 'juniAtmVp');

        //FIXME vv no $device in front of 'juniAtmVp' - will not log correctly!
        log_event("Juniper ATM VP Added: port $port_id vp $vp_id descr $vp_descr", 'juniAtmVp', $inserted);
    } else {
        echo('.');
    }
    $valid[$port_id][$vp_id] = 1;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function discover_neighbour($port, $protocol, $neighbour)
{
    print_debug("Discover neighbour: proto[" . $protocol . "], port_id[" . $port['port_id'] . "], " . print_r($neighbour, TRUE));

    if (!is_numeric($port['port_id'])) {
        print_debug("Local port unknown, skip neighbour.");
        return NULL;
    } //elseif (is_bad_xdp($neighbour['remote_hostname'], $neighbour['remote_platform']) || empty($neighbour['remote_hostname']))
    if (safe_empty($neighbour['remote_hostname']) || is_bad_xdp($neighbour['remote_hostname'])) {
        // Note in neighbour discovery, ignore only hostname!
        print_debug("Hostname ignored, skip neighbour.");
        return NULL;
    }

    // Autodiscovery id
    $hostname = $neighbour['remote_hostname'];
    safe_ip_hostname_key($hostname, $neighbour['remote_address']);
    $autodiscovery_entry           = get_autodiscovery_entry($hostname, $neighbour['remote_address'], $port['device_id']);
    $neighbour['autodiscovery_id'] = $autodiscovery_entry['autodiscovery_id'];

    $neighbour['protocol'] = $protocol;
    $neighbour['active']   = '1';

    // Get the remote device id if we've not been told it
    if (isset($neighbour['remote_port_id']) && safe_empty($neighbour['remote_device_id'])) {
        $neighbour['remote_device_id'] = get_device_id_by_port_id($neighbour['remote_port_id']);
    }

    $params       = ['protocol', 'remote_device_id', 'remote_port_id', 'remote_hostname', 'remote_port', 'remote_platform', 'remote_version', 'remote_address', 'autodiscovery_id', 'active'];
    $neighbour_db = dbFetchRow("SELECT `neighbours`.*, UNIX_TIMESTAMP(`last_change`) AS `last_change_unixtime` FROM `neighbours` WHERE `port_id` = ? AND `protocol` = ? AND `remote_hostname` = ? AND `remote_port` = ?", [$port['port_id'], $protocol, $neighbour['remote_hostname'], $neighbour['remote_port']]);
    if (!isset($neighbour_db['neighbour_id'])) {
        $update = ['port_id'   => $port['port_id'],
                   'device_id' => $port['device_id']];
        foreach ($params as $param) {
            $update[$param] = $neighbour[$param];
            if (is_null($neighbour[$param])) {
                $update[$param] = ['NULL'];
            }
        }
        // Last change as unixtime
        // FIXME. Need convert in db schema last_change to unixtime
        if (is_numeric($neighbour['last_change']) && $neighbour['last_change'] > OBS_MIN_UNIXTIME) {
            $update['last_change'] = ['FROM_UNIXTIME(' . $neighbour['last_change'] . ')'];
        }
        $id = dbInsert($update, 'neighbours');

        $GLOBALS['module_stats']['neighbours']['added']++; //echo('+');
    } else {
        $update = [];
        foreach ($params as $param) {
            if ($neighbour[$param] != $neighbour_db[$param]) {
                $update[$param] = $neighbour[$param];
                if (is_null($neighbour[$param])) {
                    $update[$param] = ['NULL'];
                }
            }
        }
        // Last change as unixtime
        // FIXME. Need convert in db schema last_change to unixtime
        if (is_numeric($neighbour['last_change']) && $neighbour['last_change'] > OBS_MIN_UNIXTIME &&
            abs($neighbour['last_change'] - $neighbour_db['last_change_unixtime']) > 90) {
            $update['last_change'] = ['FROM_UNIXTIME(' . $neighbour['last_change'] . ')'];
        }
        if (count($update)) {
            dbUpdate($update, 'neighbours', '`neighbour_id` = ?', [$neighbour_db['neighbour_id']]);
            $GLOBALS['module_stats']['neighbours']['updated']++; //echo('U');
        } else {
            $GLOBALS['module_stats']['neighbours']['unchanged']++; //echo('.');
        }
    }
    $valid_host_key = $neighbour['remote_hostname'];
    if (strlen($neighbour['remote_address'])) {
        $valid_host_key .= '-' . $neighbour['remote_address'];
    }
    $GLOBALS['valid']['neighbours'][$port['port_id']][$valid_host_key][$neighbour['remote_port']] = 1;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// FIXME don't pass valid, use this as a global variable
function discover_storage(&$valid, $device, $storage_index, $storage_type, $storage_mib, $storage_descr, $storage_units, $storage_size, $storage_used, $options = [])
{

    // options && limits
    $option  = 'storage_hc';
    $$option = isset($options[$option]) ? $options[$option] : 0;
    $option  = 'storage_ignore';
    $$option = isset($options[$option]) ? $options[$option] : 0;

    if (isset($options['limit_high'])) {
        $storage_crit_limit = $options['limit_high'];
    }
    if (isset($options['limit_high_warn'])) {
        $storage_warn_limit = $options['limit_high_warn'];
    }

    print_debug($device['device_id'] . " -> $storage_index, $storage_type, $storage_mib, $storage_descr, $storage_units, $storage_size, $storage_used, $storage_hc");

    //$storage_mib  = strtolower($storage_mib);

    // Check storage ignore filters
    if (entity_descr_check($storage_descr, 'storage')) {
        return FALSE;
    }
    //foreach ($config['ignore_mount'] as $bi)        { if (strcasecmp($bi, $storage_descr) == 0)   { print_debug("Skipped by equals: $bi, $storage_descr "); return FALSE; } }
    //foreach ($config['ignore_mount_string'] as $bi) { if (stripos($storage_descr, $bi) !== FALSE) { print_debug("Skipped by strpos: $bi, $storage_descr "); return FALSE; } }
    //foreach ($config['ignore_mount_regexp'] as $bi) { if (preg_match($bi, $storage_descr) > 0)    { print_debug("Skipped by regexp: $bi, $storage_descr "); return FALSE; } }
    // Search duplicates for same mib/descr
    if (in_array($storage_descr, array_values((array)$valid[$storage_mib]))) {
        print_debug("Skipped by already exist: $storage_descr ");
        return FALSE;
    }

    $params       = ['storage_index', 'storage_mib', 'storage_type', 'storage_descr', 'storage_hc',
                     'storage_ignore', 'storage_crit_limit', 'storage_warn_limit', 'storage_units'];
    $params_state = ['storage_size', 'storage_used', 'storage_free', 'storage_perc']; // This is changeable params, not required for update

    $device_id = $device['device_id'];

    $storage_db = dbFetchRow("SELECT * FROM `storage` WHERE `device_id` = ? AND `storage_index` = ? AND `storage_mib` = ?", [$device_id, $storage_index, $storage_mib]);
    if (!isset($storage_db['storage_id'])) {
        // FIXME. Ignore 0 storage size?
        $storage_free = $storage_size - $storage_used;
        $storage_perc = round(float_div($storage_used, $storage_size) * 100, 2);
        $update       = ['device_id' => $device_id];
        foreach (array_merge($params, $params_state) as $param) {
            $update[$param] = ($$param === NULL ? ['NULL'] : $$param);
        }
        $id = dbInsert($update, 'storage');

        //$update_state = array('storage_id' => $id);
        //foreach ($params_state as $param) { $update_state[$param] = $$param; }
        //dbInsert($update_state, 'storage-state');

        $GLOBALS['module_stats']['storage']['added']++; //echo('+');
        log_event("Storage added: index $storage_index, mib $storage_mib, descr $storage_descr", $device, 'storage', $id);
    } else {
        $update = [];
        foreach ($params as $param) {
            if ($$param != $storage_db[$param]) {
                $update[$param] = ($$param === NULL ? ['NULL'] : $$param);
            }
        }
        if (count($update)) {
            //if (isset($update['storage_descr']))
            //{
            //  // Rename storage rrds, because its filename based on description
            //  $old_rrd = $config['rrd_dir'] . '/' . $device['hostname'] . '/' . safename('storage-' . $storage_db['storage_mib'] . '-' . $storage_db['storage_descr'] . '.rrd');
            //  $new_rrd = $config['rrd_dir'] . '/' . $device['hostname'] . '/' . safename('storage-' . $storage_db['storage_mib'] . '-' . $storage_descr . '.rrd');
            //  if (is_file($old_rrd) && !is_file($new_rrd)) { rename($old_rrd, $new_rrd); print_warning("Moved RRD"); }
            //}

            dbUpdate($update, 'storage', '`storage_id` = ?', [$storage_db['storage_id']]);
            $GLOBALS['module_stats']['storage']['updated']++; //echo('U');
            log_event("Storage updated: index $storage_index, mib $storage_mib, descr $storage_descr", $device, 'storage', $storage_db['storage_id']);
        } else {
            $GLOBALS['module_stats']['storage']['unchanged']++; //echo('.');
        }
    }
    print_debug_vars($update);
    if ($storage_ignore) {
        $GLOBALS['module_stats']['storage']['ignored']++;
    }
    $valid[$storage_mib][$storage_index] = $storage_descr;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// FIXME don't pass valid, use this as a global variable
// FIXME move all the other stuff over to $options too
// $options accepts:
//  - table
function discover_mempool(&$valid, $device, $mempool_index, $mempool_mib, $mempool_descr, $mempool_multiplier, $mempool_total, $mempool_used, $mempool_hc = 0, $options = [])
{
    global $config;

    print_debug($device['device_id'] . " -> $mempool_index, $mempool_mib::" . $options['table'] . ", $mempool_descr, $mempool_multiplier, $mempool_total, $mempool_used");

    // Check mempool ignore filters
    if (entity_descr_check($mempool_descr, 'mempool')) {
        return FALSE;
    }
    //foreach ($config['ignore_mempool'] as $bi)        { if (strcasecmp($bi, $mempool_descr) == 0)   { print_debug("Skipped by equals: $bi, $mempool_descr "); return FALSE; } }
    //foreach ($config['ignore_mempool_string'] as $bi) { if (stripos($mempool_descr, $bi) !== FALSE) { print_debug("Skipped by strpos: $bi, $mempool_descr "); return FALSE; } }
    //foreach ($config['ignore_mempool_regexp'] as $bi) { if (preg_match($bi, $mempool_descr) > 0)    { print_debug("Skipped by regexp: $bi, $mempool_descr "); return FALSE; } }

    // Init $options -> regular variables until all others have been converted to be inside $options (FIXME)
    $param_opt = ['table' => 'mempool_table'];
    foreach ($param_opt as $key => $varname) {
        $$varname = $options[$key] ?: NULL;
    }

    // Limit mempool_multiplier input to fit the MySQL field (FLOAT(14,5))
    $mempool_multiplier = round($mempool_multiplier, 5);

    $params       = ['mempool_index', 'mempool_mib', 'mempool_descr', 'mempool_multiplier', 'mempool_hc', 'mempool_table'];
    $params_state = ['mempool_total', 'mempool_used', 'mempool_free', 'mempool_perc'];

    if (!is_numeric($mempool_total) || $mempool_total <= 0 || !is_numeric($mempool_used)) {
        print_debug("Skipped by not numeric or too small mempool total ($mempool_total) or used ($mempool_used)");
        return FALSE;
    }
    if (!$mempool_multiplier) {
        $mempool_multiplier = 1;
    }

    if (!(isset($options['table']) && isset($config['mibs'][$mempool_mib]['mempool'][$options['table']]))) {
        // Scale for non-definitions based
        $mempool_total *= $mempool_multiplier;
        $mempool_used  *= $mempool_multiplier;
    }
    $mempool_db = dbFetchRow("SELECT * FROM `mempools` WHERE `device_id` = ? AND `mempool_index` = ? AND `mempool_mib` = ?", [$device['device_id'], $mempool_index, $mempool_mib]);

    if (!isset($mempool_db['mempool_id'])) {
        $mempool_free = $mempool_total - $mempool_used;
        $mempool_perc = round(float_div($mempool_used, $mempool_total) * 100, 2);

        $update = ['device_id' => $device['device_id']];
        foreach ($params as $param) {
            $update[$param] = $$param;
        }
        foreach ($params_state as $param) {
            $update[$param] = $$param;
        }
        $update['mempool_polled'] = time();
        $id                       = dbInsert($update, 'mempools');

        //$update_state = array('mempool_id' => $id, 'mempool_polled' => time());
        //foreach ($params_state as $param) { $update_state[$param] = $$param; }
        //dbInsert($update_state, 'mempools-state');
        $GLOBALS['module_stats']['mempools']['added']++; //echo('+');
        log_event("Memory pool added: mib $mempool_mib" . ($mempool_table ? "::$mempool_table" : '') . ", index $mempool_index, descr $mempool_descr", $device, 'mempool', $id);
    } else {
        $update = [];
        foreach ($params as $param) {
            if ($$param != $mempool_db[$param]) {
                $update[$param] = $$param;
            }
        }
        if (count($update)) {
            dbUpdate($update, 'mempools', '`mempool_id` = ?', [$mempool_db['mempool_id']]);
            $GLOBALS['module_stats']['mempools']['updated']++; //echo('U');
            log_event("Memory pool updated: mib $mempool_mib" . ($mempool_table ? "::$mempool_table" : '') . ", index $mempool_index, descr $mempool_descr", $device, 'mempool', $mempool_db['mempool_id']);
        } else {
            $GLOBALS['module_stats']['mempools']['unchanged']++; //echo('.');
        }
    }

    $valid[$mempool_mib][$mempool_index] = 1;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// Valid params: description, type (fuser, toner, etc), index, oid, mib, level, capacity, capacity_oid, colour
function discover_printersupply($device, $options = [])
{

    // Check toner ignore filters
    if (entity_descr_check($options['description'], 'printersupply')) {
        return FALSE;
    }
    if (discovery_check_if_type_exist($options, 'printersupply')) {
        return FALSE;
    }
    //foreach ($config['ignore_toner'] as $bi)        { if (strcasecmp($bi, $options['description']) == 0)   { print_debug("Skipped by equals: $bi, " . $options['description']); return FALSE; } }
    //foreach ($config['ignore_toner_string'] as $bi) { if (stripos($options['description'], $bi) !== FALSE) { print_debug("Skipped by strpos: $bi, " . $options['description']); return FALSE; } }
    //foreach ($config['ignore_toner_regexp'] as $bi) { if (preg_match($bi, $options['description']) > 0)    { print_debug("Skipped by regexp: $bi, " . $options['description']); return FALSE; } }

    //if (dbFetchCell("SELECT COUNT(`supply_id`) FROM `printersupplies` WHERE `device_id` = ? AND `supply_index` = ?", array($device['device_id'], $options['index'])) == '0')
    if (!dbExist('printersupplies', '`device_id` = ? AND `supply_index` = ?', [$device['device_id'], $options['index']])) {
        $supply_insert = ['device_id'           => $device['device_id'],
                          'supply_index'        => $options['index'],
                          'supply_mib'          => $options['mib'],
                          'supply_descr'        => $options['description'],
                          'supply_capacity'     => $options['capacity'],
                          'supply_capacity_oid' => $options['capacity_oid'],
                          'supply_oid'          => $options['oid'],
                          'supply_value'        => $options['level'],
                          'supply_type'         => $options['type'],
                          'supply_colour'       => $options['colour']];
        $supply_id     = dbInsert($supply_insert, 'printersupplies');
        $GLOBALS['module_stats']['printersupplies']['added']++; //echo('+');
        log_event("Printer supply added: " . $options['description'] . " (" . nicecase($options['type']) . ", index " . $options['index'] . ")", $device, 'printersupply', $supply_id);
    } else {
        $supply = dbFetchRow("SELECT * FROM `printersupplies` WHERE `device_id` = ? AND `supply_index` = ?", [$device['device_id'], $options['index']]);

        $field_map = [
          'description'  => 'supply_descr',
          'type'         => 'supply_type',
          'mib'          => 'supply_mib',
          'colour'       => 'supply_colour',
          'capacity'     => 'supply_capacity',
          'oid'          => 'supply_oid',
          'capacity_oid' => 'supply_capacity_oid'
        ];

        $update = [];
        foreach ($field_map as $param => $db_param) {
            if ($options[$param] != $supply[$db_param]) {
                $update[$db_param] = $options[$param];
            }
        }

        if (count($update)) {
            dbUpdate($update, 'printersupplies', '`supply_id` = ?', [$supply['supply_id']]);
            $GLOBALS['module_stats']['printersupplies']['updated']++; //echo('U');
            log_event("Printer supply updated: " . $options['description'] . " (" . nicecase($options['type']) . ", index " . $options['index'] . ")", $device, 'printersupply', $supply['supply_id']);
        } else {
            $GLOBALS['module_stats']['printersupplies']['unchanged']++; //echo('.');
        }
    }

    $GLOBALS['valid']['printersupply'][$options['type']][$options['mib']][$options['index']] = 1;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function check_valid_printer_supplies($device)
{

    $valid = $GLOBALS['valid']['printersupply'];
    foreach (dbFetchRows("SELECT * FROM `printersupplies` WHERE `device_id` = ?", [$device['device_id']]) as $entry) {
        $index = $entry['supply_index'];
        $type  = $entry['supply_type'];
        $mib   = $entry['supply_mib'];
        if (!$valid[$type][$mib][(string)$index]) {
            $GLOBALS['module_stats']['printersupplies']['deleted']++; // echo("-");
            print_debug("Printer supply deleted: $index -> $mib -> $type");
            dbDelete('printersupplies', "`supply_id` = ?", [$entry['supply_id']]);
            log_event("Printer supply deleted: " . $entry['supply_descr'] . " (" . nicecase($entry['supply_type']) . ", index $index)", $device, 'toner', $entry['supply_id']);
        }
    }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function discover_inventory($device, $index, $inventory_tmp, $mib) {
    $entPhysical_oids = [
        'entPhysicalDescr', 'entPhysicalClass', 'entPhysicalName', 'entPhysicalHardwareRev',
        'entPhysicalFirmwareRev', 'entPhysicalSoftwareRev', 'entPhysicalAlias', 'entPhysicalAssetID',
        'entPhysicalIsFRU', 'entPhysicalModelName', 'entPhysicalVendorType', 'entPhysicalSerialNum',
        'entPhysicalContainedIn', 'entPhysicalParentRelPos', 'entPhysicalMfgName', 'ifIndex', 'inventory_mib'
    ];

    $numeric_oids = [ 'entPhysicalContainedIn', 'entPhysicalParentRelPos', 'ifIndex' ]; // DB type 'int'

    if (!is_array($inventory_tmp) || !is_numeric($index)) {
        return FALSE;
    }

    $inventory                      = [ 'entPhysicalIndex' => $index ];
    $inventory_tmp['inventory_mib'] = $mib;

    // Rewrites
    $rewrites = $GLOBALS['config']['rewrites']['inventory'];
    foreach ($entPhysical_oids as $oid) {
        $inventory[$oid] = str_replace(['"', "'"], '', snmp_fix_string($inventory_tmp[$oid]));

        if (isset($rewrites[$oid][$inventory[$oid]])) {
            $inventory[$oid] = $rewrites[$oid][$inventory[$oid]];
        }
    }
    if (!$inventory['entPhysicalModelName'] && isset($rewrites['entPhysicalVendorTypes'][$inventory['entPhysicalVendorType']])) {
        $inventory['entPhysicalModelName'] = $rewrites['entPhysicalVendorTypes'][$inventory['entPhysicalVendorType']];
    } elseif (!isset($inventory['entPhysicalModelName'])) {
        $inventory['entPhysicalModelName'] = $inventory['entPhysicalName'];
    }
    if (safe_empty($inventory['entPhysicalName']) && $inventory['entPhysicalDescr']) {
        // JunOS EVO return empty entPhysicalName
        $inventory['entPhysicalName'] = $inventory['entPhysicalDescr'];
    }

    $query        = 'SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalIndex` = ? AND `inventory_mib` = ?';
    $inventory_db = dbFetchRow($query, [ $device['device_id'], $index, $mib ]);
    if (!is_array($inventory_db)) {
        // Compatibility, try with empty mib name
        $inventory_db = dbFetchRow($query, [ $device['device_id'], $index, '' ]);
    }

    if (!is_array($inventory_db)) {
        $inventory['device_id'] = $device['device_id'];
        $id                     = dbInsert($inventory, 'entPhysical');
        print_debug('Inventory added: class ' . $inventory['entPhysicalClass'] . ', name ' . $inventory['entPhysicalName'] . ', index ' . $index);
        $GLOBALS['module_stats']['inventory']['added']++; //echo('+');
    } else {
        $update = [];
        foreach ($entPhysical_oids as $oid) {
            if ($inventory[$oid] != $inventory_db[$oid]) {
                if (in_array($oid, $numeric_oids) && $inventory[$oid] == '') {
                    $update[$oid] = ['NULL'];
                } else {
                    $update[$oid] = $inventory[$oid];
                }
            }
        }
        // Reset deleted date if not empty
        print_debug_vars($inventory_db);
        if (!empty($inventory_db['deleted'])) {
            $update['deleted'] = ['NULL'];
        }
        if (safe_count($update)) {
            print_debug_vars($update);
            $id = $inventory_db['entPhysical_id'];
            dbUpdate($update, 'entPhysical', '`entPhysical_id` = ?', [$id]);
            print_debug('Inventory updated: class ' . $inventory['entPhysicalClass'] . ', name ' . $inventory['entPhysicalName'] . ', index ' . $index);
            $GLOBALS['module_stats']['inventory']['updated']++;
        } else {
            $GLOBALS['module_stats']['inventory']['unchanged']++;
        }
    }

    $GLOBALS['valid']['inventory'][$mib][$index] = 1;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function check_valid_inventory($device) {

    $query   = 'SELECT * FROM `entPhysical` WHERE `device_id` = ?';
    $entries = dbFetchRows($query, [$device['device_id']]);

    foreach ($entries as $entry) {
        $index = $entry['entPhysicalIndex'];
        $mib   = $entry['inventory_mib'];

        //if (!$array[$index] && $entry['deleted'] == NULL)
        if (!isset($GLOBALS['valid']['inventory'][$mib][$index]) && empty($entry['deleted'])) {
            dbUpdate(['deleted' => ['NOW()']], 'entPhysical', "`entPhysical_id` = ?", [$entry['entPhysical_id']]);
            // dbDelete('entPhysical', "`entPhysical_id` = ?", array($entry['entPhysical_id']));
            print_debug('Inventory deleted: class ' . $entry['entPhysicalClass'] . ', name ' . $entry['entPhysicalName'] . ', index ' . $index);
            $GLOBALS['module_stats']['inventory']['deleted']++; //echo('-');
        }
    }

}

// DOCME needs phpdoc block
// TESTME needs unit testing
function is_bad_xdp($hostname, $platform = '')
{
    global $config;

    // Ignore Hostname string
    if (is_array($config['xdp']['ignore_hostname']) && str_contains_array($hostname, $config['xdp']['ignore_hostname'])) {
        return TRUE;
    }

    // Ignore Hostname regex
    if (is_array($config['xdp']['ignore_hostname_regex'])) {
        foreach ($config['xdp']['ignore_hostname_regex'] as $pattern) {
            if (preg_match($pattern, $hostname)) {
                return TRUE;
            }
        }
    }

    if (strlen($platform)) {
        // Ignore Platform string
        if (is_array($config['xdp']['ignore_platform']) &&
            str_icontains_array($platform, $config['xdp']['ignore_platform'])) {
            return TRUE;
        }
        // Ignore Platform regex
        if (is_array($config['xdp']['ignore_platform_regex'])) {
            foreach ($config['xdp']['ignore_platform_regex'] as $pattern) {
                if (preg_match($pattern, $platform)) {
                    return TRUE;
                }
            }
        }
    }

    return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// FIXME don't pass valid, use this as a global variable
function discover_lsp(&$valid, $device, $lsp_index, $lsp_mib, $lsp_name, $lsp_state, $lsp_uptime, $lsp_total_uptime, $lsp_primary_uptime, $lsp_proto,
                      $lsp_transitions, $lsp_path_changes, $lsp_bandwidth, $lsp_octets, $lsp_packets, $lsp_polled)
{

    print_debug($device['device_id'] . " -> $lsp_index, $lsp_mib, $lsp_name, $lsp_state, $lsp_uptime, $lsp_total_uptime, $lsp_primary_uptime, $lsp_proto, " .
                "$lsp_transitions, $lsp_path_changes, $lsp_bandwidth, $lsp_octets, $lsp_packets, $lsp_polled");

    $lsp_mib = strtolower($lsp_mib);

    // Check lsp ignore filters
    if (entity_descr_check($lsp_name, 'lsp')) {
        return FALSE;
    }
    //foreach ($config['ignore_lsp'] as $bi)        { if (strcasecmp($bi, $lsp_name) == 0)   { print_debug("Skipped by equals: $bi, $lsp_name "); return FALSE; } }
    //foreach ($config['ignore_lsp_string'] as $bi) { if (stripos($lsp_name, $bi) !== FALSE) { print_debug("Skipped by strpos: $bi, $lsp_name "); return FALSE; } }
    //foreach ($config['ignore_lsp_regexp'] as $bi) { if (preg_match($bi, $lsp_name) > 0)    { print_debug("Skipped by regexp: $bi, $lsp_name "); return FALSE; } }
    // Search duplicates for same mib/descr
    if (!safe_empty($valid[$lsp_mib]) && in_array($lsp_name, array_values($valid[$lsp_mib]))) {
        print_debug("Skipped by already exist: $lsp_name ");
        return FALSE;
    }

    $params       = ['lsp_index', 'lsp_mib', 'lsp_name', 'lsp_proto'];
    $params_state = ['lsp_polled', 'lsp_state', 'lsp_uptime', 'lsp_total_uptime', 'lsp_primary_uptime', 'lsp_transitions', 'lsp_path_changes',
                     'lsp_bandwidth', 'lsp_octets', 'lsp_packets']; // This is changable params, not required for update

    $device_id = $device['device_id'];

    $lsp_db = dbFetchRow("SELECT * FROM `lsp` WHERE `device_id` = ? AND `lsp_index` = ? AND `lsp_mib` = ?", [$device_id, $lsp_index, $lsp_mib]);
    if (!isset($lsp_db['lsp_id'])) {
        $update = ['device_id' => $device_id];
        foreach (array_merge($params, $params_state) as $param) {
            $update[$param] = ($$param === NULL ? ['NULL'] : $$param);
        }
        $id = dbInsert($update, 'lsp');

        $GLOBALS['module_stats']['lsp']['added']++;
        log_event("$lsp_proto LSP added: index $lsp_index, mib $lsp_mib, name $lsp_name", $device, 'lsp', $id);
    } else {
        $update = [];
        foreach ($params as $param) {
            if ($$param != $lsp_db[$param]) {
                $update[$param] = ($$param === NULL ? ['NULL'] : $$param);
            }
        }
        if (count($update)) {
            dbUpdate($update, 'lsp', '`lsp_id` = ?', [$lsp_db['lsp_id']]);
            $GLOBALS['module_stats']['lsp']['updated']++;
            log_event("$lsp_proto LSP updated: index $lsp_index, mib $lsp_mib, name $lsp_name", $device, 'lsp', $lsp_db['lsp_id']);
        } else {
            $GLOBALS['module_stats']['lsp']['unchanged']++;
        }
    }
    print_debug_vars($update);
    $valid[$lsp_mib][$lsp_index] = $lsp_name;
}

/**
 * Process a single node from the lshw output and discover its inventory.
 *
 * This function processes a single node from the lshw output, creates an
 * inventory entry from the node's attributes, and then calls the
 * discover_inventory() function to add or update the entry in the database.
 * It also processes any child nodes in a recursive manner.
 *
 * FIXME : This can probably be massively extended and improved. This just a PoC.
 *         entPhysical table should be generified and more fields added.
 *
 * @param array $device       The device for which the inventory is being processed.
 * @param array $node         The lshw node to process.
 * @param int   $parent_index The index of the parent node (default: 0).
 */
function process_lshw_node($device, $node, $parent_index = 0)
{
    $mib = 'lshw';

    $inventory_tmp = [
      'entPhysicalDescr'       => $node['description'] ?? '',
      'entPhysicalClass'       => $node['class'] ?? '',
      'entPhysicalName'        => $node['product'] ?? '',
      'entPhysicalModelName'   => $node['version'] ?? '',
      'entPhysicalSerialNum'   => $node['serial'] ?? '',
      'entPhysicalContainedIn' => $parent_index,
      'entPhysicalHardwareRev' => $node['configuration']['version'] ?? '',
      'entPhysicalFirmwareRev' => $node['configuration']['firmware'] ?? '',
      'entPhysicalSoftwareRev' => $node['configuration']['driver'] ?? '',
      //'entPhysicalAlias' => $node['logicalname'] ?? '',
      'entPhysicalAssetID'     => $node['physid'] ?? '',
      'entPhysicalIsFRU'       => (isset($node['claimed']) && $node['claimed'] === 'true') ? 1 : 2,
      'entPhysicalMfgName'     => $node['vendor'] ?? '',
      //'entPhysicalParentRelPos' => $node['slot'] ?? '',
    ];

    // Generate a unique, reproducible ID for the node
    $unique_str = implode('|', [
      $node['id'] ?? '',
      $node['description'] ?? '',
      $node['class'] ?? '',
      $node['product'] ?? '',
      $node['version'] ?? '',
    ]);
    $node_id    = crc32($unique_str);

    //print_vars($node);
    //print_vars($inventory_tmp);

    discover_inventory($device, $node_id, $inventory_tmp, $mib);

    echo ".";

    if (isset($node['children'])) {
        foreach ($node['children'] as $child) {
            process_lshw_node($device, $child, $node_id);
        }
    }
}

// EOF
