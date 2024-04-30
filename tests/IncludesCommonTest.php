<?php

//define('OBS_DEBUG', 2);

$base_dir = realpath(__DIR__ . '/..');
$config['install_dir'] = $base_dir;

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

// Notes about JSON precision:
// php 7.0 and earlier use precision for floats in json_encode()
ini_set('precision',           14);
ini_set('serialize_precision', 17);
//var_dump(OBS_JSON_DECODE); exit;

class IncludesCommonTest extends \PHPUnit\Framework\TestCase
{
  /**
   * @dataProvider providerSafeCount
   * @group safe
   */
  public function testSafeCount($value, $result)
  {
    $this->assertSame($result, safe_count($value));
  }

  public function providerSafeCount()
  {
    return [
      [ NULL,        0 ],
      [ TRUE,        0 ],
      [ FALSE,       0 ],
      [ '3y',        0 ],
      [ '',          0 ],
      [ '1',         0 ],
      [ '0',         0 ],
      [ '-1',        0 ],
      [ 1,           0 ],
      [ 0,           0 ],
      [ -1,          0 ],
      // common format_uptime() strings
      [ [],                                       0 ],
      [ [ 1 ],                                    1 ],
      [ [ '1234.5', 3, '1234' ],                  3 ],
      [ new ArrayIterator([]),                    0 ],
      [ new ArrayIterator(['foo', 'bar', 'baz']), 3 ]
    ];
  }

  /**
   * @dataProvider providerSafeEmpty
   * @group safe
   */
  public function testSafeEmpty($value, $result)
  {
    $this->assertSame($result, safe_empty($value));
  }

  public function providerSafeEmpty()
  {
    return [
      [ NULL,        TRUE ],
      [ TRUE,        FALSE ],
      [ FALSE,       FALSE ],
      [ '3y',        FALSE ],
      [ '',          TRUE ],
      [ '1',         FALSE ],
      [ '0',         FALSE ],
      [ '00',        FALSE ],
      [ 0x0,         FALSE ],
      [ '-1',        FALSE ],
      [ 1,           FALSE ],
      [ 0,           FALSE ],
      [ -1,          FALSE ],
      // common format_uptime() strings
      [ [],                                       TRUE ],
      [ [ 1 ],                                    FALSE ],
      [ [ '1234.5', 3, '1234' ],                  FALSE ],
      [ new ArrayIterator([]),                    FALSE ],
      [ new ArrayIterator(['foo', 'bar', 'baz']), FALSE ]
    ];
  }

  /**
   * @dataProvider providerAgeToSeconds
   * @group datetime
   */
  public function testAgeToSeconds($value, $result) {
    $this->assertSame($result, age_to_seconds($value));
  }

  public function providerAgeToSeconds() {
    return array(
      array('3y 4M 6w 5d 3h 1m 3s',         109191663),
      array('3y4M6w5d3h1m3s',               109191663),
      array('184 days 22 hrs 02 min 38 sec', 15976958),
      array('1.5w',                            907200),
      array('2Y',                            63072000),
      array('315 days18:50:04',              27283804),
      array('35 days, 06:58:01',              3049081),
      // common format_uptime() strings
      array('2 years, 1 day, 1h 1m 1s',      63162061),
      array('1 year, 1 day, 1h 1m 1s',       31626061),
      array('2 hours 2 minutes 2 seconds',       7322),
      // incorrect age
      array(-886732,                                0),
      array('Star Wars',                            0),
    );
  }

  /**
   * @dataProvider providerFormatUptime
   * @group datetime
   */
  public function providerAgeToSeconds2($result, $format, $value) {
    $this->assertSame($result, age_to_seconds($value));
  }

  /**
   * @dataProvider providerUptimeToSeconds
   * @group datetime
   */
  public function testUptimeToSeconds($value, $result) {
    $this->assertSame($result, uptime_to_seconds($value));
  }

  public function providerUptimeToSeconds() {
    return [
      [ '00:11:09',                  669 ],
      [ '4d05h',                  363600 ],
      [ '315 days18:50:04',     27283804 ],
      [ 'up 35 days, 06:58:01',  3049081 ],
    ];
  }

  /**
   * @dataProvider providerAgeToUnixtime
   * @group datetime
   */
  public function testAgeToUnixtime($value, $min_age, $result)
  {
    // We fudge this a little since it's difficult to mock time().
    // We simply make sure that we are not off by more than 2 secs.
    if ($result > 0) {
      // prevent long time running errors
      $result = time() - $result;
    }
    $this->assertLessThanOrEqual(2, age_to_unixtime($value, $min_age) - $result);
  }

  public function providerAgeToUnixtime()
  {
    return array(
      array('3y 4M 6w 5d 3h 1m 3s', 1, 109191663),
      array('3y4M6w5d3h1m3s',       1, 109191663),
      array('1.5w',                 1,    907200),
      array('30m',               7200,         0),
      array(-886732,                1,         0),
      array('Star Wars',            1,         0),
    );
  }

  /**
   * @dataProvider providerExternalExec
   * @group exec
   */
  public function testExternalExec($cmd, $timeout, $result)
  {
    //var_dump($_SERVER['SHELL']);
    $test = external_exec($cmd, $exec_status, $timeout);
    unset($exec_status['endtime'], $exec_status['runtime'], $exec_status['exitdelay']);
    if ($exec_status['exitcode'] == 127) {
      // Fix shell specific output for tests
      $exec_status['stderr'] = str_replace(array('sh: 1:', 'sh: exec:', 'not found'),
                                           array('sh:',    'sh:',       'No such file or directory'),
                                           $exec_status['stderr']);
    }
    $this->assertSame($result, $exec_status);
  }

  public function providerExternalExec()
  {
    // CentOS 6 use different place for which
    $cmd_which = is_executable('/bin/which') ? '/bin/which' : '/usr/bin/which';
    return array(
      // normal stdout
      array($cmd_which.' true',
            NULL,
            array(
              'stdout'   => '/bin/true',
              'stderr'   => '',
              //'command'  => $cmd_which.' true',
              'exitcode' => 0,
            )
      ),
      // here generate stderr
      array($cmd_which.' true >&2',
            NULL,
            array(
              'stdout'   => '',
              'stderr'   => '/bin/true',
              //'command'  => $cmd_which.' true >&2',
              'exitcode' => 0,
            )
      ),
      // normal stdout, but exitcode 1
      array('/bin/false',
            NULL,
            array(
              'stdout'   => '',
              'stderr'   => '',
              //'command'  => '/bin/false',
              'exitcode' => 1,
            )
      ),
      // real stdout, exit code 127
      array('/bin/jasdhksdhka',
            NULL,
            array(
              'stdout'   => '',
              //'stderr'   => 'sh: 1: /bin/jasdhksdhka: not found', // this stderror is shell env specific for dash
              'stderr'   => 'sh: /bin/jasdhksdhka: No such file or directory', // this stderror is shell env specific for bash
              //'command'  => '/bin/jasdhksdhka',
              'exitcode' => 127,
            )
      ),
      // normal stdout with special chars (tabs, eol in eof)
      array('/bin/cat ' . __DIR__ . '/data/text.txt',
             NULL,
             array(
               'stdout'   =>
"Observium is an autodiscovering network monitoring platform
\tsupporting a wide range of hardware platforms and operating systems
\tincluding Cisco, Windows, Linux, HP, Juniper, Dell, FreeBSD, Brocade,
\tNetscaler, NetApp and many more.

 Observium seeks to provide a powerful yet simple and intuitive interface
 to the health and status of your network.

~!@#$%^&*()_+`1234567890-=[]\{}|;':\",./<>?

",
               'stderr'   => '',
               //'command'  => '/bin/cat ' . __DIR__ . '/data/text.txt',
               'exitcode' => 0,
             )
      ),
      // timeout 5sec, ok
      array('/bin/sleep 1',
            5,
            array(
              'stdout'   => '',
              'stderr'   => '',
              //'command'  => '/bin/sleep 1',
              'exitcode' => 0,
            )
      ),
      // timeout 2sec, expired, exitcode -1
      array('/bin/sleep 10',
            1,
            array(
              'stdout'   => '',
              'stderr'   => '',
              //'command'  => '/bin/sleep 10',
              'exitcode' => -1,
            )
      ),

      // Empty
      array('',
            NULL,
            array(
              'stdout'   => '',
              'stderr'   => 'Empty command passed',
              //'command'  => '',
              'exitcode' => -1
            )
      ),
      array(FALSE,
            NULL,
            array(
              'stdout'   => '',
              'stderr'   => 'Empty command passed',
              //'command'  => '',
              'exitcode' => -1,
            )
      ),

    );
  }

  /* This test not work for now */
  ///**
  // * @dataProvider providerExternalExecHideAuth
  // * @group exechide
  // */
  //public function testExternalExecHideAuth($hide, $cmd, $result)
  //{
  //  $GLOBALS['config']['snmp']['hide_auth'] = $hide;
  //
  //  $test = external_exec($cmd);
  //  $this->assertSame($result, $GLOBALS['exec_status']['command']);
  //}
  //
  //public function providerExternalExecHideAuth()
  //{
  //  return array(
  //    // hide auth
  //    array(
  //      TRUE,
  //      "/usr/bin/snmpget -v2c -c 'public' -Pu  -Oqv -m CPQSINFO-MIB -M /opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/hp 'udp':'host':'161' cpqSiProductName.0",
  //      "/usr/bin/snmpget -v2c -c *** -Pu  -Oqv -m CPQSINFO-MIB -M /opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/hp 'udp':'host':'161' cpqSiProductName.0",
  //    ),
  //    // not hide auth
  //    array(
  //      FALSE,
  //      "/usr/bin/snmpget -v2c -c 'public' -Pu  -Oqv -m CPQSINFO-MIB -M /opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/hp 'udp':'host':'161' cpqSiProductName.0",
  //      "/usr/bin/snmpget -v2c -c *** -Pu  -Oqv -m CPQSINFO-MIB -M /opt/observium/mibs/rfc:/opt/observium/mibs/net-snmp:/opt/observium/mibs/hp 'udp':'host':'161' cpqSiProductName.0",
  //    ),
  //  );
  //}

  /**
   * @group pid
   */
  public function testGetPidInfo() {

    $pid = getmypid();
    $timezone = get_timezone(); // Get system timezone info, for correct started time conversion

    // Compare only this keys
    $compare_keys = [ 'PID', 'PPID', 'UID', 'GID', 'COMMAND', 'STARTED', 'STARTED_UNIX', 'VSZ' ];

    $test_pid_info['ps']       = get_pid_info($pid);
    $test_pid_info['ps_stats'] = get_pid_info($pid, TRUE);
    $test['ps']       = external_exec('/bin/ps -ww -o pid,ppid,uid,gid,tty,stat,time,lstart,args -p '.$pid, $exec_status, 1); // Set timeout 1sec for exec
    $test['ps_stats'] = external_exec('/bin/ps -ww -o pid,ppid,uid,gid,pcpu,pmem,vsz,rss,tty,stat,time,lstart,args -p '.$pid, $exec_status, 1); // Set timeout 1sec for exec

    foreach ($test as $ps_type => $ps) {
      // Copy-pasted from function get_pid_info()
      $ps = explode("\n", rtrim($test[$ps_type]));

      // Parse output
      $keys = preg_split("/\s+/", $ps[0], -1, PREG_SPLIT_NO_EMPTY);
      $entries = preg_split("/\s+/", $ps[1], count($keys) - 1, PREG_SPLIT_NO_EMPTY);
      $started = preg_split("/\s+/", array_pop($entries), 6, PREG_SPLIT_NO_EMPTY);
      $command = array_pop($started);

      $started[]    = str_replace(':', '', $timezone['system']); // Add system TZ to started time
      $started_rfc  = array_shift($started) . ','; // Sun
      // Reimplode and convert to RFC2822 started date 'Sun, 20 Mar 2016 18:01:53 +0300'
      $started_rfc .= ' ' . ltrim($started[1], '0'); // 20
      //$started_rfc .= ' ' . str_pad($started[1], 2, '0', STR_PAD_LEFT); // 20
      $started_rfc .= ' ' . $started[0]; // Mar
      $started_rfc .= ' ' . $started[3]; // 2016
      $started_rfc .= ' ' . $started[2]; // 18:01:53
      $started_rfc .= ' ' . $started[4]; // +0300
      //$started_rfc .= implode(' ', $started);
      $entries[] = $started_rfc;

      $entries[] = $command; // Re-add command
      //print_vars($entries);
      //print_vars($started);

      $pid_info = [];
      foreach ($keys as $i => $key) {
        if (!in_array($key, $compare_keys, TRUE)) { continue; }
        $pid_info[$key] = $entries[$i];
      }
      $pid_info['STARTED_UNIX'] = strtotime($pid_info['STARTED']);

      foreach ($test_pid_info[$ps_type] as $key => $tmp) {
        if (!in_array($key, $compare_keys, TRUE)) {
          unset($test_pid_info[$ps_type][$key]);
        }
        if ($key === 'STARTED') {
          // Another derp fix for dates
          // Wed, 01 Dec 2021 10:28:51 +0300
          $test_pid_info[$ps_type][$key] = preg_replace('/, 0(\d) /', ', $1 ', $test_pid_info[$ps_type][$key]);
        }
      }
      ksort($test_pid_info[$ps_type]);
      ksort($pid_info);

      $diff = abs($pid_info['STARTED_UNIX'] - $test_pid_info[$ps_type]['STARTED_UNIX']);
      if ($diff > 0 && $diff < 5) {
        // prevent false in time diff around 5 sec
        $pid_info['STARTED_UNIX'] = $test_pid_info[$ps_type]['STARTED_UNIX'];
        $pid_info['STARTED']      = ltrim($test_pid_info[$ps_type]['STARTED'], '0');
      }
      $this->assertSame($test_pid_info[$ps_type], $pid_info);
    }

  }

  /**
   * @dataProvider providerPercentClass
   */
  public function testPercentClass($value, $result)
  {
    $this->assertSame($result, percent_class($value));
  }

  public function providerPercentClass()
  {
    return array(
      array(  24,      'info'), // if < 25
      array(  25,          ''), // if < 50
      array(  49,          ''),
      array(  50,   'success'), // if < 75
      array(  74,   'success'),
      array(  75,   'warning'), // if < 90
      array(  89,   'warning'),
      array(  90,    'danger'), // else

      array('24',      'info'), // string input
      array(24.0,      'info'), // float input
    );
  }

  /**
   * @dataProvider providerPercentColour
   */
  public function testPercentColour($value, $brightness, $result)
  {
    if ($brightness === NULL) {
      $this->assertSame($result, percent_colour($value));
    } else {
      $this->assertSame($result, percent_colour($value, $brightness));
    }
  }

  public function providerPercentColour()
  {
    return array(
      array(0,    NULL, '#008000'), // default brightness
      array(20,   NULL, '#338000'),
      array(40,   NULL, '#668000'),
      array(60,   NULL, '#806600'),
      array(80,   NULL, '#803300'),
      array(100,  NULL, '#800000'),
      array(0,      64, '#004000'), // brightness = 64
      array(20,     64, '#194000'),
      array(40,     64, '#334000'),
      array(60,     64, '#403300'),
      array(80,     64, '#401900'),
      array(100,    64, '#400000'),
      array(0,     192, '#00c000'), // brightness = 192
      array(20,    192, '#4cc000'),
      array(40,    192, '#99c000'),
      array(60,    192, '#c09900'),
      array(80,    192, '#c04c00'),
      array(100,   192, '#c00000'),
      array(0,     255, '#00ff00'), // brightness = 256
      array(20,    255, '#66ff00'),
      array(40,    255, '#ccff00'),
      array(60,    255, '#ffcc00'),
      array(80,    255, '#ff6500'),
      array(100,   255, '#ff0000'),
    );
  }

  /**
   * @dataProvider providerTimeticksToSec
   * @group datetime
   */
  public function testTimeticksToSec($value, $float, $result)
  {
    $this->assertSame($result, timeticks_to_sec($value, $float));
  }

  public function providerTimeticksToSec()
  {
    return array(
      // Main, return integer
      array('178:23:06:59.03', FALSE,   15462419),
      array('1:2:34:56.78',    FALSE,      95696),
      array('0:0:00:00.00',    FALSE,          0),
      // Non standard with years
      array('05:314:02:06:26', FALSE,  184817186),
      // Main, return float
      array('178:23:06:59.03', TRUE, 15462419.03),
      array('1:2:34:56.78',    TRUE,    95696.78),
      array('0:0:00:00.00',    TRUE,         0.0),
      // Spaces, quotes, brackets
      array('"  (9569678)"',  FALSE,       95696),
      array('(9569678)',       TRUE,    95696.78),
      array('1546241903',      TRUE, 15462419.03),
      array('"1:2:34:56.78"',  TRUE,    95696.78),
      array('1: 2:34:56.78',  FALSE,       95696),
      // Wrong Type
      array('Wrong Type (should be Timeticks): 1632295600', FALSE, 16322956),
      // Wrong data, return FALSE
      array(NULL,            FALSE,    FALSE),
      array('',              FALSE,    FALSE),
      array('No',            FALSE,    FALSE),
      array('1-2-34-56.78',  FALSE,    FALSE),
    );
  }

  /**
   * @dataProvider providerDeviceUptime
   * @group values
   */
  public function testDeviceUptime($value, $result)
  {
    $this->assertSame($result, device_uptime($value));
  }

  public function providerDeviceUptime()
  {
    return array(
      array(array('status' => 0, 'last_polled' =>        0),  'Never polled'),
      array(array('status' => 0, 'last_polled' => '-1 day'),  'Down 1 day'),
      array(array('status' => 1, 'uptime'      =>   '3600'),  '1h'),
    );
  }

