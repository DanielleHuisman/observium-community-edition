<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

function process_port_label(&$this_port, $device) {
    global $config;

    //file_put_contents('/tmp/process_port_label_'.$device['hostname'].'_'.$this_port['ifIndex'].'.port', var_export($this_port, TRUE)); ///DEBUG

    // OS Specific rewrites (get your shit together, vendors)
    if ($device['os'] === 'zxr10') {
        $this_port['ifAlias'] = preg_replace("/^" . str_replace("/", "\\/", $this_port['ifName']) . "\s*/", '', $this_port['ifDescr']);
    } elseif ($device['os'] === 'ciscosb' && $this_port['ifType'] === 'propVirtual' && is_numeric($this_port['ifDescr'])) {
        $this_port['ifName'] = 'Vlan' . $this_port['ifDescr'];
    }

    // Will copy ifDescr -> ifAlias if ifDescr != ifName (Actually for Brocade NOS and Allied Telesys devices)
    if (isset($config['os'][$device['os']]['ifDescr_ifAlias']) && $config['os'][$device['os']]['ifDescr_ifAlias'] &&
        $this_port['ifDescr'] !== $this_port['ifName'] && $this_port['ifDescr'] !== '-' &&
        in_array($this_port['ifType'], ['ethernetCsmacd', 'opticalTransport', 'opticalChannel', 'ip'], TRUE) &&
        !str_starts($this_port['ifDescr'], ['Allied Teles', 'No description configured'])) {

        if (safe_empty($this_port['ifAlias']) || $this_port['ifName'] === $this_port['ifAlias']) {
            $this_port['ifAlias'] = $this_port['ifDescr'];
        }
    }

    // Write port_label, port_label_base and port_label_num

    // Here definition overrides for ifDescr, because Calix switches ifDescr <> ifName since fw 2.2
    // Note, only for 'calix' os now
    if ($device['os'] === 'calix') {
        unset($config['os'][$device['os']]['ifname']);
        $version_parts = explode('.', $device['version']);
        if ($version_parts[0] > 2 || ($version_parts[0] == 2 && $version_parts[1] > 1)) {
            if (safe_empty($this_port['ifName'])) {
                $this_port['port_label'] = $this_port['ifDescr'];
            } else {
                $this_port['port_label'] = $this_port['ifName'];
            }
        }
    }

    // This happens on some liebert UPS devices or when a device has memory leak (i.e. Eaton Powerware)
    if (isset($config['os'][$device['os']]['ifType_ifDescr']) && $config['os'][$device['os']]['ifType_ifDescr'] && $this_port['ifIndex']) {
        $len  = strlen($this_port['ifDescr']);
        $type = rewrite_iftype($this_port['ifType']);
        if ($type && ($len === 0 || $len > 255 ||
                      is_hex_string($this_port['ifDescr']) ||
                      preg_match('/(.)\1{4,}/', $this_port['ifDescr']))) {
            $this_port['ifDescr'] = $type . ' ' . $this_port['ifIndex'];
            print_debug("Port 'ifDescr' rewritten: '' -> '" . $this_port['ifDescr'] . "'");
        }
    }

    if (isset($config['os'][$device['os']]['ifname'])) {
        if (safe_empty($this_port['ifName'])) {
            $this_port['port_label'] = $this_port['ifDescr'];
        } else {
            $this_port['port_label'] = $this_port['ifName'];
        }
    } elseif (isset($config['os'][$device['os']]['ifalias'])) {
        $this_port['port_label'] = $this_port['ifAlias'];
    } else {
        if ($this_port['ifDescr'] === '' && $this_port['ifName'] !== '') {
            // Some new NX-OS have empty ifDescr
            $this_port['port_label'] = $this_port['ifName'];
        } else {
            $this_port['port_label'] = $this_port['ifDescr'];
        }
        if (isset($config['os'][$device['os']]['ifindex'])) {
            $this_port['port_label'] .= ' ' . $this_port['ifIndex'];
        }
    }

    // Process label by os definition rewrites
    if (!process_port_label_def($this_port, $device)) {
        // Common port name rewrites (do not escape)
        $this_port['port_label'] = rewrite_ifname($this_port['port_label'], FALSE);
    }

    process_port_label_matches($this_port, $device);

    // Make a short version (do not escape)
    if (isset($this_port['port_label_short'])) {
        // Short already parsed from definitions (not sure if need additional shorting)
        $this_port['port_label_short'] = short_ifname($this_port['port_label_short'], NULL, FALSE);
    } else {
        $this_port['port_label_short'] = short_ifname($this_port['port_label'], NULL, FALSE);
    }

    // Set entity variables for use by code which uses entities
    // Base label part: TenGigabitEthernet3/3 -> TenGigabitEthernet, GigabitEthernet4/8.722 -> GigabitEthernet, Vlan2603 -> Vlan
    //$port['port_label_base'] = preg_replace('/^([A-Za-z ]*).*/', '$1', $port['port_label']);
    //$port['port_label_num']  = substr($port['port_label'], strlen($port['port_label_base'])); // Second label part
    //
    //  // Index example for TenGigabitEthernet3/10.324:
    //  //  $ports_links['Ethernet'][] = array('label_base' => 'TenGigabitEthernet', 'label_num0' => '3', 'label_num1' => '10', 'label_num2' => '324')
    //  $label_num  = preg_replace('![^\d\.\/]!', '', substr($data['port_label'], strlen($data['port_label_base']))); // Remove base part and all not-numeric chars
    //  preg_match('!^(\d+)(?:\/(\d+)(?:\.(\d+))*)*!', $label_num, $label_nums); // Split by slash and point (1/1.324)
    //  $ports_links[$data['human_type']][$data['ifIndex']] = array(
    //    'label'      => $data['port_label'],
    //    'label_base' => $data['port_label_base'],
    //    'label_num0' => $label_nums[0],
    //    'label_num1' => $label_nums[1],
    //    'label_num2' => $label_nums[2],
    //    'link'       => generate_port_link($data, $data['port_label_short'])
    //  );

    return TRUE;
}

function process_port_speed(&$this_port, $device, $port = []) {

    if (isset($port['ifSpeed_custom']) && $port['ifSpeed_custom'] > 0) {
        // Custom ifSpeed from WebUI
        $this_port['ifSpeed'] = (int)$port['ifSpeed_custom'];
        print_debug('Port ifSpeed manually set.');
    } else {
        // Detect port speed by ifHighSpeed
        if (is_numeric($this_port['ifHighSpeed'])) {
            // Use old ifHighSpeed if current speed '0', seems as some error on device
            if ($this_port['ifHighSpeed'] == '0' && $port['ifHighSpeed'] > '0') {
                $this_port['ifHighSpeed'] = $port['ifHighSpeed'];
                print_debug('Port ifHighSpeed fixed from zero.');
            }

            if ((int)$this_port['ifHighSpeed'] === (int)$this_port['ifSpeed'] && $this_port['ifHighSpeed'] >= 1000000) {
                // https://jira.observium.org/browse/OBS-4715
                // ifSpeed same as ifHighSpeed
                $this_port['ifHighSpeed'] = (int)($this_port['ifSpeed'] / 1000000);
                print_debug('Port ifHighSpeed fixed from same ifSpeed.');
            } else {

                // Maximum possible ifSpeed value is 4294967295
                // Overwrite ifSpeed with ifHighSpeed if it's over 4G or ifSpeed equals to zero
                // ifSpeed is more accurate for low speeds (ie: ifSpeed.60 = 1536000, ifHighSpeed.60 = 2)
                // other case when (incorrect ifSpeed): ifSpeed.6 = 1000, ifHighSpeed.6 = 1000)
                $ifSpeed_max = max($this_port['ifHighSpeed'] * 1000000, $this_port['ifSpeed']);
                if ($this_port['ifHighSpeed'] > 0 &&
                    ($ifSpeed_max > 4000000000 || $this_port['ifSpeed'] == 0 ||
                        $this_port['ifSpeed'] == $this_port['ifHighSpeed'])) {
                    // echo("HighSpeed, ");
                    $this_port['ifSpeed'] = $ifSpeed_max;
                }
            }
        }
        if ($this_port['ifSpeed'] == '0' && $port['ifSpeed'] > '0') {
            // Use old ifSpeed if current speed '0', seems as some error on device
            $this_port['ifSpeed'] = $port['ifSpeed'];
            print_debug('Port ifSpeed fixed from zero.');
        }
    }
}

