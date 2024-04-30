<?php

$base_dir = realpath(__DIR__ . '/..');
$config['install_dir'] = $base_dir;

include(__DIR__ . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php');
include(__DIR__ . "/../includes/polyfill.inc.php");
include(__DIR__ . "/../includes/autoloader.inc.php");
include(__DIR__ . "/../includes/debugging.inc.php");
require_once(__DIR__ ."/../includes/constants.inc.php");
include(__DIR__ . '/../includes/common.inc.php');
include(__DIR__ . '/../includes/definitions.inc.php');
//include(__DIR__ . '/data/test_definitions.inc.php'); // Fake definitions for testing
include(__DIR__ . '/../includes/functions.inc.php');

class IncludesAlertsTest extends \PHPUnit\Framework\TestCase
{
  /**
   * @dataProvider providerTestCondition
   * @group condition
   */
  public function testTestCondition($result, $value_a, $condition, $value_b)
  {
    $this->assertSame($result, test_condition($value_a, $condition, $value_b));
  }

  public function providerTestCondition()
  {
    $array   = array(); // Init

    // >=
    $array[] = array( TRUE,       0, 'ge',        -1);

    $array[] = array( TRUE,       0, '>=',        -1);
    $array[] = array(FALSE,       0, '>=',         1);
    $array[] = array( TRUE,       0, '>=',         0);
    $array[] = array( TRUE,       1, '>=',         1);
    $array[] = array( TRUE,      -1, '>=',        -1);
    $array[] = array( TRUE,    0.11, '>=',      0.11);
    $array[] = array(FALSE, 1474559, '>=',  '1440kB');
    $array[] = array( TRUE, 1474560, '>=',  '1440kB');
    $array[] = array( TRUE, 1474561, '>=',  '1440kB');
    // note, for string compared char maps and strlen
    $array[] = array( TRUE, 'sweet', '>=',    'swee');
    $array[] = array( TRUE, 'sweet', '>=',   'sweet');
    $array[] = array( TRUE, 'swoot', '>=',   'sweet');
    $array[] = array(FALSE, 'sweet', '>=',  'sweett');
    $array[] = array(FALSE,       0, 'ge',        'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline');
    $array[] = array( TRUE, 'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline', 'ge', 0);

    // version compare
    //$array[] = array(FALSE, '15.1R4.55', 'version_>=', '15.1R5.5');
    $array[] = array(FALSE, '15.1R4.55',         '>=', '15.1R5.5');
    //$array[] = array( TRUE, '15.1R5.55', 'version_>=', '15.1R5.5');
    $array[] = array( TRUE, '15.1R5.55',         '>=', '15.1R5.5');
    //$array[] = array( TRUE, '15.1R5.5',  'version_>=', '15.1R5.5');
    $array[] = array( TRUE, '15.1R5.5',          '>=', '15.1R5.5');
    //$array[] = array(FALSE, '15.1R5.5',  'version_>=', '15.1X5.5');
    $array[] = array(FALSE, '15.1R5.5',          '>=', '15.1X5.5');
    //$array[] = array( TRUE, '15.1X5.5',  'version_>=', '15.1R5.5');
    $array[] = array( TRUE, '15.1X5.5',          '>=', '15.1R5.5');
    //$array[] = array(FALSE, '1.2.3x',    'version_>=', '1.2.3z');
    $array[] = array(FALSE, '1.2.3x',            '>=', '1.2.3z');

    // >
    $array[] = array( TRUE,       0, 'gt',        -1);
    $array[] = array( TRUE,       0, 'greater',   -1);

    $array[] = array( TRUE,       0, '>',         -1);
    $array[] = array(FALSE,       0, '>',          1);
    $array[] = array(FALSE,       0, '>',          0);
    $array[] = array(FALSE,       1, '>',          1);
    $array[] = array(FALSE,      -1, '>',         -1);
    $array[] = array(FALSE,    0.11, '>',       0.11);
    $array[] = array(FALSE, 1474559, '>',   '1440kB');
    $array[] = array(FALSE, 1474560, '>',   '1440kB');
    $array[] = array( TRUE, 1474561, '>',   '1440kB');
    // note, for string compared char maps and strlen
    $array[] = array( TRUE, 'sweet', '>',     'swee');
    $array[] = array(FALSE, 'sweet', '>',    'sweet');
    $array[] = array( TRUE, 'swoot', '>',    'sweet');
    $array[] = array(FALSE, 'sweet', '>',   'sweett');
    $array[] = array(FALSE,       0, 'gt',        'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline');
    $array[] = array( TRUE, 'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline', 'gt', 0);
    //$array[] = array(FALSE, 1474559, '>',       NULL);

    // version compare
    //$array[] = array(FALSE, '15.1R4.55', 'version_>', '15.1R5.5');
    $array[] = array(FALSE, '15.1R4.55',         '>', '15.1R5.5');
    //$array[] = array( TRUE, '15.1R5.55', 'version_>', '15.1R5.5');
    $array[] = array( TRUE, '15.1R5.55',         '>', '15.1R5.5');
    //$array[] = array(FALSE, '15.1R5.5',  'version_>', '15.1R5.5');
    $array[] = array(FALSE, '15.1R5.5',          '>', '15.1R5.5');
    //$array[] = array(FALSE, '15.1R5.5',  'version_>', '15.1X5.5');
    $array[] = array(FALSE, '15.1R5.5',          '>', '15.1X5.5');
    //$array[] = array( TRUE, '15.1X5.5',  'version_>', '15.1R5.5');
    $array[] = array( TRUE, '15.1X5.5',          '>', '15.1R5.5');
    //$array[] = array(FALSE, '1.2.3x',    'version_>', '1.2.3z');
    $array[] = array(FALSE, '1.2.3x',            '>', '1.2.3z');

    // <=
    $array[] = array( TRUE,      -1, 'le',        -1);

    $array[] = array(FALSE,       0, '<=',        -1);
    $array[] = array( TRUE,       0, '<=',         1);
    $array[] = array( TRUE,       0, '<=',         0);
    $array[] = array( TRUE,       1, '<=',         1);
    $array[] = array( TRUE,      -1, '<=',        -1);
    $array[] = array( TRUE,    0.11, '<=',      0.11);
    $array[] = array( TRUE, 1474559, '<=',  '1440kB');
    $array[] = array( TRUE, 1474560, '<=',  '1440kB');
    $array[] = array(FALSE, 1474561, '<=',  '1440kB');
    // note, for string compared char maps and strlen
    $array[] = array(FALSE, 'sweet', '<=',    'swee');
    $array[] = array( TRUE, 'sweet', '<=',   'sweet');
    $array[] = array(FALSE, 'swoot', '<=',   'sweet');
    $array[] = array( TRUE, 'sweet', '<=',  'sweett');
    $array[] = array( TRUE,       0, 'le',        'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline');
    $array[] = array(FALSE, 'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline', 'le', 0);

    // version compare
    //$array[] = array( TRUE, '15.1R4.55', 'version_<=', '15.1R5.5');
    $array[] = array( TRUE, '15.1R4.55',         '<=', '15.1R5.5');
    //$array[] = array(FALSE, '15.1R5.55', 'version_<=', '15.1R5.5');
    $array[] = array(FALSE, '15.1R5.55',         '<=', '15.1R5.5');
    //$array[] = array( TRUE, '15.1R5.5',  'version_<=', '15.1R5.5');
    $array[] = array( TRUE, '15.1R5.5',          '<=', '15.1R5.5');
    //$array[] = array( TRUE, '15.1R5.5',  'version_<=', '15.1X5.5');
    $array[] = array( TRUE, '15.1R5.5',          '<=', '15.1X5.5');
    //$array[] = array(FALSE, '15.1X5.5',  'version_<=', '15.1R5.5');
    $array[] = array(FALSE, '15.1X5.5',          '<=', '15.1R5.5');
    //$array[] = array( TRUE, '1.2.3x',    'version_<=', '1.2.3z');
    $array[] = array( TRUE, '1.2.3x',            '<=', '1.2.3z');

    // <
    $array[] = array( TRUE,      -2, 'lt',        -1);
    $array[] = array( TRUE,      -2, 'less',      -1);

    $array[] = array(FALSE,       0, '<',         -1);
    $array[] = array( TRUE,       0, '<',          1);
    $array[] = array(FALSE,       0, '<',          0);
    $array[] = array(FALSE,       1, '<',          1);
    $array[] = array(FALSE,      -1, '<',         -1);
    $array[] = array(FALSE,    0.11, '<',       0.11);
    $array[] = array( TRUE, 1474559, '<',   '1440kB');
    $array[] = array(FALSE, 1474560, '<',   '1440kB');
    $array[] = array(FALSE, 1474561, '<',   '1440kB');
    // note, for string compared char maps and strlen
    $array[] = array(FALSE, 'sweet', '<',     'swee');
    $array[] = array(FALSE, 'sweet', '<',    'sweet');
    $array[] = array(FALSE, 'swoot', '<',    'sweet');
    $array[] = array( TRUE, 'sweet', '<',   'sweett');
    $array[] = array( TRUE,       0, 'lt',        'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline');
    $array[] = array(FALSE, 'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline', 'lt', 0);

    // version compare
    //$array[] = array( TRUE, '5.11R5.5',  'version_<', '15.1R5.5');
    $array[] = array( TRUE, '5.11R5.5',          '<', '15.1R5.5');
    //$array[] = array(FALSE, '15.1R5.55', 'version_<', '15.1R5.5');
    $array[] = array(FALSE, '15.1R5.55',         '<', '15.1R5.5');
    //$array[] = array(FALSE, '15.1R5.5',  'version_<', '15.1R5.5');
    $array[] = array(FALSE, '15.1R5.5',          '<', '15.1R5.5');
    //$array[] = array( TRUE, '15.1R5.5',  'version_<', '15.1X5.5');
    $array[] = array( TRUE, '15.1R5.5',          '<', '15.1X5.5');
    //$array[] = array(FALSE, '15.1X5.5',  'version_<', '15.1R5.5');
    $array[] = array(FALSE, '15.1X5.5',          '<', '15.1R5.5');
    //$array[] = array( TRUE, '1.2.3x',    'version_<', '1.2.3z');
    $array[] = array( TRUE, '1.2.3x',            '<', '1.2.3z');

    // !=
    $array[] = array( TRUE,       0, 'notequals', -1);
    $array[] = array( TRUE,       0, 'isnot',     -1);
    $array[] = array( TRUE,       0, 'ne',        -1);
    $array[] = array( TRUE,       0, 'ne',        'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline');
    $array[] = array( TRUE, 'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline', 'ne', 0);

    $array[] = array( TRUE,       0, '!=',        -1);
    $array[] = array( TRUE,       0, '!=',         1);
    $array[] = array(FALSE,       0, '!=',         0);
    $array[] = array(FALSE,       1, '!=',         1);
    $array[] = array(FALSE,      -1, '!=',        -1);
    $array[] = array(FALSE,    0.11, '!=',      0.11);
    $array[] = array( TRUE, 1474559, '!=',  '1440kB');
    $array[] = array(FALSE, 1474560, '!=',  '1440kB');
    $array[] = array( TRUE, 1474561, '!=',  '1440kB');
    // note, for string compared char maps and strlen
    $array[] = array( TRUE, 'sweet', '!=',    'swee');
    $array[] = array(FALSE, 'sweet', '!=',   'sweet');
    $array[] = array( TRUE, 'swoot', '!=',   'sweet');
    $array[] = array( TRUE, 'sweet', '!=',  'sweett');

    // version compare
    //$array[] = array( TRUE, '15.1R4.55', 'version_!=', '15.1R5.5');
    $array[] = array( TRUE, '15.1R4.55',         '!=', '15.1R5.5');
    //$array[] = array( TRUE, '15.1R5.55', 'version_!=', '15.1R5.5');
    $array[] = array( TRUE, '15.1R5.55',         '!=', '15.1R5.5');
    //$array[] = array(FALSE, '15.1R5.5',  'version_!=', '15.1R5.5');
    $array[] = array(FALSE, '15.1R5.5',          '!=', '15.1R5.5');
    //$array[] = array( TRUE, '1.2.3x',    'version_!=', '1.2.3z');
    $array[] = array( TRUE, '1.2.3x',            '!=', '1.2.3z');

    // ==
    $array[] = array( TRUE,      -1, 'equals',    -1);
    $array[] = array( TRUE,      -1, 'is',        -1);
    $array[] = array( TRUE,      -1, 'eq',        -1);
    $array[] = array( TRUE,      -1, '=',         -1);

    $array[] = array(FALSE,       0, '==',        -1);
    $array[] = array(FALSE,       0, '==',         1);
    $array[] = array( TRUE,       0, '==',         0);
    $array[] = array( TRUE,       1, '==',         1);
    $array[] = array( TRUE,      -1, '==',        -1);
    $array[] = array( TRUE,    0.11, '==',      0.11);
    $array[] = array(FALSE, 1474559, '==',  '1440kB');
    $array[] = array( TRUE, 1474560, '==',  '1440kB');
    $array[] = array(FALSE, 1474561, '==',  '1440kB');
    // note, for string compared char maps and strlen
    $array[] = array(FALSE, 'sweet', '==',    'swee');
    $array[] = array( TRUE, 'sweet', '==',   'sweet');
    $array[] = array(FALSE, 'swoot', '==',   'sweet');
    $array[] = array(FALSE, 'sweet', '==',  'sweett');
    $array[] = array(FALSE,       0, 'eq',        'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline');
    $array[] = array(FALSE, 'acPowerAndSwitchAreOnPowerSupplyIsOnIsOkAndOnline', 'eq', 0);

    // version compare
    //$array[] = array(FALSE, '15.1R4.55', 'version_==', '15.1R5.5');
    $array[] = array(FALSE, '15.1R4.55',         '==', '15.1R5.5');
    //$array[] = array(FALSE, '15.1R5.55', 'version_==', '15.1R5.5');
    $array[] = array(FALSE, '15.1R5.55',         '==', '15.1R5.5');
    //$array[] = array( TRUE, '15.1R5.5',  'version_==', '15.1R5.5');
    $array[] = array( TRUE, '15.1R5.5',          '==', '15.1R5.5');
    //$array[] = array(FALSE, '1.2.3x',    'version_==', '1.2.3z');
    $array[] = array(FALSE, '1.2.3x',            '==', '1.2.3z');

    // match
    $array[] = array( TRUE, 'sweet', 'matches',    '.weet');

    $array[] = array( TRUE, 'sweet', 'match',      '.weet');
    $array[] = array( TRUE, 'sweet', 'match',      'sweet');
    $array[] = array( TRUE, 'sweet', 'match',      'sw..t');
    $array[] = array( TRUE, 'sweet', 'match',       'swe*');
    $array[] = array(FALSE, 'sweet', 'match',       'swee');
    $array[] = array(FALSE, 'swoot', 'match',      '.weet');
    $array[] = array(FALSE, 'swoot', 'match',      'sweet');
    $array[] = array( TRUE, 'swoot', 'match',      'sw..t');
    $array[] = array(FALSE, 'swoot', 'match',       'swe*');
    $array[] = array(FALSE, 'swoot', 'match',       'swoo');

    // !match
    $array[] = array( TRUE, 'sweet', 'notmatches', '.woot');
    $array[] = array( TRUE, 'sweet', 'notmatch',   '.woot');

    $array[] = array(FALSE, 'sweet', '!match',     '.weet');
    $array[] = array(FALSE, 'sweet', '!match',     'sweet');
    $array[] = array(FALSE, 'sweet', '!match',     'sw..t');
    $array[] = array(FALSE, 'sweet', '!match',      'swe*');
    $array[] = array( TRUE, 'sweet', '!match',      'swee');
    $array[] = array( TRUE, 'swoot', '!match',     '.weet');
    $array[] = array( TRUE, 'swoot', '!match',     'sweet');
    $array[] = array(FALSE, 'swoot', '!match',     'sw..t');
    $array[] = array( TRUE, 'swoot', '!match',      'swe*');
    $array[] = array( TRUE, 'swoot', '!match',      'swoo');

    $test1 = 'Paradyne ATM ReachDSL Unit; Model: 4213-A1-530; CCA: 868-5315-8201; S/W Release: 02.03.05; Hardware Revision: 5315-82H; Serial number: 6938473 ;';
    $test2 = 'Blue Coat SG600 Series, Version: SGOS 5.5.11.1, Release id: 110885 Proxy Edition';
    $pattern1 = '(?<hardware>(?:Paradyne|Zhone) .+?)(?: Unit)?; Model: (?<hardware1>\w+(?:\-\w+)*);.+?; S/W Release: (?<version>[\d\.]+);.+; Serial number: (?<serial>\w+)';
    $pattern2 = 'Blue Coat (?<hardware>[\w\ ]+?)(?: Series)?,(?: Proxy\w+)? Version:(?: SGOS)? (?<version>\d[\d\.]+)';
    // escaped delimiter
    $pattern3 = '(?<hardware>(?:Paradyne|Zhone) .+?)(?: Unit)?; Model: (?<hardware1>\w+(?:\-\w+)*);.+?; S\/W Release: (?<version>[\d\.]+);.+; Serial number: (?<serial>\w+)';

    // regex
    $array[] = array( TRUE, $test1, 'regexp',    $pattern1);

    $array[] = array( TRUE, $test1, 'regex',     $pattern1);
    $array[] = array(FALSE, $test1, 'regex',     $pattern2);
    $array[] = array( TRUE, $test1, 'regex',     $pattern3);
    $array[] = array(FALSE, $test2, 'regex',     $pattern1);
    $array[] = array( TRUE, $test2, 'regex',     $pattern2);
    $array[] = array(FALSE, $test2, 'regex',     $pattern3);

    // !regex
    $array[] = array( TRUE, $test1, 'notregexp', $pattern2);
    $array[] = array( TRUE, $test1, 'notregex',  $pattern2);
    $array[] = array( TRUE, $test1, '!regexp',   $pattern2);

    $array[] = array(FALSE, $test1, '!regex',    $pattern1);
    $array[] = array( TRUE, $test1, '!regex',    $pattern2);
    $array[] = array(FALSE, $test1, '!regex',    $pattern3);
    $array[] = array( TRUE, $test2, '!regex',    $pattern1);
    $array[] = array(FALSE, $test2, '!regex',    $pattern2);
    $array[] = array( TRUE, $test2, '!regex',    $pattern3);

    $test1 = 'sweet';
    $test2 = 'notsweet';
    $test3 = 'swoot';
    $list1 = 'sweet,swoot';
    $list2 = array('sweet', 'swoot');

    $test4 = 'on';
    $list4 = [ 'off', 'offLocked', 'notSet', 0 ];

    // in
    $array[] = array( TRUE, $test1, 'list',         $list1);

    $array[] = array( TRUE, $test1, 'in',           $list1);
    $array[] = array( TRUE, $test1, 'in',           $list2);
    $array[] = array(FALSE, $test2, 'in',           $list1);
    $array[] = array(FALSE, $test2, 'in',           $list2);
    $array[] = array( TRUE, $test3, 'in',           $list1);
    $array[] = array( TRUE, $test3, 'in',           $list2);

    // !in
    $array[] = array( TRUE, $test2, '!list',        $list1);
    $array[] = array( TRUE, $test2, 'notin',        $list1);
    $array[] = array( TRUE, $test2, 'notlist',      $list1);

    $array[] = array(FALSE, $test1, '!in',          $list1);
    $array[] = array(FALSE, $test1, '!in',          $list2);
    $array[] = array( TRUE, $test2, '!in',          $list1);
    $array[] = array( TRUE, $test2, '!in',          $list2);
    $array[] = array(FALSE, $test3, '!in',          $list1);
    $array[] = array(FALSE, $test3, '!in',          $list2);

      $array[] = array( TRUE, $test4, 'notin',        $list4);

    $list1 = '0900,1800';
    $list2 = array('0900', '1800');

    // between
    $array[] = array( TRUE, '1055', 'between',      $list1);
    $array[] = array( TRUE, '1055', 'between',      $list2);
    $array[] = array(FALSE, '0855', 'between',      $list1);
    $array[] = array(FALSE, '1955', 'between',      $list2);

    // notbetween
    $array[] = array(FALSE, '1055', 'notbetween',   $list1);
    $array[] = array(FALSE, '1055', 'notbetween',   $list2);
    $array[] = array( TRUE, '0855', '!between',     $list1);
    $array[] = array( TRUE, '1955', '!between',     $list2);

    // isnull (second param not used)
    $array[] = array(TRUE,     NULL, 'isnull',      NULL);
    $array[] = array(TRUE,     NULL, 'null',        NULL);
    $array[] = array(FALSE, 1474559, 'isnull',      NULL);
    $array[] = array(FALSE, 1474559, 'null',        NULL);

    // notnull
    $array[] = array(FALSE,    NULL, 'notnull',     NULL);
    $array[] = array(FALSE,    NULL, '!null',       NULL);
    $array[] = array(TRUE,  1474559, 'notnull',     NULL);
    $array[] = array(TRUE,  1474559, '!null',       NULL);

    return $array;
  }
}

// EOF