  /**
   * @dataProvider providerFormatUptime
   * @group datetime
   */
  public function testFormatUptime($value, $format, $result)
  {
    $this->assertSame($result, format_uptime($value, $format));
  }

  public function providerFormatUptime()
  {
    return array(
      array(       0, 'long',     '0s'),                          // zero

      // format = long
      array(       1, 'long',     '1s'),                          // singulars
      array(      60, 'long',     '1m'),
      array(    3600, 'long',     '1h'),
      array(   86400, 'long',     '1 day'),
      array(31536000, 'long',     '1 year'),
      array(   90061, 'long',     '1 day, 1h 1m 1s'),
      array(31626061, 'long',     '1 year, 1 day, 1h 1m 1s'),

      array(    3661, 'long',     '1h 1m 1s'),                    // h/m/s mixins
      array(      61, 'long',     '1m 1s'),
      array(    3601, 'long',     '1h 1s'),
      array(    3660, 'long',     '1h 1m'),

      array(  176461, 'long',     '2 days, 1h 1m 1s'),            // plurals
      array(63162061, 'long',     '2 years, 1 day, 1h 1m 1s'),

      // format = longest
      array(       1, 'longest',  '1 second'),                    // singulars
      array(      60, 'longest',  '1 minute'),
      array(    3600, 'longest',  '1 hour'),

      array(    3661, 'longest',  '1 hour 1 minute 1 second'),    // h/m/s mixins
      array(      61, 'longest',  '1 minute 1 second'),
      array(    3601, 'longest',  '1 hour 1 second'),
      array(    3660, 'longest',  '1 hour 1 minute'),

      array(    7322, 'longest',  '2 hours 2 minutes 2 seconds'), // plural

      // format = short-3
      array(       1, 'short-3',  '1s'),                          // singulars
      array(      60, 'short-3',  '1m'),
      array(    3600, 'short-3',  '1h'),
      array(   86400, 'short-3',  '1d'),
      array(31536000, 'short-3',  '1y'),

      array(      61, 'short-3',  '1m 1s'),                       // minute mixins

      array(    3601, 'short-3',  '1h 1s'),                       // hour mixins
      array(    3660, 'short-3',  '1h 1m'),
      array(    3661, 'short-3',  '1h 1m 1s'),

      array(   86401, 'short-3',  '1d 1s'),                       // day mixins
      array(   86460, 'short-3',  '1d 1m'),
      array(   86461, 'short-3',  '1d 1m 1s'),
      array(   90000, 'short-3',  '1d 1h'),
      array(   90001, 'short-3',  '1d 1h 1s'),
      array(   90060, 'short-3',  '1d 1h 1m'),

      array(31536001, 'short-3',  '1y 1s'),                       // year mixins
      array(31536060, 'short-3',  '1y 1m'),
      array(31536061, 'short-3',  '1y 1m 1s'),
      array(31622400, 'short-3',  '1y 1d'),
      array(31622401, 'short-3',  '1y 1d 1s'),
      array(31622460, 'short-3',  '1y 1d 1m'),
      array(31626000, 'short-3',  '1y 1d 1h'),

      array(31626061, 'short-1',  '1y'),
      array(31626061, 'short-4',  '1y 1d 1h 1m'),

      // format = short-2
      array(       1, 'short-2',  '1s'),                          // singulars
      array(      60, 'short-2',  '1m'),
      array(    3600, 'short-2',  '1h'),
      array(   86400, 'short-2',  '1d'),
      array(31536000, 'short-2',  '1y'),

      array(      61, 'short-2',  '1m 1s'),                       // minute mixins

      array(    3601, 'short-2',  '1h 1s'),                       // hour mixins
      array(    3660, 'short-2',  '1h 1m'),

      array(   86401, 'short-2',  '1d 1s'),                       // day mixins
      array(   86460, 'short-2',  '1d 1m'),
      array(   90000, 'short-2',  '1d 1h'),

      array(31536001, 'short-2',  '1y 1s'),                       // year mixins
      array(31536060, 'short-2',  '1y 1m'),
      array(31622400, 'short-2',  '1y 1d'),

      // format = shorter (should get same results as short-2)
      array(       1, 'shorter',  '1s'),                          // singulars
      array(      60, 'shorter',  '1m'),
      array(    3600, 'shorter',  '1h'),
      array(   86400, 'shorter',  '1d'),
      array(31536000, 'shorter',  '1y'),

      array(      61, 'shorter',  '1m 1s'),                       // minute mixins

      array(    3601, 'shorter',  '1h 1s'),                       // hour mixins
      array(    3660, 'shorter',  '1h 1m'),

      array(   86401, 'shorter',  '1d 1s'),                       // day mixins
      array(   86460, 'shorter',  '1d 1m'),
      array(   90000, 'shorter',  '1d 1h'),

      array(31536001, 'shorter',  '1y 1s'),                       // year mixins
      array(31536060, 'shorter',  '1y 1m'),
      array(31622400, 'shorter',  '1y 1d'),

      // incorrect data
      array(      '',    'long',     '0s'),
      array(    NULL,    'long',     '0s'),
      array(      [],    'long',     '0s'),
    );
  }

  /**
   * @dataProvider providerHumanspeed
   * @group values
   */
  public function testHumanspeed($value, $result)
  {
    $this->assertSame($result, humanspeed($value));
  }

  public function providerHumanspeed()
  {
    return array(
      array(     '',         '-'),
      array(1024000,  '1.02Mbps'),
    );
  }

  /**
   * @dataProvider providerFormatMac
   * @group mac
   */
  public function testFormatMac($value, $result, $char = ':')
  {
    $this->assertSame($result, format_mac($value, $char));
  }

  public function providerFormatMac()
  {
    return array(
      array(     '123456789ABC', '12:34:56:78:9a:bc'),
      array(   '1234.5678.9abc', '12:34:56:78:9a:bc'),
      array('12:34:56:78:9a:bc', '12:34:56:78:9a:bc'),

      // Zeropad MAC
      array( '66:c:9b:1b:62:7e', '66:0c:9b:1b:62:7e'),
      array(      '0:0:0:0:0:0', '00:00:00:00:00:00'),
      array(   '0x123456789ABC', '12:34:56:78:9a:bc'),

      // MAC valid
      array('0026.22eb.3bef',    '00:26:22:eb:3b:ef'), // Cisco
      array('00-02-2D-11-55-4D', '00:02:2d:11:55:4d'), // Windows
      array('00 0D 93 13 51 1A', '00:0d:93:13:51:1a'), // Old Unix
      array('0x000E7F0D81D6',    '00:0e:7f:0d:81:d6'), // HP-UX
      array('0004E25AA118',      '00:04:e2:5a:a1:18'), // DOS, RAW
      array('00:08:C7:1B:8C:02', '00:08:c7:1b:8c:02'), // Unix/Linux
      array('8:0:86:b6:82:9f',   '08:00:86:b6:82:9f'), // SNMP, Solaris

      // Split chars
      array(     '123456789ABC', '123456789abc',      ''),
      array(   '1234.5678.9abc', '12 34 56 78 9A BC', ' '),
      array('12:34:56:78:9a:bc', '1234.5678.9abc',    '.'),
      array( '66:c:9b:1b:62:7e', '660c9b1b627e',      ''),
      array(      '0:0:0:0:0:0', '00 00 00 00 00 00', ' '),
      array(   '0x123456789ABC', '1234.5678.9abc',    '.'),
      array(   '1234.5678.9abc', '0x123456789ABC',    '0x'),
      array('12:34:56:78:9a:bc', '0x123456789ABC',    '0x'),

      // Fake MAC to IPv4 (for 6to4 tunnels)
      array('ff:fe:56:78:9a:bc',    '86.120.154.188'),
      array('ff:fe:00:00:9a:bc',       '154.188.X.X'),
    );
  }

  /**
   * @dataProvider providerMacZeropad
   * @group mac
   */
  public function testMacZeropad($value, $result)
  {
    $this->assertSame($result, mac_zeropad($value));
  }

  public function providerMacZeropad()
  {
    return array(
      array(     '123456789ABC', '123456789abc'),
      array(   '1234.5678.9abc', '123456789abc'),
      array('12:34:56:78:9a:BC', '123456789abc'),
      array( '66:c:9b:1b:62:7e', '660c9b1b627e'),
      array(      '0:0:0:0:0:0', '000000000000'),
      array(   '0x123456789ABC', '123456789abc'),

      // incorrect
      array( '66:Z:9b:1b:62:7e',           NULL),
      array('66:c:c:b:1b:62:7e',           NULL),
      array(      'ff:fe:56:78',           NULL),
      array(                  0,           NULL),
    );
  }

  /**
   * @dataProvider providerFormatNumberShort
   * @group numbers
   */
  public function testFormatNumberShort($value, $sf, $result)
  {
    $this->assertSame($result, format_number_short($value, $sf));
  }

  public function providerFormatNumberShort()
  {
    return array(
      array( '12345', 3,  '12345'),
      //array('1234.5', 3,   '1234'),
      array('1234.5', 3, '1234.5'), // FIXME. not sure that this is correct!
      array('123.45', 3,    '123'),
      array('12.345', 3,   '12.3'),
      array('1.2345', 3,   '1.23'),
      array('.12345', 3,  '0.123'),
      array('0.1234', 3,  '0.123'),
      array(   '1.0', 3,      '1'),

      array('-1.234', 3,  '-1.23'),
      array(  '-1.0', 3,     '-1'),

      array('1.234b', 3,   '1.23'), // alpha in decimals

      // zero
      array('-0', 3,  '0'),
      array(   0, 3,  '0'),
      array( 0.0, 3,  '0'),

      // big numbers
      array('1000.0', 3,  '1000'),
      array(  '1000', 3,  '1000'),
      array( '2834639823472972947924729342934679', 3,  '2834639823472972947924729342934679'),
      array('-2834639823472972947924729342934679', 3, '-2834639823472972947924729342934679'),

      // 0 > value < 1
      array(  '0.01234', 3,   '0.0123'),
      array('0.0001234', 3,   '0.000123'),
      array('0.0001235', 3,   '0.000124'),
    );
  }

  /**
   * @dataProvider providerSgn
   * @group values
   */
  public function testSgn($value, $result)
  {
    $this->assertSame($result, sgn($value));
  }

  public function providerSgn()
  {
    return array(
      array( 10,  1),
      array(  0,  0),
      array(-10, -1),
    );
  }

  /**
   * @dataProvider providerValueToSi
   * @group values
   */
  public function testValueToSi($type, $unit, $value, $result)
  {
    if (method_exists($this, 'assertEqualsWithDelta')) {
      // PHPUnit 8+
      $this->assertEqualsWithDelta($result, value_to_si($value, $unit, $type), 0.0001);
    } else {
      $this->assertEquals($result, value_to_si($value, $unit, $type), '', 0.0001);
    }
  }

  public function providerValueToSi()
  {
    return array(
      array(         NULL,   'F',      0,  -17.7777),
      array(         NULL,   'F',     32,    0.0),
      array(         NULL,   'F',    100,   37.7777),
      array(         NULL,   'F',   -100,  -73.3333),
      array('temperature',   'K',      0, -273.15),
      array('temperature',   'K', 273.15,    0.0),
      array('temperature',   'K',    100, -173.15),
      array('temperature',   'k',    100, -173.15),
      array('temperature',   'K',   -100,  FALSE),
      array('temperature',   'C',      0,    0.0),
      array('temperature',   'C',    100,  100.0),
      array(         NULL, 'psi',      0,    0.0),
      array(   'pressure', 'psi',      1, 6894.75729),
      array(   'pressure', 'psi',   -0.1, -689.4757),
      array(   'pressure', 'Mpsi',  -0.1, -689475729.3168),
      array(   'pressure', 'mpsi',  -0.1, -689475729.3168),
      array(   'pressure', 'mmHg',     1, 133.3224),
      array(   'pressure', 'mmhg',  -0.1, -13.3322),
      array(   'pressure', 'inHg',     1, 3386.3867),
      array(   'pressure', 'inhg',  -0.1, -338.6387),
      array(   'pressure', 'atm',      1, 101325.0),
      array(   'pressure', 'ATM',      1, 101325.0),

      array(        'dbm',   'W', 0.00001, -20.0),
      array(        'dbm',   'W',   0.001,   0.0),
      array(        'dbm',   'W',   10000,  70.0),
      array(        'dbm',   'w',   10000,  70.0),
      array(        'dbm',   'W',       0, -99.0),
      array(        'dbm',   'W',    -0.1, FALSE),
      array(      'power', 'dBm',     -30,   0.000001),
      array(      'power', 'dBm',       0,   0.001),
      array(      'power', 'dBm',      50, 100.0),
      array(      'power', 'dbm',      50, 100.0),

      // derp ekinops
      array(        'dbm', 'ekinops_dbm1',          0,     0.0),
      array(        'dbm', 'ekinops_dbm1',         30,     0.3),
      array(        'dbm', 'ekinops_dbm1',      64551,   -9.85),
      array(        'dbm', 'ekinops_dbm1',      65537,   FALSE),
      array(        'dbm', 'ekinops_dbm2',       4285, -3.6805),
      array(        'dbm', 'ekinops_dbm2',      14546,  1.6274),
      // derp Accuview (different types, but same unit)
      array(    'voltage',   'accuenergy', 1116881392, 73.1288), // 429241f0
      array(    'current',   'accuenergy', 1116881392, 73.1288),
      array(     'apower',   'accuenergy', 1116881392, 73.1288),
      array(      'power',   'accuenergy',          0, 5.8774E-39),
    );
  }

  /**
   * @dataProvider providerGetSensorRrd
   */
  public function testGetSensorRrd($device, $sensor, $config, $result)
  {
    $GLOBAL['config'] = $config;
    $this->assertSame($result, get_sensor_rrd($device, $sensor));
  }

  public function providerGetSensorRrd()
  {
    return array(
      array(array('os' => 'ios'), // device
            array('poller_type' => 'snmp', 'sensor_class' => 'class', 'sensor_type' => 'type', 'sensor_descr' => 'descr', 'sensor_index' => 4), // sensor
            array('os' => array('ios' => array('sensor_descr' => 'temperature'))), // config
            'sensor-class-type-4.rrd', // result
          ),
      array(array('os' => 'oh-es'), // device
            array('poller_type' => 'ipmi', 'sensor_class' => 'class', 'sensor_type' => 'type', 'sensor_descr' => 'descr', 'sensor_index' => 4), // sensor
            array('os' => array('oh-es' => array('sensor_descr' => 'temperature'))), // config
            'sensor-class-type-descr.rrd', // result
          ),
    );
  }

  /**
   * @dataProvider providerIfclass
   */
  public function testIfclass($ifOperStatus, $ifAdminStatus, $result)
  {
    $this->assertSame($result, port_html_class($ifOperStatus, $ifAdminStatus));
  }

  public function providerIfclass()
  {
    return array(
      array(             '-',   'up', 'purple'),
      array(            'up', 'down',   'gray'),
      array(          'down',   'up',    'red'),
      array('lowerLayerDown',   'up', 'orange'),
      array(    'monitoring',   'up',  'green'),
      array(            'up',   'up',       ''),
    );
  }

  /**
   * @dataProvider providerTruncate
   */
  public function testTruncate($value, $max, $rep = '...', $result)
  {
    $this->assertSame($result, truncate($value, $max, $rep));
  }

  public function providerTruncate()
  {
    return array(
      array('Observium is an autodiscovering network monitoring platform', 19, '...',
            'Observium is an ...'),
      array('Observium is an autodiscovering network monitoring platform', 19, '???',
            'Observium is an ???'),
    );
  }

  /**
   * @dataProvider providerGenerateRandomString
   */
  public function testGenerateRandomString($len, $chars, $regex)
  {
    $rv = random_string($len, $chars);

    if (method_exists($this, 'assertMatchesRegularExpression')) {
      $this->assertMatchesRegularExpression($regex, $rv);
    } else {
      $this->assertRegExp($regex, $rv);
    }
    $this->assertTrue($len === strlen($rv));
  }

  public function providerGenerateRandomString()
  {
    return array(
      array(96, NULL, '/^[[:alnum:]]+$/'),
      array(96, '1234567890', '/^\d+$/'),
    );
  }

  /**
   * @dataProvider providerSafename
   */
  public function testSafename($value, $result)
  {
    $this->assertSame($result, safename($value));
  }

  public function providerSafename()
  {
    return array(
      array('aA0._-',  'aA0._-'), // all good
      array('\\\'',     '__'),
      array('`~!@#$%^&*()=+{}[]|";:,/?<>',  '___________________________'),
    );
  }

  /**
   * @dataProvider providerIsIntNum
   * @group numbers
   */
  public function testIsIntNum($value, $result)
  {
    $this->assertSame($result, is_intnum($value));
  }

  public function providerIsIntNum()
  {
    return [
      [ 23,          TRUE ], // ctype_digit() = FALSE
      [ "23",        TRUE ], // is_int() = FALSE
      [ -23,         TRUE ], // ctype_digit() = FALSE
      [ "-23",       TRUE ], // is_int() = FALSE, ctype_digit() = FALSE
      [ 01,          TRUE ], // ctype_digit() = FALSE
      [ "01",        TRUE ], // is_int() = FALSE
      [ 923874924209482482038402384,  FALSE ], // false, because greater than PHP_INT_MAX
      [ '923874924209482482038402384', TRUE ],  // is_int() = FALSE
      [ PHP_INT_MAX, TRUE ],
      [ 23.5,       FALSE ],
      [ "23.5",     FALSE ],
      [ -23.5,      FALSE ],
      [ "-23.5",    FALSE ],
      [ "--2",      FALSE ],
      [ '',         FALSE ],
      [ null,       FALSE ],
      [ true,       FALSE ],
      [ false,      FALSE ],
      [ [],         FALSE ],
    ];
  }