function process_port_label_def(&$this_port, $device) {
    global $config;

    // Process label by os definition rewrites
    $oid = 'port_label';
    if (!isset($config['os'][$device['os']][$oid])) {
        // No definitions for port label
        return FALSE;
    }

    $this_port['port_label'] = preg_replace('/\ {2,}/', ' ', $this_port['port_label']); // clear two and more spaces

    $oid_base  = $oid . '_base';
    $oid_num   = $oid . '_num';
    $oid_short = $oid . '_short';
    foreach ($config['os'][$device['os']][$oid] as $pattern) {
        if (preg_match($pattern, $this_port[$oid], $matches)) {
            //print_debug_vars($matches);
            if (isset($matches[$oid])) {
                // if exist 'port_label' match reference
                $this_port[$oid] = $matches[$oid];
            } else {
                // or just first reference
                $this_port[$oid] = $matches[1];
            }
            print_debug("Port '$oid' rewritten: '" . $this_port[$oid] . "' -> '" . $this_port[$oid] . "'");

            if (isset($matches[$oid_base])) {
                $this_port[$oid_base] = $matches[$oid_base];
            }
            if (isset($matches[$oid_num])) {
                if ($device['os'] === 'cisco-altiga' && $matches[$oid_num] === '') { // This derp only for altiga (I hope so)
                    // See cisco-altiga os definition
                    // If port_label_num match set, but it empty, use ifIndex as num
                    $this_port[$oid_num] = $this_port['ifIndex'];
                    $this_port[$oid]     .= $this_port['ifIndex'];
                } else {
                    $this_port[$oid_num] = $matches[$oid_num];
                }
            }

            // Additionally, possible to parse port_label_short
            if (isset($matches[$oid_short])) {
                $this_port[$oid_short] = $matches[$oid_short];
            }

            // Additionally, possible to parse ifAlias from ifDescr (i.e. timos)
            if (isset($matches['ifAlias'])) {
                $this_port['ifAlias'] = $matches['ifAlias'];
            }
            break;
        }
    }

    return TRUE; // Definitions exist
}

function process_port_label_matches(&$this_port, $device) {
    if (isset($this_port['port_label_base'])) {
        // skip when already set by previous processing, ie os definitions
        return FALSE;
    }

    // Extract bracket part from port label and remove it
    $label_bracket = '';
    if (preg_match('/\s*(\([^\)]+\))$/', $this_port['port_label'], $matches)) {
        // GigaVUE-212 Port  8/48 (Network Port)
        // rtif(172.20.30.46/28)
        print_debug('Port label (' . $this_port['port_label'] . ') matched #1'); // Just for find issues
        $label_bracket = $this_port['port_label'];                               // fallback
        $this_port['port_label'] = explode($matches[0], $this_port['port_label'], 2)[0];
    } elseif (preg_match('!^10*(?:/10*)*\s*[MGT]Bit\s+(.*)!i', $this_port['port_label'], $matches)) {
        // remove 10/100 Mbit part from beginning, this broke detect label_base/label_num (see hirschmann-switch os)
        // 10/100 MBit Ethernet Switch Interface 6
        // 1 MBit Ethernet Switch Interface 6
        print_debug('Port label (' . $this_port['port_label'] . ') matched #2');                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             // Just for find issues
        $label_bracket           = $this_port['port_label'];                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 // fallback
        $this_port['port_label'] = $matches[1];
    } elseif (preg_match('/^(.+)\s*:\s+(.+)/', $this_port['port_label'], $matches)) {
        // Another case with colon
        // gigabitEthernet 1/0/24 : copper
        // port 3: Gigabit Fiber
        print_debug('Port label (' . $this_port['port_label'] . ') matched #3');                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             // Just for find issues
        $label_bracket           = $this_port['port_label'];                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 // fallback
        $this_port['port_label'] = $matches[1];
    }

    // Detect port_label_base and port_label_num
    //if (preg_match('/\d+(?:(?:[\/:](?:[a-z])?[\d\.:]+)+[a-z\d\.\:]*(?:[\-\_][\w\.\:]+)*|\/\w+$)/i', $this_port['port_label'], $matches))
    if (preg_match('/\d+((?<periodic>(?:[\/:]([a-z]*\d+|[a-z]+[a-z0-9\-\_]*)(?:\.\d+)?)+)(?<last>[\-\_\.][\w\.\:]+)*|\/\w+$)/i', $this_port['port_label'], $matches)) {
        // Multipart numeric
        /*
        1/1/1
        e1-0/0/1.0
        e1-0/2/0:13.0
        dwdm0/1/0/6
        DTI1/1/0
        Cable8/1/4-upstream2
        Cable8/1/4
        16GigabitEthernet1/2/1
        cau4-0/2/0
        dot11radio0/0
        Dialer0/0.1
        Downstream 0/2/0
        ControlEthernet0/RSP0/CPU0/S0/10
        1000BaseTX Port 8/48 Name
        Backplane-GigabitEthernet0/3
        Ethernet1/10
        FC port 0/19
        GigabitEthernet0/0/0/1
        GigabitEthernet0/1.ServiceInstance.206
        Integrated-Cable7/0/0:0
        Logical Upstream Channel 1/0.0/0
        Slot0/1
        sonet_12/1
        GigaVUE-212 Port  8/48 (Network Port)
        Stacking Port 1/StackA
        gigabitEthernet 1/0/24 : copper
        1:38
        1/4/x24, mx480-xe-0-0-0
        1/4/x24
        5/1/lns-net
        */
        //if (str_starts_with($this_port['port_label'], 'veth')) {
        //    print_cli(PHP_EOL.'Port label (' . $this_port['port_label'] . ') matched #multipart'.PHP_EOL);
        //}
        print_debug('Port label (' . $this_port['port_label'] . ') matched #multipart'); // Just for find issues
        $this_port['port_label_num']  = $matches[0];
        $this_port['port_label_base'] = explode($matches[0], $this_port['port_label'], 2)[0];
        $this_port['port_label']      = $this_port['port_label_base'] . $this_port['port_label_num']; // Remove additional part (after port number)
    } elseif (preg_match('/^(?<port_label_base>veth)(?<port_label_num>[a-f\d]{6,})$/i', $this_port['port_label'], $matches)) {
        // Hexified
        /*
         vethfd3fe3c0
         veth1bbfdc5
        */
        //if (str_starts_with($this_port['port_label'], 'veth')) {
        //    print_cli(PHP_EOL.'Port label (' . $this_port['port_label'] . ') matched #hex'.PHP_EOL);
        //}
        print_debug('Port label (' . $this_port['port_label'] . ') matched #hex'); // Just for find issues
        $this_port['port_label_base'] = $matches['port_label_base'];
        $this_port['port_label_num']  = $matches['port_label_num'];
    } elseif (preg_match('/(?<port_label_num>(?:\d+[a-z])?\d[\d\.\:]*(?:[\-\_]\w+)?)(?: [a-z()\[\] ]+)?$/i', $this_port['port_label'], $matches)) {
        // Simple numeric
        /*
        GigaVUE-212 Port  1 (Network Port)
        MMC-A s3 SW Port
        Atm0_Physical_Interface
        wan1_phys
        fwbr101i0
        Nortel Ethernet Switch 325-24G Module - Port 1
        lo0.32768
        vlan.818
        jsrv.1
        Bundle-Ether1.1701
        Ethernet1
        ethernet_13
        eth0
        eth0.101
        BVI900
        A/1
        e1
        CATV-MAC 1
        16
        */
        //if (str_starts_with($this_port['port_label'], 'veth')) {
        //    print_cli(PHP_EOL.'Port label (' . $this_port['port_label'] . ') matched #simple'.PHP_EOL);
        //}
        print_debug('Port label (' . $this_port['port_label'] . ') matched #simple'); // Just for find issues
        $this_port['port_label_num']  = $matches['port_label_num'];
        $this_port['port_label_base'] = substr($this_port['port_label'], 0, 0 - strlen($matches[0]));
        $this_port['port_label']      = $this_port['port_label_base'] . $this_port['port_label_num']; // Remove additional part (after port number)
    } else {
        // All other (non-numeric)
        /*
        UniPing Server Solution v3/SMS Enet Port
        MMC-A s2 SW Port
        Control Plane
        */
        $this_port['port_label_base'] = $this_port['port_label'];
    }

    // When not empty label brackets and empty numeric part, re-add brackets to label
    if (!empty($label_bracket) && safe_empty($this_port['port_label_num'])) {
        // rtif(172.20.30.46/28)
        $this_port['port_label']      = $label_bracket;
        $this_port['port_label_base'] = $this_port['port_label'];
        $this_port['port_label_num']  = '';
    }

    return TRUE;
}

