<?php

include(dirname(__FILE__) . '/../includes/sql-config.inc.php'); // Here required DB connect
//include(dirname(__FILE__) . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php');
//include(dirname(__FILE__) . '/../includes/definitions.inc.php');
//include(dirname(__FILE__) . '/../includes/functions.inc.php');
include(dirname(__FILE__) . '/../html/includes/functions.inc.php');

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
      array(array('test'), NULL)
    );
  }

  /**
  * @dataProvider providerGetDeviceIcon
  * @group device
  */
  public function testGetDeviceIcon($device, $base_icon, $result)
  {
    $GLOBALS['config']['base_url'] = 'http://localhost';
    $this->assertSame($result, get_device_icon($device, $base_icon));
  }

  public function providerGetDeviceIcon()
  {
    return array(
      // by $device['os']
      array(array('os' => 'screenos'), TRUE, 'juniper-old'),
      // by $device['os'] and icon definition
      array(array('os' => 'ios'), TRUE, 'cisco'),
      // by $device['os'] and vendor definition
      array(array('os' => 'cyclades'), TRUE, 'emerson'),
      // by $device['os'] and vendor defined icon
      array(array('os' => 'summitd-wl'), TRUE, 'summitd'),
      // by $device['os'] and vendor defined icon
      array(array('os' => 'summitd-wl', 'vendor' => 'Summit Development'), TRUE, 'summitd'),
      // by $device['os'] and vendor definition (with non alpha chars)
      //array(array('os' => 'ccplus'), TRUE, 'c_c_power'),
      // by $device['os'] and distro name in array
      array(array('os' => 'linux', 'distro' => 'RedHat'), TRUE, 'redhat'),
      // by $device['os'] and icon in array
      array(array('os' => 'ios', 'icon' => 'cisco-old'), TRUE, 'cisco-old'),
      // by all, who win?
      array(array('os' => 'cyclades', 'distro' => 'RedHat', 'icon' => 'cisco-old'), TRUE, 'cisco-old'),
      // unknown
      array(array('os' => 'yohoho'), TRUE, 'generic'),
      // empty
      array(array(), TRUE, 'generic'),
      
      // Last, check with img tag
      array(array('os' => 'ios'), FALSE, '<img src="http://localhost/images/os/cisco.png" srcset="http://localhost/images/os/cisco_2x.png 2x" alt="" />'),
    );
  }
}

// EOF