  /**
   * @dataProvider providerZeropad
   * @group numbers
   */
  public function testZeropad($value, $result)
  {
    $this->assertSame($result, zeropad($value));
  }

  public function providerZeropad()
  {
    return array(
      array(1,     '01'),
      array(100,  '100'),
    );
  }

  /**
   * @dataProvider providerFormatRates
   * @group numbers
   */
  public function testFormatRates($value, $round, $sf, $result)
  {
    $this->assertSame($result, format_bps($value, $round, $sf));
  }

  public function providerFormatRates()
  {
    return array(
      // simple test; most testing is done against format_si
      array(10240000, 2, 3,  '10.2Mbps'),

      // round & sf
      array(10240000, 4, 4, '10.24Mbps'),
    );
  }

  /**
   * @dataProvider providerFormatStorage
   * @group numbers
   */
  public function testFormatStorage($value, $round, $sf, $result)
  {
    $this->assertSame($result, format_bytes($value, $round, $sf));
  }

  public function providerFormatStorage()
  {
    return array(
      // simple test; most testing is done against format_bi
      array(102400000, 2, 3,  '97.7MB'),

      // round & sf
      array(102400000, 4, 4, '97.66MB'),
    );
  }

  /**
   * @dataProvider providerFormatSi
   * @group numbers
   */
  public function testFormatSi($value, $round, $sf, $result)
  {
    $this->assertSame($result, format_si($value, $round, $sf));
  }

  public function providerFormatSi()
  {
    return array(
      // return int
      array(                   1, 2, 3,      '1'),
      array(                1000, 2, 3,     '1k'),
      array(               10000, 2, 3,    '10k'),
      array(              100000, 2, 3,   '100k'),
      array(             1000000, 2, 3,     '1M'),
      array(            10000000, 2, 3,    '10M'),
      array(           100000000, 2, 3,   '100M'),
      array(          1000000000, 2, 3,     '1G'),
      array(         10000000000, 2, 3,    '10G'),
      array(        100000000000, 2, 3,   '100G'),
      array(       1000000000000, 2, 3,     '1T'),
      array(    1000000000000000, 2, 3,     '1P'),
      array( 1000000000000000000, 2, 3,     '1E'),

      // return float
      array(                1100, 2, 3,   '1.1k'),
      array(             1100000, 2, 3,   '1.1M'),
      array(            10100000, 2, 3,  '10.1M'),
      array(          1100000000, 2, 3,   '1.1G'),
      array(       1100000000000, 2, 3,   '1.1T'),
      array(    1100000000000000, 2, 3,   '1.1P'),
      array( 1100000000000000000, 2, 3,   '1.1E'),

      // return negative
      array(               -1000, 2, 3,    '-1k'),
      array(              -10000, 2, 3,   '-10k'),
      array(             -100000, 2, 3,  '-100k'),
      array(            -1000000, 2, 3,    '-1M'),
      array(           -10000000, 2, 3,   '-10M'),
      array(          -100000000, 2, 3,  '-100M'),
      array(         -1000000000, 2, 3,    '-1G'),
      array(        -10000000000, 2, 3,   '-10G'),
      array(       -100000000000, 2, 3,  '-100G'),
      array(      -1000000000000, 2, 3,    '-1T'),
      array(   -1000000000000000, 2, 3,    '-1P'),
      array(-1000000000000000000, 2, 3,    '-1E'),

      // check base 1024
      array(                1024, 2, 3,  '1.02k'),
      array(             1024000, 2, 3,  '1.02M'),

      // round & sf
      array(             1024000, 4, 4, '1.024M'),
      array(           '1024000', 4, 4, '1.024M'),

      // incorrect data
      array(                  '', 2, 3,      '0'),
      array(                NULL, 2, 3,      '0'),
      array(                  [], 2, 3,      '0'),
    );
  }

  /**
   * @dataProvider providerFormatBi
   * @group numbers
   */
  public function testFormatBi($value, $round, $sf, $result)
  {
    $this->assertSame($result, format_bi($value, $round, $sf));
  }

  public function providerFormatBi()
  {
    return array(
      // return int
      array(                   1, 2, 3,      '1'),
      array(                1024, 2, 3,     '1k'),
      array(               10240, 2, 3,    '10k'),
      array(              102400, 2, 3,   '100k'),
      array(             1048576, 2, 3,     '1M'),
      array(            10485760, 2, 3,    '10M'),
      array(           104857600, 2, 3,   '100M'),
      array(          1073741824, 2, 3,     '1G'),
      array(         10737418240, 2, 3,    '10G'),
      array(        107374182400, 2, 3,   '100G'),
      array(       1099511627776, 2, 3,     '1T'),
      array(    1125899906842624, 2, 3,     '1P'),
      array( 1152921504606846976, 2, 3,     '1E'),

      // return float
      array(                1126, 2, 3,   '1.1k'),
      array(             1153466, 2, 3,   '1.1M'),
      array(            10590617, 2, 3,  '10.1M'),
      array(          1181116006, 2, 3,   '1.1G'),
      array(       1209462790553, 2, 3,   '1.1T'),
      array(    1238489897526886, 2, 3,   '1.1P'),
      array( 1268213655067531673, 2, 3,   '1.1E'),

      // negative
      array(                  -1, 2, 3,     '-1'),
      array(               -1024, 2, 3,    '-1k'),
      array(              -10240, 2, 3,   '-10k'),
      array(             -102400, 2, 3,  '-100k'),
      array(            -1048576, 2, 3,    '-1M'),
      array(           -10485760, 2, 3,   '-10M'),
      array(          -104857600, 2, 3,  '-100M'),
      array(         -1073741824, 2, 3,    '-1G'),
      array(        -10737418240, 2, 3,   '-10G'),
      array(       -107374182400, 2, 3,  '-100G'),
      array(      -1099511627776, 2, 3,    '-1T'),
      array(   -1125899906842624, 2, 3,    '-1P'),
      array(-1152921504606846976, 2, 3,    '-1E'),

      // check base 1000
      array(                1000, 2, 3,    '1000'),
      array(             1000000, 2, 3,    '977k'),

      // round & sf
      array(           105000000, 4, 4,  '100.1M'),
      array(           102400000, 4, 4,  '97.66M'),
    );
  }

  /**
   * @dataProvider providerFormatNumber
   * @group numbers
   */
  public function testFormatNumber($value, $base, $round, $sf, $result)
  {
    $this->assertSame($result, format_number($value, $base, $round, $sf));
  }

  public function providerFormatNumber()
  {
    return array(
      // simple base 1000 tests; most testing is done against format_si
      array( 10240000, '1000', 2, 3,  '10.2M'), // string base
      array( 10240000,   1000, 2, 3,  '10.2M'), // int base
      array( 10240000,   1000, 4, 4, '10.24M'), // round and sf

      // simple base 1024 tests; most testing is done against format_bi
      array(102400000, '1024', 2, 3,  '97.7M'), // string base
      array(102400000,   1024, 2, 3,  '97.7M'), // int base
      array(102400000,   1024, 4, 4, '97.66M'), // round and sf
    );
  }

  /**
   * @dataProvider providerIsValidHostname
   * @group hostname
   */
  public function testIsValidHostname($value, $result, $result_fqdn)
  {
    $this->assertSame($result, is_valid_hostname($value)); // Allow not FQDN
    $this->assertSame($result_fqdn, is_valid_hostname($value, TRUE)); // FQDN
  }

  public function providerIsValidHostname()
  {
    return array(
      array('router1',          TRUE, FALSE),
      array('1router',          TRUE, FALSE),
      array('router-1',         TRUE, FALSE),
      array('router.1',         TRUE, FALSE),

      array('router1.a.com',    TRUE, TRUE),
      array('1router.a.com',    TRUE, TRUE),
      array('router-1.a.com',   TRUE, TRUE),
      array('router.1.a.com',   TRUE, TRUE),

      // Domains with underscores, see: http://stackoverflow.com/a/2183140
      array('router_1',         TRUE, FALSE),
      array('router_1.a.com',   TRUE, TRUE),
      array('_router1',         TRUE, FALSE),
      array('_sip._udp.apnic.net', TRUE, TRUE),

      array('-router1',        FALSE, FALSE),
      array('.router1',        FALSE, FALSE),

      array('router~1',        FALSE, FALSE),
      array('router/1',        FALSE, FALSE),
      array('router,1',        FALSE, FALSE),
      array('router;1',        FALSE, FALSE),
      array('router 1',        FALSE, FALSE),

      // Long hostnames
      array('aaa' . str_repeat('.bb.cc.com', 25),   TRUE, TRUE), // total 253
      array(str_repeat('a', 63) . '.com',           TRUE, TRUE), // per level 63
      array('aaaa' . str_repeat('.bb.cc.com', 25), FALSE, FALSE), // total 254
      array(str_repeat('a', 64) . '.com',          FALSE, FALSE), // per level 64

      // Test cases from http://stackoverflow.com/a/4694816
      array('a',                TRUE, FALSE),
      array('0',                TRUE, FALSE),
      array('a.b',              TRUE, FALSE),
      array('localhost',        TRUE, FALSE),
      array('google.com',       TRUE, TRUE),
      array('news.google.co.uk',       TRUE, TRUE),
      array('xn--fsqu00a.xn--0zwm56d', TRUE, TRUE),
      array('goo gle.com',     FALSE, FALSE),
      array('google..com',     FALSE, FALSE),
      array('google.com ',     FALSE, FALSE),
      array('google-.com',     FALSE, FALSE),
      array('.google.com',     FALSE, FALSE),
      array('<script',         FALSE, FALSE),
      array('alert(',          FALSE, FALSE),
      array('.',               FALSE, FALSE),
      array('..',              FALSE, FALSE),
      array(' ',               FALSE, FALSE),
      array('-',               FALSE, FALSE),
      array('',                FALSE, FALSE),
      array('__',              FALSE, FALSE),
    );
  }

    /**
    * @dataProvider providerGetTime
    * @group datetime
    */
    public function testGetTime($value, $result, $future = FALSE) {
        // prevent long time running errors
        if ($future) {
            $result = time() + $result;
        } else {
            $result = time() - $result;
        }

        $diff = abs((int)$result - get_time($value, $future));
        $this->assertLessThanOrEqual(5, $diff); // +- 5 sec
    }

    public function providerGetTime()
    {
        //$now = time();
        return [
          [ 'now',         0 ],
          [ 'fiveminute',  300 ],      //time() - (5 * 60);
          [ 'fourhour',    14400 ],    //time() - (4 * 60 * 60);
          [ 'sixhour',     21600 ],    //time() - (6 * 60 * 60);
          [ 'twelvehour',  43200 ],    //time() - (12 * 60 * 60);
          [ 'day',         86400 ],    //time() - (24 * 60 * 60);
          [ 'twoday',      172800 ],   //time() - (2 * 24 * 60 * 60);
          [ 'week',        604800 ],   //time() - (7 * 24 * 60 * 60);
          [ 'twoweek',     1209600 ],  //time() - (2 * 7 * 24 * 60 * 60);
          [ 'month',       2678400 ],  //time() - (31 * 24 * 60 * 60);
          [ 'twomonth',    5356800 ],  //time() - (2 * 31 * 24 * 60 * 60);
          [ 'threemonth',  8035200 ],  //time() - (3 * 31 * 24 * 60 * 60);
          [ 'sixmonth',    16070400 ], //time() - (6 * 31 * 24 * 60 * 60);
          [ 'year',        31536000 ], //time() - (365 * 24 * 60 * 60);
          [ 'twoyear',     63072000 ], //time() - (2 * 365 * 24 * 60 * 60);
          [ 'threeyear',   94608000 ], //time() - (3 * 365 * 24 * 60 * 60);
          // new formats
          [ '10years',     315360000 ],
          [ '10years',     315360000, TRUE ],
        ];
    }

    /**
    * @dataProvider providerFormatTimestamp
    * @group datetime
    */
    public function testFormatTimestamp($value, $result)
    {
        $GLOBALS['config']['timestamp_format'] = 'Y-m-d H:i:s'; // force fixed format
        //if ($value === 'now') { $result = date('Y-m-d H:i:s'); } // force same times
        $this->assertSame($result, format_timestamp($value));
    }

    public function providerFormatTimestamp()
    {
        return [
            [ 'Aug 30 2014',      '2014-08-30 00:00:00' ],
            [ '2012-04-18 14:25', '2012-04-18 14:25:00' ],
            [ 'now',              date('Y-m-d H:i:s') ],
            [ 'Star Wars',        'Star Wars' ],
        ];
    }

    /**
    * @dataProvider providerFormatUnixtime
    * @group datetime
    */
    public function testFormatUnixtime($value, $format, $result)
    {
        // override local timezone settings or these tests may fail
        date_default_timezone_set('UTC');
        $this->assertSame($result, format_unixtime($value, $format));
    }
    
    public function providerFormatUnixtime()
    {
        return [
            [ 1409397693,                NULL, '2014-08-30 11:21:33' ],
            [ 1409397693,        DATE_RFC2822, 'Sat, 30 Aug 2014 11:21:33 +0000' ],
            [ 1551607499,        DATE_RFC2822, 'Sun, 03 Mar 2019 10:04:59 +0000' ],
            [ 1551607499.3878,   DATE_RFC2822, 'Sun, 03 Mar 2019 10:04:59 +0000' ],
            [ 1551607499.3878,      'H:i:s.u', '10:04:59.387800' ],
            [ 1551607499,           'H:i:s.v', '10:04:59.000' ],
            [ 1551607499.3873,      'H:i:s.v', '10:04:59.387' ],             // just prevent round in php less 7.0
            [ 1698831064,            'jS F Y', '1st November 2023' ],        // 1698831064 -> 1st November 2023 (test on php 7.2 and phpunit 8)
            [ 1698831064,        'jS F Y s.v', '1st November 2023 04.000' ], // 1698831064 -> 1st November 2023 (test on php 7.2 and phpunit 8)
            [ 1551607499.387867,    'H:i:s.u', '10:04:59.387867' ],
            [ '1551607499.387867',  'H:i:s.u', '10:04:59.387867' ],
            [ '1551607499.3878679', 'H:i:s.u', '10:04:59.387868' ],
            // Wrong data
            [ '0',   'r',       '' ],
            [ '',    'H:i:s.u', '' ],
            [ FALSE, 'H:i:s.u', '' ],
        ];
    }

  /**
   * @dataProvider providerUnitStringToNumeric
   * @group numbers
   */
  public function testUnitStringToNumeric($value, $result)
  {
    $this->assertSame($result, unit_string_to_numeric($value));
  }

  public function providerUnitStringToNumeric()
  {
    $results = array(
      // String should stay string
      array('Sweet',                             'Sweet'),
      array('acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline', 'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline'),
      // Version string
      array('15.1R5.5',                       '15.1R5.5'),
      // Unknown unit
      array('15.1R',                             '15.1R'),

      array(array('1'),                       array('1')), // Array should stay array
      array(TRUE,                                   TRUE), // Boolean should stay boolean
      array(NULL,                                   NULL), // NULL should stay NULL
      array('5',                                     5.0), // Numeric string should become int
      array('5.3',                                   5.3), // Numeric string should become float
      array('12b',                                  12.0),
      array('12B',                                  12.0),
      array('16bit',                                16.0),
      array('666bps',                              666.0),
      array('24Byte',                               24.0),
      array('32 byte',                              32.0), // A single space also works
      array('48bytes',                              48.0),
      array('1500Bps',                            1500.0),
      array('1440kB',                        1440*1024.0),
      array('2000k',                         2000*1024.0),
      array('60 kByte',                        60*1024.0),
      array('20 kbyte',                        20*1024.0),
      array('200kbps',                        200*1000.0), // Communication is 1000-based
      array('5000kbit',                      5000*1000.0),
      array('64kb',                            64*1000.0),
      array('16kBps',                          16*1000.0),
      array('200kbps',                        200*1000.0),
      array('50M',                        50*1024*1024.0),
      array('26MB',                       26*1024*1024.0),
      array('12.5MB',                   12.5*1024*1024.0),
      array('512 MB',                    512*1024*1024.0),
      array('119.1 GB',           119.1*1024*1024*1024.0), // Hrm, inaccurate 127882651200
      array('42 MByte',                   42*1024*1024.0),
      array('1 Mbyte',                     1*1024*1024.0),
      array('15Mb',                       15*1000*1000.0),
      array('199MBps',                   199*1000*1000.0),
      array('500Mbit',                   500*1000*1000.0),
      array('1500Mbps',                 1500*1000*1000.0),
      array('10G',                   10*1024*1024*1024.0),
      array('0GB',                    0*1024*1024*1024.0),
      array('6GByte',                 6*1024*1024*1024.0),
      array('3Gbyte',                 3*1024*1024*1024.0),
      array('2Gb',                    2*1000*1000*1000.0),
      array('2.1Gb',                2.1*1000*1000*1000.0), // Test decimal support
      array('5GBps',                  5*1000*1000*1000.0),
      array('15Gbit',                15*1000*1000*1000.0),
      array('7Gbps',                  7*1000*1000*1000.0),
      array('2T',                2*1024*1024*1024*1024.0),
      array('3TB',               3*1024*1024*1024*1024.0),
      array('5TByte',            5*1024*1024*1024*1024.0),
      array('12Tbyte',          12*1024*1024*1024*1024.0),
      array('6Tb',               6*1000*1000*1000*1000.0),
      array('9 TBps',            9*1000*1000*1000*1000.0),
      array('3 Tbit',            3*1000*1000*1000*1000.0),
      array('3.5 Tbit',        3.5*1000*1000*1000*1000.0),
      array('5 Tbps',            5*1000*1000*1000*1000.0),
      // IEC prefixes
      array('1024 MiB',                 1024*1024*1024.0),
      array('1 ZiB', 1024*1024*1024*1024*1024*1024*1024.0),
    );
    return $results;
  }

