<?php

include(__DIR__ . '/../includes/observium.inc.php'); // Here required DB connect
//include(dirname(__FILE__) . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php');
//include(dirname(__FILE__) . '/../includes/definitions.inc.php');
//include(dirname(__FILE__) . '/../includes/functions.inc.php');
include(__DIR__ . '/../html/includes/functions.inc.php');

class HtmlIncludesFunctionsTest extends \PHPUnit\Framework\TestCase
{
  /**
  * @dataProvider providerNiceCase
  */
  public function testNiceCase($string, $result)
  {
    $this->assertSame($result, nicecase($string));
  }

  public function providerNiceCase()
  {
    return array(
      array('bgp_peer', 'BGP Peer'),
      array('bgp_peer_af', 'BGP Peer (AFI/SAFI)'),
      array('netscaler_vsvr', 'Netscaler vServer'),
      array('netscaler_svc', 'Netscaler Service'),
      array('mempool', 'Memory'),
      array('ipsec_tunnels', 'IPSec Tunnels'),
      array('vrf', 'VRF'),
      array('isis', 'IS-IS'),
      array('cef', 'CEF'),
      array('eigrp', 'EIGRP'),
      array('ospf', 'OSPF'),
      array('bgp', 'BGP'),
      array('ases', 'ASes'),
      array('vpns', 'VPNs'),
      array('dbm', 'dBm'),
      array('mysql', 'MySQL'),
      array('powerdns', 'PowerDNS'),
      array('bind', 'BIND'),
      array('ntpd', 'NTPd'),
      array('powerdns-recursor', 'PowerDNS Recursor'),
      array('freeradius', 'FreeRADIUS'),
      array('postfix_mailgraph', 'Postfix Mailgraph'),
      array('ge', 'Greater or equal'),
      array('le', 'Less or equal'),
      array('notequals', 'Doesn\'t equal'),
      array('notmatch', 'Doesn\'t match'),
      array('diskio', 'Disk I/O'),
      array('ipmi', 'IPMI'),
      array('snmp', 'SNMP'),
      array('mssql', 'SQL Server'),
      array('apower', 'Apparent power'),
      array('proxysg', 'Proxy SG'),
      array('', ''),

      array(' some text here ', ' some text here '),
      array('some text here ', 'Some text here '),
      array(NULL, ''),
      array(FALSE, ''),
      array(array('test'), '')
    );
  }

  /**
  * @dataProvider providerGetDeviceIcon
  * @group device
  */
  public function testGetDeviceIcon($device, $base_icon, $result)
  {
    $GLOBALS['config']['base_url'] = 'http://localhost';
    // for device_permitted
    $device['device_id'] = 98217;
    $_SESSION['userlevel'] = 7;
    $this->assertSame($result, get_device_icon($device, $base_icon));
  }

  public function providerGetDeviceIcon()
  {
    return array(
      // by $device['os']
      array(array('os' => 'screenos', 'icon' => '', 'sysObjectID' => ''), TRUE, 'juniper-old'),
      // by $device['os'] and icon definition
      array(array('os' => 'ios', 'icon' => '', 'sysObjectID' => ''), TRUE, 'cisco'),
      // by $device['os'] and vendor definition
      array(array('os' => 'liebert', 'icon' => '', 'sysObjectID' => ''), TRUE, 'emerson'),
      // by $device['os'] and vendor defined icon
      array(array('os' => 'summitd-wl', 'icon' => '', 'sysObjectID' => ''), TRUE, 'summitd'),
      // by $device['os'] and vendor defined icon
      array(array('os' => 'summitd-wl', 'icon' => '', 'sysObjectID' => '', 'vendor' => 'Summit Development'), TRUE, 'summitd'),
      // by $device['os'] and vendor definition (with non alpha chars)
      //array(array('os' => 'ccplus'), TRUE, 'c_c_power'),
      // by $device['os'] and distro name in array
      array(array('os' => 'linux', 'icon' => '', 'sysObjectID' => '', 'distro' => 'RedHat'), TRUE, 'redhat'),
      // by $device['os'] and icon in array
      array(array('os' => 'ios', 'icon' => 'cisco-old', 'sysObjectID' => ''), TRUE, 'cisco-old'),
      // by all, who win?
      array(array('os' => 'liebert', 'distro' => 'RedHat', 'icon' => 'cisco-old', 'sysObjectID' => ''), TRUE, 'cisco-old'),
      // unknown
      array(array('os' => 'yohoho', 'icon' => '', 'sysObjectID' => ''), TRUE, 'generic'),
      // empty
      array(array(), TRUE, 'generic'),
      
      // Last, check with img tag
      array(array('os' => 'ios'),   FALSE, '<img src="http://localhost/images/os/cisco.svg" style="max-height: 32px;" alt="" />'),
      array(array('os' => 'screenos'), FALSE, '<img src="http://localhost/images/os/juniper-old.png" srcset="http://localhost/images/os/juniper-old_2x.png 2x" alt="" />'),
    );
  }

    protected function setUp(): void
    {
        // Start the session before each test
        @session_start();
    }

    protected function tearDown(): void
    {
        // Clean up the session after each test
        session_unset();
        session_destroy();
    }

    public function test_single_key()
    {
        session_set_var('key', 'value');
        $this->assertEquals('value', $_SESSION['key']);
    }

    public function test_nested_keys()
    {
        session_set_var('key1->key2->key3', 'value');
        $this->assertEquals('value', $_SESSION['key1']['key2']['key3']);
    }

    public function test_unset_single_key()
    {
        $_SESSION['key'] = 'value';
        session_set_var('key', null);
        $this->assertArrayNotHasKey('key', $_SESSION);
    }

    public function test_unset_nested_keys()
    {
        $_SESSION['key1']['key2']['key3'] = 'value';
        session_set_var('key1->key2->key3', null);
        $this->assertArrayNotHasKey('key3', $_SESSION['key1']['key2']);
    }

    public function test_no_change_single_key()
    {
        $_SESSION['key'] = 'value';
        session_set_var('key', 'value');
        $this->assertEquals('value', $_SESSION['key']);
    }

    public function test_no_change_nested_keys()
    {
        $_SESSION['key1']['key2']['key3'] = 'value';
        session_set_var('key1->key2->key3', 'value');
        $this->assertEquals('value', $_SESSION['key1']['key2']['key3']);
    }

    public function test_single_key_array()
    {
        session_set_var(['key'], 'value');
        $this->assertEquals('value', $_SESSION['key']);
    }

    public function test_nested_keys_array()
    {
        session_set_var(['key1', 'key2', 'key3'], 'value');
        $this->assertEquals('value', $_SESSION['key1']['key2']['key3']);
    }

    public function test_unset_single_key_array()
    {
        $_SESSION['key'] = 'value';
        session_set_var(['key'], null);
        $this->assertArrayNotHasKey('key', $_SESSION);
    }

    public function test_unset_nested_keys_array()
    {
        $_SESSION['key1']['key2']['key3'] = 'value';
        session_set_var(['key1', 'key2', 'key3'], null);
        $this->assertArrayNotHasKey('key3', $_SESSION['key1']['key2']);
    }

}

// EOF