// Get port id  by ip address (using cache)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_id_by_ip_cache($device, $ip)
{
    global $cache;

    $ip_version = get_ip_version($ip);

    if (is_array($device) && isset($device['device_id'])) {
        $device_id = $device['device_id'];
    } elseif (is_numeric($device)) {
        $device_id = $device;
    }
    if (!isset($device_id) || !$ip_version) {
        print_error("Invalid arguments passed into function get_port_id_by_ip_cache().");
        return FALSE;
    }

    $ip = ip_uncompress($ip);

    if (isset($cache['port_ip'][$device_id][$ip])) {
        return $cache['port_ip'][$device_id][$ip];
    }

    $ips = dbFetchRows('SELECT `port_id`, `ifOperStatus`, `ifAdminStatus` FROM `ipv' . $ip_version . '_addresses`
                      LEFT JOIN `ports` USING(`port_id`)
                      WHERE `deleted` = 0 AND `device_id` = ? AND `ipv' . $ip_version . '_address` = ?', [$device_id, $ip]);
    if (safe_count($ips) === 1) {
        // Simple
        $port = current($ips);
        //return $port['port_id'];
    } else {
        foreach ($ips as $entry) {
            if ($entry['ifAdminStatus'] === 'up' && $entry['ifOperStatus'] === 'up') {
                // First UP entry
                $port = $entry;
                break;
            } elseif ($entry['ifAdminStatus'] === 'up') {
                // Admin up, but port down or other state
                $ips_up[] = $entry;
            } else {
                // Admin down
                $ips_down[] = $entry;
            }
        }
        if (!isset($port)) {
            if ($ips_up) {
                $port = current($ips_up);
            } else {
                $port = current($ips_down);
            }
        }
    }
    $cache['port_ip'][$device_id][$ip] = $port['port_id'] ?: FALSE;

    return $cache['port_ip'][$device_id][$ip];
}

/**
 * This array used by html_highlight()
 * @param $device
 *
 * @return void
 */
function ports_links_cache($device) {
    global $cache;
    
    // Create entity links arrays
    if (!isset($cache['entity_links']['ports'])) {
        $cache['entity_links']['ports'] = [];
    }
    $ports_links = &$cache['entity_links']['ports'];

    // Highlight port links
    if (isset($ports_links[$device['device_id']])) {
        return;
    }
    if (!isset($device['os'])) {
        // Need os field.
        $device = device_by_id_cache($device['device_id']);
    }

    $ports_links[$device['device_id']] = [];

    $sql = 'SELECT `port_id`, `port_label_short`, `port_label_base`, `port_label_num`, `ifDescr`, `ifName` FROM `ports` WHERE `device_id` = ? AND `deleted` = ?';

    foreach (dbFetchRows($sql, [ $device['device_id'], 0 ]) as $port_descr) {
        $search = [ $port_descr['ifDescr'], $port_descr['ifName'], $port_descr['port_label_short'] ];

        // FIXME. OS specific hacks.
        if (preg_match('/\s(port\s*\d.*)/i', $port_descr['ifDescr'], $matches)) {
            // Hack for Extreme (should make universal with lots of examples), see:
            // https://jira.observium.org/browse/OBS-3304
            $search[] = $matches[1];
        } elseif (($device['os'] === 'nos' || $device['os'] === 'slx') &&
                  !safe_empty($port_descr['port_label_base']) && str_contains($port_descr['port_label_num'], '/')) {
            // Brocade NOS derp interfaces with rbridge ids, ie:
            // TenGigabitEthernet 22/0/20 or Te 22/0/20 -> TenGigabitEthernet 0/20
            $search[] = $port_descr['port_label_base'] . '\d+/' . $port_descr['port_label_num'];
            // and short
            $search[] = short_ifname($port_descr['port_label_base'] . '\d+/' . $port_descr['port_label_num']);
        } elseif (str_starts_with($device['os'], 'dlink') && str_contains($port_descr['port_label_num'], '/')) {
            // D-Link derp syslog ports associations, ie:
            // 1/21 - Port <1:21>
            $search[] = 'Port <' . str_replace('/', ':', $port_descr['port_label_num']) . '>';
        }

        $ports_links[$device['device_id']][$port_descr['port_id']] = [
            'search'  => $search,
            'replace' => generate_entity_link('port', $port_descr['port_id'], '$2')
        ];
    }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_id_by_mac($device, $mac)
{
    if (is_array($device) && isset($device['device_id'])) {
        $device_id = $device['device_id'];
    } elseif (is_numeric($device)) {
        $device_id = $device;
    } else {
        return FALSE;
    }

    $remote_mac = mac_zeropad($mac);
    if ($remote_mac && $remote_mac !== '000000000000' &&
        $ids = dbFetchColumn("SELECT `port_id` FROM `ports` WHERE `ifPhysAddress` = ? AND `device_id` = ? AND `deleted` = ?", [$remote_mac, $device_id, 0])) {
        if (count($ids) > 1) {
            print_debug("WARNING. Found multiple ports [" . count($ids) . "] with same MAC address $mac on device ($device_id).");
        }

        return $ids[0];
        //return dbFetchCell("SELECT `port_id` FROM `ports` WHERE `deleted` = '0' AND `ifPhysAddress` = ? AND `device_id` = ? LIMIT 1", [ $remote_mac, $device_id ]);
    }

    return FALSE;
}

function get_port_by_ent_index($device, $entPhysicalIndex, $allow_snmp = FALSE)
{
    $mib = 'ENTITY-MIB';
    if (!is_numeric($entPhysicalIndex) ||
        !is_numeric($device['device_id']) ||
        !is_device_mib($device, $mib)) {
        return FALSE;
    }

    $allow_snmp = $allow_snmp || is_cli(); // Allow snmpwalk queries in poller/discovery or if in wui passed TRUE!

    if (isset($GLOBALS['cache']['snmp'][$mib][$device['device_id']])) {
        // Cached
        $entity_array = $GLOBALS['cache']['snmp'][$mib][$device['device_id']];
        if (safe_empty($entity_array)) {
            // Force DB queries
            $allow_snmp = FALSE;
        }
    } elseif ($allow_snmp) {
        // Inventory module disabled, this DB empty, try to cache
        $entity_array = [];
        $oids         = ['entPhysicalDescr', 'entPhysicalName', 'entPhysicalClass', 'entPhysicalContainedIn', 'entPhysicalParentRelPos'];
        if (is_device_mib($device, 'ARISTA-ENTITY-SENSOR-MIB')) {
            $oids[] = 'entPhysicalAlias';
        }
        foreach ($oids as $oid) {
            $entity_array = snmpwalk_cache_oid($device, $oid, $entity_array, snmp_mib_entity_vendortype($device, 'ENTITY-MIB'));
            if (!snmp_status()) {
                break;
            }
        }
        $entity_array = snmpwalk_cache_twopart_oid($device, 'entAliasMappingIdentifier', $entity_array, 'ENTITY-MIB:IF-MIB');
        if (safe_empty($entity_array)) {
            // Force DB queries
            $allow_snmp = FALSE;
        }
        $GLOBALS['cache']['snmp'][$mib][$device['device_id']] = $entity_array;
    } else {
        // Or try to use DB
    }

    //print_debug_vars($entity_array);
    $sensor_index = $entPhysicalIndex;     // Initial ifIndex
    $sensor_name  = '';
    do {
        if ($allow_snmp) {
            // SNMP (discovery)
            $sensor_port = $entity_array[$sensor_index];
        } else {
            // DB (web)
            $sensor_port = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalIndex` = ? AND `deleted` IS NULL', [$device['device_id'], $sensor_index]);
        }
        print_debug_vars($sensor_index, 1);
        print_debug_vars($sensor_port, 1);

        if ($sensor_port['entPhysicalClass'] === 'sensor') {
            // Need to store initial sensor name, for multi-lane ports
            $sensor_name = $sensor_port['entPhysicalName'];
        }

        if ($sensor_port['entPhysicalClass'] === 'port') {
            // Port found, get mapped ifIndex
            unset($entAliasMappingIdentifier);
            foreach ([0, 1, 2] as $i) {
                if (isset($sensor_port[$i]['entAliasMappingIdentifier'])) {
                    $entAliasMappingIdentifier = $sensor_port[$i]['entAliasMappingIdentifier'];
                    break;
                }
            }

            if (isset($entAliasMappingIdentifier) && str_contains($entAliasMappingIdentifier, 'fIndex')) {
                [, $ifIndex] = explode('.', $entAliasMappingIdentifier);

                $port = get_port_by_index_cache($device['device_id'], $ifIndex);
                if (is_array($port)) {
                    // Hola, port really found
                    print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port['port_id']);
                    return $port;
                }
            } elseif (!$allow_snmp && $sensor_port['ifIndex']) {
                // ifIndex already stored by inventory module
                $ifIndex = $sensor_port['ifIndex'];
                $port    = get_port_by_index_cache($device['device_id'], $ifIndex);
                print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port['port_id']);
                return $port;
            } else {
                // This is another case for Cisco IOSXR, when have incorrect entAliasMappingIdentifier association,
                // https://jira.observium.org/browse/OBS-3654
                $port_id = get_port_id_by_ifDescr($device['device_id'], $sensor_port['entPhysicalName']);
                if (is_numeric($port_id)) {
                    // Hola, port really found
                    $port    = get_port_by_id($port_id);
                    $ifIndex = $port['ifIndex'];
                    print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port_id);

                    return $port;
                }
            }

            break; // Exit do-while
        } elseif ($device['os'] === 'arista_eos' &&
                  $sensor_port['entPhysicalClass'] === 'container' && !safe_empty($sensor_port['entPhysicalAlias'])) {
            // Arista not have entAliasMappingIdentifier, but used entPhysicalAlias as ifDescr
            $port_id = get_port_id_by_ifDescr($device['device_id'], $sensor_port['entPhysicalAlias']);
            if (is_numeric($port_id)) {
                // Hola, port really found
                $port    = get_port_by_id($port_id);
                $ifIndex = $port['ifIndex'];
                print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port_id);
                return $port; // Exit do-while
            }
            if ($port_id = get_port_id_by_ifDescr($device['device_id'], $sensor_port['entPhysicalAlias'] . '/1')) {
                // Multi-lane Tranceivers
                $port                     = get_port_by_id($port_id);
                $port['sensor_multilane'] = TRUE;
                $ifIndex                  = $port['ifIndex'];
                print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port_id);
                return $port; // Exit do-while
            }
            $sensor_index = $sensor_port['entPhysicalContainedIn']; // Next ifIndex
        } elseif ($sensor_index == $sensor_port['entPhysicalContainedIn']) {
            break; // Break if current index same as next to avoid loop
        } elseif ($sensor_port['entPhysicalClass'] === 'module' &&
                  (isset($sensor_port[0]['entAliasMappingIdentifier']) ||
                   isset($sensor_port[1]['entAliasMappingIdentifier']) ||
                   isset($sensor_port[2]['entAliasMappingIdentifier']))) {
            // Cisco IOSXR 6.5.x ASR 9900 platform && NCS 5500
            $sensor_index = $sensor_port['entPhysicalContainedIn']; // Next ifIndex

            // By first try if entAliasMappingIdentifier correct
            unset($entAliasMappingIdentifier);
            foreach ([0, 1, 2] as $i) {
                if (isset($sensor_port[$i]['entAliasMappingIdentifier'])) {
                    $entAliasMappingIdentifier = $sensor_port[$i]['entAliasMappingIdentifier'];
                    break;
                }
            }
            if (isset($entAliasMappingIdentifier) && str_contains($entAliasMappingIdentifier, 'fIndex')) {
                [, $ifIndex] = explode('.', $entAliasMappingIdentifier);

                $port = get_port_by_index_cache($device['device_id'], $ifIndex);
                if (is_array($port)) {
                    // Hola, port really found
                    print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port['port_id']);
                    return $port;
                }
            }

            // This case for Cisco IOSXR ASR 9900 platform, when have incorrect entAliasMappingIdentifier association,
            // https://jira.observium.org/browse/OBS-3147
            $port_id = FALSE;
            if (str_contains($sensor_port['entPhysicalName'], '-PORT-')) {
                // Second, try detect port by entPhysicalDescr/entPhysicalName
                if (str_starts($sensor_port['entPhysicalDescr'], ['10GBASE', '10GE']) ||
                    str_icontains_array($sensor_port['entPhysicalDescr'], [' 10GBASE', ' 10GE', ' 10G '])) {
                    $ifDescr_base = 'TenGigE';
                } elseif (str_starts($sensor_port['entPhysicalDescr'], ['25GBASE', '25GE']) ||
                          str_icontains_array($sensor_port['entPhysicalDescr'], [' 25GBASE', ' 25GE', ' 25G '])) {
                    $ifDescr_base = 'TwentyFiveGigE';
                } elseif (str_starts($sensor_port['entPhysicalDescr'], ['40GBASE', '40GE']) ||
                          str_icontains_array($sensor_port['entPhysicalDescr'], [' 40GBASE', ' 40GE', ' 40G '])) {
                    $ifDescr_base = 'FortyGigE';
                } elseif (str_starts($sensor_port['entPhysicalDescr'], ['100GBASE', '100GE']) ||
                          str_icontains_array($sensor_port['entPhysicalDescr'], [' 100GBASE', ' 100GE', ' 100G '])) {
                    // Ie:
                    // Cisco CPAK 100GBase-SR4, 100m, MMF
                    // 100GBASE-ER4 CFP2 Module for SMF (<40 km)
                    // Non-Cisco QSFP28 100G ER4 Pluggable Optics Module
                    $ifDescr_base = 'HundredGigE';
                }
                $ifDescr_num = str_replace('-PORT-', '/', $sensor_port['entPhysicalName']);
                $port_id     = get_port_id_by_ifDescr($device['device_id'], $ifDescr_base . $ifDescr_num);
                if (!is_numeric($port_id)) {
                    // FIXME, I think first node number '0/' should be detected by some how
                    $port_id = get_port_id_by_ifDescr($device['device_id'], $ifDescr_base . '0/' . $ifDescr_num);
                }
            } elseif (str_contains_array($sensor_port['entPhysicalName'], ['TenGigE', 'TwentyFiveGigE', 'FortyGigE', 'HundredGigE', 'Ethernet'])) {
                // Same as previous, but entPhysicalName contain correct ifDescr, ie:
                // NCS platform: FortyGigE0/0/0/20
                // NXOS platform: "Ethernet1/1(volt)
                [$ifDescr,] = explode('(', $sensor_port['entPhysicalName'], 2);
                $port_id = get_port_id_by_ifDescr($device['device_id'], $ifDescr);
            }

            if (is_numeric($port_id)) {
                // Hola, port really found
                $port    = get_port_by_id($port_id);
                $ifIndex = $port['ifIndex'];
                print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port_id);

                return $port;
            }

        } elseif ((($sensor_port['entPhysicalClass'] === 'sensor' && $sensor_port['entPhysicalContainedIn'] == 0) ||
                   ($sensor_port['entPhysicalClass'] === 'module' && ($sensor_port['entPhysicalIsFRU'] === 'true' || str_contains($sensor_port['entPhysicalVendorType'], 'SFP')))) &&
                  str_starts($sensor_port['entPhysicalName'], ['GigabitEthernet', 'TenGigE', 'TwentyFiveGigE', 'FortyGigE', 'HundredGigE', 'Ethernet'])) {
            // NOTE. This is deeeeeerp, you will never understand why this is so, but it is necessary because Cisco breaks its snmp every time
            $sensor_index = $sensor_port['entPhysicalContainedIn']; // Next ifIndex
            // entPhysicalName contain correct ifDescr, ie:
            // NCS platform: FortyGigE0/0/0/20
            // NXOS 6.x platform: "Ethernet1/1(volt)
            [$ifDescr,] = explode('(', $sensor_port['entPhysicalName'], 2);
            if ($port_id = get_port_id_by_ifDescr($device['device_id'], $ifDescr)) {
                // Hola, port really found
                $port    = get_port_by_id($port_id);
                $ifIndex = $port['ifIndex'];
                print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port_id);

                return $port;
            }

        } else {
            $sensor_index = $sensor_port['entPhysicalContainedIn']; // Next ifIndex

            // See: http://jira.observium.org/browse/OBS-2295
            // IOS-XE and IOS-XR can store in module index both: sensors and port
            $sensor_transceiver = $sensor_port['entPhysicalClass'] === 'sensor' &&
                                  str_icontains_array($sensor_port['entPhysicalName'] . $sensor_port['entPhysicalDescr'] . $sensor_port['entPhysicalVendorType'], ['transceiver', '-PORT-']);
            // This is multi-lane optical transceiver, ie 100G, 40G, multiple sensors for each port
            $sensor_multilane = $sensor_port['entPhysicalClass'] === 'container' &&
                                (in_array($sensor_port['entPhysicalVendorType'], ['cevContainer40GigBasePort', 'cevContainerCXP', 'cevContainerCPAK']) || // Known Cisco specific containers
                                 str_contains_array($sensor_port['entPhysicalName'] . $sensor_port['entPhysicalDescr'], ['Optical']));                    // Pluggable Optical Module Container
            if ($sensor_transceiver) {
                $tmp_index = dbFetchCell('SELECT `entPhysicalIndex` FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalContainedIn` = ? AND `entPhysicalClass` = ? AND `deleted` IS NULL', [$device['device_id'], $sensor_index, 'port']);
                if (is_numeric($tmp_index) && $tmp_index > 0) {
                    // If port index found, try this entPhysicalIndex in next round
                    $sensor_index = $tmp_index;
                }
            } elseif ($sensor_multilane) {
                $entries = dbFetchRows('SELECT `entPhysicalIndex`, `entPhysicalName` FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalContainedIn` = ? AND `entPhysicalClass` = ? AND `deleted` IS NULL', [$device['device_id'], $sensor_index, 'port']);
                print_debug("Multi-Lane entries:");
                print_debug_vars($entries, 1);
                if (count($entries) > 1 &&
                    preg_match('/(?<start>\D{2})(?<num>\d+(?:\/\d+)+).*Lane\s*(?<lane>\d+)/', $sensor_name, $matches)) { // detect port numeric part and lane
                    // There is each Line associated with breakout port, mostly is QSFP+ 40G
                    // FortyGigE0/0/0/0-Tx Lane 1 Power -> 0/RP0-TenGigE0/0/0/0/1
                    // FortyGigE0/0/0/0-Tx Lane 2 Power -> 0/RP0-TenGigE0/0/0/0/2
                    $lane_num = $matches['start'] . $matches['num'] . '/' . $matches['lane'];                            // FortyGigE0/0/0/0-Tx Lane 1 -> gE0/0/0/0/1
                    foreach ($entries as $entry) {
                        if (str_ends($entry['entPhysicalName'], $lane_num)) {
                            $sensor_index = $entry['entPhysicalIndex'];
                            break;
                        }
                    }

                } elseif (is_numeric($entries[0]['entPhysicalIndex']) && $entries[0]['entPhysicalIndex'] > 0) {
                    // Single multi-lane port association, ie 100G
                    $sensor_index = $entries[0]['entPhysicalIndex'];
                }
            }
        }
        // NOTE for self: entPhysicalParentRelPos >= 0 because on iosxr trouble
    } while ($sensor_port['entPhysicalClass'] !== 'port' && $sensor_port['entPhysicalContainedIn'] > 0 &&
             ($sensor_port['entPhysicalParentRelPos'] >= 0 || $device['os'] === 'arista_eos'));

    return NULL;
}

// Get port array by ID (using cache)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_by_id_cache($port_id)
{
    return get_entity_by_id_cache('port', $port_id);
}

// Get port array by ID (with port state)
// NOTE get_port_by_id(ID) != get_port_by_id_cache(ID)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_by_id($port_id)
{
    if (is_numeric($port_id)) {
        $port = dbFetchRow("SELECT * FROM `ports` WHERE `port_id` = ?", [ $port_id ]);
    }

    if (is_array($port)) {
        humanize_port($port);
        return $port;
    }

    return FALSE;
}

// Get port array by ifIndex (using cache)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_by_index_cache($device, $ifIndex, $deleted = 0)
{
    global $cache;

    if (is_array($device) && isset($device['device_id'])) {
        $device_id = $device['device_id'];
    } elseif (is_numeric($device)) {
        $device_id = $device;
    }
    if (!isset($device_id) || !is_intnum($ifIndex)) {
        print_error("Invalid arguments passed into function get_port_by_index_cache().");
        return FALSE;
    }

    if (OBS_PROCESS_NAME === 'poller' && !isset($cache['port_index'][$device_id]) && !$deleted) {
        // Pre-cache all ports in poller for speedup db queries
        foreach (dbFetchRows('SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ?', [$device_id, 0]) as $entity) {
            if (is_numeric($entity['port_id'])) {
                // Cache ifIndex to port ID translations
                $cache['port_index'][$device_id][$entity['ifIndex']] = $entity['port_id'];

                // Same caching as in get_entity_by_id_cache()
                humanize_port($entity);
                entity_rewrite('port', $entity);
                $cache['port'][$entity['port_id']] = $entity;
            }
        }
    }

    if (isset($cache['port_index'][$device_id][$ifIndex]) && is_numeric($cache['port_index'][$device_id][$ifIndex])) {
        $id = $cache['port_index'][$device_id][$ifIndex];
    } else {
        $deleted = $deleted ? 1 : 0; // Just convert boolean to 0 or 1

        $id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ? AND `deleted` = ? LIMIT 1", [$device_id, $ifIndex, $deleted]);
        if (!$deleted && is_numeric($id)) {
            // Cache port IDs (except deleted)
            $cache['port_index'][$device_id][$ifIndex] = $id;
        }
    }

    if (!safe_empty($id) && $port = get_entity_by_id_cache('port', $id)) {
        return $port;
    }

    return FALSE;
}

// Get port array by ifIndex
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_by_ifIndex($device_id, $ifIndex)
{
    if (is_array($device_id)) {
        $device_id = $device_id['device_id'];
    }

    if (safe_empty($ifIndex)) {
        return FALSE;
    }

    $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ? LIMIT 1", [$device_id, $ifIndex]);

    if (is_array($port)) {
        humanize_port($port);
        return $port;
    }

    return FALSE;
}

// Get port ID by ifDescr (i.e. 'TenGigabitEthernet1/1') or ifName (i.e. 'Te1/1')
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_id_by_ifDescr($device_id, $ifDescr, $deleted = 0)
{
    if (is_array($device_id)) {
        $device_id = $device_id['device_id'];
    }

    $port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE `device_id` = ? AND (`ifDescr` = ? OR `ifName` = ? OR `port_label_short` = ?) AND `deleted` = ? LIMIT 1", [$device_id, $ifDescr, $ifDescr, $ifDescr, $deleted]);

    if (is_numeric($port_id)) {
        return $port_id;
    }

    return FALSE;
}

// Get port ID by ifAlias (interface description)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_id_by_ifAlias($device_id, $ifAlias, $deleted = 0)
{
    if (is_array($device_id)) {
        $device_id = $device_id['device_id'];
    }

    $port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE `device_id` = ? AND `ifAlias` = ? AND `deleted` = ? LIMIT 1", [$device_id, $ifAlias, $deleted]);

    if (is_numeric($port_id)) {
        return $port_id;
    }

    return FALSE;
}

// Get port ID by customer params (see http://www.observium.org/wiki/Interface_Description_Parsing)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_id_by_customer($customer)
{
    $where = ' WHERE 1';
    if (is_array($customer)) {
        foreach ($customer as $var => $value) {
            if ($value != '') {
                switch ($var) {
                    case 'device':
                    case 'device_id':
                        $where .= generate_query_values_and($value, 'device_id');
                        break;
                    case 'type':
                    case 'descr':
                    case 'circuit':
                    case 'speed':
                    case 'notes':
                        $where .= generate_query_values_and($value, 'port_descr_' . $var);
                        break;
                }
            }
        }
    } else {
        return FALSE;
    }

    $query = 'SELECT `port_id` FROM `ports` ' . $where . ' ORDER BY `ifOperStatus` DESC';
    $ids   = dbFetchColumn($query);

    //print_vars($ids);
    switch (safe_count($ids)) {
        case 0:
            return FALSE;
        case 1:
            return $ids[0];

        default:
            foreach ($ids as $port_id) {
                $port   = get_port_by_id_cache($port_id);
                $device = device_by_id_cache($port['device_id']);
                if ($device['disabled'] || !$device['status']) {
                    continue; // switch to next ID
                }
                break;
            }
            return $port_id;
    }
    return FALSE;
}

function get_device_ids_by_customer($type, $customer)
{
    if (safe_empty($customer)) {
        return NULL;
    }

    // Recursive merge
    if (is_array($customer)) {
        $ids = [];
        foreach ($customer as $entry) {
            if ($entry_ids = get_device_ids_by_customer($type, $entry)) {
                $ids[] = $entry_ids;
                //$ids = array_merge($ids, $entry_ids);
            }
        }
        return array_merge([], ...$ids);
    }

    $where = ' WHERE 1';
    switch ($type) {
        case 'device':
        case 'device_id':
            $where .= generate_query_values_and($customer, 'device_id');
            break;
        case 'type':
        case 'descr':
        case 'circuit':
        case 'speed':
        case 'notes':
            $where .= generate_query_values_and($customer, 'port_descr_' . $type);
            break;
        default:
            $where .= generate_query_values_and($customer, 'port_descr_descr');
    }

    $query = 'SELECT DISTINCT `device_id` FROM `ports` ' . $where;

    return dbFetchColumn($query);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function is_port_valid($device, $port)
{
    global $config;

    print_debug("\nifIndex {$port['ifIndex']} (ifAdminStatus = {$port['ifAdminStatus']}, ifOperStatus = {$port['ifOperStatus']}). ");
    // Ignore non standard ifOperStatus
    // See http://tools.cisco.com/Support/SNMP/do/BrowseOID.do?objectInput=ifOperStatus
    $valid_ifOperStatus = [ 'testing', 'dormant', 'down', 'lowerLayerDown', 'unknown', 'up', 'monitoring' ];
    if (isset($port['ifOperStatus']) && !safe_empty($port['ifOperStatus']) &&
        !in_array($port['ifOperStatus'], $valid_ifOperStatus, TRUE)) {
        print_debug("ignored (by ifOperStatus = notPresent or invalid value).");
        return FALSE;
    }

    // Per os definition
    $def = $config['os'][$device['os']]['ports_ignore'] ?? [];

    // Ignore ports with empty ifType
    if (isset($def['allow_empty'])) {
        $ports_allow_empty = (bool)$def['allow_empty'];
        unset($def['allow_empty']);
    } else {
        $ports_allow_empty = FALSE;
    }
    if (!isset($port['ifType']) && !$ports_allow_empty) {
        /* Some devices (ie D-Link) report ports without any useful info, example:
        [74] => Array
            (
                [ifName] => po22
                [ifInMulticastPkts] => 0
                [ifInBroadcastPkts] => 0
                [ifOutMulticastPkts] => 0
                [ifOutBroadcastPkts] => 0
                [ifLinkUpDownTrapEnable] => enabled
                [ifHighSpeed] => 0
                [ifPromiscuousMode] => false
                [ifConnectorPresent] => false
                [ifAlias] => po22
                [ifCounterDiscontinuityTime] => 0:0:00:00.00
            )
        */
        print_debug("ignored (by empty ifType).");
        return FALSE;
    }

    // This happens on some liebert UPS devices or when device have memory leak (ie Eaton Powerware)
    if (isset($config['os'][$device['os']]['ifType_ifDescr']) &&
        $config['os'][$device['os']]['ifType_ifDescr'] && $port['ifIndex']) {
        $len  = strlen($port['ifDescr']);
        $type = rewrite_iftype($port['ifType']);
        if ($type && ($len === 0 || $len > 255 ||
                      is_hex_string($port['ifDescr']) ||
                      preg_match('/(.)\1{4,}/', $port['ifDescr']))) {
            $port['ifDescr'] = $type . ' ' . $port['ifIndex'];
        }
    }

    //$if = ($config['os'][$device['os']]['ifname'] ? $port['ifName'] : $port['ifDescr']);
    $valid_ifDescr = !safe_empty($port['ifDescr']);
    $valid_ifName  = !safe_empty($port['ifName']);

    // Ignore ports with empty ifName and ifDescr (while not possible store in db)
    if (!$valid_ifDescr && !$valid_ifName) {
        print_debug("ignored (by empty ifDescr and ifName).");
        return FALSE;
    }
    // ifName not same as ifDescr (speedup label checks)
    $notsame_ifName = $port['ifDescr'] !== $port['ifName'];

    // Complex Oid based checks
    foreach ($def as $i => $array) {
        if (!is_numeric($i)) {
            continue;
        }                            // Ignore old definitions
        $count = safe_count($array); // count required Oids

        // Oids: ifType, ifAlias, ifDescr, ifName, label
        $found      = 0;
        $table_rows = [];
        foreach ($array as $oid => $entry) {
            switch (strtolower($oid)) {
                case 'type':
                case 'iftype':
                    if (str_contains_array($port['ifType'], $entry)) {
                        $table_rows[] = ['ifType', $GLOBALS['str_last_needle'], $port['ifType']];
                        $found++;
                    }
                    break;

                // This defs always regex!
                case 'descr':
                case 'ifdescr':
                    if (!$valid_ifDescr) {
                        break;
                    }
                    foreach ((array)$entry as $pattern) {
                        if (preg_match($pattern, $port['ifDescr'])) {
                            $table_rows[] = ['ifDescr', $pattern, $port['ifDescr']];
                            $found++;
                            break;
                        }
                    }
                    break;

                case 'name':
                case 'ifname':
                    if (!$valid_ifName) {
                        break;
                    }
                    foreach ((array)$entry as $pattern) {
                        if (preg_match($pattern, $port['ifName'])) {
                            $table_rows[] = ['ifName', $pattern, $port['ifName']];
                            $found++;
                            break;
                        }
                    }
                    break;

                case 'label':
                    if ($valid_ifDescr) {
                        foreach ((array)$entry as $pattern) {
                            if (preg_match($pattern, $port['ifDescr'])) {
                                $table_rows[] = ['ifDescr', $pattern, $port['ifDescr']];
                                $found++;
                                break;
                            }
                        }
                    }
                    if ($valid_ifName && $notsame_ifName) {
                        foreach ((array)$entry as $pattern) {
                            if (preg_match($pattern, $port['ifName'])) {
                                $table_rows[] = ['ifName', $pattern, $port['ifName']];
                                $found++;
                                break;
                            }
                        }
                    }
                    break;

                case 'alias':
                case 'ifalias':
                    foreach ((array)$entry as $pattern) {
                        if (preg_match($pattern, $port['ifAlias'])) {
                            $table_rows[] = ['ifAlias', $pattern, $port['ifAlias']];
                            $found++;
                            break;
                        }
                    }
                    break;
            }
        }

        if ($count && $found === $count) {
            // Show matched Oids
            print_debug("ignored (by Oids):");
            $table_headers = ['%WOID%n', '%WMatched definition%n', '%WValue%n'];
            print_cli_table($table_rows, $table_headers);
            return FALSE;
        }
    }

    /* Global Configs */

    // Ignore ports by ifAlias
    if (isset($config['bad_ifalias_regexp'])) {
        foreach ((array)$config['bad_ifalias_regexp'] as $bi) {
            if (preg_match($bi, $port['ifAlias'])) {
                print_debug("ignored (by ifAlias): " . $port['ifAlias'] . " [ $bi ]");
                return FALSE;
            }
        }
    }

    // Ignore ports by ifName/ifDescr (do not forced as case insensitive)
    if (isset($config['bad_if_regexp'])) {
        foreach ((array)$config['bad_if_regexp'] as $bi) {
            if ($valid_ifDescr && preg_match($bi, $port['ifDescr'])) {
                print_debug("ignored (by ifDescr regexp): " . $port['ifDescr'] . " [ $bi ]");
                return FALSE;
            }
            if ($valid_ifName && $notsame_ifName && preg_match($bi, $port['ifName'])) {
                print_debug("ignored (by ifName regexp): " . $port['ifName'] . " [ $bi ]");
                return FALSE;
            }
        }
    }
    // FIXME. Prefer regexp
    if ($valid_ifDescr && str_icontains_array($port['ifDescr'], (array)$config['bad_if'])) {
        $bi = $GLOBALS['str_last_needle'];
        print_debug("ignored (by ifDescr): " . $port['ifDescr'] . " [ $bi ]");
        return FALSE;
    }
    if ($valid_ifName && $notsame_ifName && str_icontains_array($port['ifName'], (array)$config['bad_if'])) {
        $bi = $GLOBALS['str_last_needle'];
        print_debug("ignored (by ifName): " . $port['ifName'] . " [ $bi ]");
        return FALSE;
    }

    // Ignore ports by ifType
    if (isset($config['bad_iftype']) && str_contains_array($port['ifType'], (array)$config['bad_iftype'])) {
        $bi = $GLOBALS['str_last_needle'];
        print_debug("ignored (by ifType): " . $port['ifType'] . " [ $bi ]");
        return FALSE;
    }

    return TRUE;
}

// Delete port from database and associated rrd files
// DOCME needs phpdoc block
// TESTME needs unit testing
function delete_port($int_id, $delete_rrd = TRUE)
{
    global $config;

    $port = dbFetchRow("SELECT * FROM `ports`
                      LEFT JOIN `devices` USING (`device_id`)
                      WHERE `port_id` = ?", [$int_id]);
    $ret  = "> Deleted interface from " . $port['hostname'] . ": id=$int_id (" . $port['ifDescr'] . ")\n";

    // Remove entities from common tables
    $deleted_entities = [];
    foreach ($config['entity_tables'] as $table) {
        $where        = '`entity_type` = ?' . generate_query_values_and($int_id, 'entity_id');
        $table_status = dbDelete($table, $where, ['port']);
        if ($table_status) {
            $deleted_entities['port'] = 1;
        }
    }
    if (count($deleted_entities)) {
        $ret .= ' * Deleted common entity entries linked to port.' . PHP_EOL;
    }

    // FIXME, move to definitions
    $port_tables    = ['eigrp_ports', 'ipv4_addresses', 'ipv6_addresses',
                       'ip_mac', 'juniAtmVp', 'mac_accounting', 'ospf_nbrs', 'ospf_ports',
                       'ports_adsl', 'ports_cbqos', 'ports_vlans', 'pseudowires', 'vlans_fdb',
                       'neighbours', 'ports'];
    $deleted_tables = [];
    foreach ($port_tables as $table) {
        $table_status = dbDelete($table, "`port_id` = ?", [$int_id]);
        if ($table_status) {
            $deleted_tables[] = $table;
        }
    }

    $table_status = dbDelete('ports_stack', "`port_id_high` = ? OR `port_id_low` = ?", [$int_id, $int_id]);
    if ($table_status) {
        $deleted_tables[] = 'ports_stack';
    }
    $table_status = dbDelete('entity_permissions', "`entity_type` = 'port' AND `entity_id` = ?", [$int_id]);
    if ($table_status) {
        $deleted_tables[] = 'entity_permissions';
    }
    $table_status = dbDelete('alert_table', "`entity_type` = 'port' AND `entity_id` = ?", [$int_id]);
    if ($table_status) {
        $deleted_tables[] = 'alert_table';
    }
    $table_status = dbDelete('group_table', "`entity_type` = 'port' AND `entity_id` = ?", [$int_id]);
    if ($table_status) {
        $deleted_tables[] = 'group_table';
    }

    $ret .= ' * Deleted interface entries from tables: ' . implode(', ', $deleted_tables) . PHP_EOL;

    if ($delete_rrd) {
        $rrd_types    = ['adsl', 'dot3', 'fdbcount', 'poe', NULL];
        $deleted_rrds = [];
        foreach ($rrd_types as $type) {
            $rrdfile = get_port_rrdfilename($port, $type, TRUE);
            if (is_file($rrdfile)) {
                unlink($rrdfile);
                $deleted_rrds[] = $rrdfile;
            }
        }
        $ret .= ' * Deleted interface RRD files: ' . implode(', ', $deleted_rrds) . PHP_EOL;
    }

    return $ret;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function port_html_class($ifOperStatus, $ifAdminStatus, $encrypted = FALSE)
{
    $ifclass = "interface-upup";
    if ($ifAdminStatus == "down") {
        $ifclass = "gray";
    } elseif ($ifAdminStatus == "up") {
        if ($ifOperStatus == "down") {
            $ifclass = "red";
        } elseif ($ifOperStatus == "lowerLayerDown") {
            $ifclass = "orange";
        } elseif ($ifOperStatus == "monitoring") {
            $ifclass = "green";
        } //elseif ($encrypted === '1')                { $ifclass = "olive"; }
        elseif ($encrypted) {
            $ifclass = "olive";
        } elseif ($ifOperStatus == "up") {
            $ifclass = "";
        } else {
            $ifclass = "purple";
        }
    }

    return $ifclass;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_rrdindex($port)
{
    global $config;

    if (isset($port['device_id'])) {
        $device_id = $port['device_id'];
    } else {
        // In poller, device_id not always passed
        $port_tmp  = get_port_by_id_cache($port['port_id']);
        $device_id = $port_tmp['device_id'];
    }
    $device = device_by_id_cache($device_id);

    if (isset($config['os'][$device['os']]['port_rrd_identifier'])) {
        $device_identifier = strtolower($config['os'][$device['os']]['port_rrd_identifier']);
    } else {
        $device_identifier = 'ifindex';
    }

    // default to ifIndex
    $this_port_identifier = $port['ifIndex'];

    if ($device_identifier == "ifname" && $port['ifName'] != "") {
        $this_port_identifier = strtolower(str_replace("/", "-", $port['ifName']));
    }

    return $this_port_identifier;
}

// DOCME needs phpdoc block
function humanspeed($speed)
{
    return safe_empty($speed) ? '-' : format_bps($speed);
}

// CLEANME DEPRECATED
function get_port_rrdfilename($port, $suffix = NULL, $fullpath = FALSE)
{
    $this_port_identifier = get_port_rrdindex($port);

    if ($suffix == "") {
        $filename = "port-" . $this_port_identifier . ".rrd";
    } else {
        $filename = "port-" . $this_port_identifier . "-" . $suffix . ".rrd";
    }

    if ($fullpath) {
        if (isset($port['device_id'])) {
            $device_id = $port['device_id'];
        } else {
            // In poller, device_id not always passed
            $port_tmp  = get_port_by_id_cache($port['port_id']);
            $device_id = $port_tmp['device_id'];
        }
        $device   = device_by_id_cache($device_id);
        $filename = get_rrd_path($device, $filename);
    }

    return $filename;
}

function calculate_port_oid_stats(&$this_port, &$port, $oid, $polled_period) {
    /* Not required while used only in single place
    $stat_oids_db = [ 'ifInOctets', 'ifOutOctets', 'ifInErrors', 'ifOutErrors', 'ifInUcastPkts', 'ifOutUcastPkts',
                      'ifInNUcastPkts', 'ifOutNUcastPkts', 'ifInBroadcastPkts', 'ifOutBroadcastPkts',
                      'ifInMulticastPkts', 'ifOutMulticastPkts', 'ifInDiscards', 'ifOutDiscards' ];
    if (!in_array($oid, $stat_oids_db)) {
        print_debug("Unknown port Oid: $oid");
        return;
    }
    */

    $oid_rate  = $oid . '_rate';
    $oid_diff  = $oid . '_diff';
    $oid_delta = $oid . '_delta';
    $oids_array = [ $oid_rate, $oid_diff, $oid_delta ];
    
    if (isset($this_port[$oid_rate]) && is_numeric($this_port[$oid_rate])) {
        // This case for ports report only rates, see SPECTRA-LOGIC-STRATA-MIB definitions
        $rate = $this_port[$oid_rate];
        $diff = $rate * $polled_period;

        // Set pseudo Oid counter for RRD
        if (isset($port[$oid]) && is_numeric($port[$oid])) {
            $port['state'][$oid] = int_add($port[$oid], (int)$diff);
        } else {
            // Init
            $port['state'][$oid] = 0;
        }
        if (!isset($this_port[$oid])) {
            $this_port[$oid] = $port['state'][$oid];
        }

        // Rate stats grow port to HC..
        $port['port_64bit']           = 1;
        $port['update']['port_64bit'] = 1;
        $oids_array[] = 'port_64bit';
    } elseif (isset($port[$oid]) && is_numeric($port[$oid])) {
        $diff = int_sub($this_port[$oid], $port[$oid]); // Use accurate substract
        $rate = float_div($diff, $polled_period);

        $port['state'][$oid] = $this_port[$oid];
    } else {
        // Add zero defaults for correct multiupdate!
        $diff = 0;
        $rate = 0;

        $port['state'][$oid] = $port[$oid]; // Keep old value
    }

    if ($rate < 0) {
        print_warning("Negative $oid. Possible spike on next poll!");

        $rate = 0;
        $port['stats'][$oid . '_negative_rate'] = $rate;
        $oids_array[] = $oid . '_negative_rate';
    }
    print_debug("\n $oid ($diff B) $rate Bps $polled_period secs");

    $port['stats'][$oid_rate] = $rate;

    // Set port state/stats
    // Perhaps need to protect these from false polls.
    $port['alert_array'][$oid_rate]  = $rate;
    $port['alert_array'][$oid_delta] = (int)$diff;

    $port['state'][$oid_rate] = $rate;
    $port['stats'][$oid_diff] = (int)$diff;

    // Oid specific stats
    switch ($oid) {
        case 'ifInErrors':
        case 'ifOutErrors':
            // Record delta in database only for In/Out errors.
            $port['state'][$oid_delta] = (int)$diff;
            break;

        case 'ifInOctets':
        case 'ifOutOctets':
            // Convert Octets rates to Bits rates
            $oid_bits_rate = $oid === 'ifInOctets' ? 'ifInBits_rate' : 'ifOutBits_rate';
            $oids_array[]  = $oid_bits_rate;

            $port['stats'][$oid_bits_rate] = is_numeric($port['stats'][$oid_rate]) ? round($port['stats'][$oid_rate] * 8) : 0;
            $port['alert_array'][$oid_bits_rate] = $port['stats'][$oid_bits_rate];

            // Percent stats
            $oid_perc      = $oid . '_perc';
            $oid_bits_perc = $oid === 'ifInOctets' ? 'ifInBits_perc' : 'ifOutBits_perc';
            $oids_array[]  = $oid_perc;
            $oids_array[]  = $oid_bits_perc;

            $perc = percent($port['stats'][$oid_bits_rate], $this_port['ifSpeed'], 4);
            $port['stats'][$oid_bits_perc]  = $perc;
            $port['state'][$oid_perc]       = $perc;
            $port['alert_array'][$oid_perc] = $perc;
            break;
    }

    print_debug("Port calculate Oids [$oid]: " . implode(', ', $oids_array) . '.');
}

function debug_port($device, $this_port, $debug_port, $port, $hc_prefix, $polled_period) {
    global $config;

    // If we have been told to debug this port, output the counters we collected earlier, with the rates stuck on the end.
    if ($config['debug_port'][$port['port_id']]) {
        print_debug("Wrote port debugging data");
        $debug_file = "/tmp/port_debug_" . $port['port_id'] . ".txt";
        //FIXME. I think formatted debug out (as for spikes) more informative, but output here more parsable as CSV
        $port_msg = $port['port_id'] . "|" . $this_port['polled'] . "|" . $polled_period . "|" . $debug_port['ifInOctets'] . "|" . $debug_port['ifOutOctets'] . "|" . $debug_port['ifHCInOctets'] . "|" . $debug_port['ifHCOutOctets'];
        $port_msg .= "|" . format_bps($port['stats']['ifInOctets_rate']) . "|" . format_bps($port['stats']['ifOutOctets_rate']) . "|" . $device['snmp_version'] . "\n";
        file_put_contents($debug_file, $port_msg, FILE_APPEND);
    }

    // If we see a spike above ifSpeed or negative rate, output it to /tmp/port_debug_spikes.txt
    // Example how to read usefull info from this debug by grep:
    // grep -B 1 -A 6 'ID:\ 520' /tmp/port_debug_spikes.txt
    if ($config['debug_port']['spikes'] && $this_port['ifSpeed'] > 0 &&
        ($port['stats']['ifInBits_rate'] > $this_port['ifSpeed'] || $port['stats']['ifOutBits_rate'] > $this_port['ifSpeed'] ||
         isset($port['stats']['ifInOctets_negative_rate']) || isset($port['stats']['ifOutOctets_negative_rate']))) {
        if (!$port['port_64bit']) {
            $hc_prefix = '';
        }
        print_warning("Spike above ifSpeed or negative rate detected! See debug info here: ");
        $debug_file   = "/tmp/port_debug_spikes.txt";
        $debug_format = "| %20s | %20s | %20s |\n";
        $debug_msg    = sprintf("+%'-68s+\n", '');
        $debug_msg    .= sprintf("|%67s |\n", $device['hostname'] . " " . $debug_port['ifDescr'] . " (ID: " . $port['port_id'] . ") " . format_bps($debug_port['ifSpeed']) . " " . ($port['port_64bit'] ? 'Counter64' : 'Counter32'));
        $debug_msg    .= sprintf("+%'-68s+\n", '');
        $debug_msg    .= sprintf("| %-20s | %-20s | %-20s |\n", 'Polled time', 'if' . $hc_prefix . 'OutOctets', 'if' . $hc_prefix . 'InOctets');
        $debug_msg    .= sprintf($debug_format, '(prev) ' . $port['poll_time'], $port['ifOutOctets'], $port['ifInOctets']);
        $debug_msg    .= sprintf($debug_format, '(now)  ' . $this_port['polled'], $this_port['ifOutOctets'], $this_port['ifInOctets']);
        $debug_msg    .= sprintf($debug_format, format_unixtime($this_port['polled']), format_bps($port['stats']['ifOutBits_rate'] * 8), format_bps($port['stats']['ifInBits_rate']));
        $debug_msg    .= sprintf("%'+70s\n", '');
        $debug_msg    .= sprintf("| %-67s|\n", 'Port dump:');
        // Added full original port variable dump
        foreach ($debug_port as $debug_key => $debug_var) {
            $debug_msg .= sprintf("|  %-66s|\n", "'$debug_key' => '$debug_var',");
        }
        $debug_msg .= sprintf("+%'-68s+\n\n", '');
        file_put_contents($debug_file, $debug_msg, FILE_APPEND);
    }
}

function delete_old_fdb_entries($device, $age = NULL) {
    if (!is_intnum($device['device_id'])) {
        return;
    }

    $age = safe_empty($age) ? $GLOBALS['config']['fdb']['deleted_age'] : $age;
    $params = [ $device['device_id'], 1, [ get_time() - age_to_seconds($age) ] ];

    if (OBS_DEBUG) {
        $deleted = dbFetchCell('SELECT COUNT(*) FROM `vlans_fdb` WHERE `device_id` = ? AND `deleted` = ? AND `fdb_last_change` < ?', $params);
        print_cli("Deleting $deleted FDB entries older than $age.\n");
    }
    dbDelete('vlans_fdb', '`device_id` = ? AND `deleted` = ? AND `fdb_last_change` < ?', $params);
}

// EOF