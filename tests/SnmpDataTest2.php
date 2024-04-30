<?php

//define('OBS_DEBUG', 2);
//define('OBS_QUIET', TRUE); // Disable any additional output from tests
ini_set('opcache.enable', 0);

include(__DIR__ . '/../includes/observium.inc.php');
//include(dirname(__FILE__) . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php');
//include(dirname(__FILE__) . '/../includes/definitions.inc.php');
//include(dirname(__FILE__) . '/../includes/functions.inc.php');

/**
 * How install snmpsim:
 *   sudo pip install snmpsim
 *
 * How to record basic (system) data for oses:
 *   snmprec.py --agent-udpv4-endpoint=x.x.x.x --community=<community> --start-oid=1.3.6.1.2.1 --stop-oid=1.3.6.1.2.1.2 --output-file=osname-y.snmprec
 *
 * where: x.x.x.x - your device ip or hostname,
 *        osname  - os name, same as in observium definitions
 *        y       - any numeric string, just for multiple tests for same os name
 *
 * for other options and snmp params, see: snmprec.py --help
 */

// SNMPsim tests
$snmpsimd_ip   = $config['tests']['snmpsim_ip'] ?? '127.0.0.1';
$snmpsimd_port = $config['tests']['snmpsim_port'] ?? 16111;
$snmpsimd_data = $config['tests']['snmpsim_dir'] . '/full';
if (is_dir($snmpsimd_data))
{
  snmpsimd_init($snmpsimd_data);
  sleep(2); // Sleep before tests, because snmpsimd can get stuck
}

class SnmpDataTest2 extends \PHPUnit\Framework\TestCase
{
  protected function setUp(): void
  {
    global $snmpsimd_ip, $snmpsimd_port, $snmpsimd_data;

    if (!OBS_SNMPSIMD)
    {
      $this->markTestSkipped('SNMPsimd unavailable or daemon not started, test skipped.');
    }

    // Just get first snmp request, for alive snmpsimd
    $device = build_initial_device_array($snmpsimd_ip, 'ios-1', 'v2c', $snmpsimd_port, 'udp');
    snmp_get_oid($device, '.1.3.6.1.2.1.1.2.0');
  }