  /**
   * @dataProvider providerRangeToList
   * @group numbers
   */
  public function testRangeToList($value, $separator, $sort, $result)
  {
    $arr = explode(',', $value);
    $this->assertSame($result, range_to_list($arr, $separator, $sort));
  }

  public function providerRangeToList()
  {
    return array(
      array(0, ',', TRUE, '0'),
      array('1,2,3,4,5,6,7,8,9,10', ',', TRUE, '1-10'),
      array('1,2,3,5,7,9,10,11,12,14,0,-1', ',', TRUE, '-1-3,5,7,9-12,14'),
      array('1,2,3,5,7,9,10,11,12,14,0,-1', ',', FALSE, '1-3,5,7,9-12,14,0,-1'),
      array('1,2,3,5,7,9,10,11,12,14,0,-1', ', ', TRUE, '-1-3, 5, 7, 9-12, 14'),
      array(',', ',', TRUE, ''),
      array('edwd', ',', TRUE, ''),
    );
  }

  /**
   * @dataProvider providerListToRange
   * @group numbers
   */
  public function testListToRange($result, $separator, $sort, $value)
  {
    //$arr = explode(',', $value);
    $this->assertSame($result, list_to_range($value, $separator, $sort));
  }

  public function providerListToRange()
  {
    return array(
      array([ 0 ], ',', TRUE, '0'),
      array([ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ], ',', TRUE, '1-10'),
      array([ -1, 0, 1, 2, 3, 5, 7, 9, 10, 11, 12, 14 ], ',', TRUE, '-1-3,5,7,9-12,14'),
      array([ 1, 2, 3, 5, 7, 9, 10, 11, 12, 14, 0, -1 ], ',', FALSE, '1-3,5,7,9-12,14,0,-1'),
      array([ -1, 0, 1, 2, 3, 5, 7, 9, 10, 11, 12, 14 ], ', ', TRUE, '-1-3, 5, 7, 9-12, 14'),
      //array(',', ',', TRUE, ''),
      //array('edwd', ',', TRUE, ''),
    );
  }

  /**
   * @dataProvider providerGetDeviceMibs
   * @group mibs
   */
  public function testGetDeviceMibs($device, $result)
  {
    $mibs = array_values(get_device_mibs($device, FALSE)); // Use array_values for reset keys
    $this->assertEquals($result, $mibs);
  }

  /**
   * @dataProvider providerGetDeviceMibs
   * @group mibs
   */
  public function testGetDeviceMibs2($device, $result)
  {
    $device['device_id'] = 13;

    // Empty sysORID MIBs
    $GLOBALS['cache']['entity_attribs']['device'][$device['device_id']]['sysORID'] = '[]';
    $mibs = array_values(get_device_mibs($device, TRUE)); // Use array_values for reset keys
    $this->assertEquals($result, $mibs);

    // Any sysORID MIBs
    $GLOBALS['cache']['entity_attribs']['device'][$device['device_id']]['sysORID'] = '["SOME-MIB", "SOME2-MIB"]';
    $mibs = array_values(get_device_mibs($device, TRUE)); // Use array_values for reset keys
    if ($device['os'] === 'test_dlinkfw' && isset($device['sysObjectID'])) {
      // model definition first
      $new_result[] = array_shift($result);
      $new_result[] = 'SOME-MIB';
      $new_result[] = 'SOME2-MIB';
      $result = array_merge($new_result, $result);
    } else {
      $result = array_merge([ 'SOME-MIB', 'SOME2-MIB' ], $result);
    }
    //$result[] = 'SOME-MIB';
    //$result[] = 'SOME2-MIB';
    $this->assertEquals($result, $mibs);
  }

  public function providerGetDeviceMibs()
  {
    return array(
      // OS with empty mibs (only default)
      array(
        array(
          'device_id' => 1,
          'os'        => 'test_generic'
        ),
        array(
          'UCD-SNMP-MIB',
          'HOST-RESOURCES-MIB',
          'LSI-MegaRAID-SAS-MIB',
          'EtherLike-MIB',
          'ENTITY-MIB',
          'ENTITY-SENSOR-MIB',
          'CISCO-ENTITY-VENDORTYPE-OID-MIB',
          //'HOST-RESOURCES-MIB',
          'Q-BRIDGE-MIB',
          'LLDP-MIB',
          'CISCO-CDP-MIB',
          'PW-STD-MIB',
          'DISMAN-PING-MIB',
          'BGP4-MIB',
        )
      ),

      // OS with group mibs
      array(
        array(
          'device_id' => 1,
          'os'        => 'test_ios'
        ),
        array(
          'CISCO-IETF-IP-MIB',
          'CISCO-ENTITY-SENSOR-MIB',
          'CISCO-VTP-MIB',
          'CISCO-ENVMON-MIB',
          'CISCO-ENTITY-QFP-MIB',
          'CISCO-IP-STAT-MIB',
          'CISCO-FIREWALL-MIB',
          'CISCO-ENHANCED-MEMPOOL-MIB',
          'CISCO-MEMORY-POOL-MIB',
          'CISCO-PROCESS-MIB',
          'EtherLike-MIB',
          'ENTITY-MIB',
          'ENTITY-SENSOR-MIB',
          'CISCO-ENTITY-VENDORTYPE-OID-MIB',
          'HOST-RESOURCES-MIB',
          'Q-BRIDGE-MIB',
          'LLDP-MIB',
          'CISCO-CDP-MIB',
          'PW-STD-MIB',
          'DISMAN-PING-MIB',
          'BGP4-MIB',
        )
      ),

      // OS with group and os mibs
      array(
        array(
          'device_id' => 1,
          'os'        => 'test_linux'
        ),
        array(
          'LM-SENSORS-MIB',
          'SUPERMICRO-HEALTH-MIB',
          'MIB-Dell-10892',
          'CPQHLTH-MIB',
          'CPQIDA-MIB',
          'UCD-SNMP-MIB',
          'HOST-RESOURCES-MIB',
          'LSI-MegaRAID-SAS-MIB',
          'EtherLike-MIB',
          'ENTITY-MIB',
          'ENTITY-SENSOR-MIB',
          'CISCO-ENTITY-VENDORTYPE-OID-MIB',
          //'HOST-RESOURCES-MIB',
          'Q-BRIDGE-MIB',
          'LLDP-MIB',
          'CISCO-CDP-MIB',
          'PW-STD-MIB',
          'DISMAN-PING-MIB',
          'BGP4-MIB',
        )
      ),
      // OS with in os blacklisted mibs
      array(
        array(
          'device_id' => 1,
          'os'        => 'test_junos'
        ),
        array(
          'JUNIPER-MIB',
          'JUNIPER-ALARM-MIB',
          'JUNIPER-DOM-MIB',
          'JUNIPER-SRX5000-SPU-MONITORING-MIB',
          'JUNIPER-VLAN-MIB',
          'JUNIPER-MAC-MIB',
          'EtherLike-MIB',
          //'ENTITY-MIB',
          //'ENTITY-SENSOR-MIB',
          'CISCO-ENTITY-VENDORTYPE-OID-MIB',
          'HOST-RESOURCES-MIB',
          'Q-BRIDGE-MIB',
          'LLDP-MIB',
          'CISCO-CDP-MIB',
          'PW-STD-MIB',
          'DISMAN-PING-MIB',
          'BGP4-MIB',
        )
      ),

      // OS with in os and group blacklisted mibs
      array(
        array(
          'device_id' => 1,
          'os'        => 'test_freebsd'
        ),
        array(
          'CISCO-IETF-IP-MIB',
          'CISCO-ENTITY-SENSOR-MIB',
          'EtherLike-MIB',
          'ENTITY-MIB',
          //'ENTITY-SENSOR-MIB',
          'CISCO-ENTITY-VENDORTYPE-OID-MIB',
          'HOST-RESOURCES-MIB',
          //'Q-BRIDGE-MIB',
          'LLDP-MIB',
          'CISCO-CDP-MIB',
          'PW-STD-MIB',
          'DISMAN-PING-MIB',
          'BGP4-MIB',
        )
      ),

      // OS with per-HW mibs
      array(
        array(
          'device_id' => 1,
          'os'        => 'test_dlinkfw'
        ),
        array(
          'JUST-TEST-MIB',
          'EtherLike-MIB',
          'ENTITY-MIB',
          'ENTITY-SENSOR-MIB',
          'CISCO-ENTITY-VENDORTYPE-OID-MIB',
          'HOST-RESOURCES-MIB',
          'Q-BRIDGE-MIB',
          'LLDP-MIB',
          'CISCO-CDP-MIB',
          'PW-STD-MIB',
          'DISMAN-PING-MIB',
          'BGP4-MIB',
        )
      ),
      // OS with per-HW mibs, but with correct sysObjectID
      array(
        array(
          'device_id' => 1,
          'os'        => 'test_dlinkfw',
          'sysObjectID' => '.1.3.6.1.4.1.171.20.1.2.1'
        ),
        array(
          'DFL800-MIB', // HW specific MIB
          'JUST-TEST-MIB',
          'EtherLike-MIB',
          'ENTITY-MIB',
          'ENTITY-SENSOR-MIB',
          'CISCO-ENTITY-VENDORTYPE-OID-MIB',
          'HOST-RESOURCES-MIB',
          'Q-BRIDGE-MIB',
          'LLDP-MIB',
          'CISCO-CDP-MIB',
          'PW-STD-MIB',
          'DISMAN-PING-MIB',
          'BGP4-MIB',
        )
      ),

    );
  }

  /**
   * @dataProvider providerGetDeviceMibsOrder
   * @group mibs
   */
  public function testGetDeviceMibsOrder($device, $order, $result)
  {
    $mibs = array_values(get_device_mibs($device, FALSE, $order)); // Use array_values for reset keys
    $this->assertEquals($result, $mibs);
  }

  public function providerGetDeviceMibsOrder()
  {
    $device = array(
      'device_id' => 1,
      'os'        => 'test_order',
      'sysObjectID' => '.1.3.6.1.4.1.171.20.1.2.1'
    );
    $default = array(
      'EtherLike-MIB',
      'ENTITY-MIB',
      'ENTITY-SENSOR-MIB',
      'CISCO-ENTITY-VENDORTYPE-OID-MIB',
      'HOST-RESOURCES-MIB',
      //'Q-BRIDGE-MIB', // Blacklisted in group 'test_black'
      'LLDP-MIB',
      'CISCO-CDP-MIB',
      'PW-STD-MIB',
      'DISMAN-PING-MIB',
      'BGP4-MIB',
    );
    $model = array(
      'DFL800-MIB',
    );
    $os    = array(
      'JUST-TEST-MIB',
    );
    $group = array(
      'CISCO-IETF-IP-MIB',
      'CISCO-ENTITY-SENSOR-MIB',
    );

    return array(
      // Empty (default order)
      array(
        $device,
        NULL,
        array_merge($model, $os, $group, $default),
      ),
      // Same but with default order passed or unknown data
      array(
        $device,
        array('model', 'os', 'group', 'default'),
        array_merge($model, $os, $group, $default),
      ),
      array(
        $device,
        'model,os,group,default',
        array_merge($model, $os, $group, $default),
      ),
      array(
        $device,
        'asdasd,asdasdsw,asdasda',
        array_merge($model, $os, $group, $default),
      ),
      // Order changed
      array(
        $device,
        'default', // Default first
        array_merge($default, $model, $os, $group),
      ),
      array(
        $device,
        array('group', 'os'), // group and os first
        array_merge($group, $os, $model, $default),
      ),
      array(
        $device,
        array('group', 'os', 'default', 'model'), // full changed order
        array_merge($group, $os, $default, $model),
      ),

    );
  }

  /**
   * @dataProvider providerStringToId
   * @group string
   */
  public function testStringToId($string, $result)
  {
    $this->assertSame($result, string_to_id($string));
  }

  public function providerStringToId()
  {
    $array = array();

    // Basic data types
    $array[] = array('ldap|apisarkov',      374080493);
    $array[] = array('ldap|aananieva',      987883996);
    $array[] = array('apisarkov',           3896301514);
    $array[] = array('aananieva',           3297852923);

    return $array;
  }

  /**
   * @dataProvider providerVarEncode
   * @group vars
   */
  public function testVarEncode($var, $method, $result)
  {
    ini_set('precision',           17);
    $this->assertSame($result, var_encode($var, $method));
    ini_set('precision',           14);
  }

  /**
   * @dataProvider providerVarEncode
   * @group vars
   */
  public function testVarDecode($result, $method, $string)
  {
    ini_set('precision',           17);
    //$this->assertSame($result, var_decode($string, $method));
    $this->assertEquals($result, var_decode($string, $method));
    ini_set('precision',           14);
  }

  /**
   * @dataProvider providerVarDecodeWrong
   * @group vars
   */
  public function testVarDecodeWrong($result, $method, $string)
  {
    $this->assertSame($result, var_decode($string, $method));
  }

