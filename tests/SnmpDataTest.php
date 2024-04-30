<?php

//define('OBS_DEBUG', 2);
define('OBS_QUIET', TRUE); // Disable any additional output from tests
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
 *   snmprec.py --agent-udpv4-endpoint=x.x.x.x --community=<community> --start-oid=1.3.6.1.2.1 --stop-oid=1.3.6.1.2.1.1 --output-file=osname-y.snmprec
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
$snmpsimd_data = $config['tests']['snmpsim_dir'] . '/os';
if (is_dir($snmpsimd_data)) {
  snmpsimd_init($snmpsimd_data);
  sleep(2); // Sleep before tests, because snmpsimd can get stuck
}

/**
 * do not use this, if you not know what is it :P
 * but if use, uncomment config.php include!
 */
/*
foreach (dbFetchRows("SELECT * FROM `devices`") as $entry)
{
  if (!$entry['status'] || $entry['disabled']) { continue; }
  //if ($entry['status'] || $entry['disabled']) { continue; }
  //if (!$entry['disabled']) { continue; }

  if (strstr($entry['sysDescr'], "\n"))
  {
    // Multiline strings need convert to HEX
    $data  = '1.3.6.1.2.1.1.1.0|4x|' . str2hex($entry['sysDescr']) . PHP_EOL;
  } else {
    $data  = '1.3.6.1.2.1.1.1.0|4|' . $entry['sysDescr'] . PHP_EOL;
  }
  $data .= '1.3.6.1.2.1.1.2.0|6|' . ltrim($entry['sysObjectID'], '.'); // . PHP_EOL;
  //$data .= '1.3.6.1.6.3.10.2.1.1|4x|' . $entry['snmpEngineID'];
  $i = 1;
  while (is_file($snmpsimd_data . '/new/' . $entry['os'] . '-' . $i . '.snmprec'))
  {
    $i++;
  }
  file_put_contents($snmpsimd_data . '/new/' . $entry['os'] . '-' . $i . '.snmprec', $data);
}
exit;
*/

class SnmpDataTest extends \PHPUnit\Framework\TestCase
{
  protected function setUp(): void
  {
    global $snmpsimd_ip, $snmpsimd_port, $snmpsimd_data;

    if (!OBS_SNMPSIMD)
    {
      $this->markTestSkipped('SNMPsimd unavailable or daemon not started, test skipped.');
    }

    if (!defined('OBS_TEST_SNMP'))
    {
      // Just get first snmp request, for alive snmpsimd
      $device = build_initial_device_array($snmpsimd_ip, 'ios-1', 'v2c', $snmpsimd_port, 'udp');
      snmp_get_oid($device, '.1.3.6.1.2.1.1.2.0');
      define('OBS_TEST_SNMP', snmp_status());
    } else {
      $device = build_initial_device_array($snmpsimd_ip, 'ios-1', 'v2c', $snmpsimd_port, 'udp');
      snmp_get_oid($device, '.1.3.6.1.2.1.1.2.0');
    }
  }

  /**
  * @dataProvider providerGetDeviceOS
  * @group os
  */
  public function testGetDeviceOS($os, $community)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    $device = build_initial_device_array($snmpsimd_ip, $community, 'v2c', $snmpsimd_port, 'udp');
    $device['snmp_timeout'] = 2;
    $device['snmp_retries'] = 1;
    //var_dump($device);

