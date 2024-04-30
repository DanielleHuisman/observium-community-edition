<?php
/**
* Observium
*
*   This file is part of Observium.
*
* @package    observium
* @subpackage tests
* @author     Observium Limited <http://www.observium.org/>
*
*/

$base_dir = realpath(__DIR__ . '/..');
$config['install_dir'] = $base_dir;

//define('OBS_DEBUG', 2);

include(__DIR__ . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php'); // Do not include user editable config here
include(__DIR__ . "/../includes/polyfill.inc.php");
include(__DIR__ . "/../includes/autoloader.inc.php");
include(__DIR__ . "/../includes/debugging.inc.php");
require_once(__DIR__ ."/../includes/constants.inc.php");
include(__DIR__ . '/../includes/common.inc.php');
include(__DIR__ . '/../includes/definitions.inc.php');
//include(dirname(__FILE__) . '/data/test_definitions.inc.php'); // Fake definitions for testing
include(__DIR__ . '/../includes/functions.inc.php');

class IncludesSnmpTest extends \PHPUnit\Framework\TestCase
{
  /**
  * @dataProvider providerMibDirs
  * @group mib
  */
  public function testMibDirs($result, $value)
  {
    global $config;

    $config['mib_dir'] = '/opt/observium/mibs';

    $this->assertSame($result, mib_dirs($value));
  }

  public function providerMibDirs()
  {
    $results = array(
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp', ''),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp', ['rfc', 'net-snmp']),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/cisco', 'cisco'),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/areca:/opt/observium/mibs/dell', ['areca', 'dell']),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/cisco:/opt/observium/mibs/dell:/opt/observium/mibs/broadcom:/opt/observium/mibs/netgear', array('cisco','dell', 'broadcom','netgear')),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/d-link:/opt/observium/mibs/d_link', array('d-link','d_link')),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/dell', array('inv@lid.name','dell')),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/banana', ['banana', '######']),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/banana', ['banana', 'banana']),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs', 'mibs'),
    );
    return $results;
  }

  /**
  * @dataProvider providerSnmpMib2MibDir
  * @group mib
  */
  public function testSnmpMib2MibDir($result, $mib)
  {
    global $config;

    $config['mib_dir'] = '/opt/observium/mibs';

    $this->assertSame($result, snmp_mib2mibdirs($mib));

    // Test with alternative install/mib dirs
    $config['mib_dir'] = '/appl/observium/mibs';
    $dirs = str_replace('/opt/observium', '/appl/observium', $result);
    //var_dump($dirs);

    $this->assertSame($dirs, snmp_mib2mibdirs($mib));
  }

  public function providerSnmpMib2MibDir()
  {
    $results = array(
      // Basic
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp', 'HOST-RESOURCES-MIB'),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp', 'HOST-RESOURCES-MIB:HOST-RESOURCES-TYPES'),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/cisco', 'CISCO-RTTMON-MIB'),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/cisco', 'ENTITY-MIB:CISCO-ENTITY-VENDORTYPE-OID-MIB'),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/cisco:/opt/observium/mibs/broadcom', 'ENTITY-MIB:CISCO-ENTITY-VENDORTYPE-OID-MIB:FASTPATH-SWITCHING-MIB'),
      // Unknown
      //array('/opt/observium/mibs', 'HOST-RESOURCES'),
      array('/opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp', 'HOST-RESOURCES'),
    );

    return $results;
  }

  /**
  * @dataProvider providerSnmpMibEntityVendortype
  * @group mib
  */
  public function testSnmpMibEntityVendortype($device, $mib, $result)
  {
    //global $config;
    //
    //$config['mib_dir'] = '/opt/observium/mibs';

    $this->assertSame($result, snmp_mib_entity_vendortype($device, $mib));
  }

  public function providerSnmpMibEntityVendortype()
  {
    $device_linux = array('device_id' => 999, 'os' => 'linux');
    $device_ios   = array('device_id' => 998, 'os' => 'ios');
    $device_vrp   = array('device_id' => 997, 'os' => 'vrp');

    $results = array(
      // Basic, no additional mibs
      array($device_linux, 'UCD-SNMP-MIB', 'UCD-SNMP-MIB'),
      // Expand
      array($device_linux, 'ENTITY-MIB', 'ENTITY-MIB:CISCO-ENTITY-VENDORTYPE-OID-MIB'),
      array($device_ios,   'ENTITY-MIB', 'ENTITY-MIB:CISCO-ENTITY-VENDORTYPE-OID-MIB'),
      array($device_vrp,   'ENTITY-MIB', 'ENTITY-MIB:HUAWEI-TC-MIB:HUAWEI-ENTITY-VENDORTYPE-MIB:H3C-ENTITY-VENDORTYPE-OID-MIB'),
    );
    return $results;
  }

  /**
  * @dataProvider providerSnmpDewrap32bit
  * @group numbers
  */
  public function testSnmpDewrap32bit($result, $value)
  {
    $this->assertSame($result, snmp_dewrap32bit($value));
  }

  public function providerSnmpDewrap32bit()
  {
    return array(
      array(         0,           0),
      array(     65000,       65000),
      array(   '65000',     '65000'),
      array(        '',          ''),
      array(  'some.0',    'some.0'),
      array(     FALSE,       FALSE),
      // Here wrong (negative) 32bit values
      array(4294967289,        '-7'),
      array(4200000080, '-94967216'),
      array(4200000066,   -94967230),
    );
  }

  /**
  * @dataProvider providerSnmpSize64HighLow
  * @group numbers
  */
  public function testSnmpSize64HighLow($high, $low, $result)
  {
    $this->assertSame($result, snmp_size64_high_low($high, $low));
  }

  public function providerSnmpSize64HighLow()
  {
    return array(
      array(         0,           0, 0),
      array(     65000,           0, 279172874240000),
      array(   '65000',     '65000', 279172874305000),
      array(       '0',     '65000', 65000),
      array(4294967295,  4294967295, 18446744073709551615), // Max 32/64bit value
    );
  }

    /**
    * @dataProvider providerSnmpFixNumeric
    * @group numbers
    */
    public function testSnmpFixNumeric($value, $result, $unit) {
        $this->assertSame($result, snmp_fix_numeric($value, $unit));
    }

    public function providerSnmpFixNumeric() {

        $array = [
            array(         0,           0),
            array(  '-65000',      -65000),
            array(        '',          ''),
            array(  'Some.0',    'Some.0'),
            array(     FALSE,       FALSE),
            array(4200000066,  4200000066),
            // Here numeric fixes
            array('"-7"',              -7),
            array('+7',                 7),
            array('  20,4',          20.4),
            array('4,200000067', 4.200000067),
            array('" -002.4336 dBm: Normal "', -2.4336),
            array('"66.1 C (151.0 F)"', 66.1),
            array('"36 C/96 F"', 36),
            array('"8232W"', 8232),
            array('"1628W (+/- 3.0%)"', 1628),
            array('3.09(W-)', 3.09),
            array('-26.02(A-)', -26.02),
            array('-0.00(A-)', 0.0),
            // Convert some passed units
            array('512 MB', 512),
            array('512 MB', 536870912.0, 'bytes'),
            array('119.1 GB', 119.1),
            array('119.1 GB', 127882651238.4, 'bytes'),
            array('0x01', 1, 'hex'),
            array('0x00', 0, 'hex'),
            // More complex
            array('CPU Temperature-Ctlr B: 58 C 136.40F',   58),
            array('Capacitor Cell 1 Voltage-Ctlr B: 2.04V', 2.04),
            array('Voltage 12V Rail Loc: left-PSU: 12.22V', 12.22),
            array('Current 12V Rail Loc: right-PSU: 9.53A', 9.53),
            array('Capacitor Charge-Ctlr B: 100%',          100),
            array('Spinning at 5160 RPM',                   5160),
            // Split
            array('42.50 ,35.97 ,40.64 ,40.38', 42.5,  'split1'),
            array('42.50 ,35.97 ,40.64 ,40.38', 35.97, 'split2'),
            array('42.50 ,35.97 ,40.64 ,40.38', 40.64, 'split3'),
            array('42.50 ,35.97 ,40.64 ,40.38', 40.38, 'split4'),

            array('CPU Load (100ms, 1s, 10s) : 0%, 2%, 3%', 0, 'split1'),
            array('CPU Load (100ms, 1s, 10s) : 0%, 2%, 3%', 2, 'split2'),
            array('CPU Load (100ms, 1s, 10s) : 0%, 2%, 3%', 3, 'split3'),
            [ "    5 Secs (  6.510%)   60 Secs (  7.724%)  300 Secs (  6.3812%)", 6.510,  'split1' ],
            [ "    5 Secs (  6.510%)   60 Secs (  7.724%)  300 Secs (  6.3812%)", 7.724,  'split2' ],
            [ "    5 Secs (  6.510%)   60 Secs (  7.724%)  300 Secs (  6.3812%)", 6.3812, 'split3' ],
            [ "5 Sec (6.99%),    1 Min (6.72%),   5 Min (9.06%)", 9.06, 'split3' ],
            [ "20% (cpu1: 28%   cpu2: 13%)", 20 ],
            [ "20% (cpu1: 28%   cpu2: 13%)", 28, 'split1' ],
            [ "20% (cpu1: 28%   cpu2: 13%)", 13, 'split2' ],
        ];

        // Split lanes
        $split_lanes = '
Lane  1:  1.01 dBm
Lane  2:  1.29 dBm
Lane  3:   2.1 dBm
Lane  4:  2.71 dBm';
        $array[] = [ $split_lanes, 1.01,  'split_lane1' ];
        $array[] = [ $split_lanes, 1.29,  'split_lane2' ];
        $array[] = [ $split_lanes, 2.1,   'split_lane3' ];
        $array[] = [ $split_lanes, 2.71,  'split_lane4' ];

        foreach ($array as $index => $entry) {
            if (!isset($entry[2])) {
                $array[$index][] = NULL;
            }
        }

        return $array;
    }

  /**
  * @dataProvider providerSnmpFixString
  * @group string
  */
  /* Needed real data for test
  public function testSnmpFixString($value, $result)
  {
    $this->assertSame($result, snmp_fix_string($value));
  }

  public function providerSnmpFixString()
  {
    return array(
      array("This is a &#269;&#x5d0; test&#39; &#250;", "This is a čא test' ú"),
      array("P<FA>lt stj<F3>rnst<F6><F0>",              "Púlt stjórnstöð"),
    );
  }
  */

  /**
  * @dataProvider providerSnmpHexString
  * @group string
  */
  public function testSnmpHexString($value, $result)
  {
    $this->assertSame($result, snmp_hexstring($value));
  }

  public function providerSnmpHexString()
  {
    return array(
      array("42 6C 61 63 6B 20 43 61 72 74 72 69 64 67 65 20", "Black Cartridge "),
      array("42 6C 61 63 6B 20 43 61 72 74 72 69 64 67 65 20 38 31 58 20 48 50 20 43 46 32 38 31 58 00 ", "Black Cartridge 81X HP CF281X"),
      array("Maintenance Kit HP 110V-F2G76A, 220V-F2G77A.",    "Maintenance Kit HP 110V-F2G76A, 220V-F2G77A."),
      array('4A 7D 34 3D',                                     'J}4='),
      array('73 70 62 2D    6F 66 66 2D 67 77',                'spb-off-gw'),
      array('32 35 00 ',                                       '25'),
      // UTF-8
      array("44 61 74 61 3A 20 41 20 41 64 64 72 65 73 73 3A 20 32 32 35 2E 35 2E 31 2E 36 20 41 6C 69 61 73 3A 20 D0 94 D1 80 D0 B0 D0 B9 D0 B2 20 62 75 66 66 65 72 20 61 64 64 72 65 73 73 20 63 68 61 6E 67 65 64 20 31 31 30 34", 'Data: A Address: 225.5.1.6 Alias: Драйв buffer address changed 1104'),
      array("C3 9C 62 65 72 74 72 61 67 75 6E 67 73 77 61 6C 7A 65 2C 20 50 4E 20 31 31 35 52 30 30 31 32 36", "Übertragungswalze, PN 115R00126"),
      array("50 43 24 72 6E 75", 'PC$rnu'), // Incorrectly encoded UTF8, see: https://jira.observium.org/browse/OBS-4377
      array("50 C3 A4 72 6E 75", "Pärnu"),
      // Multiline string
      array("67 6F 6F 67 6C 65 2E 73 65 00 6E 61 6D 65 2D 73 65 72 76 65 72 00 31 37 32 2E 31 37 2E 32 30 34 2E 31 30 00", "google.se\nname-server\n172.17.204.10"),
      //Incorrect HEX strings
      array('496E707574203100',         '496E707574203100'), // HEX string without spaces (not SNMP HEX STRING)
      array('49 6E 70 75 74 20 31 0',   '49 6E 70 75 74 20 31 0'),
      array('Simple String',            'Simple String'),
      array('49 6E 70 75 74 20 31 0R ', '49 6E 70 75 74 20 31 0R '),
      // 2char strings
      array('10',                       '10'), // string
      array('10 ',                      hex2str('10', '')), // hex
      array('99',                       '99'), // string
      array('99 ',                      hex2str('99', '')), // hex
      array('A1',                       'A1'), // string
      array('A1 ',                      hex2str('A1', '')), // hex
      array('B3',                       'B3'), // string
      array('B3 ',                      hex2str('B3', '')), // hex
      array('FF',                       'FF'), // string
    );
  }

  /**
  * @dataProvider providerSnmpStringToOid
  * @group index
  */
  public function testSnmpStringToOid($value, $result)
  {
    $this->assertSame($result, snmp_string_to_oid($value));
  }

  /**
  * @dataProvider providerSnmpStringToOid
  * @group index
  */
  public function testSnmpOidToString($result, $value)
  {
    $this->assertSame((string)$result, snmp_oid_to_string($value));
  }

  public function providerSnmpStringToOid()
  {
    return array(
      array(                     '',                                    '0'),
      array(                    ' ',                                 '1.32'),
      array(                      0,                                 '1.48'),
      array(               '-65000',                  '6.45.54.53.48.48.48'),
      array(               'Some.0',               '6.83.111.109.101.46.48'),
      array(            'Observium',  '9.79.98.115.101.114.118.105.117.109'),
      array('Observium Great Again',  '21.79.98.115.101.114.118.105.117.109.32.71.114.101.97.116.32.65.103.97.105.110'),
    );
  }

  /**
  * @dataProvider providerSnmpParseLine
  * @group index
  */
  public function testSnmpParseLine($flags, $value, $result)
  {
    $this->assertSame($result, snmp_parse_line($value, $flags));
  }

  public function providerSnmpParseLine()
  {
    $flags = OBS_SNMP_ALL;
    return array(
      array($flags,
            'jnxVpnPwLocalSiteId.l2Circuit."ge-0/1/1.0".621 = "some"',
            array('oid'       => 'jnxVpnPwLocalSiteId.l2Circuit."ge-0/1/1.0".621',
                  'value'     => 'some',
                  'oid_name'  => 'jnxVpnPwLocalSiteId',
                  'index_parts' => array('l2Circuit', 'ge-0/1/1.0', '621'),
                  'index_count' => 3,
                  'index'     => 'l2Circuit.ge-0/1/1.0.621',
            ),
      ),
      array($flags | OBS_SNMP_INDEX_PARTS,
            'jnxVpnPwLocalSiteId.l2Circuit."ge-0/1/1.0".621 = "some"',
            array('oid'       => 'jnxVpnPwLocalSiteId.l2Circuit."ge-0/1/1.0".621',
                  'value'     => 'some',
                  'oid_name'  => 'jnxVpnPwLocalSiteId',
                  'index_parts' => array('l2Circuit', 'ge-0/1/1.0', '621'),
                  'index_count' => 3,
                  'index'     => 'l2Circuit->ge-0/1/1.0->621',
            ),
      ),
      array($flags,
            'vacmSecurityModel.0."wes" = xxx',
            array('oid'       => 'vacmSecurityModel.0."wes"',
                  'value'     => 'xxx',
                  'oid_name'  => 'vacmSecurityModel',
                  'index_parts' => array('0', 'wes'),
                  'index_count' => 2,
                  'index'     => '0.wes',
            ),
      ),
      array($flags,
            'nodeUuid.\'WSLNetapp02-01\' = d924dfcf-418e-11eb-91eb-d039ea255860',
            array('oid'       => 'nodeUuid.\'WSLNetapp02-01\'',
                  'value'     => 'd924dfcf-418e-11eb-91eb-d039ea255860',
                  'oid_name'  => 'nodeUuid',
                  'index_parts' => array('WSLNetapp02-01'),
                  'index_count' => 1,
                  'index'     => 'WSLNetapp02-01',
            ),
      ),
      array($flags,
            'vacmSecurityModel.0.3.119.101.115 = xxx',
            array('oid'       => 'vacmSecurityModel.0.3.119.101.115',
                  'value'     => 'xxx',
                  'oid_name'  => 'vacmSecurityModel',
                  'index_parts' => array('0', '3', '119', '101', '115'),
                  'index_count' => 5,
                  'index'     => '0.3.119.101.115',
            ),
      ),
      array($flags,
            'jnxFWCounterDisplayName."__flowspec_default_inet__"."0/0,*,proto=17,port=445".counter = "0/0,*,proto=17,port=445"',
            array('oid'       => 'jnxFWCounterDisplayName."__flowspec_default_inet__"."0/0,*,proto=17,port=445".counter',
                  'value'     => '0/0,*,proto=17,port=445',
                  'oid_name'  => 'jnxFWCounterDisplayName',
                  'index_parts' => array('__flowspec_default_inet__', '0/0,*,proto=17,port=445', 'counter'),
                  'index_count' => 3,
                  'index'     => '__flowspec_default_inet__.0/0,*,proto=17,port=445.counter',
            ),
      ),
      array($flags,
            'jnxFWCounterDisplayName."__flowspec_default_inet__"."205.213.5.242,*,proto=17,port=123".counter = "205.213.5.242,*,proto=17,port=123"',
            array('oid'       => 'jnxFWCounterDisplayName."__flowspec_default_inet__"."205.213.5.242,*,proto=17,port=123".counter',
                  'value'     => '205.213.5.242,*,proto=17,port=123',
                  'oid_name'  => 'jnxFWCounterDisplayName',
                  'index_parts' => array('__flowspec_default_inet__', '205.213.5.242,*,proto=17,port=123', 'counter'),
                  'index_count' => 3,
                  'index'     => '__flowspec_default_inet__.205.213.5.242,*,proto=17,port=123.counter',
            ),
      ),
      array($flags | OBS_SNMP_CONCAT,
            'lldpRemChassisId.0.71.31591 = "08 2E 5F 17 E7 71 "',
            array('oid'       => 'lldpRemChassisId.0.71.31591',
                  'value'     => '08 2E 5F 17 E7 71 ',
                  'oid_name'  => 'lldpRemChassisId',
                  'index_parts' => array('0', '71', '31591'),
                  'index_count' => 3,
                  'index'     => '0.71.31591',
            ),
      ),
      array($flags | OBS_SNMP_TABLE,
            'ipv6RouteIfIndex[3ffe:100:ff00:0:0:0:0:0][64][1] = 2',
            array('oid'       => 'ipv6RouteIfIndex[3ffe:100:ff00:0:0:0:0:0][64][1]',
                  'value'     => '2',
                  'oid_name'  => 'ipv6RouteIfIndex',
                  'index_parts' => array('3ffe:100:ff00:0:0:0:0:0', '64', '1'),
                  'index_count' => 3,
                  'index'     => '3ffe:100:ff00:0:0:0:0:0.64.1',
            ),
      ),
      array($flags | OBS_SNMP_TABLE,
            'ipv6RouteIfIndex[3ffe:100:ff00:0:0:0:0:0][64].1 = 2',
            array('oid'       => 'ipv6RouteIfIndex[3ffe:100:ff00:0:0:0:0:0][64].1',
                  'value'     => '2',
                  'oid_name'  => 'ipv6RouteIfIndex',
                  'index_parts' => array('3ffe:100:ff00:0:0:0:0:0', '64', '1'),
                  'index_count' => 3,
                  'index'     => '3ffe:100:ff00:0:0:0:0:0.64.1',
            ),
      ),
      array($flags | OBS_SNMP_TABLE,
            'wcAccessPointMac[6:20:c:c8:39:b].96 = 20:c:c8:39:b:60',
            array('oid'       => 'wcAccessPointMac[6:20:c:c8:39:b].96',
                  'value'     => '20:c:c8:39:b:60',
                  'oid_name'  => 'wcAccessPointMac',
                  'index_parts' => array('6:20:c:c8:39:b', '96'),
                  'index_count' => 2,
                  'index'     => '6:20:c:c8:39:b.96',
            ),
      ),
      array($flags | OBS_SNMP_TABLE,
            'wcAccessPointMac[6:20:c:c8:39:b].96.1 = 20:c:c8:39:b:60',
            array('oid'       => 'wcAccessPointMac[6:20:c:c8:39:b].96.1',
                  'value'     => '20:c:c8:39:b:60',
                  'oid_name'  => 'wcAccessPointMac',
                  'index_parts' => array('6:20:c:c8:39:b', '96', '1'),
                  'index_count' => 3,
                  'index'     => '6:20:c:c8:39:b.96.1',
            ),
      ),
      array($flags | OBS_SNMP_TABLE,
            'wcAccessPointMac[6:20:c:c8:39:b].96."qkd dj" = 20:c:c8:39:b:60',
            array('oid'       => 'wcAccessPointMac[6:20:c:c8:39:b].96."qkd dj"',
                  'value'     => '20:c:c8:39:b:60',
                  'oid_name'  => 'wcAccessPointMac',
                  'index_parts' => array('6:20:c:c8:39:b', '96', 'qkd dj'),
                  'index_count' => 3,
                  'index'     => '6:20:c:c8:39:b.96.qkd dj',
            ),
      ),
      array($flags | OBS_SNMP_TABLE | OBS_SNMP_INDEX_PARTS,
            'rlIpAddressPrefixLength[ipv6z]["fe:80:00:00:00:00:00:00:86:78:ac:ff:fe:a3:3f:49%100000"] = 64',
            array('oid'       => 'rlIpAddressPrefixLength[ipv6z]["fe:80:00:00:00:00:00:00:86:78:ac:ff:fe:a3:3f:49%100000"]',
                  'value'     => '64',
                  'oid_name'  => 'rlIpAddressPrefixLength',
                  'index_parts' => array('ipv6z', 'fe:80:00:00:00:00:00:00:86:78:ac:ff:fe:a3:3f:49%100000'),
                  'index_count' => 2,
                  'index'     => 'ipv6z->fe:80:00:00:00:00:00:00:86:78:ac:ff:fe:a3:3f:49%100000',
            ),
      ),
      array($flags | OBS_SNMP_TABLE | OBS_SNMP_INDEX_PARTS,
            'wcAccessPointMac[6:20:c:c8:39:b].96."qkd dj" = 20:c:c8:39:b:60',
            array('oid'       => 'wcAccessPointMac[6:20:c:c8:39:b].96."qkd dj"',
                  'value'     => '20:c:c8:39:b:60',
                  'oid_name'  => 'wcAccessPointMac',
                  'index_parts' => array('6:20:c:c8:39:b', '96', 'qkd dj'),
                  'index_count' => 3,
                  'index'     => '6:20:c:c8:39:b->96->qkd dj',
            ),
      ),
      array($flags | OBS_SNMP_NUMERIC,
            '.1.3.6.1.2.1.1.3.0 = 15:09:27.63',
            array('oid'       => '.1.3.6.1.2.1.1.3.0',
                  'value'     => '15:09:27.63',
                  'index_count' => 1,
                  'index'     => '.1.3.6.1.2.1.1.3.0',
            ),
      ),
      // Not used, but allowed:
      array($flags,
            'vacmSecurityModel.0.\"wes\" = xxx', // not used
            array('oid'       => 'vacmSecurityModel.0.\"wes\"',
                  'value'     => 'xxx',
                  'oid_name'  => 'vacmSecurityModel',
                  'index_parts' => array('0', '\"wes\"'),
                  'index_count' => 2,
                  'index'     => '0.\"wes\"',
            ),
      ),
      // WARNING for cleaners, here trailing spaces!!!
      array($flags,
            'vacmSecurityModel.0."wes" = 00 66 
            AA BB ', // not used
            array('oid'       => 'vacmSecurityModel.0."wes"',
                  'value'     => '00 66 
            AA BB',
                  'oid_name'  => 'vacmSecurityModel',
                  'index_parts' => array('0', 'wes'),
                  'index_count' => 2,
                  'index'     => '0.wes',
            ),
      ),

      // Empty, wrong
      array($flags,
            'jnxVpnPwLocalSiteId.l2Circuit."ge-0/1/1.0".621', // Complete not exist value part
            array('oid'       => 'jnxVpnPwLocalSiteId.l2Circuit."ge-0/1/1.0".621',
                  'value'     => NULL,
                  'oid_name'  => 'jnxVpnPwLocalSiteId',
                  'index_parts' => array('l2Circuit', 'ge-0/1/1.0', '621'),
                  'index_count' => 3,
                  'index'     => 'l2Circuit.ge-0/1/1.0.621',
            ),
      ),
      array($flags,
            'jnxVpnPwLocalSiteId.l2Circuit."ge-0/1/1.0".621 = ', // Value is string ''
            array('oid'       => 'jnxVpnPwLocalSiteId.l2Circuit."ge-0/1/1.0".621',
                  'value'     => '',
                  'oid_name'  => 'jnxVpnPwLocalSiteId',
                  'index_parts' => array('l2Circuit', 'ge-0/1/1.0', '621'),
                  'index_count' => 3,
                  'index'     => 'l2Circuit.ge-0/1/1.0.621',
            ),
      ),
      array($flags, // Yah, numeric passed without OBS_SNMP_NUMERIC flag
            '.1.3.6.1.2.1.1.3.0 = 15:09:27.63',
            array('oid'       => '.1.3.6.1.2.1.1.3.0',
                  'value'     => '15:09:27.63',
                  'oid_name' => '',
                  'index_parts' => array('1', '3', '6', '1', '2', '1', '1', '3', '0'),
                  'index_count' => 9,
                  'index'     => '1.3.6.1.2.1.1.3.0',
            ),
      ),
      array($flags | OBS_SNMP_NUMERIC,
            ' = 15:09:27.63',
            array('oid'       => '',
                  'value'     => '15:09:27.63',
                  'index_count' => 0,
                  'index'     => '',
            ),
      ),
      array($flags,
            '',
            array('oid'       => '',
                  'value'     => NULL,
                  'oid_name'  => '',
                  'index_parts' => array(),
                  'index_count' => 0,
                  'index'     => '',
            ),
      ),
    );
  }
}

// EOF
