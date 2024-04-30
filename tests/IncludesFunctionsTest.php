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
include(__DIR__ . '/data/test_definitions.inc.php'); // Fake definitions for testing
include(__DIR__ . '/../includes/definitions.inc.php');
include(__DIR__ . '/../includes/functions.inc.php');
if (is_file(__DIR__ . '/data/config.php')) {
    include(__DIR__ . '/data/config.php'); // I.e. for API keys
}

/**
 * @backupGlobals disabled
 */
class IncludesFunctionsTest extends \PHPUnit\Framework\TestCase
{
  /**
  * @dataProvider providerEmail
  */
  public function testParseEmail($string, $result)
  {
    $this->assertSame($result, parse_email($string));
  }

  public function providerEmail()
  {
    return array(
        array('test@example.com',     array('test@example.com' => NULL)),
        array(' test@example.com ',   array('test@example.com' => NULL)),
        array('<test@example.com>',   array('test@example.com' => NULL)),
        array('<test@example.com> ',  array('test@example.com' => NULL)),
        array(' <test@example.com> ', array('test@example.com' => NULL)),

        //array('Test Title <test@example>',          array('test@example' => 'Test Title')), // Non fqdn
        array('Test Title <test@example.com>',      array('test@example.com' => 'Test Title')),
        array('Test Title<test@example.com>',       array('test@example.com' => 'Test Title')),
        array('"Test Title" <test@example.com>',    array('test@example.com' => 'Test Title')),
        //array('"Test Title <test@example.com>',     array('test@example.com' => 'Test Title')), // incorrect test
        //array('Test Title" <test@example.com>',     array('test@example.com' => 'Test Title')), // incorrect test
        array('" Test Title " <test@example.com>',  array('test@example.com' => 'Test Title')),
        array('\'Test Title\' <test@example.com>',  array('test@example.com' => 'Test Title')),

        array('"Test Title" <test@example.com>,"Test Title 2" <test2@example.com>',
              array('test@example.com' => 'Test Title', 'test2@example.com' => 'Test Title 2')),
        array('\'Test Title\' <test@example.com>, "Test Title 2" <test2@sub.example.com>,     test3@example.com',
              array('test@example.com' => 'Test Title', 'test2@sub.example.com' => 'Test Title 2', 'test3@example.com' => NULL)),

        array('example.com',                 FALSE),
        array('<example.com>',               FALSE),
        array('Test Title test@example.com', FALSE),
        array('Test Title <example.com>',    FALSE),
    );
  }

  /**
   * @dataProvider providerSiToScale
   * @group numbers
   */
  public function testSiToScale($units, $precision, $result)
  {
    $this->assertSame($result, si_to_scale($units, $precision));
  }

  public function providerSiToScale()
  {
    $results = array(
      array('yocto',  5, 1.0E-29),
      array('zepto', -6, 1.0E-21),
      array('atto',   9, 1.0E-27),
      array('femto',  8, 1.0E-23),
      array('pico',   0, 1.0E-12),
      array('nano',  -7, 1.0E-9),
      array('micro',  4, 1.0E-10),
      array('milli',  7, 1.0E-10),
      array('deci',   0, 0.1),
      array('units',  3, 0.001),
      array('deca',   0, 10),
      array('kilo',   2, 10),
      array('mega',  -2, 1000000),
      array('giga',  -1, 1000000000),
      array('tera',  -4, 1000000000000),
      array('peta',   4, 100000000000),
      array('exa',   -3, 1000000000000000000),
      array('zetta',  1, 1.0E+20),
      array('yotta',  7, 100000000000000000),
      array('',      -6, 1),
      array('test',   6, 1.0E-6),
      array('0',     -3, 1),
      array('5',      2, 1000),
      array('-1',     1, 0.01),
    );
    return $results;
  }

  /**
   * @dataProvider providerSiToScaleValue
   * @group numbers
   */
  public function testSiToScaleValue($value, $scale, $result)
  {
    if (method_exists($this, 'assertEqualsWithDelta')) {
      $this->assertEqualsWithDelta($result, $value * si_to_scale($scale), 0.00001);
    } else {
      $this->assertSame($result, $value * si_to_scale($scale));
    }
  }

  public function providerSiToScaleValue()
  {
    return array(
      array('330',  '-2', 3.3),
      array('1194', '-2', 11.94),
      array('928',  NULL, 928),
      array('9',     '1', 90),
      array('22',    '0', 22),
      array('1194', 'milli', 1.194),
    );
  }

  /**
   * @dataProvider providerFloatCompare
   * @group numbers
   */
  public function testFloatCompare($a, $b, $epsilon, $result)
  {
    $this->assertSame($result, float_cmp($a, $b, $epsilon));
  }

  public function providerFloatCompare()
  {
    return array(
      // numeric tests
      array('330', '-2', NULL,  1), // $a > $b
      array('1',    '2', 0.1,  -1), // $a < $b
      array(-1,      -2, 0.1,   1), // $a > $b
      array(-1.1,  -1.4, 0.5,   0), // $a == $b
      array(-1.1,  -1.4, -0.5,  0), // $a == $b
      array((double)0, (double)70, 0.1, -1), // $a < $b and $a == 0
      array((double)70, (double)0, 0.1,  1), // $a > $b and $b == 0
      array((int)0.001, (double)0, NULL, 0), // $a == $b
      array(0.001,    0.000999999,  0.00001,  0), // $a == $b
      array(-0.001,  -0.000999999,  0.00001,  0), // $a == $b
      array(-0.001,  -0.000899999,  0.00001, -1), // $a < $b
      //array('-0.00000001', 0.00000002, NULL,  0), // $a == $b, FIXME, FALSE
      //array(0.00000002, '-0.00000001', NULL,  0), // $a == $b, FIXME, FALSE
      array(0.2, '-0.000000000001', NULL,  1), // $a == $b
      array(0.99999999, 1.00000002, NULL,  0), // $a == $b
      array(0.001,   -0.000999999,  NULL,  1), // $a > $b
      array(-0.000999999,   0.001,  NULL, -1), // $a < $b
      array(3672,   3888,           0.05,  0), // big numbers, greater epsilon
      array(3888,   3672,           0.05,  0), // big numbers, greater epsilon
      array(4000,   4810,            0.1,  0), // big numbers, greater epsilon
      array(4000,   4000.01,        NULL,  0), // big numbers

      /* Regular large numbers */
      array(1000000,      1000001,  NULL,  0),
      array(1000001,      1000000,  NULL,  0),
      array(10000,          10001,  NULL, -1),
      array(10001,          10000,  NULL,  1),
      /* Negative large numbers */
      array(-1000000,    -1000001,  NULL,  0),
      array(-1000001,    -1000000,  NULL,  0),
      array(-10000,        -10001,  NULL,  1),
      array(-10001,        -10000,  NULL, -1),
      /* Numbers around 1 */
      array(1.0000001,  1.0000002,  NULL,  0),
      array(1.0000002,  1.0000001,  NULL,  0),
      array(1.0002,        1.0001,  NULL,  1),
      array(1.0001,        1.0002,  NULL, -1),
      /* Numbers around -1 */
      array(-1.0000001,-1.0000002,  NULL,  0),
      array(-1.0000002,-1.0000001,  NULL,  0),
      array(-1.0002,      -1.0001,  NULL, -1),
      array(-1.0001,      -1.0002,  NULL,  1),
      /* Numbers between 1 and 0 */
      array(0.000000001000001,   0.000000001000002,  NULL,  0),
      array(0.000000001000002,   0.000000001000001,  NULL,  0),
      array(0.000000000001002,   0.000000000001001,  NULL,  1),
      array(0.000000000001001,   0.000000000001002,  NULL, -1),
      /* Numbers between -1 and 0 */
      array(-0.000000001000001, -0.000000001000002,  NULL,  0),
      array(-0.000000001000002, -0.000000001000001,  NULL,  0),
      array(-0.000000000001002, -0.000000000001001,  NULL, -1),
      array(-0.000000000001001, -0.000000000001002,  NULL,  1),
      /* Comparisons involving zero */
      array(0.0,              0.0,  NULL,  0),
      array(0.0,             -0.0,  NULL,  0),
      array(-0.0,            -0.0,  NULL,  0),
      array(0.00000001,       0.0,  NULL,  1),
      array(0.0,       0.00000001,  NULL, -1),
      array(-0.00000001,      0.0,  NULL, -1),
      array(0.0,      -0.00000001,  NULL,  1),

      array(0.0,     1.0E-10,        0.1,  0),
      array(1.0E-10,     0.0,        0.1,  0),
      array(1.0E-10,     0.0, 0.00000001,  1),
      array(0.0,     1.0E-10, 0.00000001, -1),

      array(0.0,    -1.0E-10,        0.1,  0),
      array(-1.0E-10,    0.0,        0.1,  0),
      array(-1.0E-10,    0.0, 0.00000001, -1),
      array(0.0,    -1.0E-10, 0.00000001,  1),
      /* Comparisons of numbers on opposite sides of 0 */
      array(1.000000001, -1.0,  NULL,  1),
      array(-1.0,   1.0000001,  NULL, -1),
      array(-1.000000001, 1.0,  NULL, -1),
      array(1.0, -1.000000001,  NULL,  1),
      /* Comparisons involving extreme values (overflow potential) */
      array(PHP_INT_MAX,  PHP_INT_MAX,  NULL,  0),
      array(PHP_INT_MAX, -PHP_INT_MAX,  NULL,  1),
      array(-PHP_INT_MAX, PHP_INT_MAX,  NULL, -1),
      array(PHP_INT_MAX,  PHP_INT_MAX / 2, NULL,  1),
      array(PHP_INT_MAX, -PHP_INT_MAX / 2, NULL,  1),
      array(-PHP_INT_MAX, PHP_INT_MAX / 2, NULL, -1),

      // other tests
      array('test',       'milli', 1.194,  1),
      array(array('NULL'),    '0',  0.01,  1),
      array(array('NULL'), array('NULL'), NULL, 0),
    );
  }

    /**
    * @dataProvider providerIntAdd
    * @group numbers
    */
    public function testIntAdd($a, $b, $result) {
        $this->assertSame($result, int_add($a, $b));
    }