    // Clear cache before get_device_os()
    unset($GLOBALS['cache_snmp']);
    $this->assertSame($os, get_device_os($device));
  }

  public function providerGetDeviceOS()
  {
    global $snmpsimd_data;

    $results = array();

    if ($handle = opendir($snmpsimd_data))
    {
      while (false !== ($file = readdir($handle)))
      {
        if (filetype($snmpsimd_data . '/' . $file) === 'file' && preg_match('/^(?<community>(?<os>[\S]+?)(?:\-\d+)?)\.snmprec$/', $file, $matches))
        {
          //if (!str_starts($matches['os'], 'zyxel')) { continue; }
          $results[] = array($matches['os'], $matches['community']);
        }
      }
      closedir($handle);
    }
    //var_dump($results);

    return $results;
  }

  /**
   * Redetect OS (simulate previously incorrect detected os, or changed)
   *
   * @dataProvider providerGetDeviceOS2
   * @group os-changed
   */
  public function testGetDeviceOS2($community, $old_os)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    $device = build_initial_device_array($snmpsimd_ip, $community, 'v2c', $snmpsimd_port, 'udp');
    $device['snmp_timeout'] = 2;
    $device['snmp_retries'] = 1;
    if ($old_os)
    {
      $device['os'] = $old_os;
    }
    //var_dump($device);

    if (preg_match('/^(?<community>(?<os>[\S]+?)(?:\-\d+)?)$/', $community, $matches))
    {
      $os = $matches['os'];

      // Clear cache before get_device_os()
      unset($GLOBALS['cache_snmp']);
      $this->assertSame($os, get_device_os($device));
    }
  }

  public function providerGetDeviceOS2()
  {
    return array(
      //         community/os,       old os (incorrect)
      array(        'iosxe-1',         'ios'),
      array(        'iosxe-2',       'iosxr'),
      array(        'iosxe-3',       'linux'),

      // Linux based
      array(     'engenius-4',       'linux'),
      array(        'ddwrt-1',       'linux'),
      array(        'ddwrt-2',       'linux'),
      array(      'openwrt-1',       'linux'),
      array(      'openwrt-2',       'linux'),
      array(    'steelhead-1',       'linux'),
      array(     'opengear-1',       'linux'),
      array(         'epmp-2',       'linux'),
      array(        'zxv10-2',       'linux'),
      array(   'cumulus-os-1',       'linux'),
      array(     'cisco-uc-1',       'linux'),
      array(  'ciscosb-nss-1',       'linux'),
      array(      'kemp-lb-1',       'linux'),
      array(    'exinda-os-1',       'linux'),
      array(       'ge-ups-1',       'linux'),
      array(     'tplinkap-2',       'linux'),
      array(       'tplink-1',       'linux'),
      array(   'ciscosb-wl-1',       'linux'),
      array(   'ciscosb-rv-1',       'linux'),
      array(  'cisco-acano-1',       'linux'),
      array(      'ibm-imm-1',       'linux'),
      array(   'mcafee-meg-1',       'linux'),
      array( 'barracuda-lb-1',       'linux'),
      array(         'drac-2',       'linux'),
      array(     'sofaware-2',       'linux'),
      array(          'dss-1',       'linux'),
      array(  'generex-ups-3',       'linux'),
      array(   'eltex-voip-1',       'linux'),
      array(   'eltex-voip-3',     'openwrt'),
      array(   'eltex-gpon-1',       'linux'),
      array(       'ge-ups-1',       'linux'),
      array(         'qnap-1',       'linux'),
      array(         'qnap-2',       'linux'),
      array(          'srm-1',       'linux'),
      array(          'dsm-1',       'linux'),
      array(         'gaia-1',       'linux'),
      array(        'splat-1',       'linux'),
      array(      'fireeye-1',       'linux'),
      array(          'ddn-1',       'linux'),
      array(       'zeustm-1',       'linux'),
      array(    'steelhead-1',       'linux'),
      array(  'jetnexus-lb-1',       'linux'),
      array(        'airos-1',       'linux'),
      array(     'airos-af-1',       'linux'),
      array(        'unifi-1',       'linux'),
      array(        'unifi-2',       'linux'),
      array('netgear-readyos-1',     'linux'),
      //array('pcoweb-chiller-1',      'linux'),
      //array(  'pcoweb-crac-1',       'linux'),
      array(      'edgemax-5',       'airos'),

      // FreeBSD based
      array(     'opnsense-1',     'freebsd'),
      array(     'opnsense-2',     'freebsd'),
      array(     'monowall-2',     'freebsd'),
      array(   'compellent-1',     'freebsd'),
      array(      'pfsense-1',     'freebsd'),
      array(      'pfsense-2',     'freebsd'),
      array(     'nas4free-1',     'freebsd'),
      array(      'freenas-1',     'freebsd'),

      // Solaris based
      array(  'opensolaris-1',     'solaris'),
      array(  'openindiana-1',     'solaris'),

      // Extreme
      array(  'xos-1',             'extremeware'),
      array(  'extreme-wlc-1',     'extremeware'),
    );
  }

  /**
  * @dataProvider providerIsSNMPable
  * @group snmpable
  */
  public function testIsSNMPable($os, $test_os, $result)
  {
    global $snmpsimd_ip, $snmpsimd_port;

    foreach (array('v2c', 'v3') as $snmp_version)
    //foreach (array('v3') as $snmp_version)
    {
      switch ($snmp_version)
      {
        case 'v2c':
          $community = $os . '-1';
          $device = build_initial_device_array($snmpsimd_ip, $community, $snmp_version, $snmpsimd_port, 'udp');
          break;
        case 'v3':
          $snmp_version = 'v3';
          $snmp_v3 = array('authlevel'  => 'authPriv',
                           'authname'   => 'simulator',
                           'authpass'   => 'auctoritas',
                           'authalgo'   => 'MD5',
                           'cryptopass' => 'privatus',
                           'cryptoalgo' => 'DES');
          $device = build_initial_device_array($snmpsimd_ip, NULL, $snmp_version, $snmpsimd_port, 'udp', $snmp_v3);
          $device['snmp_context'] = $os . '-1';
          break;
      }
      $device['snmp_timeout'] = 2;
      $device['snmp_retries'] = 1;
      //var_dump($device);
      if ($test_os)
      {
        $device['os'] = $test_os;
      }
      if ($result)
      {
        // good response - int greater than 0
        $this->assertGreaterThan(0, is_snmpable($device));
      } else {
        // bad response return 0
        $device['snmp_timeout'] = 1;
        $this->assertSame(0, is_snmpable($device));
      }
    }
  }

    /**
     * @dataProvider providerIsSNMPable
     * @group snmperrors
     */
    public function testIsSNMPableError($os, $test_os, $status, $error = OBS_SNMP_ERROR_OK)
    {
        global $snmpsimd_ip, $snmpsimd_port;

        $GLOBALS['config']['snmp']['errors'] = FALSE; // force disable log to db

        foreach ([ 'v2c', 'v3' ] as $snmp_version)
            //foreach (array('v3') as $snmp_version)
        {
            switch ($snmp_version)
            {
                case 'v2c':
                    $community = $os . '-1';
                    $device = build_initial_device_array($snmpsimd_ip, $community, $snmp_version, $snmpsimd_port, 'udp');
                    break;
                case 'v3':
                    $snmp_version = 'v3';
                    $snmp_v3 = [ 'authlevel'  => 'authPriv',
                                 'authname'   => 'simulator',
                                 'authpass'   => 'auctoritas',
                                 'authalgo'   => 'MD5',
                                 'cryptopass' => 'privatus',
                                 'cryptoalgo' => 'DES' ];
                    $device = build_initial_device_array($snmpsimd_ip, NULL, $snmp_version, $snmpsimd_port, 'udp', $snmp_v3);
                    $device['snmp_context'] = $os . '-1';
                    break;
            }
            $device['snmp_timeout'] = 2;
            $device['snmp_retries'] = 1;
            //var_dump($device);
            if ($test_os)
            {
                $device['os'] = $test_os;
            }
            if (!$status) {
                // bad response return 0
                $device['snmp_timeout'] = 1;
            }
            is_snmpable($device);
            $this->assertSame($error, snmp_error_code());
        }
    }

  public function providerIsSNMPable()
  {
    return array(
      // Both sysObjectID.0 and sysUpTime.0 available
      array(        'linux',            NULL, TRUE), // os not known
      array(        'linux',         'linux', TRUE), // known os
      array(        'linux', '8734hr7hf3f38', TRUE), // simulate os name changed

      // Only sysObjectID.0 available
      array(          'ios',            NULL, TRUE), // os not known
      array(          'ios',           'ios', TRUE), // known os
      array(          'ios', '8734hr7hf3f38', TRUE), // simulate os name changed

      // Only sysUpTime.0 available
      array(      'airport',            NULL, TRUE), // os not known
      array(      'airport',       'airport', TRUE), // known os
      array(      'airport', '8734hr7hf3f38', TRUE), // simulate os name changed

      // sysObjectID.0 and sysUpTime.0 not exist
      array('hikvision-cam',            NULL, TRUE), // os not known
      array('hikvision-cam', 'hikvision-cam', TRUE), // known os
      array('hikvision-cam', '8734hr7hf3f38', TRUE), // simulate os name changed

      // Simulate not snmpable device
      array('hikvision-XXX',            NULL, FALSE, OBS_SNMP_ERROR_REQUEST_TIMEOUT), // os not known
      array('hikvision-XXX', 'hikvision-cam', FALSE, OBS_SNMP_ERROR_ISSNMPABLE), // known os
      array('hikvision-XXX', '8734hr7hf3f38', FALSE, OBS_SNMP_ERROR_REQUEST_TIMEOUT), // simulate os name changed
    );
  }
}

// EOF
