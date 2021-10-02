<?php

//define('OBS_DEBUG', 2);

$base_dir = realpath(__DIR__ . '/..');
$config['install_dir'] = $base_dir;

include(__DIR__ . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php'); // Do not include user editable config here
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
    $this->assertLessThanOrEqual(2, age_to_unixtime($value, $min_age) - $result);
  }

  public function providerAgeToUnixtime()
  {
    return array(
      array('3y 4M 6w 5d 3h 1m 3s', 1, time() - 109191663),
      array('3y4M6w5d3h1m3s',       1, time() - 109191663),
      array('1.5w',                 1,    time() - 907200),
      array('30m',               7200,                  0),
      array(-886732,                1,                  0),
      array('Star Wars',            1,                  0),
    );
  }

  /**
   * @dataProvider providerExternalExec
   * @group exec
   */
  public function testExternalExec($cmd, $timeout, $result)
  {
    //var_dump($_SERVER['SHELL']);
    $test = external_exec($cmd, $timeout);
    $exec_status = $GLOBALS['exec_status'];
    unset($exec_status['endtime'], $exec_status['runtime'], $exec_status['exitdelay']);
    if ($exec_status['exitcode'] == 127)
    {
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
            array('command'  => $cmd_which.' true',
                  'exitcode' => 0,
                  'stderr'   => '',
                  'stdout'   => '/bin/true')
            ),
      // here generate stderr
      array($cmd_which.' true >&2',
            NULL,
            array('command'  => $cmd_which.' true >&2',
                  'exitcode' => 0,
                  'stderr'   => '/bin/true',
                  'stdout'   => '')
            ),
      // normal stdout, but exitcode 1
      array('/bin/false',
            NULL,
            array('command'  => '/bin/false',
                  'exitcode' => 1,
                  'stderr'   => '',
                  'stdout'   => '')
            ),
      // real stdout, exit code 127
      array('/bin/jasdhksdhka',
            NULL,
            array('command'  => '/bin/jasdhksdhka',
                  'exitcode' => 127,
                  //'stderr'   => 'sh: 1: /bin/jasdhksdhka: not found', // this stderror is shell env specific for dash
                  'stderr'   => 'sh: /bin/jasdhksdhka: No such file or directory', // this stderror is shell env specific for bash
                  'stdout'   => '')
            ),
      // normal stdout with special chars (tabs, eol in eof)
      array( '/bin/cat ' . __DIR__ . '/data/text.txt',
             NULL,
             array( 'command'  => '/bin/cat ' . __DIR__ . '/data/text.txt',
                    'exitcode' => 0,
                    'stderr'   => '',
                    'stdout'   =>
"Observium is an autodiscovering network monitoring platform
\tsupporting a wide range of hardware platforms and operating systems
\tincluding Cisco, Windows, Linux, HP, Juniper, Dell, FreeBSD, Brocade,
\tNetscaler, NetApp and many more.

 Observium seeks to provide a powerful yet simple and intuitive interface
 to the health and status of your network.

~!@#$%^&*()_+`1234567890-=[]\{}|;':\",./<>?

")
            ),
      // timeout 5sec, ok
      array('/bin/sleep 1',
            5,
            array('command'  => '/bin/sleep 1',
                  'exitcode' => 0,
                  'stderr'   => '',
                  'stdout'   => '')
            ),
      // timeout 2sec, expired, exitcode -1
      array('/bin/sleep 10',
            1,
            array('command'  => '/bin/sleep 10',
                  'exitcode' => -1,
                  'stderr'   => '',
                  'stdout'   => '')
            ),

      // Empty
      array('',
            NULL,
            array('command'  => '',
                  'exitcode' => -1,
                  'stderr'   => 'Empty command passed',
                  'stdout'   => '')
            ),
      array(FALSE,
            NULL,
            array('command'  => '',
                  'exitcode' => -1,
                  'stderr'   => 'Empty command passed',
                  'stdout'   => '')
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
  public function testGetPidInfo()
  {

    $pid = getmypid();
    $timezone = get_timezone(); // Get system timezone info, for correct started time conversion

    // Compare only this keys
    $compare_keys = array('PID', 'PPID', 'UID', 'GID', 'COMMAND', 'STARTED', 'STARTED_UNIX', 'VSZ');

    $test_pid_info['ps']       = get_pid_info($pid);
    $test_pid_info['ps_stats'] = get_pid_info($pid, TRUE);
    $test['ps']       = external_exec('/bin/ps -ww -o pid,ppid,uid,gid,tty,stat,time,lstart,args -p '.$pid, 1); // Set timeout 1sec for exec
    $test['ps_stats'] = external_exec('/bin/ps -ww -o pid,ppid,uid,gid,pcpu,pmem,vsz,rss,tty,stat,time,lstart,args -p '.$pid, 1); // Set timeout 1sec for exec

    foreach ($test as $ps_type => $ps)
    {
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
      $started_rfc .= ' ' . $started[0]; // Mar
      $started_rfc .= ' ' . $started[3]; // 2016
      $started_rfc .= ' ' . $started[2]; // 18:01:53
      $started_rfc .= ' ' . $started[4]; // +0300
      //$started_rfc .= implode(' ', $started);
      $entries[] = $started_rfc;

      $entries[] = $command; // Re-add command
      //print_vars($entries);
      //print_vars($started);

      $pid_info = array();
      foreach ($keys as $i => $key)
      {
        if (!in_array($key, $compare_keys)) { continue; }
        $pid_info[$key] = $entries[$i];
      }
      $pid_info['STARTED_UNIX'] = strtotime($pid_info['STARTED']);

      foreach ($test_pid_info[$ps_type] as $key => $tmp)
      {
        if (!in_array($key, $compare_keys))
        {
          unset($test_pid_info[$ps_type][$key]);
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
    $this->assertSame($result, deviceUptime($value));
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
   * @group values
   */
  public function testFormatNumberShort($value, $sf, $result)
  {
    $this->assertSame($result, format_number_short($value, $sf));
  }

  public function providerFormatNumberShort()
  {
    return array(
      array( '12345', 3,  '12345'),
      array('1234.5', 3,   '1234'),
      array('123.45', 3,    '123'),
      array('12.345', 3,   '12.3'),
      array('1.2345', 3,   '1.23'),
      array('.12345', 3,   '.123'),
      array('0.1234', 3,   '0.12'),

      array('-1.234', 3,   '-1.2'),

      array('1.234b', 3,      '1'), // alpha in decimals

      // < 0
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
    $rv = generate_random_string($len, $chars);

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
    $this->assertSame($result, formatRates($value, $round, $sf));
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
    $this->assertSame($result, formatStorage($value, $round, $sf));
  }

  public function providerFormatStorage()
  {
    return array(
      // simple test; most testing is done against format_bi
      array(102400000, 2, 3,  '97.6MB'),

      // round & sf
      array(102400000, 4, 4, '97.65MB'),
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
      array(             1000000, 2, 3,    '976k'),

      // round & sf
      array(           105000000, 4, 4,  '100.1M'),
      array(           102400000, 4, 4,  '97.65M'),
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
      array(102400000, '1024', 2, 3,  '97.6M'), // string base
      array(102400000,   1024, 2, 3,  '97.6M'), // int base
      array(102400000,   1024, 4, 4, '97.65M'), // round and sf
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
  public function testGetTime($value, $result)
  {
    $diff = abs((int)$result - get_time($value));
    $this->assertLessThanOrEqual(10, $diff); // +- 10 sec
  }

  public function providerGetTime()
  {
    $now = time();
    return array(
      [ 'now',         $now ],
      [ 'fiveminute',  $now - 300 ],      //time() - (5 * 60);
      [ 'fourhour',    $now - 14400 ],    //time() - (4 * 60 * 60);
      [ 'sixhour',     $now - 21600 ],    //time() - (6 * 60 * 60);
      [ 'twelvehour',  $now - 43200 ],    //time() - (12 * 60 * 60);
      [ 'day',         $now - 86400 ],    //time() - (24 * 60 * 60);
      [ 'twoday',      $now - 172800 ],   //time() - (2 * 24 * 60 * 60);
      [ 'week',        $now - 604800 ],   //time() - (7 * 24 * 60 * 60);
      [ 'twoweek',     $now - 1209600 ],  //time() - (2 * 7 * 24 * 60 * 60);
      [ 'month',       $now - 2678400 ],  //time() - (31 * 24 * 60 * 60);
      [ 'twomonth',    $now - 5356800 ],  //time() - (2 * 31 * 24 * 60 * 60);
      [ 'threemonth',  $now - 8035200 ],  //time() - (3 * 31 * 24 * 60 * 60);
      [ 'sixmonth',    $now - 16070400 ], //time() - (6 * 31 * 24 * 60 * 60);
      [ 'year',        $now - 31536000 ], //time() - (365 * 24 * 60 * 60);
      [ 'twoyear',     $now - 63072000 ], //time() - (2 * 365 * 24 * 60 * 60);
      [ 'threeyear',   $now - 94608000 ], //time() - (3 * 365 * 24 * 60 * 60);
    );
  }

  /**
   * @dataProvider providerFormatTimestamp
   * @group datetime
   */
  public function testFormatTimestamp($value, $result)
  {
    $GLOBALS['config']['timestamp_format'] = 'Y-m-d H:i:s'; // force fixed format
    if ($value === 'now') { $result = date('Y-m-d H:i:s'); } // force same times
    $this->assertSame($result, format_timestamp($value));
  }

  public function providerFormatTimestamp()
  {
    return array(
      array('Aug 30 2014',      '2014-08-30 00:00:00'),
      array('2012-04-18 14:25', '2012-04-18 14:25:00'),
      array('now',              date('Y-m-d H:i:s')),
      array('Star Wars',        'Star Wars'),
    );
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
    return array(
      array(1409397693,                NULL, '2014-08-30 11:21:33'),
      array(1409397693,        DATE_RFC2822, 'Sat, 30 Aug 2014 11:21:33 +0000'),
      array(1551607499,        DATE_RFC2822, 'Sun, 03 Mar 2019 10:04:59 +0000'),
      array(1551607499.3878,   DATE_RFC2822, 'Sun, 03 Mar 2019 10:04:59 +0000'),
      array(1551607499.3878,      'H:i:s.u', '10:04:59.387800'),
      array(1551607499.3878,      'H:i:s.v', '10:04:59.387'),
      array(1551607499.387867,    'H:i:s.u', '10:04:59.387867'),
      array('1551607499.387867',  'H:i:s.u', '10:04:59.387867'),
      array('1551607499.3878679', 'H:i:s.u', '10:04:59.387868'),
      // Wrong data
      array('0',   'r',       ''),
      array('',    'H:i:s.u', ''),
      array(FALSE, 'H:i:s.u', ''),
    );
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
    $this->assertSame($result, is_array_seq($array));
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
   * @group string
   */
  public function testStrCompress($string, $result) {
    $this->assertSame($result, str_compress($string));
  }

  /**
   * @depends testStrCompress
   * @dataProvider providerStrCompress
   * @group string
   */
  public function testStrDecompress($result, $string) {
    $this->assertSame($result, str_decompress($string));
  }

  /**
   * @depends testStrCompress
   * @dataProvider providerStrCompressRandom
   * @group string
   */
  public function testStrCompressRandom($string) {
    $encode = str_compress($string);
    $decode = str_decompress($encode);
    $this->assertSame($decode, $string);
  }

  /**
   * @depends testStrDecompress
   * @dataProvider providerStrDecompress
   * @group string
   */
  public function testStrDecompressInvalid($string, $result) {
    $this->assertSame($result, str_decompress($string));
  }
  public function providerStrCompress() {
    return [
      // 681 chars compress to 180 chars
      [ '.1.3.6.1.4.1.2606.7.4.2.2.1.11.1.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.40 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.47 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.54 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.64 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.73 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.82 .1.3.6.1.4.1.2606.7.4.2.2.1.11.4.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.4.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.4.20 .1.3.6.1.4.1.2606.7.4.2.2.1.11.6.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.6.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.6.20 .1.3.6.1.4.1.2606.7.4.2.2.1.11.7.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.7.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.7.20 .1.3.6.1.4.1.2606.7.4.2.2.1.11.12.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.12.11 .1.3.6.1.4.1.2606.7.4.2.2.1.11.12.20',
        '015500aaff8d9281090031080357f9094263fdf8fb4ff6ed040922881c1e8a203604a24f969630a7ac1304797b0f1cd22b60c6336f7b460133db339fddab11206430c69e475ea540a540355e35816a02152b789e0a6477d0fa01' ],
    ];
  }

  public function providerStrCompressRandom() {
    $charlist = ' 0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ`~!@#$%^&*()_+-=[]{}\|/?,.<>;:"'."'";
    $result = array();
    for ($i=0; $i<20; $i++) {
      $string = generate_random_string(mt_rand(200, 4000), $charlist);
      $result[] = array($string);
    }
    return $result;
  }

  public function providerStrDecompress() {
    return [
      [ '15500aaff8d9281090031080357f9094263fdf8fb4ff6ed040922881c1e8a203604a24f969630a7ac1304797b0f1cd22b60c6336f7b460133db339fddab11206430c69e475ea540a540355e35816a02152b789e0a6477d0fa01', FALSE ],
      [ '.1.3.6.1.4.1.2606.7.4.2.2.1.11.1.2 .1.3.6.1.4.1.2606.7.4.2.2.1.11.1.40', FALSE ],
      [ NULL, FALSE ],
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
      array('http://observium.org',      '<html',  TRUE, 200), // OK, http
      array('https://www.observium.org', '<html',  TRUE, 200), // OK, https
      array('http://somewrong.test',       FALSE, FALSE, 408), // Unknown host
      array('http://observium.org/404',    FALSE, FALSE, 404), // OK, not found
    );
  }

}

// EOF
