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
   * @group string
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
     * @group icon
     */
    public function testGetDeviceIcon($device, $base_icon, $result) {
        $GLOBALS['config']['base_url'] = 'http://localhost';
        // for device_permitted
        $device['device_id'] = 98217;
        $_SESSION['userlevel'] = 7;
        $this->assertSame($result, get_device_icon($device, $base_icon));
    }

    public function providerGetDeviceIcon() {
        return [
            // by $device['os']
            [ [ 'os' => 'screenos', 'icon' => '', 'sysObjectID' => '' ], TRUE, 'juniper-old' ],
            // by $device['os'] and icon definition
            [ [ 'os' => 'ios', 'icon' => '', 'sysObjectID' => '' ], TRUE, 'cisco' ],
            // by $device['os'] and vendor definition
            [ [ 'os' => 'liebert', 'icon' => '', 'sysObjectID' => '' ], TRUE, 'emerson' ],
            // by $device['os'] and vendor defined icon
            [ [ 'os' => 'summitd-wl', 'icon' => '', 'sysObjectID' => '' ], TRUE, 'summitd' ],
            // by $device['os'] and vendor defined icon
            [ [ 'os' => 'summitd-wl', 'icon' => '', 'sysObjectID' => '', 'vendor' => 'Summit Development' ], TRUE, 'summitd' ],
            // by $device['os'] and vendor definition (with non alpha chars)
            [ [ 'os' => 'wut-com' ], TRUE, 'wut' ], // W&T
            // by $device['os'] and distro name in array
            [ [ 'os' => 'linux', 'icon' => '', 'sysObjectID' => '', 'distro' => 'RedHat' ], TRUE, 'redhat' ],
            // by $device['os'] and icon in device array
            [ [ 'os' => 'ios', 'icon' => 'cisco-old', 'sysObjectID' => '' ], TRUE, 'cisco-old' ],
            // by all, who win?
            [ [ 'os' => 'liebert', 'distro' => 'RedHat', 'icon' => 'cisco-old', 'sysObjectID' => '' ], TRUE, 'cisco-old' ],
            // unknown
            [ [ 'os' => 'yohoho', 'icon' => '', 'sysObjectID' => '' ], TRUE, 'generic' ],
            // empty
            [ [], TRUE, 'generic' ],

            // Prevent use vendor icon for unix/window oses and visa-versa for others
            [ [ 'os' => 'pve',            'type' => 'hypervisor', 'sysObjectID' => '', 'vendor' => 'Supermicro' ], TRUE, 'proxmox' ],
            [ [ 'os' => 'proxmox-server', 'type' => 'server',     'sysObjectID' => '', 'vendor' => 'Supermicro' ], TRUE, 'proxmox' ],
            [ [ 'os' => 'truenas-core',   'type' => 'storage',    'sysObjectID' => '', 'vendor' => 'Supermicro' ], TRUE, 'truenas' ],
            [ [ 'os' => 'generic-ups',    'type' => 'power',      'sysObjectID' => '', 'vendor' => '' ],           TRUE, 'ups' ],
            [ [ 'os' => 'generic-ups',    'type' => 'power',      'sysObjectID' => '', 'vendor' => 'Supermicro' ], TRUE, 'supermicro' ],

            // Last, check with img tag
            [ [ 'os' => 'ios' ],   FALSE, '<img src="http://localhost/images/os/cisco.svg" style="max-height: 32px; max-width: 48px;" alt="" />' ],
            [ [ 'os' => 'screenos' ], FALSE, '<img src="http://localhost/images/os/juniper-old.png" srcset="http://localhost/images/os/juniper-old_2x.png 2x" alt="" />' ],
        ];
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

    /**
     * @group session
     */
    public function test_single_key()
    {
        session_set_var('key', 'value');
        $this->assertEquals('value', $_SESSION['key']);
    }

    /**
     * @group session
     */
    public function test_nested_keys()
    {
        session_set_var('key1->key2->key3', 'value');
        $this->assertEquals('value', $_SESSION['key1']['key2']['key3']);
    }

    /**
     * @group session
     */
    public function test_unset_single_key()
    {
        $_SESSION['key'] = 'value';
        session_set_var('key', null);
        $this->assertArrayNotHasKey('key', $_SESSION);
    }

    /**
     * @group session
     */
    public function test_unset_nested_keys()
    {
        $_SESSION['key1']['key2']['key3'] = 'value';
        session_set_var('key1->key2->key3', null);
        $this->assertArrayNotHasKey('key3', $_SESSION['key1']['key2']);
    }

    /**
     * @group session
     */
    public function test_no_change_single_key()
    {
        $_SESSION['key'] = 'value';
        session_set_var('key', 'value');
        $this->assertEquals('value', $_SESSION['key']);
    }

    /**
     * @group session
     */
    public function test_no_change_nested_keys()
    {
        $_SESSION['key1']['key2']['key3'] = 'value';
        session_set_var('key1->key2->key3', 'value');
        $this->assertEquals('value', $_SESSION['key1']['key2']['key3']);
    }

    /**
     * @group session
     */
    public function test_single_key_array()
    {
        session_set_var(['key'], 'value');
        $this->assertEquals('value', $_SESSION['key']);
    }

    /**
     * @group session
     */
    public function test_nested_keys_array()
    {
        session_set_var(['key1', 'key2', 'key3'], 'value');
        $this->assertEquals('value', $_SESSION['key1']['key2']['key3']);
    }

    /**
     * @group session
     */
    public function test_unset_single_key_array()
    {
        $_SESSION['key'] = 'value';
        session_set_var(['key'], null);
        $this->assertArrayNotHasKey('key', $_SESSION);
    }

    /**
     * @group session
     */
    public function test_unset_nested_keys_array()
    {
        $_SESSION['key1']['key2']['key3'] = 'value';
        session_set_var(['key1', 'key2', 'key3'], null);
        $this->assertArrayNotHasKey('key3', $_SESSION['key1']['key2']);
    }

}

// EOF