  /**
  * @dataProvider providerSnmp_Get
  * @group snmpget
  */
  public function testSnmp_Get($community, $options, $oid, $mib, $result)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    $device = build_initial_device_array($snmpsimd_ip, $community, 'v2c', $snmpsimd_port, 'udp');
    $device['snmp_timeout'] = 2;
    $device['snmp_retries'] = 1;
    //var_dump($device);
    $this->assertSame($result, snmp_get($device, $oid, $options, $mib));
  }

  public function providerSnmp_Get()
  {
    $community_ftos = 'ftos-9.10';
    return array(
      // each single option
      // BGP4-MIB::bgpLocalAs.0 = Wrong Type (should be INTEGER): Gauge32: 20282
      array( $community_ftos,      '',  'bgpLocalAs.0',   'BGP4-MIB', 'BGP4-MIB::bgpLocalAs.0 = Wrong Type (should be INTEGER): Gauge32: 20282'),
      array( $community_ftos,   '-OU',  'bgpLocalAs.0',   'BGP4-MIB', 'BGP4-MIB::bgpLocalAs.0 = Wrong Type (should be INTEGER): Gauge32: 20282'),
      array( $community_ftos,   '-Os',  'bgpLocalAs.0',   'BGP4-MIB', 'bgpLocalAs.0 = Wrong Type (should be INTEGER): Gauge32: 20282'),
      array( $community_ftos,   '-On',  'bgpLocalAs.0',   'BGP4-MIB', '.1.3.6.1.2.1.15.2.0 = Wrong Type (should be INTEGER): Gauge32: 20282'),
      array( $community_ftos,   '-Oq',  'bgpLocalAs.0',   'BGP4-MIB', 'BGP4-MIB::bgpLocalAs.0 Wrong Type (should be INTEGER): 20282'),
      array( $community_ftos,   '-OQ',  'bgpLocalAs.0',   'BGP4-MIB', 'BGP4-MIB::bgpLocalAs.0 = 20282'),
      array( $community_ftos,   '-Ov',  'bgpLocalAs.0',   'BGP4-MIB', 'Gauge32: 20282'),
      // options set
      array( $community_ftos,  '-Oqv',  'bgpLocalAs.0',   'BGP4-MIB', '20282'), // Oh, we clean this too
      array( $community_ftos,  '-OQv',  'bgpLocalAs.0',   'BGP4-MIB', '20282'),

      // BGP4-MIB::bgpPeerState.64.72.74.193 = INTEGER: idle(1)
      array( $community_ftos,      '',  'bgpPeerState.64.72.74.193',  'BGP4-MIB', 'BGP4-MIB::bgpPeerState.64.72.74.193 = INTEGER: idle(1)'),
      array( $community_ftos,   '-Oe',  'bgpPeerState.64.72.74.193',  'BGP4-MIB', 'BGP4-MIB::bgpPeerState.64.72.74.193 = INTEGER: 1'),
      array( $community_ftos,   '-On',  'bgpPeerState.64.72.74.193',  'BGP4-MIB', '.1.3.6.1.2.1.15.3.1.2.64.72.74.193 = INTEGER: idle(1)'),
      array( $community_ftos,   '-OX',  'bgpPeerState.64.72.74.193',  'BGP4-MIB', 'BGP4-MIB::bgpPeerState[64.72.74.193] = INTEGER: idle(1)'),

      // SNMPv2-MIB::sysUpTime.0 = Timeticks: (3972400096) 459 days, 18:26:40.96
      array( $community_ftos,      '',   'sysUpTime.0', 'SNMPv2-MIB', 'SNMPv2-MIB::sysUpTime.0 = Timeticks: (3972400096) 459 days, 18:26:40.96'),
      array( $community_ftos,   '-Ot',   'sysUpTime.0', 'SNMPv2-MIB', 'SNMPv2-MIB::sysUpTime.0 = 3972400096'),
      array( $community_ftos,   '-Ov',   'sysUpTime.0', 'SNMPv2-MIB', 'Timeticks: (3972400096) 459 days, 18:26:40.96'),
      array( $community_ftos,  '-OQv',   'sysUpTime.0', 'SNMPv2-MIB', '459:18:26:40.96'),
      array( $community_ftos, '-OQvt',   'sysUpTime.0', 'SNMPv2-MIB', '3972400096'),

      // SNMPv2-MIB::sysObjectID.0 = OID: SNMPv2-SMI::enterprises.6027.1.3.14
      array( $community_ftos,      '', 'sysObjectID.0', 'SNMPv2-MIB', 'SNMPv2-MIB::sysObjectID.0 = OID: SNMPv2-SMI::enterprises.6027.1.3.14'),
      array( $community_ftos,   '-On', 'sysObjectID.0', 'SNMPv2-MIB', '.1.3.6.1.2.1.1.2.0 = OID: .1.3.6.1.4.1.6027.1.3.14'),
      array( $community_ftos,  '-OQv', 'sysObjectID.0', 'SNMPv2-MIB', 'SNMPv2-SMI::enterprises.6027.1.3.14'),
      array( $community_ftos, '-OQvn', 'sysObjectID.0', 'SNMPv2-MIB', '.1.3.6.1.4.1.6027.1.3.14'),
      //array( '', '', '', '', ''),
    );
  }

  /**
  * @dataProvider providerSnmpGetOid
  * @group snmpget
  */
  public function testSnmpGetOid($community, $flags, $oid, $mib, $result)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    $device = build_initial_device_array($snmpsimd_ip, $community, 'v2c', $snmpsimd_port, 'udp');
    $device['snmp_timeout'] = 2;
    $device['snmp_retries'] = 1;
    //var_dump($device);
    $test = snmp_get_oid($device, $oid, $mib, NULL, $flags);
    //var_dump($test);
    $this->assertSame($result, $test);
  }

  /**
   * @dataProvider providerSnmpGetOid
   * @group snmpget
   */
  public function testSnmpGetNextOid($community, $flags, $oid, $mib, $result)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    $device = build_initial_device_array($snmpsimd_ip, $community, 'v2c', $snmpsimd_port, 'udp');
    $device['snmp_timeout'] = 2;
    $device['snmp_retries'] = 1;
    //var_dump($device);
    list($oid, $index) = explode('.', $oid, 2); // Split oid and index parts
    if ($index === '0') // Fot test purpose use only Oids with first index is 0
    {
      $test = snmp_getnext_oid($device, $oid, $mib, NULL, $flags);
      //var_dump($test);
      if (strpos($result, '::') !== FALSE) {
        // snmpgetnext have different output from snmpget
        list(, $result) = explode('::', $result, 2);
      }
      $this->assertSame($result, $test);
    }
  }

  public function providerSnmpGetOid()
  {
    $community_ftos     = 'ftos-9.10';
    $community_ubuntu   = 'ubuntu-16.04';

    // Note, here \r\n as line ends!
    $sysdescr_ftos      = "Dell Networking OS\r\n";
    $sysdescr_ftos     .= "Operating System Version: 2.0\r\n";
    $sysdescr_ftos     .= "Application Software Version: 9.9(0.0P9)\r\n";
    $sysdescr_ftos     .= "Series: S4810\r\n";
    $sysdescr_ftos     .= "Copyright (c) 1999-2015 by Dell Inc. All Rights Reserved.\r\n";
    $sysdescr_ftos     .= "Build Time: Thu Feb  4 06:57:34 2016";
    $sysdescr_ftos_hex  = "\"44 65 6C 6C 20 4E 65 74 77 6F 72 6B 69 6E 67 20 ";
    $sysdescr_ftos_hex .= "4F 53 0D 0A 4F 70 65 72 61 74 69 6E 67 20 53 79 ";
    $sysdescr_ftos_hex .= "73 74 65 6D 20 56 65 72 73 69 6F 6E 3A 20 32 2E ";
    $sysdescr_ftos_hex .= "30 0D 0A 41 70 70 6C 69 63 61 74 69 6F 6E 20 53 ";
    $sysdescr_ftos_hex .= "6F 66 74 77 61 72 65 20 56 65 72 73 69 6F 6E 3A ";
    $sysdescr_ftos_hex .= "20 39 2E 39 28 30 2E 30 50 39 29 0D 0A 53 65 72 ";
    $sysdescr_ftos_hex .= "69 65 73 3A 20 53 34 38 31 30 0D 0A 43 6F 70 79 ";
    $sysdescr_ftos_hex .= "72 69 67 68 74 20 28 63 29 20 31 39 39 39 2D 32 ";
    $sysdescr_ftos_hex .= "30 31 35 20 62 79 20 44 65 6C 6C 20 49 6E 63 2E ";
    $sysdescr_ftos_hex .= "20 41 6C 6C 20 52 69 67 68 74 73 20 52 65 73 65 ";
    $sysdescr_ftos_hex .= "72 76 65 64 2E 0D 0A 42 75 69 6C 64 20 54 69 6D ";
    $sysdescr_ftos_hex .= "65 3A 20 54 68 75 20 46 65 62 20 20 34 20 30 36 ";
    $sysdescr_ftos_hex .= "3A 35 37 3A 33 34 20 32 30 31 36 \"";
    $distro_ubuntu_hex  = "4C 69 6E 75 78 7C 34 2E 34 2E 30 2D 37 37 2D 67 ";
    $distro_ubuntu_hex .= "65 6E 65 72 69 63 7C 61 6D 64 36 34 7C 55 62 75 ";
    $distro_ubuntu_hex .= "6E 74 75 7C 31 36 2E 30 34 7C 6B 76 6D ";
    return array(
      // BGP4-MIB::bgpLocalAs.0 = Wrong Type (should be INTEGER): Gauge32: 20282
      array( $community_ftos,                              0,   'bgpLocalAs.0',           'BGP4-MIB', '20282'),
      array( $community_ftos,                OBS_QUOTES_TRIM,   'bgpLocalAs.0',           'BGP4-MIB', '20282'), // default

      // BGP4-MIB::bgpPeerState.64.72.74.193 = INTEGER: idle(1)
      array( $community_ftos,                              0,  'bgpPeerState.64.72.74.193',  'BGP4-MIB', 'idle'),
      array( $community_ftos,                  OBS_SNMP_ENUM,  'bgpPeerState.64.72.74.193',  'BGP4-MIB', '1'),
      array( $community_ftos,               OBS_SNMP_NUMERIC,  'bgpPeerState.64.72.74.193',  'BGP4-MIB', 'idle'),
      array( $community_ftos,                   OBS_SNMP_HEX,  'bgpPeerState.64.72.74.193',  'BGP4-MIB', 'idle'),

      // SNMP-FRAMEWORK-MIB::snmpEngineID.0 = Hex-STRING: 00 00 17 8B 02 00 00 01 E8 8A E1 12
      array( $community_ftos,                OBS_QUOTES_TRIM, 'snmpEngineID.0', 'SNMP-FRAMEWORK-MIB', '00 00 17 8B 02 00 00 01 E8 8A E1 12 '), // DO NOT CLEAN SPACEs HERE!

      // SNMPv2-MIB::sysUpTime.0 = Timeticks: (3972400096) 459 days, 18:26:40.96
      array( $community_ftos,                OBS_QUOTES_TRIM, 'sysUpTime.0', 'SNMPv2-MIB', '459:18:26:40.96'),
      //array( $community_ftos,               OBS_SNMP_NUMERIC, 'sysUpTime.0', 'SNMPv2-MIB', '3972400096'),
      array( $community_ftos,               OBS_SNMP_NUMERIC, 'sysUpTime.0', 'SNMPv2-MIB', '459:18:26:40.96'),

      // sysDescr (see above)
      array( $community_ftos,                OBS_QUOTES_TRIM, 'sysDescr.0', 'SNMPv2-MIB', $sysdescr_ftos),
      array( $community_ftos,                   OBS_SNMP_HEX, 'sysDescr.0', 'SNMPv2-MIB', $sysdescr_ftos_hex),
      array( $community_ftos, OBS_QUOTES_TRIM | OBS_SNMP_HEX, 'sysDescr.0', 'SNMPv2-MIB', trim($sysdescr_ftos_hex, '"')),

      // SNMPv2-MIB::sysObjectID.0 = OID: SNMPv2-SMI::enterprises.6027.1.3.14
      array( $community_ftos,                OBS_QUOTES_TRIM, 'sysObjectID.0', 'SNMPv2-MIB', 'SNMPv2-SMI::enterprises.6027.1.3.14'),
      array( $community_ftos,               OBS_SNMP_NUMERIC, 'sysObjectID.0', 'SNMPv2-MIB', '.1.3.6.1.4.1.6027.1.3.14'),

      //NET-SNMP-EXTEND-MIB::nsExtendOutLine."distro".1 = STRING: Linux|4.4.0-77-generic|amd64|Ubuntu|16.04|kvm
      array($community_ubuntu,               OBS_QUOTES_TRIM, 'nsExtendOutLine."distro".1', 'NET-SNMP-EXTEND-MIB', 'Linux|4.4.0-77-generic|amd64|Ubuntu|16.04|kvm'),
      array($community_ubuntu,              OBS_SNMP_ALL_HEX, 'nsExtendOutLine."distro".1', 'NET-SNMP-EXTEND-MIB', $distro_ubuntu_hex),
    );
  }

  /**
  * @dataProvider providerSnmp_UTF8
  * @group snmpfix
  */
  public function testSnmp_UTF8($community, $oid, $mib, $result)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    $device = build_initial_device_array($snmpsimd_ip, 'utf8-test', 'v2c', $snmpsimd_port, 'udp');
    $device['snmp_timeout'] = 2;
    $device['snmp_retries'] = 1;
    //var_dump($device);

    $test = snmp_get_oid($device, 'sysLocation.0', 'SNMPv2-MIB');
    if ($test === 'Rue de la M..tallurgie, 4530 Villers-le-Bouillet, Belgi..')
    {
      $this->markTestSkipped('On this system used NET-SNMP with UTF8 issue. See: https://sourceforge.net/p/net-snmp/bugs/2815/');
    }

    $device['snmp_community'] = $community;
    $test = snmp_get_oid($device, $oid, $mib);
    //var_dump($test);
    $this->assertSame($result, snmp_fix_string($test));
  }

  public function providerSnmp_UTF8()
  {
    $sysDescr = 'D-Link £¨India£© Limited Internetwork Operating System Software
1705 Series Software, Version 1.0.7F (BASE), RELEASE SOFTWARE
Copyright (c) 2007 by D-Link £¨India£© Limited
Compiled: 2008-01-31 14:25:20 by system, Image text-base: 0x10000
ROM: System Bootstrap, Version 0.8.2
Serial num:000H682000022, ID num:009675
System image file is "Router.bin"
DI-1705 (RISC)
32768K bytes of memory,3584K bytes of flash';
    $sysLocation = 'Rue de la Métallurgie, 4530 Villers-le-Bouillet, België';
    $sysContact  = '"Майк Ступалов" <mike@observium.ru>';
    return array(
      array('utf8-test',  'sysDescr.0',    'SNMPv2-MIB', $sysDescr),
      array('utf8-test',  'sysLocation.0', 'SNMPv2-MIB', $sysLocation),
      array('utf8-test',  'sysContact.0',  'SNMPv2-MIB', $sysContact),
      array('utf8-test2', 'ifAlias.556',   'IF-MIB',     'Cust: Priva De-Lier AZURE SID00829 {XC.1001.2015036}â'),
      array('utf8-test3', 'ifAlias.521',   'IF-MIB',     'Tránsito Cogent (AS174): IPv4 1-182344586, IPv6 1-182344646;'),
    );
  }

  /**
  * @dataProvider providerSnmpGetMultiOid
  * @group snmpget
  */
  public function testSnmpGetMultiOid($community, $flags, $oid, $mib, $result)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    $device = build_initial_device_array($snmpsimd_ip, $community, 'v2c', $snmpsimd_port, 'udp');
    $device['snmp_timeout'] = 2;
    $device['snmp_retries'] = 1;
    //var_dump($device);
    $test = snmp_get_multi_oid($device, $oid, array(), $mib, NULL, $flags);
    //var_dump($test);
    $this->assertSame($result, $test);
  }

  public function providerSnmpGetMultiOid()
  {
    $community_ftos     = 'ftos-9.10';
    $community_ubuntu   = 'ubuntu-16.04';

    return array(
      array( $community_ftos,                OBS_QUOTES_TRIM,   'sysUpTime.0 sysObjectID.0', 'SNMPv2-MIB',
            array(0 => array('sysUpTime' => '459:18:26:40.96', 'sysObjectID' => 'enterprises.6027.1.3.14'))
      ),
      array( $community_ftos,                OBS_QUOTES_TRIM,   array('sysUpTime.0', 'sysObjectID.0'), 'SNMPv2-MIB',
            array(0 => array('sysUpTime' => '459:18:26:40.96', 'sysObjectID' => 'enterprises.6027.1.3.14'))
      ),
      array( $community_ftos,           OBS_SNMP_ALL_NUMERIC,   'sysUpTime.0 sysObjectID.0', 'SNMPv2-MIB',
            array('.1.3.6.1.2.1.1.3.0' => '459:18:26:40.96', '.1.3.6.1.2.1.1.2.0' => '.1.3.6.1.4.1.6027.1.3.14')
      ),
      array( $community_ftos,     OBS_SNMP_ALL_NUMERIC_INDEX,   'sysUpTime.0 sysObjectID.0', 'SNMPv2-MIB',
            array(0 => array('sysUpTime' => '459:18:26:40.96', 'sysObjectID' => 'enterprises.6027.1.3.14'))
      ),

      array( $community_ftos,                OBS_QUOTES_TRIM,   'bgpLocalAs.0 bgpPeerState.64.72.74.193', 'BGP4-MIB',
            array(0 => array('bgpLocalAs' => '20282'), '64.72.74.193' => array('bgpPeerState' => 'idle'))
      ),
      array( $community_ftos,              OBS_SNMP_ALL_ENUM,   'bgpLocalAs.0 bgpPeerState.64.72.74.193', 'BGP4-MIB',
            array(0 => array('bgpLocalAs' => '20282'), '64.72.74.193' => array('bgpPeerState' => '1'))
      ),

      //NET-SNMP-EXTEND-MIB::nsExtendOutLine."distro".1 = STRING: Linux|4.4.0-77-generic|amd64|Ubuntu|16.04|kvm
      array( $community_ubuntu,              OBS_QUOTES_TRIM,   'nsExtendOutLine."distro".1 nsExtendOutput1Line."distro"', 'NET-SNMP-EXTEND-MIB',
            array('distro.1' => array('nsExtendOutLine'     => 'Linux|4.4.0-77-generic|amd64|Ubuntu|16.04|kvm'),
                  'distro'   => array('nsExtendOutput1Line' => 'Linux|4.4.0-77-generic|amd64|Ubuntu|16.04|kvm'))
      ),

    );
  }

  /**
   * @dataProvider providerSnmpWalkBareOid
   * @group snmpwalk_new
   */
  public function testSnmpWalkBareOid($community, $flags, $oids, $mib, $result_file)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    $device = build_initial_device_array($snmpsimd_ip, $community, 'v2c', $snmpsimd_port, 'udp');
    $device['snmp_timeout'] = 2;
    $device['snmp_retries'] = 1;
    //var_dump($device);
    $test = array();
    foreach ((array)$oids as $oid)
    {
      $test = snmpwalk_cache_bare_oid($device, $oid, $test, $mib, NULL, $flags);
    }
    //echo PHP_EOL . json_encode($test, JSON_PRETTY_PRINT) . PHP_EOL;
    //var_dump($test);
    //$result = array();
    // Fetch array from JSON file
    $result = json_decode(file_get_contents(dirname(__FILE__) . '/data/snmp/' . $result_file), TRUE);
    $this->assertSame($result, $test);
  }

  public function providerSnmpWalkBareOid()
  {
    $community  = 'delta-rt-ups';

    return array(

      array( $community,                OBS_QUOTES_TRIM,   array('dupsIdentManufacturer', 'dupsIdentModel', 'wrongOid'), 'DeltaUPS-MIB',
             'snmp_walk_bare_oid-1.json' // Link to result json
      ),
    );
  }

  /**
  * @dataProvider providerSnmpWalkMultiPartOid
  * @group snmpwalk_new
  */
  public function testSnmpWalkMultiPartOid($community, $flags, $oids, $mib, $result_file)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    $device = build_initial_device_array($snmpsimd_ip, $community, 'v2c', $snmpsimd_port, 'udp');
    $device['snmp_timeout'] = 2;
    $device['snmp_retries'] = 1;
    //var_dump($device);
    $test = array();
    foreach ((array)$oids as $oid)
    {
      $test = snmpwalk_multipart_oid($device, $oid, $test, $mib, NULL, $flags);
    }
    //echo PHP_EOL . json_encode($test, JSON_PRETTY_PRINT) . PHP_EOL;
    //var_dump($test);
    //$result = array();
    // Fetch array from JSON file
    $result = json_decode(file_get_contents(dirname(__FILE__) . '/data/snmp/' . $result_file), TRUE);
    $this->assertSame($result, $test);
  }

  public function providerSnmpWalkMultiPartOid()
  {
    $community_ftos     = 'ftos-9.10';
    $community_ubuntu   = 'ubuntu-16.04';
    $community_lldp     = 'lldp1';
    $community_prnt     = 'prnt';
    $community_delta    = 'delta-rt-ups';

    return array(

      array( $community_ftos,                OBS_QUOTES_TRIM,   array('bgpPeerState', 'bgpPeerAdminStatus', 'wrongOid'), 'BGP4-MIB',
            'snmp_walk_multipart_oid-1.json' // Link to result json
      ),
      array( $community_ftos,           OBS_SNMP_ALL_NUMERIC,   array('bgpPeerState', 'bgpPeerAdminStatus', 'wrongOid'), 'BGP4-MIB',
            'snmp_walk_multipart_oid-2.json' // Link to result json
      ),

      array( $community_ubuntu,              OBS_QUOTES_TRIM,   array('nsExtendNumEntries', 'nsExtendOutputFull', 'nsExtendOutLine'), 'NET-SNMP-EXTEND-MIB',
            'snmp_walk_multipart_oid-3.json' // Link to result json
      ),

      array( $community_delta, OBS_SNMP_ALL | OBS_SNMP_NOINDEX,   array('dupsIdentManufacturer', 'dupsIdentModel'), 'DeltaUPS-MIB',
             'snmp_walk_multipart_oid-4.json' // Link to result json
      ),

      array( $community_lldp,         OBS_SNMP_ALL_MULTILINE,   array('lldpRemChassisIdSubtype', 'lldpRemChassisId', 'lldpRemPortIdSubtype', 'lldpRemPortId', 'lldpRemPortDesc', 'lldpRemSysName', 'lldpRemSysDesc'), 'LLDP-MIB',
             'snmp_walk_multipart_lldp-1.json' // Link to result json
      ),
      array( $community_lldp,                   OBS_SNMP_ALL,   array('lldpRemChassisIdSubtype', 'lldpRemChassisId', 'lldpRemPortIdSubtype', 'lldpRemPortId', 'lldpRemPortDesc', 'lldpRemSysName'), 'LLDP-MIB',
             'snmp_walk_multipart_lldp-2.json' // Link to result json
      ),

      array( $community_prnt,                   OBS_SNMP_ALL,   array('prtMarkerSuppliesDescription', 'prtMarkerSuppliesType', 'prtMarkerSuppliesLevel'), 'Printer-MIB',
             'snmp_walk_multipart_prnt-1.json' // Link to result json
      ),
      array( $community_prnt,         OBS_SNMP_ALL_MULTILINE,   array('prtMarkerSuppliesDescription', 'prtMarkerSuppliesType', 'prtMarkerSuppliesLevel'), 'Printer-MIB',
             'snmp_walk_multipart_prnt-2.json' // Link to result json
      ),
      array( $community_prnt,             OBS_SNMP_ALL_ASCII,   array('prtMarkerSuppliesDescription', 'prtMarkerSuppliesType', 'prtMarkerSuppliesLevel'), 'Printer-MIB',
             'snmp_walk_multipart_prnt-3.json' // Link to result json
      ),

    );
  }

  /**
  * @dataProvider providerSnmpWalkThreepartOid
  * @group snmpwalk
  */
  public function testSnmpWalkThreepartOid($community, $flags, $oid, $mib, $result_file)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    $device = build_initial_device_array($snmpsimd_ip, $community, 'v2c', $snmpsimd_port, 'udp');
    $device['snmp_timeout'] = 2;
    $device['snmp_retries'] = 1;
    //var_dump($device);
    $test = snmpwalk_cache_threepart_oid($device, $oid, array(), $mib, NULL, $flags);
    //echo PHP_EOL . json_encode($test, JSON_PRETTY_PRINT) . PHP_EOL;
    //var_dump($test);
    // Fetch array from JSON file
    $result = json_decode(file_get_contents(dirname(__FILE__) . '/data/snmp/' . $result_file), TRUE);
    $this->assertSame($result, $test);
  }

  public function providerSnmpWalkThreepartOid()
  {
    $community_lldp1    = 'lldp1';
    $community_lldp2    = 'lldp2';

    return array(
      array( $community_lldp1,                   OBS_SNMP_ALL,   'lldpRemSysDesc',         'LLDP-MIB',
             'snmp_walk_lldp-1.json' // Link to result json
      ),
      array( $community_lldp1, OBS_SNMP_ALL | OBS_SNMP_CONCAT,   'lldpRemSysDesc',         'LLDP-MIB',
             'snmp_walk_lldp-2.json' // Link to result json
      ),

      array( $community_lldp2,                   OBS_SNMP_ALL,   'lldpRemSysDesc',         'LLDP-MIB',
             'snmp_walk_lldp-3.json' // Link to result json
      ),
      array( $community_lldp2, OBS_SNMP_ALL | OBS_SNMP_CONCAT,   'lldpRemSysDesc',         'LLDP-MIB',
             'snmp_walk_lldp-3.json' // Link to result json
      ),
    );
  }

}

// EOF
