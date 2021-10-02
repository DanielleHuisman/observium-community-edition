<?php

// Here required DB connect and disable phpunit backupGlobals
// https://phpunit.de/manual/current/en/appendixes.annotations.html#appendixes.annotations.backupGlobals
define('OBS_DB_SKIP', FALSE);
//define('OBS_DEBUG', 2);

include(__DIR__ . '/../includes/sql-config.inc.php');
//include(dirname(__FILE__) . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php');
//include(dirname(__FILE__) . '/../includes/definitions.inc.php');
//include(dirname(__FILE__) . '/../includes/functions.inc.php');
include(__DIR__ . '/../html/includes/functions.inc.php');

/**
 * @backupGlobals disabled
 */
class IncludesDbTest extends \PHPUnit\Framework\TestCase
{
  /**
   * @dataProvider providerGenerateQueryValues
   * @group sql
   */
  public function testGenerateQueryValues($value, $column, $condition, $result)
  {
    $this->assertSame($result, generate_query_values($value, $column, $condition));
  }

  /**
   * @dataProvider providerGenerateQueryValues
   * @group sql
   */
  public function testGenerateQueryValuesNoAnd($value, $column, $condition, $result)
  {
    $result = preg_replace('/^ AND/', '', $result);
    $this->assertSame($result, generate_query_values($value, $column, $condition, FALSE));
  }

  public function providerGenerateQueryValues()
  {
    return array(
      // Basic values
      array(0,                            'test', FALSE, " AND `test` = '0'"),
      array('1,sf,98u8',                '`test`', FALSE, " AND `test` = '1,sf,98u8'"),
      array(array('1,sf,98u8'),         'I.test', FALSE, " AND `I`.`test` = '1,sf,98u8'"),
      array(array('1,sf','98u8', ''), '`I`.`test`', FALSE, " AND (`I`.`test` IN ('1,sf','98u8','') OR `I`.`test` IS NULL)"),
      array(OBS_VAR_UNSET,              '`test`', FALSE, " AND (`test` = '' OR `test` IS NULL)"),
      array('"*%F@W)b\'_u<[`R1/#F"',      'test', FALSE, " AND `test` = '\\\"*%F@W)b\'_u<[`R1/#F\\\"'"),
      array('*?%_',                       'test', FALSE, " AND `test` = '*?%_'"),
      // Negative
      array(array('1,sf,98u8'),         'I.test', 'NOT', " AND `I`.`test` != '1,sf,98u8'"),
      array(array('1,sf,98u8'),         'I.test',  '!=', " AND `I`.`test` != '1,sf,98u8'"),
      array(array('1,sf,98u8', ''), '`I`.`test`',  '!=', " AND (`I`.`test` NOT IN ('1,sf,98u8','') AND `I`.`test` IS NOT NULL)"),
      // LIKE conditions
      array(0,                            'test',  '%LIKE', " AND (`test` LIKE '%0')"),
      array('1,sf,98u8',                '`test`',  'LIKE%', " AND (`test` LIKE '1,sf,98u8%')"),
      array(array('1,sf,98u8'),         'I.test', '%LIKE%', " AND (`I`.`test` LIKE '%1,sf,98u8%')"),
      array(array('1,sf,98u8', ''), '`I`.`test`',   'LIKE', " AND (`I`.`test` LIKE '1,sf,98u8' OR COALESCE(`I`.`test`, '') LIKE '')"),
      array(OBS_VAR_UNSET,              '`test`',   'LIKE', " AND (`test` LIKE '".OBS_VAR_UNSET."')"),
      array('"*%F@W)b\'_u<[`R1/#F"',      'test',   'LIKE', " AND (`test` LIKE '\\\"%\%F@W)b\'\_u<[`R1/#F\\\"')"),
      // LIKE with match *?
      array('*?%_',                       'test',   'LIKE', " AND (`test` LIKE '%_\%\_')"),
      // Negative LIKE
      array('1,sf,98u8',                '`test`', 'NOT LIKE%', " AND (`test` NOT LIKE '1,sf,98u8%')"),
      array(array('1,sf,98u8', ''), '`I`.`test`',  'NOT LIKE', " AND (`I`.`test` NOT LIKE '1,sf,98u8' AND COALESCE(`I`.`test`, '') NOT LIKE '')"),
      // Duplicates
      array(array('1','sf','1','1','98u8',''), '`I`.`test`', FALSE, " AND (`I`.`test` IN ('1','sf','98u8','') OR `I`.`test` IS NULL)"),
      array(array('1','sf','98u8','1','sf',''), 'I.test', '%LIKE%', " AND (`I`.`test` LIKE '%1%' OR `I`.`test` LIKE '%sf%' OR `I`.`test` LIKE '%98u8%' OR COALESCE(`I`.`test`, '') LIKE '')"),
      // Wrong conditions
      array('"*%F@W)b\'_u<[`R1/#F"',      'test',    'wtf', " AND `test` = '\\\"*%F@W)b\'_u<[`R1/#F\\\"'"),
      array('ssdf',                     '`test`',     TRUE, " AND (`test` LIKE 'ssdf')"),
      // Empty values
      array(NULL,                       '`test`',    FALSE, " AND (`test` = '' OR `test` IS NULL)"),
      array('',                         '`test`',    FALSE, " AND (`test` = '' OR `test` IS NULL)"),
      array(array(),                    '`test`',    FALSE, " AND 0"),
      array(NULL,                       '`test`',   'LIKE', " AND (COALESCE(`test`, '') LIKE '')"),
      array('',                         '`test`',   'LIKE', " AND (COALESCE(`test`, '') LIKE '')"),
      array(array(),                    '`test`',   'LIKE', " AND 0"),
      // Empty values negative condition
      array(NULL,                       '`test`',     '!=', " AND (`test` != '' AND `test` IS NOT NULL)"),
      array('',                         '`test`',     '!=', " AND (`test` != '' AND `test` IS NOT NULL)"),
      array(array(),                    '`test`',     '!=', " AND 1"),
      array(NULL,                       '`test`', 'NOT LIKE', " AND (COALESCE(`test`, '') NOT LIKE '')"),
      array('',                         '`test`', 'NOT LIKE', " AND (COALESCE(`test`, '') NOT LIKE '')"),
      array(array(),                    '`test`', 'NOT LIKE', " AND 1"),
    );
  }

}

// EOF