  public function providerVarEncode()
  {
    $json_utf8 = defined('JSON_UNESCAPED_UNICODE');
    if (!$json_utf8) echo("WARNING. In var_encode() with method 'json' converts UTF-8 characters to Unicode escape sequences!\n\n");

    $array = array();

    // Basic data types
    $array[] = array(NULL,    'json',      'bnVsbA==');
    $array[] = array(array(), 'json',      'W10=');
    $array[] = array(TRUE,    'json',      'dHJ1ZQ==');
    $array[] = array(FALSE,   'json',      'ZmFsc2U=');
    $array[] = array('true',  'json',      'InRydWUi');
    $array[] = array('false', 'json',      'ImZhbHNlIg==');
    $array[] = array('0',     'json',      'IjAi');
    $array[] = array('',      'json',      'IiI=');
    $array[] = array(0,       'json',      'MA==');
    $array[] = array(1,       'json',      'MQ==');
    $array[] = array(81,      'json',      'ODE=');
    $array[] = array(9.8172397123457E-14,      'json', 'OS44MTcyMzk3MTIzNDU3ZS0xNA==');
    $array[] = array(NULL,    'serialize', 'Tjs=');
    $array[] = array(array(), 'serialize', 'YTowOnt9');
    $array[] = array(TRUE,    'serialize', 'YjoxOw==');
    $array[] = array(FALSE,   'serialize', 'YjowOw==');
    $array[] = array('true',  'serialize', 'czo0OiJ0cnVlIjs=');
    $array[] = array('false', 'serialize', 'czo1OiJmYWxzZSI7');
    $array[] = array('0',     'serialize', 'czoxOiIwIjs=');
    $array[] = array('',      'serialize', 'czowOiIiOw==');
    $array[] = array(0,       'serialize', 'aTowOw==');
    $array[] = array(1,       'serialize', 'aToxOw==');
    $array[] = array(81,      'serialize', 'aTo4MTs=');
    $array[] = array(9.8172397123457E-14, 'serialize', 'ZDo5LjgxNzIzOTcxMjM0NTdFLTE0Ow==');

    // Basic string encode
    $array[] = array('Sgt. Pepper\'s Lonely Hearts Club Band', 'json',      'IlNndC4gUGVwcGVyJ3MgTG9uZWx5IEhlYXJ0cyBDbHViIEJhbmQi');
    $array[] = array('Sgt. Pepper\'s Lonely Hearts Club Band', 'serialize', 'czozNzoiU2d0LiBQZXBwZXIncyBMb25lbHkgSGVhcnRzIENsdWIgQmFuZCI7');

    // Random string encode
    $array[] = array('8,G(?\'A>_7p`>qbr!;9``1ssc$WZpc\'>KxD*?Py3', 'json',      'IjgsRyg/J0E+XzdwYD5xYnIhOzlgYDFzc2MkV1pwYyc+S3hEKj9QeTMi');
    $array[] = array('8,G(?\'A>_7p`>qbr!;9``1ssc$WZpc\'>KxD*?Py3', 'serialize', 'czo0MDoiOCxHKD8nQT5fN3BgPnFiciE7OWBgMXNzYyRXWnBjJz5LeEQqP1B5MyI7');

    // UTF-8 string encode
    if ($json_utf8)
    {
      $array[] = array('Оркестр Клуба Одиноких Сердец Сержанта Пеппера', 'json',
                       'ItCe0YDQutC10YHRgtGAINCa0LvRg9Cx0LAg0J7QtNC40L3QvtC60LjRhSDQodC10YDQtNC10YYg0KHQtdGA0LbQsNC90YLQsCDQn9C10L/Qv9C10YDQsCI=');
    } else {
      // Wrong pre-5.4 Unicode escaped
      $array[] = array('Оркестр Клуба Одиноких Сердец Сержанта Пеппера', 'json',
                       'Ilx1MDQxZVx1MDQ0MFx1MDQzYVx1MDQzNVx1MDQ0MVx1MDQ0Mlx1MDQ0MCBcdTA0MWFcdTA0M2JcdTA0NDNcdTA0MzFcdTA0MzAgXHUwNDFlXHUwNDM0XHUwNDM4XHUwNDNkXHUwNDNlXHUwNDNhXHUwNDM4XHUwNDQ1IFx1MDQyMVx1MDQzNVx1MDQ0MFx1MDQzNFx1MDQzNVx1MDQ0NiBcdTA0MjFcdTA0MzVcdTA0NDBcdTA0MzZcdTA0MzBcdTA0M2RcdTA0NDJcdTA0MzAgXHUwNDFmXHUwNDM1XHUwNDNmXHUwNDNmXHUwNDM1XHUwNDQwXHUwNDMwIg==');
    }
    $array[] = array('Оркестр Клуба Одиноких Сердец Сержанта Пеппера', 'serialize', 'czo4Nzoi0J7RgNC60LXRgdGC0YAg0JrQu9GD0LHQsCDQntC00LjQvdC+0LrQuNGFINCh0LXRgNC00LXRhiDQodC10YDQttCw0L3RgtCwINCf0LXQv9C/0LXRgNCwIjs=');

    // Basic array
    //$array[] = array(array(3.14, '0', 'Yellow Submarine', TRUE), 'json',      'WzMuMTQsIjAiLCJZZWxsb3cgU3VibWFyaW5lIix0cnVlXQ==');
    $array[] = array(array(3.14, '0', 'Yellow Submarine', TRUE), 'json',      'WzMuMTQwMDAwMDAwMDAwMDAwMSwiMCIsIlllbGxvdyBTdWJtYXJpbmUiLHRydWVd');
    $array[] = array(array(3.14, '0', 'Yellow Submarine', TRUE), 'serialize', 'YTo0OntpOjA7ZDozLjE0MDAwMDAwMDAwMDAwMDE7aToxO3M6MToiMCI7aToyO3M6MTY6IlllbGxvdyBTdWJtYXJpbmUiO2k6MztiOjE7fQ==');

    // Complex array
    $array[] = array(
      array(
        0       => array('tempHumidSensorID' => 'A1', 'tempHumidSensorName' => 'Temp_Humid_Sensor_A1'),
        '1.2'   => array('tempHumidSensorID' => 'A2', 'tempHumidSensorName' => 'Temp_Humid_Sensor_A2'),
        'Frodo' => 'Baggins',
        'binary' => TRUE,
        //'number' => 98172397.1234567890, // Ohoho, json rounds this number to 98172397.123457
        'number' => 98172397.123456,
        NULL
      ),
      //'json',      'eyIwIjp7InRlbXBIdW1pZFNlbnNvcklEIjoiQTEiLCJ0ZW1wSHVtaWRTZW5zb3JOYW1lIjoiVGVtcF9IdW1pZF9TZW5zb3JfQTEifSwiMS4yIjp7InRlbXBIdW1pZFNlbnNvcklEIjoiQTIiLCJ0ZW1wSHVtaWRTZW5zb3JOYW1lIjoiVGVtcF9IdW1pZF9TZW5zb3JfQTIifSwiRnJvZG8iOiJCYWdnaW5zIiwiYmluYXJ5Ijp0cnVlLCJudW1iZXIiOjk4MTcyMzk3LjEyMzQ1NiwiMSI6bnVsbH0='
      'json',      'eyIwIjp7InRlbXBIdW1pZFNlbnNvcklEIjoiQTEiLCJ0ZW1wSHVtaWRTZW5zb3JOYW1lIjoiVGVtcF9IdW1pZF9TZW5zb3JfQTEifSwiMS4yIjp7InRlbXBIdW1pZFNlbnNvcklEIjoiQTIiLCJ0ZW1wSHVtaWRTZW5zb3JOYW1lIjoiVGVtcF9IdW1pZF9TZW5zb3JfQTIifSwiRnJvZG8iOiJCYWdnaW5zIiwiYmluYXJ5Ijp0cnVlLCJudW1iZXIiOjk4MTcyMzk3LjEyMzQ1NjAwMSwiMSI6bnVsbH0='
    );
    $array[] = array(
      array(
        0       => array('tempHumidSensorID' => 'A1', 'tempHumidSensorName' => 'Temp_Humid_Sensor_A1'),
        '1.2'   => array('tempHumidSensorID' => 'A2', 'tempHumidSensorName' => 'Temp_Humid_Sensor_A2'),
        'Frodo' => 'Baggins',
        'binary' => TRUE,
        'number' => 98172397.1234567890,
        NULL
      ),
      'serialize', 'YTo2OntpOjA7YToyOntzOjE3OiJ0ZW1wSHVtaWRTZW5zb3JJRCI7czoyOiJBMSI7czoxOToidGVtcEh1bWlkU2Vuc29yTmFtZSI7czoyMDoiVGVtcF9IdW1pZF9TZW5zb3JfQTEiO31zOjM6IjEuMiI7YToyOntzOjE3OiJ0ZW1wSHVtaWRTZW5zb3JJRCI7czoyOiJBMiI7czoxOToidGVtcEh1bWlkU2Vuc29yTmFtZSI7czoyMDoiVGVtcF9IdW1pZF9TZW5zb3JfQTIiO31zOjU6IkZyb2RvIjtzOjc6IkJhZ2dpbnMiO3M6NjoiYmluYXJ5IjtiOjE7czo2OiJudW1iZXIiO2Q6OTgxNzIzOTcuMTIzNDU2NzkxO2k6MTtOO30='
    );

    return $array;
  }

  public function providerVarDecodeWrong()
  {
    $tests = array(
      0, 1, 9.8E-14, TRUE, FALSE, NULL, '', array(), array('Yellow Submarine'),
      'WWVsbG93IFN1Ym1hcmluZQ==',          // base64
      '["Yellow Submarine"]',              // json
      'a:1:{i:0;s:16:"Yellow Submarine";}', // serialize
      'ODE',
    );
    foreach (array('json', 'serialize') as $method)
    {
      foreach ($tests as $test)
      {
        $array[] = array($test, $method, $test);
      }
    }
    return $array;
  }

  /**
   * @dataProvider providerGetVarCsv
   * @group vars
   */
  public function testGetVarCsv($var, $encoded, $result)
  {
    $this->assertSame($result, get_var_csv($var, $encoded));
  }

  public function providerGetVarCsv()
  {
    $var_encoded        = 'WzMuMTQwMDAwMDAwMDAwMDAwMSwiMCIsIlllbGxvdyBTdWJtYXJpbmUiLHRydWVd';
    $var_encoded_quoted = '"WzMuMTQwMDAwMDAwMDAwMDAwMSwiMCIsIlllbGxvdyBTdWJtYXJpbmUiLHRydWVd"';
    $var_string         = 'aksdlasmd';
    $var_string_list    = 'asdjknd,aksjdnasd,adadda';
    $var_string_csv     = '"asdjknd","aksjdnasd","adadda"';
    $var_array          = [ 'asdasd', 'asdasd' ];

    $array = [
      // No decode
      [ $var_encoded,         FALSE, $var_encoded ],
      [ $var_encoded_quoted,  FALSE, $var_encoded ],
      [ $var_string,          FALSE, $var_string ],
      [ $var_string_list,     FALSE, [ 'asdjknd', 'aksjdnasd', 'adadda' ] ],
      [ $var_string_csv,      FALSE, [ 'asdjknd', 'aksjdnasd', 'adadda' ] ],
      [ $var_array,           FALSE, $var_array ],
      // Decode
      [ $var_encoded,          TRUE, [ 3.14, '0', 'Yellow Submarine', TRUE ] ],
      [ $var_encoded_quoted,   TRUE, $var_encoded ],
      [ $var_string,           TRUE, $var_string ],
      [ $var_string_list,      TRUE, [ 'asdjknd', 'aksjdnasd', 'adadda' ] ],
      [ $var_string_csv,       TRUE, [ 'asdjknd', 'aksjdnasd', 'adadda' ] ],
      [ $var_array,            TRUE, $var_array ],
    ];

    return $array;
  }

  /**
   * @dataProvider providerArrayGetNested
   * @group arrays
   */
  public function testArrayGetNested($result, $array, $nest) {
    $this->assertSame($result, array_get_nested($array, $nest));
  }

  public function providerArrayGetNested() {
    $arr1 = [
              [
                'device_id' => 1,
                'os'        => 'test_dlinkfw'
              ],
              [
                'JUST-TEST-MIB',
                'EtherLike-MIB',
                'ENTITY-MIB',
                'PW-STD-MIB',
                'DISMAN-PING-MIB',
                'BGP4-MIB',
              ],
    ];
    $arr2 = [
      'lev1-1' => [ 'lev2-1-1' => [], [ 'a', 'b', 'c' ] ],
      'lev1-2' => [ 'lev2-2-1' => NULL, 'lev2-2-2' => 'asd', ],
    ];
    //print_vars($arr1);

    return [
      [ 'test_dlinkfw',    $arr1, '0->os' ],

      [ [],                $arr2, 'lev1-1->lev2-1-1' ],
      [ [ 'a', 'b', 'c' ], $arr2, 'lev1-1->0' ],
      [ 'c',               $arr2, 'lev1-1->0->2' ],
      [ NULL,              $arr2, 'lev1-1->0->2->unknown' ], // not exist key
      [ 'asd',             $arr2, 'lev1-2->lev2-2-2' ],
      [ NULL,              $arr2, 'lev1-2->lev2-2-1' ],

      // Not array!
      [ NULL, "string", 'lev1-1->lev2-1-1' ],
      [ NULL,      243, 'lev1-1->lev2-1-1' ],
      [ NULL,     TRUE, 'lev1-1->lev2-1-1' ],
    ];
  }
  
  /**
   * @dataProvider providerIsArrayAssoc
   * @group arrays
   */
  public function testIsArrayAssoc($result, $array)
  {
    $this->assertSame($result, is_array_assoc($array));
  }

  public function providerIsArrayAssoc()
  {
    return array(
      array(FALSE, array('a', 'b', 'c')),
      array(FALSE, array(array(), 1, 'c')),
      array(FALSE, array("0" => 'a', "1" => array(), "2" => 2)),
      array(FALSE, array("0" => 'a', "1" => 'b', "2" => 'c')),

      array(TRUE, array("1" => 'a', "0" => 'b', "2" => 'c')),
      array(TRUE, array("1" => 'a', "0" => 'b', "2" => 'c')),
      array(TRUE, array("a" => 'a', "b" => 'b', "c" => 'c')),

      // Not array!
      array(FALSE, "string"),
    );
  }

  /**
   * @dataProvider providerIsArraySeq
   * @group arrays
   */
  public function testIsArraySeq($result, $array)
  {
    $this->assertSame($result, is_array_list($array));
  }

  public function providerIsArraySeq()
  {
    return array(
      array(TRUE, array('a', 'b', 'c')),
      array(TRUE, array(array(), 1, 'c')),
      array(TRUE, array("0" => 'a', "1" => array(), "2" => 2)),
      array(TRUE, array("0" => 'a', "1" => 'b', "2" => 'c')),

      array(FALSE, array("1" => 'a', "0" => 'b', "2" => 'c')),
      array(FALSE, array("1" => 'a', "0" => 'b', "2" => 'c')),
      array(FALSE, array("a" => 'a', "b" => 'b', "c" => 'c')),

      // Not array!
      array(FALSE, "string"),
    );
  }

  /**
   * @dataProvider providerArrayPushAfter
   * @group arrays
   */
  public function testArrayPushAfter($array, $key, $new, $result)
  {
    $this->assertSame($result, array_push_after($array, $key, $new));
  }

