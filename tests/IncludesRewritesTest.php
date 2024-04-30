<?php

// Import from CVS
require(__DIR__ . '/data/CsvFileIterator.php');

$base_dir = realpath(__DIR__ . '/..');
$config['install_dir'] = $base_dir;

// Base observium includes
include(__DIR__ . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php'); // Do not include user editable config here
include(__DIR__ . "/../includes/polyfill.inc.php");
include(__DIR__ . "/../includes/autoloader.inc.php");
include(__DIR__ . "/../includes/debugging.inc.php");
require_once(__DIR__ ."/../includes/constants.inc.php");
include(__DIR__ . '/../includes/common.inc.php');
include(__DIR__ . '/../includes/definitions.inc.php');
include(__DIR__ . '/data/test_definitions.inc.php'); // Fake definitions for testing
include(__DIR__ . '/../includes/functions.inc.php');

// for generate provider data, uncomment this and run:
// php tests/IncludesRewritesTest.php

/*
// Generate provider data
foreach (array('iosrx', 'iosxe', 'ios', 'procurve', 'vrp') as $os)
{
  foreach (array('entPhysicalDescr', 'entPhysicalName', 'hwEntityBomEnDesc') as $file)
  {
    if (!is_file(dirname(__FILE__) . "/data/$os.$file.txt")) { continue; }
    $s = fopen(dirname(__FILE__) . "/data/$os.$file.txt", 'r');
    while ($line = fgets($s))
    {
      list(,$string) = explode(' = ', $line, 2);
      $string = trim($string);
      if (!isset($valid[$string]))
      {
        $rewrite = rewrite_entity_name($string);
        $valid[$string] = $rewrite;
      }
    }
    fclose($s);
  }
}
$csv = fopen(dirname(__FILE__) . "/data/providerRewriteEntityName.csv", 'w');
foreach ($valid as $string => $rewrite)
{
  fputcsv($csv, array($string, $rewrite));
}
fclose($csv);
exit;
*/

class IncludesRewritesTest extends \PHPUnit\Framework\TestCase
{
  /**
  * @dataProvider providerRewriteEntityNameCsv
  * @group rename_entity
  */
  public function testRewriteEntityNameCsv($string, $result)
  {
    $this->assertSame($result, rewrite_entity_name($string));
  }

  public function providerRewriteEntityNameCsv()
  {
    return new CsvFileIterator(__DIR__ . '/data/providerRewriteEntityName.csv');
  }

  /**
  * @dataProvider providerRewriteEntityName
  * @group rename_entity
  */
  public function testRewriteEntityName($string, $result)
  {
    $this->assertSame($result, rewrite_entity_name($string));
  }

  public function providerRewriteEntityName()
  {
    return array(
      array('GenuineIntel Intel Celeron M processor .50GHz, 1496 MHz', 'Intel Celeron M processor .50GHz, 1496 MHz'),
      array('CPU Intel Celeron M (TM) (R)', 'Intel Celeron M'),
    );
  }

  /**
  * @dataProvider providerRewriteVendor
  * @group vendor
  */
  public function testRewriteVendor($string, $result)
  {
    $this->assertSame($result, rewrite_vendor($string));
  }

  public function providerRewriteVendor()
  {
    return array(
      // Simple rewrites
      array('Brocade Communications Systems, Inc.',   'Brocade'),
      array('Cisco Systems Inc',                      'Cisco'),
      array('Cisco Systems Inc.',                     'Cisco'),
      array('Cisco Systems, Inc.',                    'Cisco'),
      array('Juniper Networks, Inc.',                 'Juniper'),
      array('Juniper Networks',                       'Juniper'),
      array('Dell Inc.',                              'Dell'),
      array('Enterasys Networks, Inc.',               'Enterasys'),
      array('FIBERXON INC.',                          'Fiberxon'),
      array('Netgear Inc',                            'Netgear'),
      array('Volex Inc.',                             'Volex'),
      array('Broadcom Corp.',                         'Broadcom'),
      array('FINISAR CORP.',                          'Finisar'),
      array('Oracle Corporation',                     'Oracle'),
      array('Methode Elec.',                          'Methode'),
      array('Deell Computer Corporation',             'Deell'),
      array('Liebert Corporation Liebert',            'Liebert'),
      // Keep MultiCase as is
      array('OneAccess',                              'OneAccess'),
      array('3Y Power',                               '3Y Power'),
      array('Alcatel-Lucent',                         'Alcatel-Lucent'),
      array('CAS-systems',                            'CAS-systems'),
      array('VMware',                                 'VMware'),
      array('VMware, Inc.',                           'VMware'),
      array('EfficientIP',                            'EfficientIP'),
      // Small name also keep as is
      array('NHR',                                    'NHR'),
      array('OEM',                                    'OEM'),
      array('JDSU',                                   'JDSU'),
      array('TI',                                     'TI'),
      array('HP',                                     'HP'),
      // Search in definitions
      array('Hewlett-Packard',                        'HP'),
      array('Hewlett Packard',                        'HP'),
      array('Hewlett-Packard Company',                'HP'),
      array('HP Enterprise',                          'HPE'),
      array('H3C Comware',                            'HPE'),
      array('Hangzhou H3C Comware',                   'HPE'),
      array('HP Comware',                             'HPE'),
      array('Comware',                                'HPE'),
      array('Huawei Technologies Co., Ltd.',          'Huawei'),
      array('Huawei Technologies',                    'Huawei'),
      array('Huawei-3Com',                            'H3C'),
      array('3Com',                                   'H3C'),
      array('HUAWEI-3COM CORP.',                      'H3C'),
      array('Hangzhou H3C Tech. Co.',                 'H3C'),
      array('EC',                                     'Edgecore'),
      array('Edge-Core',                              'Edgecore'),
      array('CISCO-OPNEXT,INC',                       'Cisco'),
      array('Dell Computer Corporation',              'Dell'),
      array('MRV COMM, INC.',                         'MRV'),
      array('CASA-systems',                           'Casa Systems'),
      array('COMET SYSTEM, s.r.o.',                   'Comet System'),
      array('COMET SYSTEM, sro',                      'Comet System'),
      // This must keep Systems in name
      array('Open Systems',                           'Open Systems'),
      array('Open Systems AG',                        'Open Systems'),
      // Some intersects
      array('Geist',                                  'Geist'),
      array('GE',                                     'GE'),
    );
  }

  /**
   * @dataProvider providerTrimQuotes
   * @group string
   */
  public function testTrimQuotes($string, $result)
  {
    $this->assertEquals($result, trim_quotes($string));
  }

  public function providerTrimQuotes()
  {
    return array(
      array('\"sdfslfkm s\'fdsf" a;lm aamjn ',          '"sdfslfkm s\'fdsf" a;lm aamjn'),
      array('sdfslfkm s\'fdsf" a;lm aamjn \"',          'sdfslfkm s\'fdsf" a;lm aamjn "'),
      array('sdfslfkm s\'fdsf" a;lm aamjn ',            'sdfslfkm s\'fdsf" a;lm aamjn'),
      array('\"sdfslfkm s\'fdsf" a;lm aamjn \"',        'sdfslfkm s\'fdsf" a;lm aamjn '),
      array('"sdfslfkm s\'fdsf" a;lm aamjn "',          'sdfslfkm s\'fdsf" a;lm aamjn '),
      array('"\"sdfslfkm s\'fdsf" a;lm aamjn \""',      'sdfslfkm s\'fdsf" a;lm aamjn '),
      array('\'\"sdfslfkm s\'fdsf" a;lm aamjn \"\'',    'sdfslfkm s\'fdsf" a;lm aamjn '),
      array('"\'\"sdfslfkm s\'fdsf" a;lm aamjn \"\'"',  'sdfslfkm s\'fdsf" a;lm aamjn '),
      array('  \'\"sdfslfkm s\'fdsf" a;lm aamjn \"\' ', 'sdfslfkm s\'fdsf" a;lm aamjn '),
      array('"""sdfslfkm s\'fdsf" a;lm aamjn """',      'sdfslfkm s\'fdsf" a;lm aamjn '),
      array('"""sdfslfkm s\'fdsf" a;lm aamjn """"""""', 'sdfslfkm s\'fdsf" a;lm aamjn """""'),
      array('"""""""sdfslfkm s\'fdsf" a;lm aamjn """',  '""""sdfslfkm s\'fdsf" a;lm aamjn '),
      // escaped quotes
      array('\"Mike Stupalov\" <mike@observium.org>',      '"Mike Stupalov" <mike@observium.org>'),
      // utf-8
      array('Avenue Léon, België ',                     'Avenue Léon, België'),
      array('\"Avenue Léon, België \"',                 'Avenue Léon, België '),
      array('"Винни пух и все-все-все "',               'Винни пух и все-все-все '),
      // multilined
      array('  \'\"\"sdfslfkm s\'fdsf"
            a;lm aamjn \"\"\' ', 'sdfslfkm s\'fdsf"
            a;lm aamjn '),
    );
  }

  /**
   * @dataProvider providerCountryFromCode
   * @group countries
   */
  public function testCountryFromCode($string, $result)
  {
    $this->assertEquals($result, country_from_code($string));
  }

  public function providerCountryFromCode()
  {
    return array(
      array('gb',                 'United Kingdom'),
      array('gbr',                'United Kingdom'),
      array('United Kingdom',     'United Kingdom'),
      array('us',                 'United States'),
      array('usa',                'United States'),
      array('United States',      'United States'),
      array('ru',                 'Russian Federation'),
      array('rus',                'Russian Federation'),
      array('Russian Federation', 'Russian Federation'),
      array('russia',             'Russian Federation'),
      array('South Korea',        'South Korea'),
    );
  }

  /**
   * @dataProvider providerRewriteDefinitionHardware
   * @group hardware
   */
  public function testGetModelParam($os, $id, $result)
  {
    $device = array('os' => $os, 'sysObjectID' => $id);
    $this->assertEquals($result, get_model_param($device, 'hardware'));
  }

  public function providerRewriteDefinitionHardware()
  {
    return array(
      array('calix', '.1.3.6.1.4.1.6321.1.2.2.5.3', 'E7-2'),
      array('calix', '.1.3.6.1.4.1.6321.1.2.1',     'C7'),
      array('calix', '.1.3.6.1.4.1.6321',           'C7'),
      array('calix', '.1.3.6.1.4.1.6321.1.2.3',     'E5-100'),
    );
  }

  /**
   * @dataProvider providerArrayKeyReplace
   * @group replace
   */
  public function testArrayKeyReplace($string, $array, $result)
  {
    $this->assertEquals($result, array_key_replace($array, $string));
  }

  public function providerArrayKeyReplace()
  {
    $rewrite_array = array(
      'other' => 'Other',
      'rfc877x25',
      'ethernetCsmacd' => 'Ethernet',
    );

    return array(
      // empty/not exist pattern
      array('Some other', array(),                           'Some other'),
      array('Some other', array('bleh' => 'REPLACE_STRING'), 'Some other'),

      // incorrect
      array('EthernetCsmacd',          $rewrite_array,        'EthernetCsmacd'),

      // real tests
      array('other',                   $rewrite_array,        'Other'),
      array('rfc877x25',               $rewrite_array,        'rfc877x25'),
      array('ethernetCsmacd',          $rewrite_array,        'Ethernet'),
    );
  }

  /**
   * @dataProvider providerArrayStrReplace
   * @group replace
   */
  public function testArrayStrReplace($string, $array, $result)
  {
    $this->assertEquals($result, array_str_replace($array, $string));
  }

  public function providerArrayStrReplace()
  {
    $rewrite_array = array(
      'ether' => 'Ether',
      'gig'   => 'Gig',
      'fast'  => 'Fast',
      'ten'   => 'Ten',
    );

    return array(
      // empty/not exist pattern
      array('Some Text', array(),                           'Some Text'),
      array('Some Text', array('bleh' => 'REPLACE_STRING'), 'Some Text'),

      // real tests
      array('Some gigabit ethernet', $rewrite_array,        'Some Gigabit Ethernet'),
      array('Some gIgabit ETHERNET', $rewrite_array,        'Some Gigabit EtherNET'),
      array('fastethernet',          $rewrite_array,        'FastEthernet'),
    );
  }

  /**
   * @dataProvider providerArrayStrReplace2
   * @group replace
   */
  public function testArrayStrReplace2($string, $array, $result)
  {
    $this->assertEquals($result, array_str_replace($array, $string, TRUE));
  }

  public function providerArrayStrReplace2()
  {
    // Case Sensitive test
    $rewrite_array = array(
      'ether' => 'Ether',
      'gig'   => 'Gig',
      'fast'  => 'Fast',
      'ten'   => 'Ten',
    );

    return array(
      // real tests
      array('Some gigabit ethernet', $rewrite_array,        'Some Gigabit Ethernet'),
      array('Some gIgabit ETHERNET', $rewrite_array,        'Some gIgabit ETHERNET'),
      array('fastethernet',          $rewrite_array,        'FastEthernet'),
    );
  }

  /**
   * @dataProvider providerArrayPregReplace
   * @group replace
   */
  public function testArrayPregReplace($string, $array, $result)
  {
    $this->assertEquals($result, array_preg_replace($array, $string));
  }

  public function providerArrayPregReplace()
  {
    $rewrite_regexp = array(
      '/Nortel .* Module - /i' => '%TEST1%',
      '/Baystack .* - /i'     => '%TEST2%',
      '/DEC [a-z\d]+ PCI /i'  => '%TEST3%',
      '!^APC !'               => '%TEST4%',
    );

    return array(
      // empty/not exist pattern
      array('Some Text Baystack IUHIINind jawdbjdn - @@', array(),                                  'Some Text Baystack IUHIINind jawdbjdn - @@'),
      array('Some Text Baystack IUHIINind jawdbjdn - @@', array('/some text/' => 'REPLACE_STRING'), 'Some Text Baystack IUHIINind jawdbjdn - @@'),

      // real tests
      array('Some Text Nortel IUHIINind ggg Module - @@', $rewrite_regexp,                          'Some Text %TEST1%@@'),
      array('Some Text Baystack IUHIINind jawdbjdn - @@', $rewrite_regexp,                          'Some Text %TEST2%@@'),
      array('Some Text DEC ind666618368318318368 PCI @@', $rewrite_regexp,                          'Some Text %TEST3%@@'),
      array('APC  Some Text @@',                          $rewrite_regexp,                          '%TEST4% Some Text @@'),
    );
  }

  /**
   * @dataProvider providerArrayTagReplace
   * @group replace
   */
  public function testArrayTagReplace($string, $array, $result)
  {
    $this->assertEquals($result, array_tag_replace($array, $string));
  }

  public function providerArrayTagReplace()
  {
    // recursive from array
    $rfrom = array(
      'somekey' => array(
        'somekey3' => 'port-%INDEX%-%index%.rrd',
        'somekey4' => 'port-%descr%-%index%-% %.rrd',
      ),
      'somekey2' => 'port-%descr%-%index%-%.rrd',
      'perf-pollermodule-%index%.rrd',
      // Must be keep as is
      'bool' => TRUE,
      'int' => 293847,
      'null' => NULL
    );
    $rto = array(
      'somekey' => array(
        'somekey3' => 'port--0.rrd',
        'somekey4' => 'port--0-% %.rrd',
      ),
      'somekey2' => 'port--0-%.rrd',
      'perf-pollermodule-0.rrd',
      'bool' => TRUE,
      'int' => 293847,
      'null' => NULL
    );

    return array(
      // empty/not exist keys
      array('%some_key%',                            array(),                                     ''),
      array('http://some_url/%some_url%/some_url',   array('some_url' => 'REPLACE_STRING'),       'http://some_url/REPLACE_STRING/some_url'),
      // duplicate keys
      array('http://some_url/%some_url%/%some_url%', array('some_url' => 'REPLACE_STRING'),       'http://some_url/REPLACE_STRING/REPLACE_STRING'),
      // multiple keys
      array('http://some_url/%some1%/%some2%',       array('some1' => '1111', 'some2' => '2222'), 'http://some_url/1111/2222'),
      // real test
      array('perf-pollermodule-%index%.rrd',         array('index' => 0), 'perf-pollermodule-0.rrd'),
      array('port-%descr%-%index%.rrd',              array('index' => 0), 'port--0.rrd'),

      array('%url%%routing_key%',                    array('url' => 'https://alert.victorops.com/integrations/generic/20131114/alert/XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/', 'routing_key' => 'everyone'),
                                                     'https://alert.victorops.com/integrations/generic/20131114/alert/XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/everyone'),

      // Tags is case-sensitive!
      array('port-%INDEX%-%index%.rrd',              array('index' => 0), 'port--0.rrd'),
      array(1000000000,                              array('index' => 0), '1000000000'), // integer to string
      // Keep not tagged percent signs
      array('port-%descr%-%index%-%.rrd',            array('index' => 0), 'port--0-%.rrd'),
      array('port-%descr%-%index%-%%.rrd',           array('index' => 0), 'port--0-%%.rrd'),
      array('port-%descr%-%index%-% %.rrd',          array('index' => 0), 'port--0-% %.rrd'),

      // recursive arrays with string
      array($rfrom,                                  array('index' => 0), $rto),
    );
  }

  /**
   * @dataProvider providerProcessPortLabel
   * @group process
   */
  public function testProcessPortLabel($os, $array, $result)
  {
    // Port array template
    $port = array(
      'ifIndex' => '5',
      //'ifDescr' => 'GigabitEthernet0/1',
      'ifType' => 'ethernetCsmacd',
      'ifMtu' => '1500',
      'ifSpeed' => '1000000000',
      'ifPhysAddress' => '4c:4e:35:fb:c7:20',
      'ifAdminStatus' => 'up',
      'ifOperStatus' => 'up',
      'ifLastChange' => '0:0:01:03.68',
      'ifInOctets' => '2336423782',
      'ifInUcastPkts' => '56523160',
      'ifInDiscards' => '0',
      'ifInErrors' => '195562',
      'ifInUnknownProtos' => '0',
      'ifOutOctets' => '2769871040',
      'ifOutUcastPkts' => '83292747',
      'ifOutDiscards' => '0',
      'ifOutErrors' => '0',
      //'ifName' => 'Gi0/1',
      'ifInMulticastPkts' => '1',
      'ifInBroadcastPkts' => '21',
      'ifOutMulticastPkts' => '16933',
      'ifOutBroadcastPkts' => '2434835',
      'ifHCInOctets' => '10926358374',
      'ifHCInUcastPkts' => '56523160',
      'ifHCInMulticastPkts' => '1',
      'ifHCInBroadcastPkts' => '21',
      'ifHCOutOctets' => '75784315072',
      'ifHCOutUcastPkts' => '83292747',
      'ifHCOutMulticastPkts' => '16933',
      'ifHCOutBroadcastPkts' => '2434835',
      'ifLinkUpDownTrapEnable' => 'enabled',
      'ifHighSpeed' => '1000',
      'ifPromiscuousMode' => 'false',
      'ifConnectorPresent' => 'true',
      //'ifAlias' => 'Po1#2',
      'ifCounterDiscontinuityTime' => '0:0:00:32.81',
      'dot3StatsDuplexStatus' => 'fullDuplex',
    );
    foreach ($array as $oid => $value)
    {
      $port[$oid] = $value;
    }

    // Device array template
    $device = array(
      'device_id' => 99999999,
      'os' => $os,
    );
    // Process $port array
    process_port_label($port, $device);
    //var_dump($port);

    // Select specific entries for validate
    $test_array = array();
    foreach (array('port_label', 'port_label_num', 'port_label_base', 'port_label_short') as $oid)
    {
      $test_array[$oid] = $port[$oid];
    }
    // Additionally test processing ifAlias on some entries
    if (isset($result['ifAlias']))
    {
      $test_array['ifAlias'] = $port['ifAlias'];
    }

    $this->assertEquals($result, $test_array);
  }

  public function providerProcessPortLabel()
  {
    return array(
      array('ios',          array('ifDescr' => 'GigabitEthernet0/1', 'ifName' => 'Gi0/1', 'ifAlias' => 'Po1#2'),
                            array('port_label' => 'GigabitEthernet0/1', 'port_label_num' => '0/1', 'port_label_base' => 'GigabitEthernet', 'port_label_short' => 'Gi0/1')),
      array('ios',          array('ifDescr' => 'FastEthernet0/1/1', 'ifName' => 'Fa0/1/1', 'ifAlias' => 'COMSTAR BGP link'),
                            array('port_label' => 'FastEthernet0/1/1', 'port_label_num' => '0/1/1', 'port_label_base' => 'FastEthernet', 'port_label_short' => 'Fa0/1/1')),
      array('ios',          array('ifDescr' => 'Null0', 'ifName' => 'Nu0', 'ifAlias' => ''),
                            array('port_label' => 'Null0', 'port_label_num' => '0', 'port_label_base' => 'Null', 'port_label_short' => 'Nu0')),
      array('ios',          array('ifDescr' => 'Vlan1', 'ifName' => 'Vl1', 'ifAlias' => ''),
                            array('port_label' => 'Vlan1', 'port_label_num' => '1', 'port_label_base' => 'Vlan', 'port_label_short' => 'Vlan1')),

      array('iosxe',        array('ifDescr' => 'TwentyFiveGigE1/0/14', 'ifName' => 'Twe1/0/14', 'ifAlias' => ''),
                            array('port_label' => 'TwentyFiveGigE1/0/14', 'port_label_num' => '1/0/14', 'port_label_base' => 'TwentyFiveGigE', 'port_label_short' => 'Twe1/0/14')),
      array('iosxe',        array('ifDescr' => 'HundredGigE1/0/52', 'ifName' => 'Hu1/0/52', 'ifAlias' => ''),
                            array('port_label' => 'HundredGigE1/0/52', 'port_label_num' => '1/0/52', 'port_label_base' => 'HundredGigE', 'port_label_short' => 'Hu1/0/52')),

      array('iosxr',        array('ifDescr' => 'Bundle-Ether670.1793', 'ifName' => 'Bundle-Ether670.1793', 'ifAlias' => ''),
                            array('port_label' => 'Bundle-Ether670.1793', 'port_label_num' => '670.1793', 'port_label_base' => 'Bundle-Ether', 'port_label_short' => 'BE670.1793')),
      array('iosxr',        array('ifDescr' => 'ControlEthernet0/RSP0/CPU0/S0/10', 'ifName' => 'ControlEthernet0/RSP0/CPU0/S0/10', 'ifAlias' => ''),
                            array('port_label' => 'ControlEthernet0/RSP0/CPU0/S0/10', 'port_label_num' => '0/RSP0/CPU0/S0/10', 'port_label_base' => 'ControlEthernet', 'port_label_short' => 'CE0/RSP0/CPU0/S0/10')),
      array('iosxr',        array('ifDescr' => 'MgmtEth0/RP0/CPU0/0', 'ifName' => 'MgmtEth0/RP0/CPU0/0', 'ifAlias' => ''),
                            array('port_label' => 'MgmtEth0/RP0/CPU0/0', 'port_label_num' => '0/RP0/CPU0/0', 'port_label_base' => 'MgmtEth', 'port_label_short' => 'Mgmt0/RP0/CPU0/0')),
      array('iosxr',        array('ifDescr' => 'Optics0/0/0/5', 'ifName' => 'Optics0/0/0/5', 'ifAlias' => ''),
                            array('port_label' => 'Optics0/0/0/5', 'port_label_num' => '0/0/0/5', 'port_label_base' => 'Optics', 'port_label_short' => 'Optics0/0/0/5')),
      array('iosxr',        array('ifDescr' => 'HundredGigE0/0/0/19', 'ifName' => 'HundredGigE0/0/0/19', 'ifAlias' => ''),
                            array('port_label' => 'HundredGigE0/0/0/19', 'port_label_num' => '0/0/0/19', 'port_label_base' => 'HundredGigE', 'port_label_short' => 'Hu0/0/0/19')),

      array('cisco-fxos',   array('ifDescr' => 'Adaptive Security Appliance \'Ethernet1/13', 'ifName' => 'Adaptive Security Appliance \'Ethernet1/13\' interface', 'ifAlias' => ''),
                            array('port_label' => 'Ethernet1/13', 'port_label_num' => '1/13', 'port_label_base' => 'Ethernet', 'port_label_short' => 'Et1/13')),
      array('cisco-fxos',   array('ifDescr' => 'Adaptive Security Appliance \'Port-Channel13\' interface', 'ifName' => 'Adaptive Security Appliance \'Port-Channel13\' interface', 'ifAlias' => ''),
                            array('port_label' => 'Port-Channel13', 'port_label_num' => '13', 'port_label_base' => 'Port-Channel', 'port_label_short' => 'Po13')),

      array('nxos',         array('ifDescr' => '', 'ifName' => 'Ethernet1/49.2', 'ifAlias' => ''),
                            array('port_label' => 'Ethernet1/49.2', 'port_label_num' => '1/49.2', 'port_label_base' => 'Ethernet', 'port_label_short' => 'Et1/49.2')),

      array('junos',        array('ifDescr' => 'ge-4/1/0.29', 'ifName' => 'ge-4/1/0.29', 'ifAlias' => 'Cust: Europool DIA'),
                            array('port_label' => 'ge-4/1/0.29', 'port_label_num' => '4/1/0.29', 'port_label_base' => 'ge-', 'port_label_short' => 'ge-4/1/0.29')),
      array('junos',        array('ifDescr' => 'reth3.0', 'ifName' => 'reth3.0', 'ifAlias' => ''),
                            array('port_label' => 'reth3.0', 'port_label_num' => '3.0', 'port_label_base' => 'reth', 'port_label_short' => 'reth3.0')),

      array('nos',          array('ifDescr' => 'TenGigabitEthernet 122/0/41', 'ifName' => 'TenGigabitEthernet 122/0/41', 'ifAlias' => ''),
                            array('port_label' => 'TenGigabitEthernet 122/0/41', 'port_label_num' => '122/0/41', 'port_label_base' => 'TenGigabitEthernet ', 'port_label_short' => 'Te 122/0/41')),
      array('nos',          array('ifDescr' => 'FortyGigabitEthernet 122/0/49', 'ifName' => 'FortyGigabitEthernet 122/0/49', 'ifAlias' => ''),
                            array('port_label' => 'FortyGigabitEthernet 122/0/49', 'port_label_num' => '122/0/49', 'port_label_base' => 'FortyGigabitEthernet ', 'port_label_short' => 'Fo 122/0/49')),

      array('dnos',         array('ifDescr' => 'fortyGigE 0/37', 'ifName' => 'fortyGigE 0/37', 'ifAlias' => ''),
                            array('port_label' => 'FortyGigE 0/37', 'port_label_num' => '0/37', 'port_label_base' => 'FortyGigE ', 'port_label_short' => 'Fo 0/37')),
      array('dnos',         array('ifDescr' => 'ManagementEthernet 0/0', 'ifName' => 'ManagementEthernet 0/0', 'ifAlias' => ''),
                            array('port_label' => 'ManagementEthernet 0/0', 'port_label_num' => '0/0', 'port_label_base' => 'ManagementEthernet ', 'port_label_short' => 'Mgmt 0/0')),

      array('fsos',         array('ifDescr' => 'eth-0-10', 'ifName' => 'eth-0-10', 'ifAlias' => ''),
                            array('port_label' => 'eth-0-10', 'port_label_num' => '0-10', 'port_label_base' => 'eth-', 'port_label_short' => 'eth-0-10')),

      array('vrp',          array('ifDescr' => '40GE4/0/6', 'ifName' => '40GE4/0/6', 'ifAlias' => ''),
                            array('port_label' => '40GE4/0/6', 'port_label_num' => '4/0/6', 'port_label_base' => '40GE', 'port_label_short' => '40GE4/0/6')),
      array('vrp',          array('ifDescr' => 'XGigabitEthernet5/0/14', 'ifName' => 'XGigabitEthernet5/0/14', 'ifAlias' => ''),
                            array('port_label' => 'XGigabitEthernet5/0/14', 'port_label_num' => '5/0/14', 'port_label_base' => 'XGigabitEthernet', 'port_label_short' => 'XGi5/0/14')),

      array('hh3c',         array('ifDescr' => 'Ten-GigabitEthernet2/0/25', 'ifName' => 'Ten-GigabitEthernet2/0/25', 'ifAlias' => 'Ten-GigabitEthernet2/0/25 Interface'),
                            array('port_label' => 'Ten-GigabitEthernet2/0/25', 'port_label_num' => '2/0/25', 'port_label_base' => 'Ten-GigabitEthernet', 'port_label_short' => 'Te2/0/25')),
      array('hh3c',         array('ifDescr' => 'Vlan-interface1504', 'ifName' => 'Vlan-interface1504', 'ifAlias' => ''),
                            array('port_label' => 'Vlan-interface1504', 'port_label_num' => '1504', 'port_label_base' => 'Vlan-interface', 'port_label_short' => 'Vlan1504')),

      array('routeros',     array('ifDescr' => 'Core: sfp-sfpplus1- Trunk to 6509', 'ifName' => 'Core: sfp-sfpplus1- Trunk to 6509', 'ifAlias' => ''),
                            array('port_label' => 'Core: sfp-sfpplus1- Trunk to 6509', 'port_label_num' => '', 'port_label_base' => 'Core: sfp-sfpplus1- Trunk to 6509', 'port_label_short' => 'Core: sfp-sfpplus1- Trunk to 6509')),

      array('linux',        array('ifDescr' => 'lo', 'ifName' => 'lo', 'ifAlias' => ''),
                            array('port_label' => 'lo', 'port_label_num' => '', 'port_label_base' => 'lo', 'port_label_short' => 'lo')),
      array('linux',        array('ifDescr' => 'Red Hat, Inc Device 0001', 'ifName' => 'eth0', 'ifAlias' => ''),
                            array('port_label' => 'eth0', 'port_label_num' => '0', 'port_label_base' => 'eth', 'port_label_short' => 'eth0')),
      array('linux',        array('ifDescr' => 'eth0.101', 'ifName' => 'eth0.101', 'ifAlias' => ''),
                            array('port_label' => 'eth0.101', 'port_label_num' => '0.101', 'port_label_base' => 'eth', 'port_label_short' => 'eth0.101')),

      [ 'pve',              [ 'ifDescr' => 'veth101i0', 'ifName' => 'veth101i0', 'ifAlias' => '' ],
                            [ 'port_label' => 'veth101i0', 'port_label_num' => '101i0', 'port_label_base' => 'veth', 'port_label_short' => 'veth101i0' ] ],
      [ 'openbsd',          [ 'ifDescr' => 'vether1', 'ifName' => 'vether1', 'ifAlias' => '' ],
                            [ 'port_label' => 'vether1', 'port_label_num' => '1', 'port_label_base' => 'vether', 'port_label_short' => 'vether1' ] ],
      [ 'generic',          [ 'ifDescr' => 'veth1bbfdc5', 'ifName' => 'veth1bbfdc5', 'ifAlias' => '' ],
                            [ 'port_label' => 'veth1bbfdc5', 'port_label_num' => '1bbfdc5', 'port_label_base' => 'veth', 'port_label_short' => 'veth1bbfdc5' ] ],
      [ 'nutanix',          [ 'ifDescr' => 'vethfd3fe3c0', 'ifName' => 'vethfd3fe3c0', 'ifAlias' => '' ],
                            [ 'port_label' => 'vethfd3fe3c0', 'port_label_num' => 'fd3fe3c0', 'port_label_base' => 'veth', 'port_label_short' => 'vethfd3fe3c0' ] ],

      // port_label os definitions
      array('vmware',       array('ifDescr' => 'Device vmnic7 at 08:00.1 bnx2', 'ifName' => '', 'ifAlias' => ''),
                            array('port_label' => 'vmnic7', 'port_label_num' => '7', 'port_label_base' => 'vmnic', 'port_label_short' => 'vmnic7')),
      array('vmware',       array('ifDescr' => 'Traditional Virtual VMware switch: vSwitch0', 'ifName' => 'vSwitch0', 'ifAlias' => ''),
                            array('port_label' => 'vSwitch0', 'port_label_num' => '0', 'port_label_base' => 'vSwitch', 'port_label_short' => 'vSwitch0')),
      array('vmware',       array('ifDescr' => 'Virtual interface: vmk2 on vswitch vSwitchISCSI portgroup: iSCSI0', 'ifName' => 'vmk2', 'ifAlias' => ''),
                            array('port_label' => 'vmk2', 'port_label_num' => '2', 'port_label_base' => 'vmk', 'port_label_short' => 'vmk2')),
      array('vmware',       array('ifDescr' => 'Link Aggregation VM_iSCSI on switch: vSwitchISCSI, load balancing algorithm: source port id hash', 'ifName' => '', 'ifAlias' => ''),
                            array('port_label' => 'Link Aggregation VM_iSCSI', 'port_label_num' => NULL, 'port_label_base' => 'Link Aggregation VM_iSCSI', 'port_label_short' => 'Lagg VM_iSCSI')),
      array('aix',          array('ifDescr' => 'en0; Product: 2-Port 10/100/1000 Base-TX PCI-X Adapter Manufacturer: not available! Part Number: not available! FRU Number: not available!', 'ifName' => '', 'ifAlias' => ''),
                            array('port_label' => 'en0', 'port_label_num' => '0', 'port_label_base' => 'en', 'port_label_short' => 'en0')),
      array('cisco-altiga', array('ifDescr' => 'DEC 21143A PCI Fast Ethernet', 'ifName' => '', 'ifAlias' => ''), // ++ ifIndex
                            array('port_label' => 'Fast Ethernet5', 'port_label_num' => '5', 'port_label_base' => 'Fast Ethernet', 'port_label_short' => 'Fa5')),
      array('deltaups',     array('ifDescr' => 'eth0............', 'ifName' => '', 'ifAlias' => ''),
                            array('port_label' => 'eth0', 'port_label_num' => '0', 'port_label_base' => 'eth', 'port_label_short' => 'eth0')),
      array('netapp',       array('ifDescr' => 'e0a'),
                            array('port_label' => 'e0a', 'port_label_num' => '0a', 'port_label_base' => 'e', 'port_label_short' => 'e0a')),
      array('netapp',       array('ifDescr' => 'vega-01:MGMT_PORT_ONLY e0M'),
                            array('port_label' => 'vega-01:MGMT_PORT_ONLY e0M', 'port_label_num' => '0M', 'port_label_base' => 'vega-01:MGMT_PORT_ONLY e', 'port_label_short' => 'vega-01:MGMT_PORT_ONLY e0M')),
      array('speedtouch',   array('ifDescr' => 'Some2thomson', 'ifName' => '', 'ifAlias' => ''),
                            array('port_label' => 'Some2', 'port_label_num' => '2', 'port_label_base' => 'Some', 'port_label_short' => 'Some2')),
      array('mrvos',        array('ifDescr' => 'Port 2 - ETH10/100/1000'),
                            array('port_label' => 'Port 2', 'port_label_num' => '2', 'port_label_base' => 'Port ', 'port_label_short' => 'Port 2')),
      array('zxr10',        array('ifDescr' => 'ZXR10 2928-SI 100BaseT port  23', 'ifName' => '', 'ifAlias' => ''),
                            array('port_label' => '100BaseT port 23', 'port_label_num' => '23', 'port_label_base' => '100BaseT port ', 'port_label_short' => 'port 23')),
      array('extreme-ers',  array('ifDescr' => 'Nortel Ethernet Routing Switch 5650TD Module - Port 36'),
                            array('port_label' => 'Port 36', 'port_label_num' => '36', 'port_label_base' => 'Port ', 'port_label_short' => 'Port 36')),
      array('uniping',      array('ifDescr' => 'UniPing Server Solution v3/SMS Enet Port'),
                            array('port_label' => 'Enet Port', 'port_label_num' => NULL, 'port_label_base' => 'Enet Port', 'port_label_short' => 'Enet Port')),

      array('timos',        array('ifDescr' => 'to-155KRD-1/1/23:1502, IP interface', 'ifName' => 'to-155KRD-1/1/23:1502', 'ifAlias' => ''),
                            array('port_label' => 'to-155KRD-1/1/23:1502', 'port_label_num' => '1/1/23:1502', 'port_label_base' => 'to-155KRD-', 'port_label_short' => 'to-155KRD-1/1/23:1502', 'ifAlias' => '')),
      array('timos',        array('ifDescr' => '1/1/26, 10-Gig Ethernet, Link to MDR-AGG-SW01 xe-0/0/4', 'ifName' => '1/1/26', 'ifAlias' => ''),
                            array('port_label' => '1/1/26', 'port_label_num' => '1/1/26', 'port_label_base' => '', 'port_label_short' => '1/1/26', 'ifAlias' => 'Link to MDR-AGG-SW01 xe-0/0/4')),
      array('timos',        array('ifDescr' => 'lag-13, LAG Group, LAG to 7750 port 1/2/3', 'ifName' => 'lag-13', 'ifAlias' => ''),
                            array('port_label' => 'lag-13', 'port_label_num' => '13', 'port_label_base' => 'lag-', 'port_label_short' => 'lag-13', 'ifAlias' => 'LAG to 7750 port 1/2/3')),
      array('timos',        array('ifDescr' => '5/1/lns-net, IP interface, "Broadband"', 'ifName' => '5/1/lns-net', 'ifAlias' => ''),
                            array('port_label' => '5/1/lns-net', 'port_label_num' => '5/1/lns-net', 'port_label_base' => '', 'port_label_short' => '5/1/lns-net', 'ifAlias' => 'Broadband')),
      array('timos',        array('ifDescr' => '_tmnx_nat-network_7/1, IP interface', 'ifName' => '_tmnx_nat-network_7/1', 'ifAlias' => ''),
                            array('port_label' => '_tmnx_nat-network_7/1', 'port_label_num' => '7/1', 'port_label_base' => '_tmnx_nat-network_', 'port_label_short' => '_tmnx_nat-network_7/1', 'ifAlias' => '')),
      array('timos',        array('ifDescr' => 'pw-24100, PW Port, "STRELA_TAISHET_EQM_MON_SVID240"', 'ifName' => 'pw-24100', 'ifAlias' => ''),
                            array('port_label' => 'pw-24100', 'port_label_num' => '24100', 'port_label_base' => 'pw-', 'port_label_short' => 'pw-24100', 'ifAlias' => 'STRELA_TAISHET_EQM_MON_SVID240')),

      array('ekinops-360',  array('ifDescr' => 'EKINOPS/C200/6/PM10010MP/Line(ROV-VOR-194,2)', 'ifName' => 'EKINOPS/C200/6/PM10010MP/Line(ROV-VOR-194,2)', 'ifAlias' => ''),
                            array('port_label' => '6/PM10010MP/Line', 'port_label_num' => '', 'port_label_base' => '6/PM10010MP/Line', 'port_label_short' => '6/PM10010MP/Line', 'ifAlias' => 'ROV-VOR-194,2')),
      array('ekinops-360',  array('ifDescr' => 'EKINOPS/C200HC/1/PM_10010MP/S1-Client1(PORT_Number 1   )', 'ifName' => 'EKINOPS/C200HC/1/PM_10010MP/S1-Client1(PORT_Number 1   )', 'ifAlias' => ''),
                            array('port_label' => '1/PM_10010MP/S1-Client1', 'port_label_num' => '1-Client1', 'port_label_base' => '1/PM_10010MP/S', 'port_label_short' => '1/PM_10010MP/S1-Client1', 'ifAlias' => 'PORT_Number 1')),
      array('ekinops-360',  array('ifDescr' => 'EKINOPS/C200/6/PM10010MP/S4-Client4()', 'ifName' => 'EKINOPS/C200/6/PM10010MP/S4-Client4()', 'ifAlias' => ''),
                            array('port_label' => '6/PM10010MP/S4-Client4', 'port_label_num' => '4-Client4', 'port_label_base' => '6/PM10010MP/S', 'port_label_short' => '6/PM10010MP/S4-Client4', 'ifAlias' => '')),
      array('ekinops-360',  array('ifDescr' => 'EKINOPS/C200/1/MGNT/FE_3', 'ifName' => 'EKINOPS/C200/1/MGNT/FE_3', 'ifAlias' => ''),
                            array('port_label' => '1/MGNT/FE_3', 'port_label_num' => '3', 'port_label_base' => '1/MGNT/FE_', 'port_label_short' => '1/MGNT/FE_3', 'ifAlias' => '')),
      array('ekinops-360',  array('ifDescr' => 'EKINOPS/C200/1/MGNT/GbE_RJ45#1', 'ifName' => 'EKINOPS/C200/1/MGNT/GbE_RJ45#1', 'ifAlias' => ''),
                            array('port_label' => '1/MGNT/GbE_RJ45#1', 'port_label_num' => '1', 'port_label_base' => '1/MGNT/GbE_RJ45#', 'port_label_short' => '1/MGNT/GbE_RJ45#1', 'ifAlias' => '')),

      // ifType_ifDescr
      array('liebert',      array('ifDescr' => '', 'ifType' => 'softwareLoopback'), // ++ ifType, ifIndex
                            array('port_label' => 'Loopback 5', 'port_label_num' => '5', 'port_label_base' => 'Loopback ', 'port_label_short' => 'Lo 5')),
      array('liebert',      array('ifDescr' => '', 'ifType' => 'ethernetCsmacd'),   // ++ ifType, ifIndex
                            array('port_label' => 'Ethernet 5', 'port_label_num' => '5', 'port_label_base' => 'Ethernet ', 'port_label_short' => 'Et 5')),

      array('aos',          array('ifDescr' => 'Alcatel-Lucent 1/10', 'ifName' => '', 'ifAlias' => ''), // -- ifName
                            array('port_label' => '1/10', 'port_label_num' => '1/10', 'port_label_base' => '', 'port_label_short' => '1/10')),

      // ifname
      array('aos',          array('ifDescr' => 'Alcatel-Lucent 1/10', 'ifName' => '1/10', 'ifAlias' => ''),
                            array('port_label' => '1/10', 'port_label_num' => '1/10', 'port_label_base' => '', 'port_label_short' => '1/10')),
      array('aos',          array('ifDescr' => 'Alcatel-Lucent Stacking Port 1/StackA', 'ifName' => 'Stacking Port 1/StackA', 'ifAlias' => ''),
                            array('port_label' => 'Stacking Port 1/StackA', 'port_label_num' => '1/StackA', 'port_label_base' => 'Stacking Port ', 'port_label_short' => 'Stacking 1/StackA')),
      array('aosw',         array('ifDescr' => '802.1Q VLAN', 'ifName' => 'vlan 4095', 'ifAlias' => ''),
                            array('port_label' => 'Vlan 4095', 'port_label_num' => '4095', 'port_label_base' => 'Vlan ', 'port_label_short' => 'Vlan 4095')),
      array('aosw',         array('ifDescr' => 'SWITCH IP INTERFACE', 'ifName' => 'loop', 'ifAlias' => ''),
                            array('port_label' => 'loop', 'port_label_num' => NULL, 'port_label_base' => 'loop', 'port_label_short' => 'loop')),

      array('xos',          array('ifDescr' => 'X690-48x-2q-4c Port 10', 'ifName' => '1:10', 'ifAlias' => ''),
                            array('port_label' => '1:10', 'port_label_num' => '1:10', 'port_label_base' => '', 'port_label_short' => '1:10')),

      array('hirschmann-switch', array('ifDescr' => 'Module: 1 Port: 3 - 10/100 Mbit TX', 'ifName' => '1/1/3', 'ifAlias' => ''),
                            array('port_label' => '1/1/3', 'port_label_num' => '1/1/3', 'port_label_base' => '', 'port_label_short' => '1/1/3')),
      array('hirschmann-switch', array('ifDescr' => 'Module: 1 Port: 3 - 10/100 Mbit TX', 'ifName' => '', 'ifAlias' => ''),
                            array('port_label' => 'Module: 1 Port: 3', 'port_label_num' => '1 Port: 3', 'port_label_base' => 'Module: ', 'port_label_short' => 'Module: 1 Port: 3')),
      array('hirschmann-switch', array('ifDescr' => 'CPU Interface for Module: 4 Port: 1', 'ifName' => 'CPU Interface:  1/4/1', 'ifAlias' => ''),
                            array('port_label' => 'CPU Interface: 1/4/1', 'port_label_num' => '1/4/1', 'port_label_base' => 'CPU Interface: ', 'port_label_short' => 'CPU Interface: 1/4/1')),

      // hard coded rewrites
      array('ciscosb',      array('ifDescr' => '1', 'ifName' => '1', 'ifType' => 'propVirtual'), // ++ ifType
                            array('port_label' => 'Vlan1', 'port_label_num' => '1', 'port_label_base' => 'Vlan', 'port_label_short' => 'Vlan1')),

      array('alliedwareplus', array('ifDescr' => 'apc-ups', 'ifName' => 'port1.0.21', 'ifType' => 'ethernetCsmacd'), // ifAlias in ifDescr
                            array('port_label' => 'port1.0.21', 'port_label_num' => '1.0.21', 'port_label_base' => 'port', 'port_label_short' => 'port1.0.21', 'ifAlias' => 'apc-ups')),
      array('alliedwareplus', array('ifDescr' => '-', 'ifName' => 'port1.0.21', 'ifType' => 'ethernetCsmacd'), // ifAlias in ifDescr
                            array('port_label' => 'port1.0.21', 'port_label_num' => '1.0.21', 'port_label_base' => 'port', 'port_label_short' => 'port1.0.21', 'ifAlias' => '')),

      // base/num port
      array('generic',      array('ifDescr' => 'GigaVUE-212 Port  8/48 (Network Port)'),
                            array('port_label' => 'GigaVUE-212 Port 8/48', 'port_label_num' => '8/48', 'port_label_base' => 'GigaVUE-212 Port ', 'port_label_short' => 'Port 8/48')),
      array('generic',      array('ifDescr' => 'rtif(172.20.30.46/28)'),
                            array('port_label' => 'rtif(172.20.30.46/28)', 'port_label_num' => '', 'port_label_base' => 'rtif(172.20.30.46/28)', 'port_label_short' => 'rtif(172.20.30.46/28)')),
      array('generic',      array('ifDescr' => '10/100 MBit Ethernet Switch Interface 6'),
                            array('port_label' => 'Ethernet 6', 'port_label_num' => '6', 'port_label_base' => 'Ethernet ', 'port_label_short' => 'Et 6')),
      array('generic',      array('ifDescr' => '1/1/1'),
                            array('port_label' => '1/1/1', 'port_label_num' => '1/1/1', 'port_label_base' => '', 'port_label_short' => '1/1/1')),
      array('generic',      array('ifDescr' => 'e1-0/2/0:13.0'),
                            array('port_label' => 'e1-0/2/0:13.0', 'port_label_num' => '0/2/0:13.0', 'port_label_base' => 'e1-', 'port_label_short' => 'e1-0/2/0:13.0')),
      array('generic',      array('ifDescr' => 'dwdm0/1/0/6'),
                            array('port_label' => 'dwdm0/1/0/6', 'port_label_num' => '0/1/0/6', 'port_label_base' => 'dwdm', 'port_label_short' => 'DWDM0/1/0/6')),
      array('generic',      array('ifDescr' => 'Cable8/1/4-upstream2'),
                            array('port_label' => 'Cable8/1/4-upstream2', 'port_label_num' => '8/1/4-upstream2', 'port_label_base' => 'Cable', 'port_label_short' => 'Ca8/1/4-upstream2')),
      array('generic',      array('ifDescr' => '16GigabitEthernet1/2/1'),
                            array('port_label' => '16GigabitEthernet1/2/1', 'port_label_num' => '1/2/1', 'port_label_base' => '16GigabitEthernet', 'port_label_short' => '16Gi1/2/1')),
      array('generic',      array('ifDescr' => 'cau4-0/2/0'),
                            array('port_label' => 'cau4-0/2/0', 'port_label_num' => '0/2/0', 'port_label_base' => 'cau4-', 'port_label_short' => 'cau4-0/2/0')),
      array('generic',      array('ifDescr' => 'dot11radio0/0'),
                            array('port_label' => 'dot11radio0/0', 'port_label_num' => '0/0', 'port_label_base' => 'dot11radio', 'port_label_short' => 'dot11radio0/0')),
      array('generic',      array('ifDescr' => '1000BaseTX Port 8/48 Name'),
                            array('port_label' => '1000BaseTX Port 8/48', 'port_label_num' => '8/48', 'port_label_base' => '1000BaseTX Port ', 'port_label_short' => 'Port 8/48')),
      array('generic',      array('ifDescr' => 'Backplane-GigabitEthernet0/3'),
                            array('port_label' => 'Backplane-GigabitEthernet0/3', 'port_label_num' => '0/3', 'port_label_base' => 'Backplane-GigabitEthernet', 'port_label_short' => 'Bpl-Gi0/3')),
      array('generic',      array('ifDescr' => 'FC port 0/19'),
                            array('port_label' => 'FC port 0/19', 'port_label_num' => '0/19', 'port_label_base' => 'FC port ', 'port_label_short' => 'FC port 0/19')),
      array('generic',      array('ifDescr' => 'GigabitEthernet0/1.ServiceInstance.206'),
                            array('port_label' => 'GigabitEthernet0/1.ServiceInstance.206', 'port_label_num' => '0/1.ServiceInstance.206', 'port_label_base' => 'GigabitEthernet', 'port_label_short' => 'Gi0/1.SI.206')),
      array('generic',      array('ifDescr' => 'Integrated-Cable7/0/0:0'),
                            array('port_label' => 'Integrated-Cable7/0/0:0', 'port_label_num' => '7/0/0:0', 'port_label_base' => 'Integrated-Cable', 'port_label_short' => 'In7/0/0:0')),
      array('generic',      array('ifDescr' => 'Logical Upstream Channel 1/0.0/0'),
                            array('port_label' => 'Logical Upstream Channel 1/0.0/0', 'port_label_num' => '1/0.0/0', 'port_label_base' => 'Logical Upstream Channel ', 'port_label_short' => 'Upstream 1/0.0/0')),
      array('generic',      array('ifDescr' => 'Video Downstream 0/0/38'),
                            array('port_label' => 'Video Downstream 0/0/38', 'port_label_num' => '0/0/38', 'port_label_base' => 'Video Downstream ', 'port_label_short' => 'Downstream 0/0/38')),
      array('generic',      array('ifDescr' => 'Downstream RF Port 4/7'),
                            array('port_label' => 'Downstream RF Port 4/7', 'port_label_num' => '4/7', 'port_label_base' => 'Downstream RF Port ', 'port_label_short' => 'Downstream 4/7')),
      array('generic',      array('ifDescr' => 'Slot0/1'),
                            array('port_label' => 'Slot0/1', 'port_label_num' => '0/1', 'port_label_base' => 'Slot', 'port_label_short' => 'Slot0/1')),
      array('generic',      array('ifDescr' => 'sonet_12/1'),
                            array('port_label' => 'sonet_12/1', 'port_label_num' => '12/1', 'port_label_base' => 'sonet_', 'port_label_short' => 'sonet_12/1')),
      array('generic',      array('ifDescr' => 'gigabitEthernet 1/0/24 : copper'),
                            array('port_label' => 'GigabitEthernet 1/0/24', 'port_label_num' => '1/0/24', 'port_label_base' => 'GigabitEthernet ', 'port_label_short' => 'Gi 1/0/24')),
      array('generic',      array('ifDescr' => '1:38'),
                            array('port_label' => '1:38', 'port_label_num' => '1:38', 'port_label_base' => '', 'port_label_short' => '1:38')),
      array('generic',      array('ifDescr' => '1/4/x24, mx480-xe-0-0-0'),
                            array('port_label' => '1/4/x24', 'port_label_num' => '1/4/x24', 'port_label_base' => '', 'port_label_short' => '1/4/x24')),
      array('generic',      array('ifDescr' => '1/4/x24'),
                            array('port_label' => '1/4/x24', 'port_label_num' => '1/4/x24', 'port_label_base' => '', 'port_label_short' => '1/4/x24')),
      array('generic',      array('ifDescr' => 'MMC-A s3 SW Port'),
                            array('port_label' => 'MMC-A s3', 'port_label_num' => '3', 'port_label_base' => 'MMC-A s', 'port_label_short' => 'MMC-A s3')),
      array('generic',      array('ifDescr' => 'Atm0_Physical_Interface'),
                            array('port_label' => 'Atm0', 'port_label_num' => '0', 'port_label_base' => 'Atm', 'port_label_short' => 'Atm0')),
      array('generic',      array('ifDescr' => 'wan1_phys'),
                            array('port_label' => 'wan1_phys', 'port_label_num' => '1_phys', 'port_label_base' => 'wan', 'port_label_short' => 'wan1_phys')),
      array('generic',      array('ifDescr' => 'fwbr101i0'),
                            array('port_label' => 'fwbr101i0', 'port_label_num' => '101i0', 'port_label_base' => 'fwbr', 'port_label_short' => 'fwbr101i0')),
      array('generic',      array('ifDescr' => 'lo0.32768'),
                            array('port_label' => 'lo0.32768', 'port_label_num' => '0.32768', 'port_label_base' => 'lo', 'port_label_short' => 'lo0.32768')),
      array('generic',      array('ifDescr' => 'Vlan.818'),
                            array('port_label' => 'Vlan.818', 'port_label_num' => '818', 'port_label_base' => 'Vlan.', 'port_label_short' => 'Vlan.818')),
      array('generic',      array('ifDescr' => 'Bundle-Ether1.1701'),
                            array('port_label' => 'Bundle-Ether1.1701', 'port_label_num' => '1.1701', 'port_label_base' => 'Bundle-Ether', 'port_label_short' => 'BE1.1701')),
      array('generic',      array('ifDescr' => 'BVI900'),
                            array('port_label' => 'BVI900', 'port_label_num' => '900', 'port_label_base' => 'BVI', 'port_label_short' => 'BVI900')),
      array('generic',      array('ifDescr' => 'A/1'),
                            array('port_label' => 'A/1', 'port_label_num' => '1', 'port_label_base' => 'A/', 'port_label_short' => 'A/1')),
      array('generic',      array('ifDescr' => 'e1'),
                            array('port_label' => 'e1', 'port_label_num' => '1', 'port_label_base' => 'e', 'port_label_short' => 'e1')),
      array('generic',      array('ifDescr' => 'CATV-MAC 1'),
                            array('port_label' => 'CATV-MAC 1', 'port_label_num' => '1', 'port_label_base' => 'CATV-MAC ', 'port_label_short' => 'CATV-MAC 1')),
      array('generic',      array('ifDescr' => 'Control Plane'),
                            array('port_label' => 'Control Plane', 'port_label_num' => NULL, 'port_label_base' => 'Control Plane', 'port_label_short' => 'Control Plane')),
      array('generic',      array('ifDescr' => 'port 3: Gigabit Fiber'), // TP-LINK
                            array('port_label' => 'port 3', 'port_label_num' => '3', 'port_label_base' => 'port ', 'port_label_short' => 'port 3')),
      //array('generic',      array('ifDescr' => 'microsens'),
      //                      array('port_label' => 'microsens', 'port_label_num' => 'microsens', 'port_label_base' => '', 'port_label_short' => 'microsens')),
      //array('generic',      array('ifDescr' => 'microsens'),
      //                      array('port_label' => 'microsens', 'port_label_num' => 'microsens', 'port_label_base' => '', 'port_label_short' => 'microsens')),

    );
  }
}

// EOF