    public function providerIntAdd() {
        // $a = "18446742978492891134"; $b = "0"; $sum = gmp_add($a, $b); echo gmp_strval($sum) . "\n"; // Result: 18446742978492891134
        // $a = "18446742978492891134"; $b = "0"; $sum = $a + $b; printf("%.0f\n", $sum);               // Result: 18446742978492891136
        // Accurate math
        return [
            array( '18446742978492891134', '0',  '18446742978492891134'),
            array('-18446742978492891134', '0', '-18446742978492891134'),
            array( '18446742978492891134', '18446742978492891134', '36893485956985782268'),
            array('-18446742978492891134', '18446742978492891134', '0'),

            // Floats
            [ '1111111111111111111111111.6', 0, '1111111111111111111111112' ],
            [ 0, '1111111111111111111111111.6', '1111111111111111111111112' ],
            [ '18446742978492891134.3', '18446742978492891134.6', '36893485956985782269' ],

            // numbers with comma
            [ '7,619,627.6010', 0, '7619628' ],
            [ 0, '7,619,627.6010', '7619628' ],
        ];
    }

    /**
    * @dataProvider providerIntSub
    * @group numbers
    */
    public function testIntSub($a, $b, $result) {
        $this->assertSame($result, int_sub($a, $b));
    }

    public function providerIntSub() {
        // Accurate math
        return [
            array( '18446742978492891134', '0',  '18446742978492891134'),
            array('-18446742978492891134', '0', '-18446742978492891134'),
            array( '18446742978492891134', '18446742978492891134', '0'),
            array('-18446742978492891134', '18446742978492891134', '-36893485956985782268'),

            // Floats
            [ '1111111111111111111111111.6', 0, '1111111111111111111111112' ],
            [ 0, '1111111111111111111111111.6', '-1111111111111111111111112' ],
            [ '-18446742978492891134.3', '18446742978492891134.6', '-36893485956985782269' ],

            // numbers with comma
            [ '7,619,627.6010', 0, '7619628' ],
            [ 0, '7,619,627.6010', '-7619628' ],
        ];
    }

    /**
     * @dataProvider providerFloatDiv
     * @group numbers
     */
    public function testFloatDiv($a, $b, $result) {
        if (method_exists($this, 'assertEqualsWithDelta')) {
            $this->assertEqualsWithDelta($result, float_div($a, $b), 0.00001);
        } else {
            $this->assertSame($result, float_div($a, $b));
        }
    }

    public function providerFloatDiv() {
        // Accurate math
        return [
            array( '18446742978492891134', '0',  0),
            array('-18446742978492891134', '0',  0),
            array( '18446742978492891134', '18446742978492891134',  1.0),
            array('-18446742978492891134', '18446742978492891134', -1.0),

            // Floats
            [ '1111111111111111111111111.6', 0, 0 ],
            [ 0, '1111111111111111111111111.6', 0 ],
            [ '18446742978492891134.3', '18446742978492891134.6', 1.0 ],

            // numbers with comma
            [ '7,619,627.6010', 0, 0 ],
            [ 0, '7,619,627.6010', 0 ],
            [ '1,192.0036', 6.3, 189.20692 ]
        ];
    }

    /**
     * @dataProvider providerHexToFloat
     * @group numbers
     */
    public function testHexToFloat($hex, $result)
    {
        $this->assertSame($result, hex2float($hex));
    }

    public function providerHexToFloat()
    {
        // Accurate math
        $array = [

            [ '429241f0', 73.1287841796875 ],

        ];

        return $array;
    }

    /**
     * @dataProvider providerIeeeIntToFloat
     * @group numbers
     */
    public function testIeeeIntToFloat($int, $result)
    {
        $this->assertSame($result, ieeeint2float($int));
    }

    public function providerIeeeIntToFloat()
    {
        $array = [

            [ 1070575314,          1.6225225925445557 ],
            [ 2998520959,         -2.1629828594882383E-8 ],
            [ '1070575314',        1.6225225925445557 ],
            [ hexdec('429241f0'), 73.1287841796875 ],
            [ 0,                   0.0 ],

        ];

        return $array;
    }

  /**
   * @dataProvider providerIsHexString
   * @group hex
   */
  public function testIsHexString($string, $result)
  {
    $this->assertSame($result, is_hex_string($string));
  }

  public function providerIsHexString()
  {
    $results = array(
      array('49 6E 70 75 74 20 31 00 ', TRUE),
      array('49 6E 70 75 74 20 31 00',  TRUE),
      array('496E707574203100',         FALSE), // SNMP HEX string only with spaces!
      array('49 6E 70 75 74 20 31 0',   FALSE),
      array('Simple String',            FALSE),
      array('49 6E 70 75 74 20 31 0R ', FALSE)
    );
    return $results;
  }

  /**
   * @dataProvider providerStr2Hex
   * @group hex
   */
  public function testStr2Hex($string, $result)
  {
    $this->assertSame($result, str2hex($string));
  }

  public function providerStr2Hex()
  {
    $results = array(
      array(' ',              '20'),
      array('Input 1',        '496e7075742031'),
      array('J}4=',           '4a7d343d'),
      array('Simple String',  '53696d706c6520537472696e67'),
      array('PC$rnu',  '504324726e75'),
      array('Pärnu',   '50c3a4726e75'),
    );
    return $results;
  }

  /**
   * @dataProvider providerMatchOidNum
   * @group oid
   */
  public function testMatchOidNum($oid, $needle, $result)
  {
    $this->assertSame($result, match_oid_num($oid, $needle));
  }