  public function providerArrayPushAfter()
  {
    return [
      array([ 'a', 'b', 'c' ], 0, 'new', [ 'a', 'new', 'b', 'c' ]),
      array([ 'a', 'b', 'c' ], 1, [ 'one', 'two' ], [ 'a', 'b', 'one', 'two', 'c' ]),
      array([ 'a', 'b', 'c' ], FALSE, [ 'one', 'two' ], [ 'a', 'b', 'c', 'one', 'two' ]), // same as array_push()

      array([ 1 => 'a', 3 => 'b', 8 => 'c' ], 0, 'new', [ 0 => 'a', 1 => 'b', 2 => 'c', 3 => 'new' ]),
      array([ 1 => 'a', 3 => 'b', 8 => 'c' ], 1, [ 4 => 'one', 6 => 'two' ], [ 0 => 'a', 1 => 'one', 2 => 'two', 3 => 'b', 4 => 'c' ]),
      array([ 1 => 'a', 3 => 'b', 8 => 'c' ], FALSE, [ 'one', 'two' ], [ 'a', 'b', 'c', 'one', 'two' ]), // same as array_push()

      array([ 'k1' => 'a', 'k2' => 'b', 'k3' => 'c' ], 0, 'new', [ 'k1' => 'a', 'k2' => 'b', 'k3' => 'c', 0 => 'new' ]),
      array([ 'k1' => 'a', 'k2' => 'b', 'k3' => 'c' ], 'k1', [ 4 => 'one', 6 => 'two' ], [ 'k1' => 'a', 0 => 'one', 1 => 'two', 'k2' => 'b', 'k3' => 'c' ]),
      array([ 'k1' => 'a', 'k2' => 'b', 'k3' => 'c' ], FALSE, [ 'one', 'two' ], [ 'k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'one', 'two' ]), // same as array_push()
    ];
  }

  /**
   * @dataProvider providerSafeJsonDecode
   * @group json
   */
  public function testSafeJsonDecode($string, $result) {
    $this->assertSame($result, safe_json_decode($string));
  }

    /**
     * @dataProvider providerSafeJsonDecode
     * @group json
     */
    public function testJsonValidate($string, $result, $valid = TRUE) {
        //$this->assertSame(TRUE, json_validate($string));
        if ($valid) {
            $this->assertTrue(json_validate($string));
        } else {
            $this->assertFalse(json_validate($string));
        }
    }

  public function providerSafeJsonDecode() {
    $array = [];
    $array[] = [
      '["QWERTYUIOPASDFGHJKLZXCVBNM"]',
      [ 'QWERTYUIOPASDFGHJKLZXCVBNM' ]
    ];
    $array[] = [
      '["ËЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ"]',
      [ 'ËЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ' ]
    ];
    $array[] = [
      '{"ALERT_STATE":"RECOVER","ALERT_EMOJI":"&#x2705;","ALERT_EMOJI_NAME":"white_check_mark","ALERT_STATUS":"1","ALERT_SEVERITY":"Critical","ALERT_COLOR":"","ALERT_URL":"https://observium/device/device=26/tab=alert/alert_entry=1/","ALERT_UNIXTIME":1624945593,"ALERT_TIMESTAMP":"2021-06-29 10:46:33 +05:00","ALERT_TIMESTAMP_RFC2822":"Tue, 29 Jun 2021 10:46:33 +0500","ALERT_TIMESTAMP_RFC3339":"2021-06-29T10:46:33+05:00","ALERT_ID":"1","ALERT_MESSAGE":"Внимание! Отключение отключение устройства.","CONDITIONS":"","METRICS":"device_status = 1","DURATION":"4m 49s (29-06-2021 10:41:44)","ENTITY_URL":"https://observium/device/device=26/","ENTITY_LINK":"<a href=\"https://observium/device/device=26/\" class=\"entity-popup \" data-eid=\"26\" data-etype=\"device\">hostname1</a>","ENTITY_NAME":"hostname1","ENTITY_ID":"26","ENTITY_TYPE":"device","ENTITY_DESCRIPTION":null,"DEVICE_HOSTNAME":"hostname1","DEVICE_SYSNAME":"hostname","DEVICE_DESCRIPTION":null,"DEVICE_ID":"26","DEVICE_URL":"https://observium/device/device=26/","DEVICE_LINK":"<a href=\"https://observium/device/device=26/\" class=\"entity-popup \" data-eid=\"26\" data-etype=\"device\">hostname1</a>","DEVICE_HARDWARE":"X440G2-48t-10G4","DEVICE_OS":"Extreme XOS 21.1.3.7 (ssh)","DEVICE_TYPE":"network","DEVICE_LOCATION":"Kirovsky District, Yekaterinburg, Sverdlovsk Region, Russia, 620049","DEVICE_UPTIME":"10 days, 1h 4m 59s","DEVICE_REBOOTED":"19-06-2021 09:35:42","TITLE":"RECOVER: [hostname1] [device] Внимание! Отключение отключение устройства."}',
      [
        'ALERT_STATE' => "RECOVER",
        'ALERT_EMOJI' => "&#x2705;",
        'ALERT_EMOJI_NAME' => "white_check_mark",
        'ALERT_STATUS' => "1",
        'ALERT_SEVERITY' => "Critical",
        'ALERT_COLOR' => "",
        'ALERT_URL' => "https://observium/device/device=26/tab=alert/alert_entry=1/",
        'ALERT_UNIXTIME' => 1624945593,
        'ALERT_TIMESTAMP' => "2021-06-29 10:46:33 +05:00",
        'ALERT_TIMESTAMP_RFC2822' => "Tue, 29 Jun 2021 10:46:33 +0500",
        'ALERT_TIMESTAMP_RFC3339' => "2021-06-29T10:46:33+05:00",
        'ALERT_ID' => "1",
        'ALERT_MESSAGE' => "Внимание! Отключение отключение устройства.",
        'CONDITIONS' => "",
        'METRICS' => "device_status = 1",
        'DURATION' => "4m 49s (29-06-2021 10:41:44)",
        'ENTITY_URL' => "https://observium/device/device=26/",
        'ENTITY_LINK' => "<a href=\"https://observium/device/device=26/\" class=\"entity-popup \" data-eid=\"26\" data-etype=\"device\">hostname1</a>",
        'ENTITY_NAME' => "hostname1",
        'ENTITY_ID' => "26",
        'ENTITY_TYPE' => "device",
        'ENTITY_DESCRIPTION' => NULL,
        'DEVICE_HOSTNAME' => "hostname1",
        'DEVICE_SYSNAME' => "hostname",
        'DEVICE_DESCRIPTION' => NULL,
        'DEVICE_ID' => "26",
        'DEVICE_URL' => "https://observium/device/device=26/",
        'DEVICE_LINK' => "<a href=\"https://observium/device/device=26/\" class=\"entity-popup \" data-eid=\"26\" data-etype=\"device\">hostname1</a>",
        'DEVICE_HARDWARE' => "X440G2-48t-10G4",
        'DEVICE_OS' => "Extreme XOS 21.1.3.7 (ssh)",
        'DEVICE_TYPE' => "network",
        'DEVICE_LOCATION' => "Kirovsky District, Yekaterinburg, Sverdlovsk Region, Russia, 620049",
        'DEVICE_UPTIME' => "10 days, 1h 4m 59s",
        'DEVICE_REBOOTED' => "19-06-2021 09:35:42",
        'TITLE' => "RECOVER: [hostname1] [device] Внимание! Отключение отключение устройства."
      ]
    ];
    // smart (incorrect) quotes
    $array[] = [
      '[«ËЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ»]',
      [ 'ËЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ' ],
      FALSE
    ];
    $array[] = [
      '{“method”:”sms.send_togroup”, “params”:{“access_token”:”0005gOjCOlMH8F2BP8″,”groupname”:”admins”,”message”:”mymessage”,”highpriority”:”1″}}',
      [ 'method' => 'sms.send_togroup', 'params' => [ 'access_token' => '0005gOjCOlMH8F2BP8', 'groupname' => 'admins', 'message' => 'mymessage', 'highpriority' => '1' ] ],
      FALSE
    ];
    $array[] = [
      "[ \"/^ONU.+ Operation-&gt;Deactivated/\n/^ONU.+ Operation-&gt;Deactivated/\" ]",
      [ '/^ONU.+ Operation-&gt;Deactivated//^ONU.+ Operation-&gt;Deactivated/' ],
      FALSE
    ];
    // ctrl chars
    $array[] = [
      '{"url":"https://<apiurl>","json":"{'."\r\n".'    \"ALERT_ID\": \"%ALERT_ID%\",'."\r\n".'    \"ALERT_MESSAGE\": \"%ALERT_MESSAGE%\",'."\r\n".'    \"ALERT_SEVERITY\": \"%ALERT_SEVERITY%\",'."\r\n".'    \"ALERT_STATE\": \"%ALERT_STATE%\",'."\r\n".'    \"ALERT_STATUS\": \"%ALERT_STATUS%\",'."\r\n".'    \"ALERT_TIMESTAMP\": \"%ALERT_TIMESTAMP%\",'."\r\n".'    \"CONDITIONS\": \"%CONDITIONS%\",'."\r\n".'    \"DEVICE_HOSTNAME\": \"%DEVICE_HOSTNAME%\",'."\r\n".'    \"DEVICE_SYSNAME\": \"%DEVICE_SYSNAME%\",'."\r\n".'    \"DURATION\": \"%DURATION%\",'."\r\n".'    \"ENTITY_LINK\": \"%ENTITY_LINK%\",'."\r\n".'    \"METRICS\": \"%METRICS%\",'."\r\n".'    \"TITLE\": \"%TITLE%\"'."\r\n".'}"}',
      [ 'url' => 'https://<apiurl>', 'json' => '{    "ALERT_ID": "%ALERT_ID%",    "ALERT_MESSAGE": "%ALERT_MESSAGE%",    "ALERT_SEVERITY": "%ALERT_SEVERITY%",    "ALERT_STATE": "%ALERT_STATE%",    "ALERT_STATUS": "%ALERT_STATUS%",    "ALERT_TIMESTAMP": "%ALERT_TIMESTAMP%",    "CONDITIONS": "%CONDITIONS%",    "DEVICE_HOSTNAME": "%DEVICE_HOSTNAME%",    "DEVICE_SYSNAME": "%DEVICE_SYSNAME%",    "DURATION": "%DURATION%",    "ENTITY_LINK": "%ENTITY_LINK%",    "METRICS": "%METRICS%",    "TITLE": "%TITLE%"}' ],
      FALSE
    ];
    $array[] = [
      "[\r\n".' "ËЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ" '."\r\n]",
      [ 'ËЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ' ]
    ];
    return $array;
  }

  /**
   * @dataProvider providerStrContains
   * @group        string
   *
   * @param      $result
   * @param      $case
   * @param      $string
   * @param      $needle
   * @param bool $encoding
   */
  public function testStrContains($result, $case, $string, $needle, $encoding = FALSE)
  {
    if ($case)
    {
      $this->assertSame($result, str_icontains_array($string, $needle, $encoding));
    } else {
      $this->assertSame($result, str_contains_array($string, $needle, $encoding));
    }
  }

  public function providerStrContains()
  {
    $test_string1 = 'Observium is a low-maintenance auto-discovering network monitoring platform.';
    $test_string2 = 'Съешь ещё этих мягких французских булок, да выпей же чаю.'; // UTF-8
    return array(
      // case-sensitive
      array(FALSE, FALSE, $test_string1, ''),
      array( TRUE, FALSE, $test_string1, $test_string1),
      array(FALSE, FALSE, $test_string1, $test_string2),
      array( TRUE, FALSE, $test_string1, 'Observium is a '),
      array(FALSE, FALSE, $test_string1, 'ObserVium is A '),
      array(FALSE, FALSE, $test_string1, 'observium is a '),
      array( TRUE, FALSE, $test_string1, 'bservium'),
      array(FALSE, FALSE, $test_string2, ''),
      array( TRUE, FALSE, $test_string2, $test_string2),
      array(FALSE, FALSE, $test_string2, $test_string1),
      array( TRUE, FALSE, $test_string2, 'Съешь ещё этих '),
      array(FALSE, FALSE, $test_string2, 'СъЕшь ещё этиХ '),
      array(FALSE, FALSE, $test_string2, 'Съешь еще этих '),
      array( TRUE, FALSE, $test_string2, 'ъешь'),
      // case-insensitive
      array(FALSE,  TRUE, $test_string1, ''),
      array( TRUE,  TRUE, $test_string1, $test_string1),
      array(FALSE,  TRUE, $test_string1, $test_string2),
      array( TRUE,  TRUE, $test_string1, 'Observium is a '),
      array( TRUE,  TRUE, $test_string1, 'ObserVium is A '),
      array( TRUE,  TRUE, $test_string1, 'observium is a '),
      array( TRUE,  TRUE, $test_string1, 'bservium'),
      array(FALSE,  TRUE, $test_string2, ''),
      array( TRUE,  TRUE, $test_string2, $test_string2),
      array(FALSE,  TRUE, $test_string2, $test_string1),
      array( TRUE,  TRUE, $test_string2, 'Съешь ещё этих '),
      array( TRUE,  TRUE, $test_string2, 'СъЕшь ещё этиХ ', 'UTF-8'), // Require enable "slow" multibyte code in str_startwith()
      array(FALSE,  TRUE, $test_string2, 'Съешь еще этих '),
      array( TRUE,  TRUE, $test_string2, 'ъешь'),
      // other
      array( TRUE, FALSE,            '', ''),
      array(FALSE, FALSE,          'abc', 'abcd'),
      array( TRUE, FALSE, '.1.2.3.1.2.3', '.'),
      array( TRUE, FALSE, '.1.2.3.1.2.3', '.1.2.3'),
      array( TRUE, FALSE, '.1.2.3.1.2.3', '1.2.3'),
      array( TRUE, FALSE,  '1.2.3.1.2.3', 1),
      array(FALSE, FALSE,  '1.2.3.1.2.3', 7),
      array( TRUE, FALSE,  '1.2.3.1.2.3', 3.1), // Hrm :)
      // Arrays (recursive)
      array(FALSE, FALSE,            '', []),
      array( TRUE, FALSE, $test_string1, array(11,   'Observium is a ')),
      array(FALSE, FALSE, $test_string1, array(11,   'ObserviuM is a ')),
      array(FALSE, FALSE, $test_string1, array(11,   'observium is a ')),
      array(FALSE,  TRUE,            '', []),
      array( TRUE,  TRUE, $test_string1, array(11,   'Observium is a ')),
      array( TRUE,  TRUE, $test_string1, array(11,   'ObserviuM is a ')),
      array( TRUE,  TRUE, $test_string1, array(11,   'bservium is a ')),
      array( TRUE,  TRUE, '8 times',     array('qwerty', 'times')),
      // Not strings
      array(FALSE,  TRUE, $test_string1, array('fs', array('Observium is a '))),
      array(FALSE, FALSE, $test_string1, NULL),
      array(FALSE, FALSE, $test_string1, array()),
    );
  }

  /**
   * @dataProvider providerStrStarts
   * @group string
   */
  public function testStrStarts($result, $incase, $string, $needle, $encoding = FALSE)
  {
    if ($incase)
    {
      $this->assertSame($result, str_istarts($string, $needle, $encoding));
    } else {
      $this->assertSame($result, str_starts($string, $needle, $encoding));
    }
  }

  public function providerStrStarts()
  {
    $test_string1 = 'Observium is a low-maintenance auto-discovering network monitoring platform.';
    $test_string2 = 'Съешь ещё этих мягких французских булок, да выпей же чаю.'; // UTF-8
    return array(
      // case-sensitive
      array(FALSE, FALSE, $test_string1, ''),
      array( TRUE, FALSE, $test_string1, $test_string1),
      array(FALSE, FALSE, $test_string1, $test_string2),
      array( TRUE, FALSE, $test_string1, 'Observium is a '),
      array(FALSE, FALSE, $test_string1, 'ObserVium is A '),
      array(FALSE, FALSE, $test_string1, 'observium is a '),
      array(FALSE, FALSE, $test_string1, 'bservium'),
      array(FALSE, FALSE, $test_string2, ''),
      array( TRUE, FALSE, $test_string2, $test_string2),
      array(FALSE, FALSE, $test_string2, $test_string1),
      array( TRUE, FALSE, $test_string2, 'Съешь ещё этих '),
      array(FALSE, FALSE, $test_string2, 'СъЕшь ещё этиХ '),
      array(FALSE, FALSE, $test_string2, 'Съешь еще этих '),
      array(FALSE, FALSE, $test_string2, 'ъешь'),
      // case-insensitive
      array(FALSE,  TRUE, $test_string1, ''),
      array( TRUE,  TRUE, $test_string1, $test_string1),
      array(FALSE,  TRUE, $test_string1, $test_string2),
      array( TRUE,  TRUE, $test_string1, 'Observium is a '),
      array( TRUE,  TRUE, $test_string1, 'ObserVium is A '),
      array( TRUE,  TRUE, $test_string1, 'observium is a '),
      array(FALSE,  TRUE, $test_string1, 'bservium'),
      array(FALSE,  TRUE, $test_string2, ''),
      array( TRUE,  TRUE, $test_string2, $test_string2),
      array(FALSE,  TRUE, $test_string2, $test_string1),
      array( TRUE,  TRUE, $test_string2, 'Съешь ещё этих '),
      array( TRUE,  TRUE, $test_string2, 'СъЕшь ещё этиХ ', 'UTF-8'), // Require enable "slow" multibyte code in str_startwith()
      array(FALSE,  TRUE, $test_string2, 'Съешь еще этих '),
      array(FALSE,  TRUE, $test_string2, 'ъешь'),
      // other
      array( TRUE, FALSE,            '', ''),
      array(FALSE, FALSE,          'abc', 'abcd'),
      array( TRUE, FALSE, '.1.2.3.1.2.3', '.'),
      array( TRUE, FALSE, '.1.2.3.1.2.3', '.1.2.3'),
      array(FALSE, FALSE, '.1.2.3.1.2.3', '1.2.3'),
      array( TRUE, FALSE,  '1.2.3.1.2.3', 1),
      array(FALSE, FALSE,  '1.2.3.1.2.3', 3),
      array( TRUE, FALSE,  '1.2.3.1.2.3', 1.2), // Hrm :)
      // Arrays (recursive)
      array(FALSE, FALSE,            '', []),
      array( TRUE, FALSE, $test_string1, array(11,   'Observium is a ')),
      array(FALSE, FALSE, $test_string1, array(11,   'ObserviuM is a ')),
      array(FALSE, FALSE, $test_string1, array(11,   'observium is a ')),
      array(FALSE,  TRUE,            '', []),
      array( TRUE,  TRUE, $test_string1, array(11,   'Observium is a ')),
      array( TRUE,  TRUE, $test_string1, array(11,   'ObserviuM is a ')),
      array( TRUE,  TRUE, $test_string1, array(11,   'observium is a ')),
      // Not strings
      array(FALSE,  TRUE, $test_string1, array('fs', array('Observium is a '))),
      array(FALSE, FALSE, $test_string1, NULL),
      array(FALSE, FALSE, $test_string1, array()),
    );
  }

  /**
   * @dataProvider providerStrEnds
   * @group string
   */
  public function testStrEnds($result, $incase, $string, $needle, $encoding = FALSE)
  {
    if ($incase)
    {
      $this->assertSame($result, str_iends($string, $needle, $encoding));
    } else {
      $this->assertSame($result, str_ends($string, $needle, $encoding));
    }
  }

  public function providerStrEnds()
  {
    $test_string1 = 'Observium is a low-maintenance auto-discovering network monitoring platforM.';
    $test_string2 = 'Съешь ещё этих мягких французских булок, да выпей же чаЮ.'; // UTF-8
    return array(
      // case-sensitive
      array(FALSE, FALSE, $test_string1, ''),
      array( TRUE, FALSE, $test_string1, $test_string1),
      array(FALSE, FALSE, $test_string1, $test_string2),
      array( TRUE, FALSE, $test_string1, 'monitoring platforM.'),
      array(FALSE, FALSE, $test_string1, 'monitOring Platform.'),
      array(FALSE, FALSE, $test_string1, 'monitoring platform.'),
      array(FALSE, FALSE, $test_string1, 'Observium is a'),
      array(FALSE, FALSE, $test_string2, ''),
      array( TRUE, FALSE, $test_string2, $test_string2),
      array(FALSE, FALSE, $test_string2, $test_string1),
      array( TRUE, FALSE, $test_string2, ', да выпей же чаЮ.'),
      array(FALSE, FALSE, $test_string2, ', Да выпей же ЧаЮ.'),
      array(FALSE, FALSE, $test_string2, ', да выпеи же чаю.'),
      array(FALSE, FALSE, $test_string2, 'чаю'),
      // case-insensitive
      array(FALSE,  TRUE, $test_string1, ''),
      array( TRUE,  TRUE, $test_string1, $test_string1),
      array(FALSE,  TRUE, $test_string1, $test_string2),
      array( TRUE,  TRUE, $test_string1, 'monitoring platforM.'),
      array( TRUE,  TRUE, $test_string1, 'monitOring Platform.'),
      array( TRUE,  TRUE, $test_string1, 'monitoring platform.'),
      array(FALSE,  TRUE, $test_string1, 'platforM'),
      array(FALSE,  TRUE, $test_string2, ''),
      array( TRUE,  TRUE, $test_string2, $test_string2),
      array(FALSE,  TRUE, $test_string2, $test_string1),
      array( TRUE,  TRUE, $test_string2, ', да выпей же чаЮ.'),
      array( TRUE,  TRUE, $test_string2, ', Да выпей же ЧаЮ.', 'UTF-8'), // Require enable "slow" multibyte code in str_endwith()
      array(FALSE,  TRUE, $test_string2, ', да выпеи же чаю.'),
      array(FALSE,  TRUE, $test_string2, 'чаю'),
      // other
      array( TRUE, FALSE,            '', ''),
      array(FALSE, FALSE,          'abc', 'abcd'),
      array( TRUE, FALSE, '.1.2.3.1.2.3', '3'),
      array( TRUE, FALSE, '.1.2.3.1.2.3', '.1.2.3'),
      array(FALSE, FALSE, '.1.2.3.1.2.3', '1.2.'),
      array( TRUE, FALSE,  '1.2.3.1.2.3', 3),
      array(FALSE, FALSE,  '1.2.3.1.2.3', 1),
      array( TRUE, FALSE,  '1.2.3.1.2.3', 2.3), // Hrm :)
      // Arrays (recursive)
      array(FALSE, FALSE,            '', []),
      array( TRUE, FALSE, $test_string1, array(11,   'monitoring platforM.')),
      array(FALSE, FALSE, $test_string1, array(11,   'Monitoring platforM.')),
      array(FALSE, FALSE, $test_string1, array(11,   'monitoring platform.')),
      array(FALSE,  TRUE,            '', []),
      array( TRUE,  TRUE, $test_string1, array(11,   'monitoring platforM.')),
      array( TRUE,  TRUE, $test_string1, array(11,   'Monitoring platforM.')),
      array( TRUE,  TRUE, $test_string1, array(11,   'monitoring platform.')),
      // Not strings
      array(FALSE,  TRUE, $test_string1, array('fs', array('monitoring platforM.'))),
      array(FALSE, FALSE, $test_string1, NULL),
      array(FALSE, FALSE, $test_string1, array()),
    );
  }

  /**
   * @dataProvider providerStrCompress
   * @group compress
   */
  public function testStrCompress($string, $result) {
    $this->assertSame($result, str_compress($string));
  }

  /**
   * @depends testStrCompress
   * @dataProvider providerStrCompress
   * @group compress
   */
  public function testStrDecompress($result, $string) {
    $this->assertSame($result, str_decompress($string));
  }

  /**
   * @depends testStrCompress
   * @dataProvider providerStrCompressRandom
   * @group compress
   */
  public function testStrCompressRandom($string) {
    $encode = str_compress($string);
    $decode = str_decompress($encode);
    $this->assertSame($decode, $string);
  }

  /**
   * @depends testStrDecompress
   * @dataProvider providerStrDecompress
   * @group compress
   */
  public function testStrDecompressInvalid($string, $result) {
    $this->assertSame($result, str_decompress($string));
  }
  public function providerStrCompress() {
    return [
      // 681 chars compressed to 120 chars (base64)
      [ '.1.3.6.1.4.1.2606.7.4.2.2.1.11.1.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.40 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.47 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.54 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.64 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.73 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.82 .1.3.6.1.4.1.2606.7.4.2.2.1.11.4.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.4.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.4.20 .1.3.6.1.4.1.2606.7.4.2.2.1.11.6.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.6.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.6.20 .1.3.6.1.4.1.2606.7.4.2.2.1.11.7.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.7.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.7.20 .1.3.6.1.4.1.2606.7.4.2.2.1.11.12.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.12.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.12.20',
        'AVUAqv-NkoEJADEIA1f5CUJj_fj7T_btBAkiiBweiiA2BKJPlpYwp6wTBHl7DxzSK2DGM297RgEz2zOf3asRIGQwxp5HXqVApUA1XjWBagIVK3ieCmR30PoB' ],
      // 681 chars compressed to 180 chars (hex)
      // [ '.1.3.6.1.4.1.2606.7.4.2.2.1.11.1.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.40 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.47 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.54 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.64 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.73 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.82 .1.3.6.1.4.1.2606.7.4.2.2.1.11.4.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.4.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.4.20 .1.3.6.1.4.1.2606.7.4.2.2.1.11.6.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.6.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.6.20 .1.3.6.1.4.1.2606.7.4.2.2.1.11.7.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.7.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.7.20 .1.3.6.1.4.1.2606.7.4.2.2.1.11.12.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.12.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.12.20',
      //   '015500aaff8d9281090031080357f9094263fdf8fb4ff6ed040922881c1e8a203604a24f969630a7ac1304797b0f1cd22b60c6336f7b460133db339fddab11206430c69e475ea540a540355e35816a02152b789e0a6477d0fa01',
      //   'hex' ],
      // 4225 chars compressed to 2260 chars (base64)
      [ '603792,603795,735967,735962,706916,706931,692749,595380,716207,716197,834314,834237,834227,811153,811147,771424,771425,767750,767720,580799,580797,800238,602988,602987,778425,778406,778407,818925,818988,808167,808142,808143,736931,637484,811486,719059,768687,713042,713014,705867,603790,604635,799968,799990,799983,805612,733641,713864,797601,797599,734026,734024,740320,740324,710622,710591,741431,741400,834269,834274,822695,811485,795671,795638,603844,603845,812874,812866,703413,703427,718904,718953,816882,836314,836307,811132,811136,730931,730917,602000,602001,768844,768825,768826,584527,800325,800443,800444,704006,704004,718213,605635,614473,699119,809219,799013,781867,834928,810348,810344,837290,812181,812202,812179,812198,696049,696040,767203,742978,706948,706932,692750,595381,779344,779326,779327,741989,771373,17761,811154,811148,787755,617388,580800,800239,818926,818990,787965,768657,768658,705868,603791,604636,799969,799970,810690,810691,710623,710592,741417,741401,769964,620092,795655,795639,795640,703415,703429,836315,836308,811133,811137,811138,758080,591837,800326,601888,834931,810349,810345,812182,812204,812180,812200,777908,638004,718307,696041,710903,619539,813583,736360,630290,706917,706933,785242,741995,741996,713065,629651,815582,647028,647031,807790,807770,807771,705869,787393,608879,741435,741402,601878,836313,836324,740662,740642,800240,800241,812183,812192,714326,22756,718310,718312,788983,702011,730526,730441,813575,768067,767645,719586,628359,721547,721539,721551,721540,831999,831973,813882,809086,766933,766935,807789,767748,767744,807786,807787,580798,580793,580796,813888,788348,795654,795645,713204,713198,735964,758592,833556,833560,735938,758586,736513,47769,813883,765202,742010,785804,736745,709102,719061,779341,806057,806067,740186,742022,785808,719068,719069,765195,709098,779332,765194,709099,813885,810704,810700,816894,768842,816886,808511,797485,808515,811202,713206,735965,758593,713041,106040,591213,591023,713201,735940,591211,713018,758589,591025,800347,809090,594018,593642,814542,739551,800262,812177,800322,800266,779076,814539,584824,814538,594016,812175,618538,809087,812372,812401,813884,834222,708223,766351,710618,710613,710614,811720,834226,808163,708229,708231,650786,601582,811712,813887,585963,584958,619128,590360,620158,809093,741498,730930,741490,730923,809091,778607,778615,811151,808514,811165,813890,810347,707545,800465,765036,810357,800462,616617,616631,705885,705887,800331,809092,720660,704519,703414,834942,834947,785805,709103,736508,294034,740250,767886,719062,829759,785809,829764,767890,742119,782569,720648,736514,703424,719071,47770,709100,709101,719072,686510,813886,834319,595583,710620,811721,834312,710616,710615,595582,650791,834313,811714,294030,817096,716061,813889,809088,808510,832277,808503,808502,708224,708233,708232,582909,701990,702003,702010,779342,779334,797486,797479,550429,550425,550427,834306,834233,834210,765666,617384,591142,430351,767749,767746,584959,584957,619122,808136,808140,768843,768836,730929,730921,713040,713016,806058,829758,806068,829762,768652,788309,788350,805603,805611,805594,805595,805591,591210,591212,740318,740327,795657,795647,833742,833738,739846,739849,741428,741399,596541,596539,620021,834223,834167,834228,768491,806220,795668,795637,620053,740319,740330,768673,768672,768665,833558,833563,591022,591024,768852,799988,799979,591081,591082,811131,811135,612967,809222,809211,809224,768823,767885,767889,820386,820351,814543,584533,814540,814541,584529,619923,768674,768668,768667,584953,580794,629557,834925,805596,805597,837287,837284,812864,812865,812178,812196,720659,720649,720650,812395,812398,784444,30289,30290,587405,587407,706930,834267,834273,784442,813572,813573,799989,800000,800458,800461,599121,599132,587408,591141,602551,23716,23717,808146,808141,768663,602550,705871,594017,696680,618551,705883,705882,807788,834941,834945,765203,765196,765197,596542,596540,620034,741415,741407,741408,808155,834268,834275,779077,716616,816895,816903,800263,800245,810346,810356,585205,585199,586277,586279,696682,696678,580810,580812,834318,834322,741503,601882,601886,4853,5317,4865,5330,580809,580811,812870,812876,742011,742023,818620,742118,810705,703412,703421,741625,741626',
        'AZoGZfk1V0mWBSEIu9BfgCjK_S_WGap70Xn-KpUhBKqj7qxfE87v1pm-hvW70ZMtqPz1rLvnd-bUi9_NXnEJOff3alduwiqt1gJk5inBxps399oGXNT3nhCs-J0Xd8aAfRGrHkxa8z7g9qd9gGgDb3iDHwl488XLvoK9DAVXbHzd_TZt2Q_bc-IMbn_No7MCGwjw4cZ5OEUBCcDuwrUz00-AHwkPjsXpxL6q3sntr7F9bkcSDjy6tWO1Ac92FLwVYJXRi9fCFGzYsNYQoQj2CC6sXlgcG09bTt8UKEr19jbwlfW4AdBMXO0swaKbb2ILlJV-D1GqduK6whmrZaDVwdARkgFZEWFIho7XEpRNQCN_-yzlr5iViL3LwLDCrzbIiAXLGhFEdDv3vljNZMLpmAVAkIPGI7ktSs1CihO-fECr70I64G2-JKxYWt0RDMIzyOAYRLcVOHOvuU-83gY4TXqDkaI3_LvDGwjMH-EyOfOGFC6Ym_d2muTbJMdZD7SmQ7dASPD5MZnk85isLSCH3p1W5PpcwzP3nrmX5l6be4rHXDoLcz9Ic6jMoSXy5DWHmCLsAzOQLz4DXY7JUyNgPEiQY4KMuXDMhWcSuHrrYwYMlEsIUr76Et2wMx_LDymqdG7GcJyb5dxsr8Ir3I6w4iKw-KMEKahMybFBpiAuR6Gr81TL1aBgBdMudbpOH3ly1lYIhjpGaJU0gtwLoaZl58CW3jdAJQLNDVohuB-k8zDMUQ1Z-pAsV-hxdJecvs8xK4ELvKmbACkQit7p32nfy7xk1W-GDjJ5Wq5nGPDoPaoLjIxU9R0pCKooFYgr1gRlum8jxlAzmAsvX1HUFvh4BeUVXNePlBWEZQSXppQkAJGmJrbjSDgKBKkOfSatCds_tuFarZ-hDO0zWQePdSrSbbONdtZSoouVqR6zyShS91UdRIKADPOZ2UbHkPaDGG_U2_gCmnlY7KjkYOTwHk-uvrwHesVnEPn-Kpl5hthcASOHAuHJ2E4J5vbnDR_Q9ZOjw4LW4hSIhH7c_vGzhRyPK3IDQsI6VkZygDLLkL2T6gpUcK2k5_KBYWnH4zge5Z6UP5Q3dQv1Rrk89Ku8IbXh_1m6e30hG78pCa59leHhm_QaUGJn7sOCqSFBSNL-xPMrbBO4pX5xWxuKTXq_tb16PrO9j7r3-KMYRc3AMKAzKUcKlkcEhjzQ1MS2OpYxGk8ogySVs4E2tJt7ed8I2NVPkJCoxSORyZvLF5GeiCd5uVEdFBL0RJgbUpDFHY5LqaJFSCzCq9BqlV8hiV6HhpB24lIxQxot_X10rYWZIb9xz3YPlMof3Ktn4iB-hIJkN_sqoCw5bO4EJ0DiNNJu8INVEfuwMVK0FchhGgnXFP6oL6E8IPRCckqqtDxrvW_2YaYXJxTvG61YjHiFvu_FRnzR2lkIuJ21zCrc7hVbp0Aot1RTt36QfgT30NEynI32gKjhUULOthXOcPrZctrbcPzmUobne6Wc4W2_uB03ckNHf_wac-85NyQPFFYjIRJg-Li3zSEzCpWNyxBrrFLdmVPOp8FhCVnWgO0ibgG6wjnB3ik4Bo_D0R6HS8BT-jSqSbPBZoVySsV7KgAK7Ce3bc6O4Zq6nmfLdaBJBsVUgm9SY1UQLAV8hYrQFr7nfD_rn1dsVEySOk7Rd8AJjbXxTbek4EGNG44hrTmf9KjdFauXY-215hsoPYXBW_J-Keg1b7fBDXVxXxaHf6jfTgFUhlOLqUGZAGR_3xXsRtgtRV-cpnFRu9VgJOG-UzZpBKVg9S2DnWa9suM8d5xPWpfB4s24cM731H-tqS8N34zs8U8T7Bp9esxSqmYpdLO-wyR1VwVOYMFhYG-BJhNosbTqaOTaataE9FA9ZMGsz4dtH57hmihfC-bAh9L5JuYvY224mpjfB99nwgce1TjRcEBpVf35it9wNLiVPkQAbPKbkz2mMTjkmezg6wMyJPhGs-9jxtljFrhreZL5oBxpFi__JJDiKnSSASfNBKpTHP1cPhyRF7sX-gyYzv_f599XJ-kIld8LqetNNy2Nmpij1LTOJ8BlWJ5xPNPuNBwPHeUhoA3XvF0GdRZLbmqw5qz4DeT-Pj3H4XgOx3Fz1Xd0q49ieGCAe1SC6L8GjdI4-Wsi1AjYwkjDCEpFU-cEY8eW4PorJMOwrKS6nR0ellEYNcF7pgUpMaKATIUIbfLisIQ0948PSX9mhsFTFCcQDlNk7-v19ZDnochfGFQKNg995PY6hv4D' ],
      // 4225 chars compressed to 3390 chars (hex)
      // [ '603792,603795,735967,735962,706916,706931,692749,595380,716207,716197,834314,834237,834227,811153,811147,771424,771425,767750,767720,580799,580797,800238,602988,602987,778425,778406,778407,818925,818988,808167,808142,808143,736931,637484,811486,719059,768687,713042,713014,705867,603790,604635,799968,799990,799983,805612,733641,713864,797601,797599,734026,734024,740320,740324,710622,710591,741431,741400,834269,834274,822695,811485,795671,795638,603844,603845,812874,812866,703413,703427,718904,718953,816882,836314,836307,811132,811136,730931,730917,602000,602001,768844,768825,768826,584527,800325,800443,800444,704006,704004,718213,605635,614473,699119,809219,799013,781867,834928,810348,810344,837290,812181,812202,812179,812198,696049,696040,767203,742978,706948,706932,692750,595381,779344,779326,779327,741989,771373,17761,811154,811148,787755,617388,580800,800239,818926,818990,787965,768657,768658,705868,603791,604636,799969,799970,810690,810691,710623,710592,741417,741401,769964,620092,795655,795639,795640,703415,703429,836315,836308,811133,811137,811138,758080,591837,800326,601888,834931,810349,810345,812182,812204,812180,812200,777908,638004,718307,696041,710903,619539,813583,736360,630290,706917,706933,785242,741995,741996,713065,629651,815582,647028,647031,807790,807770,807771,705869,787393,608879,741435,741402,601878,836313,836324,740662,740642,800240,800241,812183,812192,714326,22756,718310,718312,788983,702011,730526,730441,813575,768067,767645,719586,628359,721547,721539,721551,721540,831999,831973,813882,809086,766933,766935,807789,767748,767744,807786,807787,580798,580793,580796,813888,788348,795654,795645,713204,713198,735964,758592,833556,833560,735938,758586,736513,47769,813883,765202,742010,785804,736745,709102,719061,779341,806057,806067,740186,742022,785808,719068,719069,765195,709098,779332,765194,709099,813885,810704,810700,816894,768842,816886,808511,797485,808515,811202,713206,735965,758593,713041,106040,591213,591023,713201,735940,591211,713018,758589,591025,800347,809090,594018,593642,814542,739551,800262,812177,800322,800266,779076,814539,584824,814538,594016,812175,618538,809087,812372,812401,813884,834222,708223,766351,710618,710613,710614,811720,834226,808163,708229,708231,650786,601582,811712,813887,585963,584958,619128,590360,620158,809093,741498,730930,741490,730923,809091,778607,778615,811151,808514,811165,813890,810347,707545,800465,765036,810357,800462,616617,616631,705885,705887,800331,809092,720660,704519,703414,834942,834947,785805,709103,736508,294034,740250,767886,719062,829759,785809,829764,767890,742119,782569,720648,736514,703424,719071,47770,709100,709101,719072,686510,813886,834319,595583,710620,811721,834312,710616,710615,595582,650791,834313,811714,294030,817096,716061,813889,809088,808510,832277,808503,808502,708224,708233,708232,582909,701990,702003,702010,779342,779334,797486,797479,550429,550425,550427,834306,834233,834210,765666,617384,591142,430351,767749,767746,584959,584957,619122,808136,808140,768843,768836,730929,730921,713040,713016,806058,829758,806068,829762,768652,788309,788350,805603,805611,805594,805595,805591,591210,591212,740318,740327,795657,795647,833742,833738,739846,739849,741428,741399,596541,596539,620021,834223,834167,834228,768491,806220,795668,795637,620053,740319,740330,768673,768672,768665,833558,833563,591022,591024,768852,799988,799979,591081,591082,811131,811135,612967,809222,809211,809224,768823,767885,767889,820386,820351,814543,584533,814540,814541,584529,619923,768674,768668,768667,584953,580794,629557,834925,805596,805597,837287,837284,812864,812865,812178,812196,720659,720649,720650,812395,812398,784444,30289,30290,587405,587407,706930,834267,834273,784442,813572,813573,799989,800000,800458,800461,599121,599132,587408,591141,602551,23716,23717,808146,808141,768663,602550,705871,594017,696680,618551,705883,705882,807788,834941,834945,765203,765196,765197,596542,596540,620034,741415,741407,741408,808155,834268,834275,779077,716616,816895,816903,800263,800245,810346,810356,585205,585199,586277,586279,696682,696678,580810,580812,834318,834322,741503,601882,601886,4853,5317,4865,5330,580809,580811,812870,812876,742011,742023,818620,742118,810705,703412,703421,741625,741626',
      //   '019a0665f935574996052108bbd05f8028cafd2fd619aa7bd179fe2a952104aaa3eeac5f13ceefd699be86f5bbd1932da8fcf5acbbe777e6d48bdfcd5e710939f7f76a576ec22aadd60264e629c1c69b37f7da065cd4f79e10acf89d1777c6807d11ab1e4c5af33ee0f6a77d8068036f78831f0978f3c5cbbe82bd0c05576c7cddfd366dd90fdb73e20c6e7fcda3b3021b08f0e1c67938450109c0eec2b533d34f801f090f8ec5e9c4beaadec9edafb17d6e47120e3cbab563b501cf7614bc156095d18bd7c2146cd8b0d610a108f6082eac5e581c1b4f5b4edf14284af5f636f095f5b801d04c5ced2cc1a29b6f620b94957e0f51aa76e2bac219ab65a0d5c1d011920159116148868ed712944d40237ffb2ce5af989588bdcbc0b0c2af36c88805cb1a1144743bf7be58cd64c2e99805409083c623b92d4acd428a13be7c40abef423ae06dbe24ac585add110cc233c8e01844b7153873afb94fbcde06384d7a8391a237fcbbc31b08cc1fe13239f386142e989bf7769ae4db24c7590fb4a643b74048f0f93199e4f398ac2d2087de9d56e4fa5cc333f79eb997e65e9b7b8ac75c3a0b733f4873a8cca125f2e435879822ec0333902f3e035d8ec95323603c489063828cb970cc856712b87aeb63060c944b0852befa12ddb0331fcb0f29aa746ec6709c9be5dc6cafc22bdc8eb0e222b0f8a30429a84cc9b141a6202e47a1abf354cbd5a06005d32e75ba4e1f7972d65608863a4668953482dc0ba1a665e7c096de37402502cd0d5a21b81fa4f330cc510d59fa902c57e87174979cbecf312b810bbca99b0029108adee9df69dfcbbc64d56f860e32795aae6718f0e83daa0b8c8c54f51d2908aa2815882bd60465ba6f23c65033980b2f5f51d416f87805e5155cd78f941584650497a694240091a626b6e348380a04a90e7d26ad09db3fb6e15aad9fa10ced3359078f752ad26db38d76d652a28b95a91eb3c92852f7551d4482800cf399d946c790f683186fd4dbf8029a7958eca8e460e4f01e4faebebc077ac56710f9fe2a997986d85c01238702e1c9d84e09e6f6e70d1fd0f593a3c382d6e21488847edcfef1b3851c8f2b720342c23a5646728032cb90bd93ea0a5470ada4e7f2816169c7e3381ee59e943f9437750bf546b93cf4abbc21b5e1ff59ba7b7d211bbf2909ae7d95e1e19bf41a506267eec382a9214148d2fec4f32b6c13b8a57e715b1b8a4d7abfb5bd7a3eb3bd8fbaf7f8a31845cdc030a03329470a964704863cd0d4c4b63a96311a4f28832495b38136b49b7b79df08d8d54f9090a8c52391c99bcb17919e882779b9511d1412f444981b5290c51d8e4ba9a245482cc2abd06a955f21895e87869076e25231431a2dfd7d74ad859921bf71cf760f94ca1fdcab67e2207e84826437fb2aa02c396cee042740e234d26ef0835511fbb03152b415c8611a09d714fea82fa13c20f442724aaab43c6bbd6ff661a6172714ef1bad588c7885beefc5467cd1da5908b89db5cc2adcee155ba74028b75453b77e907e04f7d0d1329c8df680a8e15142ceb615ce70fad972dadb70fce65286e77ba59ce16dbfb81d377243477ffc1a73ef3937240f145623211260f8b8b7cd21330a958dcb106bac52dd9953cea7c1610959d680ed226e01bac239c1de2938068fc3d11e874bc053fa34aa49b3c16685724ac57b2a000aec27b76dce8ee19aba9e67cb75a04906c554826f526355102c057c858ad016bee77c3feb9f576c544c923a4ed177c0098db5f14db7a4e0418d1b8e21ad399ff4a8dd15ab9763edb5e61b283d85c15bf27e29e8356fb7c10d75715f16877fa8df4e015486538ba9419900647fdf15ec46d82d455f9ca67151bbd56024e1be53366904a560f52d839d66bdb2e33c779c4f5a97c1e2cdb870cef7d47fada92f0ddf8cecf14f13ec1a7d7acc52aa662974b3bec3247557054e60c161606f81261368b1b4ea68e4da6ad684f4503d64c1accf876d1f9ee19a285f0be6c087d2f926e62f636db89a98df07df67c2071ed538d170406955fdf98adf7034b8953e44006cf29b933da63138e499ece0eb033224f846b3ef63c6d96316b86b7992f9a01c69162fff2490e22a74920127cd04aa531cfd5c3e1c9117bb17fa0c98ceffdfe7df5727e90895df0ba9eb4d372d8d9a98a3d4b4ce27c065589e713cd3ee341c0f1de521a00dd7bc5d0675164b6e6ab0e6acf80de4fe3e3dc7e1780ec77173d57774ab8f627860807b5482e8bf068dd238f96b22d408d8c248c3084a4553e70463c796e0fa2b24c3b0aca4ba9d1d1e96511835c17ba6052931a2804c85086df2e2b08434f78f0f497f6686c1531427100e5364efebf5f590e7a1c85f18540a360f7de4f63a86fe03',
      //   'hex' ],
    ];
  }

  public function providerStrCompressRandom() {
    $charlist = ' 0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ`~!@#$%^&*()_+-=[]{}\|/?,.<>;:"'."'";
    $result = [];
    for ($i=0; $i<20; $i++) {
      $string = random_string(mt_rand(200, 4000), $charlist);
      $result[] = [ $string ];
    }
    return $result;
  }

  public function providerStrDecompress() {
    return [
      [ '15500aaff8d9281090031080357f9094263fdf8fb4ff6ed040922881c1e8a203604a24f969630a7ac1304797b0f1cd22b60c6336f7b460133db339fddab11206430c69e475ea540a540355e35816a02152b789e0a6477d0fa01', FALSE ],
      [ 'AVUAqv-NkoEJADEIA1f5CUJj_fj7T_btBAkiiBweiiA2BKJPlpYwp6wTBHl7DxzSK2DGM297RgEz2zOf3asRIGQwxp5HXqVApUA1XjWBagIVK3ieCmR30Po', FALSE ],
      [ '.1.3.6.1.4.1.2606.7.4.2.2.1.11.1.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.40', FALSE ],
      [ NULL, FALSE ],
    ];
  }

  /**
   * @dataProvider providerIsValidParam
   * @group string
   */
  public function testIsValidParam($string, $type, $result)
  {
    $this->assertSame($result, is_valid_param($string, $type));
  }

  public function providerIsValidParam()
  {
    return [
      // common
      [ '',          '', FALSE ],
      [ '**--.--**', '', FALSE ],
      // serial
      [ '1234567890',                                    'serial', FALSE ],
      [ 'Ã´Â¿i+',                                        'serial', FALSE ],
      [ 'Ã´▒Â¿i+',                                       'serial', FALSE ],
      [ '22:00:00:33:FF:AA',                             'serial', TRUE ],
      [ '~!@#$%^&*()_+`1234567890-=[]\\{}|;: \'",./<>?', 'serial', TRUE ],
      // snmp community
      [ 'f%!@#$%^&*()_+~`[]{}\|<>,./?;:',                'snmp_community', TRUE ],
      [ 'Domain observiuvm.org wasddddddd',              'snmp_community', TRUE ],
      [ '32chars.........................',              'snmp_community', TRUE ],
      [ '32+chars.........................',             'snmp_community', FALSE ],
    ];
  }

    /**
    * @dataProvider providerEscapeHtml
    * @group string
    */
    public function testEscapeHtml($value, $result) {
        $this->assertSame($result, escape_html($value));
    }

    public function providerEscapeHtml() {
        return [
            [ '<div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button>',
              '&lt;div class=&quot;alert alert-info&quot;&gt;&lt;button type=&quot;button&quot; class=&quot;close&quot; data-dismiss=&quot;alert&quot;&gt;&amp;times;&lt;/button&gt;' ],
            // excludes
            [ '<p>Text with tags <sup>sup</sup> and <sub>sub</sub> and newline <br/> <br />',
              '&lt;p&gt;Text with tags <sup>sup</sup> and <sub>sub</sub> and newline <br/> <br />' ],
            // entities
            [ '<p>Text with entities &#x200B; &pi; &pipipi;',
              '&lt;p&gt;Text with entities &#x200B; &pi; &amp;pipipi;' ],
            // EMPTY
            [ NULL, NULL ],
        ];
    }

  /**
   * @dataProvider providerPrintMessage
   * @group print
   */
  public function testPrintMessage($cli, $type, $strip, $text, $result)
  {
    $this->expectOutputString($result);
    $GLOBALS['cache']['is_cli'] = $cli; // Override actual is_cli test
    //define('OBS_CLI', $cli);
    print_message($text, $type, $strip);
  }

  public function providerPrintMessage()
  {
    return array(
      // Basic CLI output ($cli=TRUE, $type='', $strip=TRUE)
      array(
        TRUE, '' , TRUE,
        "",   "\n"),
      array(
        TRUE, '' , TRUE,
        "  My test \n is simple",   "  My test \n is simple\n"),
      array(
        TRUE, '' , TRUE,
        29874234,   "29874234\n"),
      array( // Actually not print anything
        TRUE, '' , TRUE,
        NULL, ''),
      array(
        TRUE, '' , TRUE,
        array(), ''),
      // CLI other types: success, warning, error, debug
      array(TRUE, 'success', TRUE, "  My test \n is simple",   "  My test \n is simple\n"),
      array(TRUE, 'warning', TRUE, "  My test \n is simple",   "  My test \n is simple\n"),
      array(TRUE, 'error',   TRUE, "  My test \n is simple",   "  My test \n is simple\n"),
      array(TRUE, 'console', TRUE, "\n  My test \n is simple", "\n  My test \n is simple\033[0m\n\033[0m"),
      // Note, global $debug var checked only in print_debug() function
      array(TRUE, 'debug',   TRUE, "  My test \n is simple",   "  My test \n is simple\n"),
      // CLI strip html ($cli=TRUE, $type='', $strip=TRUE)
      array(
        TRUE, '' , TRUE,
        '  <h1>My test</h1> <br /> <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button></div>',
        "  My test \n ×\n"),
      // CLI no strip html ($cli=TRUE, $type='', $strip=FALSE)
      array(
        TRUE, '' , FALSE,
        '  <h1>My test</h1> <br /> <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button></div>',
        '  <h1>My test</h1> <br /> <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button></div>'.PHP_EOL),
      // CLI strip html with color ($cli=TRUE, $type='color', $strip=TRUE)
      array(
        TRUE, 'color' , TRUE,
        '  <h1>My test</h1> <br /> <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button></div>',
        "  My test \n ×\033[0m\n\033[0m"),
      // CLI no strip html with color ($cli=TRUE, $type='color', $strip=FALSE)
      array(
        TRUE, 'color' , FALSE,
        '  <h1>My test</h1> <br /> <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button></div>',
        '  <h1>My test</h1> <br /> <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button></div>'."\033[0m\n\033[0m"),
      // CLI color codes ($cli=TRUE, $type='color', $strip=TRUE)
      array(
        TRUE, 'color' , TRUE,
      '%n, %y, %g, %r, %b, %c, %W, %k, %_, %U, %%, &',
      "\033[0m, \033[0;33m, \033[0;32m, \033[0;31m, \033[0;34m, \033[0;36m, \033[1;37m, \033[0;30m, \033[1m, \033[4m, %, &\033[0m\n\033[0m"),
      // CLI color codes ($cli=TRUE, $type='color', $strip=FALSE)
      array(
        TRUE, 'color' , FALSE,
      '%n, %y, %g, %r, %b, %c, %W, %k, %_, %U, %%, &',
      "\033[0m, \033[0;33m, \033[0;32m, \033[0;31m, \033[0;34m, \033[0;36m, \033[1;37m, \033[0;30m, \033[1m, \033[4m, %, &\033[0m\n\033[0m"),

      // Basic HTML output ($cli=FALSE, $type='', $strip=TRUE)
      array( // Actually not print anything
        FALSE, '' , TRUE,
        "",   ""),
      array(
        FALSE, '' , TRUE,
        "  My test \n is simple",
        '
    <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button>
      <div>  My test 
 is simple</div>
    </div>'.PHP_EOL),
      array(
        FALSE, '' , TRUE,
        29874234, '
    <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button>
      <div>29874234</div>
    </div>'.PHP_EOL),
      array( // Actually not print anything
        FALSE, '' , TRUE,
        NULL, ''),
      array( // Actually not print anything
        FALSE, '' , TRUE,
        array(), ''),
      // HTML other types: success, warning, error, debug
      array(
        FALSE, 'success' , TRUE,
        "  My test is simple",
        '
    <div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>
      <div>  My test is simple</div>
    </div>'.PHP_EOL),
      array(
        FALSE, 'warning' , TRUE,
        "  My test is simple",
        '
    <div class="alert alert-warning">
      <div>  My test is simple</div>
    </div>'.PHP_EOL),
      array(
        FALSE, 'error' , TRUE,
        "  My test is simple",
        '
    <div class="alert alert-danger">
      <div>  My test is simple</div>
    </div>'.PHP_EOL),
      array( // Note, global $debug var checked only in print_debug() function
        FALSE, 'debug' , TRUE,
        "  My test is simple",
        '
    <div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>
      <div>  My test is simple</div>
    </div>'.PHP_EOL),
      // HTML strip cli ($cli=FALSE, $type='', $strip=TRUE)
      array(
        FALSE, '' , TRUE,
        '%n, %y, %g, %r, %b, %c, %W, %k, %_, %U, %%, &',
        '
    <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button>
      <div>, , , , , , , , , , %, &amp;</div>
    </div>'.PHP_EOL),
      // HTML strip cli, input text have html tags ($cli=FALSE, $type='', $strip=TRUE)
      array(
        FALSE, '' , TRUE,
        '%n, %y, %g, %r, %b, %c, %W, %k, %_, %U, %%, & <h1>',
        '
    <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button>
      <div>, , , , , , , , , , %, & <h1></div>
    </div>'.PHP_EOL),
      // HTML no strip cli ($cli=FALSE, $type='', $strip=FALSE)
      array(
        FALSE, '' , FALSE,
        '%n, %y, %g, %r, %b, %c, %W, %k, %_, %U, %%, &',
        '
    <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button>
      <div>%n, %y, %g, %r, %b, %c, %W, %k, %_, %U, %%, &</div>
    </div>'.PHP_EOL),
      // HTML strip cli with color ($cli=FALSE, $type='color', $strip=TRUE)
      array(
        FALSE, 'color' , TRUE,
        '%n, %y, %g, %r, %b, %c, %W, %k, %_, %U, %%, &',
        '
    <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button>
      <div></span>, <span class="label label-warning">, <span class="label label-success">, <span class="label label-danger">, <span class="label label-primary">, <span class="label label-info">, <span class="label label-default">, <span class="label label-default" style="color:black;">, <span style="font-weight: bold;">, <span style="text-decoration: underline;">, %, &amp;</div>
    </div>'.PHP_EOL),
      // HTML no strip cli with color ($cli=FALSE, $type='color', $strip=FALSE)
      array(
        FALSE, 'color' , FALSE,
        '%n, %y, %g, %r, %b, %c, %W, %k, %_, %U, %%, &',
        '
    <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">&times;</button>
      <div>%n, %y, %g, %r, %b, %c, %W, %k, %_, %U, %%, &</div>
    </div>'.PHP_EOL),
      array(FALSE, 'console', TRUE,
        "\n  My test \n is simple",
        '
    <div class="alert alert-suppressed"><button type="button" class="close" data-dismiss="alert">&times;</button>
      <div>My test <br />
 is simple</div>
    </div>'.PHP_EOL),
    );
  }

  /**
   * @dataProvider providerReformatUSDate
   * @group datetime
   */
  public function testReformatUSDate($value, $result)
  {
    // Keep always default
    $GLOBALS['config']['timestamp_format']  = 'Y-m-d H:i:s';
    $GLOBALS['config']['date_format']       = 'Y-m-d';

    $this->assertSame($result, reformat_us_date($value));
  }

  public function providerReformatUSDate()
  {
    return array(
      array('07/01/12',   '2012-07-01'),
      array('7/1/12',     '2012-07-01'),
      array('12/06/99',   '1999-12-06'),
      array('03/05/2012', '2012-03-05'),
      array('02/14/1995', '1995-02-14'),
      array('05/30/2011 00:12:17', '2011-05-30 00:12:17'),
      array('05/30/2011 00:12',    '2011-05-30 00:12:00'),
      array('Banana',     'Banana'),
      array('Ob-servium', 'Ob-servium'),
    );
  }

    /**
     * @dataProvider providerGetHttpRequest
     * @group http
     */
    public function testGetHttpRequest($url, $result, $status, $code)
    {
        $test = get_http_request($url);
        if (is_string($result))
        {
            if (method_exists($this, 'assertStringContainsString')) {
                // PHPUnit 9+
                $this->assertStringContainsString($result, $test);
            } else {
                $this->assertContains($result, $test);
            }
        } else {
            // This for wrong url, return FALSE
            $this->assertSame($result, $test);
        }
        $this->assertSame($status, get_http_last_status());
        $this->assertSame($code,   get_http_last_code());
    }

    public function providerGetHttpRequest()
    {
        return array(
            array('http://info.cern.ch',           '<html',  TRUE, 200), // OK, http
            array('https://www.observium.org',     '<html',  TRUE, 200), // OK, https
            array('http://somewrong.test',           FALSE, FALSE,   6), // Unknown host
            array('https://www.observium.org/404', '<html', FALSE, 404), // OK, not found
        );
    }

    /**
     * @group json
     */
    public function test_fix_json_unicode()
    {
        $input = "ËЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ";
        $expectedOutput = "\u00cb\u0419\u0426\u0423\u041a\u0415\u041d\u0413\u0428\u0429\u0417\u0425\u042a\u0424\u042b\u0412\u0410\u041f\u0420\u041e\u041b\u0414\u0416\u042d\u042f\u0427\u0421\u041c\u0418\u0422\u042c\u0411\u042e";

        $result = fix_json_unicode($input);

        $this->assertSame($expectedOutput, $result);
    }
}

// EOF
