<?php

$base_dir = realpath(dirname(__FILE__) . '/..');
$config['install_dir'] = $base_dir;

include(dirname(__FILE__) . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php');
include(dirname(__FILE__) . '/../includes/functions.inc.php');
include(dirname(__FILE__) . '/../includes/definitions.inc.php');

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

    // !=
    $array[] = array( TRUE,       0, 'notequals', -1);
    $array[] = array( TRUE,       0, 'isnot',     -1);
    $array[] = array( TRUE,       0, 'ne',        -1);

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

    return $array;
  }
}

// EOF