  public function providerMatchOidNum()
  {
    return [
      # true
      [ '.1.3.6.1.4.1.2011.2.27', '1.3.6.1.4.1.2011',        TRUE ],
      [ '1.3.6.1.4.1.2011.2.27',  '.1.3.6.1.4.1.2011',       TRUE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.1.2011',       TRUE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.1.2011.',      TRUE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.1.2011.2.27',  TRUE ],
      # false
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.1.20110',      FALSE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.1.201',        FALSE ],
      [ '.1.3.6.1.4.1.2011.2.27', '3.6.1.4.1.2011.2.27',     FALSE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.1.2011.2.27.', FALSE ],
      # list true
      [ '.1.3.6.1.4.1.2011.2.27', '1.3.6.1.4.1.2011.*',      TRUE ],
      [ '1.3.6.1.4.1.2011.2.27',  '.1.3.6.1.*.1.2011',       TRUE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.(0|4).1.2011*',  TRUE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.[1-5].2011.',  TRUE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.1.*.27',       TRUE ],
      # list false
      [ '.1.3.6.1.4.1.2011.2.27', '1.3.6.1.4.1.2011.3*',     FALSE ],
      [ '1.3.6.1.4.1.2011.2.27',  '.1.3.6.1.4.*.1.2011',     FALSE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.(0|3).1.2011*',  FALSE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.[2-4].2011.',  FALSE ],
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.1.1*.27',      FALSE ],
      # array compare
      [ '.1.3.6.1.4.1.2011.2.27', [ '.1.3.6.1.4.1.20110', '.1.3.6.1.4.1.2011' ],   TRUE ],
      [ '.1.3.6.1.4.1.2011.2.27', [ '.1.3.6.1.4.1.20110', '3.6.1.4.1.2011.2.27' ], FALSE ],
      [ '.1.3.6.1.4.1.2011.2.27', [],                                              FALSE ],
      # incorrect data
      [ '.1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.1.2011..',      FALSE ],
      [ '..1.3.6.1.4.1.2011.2.27', '.1.3.6.1.4.1.2011.',      FALSE ],
      [ '.1.3.6.1.4.1.2011.2.27', 'gg',      FALSE ],
      [ '.1.3.6.1.4.1.2011.2.27', NULL,      FALSE ],
      [ 'as',      '.1.3.6.1.4.1.2011',      FALSE ],
      [ NULL,      '.1.3.6.1.4.1.2011',      FALSE ],
    ];
  }

  /**
  * @dataProvider providerBgpASNumber
  * @group bgp
  */
  public function testBgpASdot($asplain, $asdot, $private)
  {
    $this->assertSame(bgp_asplain_to_asdot($asplain), $asdot);
    $this->assertSame(bgp_asplain_to_asdot($asdot),   $asdot);
  }

  /**
  * @dataProvider providerBgpASNumber
  * @group bgp
  */
  public function testBgpASplain($asplain, $asdot, $private)
  {
    $this->assertSame(bgp_asdot_to_asplain($asdot),   $asplain);
    $this->assertSame(bgp_asdot_to_asplain($asplain), $asplain);
  }

  /**
  * @dataProvider providerBgpASNumber
  * @group bgp
  */
  public function testBgpASprivate($asplain, $asdot, $private)
  {
    $this->assertSame(is_bgp_as_private($asdot),   $private);
    $this->assertSame(is_bgp_as_private($asplain), $private);
  }

  public function providerBgpASNumber()
  {
    return array(
      //         ASplain, ASdot, Private?
      /* 16bit */
      array(         '0',           '0', FALSE),
      array(     '64511',       '64511', FALSE),
      array(     '64512',       '64512', TRUE),
      array(     '65534',       '65534', TRUE),
      array(     '65535',       '65535', TRUE), // This AS not
      /* 32bit */
      array(     '65536',         '1.0', FALSE),
      array(    '327700',        '5.20', FALSE),
      array('4199999999', '64086.59903', FALSE),
      array('4200000000', '64086.59904', TRUE),
      array('4294967294', '65535.65534', TRUE),
      array('4294967295', '65535.65535', TRUE),
    );
  }

  /**
  * @dataProvider providerParseBgpPeerIndex
  * @group bgp
  */
  public function testParseBgpPeerIndex($mib, $index, $result)
  {
    $peer = array();
    parse_bgp_peer_index($peer, $index, $mib);
    $this->assertSame($result, $peer);
  }

  public function providerParseBgpPeerIndex()
  {
    $results = array(
      // IPv4
      array('BGP4-V2-MIB-JUNIPER', '0.1.203.153.47.15.1.203.153.47.207',
            array('jnxBgpM2PeerRoutingInstance' => '0',
                  'jnxBgpM2PeerLocalAddrType'   => 'ipv4',
                  'jnxBgpM2PeerLocalAddr'       => '203.153.47.15',
                  'jnxBgpM2PeerRemoteAddrType'  => 'ipv4',
                  'jnxBgpM2PeerRemoteAddr'      => '203.153.47.207')),
      array('BGP4-V2-MIB-JUNIPER', '47.1.0.0.0.0.1.10.241.224.142',
            array('jnxBgpM2PeerRoutingInstance' => '47',
                  'jnxBgpM2PeerLocalAddrType'   => 'ipv4',
                  'jnxBgpM2PeerLocalAddr'       => '0.0.0.0',
                  'jnxBgpM2PeerRemoteAddrType'  => 'ipv4',
                  'jnxBgpM2PeerRemoteAddr'      => '10.241.224.142')),
      // IPv6
      array('BGP4-V2-MIB-JUNIPER', '0.2.32.1.4.112.0.20.0.101.0.0.0.0.0.0.0.2.2.32.1.4.112.0.20.0.101.0.0.0.0.0.0.0.1',
            array('jnxBgpM2PeerRoutingInstance' => '0',
                  'jnxBgpM2PeerLocalAddrType'   => 'ipv6',
                  'jnxBgpM2PeerLocalAddr'       => '2001:0470:0014:0065:0000:0000:0000:0002',
                  'jnxBgpM2PeerRemoteAddrType'  => 'ipv6',
                  'jnxBgpM2PeerRemoteAddr'      => '2001:0470:0014:0065:0000:0000:0000:0001')),
      // Wrong data
      //array('4a7d343dd',              FALSE),
    );
    return $results;
  }

  /**
  * @dataProvider providerStateStringToNumeric
  * @group states
  */
  public function testStateStringToNumeric($type, $value, $result)
  {
    $this->assertSame($result, state_string_to_numeric($type, $value)); // old without mib
  }

  public function providerStateStringToNumeric() {
    $results = array(
      array('mge-status-state',           'No',              2),
      array('mge-status-state',           'no',              2),
      array('mge-status-state',           'Banana',      FALSE),
      array('inexistent-status-state',    'Vanilla',     FALSE),
      array('radlan-hwenvironment-state', 'notFunctioning',  6),
      array('radlan-hwenvironment-state', 'notFunctioning ', 6),
      array('cisco-envmon-state',         'warning',         2),
      array('cisco-envmon-state',         'war ning',    FALSE),
      array('powernet-sync-state',        'inSync',          1),
      array('power-ethernet-mib-pse-state', 'off',           2),
      // Numeric value
      array('cisco-envmon-state',         '2',               2),
      array('cisco-envmon-state',          2,                2),
      array('cisco-envmon-state',         '2.34',        FALSE),
      array('cisco-envmon-state',          10,           FALSE),
    );
    return $results;
  }

  /**
   * @dataProvider providerStateStringToNumeric2
   * @group states
   */
  public function testStateStringToNumeric2($type, $mib, $value, $result)
  {
    $this->assertSame($result, state_string_to_numeric($type, $value, $mib));
  }

  public function providerStateStringToNumeric2() {
    $results = array(
      // String statuses
      array('status', 'QSAN-SNMP-MIB', 'Checking (0%)',   2), // warning
      array('status', 'QSAN-SNMP-MIB', 'Online',          1), // ok
      array('status', 'QSAN-SNMP-MIB', 'ajhbxsjshab',     3), // alert
    );
    return $results;
  }

  /**
  * @dataProvider providerGetStateArray
  * @group states
  */
  public function testGetStateArray($type, $value, $poller, $result)
  {
    $this->assertSame($result, get_state_array($type, $value, '', NULL, $poller)); // old without know mib
  }

  public function providerGetStateArray()
  {
    $results = array(
      array('mge-status-state',           'No',             'snmp', array('value' => 2, 'name' => 'no', 'event' => 'ok', 'mib' => 'MG-SNMP-UPS-MIB')),
      array('mge-status-state',           'no',             'snmp', array('value' => 2, 'name' => 'no', 'event' => 'ok', 'mib' => 'MG-SNMP-UPS-MIB')),
      array('mge-status-state',           'Banana',         'snmp', array('value' => FALSE)),
      array('inexistent-status-state',    'Vanilla',        'snmp', array('value' => FALSE)),
      array('radlan-hwenvironment-state', 'notFunctioning', 'snmp', array('value' => 6, 'name' => 'notFunctioning', 'event' => 'exclude', 'mib' => 'RADLAN-HWENVIROMENT')),
      array('radlan-hwenvironment-state', 'notFunctioning ','snmp', array('value' => 6, 'name' => 'notFunctioning', 'event' => 'exclude', 'mib' => 'RADLAN-HWENVIROMENT')),
      array('cisco-envmon-state',         'warning',        'snmp', array('value' => 2, 'name' => 'warning', 'event' => 'warning', 'mib' => 'CISCO-ENVMON-MIB')),
      array('cisco-envmon-state',         'war ning',       'snmp', array('value' => FALSE)),
      array('powernet-sync-state',        'inSync',         'snmp', array('value' => 1, 'name' => 'inSync', 'event' => 'ok', 'mib' => 'PowerNet-MIB')),
      array('power-ethernet-mib-pse-state', 'off',          'snmp', array('value' => 2, 'name' => 'off', 'event' => 'ignore', 'mib' => 'POWER-ETHERNET-MIB')),
      // Numeric value
      array('cisco-envmon-state',         '2',              'snmp', array('value' => 2, 'name' => 'warning', 'event' => 'warning', 'mib' => 'CISCO-ENVMON-MIB')),
      array('cisco-envmon-state',          2,               'snmp', array('value' => 2, 'name' => 'warning', 'event' => 'warning', 'mib' => 'CISCO-ENVMON-MIB')),
      array('cisco-envmon-state',         '2.34',           'snmp', array('value' => FALSE)),
      array('cisco-envmon-state',          10,              'snmp', array('value' => FALSE)),
      // agent, ipmi
      array('unix-agent-state',           'warn',          'agent', array('value' => 2, 'name' => 'warn', 'event' => 'warning', 'mib' => '')),
      array('unix-agent-state',           0,               'agent', array('value' => 0, 'name' => 'fail', 'event' => 'alert',   'mib' => '')),
    );
    return $results;
  }

  /**
  * @dataProvider providerGetStateArray2
  * @group states
  */
  public function testGetStateArray2($type, $value, $event_value, $mib, $result)
  {
    $this->assertSame($result, get_state_array($type, $value, $mib, $event_value));
  }

  public function providerGetStateArray2()
  {
    $mib = 'PowerNet-MIB';
    $results = array(
      array('emsInputContactStatusInputContactState', 'contactClosedEMS', 'normallyClosedEMS',   '', array('value' => 1, 'name' => 'contactClosedEMS', 'event' => 'ok',    'mib' => 'PowerNet-MIB')),
      array('emsInputContactStatusInputContactState', 'contactClosedEMS',   'normallyOpenEMS',   '', array('value' => 1, 'name' => 'contactClosedEMS', 'event' => 'alert', 'mib' => 'PowerNet-MIB')),
      array('emsInputContactStatusInputContactState',   'contactOpenEMS', 'normallyClosedEMS',   '', array('value' => 2, 'name' => 'contactOpenEMS',   'event' => 'alert', 'mib' => 'PowerNet-MIB')),
      array('emsInputContactStatusInputContactState',   'contactOpenEMS',   'normallyOpenEMS',   '', array('value' => 2, 'name' => 'contactOpenEMS',   'event' => 'ok',    'mib' => 'PowerNet-MIB')),
      array('emsInputContactStatusInputContactState', 'contactClosedEMS', 'normallyClosedEMS', $mib, array('value' => 1, 'name' => 'contactClosedEMS', 'event' => 'ok',    'mib' => 'PowerNet-MIB')),
      array('emsInputContactStatusInputContactState', 'contactClosedEMS',   'normallyOpenEMS', $mib, array('value' => 1, 'name' => 'contactClosedEMS', 'event' => 'alert', 'mib' => 'PowerNet-MIB')),
      array('emsInputContactStatusInputContactState',   'contactOpenEMS', 'normallyClosedEMS', $mib, array('value' => 2, 'name' => 'contactOpenEMS',   'event' => 'alert', 'mib' => 'PowerNet-MIB')),
      array('emsInputContactStatusInputContactState',   'contactOpenEMS',   'normallyOpenEMS', $mib, array('value' => 2, 'name' => 'contactOpenEMS',   'event' => 'ok',    'mib' => 'PowerNet-MIB')),

    );
    // String statuses
    $mib = 'QSAN-SNMP-MIB';
    $results[] = [ 'status',   'Checking (0%)',   NULL, $mib, [ 'value' => 2, 'name' => 'Checking (0%)', 'event' => 'warning', 'mib' => 'QSAN-SNMP-MIB' ] ];
    return $results;
  }

  /**
  * @dataProvider providerGetBitsStateArray
  * @group states_bits
  */
  /* WiP
  public function testGetBitsStateArray($hex, $mib, $object, $result)
  {
    $this->assertSame($result, get_bits_state_array($hex, $mib, $object));
  }

  public function providerGetBitsStateArray()
  {
    $results = array(
      array('40 00', 'CISCO-STACK-MIB', 'portAdditionalOperStatus', [ 1 => 'connected' ]), // CISCO-STACK-MIB::portAdditionalOperStatus.1.1 = BITS: 40 00 connected(1)
    );
    return $results;
  }
  */

  /**
  * @dataProvider providerGetBitsStateArray2
  * @group states_bits
  */
  /* WiP
  public function testGetBitsStateArray2($hex, $def, $result)
  {
    $this->assertSame($result, get_bits_state_array($hex, NULL, NULL, $def));
  }

  public function providerGetBitsStateArray2()
  {
    $results = array(
      array('40 00', [], [ 1 => 'connected' ]), // CISCO-STACK-MIB::portAdditionalOperStatus.1.1 = BITS: 40 00 connected(1)
    );
    return $results;
  }
  */

  /**
  * @dataProvider providerPriorityStringToNumeric
  */
  public function testPriorityStringToNumeric($value, $result)
  {
    $this->assertSame($result, priority_string_to_numeric($value));
  }

  public function providerPriorityStringToNumeric()
  {
    $results = array(
      // Named value
      array('emerg',    0),
      array('alert',    1),
      array('crit',     2),
      array('err',      3),
      array('warning',  4),
      array('notice',   5),
      array('info',     6),
      array('debug',    7),
      array('DeBuG',    7),
      // Numeric value
      array('0',        0),
      array('7',        7),
      array(8,          8),
      // Wrong value
      array('some',    15),
      array(array(),   15),
      array(0.1,       15),
      array('100',     15),
    );
    return $results;
  }

  /**
  * @dataProvider providerArrayMergeIndexed
  * @group array
  */
  public function testArrayMergeIndexed($result, $array1, $array2)
  {

    $this->assertSame($result, array_merge_indexed($array1, $array2));
    //if ($array3 == NULL)
    //{
    //  $this->assertSame($result, array_merge_indexed($array1, $array2));
    //} else {
    //  $this->assertSame($result, array_merge_indexed($array1, $array2, $array3));
    //}
  }

  public function providerArrayMergeIndexed()
  {
    $results = array(
      array( // Simple 2 array test with NULL
             array( // Result
                    1 => array('Test2' => 'Foo', 'Test3' => 'Bar'),
                    2 => array('Test2' => 'Qux'),
             ),
             NULL,
             array( // Array 2
                    1 => array('Test2' => 'Foo', 'Test3' => 'Bar'),
                    2 => array('Test2' => 'Qux'),
             ),
      ),
      array( // Simple 2 array test
        array( // Result
          1 => array('Test1' => 'Moo', 'Test2' => 'Foo', 'Test3' => 'Bar'),
          2 => array('Test1' => 'Baz', 'Test4' => 'Bam', 'Test2' => 'Qux'),
        ),
        array( // Array 1
          1 => array('Test1' => 'Moo'),
          2 => array('Test1' => 'Baz', 'Test4' => 'Bam'),
          ),
        array( // Array 2
          1 => array('Test2' => 'Foo', 'Test3' => 'Bar'),
          2 => array('Test2' => 'Qux'),
        ),
      ),
      array( // Simple 3 array test
        array( // Result
          1 => array('Test1' => 'Moo', 'Test2' => 'Foo'), //, 'Test3' => 'Bar'),
          2 => array('Test1' => 'Baz', 'Test4' => 'Bam', 'Test2' => 'Qux'),
        ),
        array( // Array 1
          1 => array('Test1' => 'Moo'),
          2 => array('Test1' => 'Baz', 'Test4' => 'Bam'),
          ),
        array( // Array 2
          1 => array('Test2' => 'Foo'),
          2 => array('Test2' => 'Qux'),
        ),
        //array( // Array 3
        //  1 => array('Test3' => 'Bar'),
        //  2 => array('Test2' => 'Qux'),
        //),
      array( // Partial overwrite by array 2
        array( // Result
          1 => array('Test1' => 'Moo', 'Test2' => 'Foo', 'Test3' => 'Bar'),
          2 => array('Test1' => 'Baz', 'Test4' => 'Bam', 'Test2' => 'Qux'),
        ),
        array( // Array 1
          1 => array('Test1' => 'Moo', 'Test2' => '000', 'Test3' => '666'),
          2 => array('Test1' => 'Baz', 'Test4' => 'Bam'),
          ),
        array( // Array 2
          1 => array('Test2' => 'Foo', 'Test3' => 'Bar'),
          2 => array('Test2' => 'Qux'),
        ),
      ),
      ),
    );

    return $results;
  }

  /**
   * @dataProvider providerHex2IP
   * @group ip
   */
  public function testHex2IP($string, $result)
  {
    $this->assertSame($result, hex2ip($string));
  }

  public function providerHex2IP()
  {
    $results = array(
      // IPv4
      array('C1 9C 5A 26',  '193.156.90.38'),
      array('4a7d343d',     '74.125.52.61'),
      array('207d343d',     '32.125.52.61'),
      // cisco IPv4
      array('54 2E 68 02 FF FF FF FF ', '84.46.104.2'),
      array('90 7F 8A ',    '144.127.138.0'), // should be '90 7F 8A 00 '
      // IPv4 (converted to snmp string)
      array('J}4=',         '74.125.52.61'),
      array('J}4:',         '74.125.52.58'),
      // with newline
      array('
^KL=', '94.75.76.61'),
      // with first space char (possible for OBS_SNMP_CONCAT)
      array(' ^KL=',        '94.75.76.61'),
      array('  KL=',        '32.75.76.61'),
      array('    ',         '32.32.32.32'),
      // hex string
      array('31 38 35 2E 31 39 2E 31 30 30 2E 31 32 ', '185.19.100.12'),
      // IPv6
      array('20 01 07 F8 00 12 00 01 00 00 00 00 00 05 02 72',  '2001:07f8:0012:0001:0000:0000:0005:0272'),
      array('20:01:07:F8:00:12:00:01:00:00:00:00:00:05:02:72',  '2001:07f8:0012:0001:0000:0000:0005:0272'),
      array('200107f8001200010000000000050272',                 '2001:07f8:0012:0001:0000:0000:0005:0272'),
      // IPv6z
      //array('20 01 07 F8 00 12 00 01 00 00 00 00 00 05 02 72',  '2001:07f8:0012:0001:0000:0000:0005:0272'),
      array('2a:02:a0:10:80:03:00:00:00:00:00:00:00:00:00:01%503316482',  '2a02:a010:8003:0000:0000:0000:0000:0001'),
      //array('200107f8001200010000000000050272',                 '2001:07f8:0012:0001:0000:0000:0005:0272'),
      // Wrong data
      array('4a7d343dd',                        '4a7d343dd'),
      array('200107f800120001000000000005027',  '200107f800120001000000000005027'),
      array('193.156.90.38',                    '193.156.90.38'),
      array('Simple String',                    'Simple String'),
      array('',  ''),
      array(FALSE,  FALSE),
    );
    return $results;
  }

  /**
   * @dataProvider providerIp2Hex
   * @group ip
   */
  public function testIp2Hex($string, $separator, $result)
  {
    $this->assertSame($result, ip2hex($string, $separator));
  }

  public function providerIp2Hex()
  {
    $results = array(
      // IPv4
      array('193.156.90.38', ' ', 'c1 9c 5a 26'),
      array('74.125.52.61',  ' ', '4a 7d 34 3d'),
      array('74.125.52.61',   '', '4a7d343d'),
      // IPv6
      array('2001:07f8:0012:0001:0000:0000:0005:0272', ' ', '20 01 07 f8 00 12 00 01 00 00 00 00 00 05 02 72'),
      array('2001:7f8:12:1::5:0272',                   ' ', '20 01 07 f8 00 12 00 01 00 00 00 00 00 05 02 72'),
      array('2001:7f8:12:1::5:0272',                    '', '200107f8001200010000000000050272'),
      // Wrong data
      array('4a7d343dd',                       NULL, '4a7d343dd'),
      array('200107f800120001000000000005027', NULL, '200107f800120001000000000005027'),
      array('300.156.90.38',                   NULL, '300.156.90.38'),
      array('Simple String',                   NULL, 'Simple String'),
      array('',    NULL, ''),
      array(FALSE, NULL, FALSE),
    );
    return $results;
  }

  /**
   * @dataProvider providerGetIpVersion
   * @group ip
   */
  public function testGetIpVersion($string, $result)
  {
    $this->assertSame($result, get_ip_version($string));
  }

  public function providerGetIpVersion()
  {
    $results = array(
      // IPv4
      array('193.156.90.38',    4),
      array('32.125.52.61',     4),
      array('127.0.0.1',        4),
      array('0.0.0.0',          4),
      array('255.255.255.255',  4),
      // IPv6
      array('ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff',  6),
      array('2001:07f8:0012:0001:0000:0000:0005:0272',  6),
      array('2001:7f8:12:1::5:0272',                    6),
      array('::1',                                      6),
      array('::',                                       6),
      array('::ffff:192.0.2.128',                       6), // IPv4 mapped to IPv6
      array('2002:c000:0204::',                         6), // 6to4 address 192.0.2.4
      // Wrong data
      array('4a7d343dd',              FALSE),
      array('my.domain.name',         FALSE),
      array('256.156.90.38',          FALSE),
      array('1.1.1.1.1',              FALSE),
      array('2001:7f8:12:1::5:0272f', FALSE),
      array('gggg:7f8:12:1::5:272f',  FALSE),
      //array('2002::',                 FALSE), // 6to4 address, must be full
      array('',                       FALSE),
      array(FALSE,                    FALSE),
      // IP with mask also wrong!
      array('193.156.90.38/32',           FALSE),
      array('2001:7f8:12:1::5:0272/128',  FALSE),
    );
    return $results;
  }

  /**
  * @dataProvider providerParseNetwork
  * @group ip
  */
  public function testParseNetwork($network, $result)
  {
    $test = parse_network($network);
    if (is_array($test))   { ksort($test); }
    unset($test['address_binary'], $test['network_start_binary'], $test['network_end_binary']);
    if (is_array($result)) { ksort($result); }
    $this->assertSame($result, $test);
  }

  public function providerParseNetwork()
  {
    $array = array();

    // Valid IPv4
    $array[] = array('10.0.0.0/8',   array('query_type' => 'network', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '10.0.0.0', 'prefix' => '8',
                                           'network_start' => '10.0.0.0', 'network_end' => '10.255.255.255', 'network' => '10.0.0.0/8'));
    $array[] = array('10.0.0.0/255.255.255.0', array('query_type' => 'network', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '10.0.0.0', 'prefix' => '24',
                                           'network_start' => '10.0.0.0', 'network_end' => '10.0.0.255', 'network' => '10.0.0.0/24'));
    $array[] = array('10.12.0.3/8',  array('query_type' => 'network', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '10.12.0.3', 'prefix' => '8',
                                           'network_start' => '10.0.0.0', 'network_end' => '10.255.255.255', 'network' => '10.0.0.0/8'));
    $array[] = array('10.12.0.3/255.0.0.0', array('query_type' => 'network', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '10.12.0.3', 'prefix' => '8',
                                           'network_start' => '10.0.0.0', 'network_end' => '10.255.255.255', 'network' => '10.0.0.0/8'));
    // Inverse mask
    $array[] = array('10.12.0.3/0.0.0.255', array('query_type' => 'network', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '10.12.0.3', 'prefix' => '24',
                                           'network_start' => '10.12.0.0', 'network_end' => '10.12.0.255', 'network' => '10.12.0.0/24'));
    $array[] = array('10.12.0.3',    array('query_type' => 'single', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '10.12.0.3', 'prefix' => '32',
                                           'network_start' => '10.12.0.3', 'network_end' => '10.12.0.3', 'network' => '10.12.0.3/32'));
    $array[] = array('10.12.0.3/32', array('query_type' => 'single', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '10.12.0.3', 'prefix' => '32',
                                           'network_start' => '10.12.0.3', 'network_end' => '10.12.0.3', 'network' => '10.12.0.3/32'));
    $array[] = array('10.12.0.3/0', array('query_type' => 'network', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '10.12.0.3', 'prefix' => '0',
                                           'network_start' => '0.0.0.0', 'network_end' => '255.255.255.255', 'network' => '0.0.0.0/0'));
    $array[] = array('0.0.0.0/0',   array('query_type' => 'network', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '0.0.0.0', 'prefix' => '0',
                                           'network_start' => '0.0.0.0', 'network_end' => '255.255.255.255', 'network' => '0.0.0.0/0'));
    $array[] = array('*.12.0.3',     array('query_type' => 'like', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '*.12.0.3'));
    $array[] = array('10.?.?.3',     array('query_type' => 'like', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '10.?.?.3'));
    $array[] = array('10.12',        array('query_type' => '%like%', 'ip_version' => 4, 'ip_type' => 'ipv4',
                                           'address' => '10.12'));
    // Valid IPv6
    $array[] = array('1762:0:0:0:0:B03:1:AF18/99', array('query_type' => 'network', 'ip_version' => 6, 'ip_type' => 'ipv6',
                                           'address' => '1762:0:0:0:0:B03:1:AF18', 'prefix' => '99',
                                           'network_start' => '1762:0:0:0:0:b03:0:0', 'network_end' => '1762:0:0:0:0:b03:1fff:ffff', 'network' => '1762:0:0:0:0:b03:0:0/99'));
    $array[] = array('1762:0:0:0:0:b03:0:0/99',    array('query_type' => 'network', 'ip_version' => 6, 'ip_type' => 'ipv6',
                                           'address' => '1762:0:0:0:0:b03:0:0', 'prefix' => '99',
                                           'network_start' => '1762:0:0:0:0:b03:0:0', 'network_end' => '1762:0:0:0:0:b03:1fff:ffff', 'network' => '1762:0:0:0:0:b03:0:0/99'));
    $array[] = array('1762:0:0:0:0:B03:1:AF18',    array('query_type' => 'single', 'ip_version' => 6, 'ip_type' => 'ipv6',
                                           'address' => '1762:0:0:0:0:B03:1:AF18', 'prefix' => '128',
                                           'network_start' => '1762:0:0:0:0:B03:1:AF18', 'network_end' => '1762:0:0:0:0:B03:1:AF18', 'network' => '1762:0:0:0:0:B03:1:AF18/128'));
    $array[] = array('::ffff:192.0.2.47/127', array('query_type' => 'network', 'ip_version' => 6, 'ip_type' => 'ipv6',
                                           'address' => '::ffff:192.0.2.47', 'prefix' => '127',
                                           'network_start' => '0:0:0:0:0:ffff:c000:22e', 'network_end' => '0:0:0:0:0:ffff:c000:22f', 'network' => '0:0:0:0:0:ffff:c000:22e/127'));
    //$array[] = array('2001:0002:6c::430/48', array('query_type' => 'network', 'ip_version' => 6, 'ip_type' => 'ipv6',
    //                                       'address' => '::ffff:192.0.2.47', 'prefix' => '48',
    //                                       'network_start' => '0:0:0:0:0:ffff:c000:22e', 'network_end' => '0:0:0:0:0:ffff:c000:22f', 'network' => '0:0:0:0:0:ffff:c000:22e/48'));
    $array[] = array('1762:0:0:0:0:B03:1:AF18/128', array('query_type' => 'single', 'ip_version' => 6, 'ip_type' => 'ipv6',
                                           'address' => '1762:0:0:0:0:B03:1:AF18', 'prefix' => '128',
                                           'network_start' => '1762:0:0:0:0:B03:1:AF18', 'network_end' => '1762:0:0:0:0:B03:1:AF18', 'network' => '1762:0:0:0:0:B03:1:AF18/128'));
    $array[] = array('1762:0:0:0:0:B03:1:AF18/0', array('query_type' => 'network', 'ip_version' => 6, 'ip_type' => 'ipv6',
                                           'address' => '1762:0:0:0:0:B03:1:AF18', 'prefix' => '0',
                                           'network_start' => '0:0:0:0:0:0:0:0', 'network_end' => 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff', 'network' => '0:0:0:0:0:0:0:0/0'));
    $array[] = array('::/0',               array('query_type' => 'network', 'ip_version' => 6, 'ip_type' => 'ipv6',
                                           'address' => '::', 'prefix' => '0',
                                           'network_start' => '0:0:0:0:0:0:0:0', 'network_end' => 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff', 'network' => '0:0:0:0:0:0:0:0/0'));
    $array[] = array('1762::*:AF18',       array('query_type' => 'like', 'ip_version' => 6, 'ip_type' => 'ipv6',
                                           'address' => '1762::*:AF18'));
    $array[] = array('?::B03:1:AF18',      array('query_type' => 'like', 'ip_version' => 6, 'ip_type' => 'ipv6',
                                           'address' => '?::B03:1:AF18'));
    $array[] = array('1762:b03',           array('query_type' => '%like%', 'ip_version' => 6, 'ip_type' => 'ipv6',
                                           'address' => '1762:b03'));

    return $array;
  }

  /**
  * @dataProvider providerGetIpType
  * @group ip
  */
  public function testGetIpType($ip, $result)
  {
    $this->assertSame($result, get_ip_type($ip));
  }

  public function providerGetIpType()
  {
    return array(
      array('0.0.0.0',                  'unspecified'),
      array('::',                       'unspecified'),
      array('10.255.255.255/32',        'private'), // Do not set /31 and /32 as broadcast!
      array('10.255.255.255/31',        'private'), // Do not set /31 and /32 as broadcast!
      array('10.255.255.255/8',         'broadcast'),
      array('127.0.0.1',                'loopback'),
      array('::1',                      'loopback'),
      array('0:0:0:0:0:0:0:1/128',      'loopback'),
      array('10.12.0.3',                'private'),
      array('172.16.1.1',               'private'),
      array('192.168.0.3',              'private'),
      array('fdf8:f53b:82e4::53',       'private'),
      array('100.80.76.30',             'cgnat'),
      array('0:0:0:0:0:ffff:c000:22f',  'ipv4mapped'),
      array('::ffff:192.0.2.47',        'ipv4mapped'),
      array('77.222.50.30',             'unicast'),
      array('2a02:408:7722:5030::5030', 'unicast'),
      array('169.254.2.47',             'link-local'),
      array('fe80::200:5aee:feaa:20a2', 'link-local'),
      array('2001:0000:4136:e378:8000:63bf:3fff:fdd2', 'teredo'),
      array('198.18.0.1',               'benchmark'),
      array('2001:0002:0:6C::430',      'benchmark'),
      array('2001:10:240:ab::a',        'orchid'),
      array('1:0002:6c::430',           'reserved'),
      array('ff02::1:ff8b:4d51/0',      'multicast'),
    );
  }

  /**
  * @dataProvider providerMatchNetwork
  * @group ip
  */
  public function testMatchNetwork($result, $ip, $nets, $first = FALSE)
  {
    $this->assertSame($result, match_network($ip, $nets, $first));
  }

  public function providerMatchNetwork()
  {
    $nets1 = array('127.0.0.0/8', '192.168.0.0/16', '10.0.0.0/8', '172.16.0.0/12', '!172.16.6.7/32');
    $nets2 = array('fe80::/16', '!fe80:ffff:0:ffff:1:144:52:0/112', '192.168.0.0/16', '172.16.0.0/12', '!172.16.6.7/32');
    $nets3 = array('fe80::/16', 'fe80:ffff:0:ffff:1:144:52:0/112', '!fe80:ffff:0:ffff:1:144:52:0/112');
    $nets4 = array('172.16.0.0/12', '!172.16.6.7');
    $nets5 = array('fe80::/16', '!FE80:FFFF:0:FFFF:1:144:52:38');
    $nets6 = "I'm a stupid";
    $nets7 = array('::ffff/96', '2001:0002:6c::/48');
    $nets8 = array("10.11.1.0/24",  "10.11.2.0/24",  "10.11.11.0/24", "10.11.12.0/24", "10.11.21.0/24", "10.11.22.0/24",
                   "10.11.30.0/23", "10.11.32.0/24", "10.11.33.0/24", "10.11.34.0/24", "10.11.41.0/24", "10.11.42.0/24",
                   "10.11.43.0/24", "10.11.51.0/24", "10.11.52.0/24", "10.11.53.0/24", "10.11.61.0/24", "10.11.62.0/24");

    return array(
      // Only IPv4 nets
      array(TRUE,  '127.0.0.1',  $nets1),
      array(FALSE, '1.1.1.1',    $nets1),       // not in ranges
      array(TRUE,  '172.16.6.6', $nets1),
      array(FALSE, '172.16.6.7', $nets1),       // excluded net
      array(TRUE,  '172.16.6.7', $nets1, TRUE), // excluded, but first match
      array(FALSE, '256.16.6.1', $nets1),       // wrong IP
      // Both IPv4 and IPv6
      array(FALSE, '1.1.1.1',    $nets2),
      array(TRUE,  '172.16.6.6', $nets2),
      array(TRUE,  'FE80:FFFF:0:FFFF:129:144:52:38', $nets2),
      array(FALSE, 'FE81:FFFF:0:FFFF:129:144:52:38', $nets2), // not in ranges
      array(FALSE, 'FE80:FFFF:0:FFFF:1:144:52:38',   $nets2), // excluded net
      // Only IPv6 nets
      array(FALSE, '1.1.1.1',    $nets3),
      array(FALSE, '172.16.6.6', $nets3),
      array(TRUE,  'FE80:FFFF:0:FFFF:129:144:52:38', $nets3),
      //array(TRUE,  '2001:0002:0:6c::430',            $nets7),
      array(FALSE, 'FE81:FFFF:0:FFFF:129:144:52:38', $nets3),
      array(FALSE, 'FE80:FFFF:0:FFFF:1:144:52:38',   $nets3),
      array(TRUE,  'FE80:FFFF:0:FFFF:1:144:52:38',   $nets3, TRUE), // excluded, but first match
      // IPv4 net without mask
      array(TRUE,  '172.16.6.6', $nets4),
      array(FALSE, '172.16.6.7', $nets4),       // excluded net
      // IPv6 net without mask
      array(TRUE,  'FE80:FFFF:0:FFFF:129:144:52:38', $nets5),
      array(FALSE, 'FE81:FFFF:0:FFFF:129:144:52:38', $nets5),
      array(FALSE, 'FE80:FFFF:0:FFFF:1:144:52:38',   $nets5),
      array(TRUE,  'FE80:FFFF:0:FFFF:1:144:52:38',   $nets5, TRUE), // excluded, but first match
      // IPv6 IPv4 mapped
      array(TRUE,  '::ffff:192.0.2.47', $nets7),
      // Are you stupid? YES :)
      array(FALSE, '172.16.6.6', $nets6),
      array(FALSE, 'FE80:FFFF:0:FFFF:129:144:52:38', $nets6),
      // Issues test
      array(FALSE, '10.52.25.254', $nets8),
      array(TRUE,  '10.52.25.254',  [ '!217.66.159.18' ]),
      array(FALSE, '217.66.159.18', [ '!217.66.159.18' ]),
      array(TRUE,  '217.66.159.18', [ '217.66.159.18' ]),
    );
  }

  /**
   * @dataProvider providerStringQuoted
   * @group string
   */
  /* WIP
  public function testStringQuoted($string, $result)
  {
    $this->assertSame($result, is_string_quoted($string));
  }

  public function providerStringQuoted()
  {
    return array(
      array('\"sdfslfkm s\'fdsf" a;lm aamjn ',          FALSE),
      array('sdfslfkm s\'fdsf" a;lm aamjn \"',          FALSE),
      array('sdfslfkm s\'fdsf" a;lm aamjn ',            FALSE),
      array('\"sdfslfkm s\'fdsf" a;lm aamjn \"',        TRUE),
      array('"sdfslfkm s\'fdsf" a;lm aamjn "',          TRUE),
      array('"\"sdfslfkm s\'fdsf" a;lm aamjn \""',      TRUE),
      array('\'\"sdfslfkm s\'fdsf" a;lm aamjn \"\'',    TRUE),
      array('  \'\"sdfslfkm s\'fdsf" a;lm aamjn \"\' ', TRUE),
      array('"""sdfslfkm s\'fdsf" a;lm aamjn """',      TRUE),
      array('"""sdfslfkm s\'fdsf" a;lm aamjn """"""""', TRUE),
      array('"""""""sdfslfkm s\'fdsf" a;lm aamjn """',  TRUE),
      // escaped quotes
      array('\"Mike Stupalov\" <mike@observium.org>',   FALSE),
      // utf-8
      array('Avenue Léon, België ',                     FALSE),
      array('\"Avenue Léon, België \"',                 TRUE),
      array('"Винни пух и все-все-все "',               TRUE),
      // multilined
      array('  \'\"\"sdfslfkm s\'fdsf"
            a;lm aamjn \"\"\' ',                        TRUE),
    );
  }
  */

  /**
  * @dataProvider providerStringTransform
  * @group string
  */
  public function testStringTransform($result, $string, $transformations)
  {
    $this->assertSame($result, string_transform($string, $transformations));
  }

  public function providerStringTransform()
  {
    $results = array(
      // Append
      array('Bananarama',     'Banana',          array(
                                                   array('action' => 'append', 'string' => 'rama')
                                                 )),
      array('Bananarama',     'Banana',          array(
                                                   array('action' => 'append', 'string' => 'ra'),
                                                   array('action' => 'append', 'string' => 'ma')
                                                 )),
      // Prepend
      array('Benga boys',     'boys',            array(
                                                   array('action' => 'prepend', 'string' => 'Benga ')
                                                 )),
      // Replace
      array('Observium',      'ObserverNMS',     array(
                                                   array('action' => 'replace', 'from' => 'erNMS', 'to' => 'ium')
                                                 )),
      array('ObserverNMS',    'ObserverNMS',     array(
                                                   array('action' => 'replace', 'from' => 'ernms', 'to' => 'ium')
                                                 )),
      // Case Insensitive Replace
      array('Observium',      'ObserverNMS',     array(
                                                   array('action' => 'ireplace', 'from' => 'erNMS', 'to' => 'ium')
                                                 )),
      array('Observium',      'ObserverNMS',     array(
                                                   array('action' => 'ireplace', 'from' => 'ernms', 'to' => 'ium')
                                                 )),
      // Regex Replace
      array('1.46.82', 'CS141-SNMP V1.46.82 161207', array(
                                                   array('action' => 'regex_replace', 'from' => '/CS1\d1\-SNMP V(\d\S+).*/', 'to' => '$1')
                                                 )),
      // Regex Replace
      array('1.46.82', 'CS141-SNMP V1.46.82 161207', array(
                                                   array('action' => 'preg_replace', 'from' => '/CS1\d1\-SNMP V(\d\S+).*/', 'to' => '$1')
                                                 )),
      // Regex Replace (missed delimiters)
      array('1.46.82', 'CS141-SNMP V1.46.82 161207', array(
                                                   array('action' => 'preg_replace', 'from' => 'CS1\d1\-SNMP V(\d\S+).*', 'to' => '$1')
      )),
      // Regex Replace (to empty)
      array('', 'FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF FF', array(
                                                   array('action' => 'preg_replace', 'from' => '/^FF( FF)*$/', 'to' => '')
      )),
      // Regex Replace (not match)
      array('CS141-SNMP', 'CS141-SNMP',          array(
                                                   array('action' => 'preg_replace', 'from' => '/CS1\d1\-SNMP V(\d\S+).*/', 'to' => '$1')
                                                 )),
      // Trim
      array('OOObservium',    'oooOOObserviumo', array(
                                                   array('action' => 'trim', 'characters' => 'o')
                                                 )),
      // LTrim
      array('OOObserviumo',   'oooOOObserviumo', array(
                                                   array('action' => 'ltrim', 'characters' => 'o')
                                                 )),
      // RTrim
      array('oooOOObservium', 'oooOOObserviumo', array(
                                                   array('action' => 'rtrim', 'characters' => 'o')
                                                 )),
      // MAP
      array('oooOOObserviumo', 'oooOOObservium', array(
                                                   array('action' => 'map', 'map' => [ 'oooOOObservium' => 'oooOOObserviumo' ])
      )),
      array('oooOOO', 'oooOOO', array(
        array('action' => 'map', 'map' => [ 'oooOOObservium' => 'oooOOObserviumo' ])
      )),
      // MAp by regex
      array('oooOOObserviumo', 'ooo3748yhrfnhnd3', array(
        array('action' => 'map_match', 'map' => [ '/^ooo/' => 'oooOOObserviumo' ])
      )),
      array('ooo3748yhrfnhnd3', 'ooo3748yhrfnhnd3', array(
        array('action' => 'map_match', 'map' => [ '/^xoo/' => 'oooOOObserviumo' ])
      )),

      // Timeticks
      array(15462419, '178:23:06:59.03', array(
                                                   array('action' => 'timeticks')
                                                 )),

      // BGP 32bit ASdot
      array('327700', '5.20', array(
                                                   array('action' => 'asdot')
                                                 )),

      // Explode (defaults - delimiter: " ", index: first)
      array('1.6', '1.6 Build 13120415', array(
                                                   array('action' => 'explode')
                                                 )),
      array('1.6', '1.6 Build 13120415', array(
                                                   array('action' => 'explode', 'delimiter' => ' ', 'index' => 'first')
                                                 )),
      array('1.6', '1.6 Build 13120415', array(
                                                   array('action' => 'explode', 'delimiter' => ' ', 'index' => 0)
                                                 )),
      array('13120415', '1.6 Build 13120415', array(
                                                   array('action' => 'explode', 'delimiter' => ' ', 'index' => 'end')
                                                 )),
      array('Build', '1.6 Build 13120415', array(
                                                   array('action' => 'explode', 'delimiter' => ' ', 'index' => 1)
                                                 )),
      array('6 Build 13120415', '1.6 Build 13120415', array(
                                                   array('action' => 'explode', 'delimiter' => '.', 'index' => 1)
                                                 )),
      // (unknown index)
      array('1.6 Build 13120415', '1.6 Build 13120415', array(
                                                   array('action' => 'explode', 'delimiter' => '.', 'index' => 10)
                                                 )),


      // Single action with less array nesting
      array('1.46.82', 'CS141-SNMP V1.46.82 161207', array('action' => 'preg_replace', 'from' => '/CS1\d1\-SNMP V(\d\S+).*/', 'to' => '$1')),
      array('327700', '5.20',                        array('action' => 'asdot')),

      // Combinations, to be done in exact order, including no-ops
      array('Observium',      'oooOOOKikkero',   array(
                                                   array('action' => 'trim', 'characters' => 'o'),
                                                   array('action' => 'ltrim', 'characters' => 'O'),
                                                   array('action' => 'rtrim', 'characters' => 'F'),
                                                   array('action' => 'replace', 'from' => 'Kikker', 'to' => 'ObserverNMS'),
                                                   array('action' => 'replace', 'from' => 'erNMS', 'to' => 'ium')
                                                 )),
    );
    return $results;
  }

  /**
  * @dataProvider providerStringSimilar
  * @group string
  */
  public function testStringSimilar($result, $string1, $string2)
  {
    $this->assertSame($result, str_similar($string1, $string2));
    $this->assertSame($result, str_similar($string2, $string1));
  }

  public function providerStringSimilar()
  {
    return array(
      array('Intel Xeon E5430 @ 2.66GH', '0/0/0 Intel Xeon E5430 @ 2.66GH', '0/1/0 Intel Xeon E5430 @ 2.66GH'),
      array('Intel Xeon E5430 @',        '0/0/0 Intel Xeon E5430 @ 2.66GH', '0/1/0 Intel Xeon E5430 @ 2.66G'),
      array('Network Processor',         'Network Processor CPU8', 'Network Processor CPU31'),
      array('',                          'Network Processor CPU8', 'Supervisor Card CPU'),
    );
  }

  /**
  * @dataProvider providerFindSimilar
  * @group string
  */
  public function testFindSimilar($result, $result_flip, $array)
  {
    shuffle($array); // Randomize array for more natural test

    $this->assertSame($result,      find_similar($array));
    $this->assertSame($result_flip, find_similar($array, TRUE));
  }

  public function providerFindSimilar()
  {
    $array1 = ['0/0/0 Intel Xeon E5430 @ 2.66GHz', '0/1/0 Intel Xeon E5430 @ 2.66GHz', '0/10/0 Intel Xeon E5430 @ 2.66GHz',
               'Supervisor Card CPU',
               'Network Processor CPU8', 'Network Processor CPU31'];
    $array2 = ['0/0/0 Intel Xeon E5430 @ 2.66GH', '0/1/0 Intel Xeon E5430 @ 2.66GH', '0/10/0 Intel Xeon E5430 @ 2.66G'];
    $array3 = ['Slot 1 BR-MLX-10Gx8-X [1]', 'Slot 2 BR-MLX-10Gx8-X [1]',
               'Slot 4 BR-MLX-1GFx24-X [1]',
               'Slot 5 BR-MLX-MR2-X [1]', 'Slot 6 BR-MLX-MR2-X [1]'];

    return array(
      array(['Intel Xeon E5430 @ 2.66GHz' => ['0/0/0 Intel Xeon E5430 @ 2.66GHz', '0/1/0 Intel Xeon E5430 @ 2.66GHz', '0/10/0 Intel Xeon E5430 @ 2.66GHz'],
             'Network Processor'          => ['Network Processor CPU8', 'Network Processor CPU31'],
             'Supervisor Card CPU'        => ['Supervisor Card CPU']
            ],
            ['0/0/0 Intel Xeon E5430 @ 2.66GHz' => 'Intel Xeon E5430 @ 2.66GHz',
             '0/1/0 Intel Xeon E5430 @ 2.66GHz' => 'Intel Xeon E5430 @ 2.66GHz',
             '0/10/0 Intel Xeon E5430 @ 2.66GHz' => 'Intel Xeon E5430 @ 2.66GHz',
             'Network Processor CPU8' => 'Network Processor',
             'Network Processor CPU31' => 'Network Processor',
             'Supervisor Card CPU' => 'Supervisor Card CPU'
            ],
            $array1),
      array(['Intel Xeon E5430 @ 2.66GH' => ['0/0/0 Intel Xeon E5430 @ 2.66GH', '0/1/0 Intel Xeon E5430 @ 2.66GH', '0/10/0 Intel Xeon E5430 @ 2.66G']],
            ['0/0/0 Intel Xeon E5430 @ 2.66GH' => 'Intel Xeon E5430 @ 2.66GH',
             '0/1/0 Intel Xeon E5430 @ 2.66GH' => 'Intel Xeon E5430 @ 2.66GH',
             '0/10/0 Intel Xeon E5430 @ 2.66G' => 'Intel Xeon E5430 @ 2.66GH'
            ],
            $array2),
      array(['Slot BR-MLX-10Gx8-X [1]'    => ['Slot 1 BR-MLX-10Gx8-X [1]', 'Slot 2 BR-MLX-10Gx8-X [1]'],
             'Slot 4 BR-MLX-1GFx24-X [1]' => ['Slot 4 BR-MLX-1GFx24-X [1]'],
             'Slot BR-MLX-MR2-X [1]'      => ['Slot 5 BR-MLX-MR2-X [1]', 'Slot 6 BR-MLX-MR2-X [1]']
            ],
            ['Slot 1 BR-MLX-10Gx8-X [1]'  => 'Slot BR-MLX-10Gx8-X [1]',
             'Slot 2 BR-MLX-10Gx8-X [1]'  => 'Slot BR-MLX-10Gx8-X [1]',
             'Slot 4 BR-MLX-1GFx24-X [1]' => 'Slot 4 BR-MLX-1GFx24-X [1]',
             'Slot 5 BR-MLX-MR2-X [1]'    => 'Slot BR-MLX-MR2-X [1]',
             'Slot 6 BR-MLX-MR2-X [1]'    => 'Slot BR-MLX-MR2-X [1]'
            ],
            $array3),
    );
  }
  /**
  * @dataProvider providerIsPingable
  * @group network
  */
  public function testIsPingable($result, $hostname, $try_a = TRUE)
  {
    if (!is_executable($GLOBALS['config']['fping']))
    {
      // CentOS 6.8
      $GLOBALS['config']['fping']  = '/usr/sbin/fping';
      $GLOBALS['config']['fping6'] = '/usr/sbin/fping6';
    }
    $flags = OBS_DNS_ALL;
    if (!$try_a) { $flags ^= OBS_DNS_A; }
    $ping = is_pingable($hostname, $flags);
    $ping = is_numeric($ping) && $ping > 0; // Function returns random float number
    $this->assertSame($result, $ping);
  }

  public function providerIsPingable()
  {
    $array = array(
      array(TRUE,  'localhost'),
      array(TRUE,  '127.0.0.1'),
      array(FALSE, 'yohoho.i.butylka.roma'),
      array(FALSE, '127.0.0.1', FALSE), // Try ping IPv4 with IPv6 disabled
    );
    $cmd = $GLOBALS['config']['fping6'] . " -c 1 -q ::1 2>&1";
    exec($cmd, $output, $return); // Check if we have IPv6 support in current system
    if ($return === 0)
    {
      // IPv6 only
      //$array[] = array(TRUE,  'localhost', FALSE);
      $array[] = array(TRUE,  '::1',       FALSE);
      $array[] = array(FALSE, '::ffff',    FALSE);
      foreach (array('localhost', 'ip6-localhost') as $hostname)
      {
        // Debian used ip6-localhost instead localhost.. lol
        $ip = gethostbyname6($hostname, OBS_DNS_AAAA);
        if ($ip)
        {
          $array[] = array(TRUE,  $hostname, FALSE);
          //var_dump($hostname);
          break;
        }
      }
    }
    return $array;
  }

  /**
  * @dataProvider providerCalculateMempoolProperties
  * @group numbers
  */
  public function testCalculateMempoolProperties($scale, $used, $total, $free, $perc, $result)
  {
    $this->assertSame($result, calculate_mempool_properties($scale, $used, $total, $free, $perc));
  }

  public function providerCalculateMempoolProperties()
  {
    $results = array(
      array(  1, 123456789, 234567890, NULL, NULL, array('used' => 123456789,  'total' => 234567890,   'free' => 111111101,  'perc' => 52.63, 'units' => 1,   'scale' => 1,   'valid' => TRUE)), // Used + Total known
      array( 10, 123456789, 234567890, NULL, NULL, array('used' => 1234567890, 'total' => 2345678900,  'free' => 1111111010, 'perc' => 52.63, 'units' => 10,  'scale' => 10,  'valid' => TRUE)), // Used + Total known, scale factor 10
      array(0.5, 123456789, 234567890, NULL, NULL, array('used' => 61728394.5, 'total' => 117283945.0, 'free' => 55555550.5, 'perc' => 52.63, 'units' => 0.5, 'scale' => 0.5, 'valid' => TRUE)), // Used + Total known, scale factor 0.5

      array(  1, NULL, 1234567890, 1597590, NULL, array('used' => 1232970300,   'total' => 1234567890,   'free' => 1597590,   'perc' => 99.87, 'units' => 1,   'scale' => 1,   'valid' => TRUE)), // Total + Free known
      array(100, NULL, 1234567890, 1597590, NULL, array('used' => 123297030000, 'total' => 123456789000, 'free' => 159759000, 'perc' => 99.87, 'units' => 100, 'scale' => 100, 'valid' => TRUE)), // Total + Free known, scale factor 10
      array(0.5, NULL, 1234567890, 1597590, NULL, array('used' => 616485150.0,  'total' => 617283945.0,  'free' => 798795.0,  'perc' => 99.87, 'units' => 0.5, 'scale' => 0.5, 'valid' => TRUE)), // Total + Free known, scale factor 0.5

      array(  1, 13333337, 23333337, 10000000, NULL, array('used' => 13333337,  'total' => 23333337,   'free' => 10000000,    'perc' => 57.14, 'units' => 1,   'scale' => 1,   'valid' => TRUE)), // All known
      array( 10, 13333337, 23333337, 10000000, NULL, array('used' => 133333370, 'total' => 233333370,  'free' => 100000000,   'perc' => 57.14, 'units' => 10,  'scale' => 10,  'valid' => TRUE)), // All known, scale factor 10
      array(0.5, 13333337, 23333337, 10000000, NULL, array('used' => 6666668.5, 'total' => 11666668.5, 'free' => 5000000.0,   'perc' => 57.14, 'units' => 0.5, 'scale' => 0.5, 'valid' => TRUE)), // All known, scale factor 0.5

      array(  1, 123456789, NULL, 163840, NULL, array('used' => 123456789,   'total' => 123620629,   'free' => 163840,        'perc' => 99.87, 'units' => 1,   'scale' => 1,   'valid' => TRUE)), // Used + Free known
      array(100, 123456789, NULL, 163840, NULL, array('used' => 12345678900, 'total' => 12362062900, 'free' => 16384000,      'perc' => 99.87, 'units' => 100, 'scale' => 100, 'valid' => TRUE)), // Used + Free known, scale factor 100
      array(0.5, 123456789, NULL, 163840, NULL, array('used' => 61728394.5,  'total' => 61810314.5,  'free' => 81920.0,       'perc' => 99.87, 'units' => 0.5, 'scale' => 0.5, 'valid' => TRUE)), // Used + Free known, scale factor 0.5

      array(   1, NULL, 600000000, NULL, 30, array('used' => 180000000,    'total' => 600000000,    'free' => 420000000,      'perc' => 30, 'units' => 1,    'scale' => 1,    'valid' => TRUE)),    // Total + Percentage known
      array(1000, NULL, 600000000, NULL, 30, array('used' => 180000000000, 'total' => 600000000000, 'free' => 420000000000,   'perc' => 30, 'units' => 1000, 'scale' => 1000, 'valid' => TRUE)),    // Total + Percentage known, scale factor 1000
      array( 0.5, NULL, 600000000, NULL, 30, array('used' => 90000000.0,   'total' => 300000000.0,  'free' => 210000000.0,    'perc' => 30, 'units' => 0.5,  'scale' => 0.5,  'valid' => TRUE)),    // Total + Percentage known, scale factor 0.5

      array(  1, 1597590, 1234567890, NULL, NULL, array('used' => 1597590,  'total' => 1234567890,  'free' => 1232970300,     'perc' => 0.13, 'units' => 1,   'scale' => 1,   'valid' => TRUE)),  // Used + Total known
      array( 10, 1597590, 1234567890, NULL, NULL, array('used' => 15975900, 'total' => 12345678900, 'free' => 12329703000,    'perc' => 0.13, 'units' => 10,  'scale' => 10,  'valid' => TRUE)),  // Used + Total known, scale factor 10
      array(0.5, 1597590, 1234567890, NULL, NULL, array('used' => 798795.0, 'total' => 617283945.0, 'free' => 616485150.0,    'perc' => 0.13, 'units' => 0.5, 'scale' => 0.5, 'valid' => TRUE)),  // Used + Total known, scale factor 0.5

      array(  1, NULL, NULL, NULL, 57, array('used' => 57, 'total' => 100, 'free' => 43, 'perc' => 57, 'units' => 1,   'scale' => 1,   'valid' => TRUE)),    // Only percentage known
      array( 40, NULL, NULL, NULL, 23, array('used' => 23, 'total' => 100, 'free' => 77, 'perc' => 23, 'units' => 40,  'scale' => 40,  'valid' => TRUE)),   // Only percentage known, scale factor 40
      array(0.1, NULL, NULL, NULL, 16, array('used' => 16, 'total' => 100, 'free' => 84, 'perc' => 16, 'units' => 0.1, 'scale' => 0.1, 'valid' => TRUE)),  // Only percentage known, scale factor 0.1
    );
    return $results;
  }

  /**
  * @dataProvider providerCalculateMempoolPropertiesScale
  * @group numbers
  */
  public function testCalculateMempoolPropertiesScale($scale, $used, $total, $free, $perc, $options, $result)
  {
    $this->assertSame($result, calculate_mempool_properties($scale, $used, $total, $free, $perc, $options));
  }

  public function providerCalculateMempoolPropertiesScale()
  {
    $scale1 = array('scale_total' => 1024);
    $scale2 = array('scale_used'  => 2048);
    $scale3 = array('scale_free'  => 4096);

    $results = array(
      array(  1, 123456789, 234567890, NULL, NULL, $scale1, array('used' => 123456789,    'total' => 240197519360,  'free' => 240074062571,  'perc' =>     0.05, 'units' => 1,   'scale' => 1, 'valid' => TRUE)),   // Used + Total known
      array( 10, 123456789, 234567890, NULL, NULL, $scale2, array('used' => 252839503872, 'total' => 2345678900,    'free' => -250493824972, 'perc' => 10778.95, 'units' => 10,  'scale' => 10, 'valid' => FALSE)),  // Used + Total known, scale factor 10
      array(0.5, 123456789, 234567890, NULL, NULL, $scale3, array('used' => 61728394.5,   'total' => 117283945.0,   'free' => 55555550.5,    'perc' =>    52.63, 'units' => 0.5, 'scale' => 0.5, 'valid' => TRUE)), // Used + Total known, scale factor 0.5

      array(  1, NULL, 1234567890, 1597590, NULL, $scale1, array('used' => 1264195921770, 'total' => 1264197519360, 'free' => 1597590,       'perc' =>    100.0, 'units' => 1,   'scale' => 1, 'valid' => TRUE)),   // Total + Free known
      array(100, NULL, 1234567890, 1597590, NULL, $scale2, array('used' => 123297030000,  'total' => 123456789000,  'free' => 159759000,     'perc' =>    99.87, 'units' => 100, 'scale' => 100, 'valid' => TRUE)), // Total + Free known, scale factor 10
      array(0.5, NULL, 1234567890, 1597590, NULL, $scale3, array('used' => -5926444695.0, 'total' => 617283945.0,   'free' => 6543728640,    'perc' =>  -960.08, 'units' => 0.5, 'scale' => 0.5, 'valid' => FALSE)), // Total + Free known, scale factor 0.5
    );
    return $results;
  }

    /**
     * @dataProvider providerGetGeolocation
     * @group geo
     */
    public function testGetGeolocation($address, $result, $api = 'geocodefarm', $geo_db = [], $dns_only = FALSE)
    {
        if ($api === 'geocodefarm' || $api === 'openstreetmap' ||
            !empty($GLOBALS['config']['geo_api'][$api]['key'])) {
            $GLOBALS['config']['geocoding']['dns'] = $dns_only;
            $GLOBALS['config']['geocoding']['api'] = $api;

            $test = get_geolocation($address, $geo_db, $dns_only);
            unset($test['location_updated'], $test['location_status']);
            $this->assertSame($result, $test);
        }
    }

    public function providerGetGeolocation()
    {
        $array = [];

        // DNS LOC (reverse)
        $location = 'qwerty';
        $api = 'openstreetmap';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 37.7749289, 'location_lon' => -122.4194178,
                    'location_city' => 'San Francisco', 'location_county' => 'Unknown', 'location_state' => 'California', 'location_country' => 'United States' ];
        $array[] = [ $location, $result, $api, [ 'hostname' => 'loc-degree.observium.dev' ], TRUE ]; // reverse, dns only
        $api = 'geocodefarm';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 37.7749289, 'location_lon' => -122.4194178,
                    'location_city' => 'Mission District', 'location_county' => 'San Francisco', 'location_state' => 'CA', 'location_country' => 'United States' ];
        $array[] = [ $location, $result, $api, [ 'hostname' => 'loc-degree.observium.dev' ], TRUE ]; // reverse, dns only
        $api = 'yandex';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 37.7749289, 'location_lon' => -122.4194178,
                    'location_country' => 'United States', 'location_state' => 'California', 'location_county' => 'San Francisco', 'location_city' => 'SoMa' ];
        $array[] = [ $location, $result, $api, [ 'hostname' => 'loc-degree.observium.dev' ], TRUE ]; // reverse, dns only
        $api = 'mapquest';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 37.7749289, 'location_lon' => -122.4194178,
                    'location_city' => 'San Francisco', 'location_county' => 'San Francisco', 'location_state' => 'CA', 'location_country' => 'United States' ];
        $array[] = [ $location, $result, $api, [ 'hostname' => 'loc-degree.observium.dev' ], TRUE ]; // reverse, dns only
        $api = 'bing';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 37.7749289, 'location_lon' => -122.4194178,
                    'location_city' => 'Mission District', 'location_county' => 'San Francisco', 'location_state' => 'California', 'location_country' => 'United States' ];
        $array[] = [ $location, $result, $api, [ 'hostname' => 'loc-degree.observium.dev' ], TRUE ]; // reverse, dns only

        // Location (reverse)
        $location = 'Some location [47.616380;-122.341673]';
        $api = 'openstreetmap';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 47.61638, 'location_lon' => -122.341673,
                    'location_city' => 'Seattle', 'location_county' => 'King', 'location_state' => 'Washington', 'location_country' => 'United States' ];
        $array[] = [ $location, $result, $api ];
        $location = 'Some location|\'47.616380\'|\'-122.341673\'';
        $api = 'openstreetmap';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 47.61638, 'location_lon' => -122.341673,
                    'location_city' => 'Seattle', 'location_county' => 'King', 'location_state' => 'Washington', 'location_country' => 'United States' ];
        $array[] = [ $location, $result, $api ];

        // First request (forward)
        $location = 'Badenerstrasse 569, Zurich, Switzerland';
        $api = 'openstreetmap';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 47.3832766, 'location_lon' => 8.4955511,
                    'location_city' => 'Zurich', 'location_county' => 'District Zurich', 'location_state' => 'Zurich', 'location_country' => 'Switzerland' ];
        $array[] = [ $location, $result, $api ];
        $location = 'Nikhef, Amsterdam, NL';
        $api = 'yandex';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lon' => 4.892557, 'location_lat' => 52.373057,
                    'location_country' => 'Netherlands', 'location_state' => 'North Holland', 'location_county' => 'North Holland', 'location_city' => 'Amsterdam' ];
        $array[] = [ $location, $result, $api ];
        $location = 'Korea_Seoul';
        $api = 'mapquest';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 37.55886, 'location_lon' => 126.99989,
                    'location_city' => 'Seoul', 'location_county' => 'South Korea', 'location_state' => 'Unknown', 'location_country' => 'South Korea' ];
        $array[] = [ $location, $result, $api ];

        // Second request (forward)
        $location = 'ZRH2, Badenerstrasse 569, Zurich, Switzerland';
        $api = 'openstreetmap';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 47.3832766, 'location_lon' => 8.4955511,
                    'location_city' => 'Zurich', 'location_county' => 'District Zurich', 'location_state' => 'Zurich', 'location_country' => 'Switzerland' ];
        $array[] = [ $location, $result, $api ];
        $location = 'Rack: NK-76 - Nikhef, Amsterdam, NL';
        $api = 'yandex';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lon' => 4.892557, 'location_lat' => 52.373057,
                    'location_country' => 'Netherlands', 'location_state' => 'North Holland', 'location_county' => 'North Holland', 'location_city' => 'Amsterdam' ];
        $array[] = [ $location, $result, $api ];
        $location = 'Korea_Seoul';
        $api = 'bing';
        $result = [ 'location' => $location, 'location_geoapi' => $api,
                    'location_lat' => 37.5682945, 'location_lon' => 126.9977875,
                    'location_city' => 'Seoul', 'location_county' => 'Unknown', 'location_state' => 'Seoul', 'location_country' => 'South Korea' ];
        $array[] = [ $location, $result, $api ];

        return $array;
    }

    /**
     * @group sql
     */
    public function testGenerateWhereClauseWithValidConditions()
    {
        $conditions = [
            'column1 = "value1"',
            '',
            '  ',
            'column2 > 10'
        ];
        $additional_conditions = [
            'column3 < 50',
            'column4 LIKE "%example%"'
        ];

        $expected = ' WHERE column1 = "value1" AND column2 > 10 AND column3 < 50 AND column4 LIKE "%example%"';
        $result = generate_where_clause($conditions, $additional_conditions);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group sql
     */
    public function testGenerateWhereClauseWithOnlyEmptyConditions()
    {
        $conditions = [
            '',
            '  ',
            "\t",
            "\n"
        ];

        $result = generate_where_clause($conditions);
        //$this->assertNull($result);
        $this->assertEquals('', $result);
    }

    /**
     * @group sql
     */
    public function testGenerateWhereClauseWithSingleCondition()
    {
        $conditions = [
            'column1 = "value1"'
        ];

        $expected = ' WHERE column1 = "value1"';
        $result = generate_where_clause($conditions);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group sql
     */
    public function testGenerateWhereClauseWithNoConditions()
    {
        $conditions = [];

        $result = generate_where_clause($conditions);
        //$this->assertNull($result);
        $this->assertEquals('', $result);
    }

    /**
     * @group sql
     */
    public function testGenerateWhereClauseWithOnlyAdditionalConditions()
    {
        $conditions = [];
        $additional_conditions = [
            'column1 = "value1"',
            'column2 > 10'
        ];

        $expected = ' WHERE column1 = "value1" AND column2 > 10';
        $result = generate_where_clause($conditions, $additional_conditions);
        $this->assertEquals($expected, $result);
    }
}



// EOF
