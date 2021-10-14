<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage db
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

/* Here common DB functions which uses calls to specific api functions */

// Initial variables
$db_stats = array('insert'    => 0, 'insert_sec'    => 0,
                  'update'    => 0, 'update_sec'    => 0,
                  'delete'    => 0, 'delete_sec'    => 0,
                  'fetchcell' => 0, 'fetchcell_sec' => 0,
                  'fetchrow'  => 0, 'fetchrow_sec'  => 0,
                  'fetchrows' => 0, 'fetchrows_sec' => 0,
                  'fetchcol'  => 0, 'fetchcol_sec'  => 0);

// Include DB api. Default and recommended mysqli, legacy mysql
switch (OBS_DB_EXTENSION)
{
  case 'mysql':
    if (@function_exists('mysql_connect'))
    {
      require($config['install_dir'] . '/includes/db/mysql.inc.php');
    } else {
      echo('ERROR. PHP mysql extension does not exist. Execution is stopped.' . PHP_EOL);
      exit(2);
    }
    break;
  case 'mysqli':
  default:
    if (@function_exists('mysqli_connect'))
    {
      require($config['install_dir'] . '/includes/db/mysqli.inc.php');
    } else {
      echo('ERROR. PHP mysqli extension does not exist. Execution is stopped.' . PHP_EOL);
      exit(2);
    }
}

/**
 * Provides server status information
 *
 * @param string $scope GLOBAL or SESSION variable scope modifier
 * @return array Array with server status variables
 */
function dbShowStatus($scope = 'SESSION')
{
  switch ($scope)
  {
    case 'GLOBAL':
      $sql = 'SHOW GLOBAL STATUS;';
      break;
    default:
      $sql = 'SHOW STATUS;';
  }

  $rows = array();
  foreach (dbFetchRows($sql) as $row)
  {
    $rows[$row['Variable_name']] = $row['Value'];
  }

  return $rows;
}

/**
 * Shows the values of MySQL system variables
 *
 * @param string $where WHERE or LIKE clause
 * @param string $scope GLOBAL or SESSION variable scope modifier
 * @return array Array with variables
 */
function dbShowVariables($where = '', $scope = 'SESSION')
{
  switch ($scope)
  {
    case 'GLOBAL':
      $sql = 'SHOW GLOBAL VARIABLES';
      break;
    default:
      $sql = 'SHOW VARIABLES';
  }
  if (strlen($where))
  {
    $sql .= ' ' . $where;
  }

  $rows = array();
  foreach (dbFetchRows($sql) as $row)
  {
    $rows[$row['Variable_name']] = $row['Value'];
  }

  return $rows;
}

function dbSetVariable($var, $value, $scope = 'SESSION')
{
  if (!is_string($var) || !preg_match('/^[A-Z_]+$/i', $var) || !strlen($value))
  {
    return;
  }

  if (!in_array($scope, [ 'GLOBAL', 'SESSION' ]))
  {
    $scope = 'SESSION';
  }

  // Define DB NAME for later use (same as in get_versions())
  if (!defined('OBS_DB_NAME'))
  {
    $mysql_version = dbFetchCell("SELECT version();");
    $mysql_name    = str_icontains_array($mysql_version, 'Maria') ? 'MariaDB' : 'MySQL';
    define('OBS_DB_NAME', $mysql_name);
  }

  // Override MySQL variable with MariaDB analog
  if ($var === 'MAX_EXECUTION_TIME' && OBS_DB_NAME === 'MariaDB')
  {
    $var = 'MAX_STATEMENT_TIME';
    $value /= 1000;
  }

  if (is_intnum($value) || is_float($value))
  {
    $value = [ $value ];
  }

  return dbQuery("SET $scope $var=?;", [ $value ]);
}

/**
 * Provides table index list
 *
 * @param string $table Table name
 * @param string $index_name Index name (if empty get all indexes)
 * @return array Array with table indexes: array()->$key_name->$column_name
 */
function dbShowIndexes($table, $index_name = NULL)
{
  $table  = dbEscape($table);
  $params = array();
  if ($index_name)
  {
    $sql = 'SHOW INDEX FROM `'.$table.'` WHERE `Key_name` = ?;';
    $params[] = $index_name;
  } else {
    $sql = 'SHOW INDEX FROM `'.$table.'`;';
  }

  $rows = array();
  foreach (dbFetchRows($sql, $params) as $row)
  {
    $rows[$row['Key_name']][$row['Column_name']] = $row;
  }

  return $rows;
}

/**
 * Get next Auto Increment value for main table ID
 *
 * @param string $table Table name
 * @return integer Next auti increment id
 */
function dbShowNextID($table)
{
  $table  = dbEscape($table);
  $sql = "SHOW TABLE STATUS LIKE '$table';";

  $row = dbFetchRow($sql);
  //print_debug_vars($row);
  if (isset($row['Auto_increment']))
  {
    return $row['Auto_increment'];
  }

  return FALSE;
}

/*
 * Performs a query using the given string.
 * Used by the other _query functions.
 * */
function dbQuery($sql, $parameters = array(), $print_query = FALSE)
{
  global $fullSql;

  //r($_REQUEST);
  // if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'print_query')) {
  //   $print_query = TRUE;
  // }

  $fullSql = dbMakeQuery($sql, $parameters);

  $debug = defined('OBS_DEBUG') ? OBS_DEBUG : 0;
  if ($debug > 0 || $print_query) {
    // Pre query debug output
    if (is_cli()) {
      //$debug_sql = explode(PHP_EOL, $fullSql);
      //print_message(PHP_EOL . 'SQL[%y' . implode('%n' . PHP_EOL . '%y', $debug_sql) . '%n]', 'console', FALSE);
      echo(PHP_EOL . 'SQL[');
      print_sql($fullSql);
      echo(']' . PHP_EOL);
    } elseif ($print_query === 'log') {
      logfile('db.log', 'Requested Query: ' . print_sql($fullSql, 'log'));
    } else {
      print_sql($fullSql);
      //print_sql($fullSql, 'html');
    }
  }

  if ($debug > 0 || $GLOBALS['config']['profile_sql']) {
    $time_start = microtime(true);
  }

  $result = dbCallQuery($fullSql); // sets $this->result

  if ($debug > 0 || $GLOBALS['config']['profile_sql']) {
    $runtime = number_format(microtime(true) - $time_start, 8);
    $debug_msg .= 'SQL RUNTIME['.($runtime > 0.05 ? '%r' : '%g').$runtime.'s%n]';
    if ($GLOBALS['config']['profile_sql'])
    {
      #fwrite($this->logFile, date('Y-m-d H:i:s') . "\n" . $fullSql . "\n" . number_format($time_end - $time_start, 8) . " seconds\n\n");
      $GLOBALS['sql_profile'][] = array('sql' => $fullSql, 'time' => $runtime);
    }
  }

  if ($debug > 0) {
    if ($result === FALSE && (error_reporting() & 1))
    {
      $error_msg = 'Error in query: (' . dbError() . ') ' . dbErrorNo();
      $debug_msg .= PHP_EOL . 'SQL ERROR[%r'.$error_msg.'%n]';
    }
    if ($warnings = dbWarnings())
    {
      $debug_msg .= PHP_EOL . "SQL WARNINGS[\n %m" . implode("%n\n %m", $warnings) . "%n\n]";
    }

    if (is_cli())
    {
      if ($debug > 1) {
        $rows = dbAffectedRows();
        $debug_msg = 'ROWS['.($rows < 1 ? '%r' : '%g').$rows.'%n]'.PHP_EOL.$debug_msg;
      }
      // After query debug output for cli
      print_message($debug_msg, 'console', FALSE);
    } else {
      print_error($error_msg);
    }
  }

  if ($result === FALSE && isset($GLOBALS['config']['db']['debug']) && $GLOBALS['config']['db']['debug'])
  {
    logfile('db.log', 'Failed dbQuery (#' . dbErrorNo() . ' - ' . dbError() . '), Query: ' . $fullSql);
  }

  return $result;
}

/*
 * This is intended to be the method used for large result sets.
 * It is intended to return an iterator, and act upon buffered data.
 * */
function dbFetch($sql, $parameters = array(), $print_query = FALSE)
{
  return dbFetchRows($sql, $parameters, $print_query);
}

/*
 * This method is quite different from fetchCell(), actually
 * It fetches one cell from each row and places all the values in 1 array
 * */
function dbFetchColumn($sql, $parameters = array(), $print_query = FALSE)
{
  $time_start = microtime(true);
  $cells = array();
  foreach (dbFetchRows($sql, $parameters, $print_query) as $row)
  {
    $cells[] = array_shift($row);
  }
  $time_end = microtime(true);

  $GLOBALS['db_stats']['fetchcol_sec'] += number_format($time_end - $time_start, 8);
  $GLOBALS['db_stats']['fetchcol']++;

  return $cells;
}

/*
 * Should be passed a query that fetches two fields
 * The first will become the array key
 * The second the key's value
 */
function dbFetchKeyValue($sql, $parameters = array(), $print_query = FALSE)
{
  $data = array();
  foreach (dbFetchRows($sql, $parameters, $print_query) as $row)
  {
    $key = array_shift($row);
    if (is_array($row) && count($row) === 1)
    { // if there were only 2 fields in the result
      // use the second for the value
      $data[$key] = array_shift($row);
    } else { // if more than 2 fields were fetched
      // use the array of the rest as the value
      $data[$key] = $row;
    }
  }

  return $data;
}

/*
 * Passed an array and a table name, it attempts to insert the data into the table.
 * Check for boolean false to determine whether insert failed
 * */
function dbInsert($data, $table, $print_query = FALSE)
{
  global $fullSql;

  // the following block swaps the parameters if they were given in the wrong order.
  // it allows the method to work for those that would rather it (or expect it to)
  // follow closer with SQL convention:
  // insert into the TABLE this DATA
  if (is_string($data) && is_array($table))
  {
    $tmp = $data;
    $data = $table;
    $table = $tmp;

    print_debug('Parameters passed to dbInsert() were in reverse order.');
  }

  $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', array_keys($data)) . '`)  VALUES (' . implode(',', dbPlaceHolders($data)) . ')';

  $time_start = microtime(true);
  //dbBeginTransaction();
  $result = dbQuery($sql, $data, $print_query);
  if ($result)
  {
    // This should return true if insert succeeded, but no ID was generated
    $id = dbLastID();
    //dbCommitTransaction();
  } else {
    //dbRollbackTransaction();
    $id = FALSE;
  }

  $time_end = microtime(true);
  $GLOBALS['db_stats']['insert_sec'] += number_format($time_end - $time_start, 8);
  $GLOBALS['db_stats']['insert']++;

  return $id;
}

/**
 * Passed an array and a table name, it attempts to insert the data into the table.
 * Check for boolean false to determine whether insert failed
 */
function dbInsertMulti($data, $table, $columns = NULL, $print_query = FALSE)
{
  global $fullSql;

  // the following block swaps the parameters if they were given in the wrong order.
  // it allows the method to work for those that would rather it (or expect it to)
  // follow closer with SQL convention:
  // insert into the TABLE this DATA
  if (is_string($data) && is_array($table))
  {
    $tmp = $data;
    $data = $table;
    $table = $tmp;

    print_debug('Parameters passed to dbInsertMulti() were in reverse order.');
  }

  // Detect if data is multiarray
  $first_data = reset($data);
  if (!is_array($first_data))
  {
    $first_data = $data;
    $data = array($data);
  }

  // Columns, if not passed use keys from first element
  if (empty($columns))
  {
    $columns = array_keys($first_data);
  }

  $values = array();
  // Multiarray data
  foreach ($data as $entry)
  {
    $entry = dbPrepareData($entry); // Escape data

    // Keep same columns order as in first entry
    $entries = array();
    foreach ($columns as $column)
    {
      $entries[$column] = $entry[$column];
    }

    $values[] = '(' . implode(',', $entries) . ')';
  }

  $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`)  VALUES ' . implode(',', $values);

  $time_start = microtime(true);
  //dbBeginTransaction();
  $result = dbQuery($sql, NULL, $print_query);
  if ($result)
  {
    // This should return true if insert succeeded, but no ID was generated
    $id = dbLastID();
    //dbCommitTransaction();
  } else {
    //dbRollbackTransaction();
    $id = FALSE;
  }

  $time_end = microtime(true);
  $GLOBALS['db_stats']['insert_sec'] += number_format($time_end - $time_start, 8);
  $GLOBALS['db_stats']['insert']++;

  return $id;
}

/*
 * Passed an array, table name, WHERE clause, and placeholder parameters, it attempts to update a record.
 * Returns the number of affected rows
 * */
function dbUpdate($data, $table, $where = NULL, $parameters = array(), $print_query = FALSE)
{
  global $fullSql;

  // the following block swaps the parameters if they were given in the wrong order.
  // it allows the method to work for those that would rather it (or expect it to)
  // follow closer with SQL convention:
  // update the TABLE with this DATA
  if (is_string($data) && is_array($table))
  {
    $tmp = $data;
    $data = $table;
    $table = $tmp;
    //trigger_error('QDB - The first two parameters passed to update() were in reverse order, but it has been allowed', E_USER_NOTICE);
  }

  // need field name and placeholder value
  // but how merge these field placeholders with actual $parameters array for the WHERE clause
  $sql = 'UPDATE `' . $table . '` set ';
  foreach ($data as $key => $value)
  {
    $sql .= "`".$key."` ". '=:' . $key . ',';
  }
  $sql = substr($sql, 0, -1); // strip off last comma

  if ($where)
  {
    // Remove WHERE clause at the beginning and ; at end
    $where = preg_replace(array('/^\s*WHERE\s+/i', '/\s*;\s*$/'), '', $where);
    $sql .= ' WHERE ' . $where;
    $data = array_merge($data, $parameters);
  }

  $time_start = microtime(true);
  if (dbQuery($sql, $data, $print_query))
  {
    $return = dbAffectedRows();
  } else {
    $return = FALSE;
  }
  $time_end = microtime(true);
  $GLOBALS['db_stats']['update_sec'] += number_format($time_end - $time_start, 8);
  $GLOBALS['db_stats']['update']++;

  return $return;
}

/**
 * Passed an array and a table name, it attempts to update the data in the table.
 * Check for boolean false to determine whether update failed
 * This is really INSERT with ODKU update
 * For key really better use only ID field!
 * https://stackoverflow.com/questions/25674737/mysql-update-multiple-rows-with-different-values-in-one-query/25674827
 */
function dbUpdateMulti($data, $table, $columns = NULL, $print_query = FALSE)
{
  global $fullSql;

  // the following block swaps the parameters if they were given in the wrong order.
  // it allows the method to work for those that would rather it (or expect it to)
  // follow closer with SQL convention:
  // insert into the TABLE this DATA
  if (is_string($data) && is_array($table))
  {
    $tmp = $data;
    $data = $table;
    $table = $tmp;

    print_debug('Parameters passed to dbUpdateMulti() were in reverse order.');
  }

  // Detect if data is multiarray
  $first_data = reset($data);
  if (!is_array($first_data))
  {
    $first_data = $data;
    $data = array($data);
  }

  // Columns, if not passed use keys from first element
  $all_columns = array_keys($first_data); // All columns data and UNIQUE indexes
  if (!empty($columns))
  {
    // Update only passed columns from param
    $update_columns = $columns;
  } else {
    // Fallback for all columns (also indexes),
    // this is normal, UNIQUE indexes not updated anyway
    $update_columns = $all_columns;
  }

  // Columns which will updated
  foreach ($update_columns as $key)
  {
    $update_keys[] = '`'.$key.'`=VALUES(`'.$key.'`)';
  }

  $values = array();
  // Multiarray data
  foreach ($data as $entry)
  {
    $entry = dbPrepareData($entry); // Escape data

    // Keep same columns order as in first entry
    $entries = array();
    foreach ($all_columns as $column)
    {
      $entries[$column] = $entry[$column];
    }

    $values[] = '(' . implode(',', $entries) . ')';
  }

  $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $all_columns) . '`)  VALUES ' . implode(',', $values);

  // This is only way for update multiple rows at once
  $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(',', $update_keys);
 
  $time_start = microtime(true);
  //dbBeginTransaction();
  if (dbQuery($sql, NULL, $print_query))
  {
    $return = dbAffectedRows(); // This value should be divided into two for innodb
  } else {
    $return = FALSE;
  }

  $time_end = microtime(true);
  $GLOBALS['db_stats']['update_sec'] += number_format($time_end - $time_start, 8);
  $GLOBALS['db_stats']['update']++;

  return $return;
}

function dbExist($table, $where = NULL, $parameters = array(), $print_query = FALSE)
{
  $sql = 'SELECT EXISTS (SELECT 1 FROM `' . $table . '`';
  if ($where)
  {
    // Remove WHERE clause at the beginning and ; at end
    $where = preg_replace(array('/^\s*WHERE\s+/i', '/\s*;\s*$/'), '', $where);
    $sql .= ' WHERE ' . $where;
  }
  $sql .= ')';

  $return = dbFetchCell($sql, $parameters, $print_query);
  //print_vars($return);

  return (bool)$return;
}

function dbDelete($table, $where = NULL, $parameters = array(), $print_query = FALSE)
{
  $sql = 'DELETE FROM `' . $table.'`';
  if ($where)
  {
    // Remove WHERE clause at the beginning and ; at end
    $where = preg_replace(array('/^\s*WHERE\s+/i', '/\s*;\s*$/'), '', $where);
    $sql .= ' WHERE ' . $where;
  }

  $time_start = microtime(true);
  if (dbQuery($sql, $parameters, $print_query))
  {
    $return = dbAffectedRows();
  } else {
    $return = FALSE;
  }
  $time_end = microtime(true);
  $GLOBALS['db_stats']['delete_sec'] += number_format($time_end - $time_start, 8);
  $GLOBALS['db_stats']['delete']++;

  return $return;
}

/*
 * This combines a query and parameter array into a final query string for execution
 * PDO drivers don't need to use this
 */
function dbMakeQuery($sql, $parameters)
{
  // bypass extra logic if we have no parameters
  
  if (safe_count($parameters) === 0)
  {
    return $sql;
  }

  $parameters = dbPrepareData($parameters);
  // separate the two types of parameters for easier handling
  $questionParams = array();
  $namedParams = array();
  foreach ($parameters as $key => $value)
  {
    if (is_numeric($key))
    {
      $questionParams[] = $value;
    } else {
      $namedParams[':' . $key] = $value;
    }
  }

  if (safe_count($namedParams) === 0)
  {
    // use simple pattern if named params not used (this broke some queries)
    $pattern = '/(\?)/';
  } else {
    // sort namedParams in reverse to stop substring squashing
    krsort($namedParams);
    // full pattern
    $pattern = '/(\?|:[a-zA-Z0-9_-]+)/';
  }

  // split on question-mark and named placeholders
  $result = preg_split($pattern, $sql, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

  // every-other item in $result will be the placeholder that was found

  $query = '';
  for ($i = 0; $i < safe_count($result); $i+=2)
  {
    $query .= $result[$i];

    $j = $i+1;
    if (array_key_exists($j, $result))
    {
      $test = $result[$j];
      if ($test === '?')
      {
        $query .= array_shift($questionParams);
      } else {
        $query .= $namedParams[$test];
      }
    }
  }

  return $query;
}

function dbPrepareData($data)
{
  $values = array();

  foreach ($data as $key => $value)
  {
    $escape = true;
    // don't quote if value is an array, we treat it
    // as a "decorator" that tells us not to escape the
    // value contained in the array IF there is one item in the array
    if (is_array($value) && !is_object($value))
    {
      if (count($value) === 1)
      {
        $escape = false;
        $value = array_shift($value);
        $value = dbEscape($value);
        // Probably we use this only for pass NULL,
        // but currently allow pass any other values and quote if space found
        if (preg_match('/\s+/', $value))
        {
          $value = "'" . $value . "'";
        }
      } else {
        // if this is a multi-value array, implode this as it's probably
        // (hopefully) used in an IN statement.
        $escape = false; // we'll escape on our own, thanks.
        // escape each entry by itself, unfortunately requires an extra array
        // but implode() can't first escape each string, of course.
        $escaped = [];
        foreach ($value as $entry)
        {
          $escaped[] = "'" . dbEscape($entry) . "'";
        }
        $value = implode(",", $escaped);
      }
    }

    // it's not right to worry about invalid fields in this method because we may be operating on fields
    // that are aliases, or part of other tables through joins
    //if (!in_array($key, $columns)) // skip invalid fields
    //  continue;
    if ($escape)
    {
      $values[$key] = "'" . dbEscape($value) . "'";
    } else {
      $values[$key] = $value;
    }
  }

  return $values;
}

/*
 * Given a data array, this returns an array of placeholders
 * These may be question marks, or ":email" type
 */
function dbPlaceHolders($values)
{
  $data = array();
  foreach ($values as $key => $value)
  {
    if (is_numeric($key))
    {
      $data[] = '?';
    } else {
      $data[] = ':' . $key;
    }
  }
  return $data;
}

/**
 * This function generates WHERE condition string from array with values
 * NOTE, value should be exploded by comma before use generate_query_values(), for example in get_vars()
 *
 * @param mixed  $value       Values
 * @param string $column      Table column name
 * @param string $condition   Compare condition, known: =, !=, NOT, NULL, NOT NULL, LIKE (and variants %LIKE%, %LIKE, LIKE%)
 * @param bool   $leading_and Add leading AND to result query
 * @return string             Generated query
 */
function generate_query_values($value, $column, $condition = NULL, $leading_and = TRUE) {
  //if (!is_array($value)) { $value = explode(',', $value); }
  if (!is_array($value)) { $value = array((string)$value); }
  $column = '`' . str_replace(array('`', '.'), array('', '`.`'), $column) . '`'; // I.column -> `I`.`column`
  $condition = ($condition === TRUE ? 'LIKE' : strtoupper(trim($condition)));
  if (str_contains_array($condition, [ 'NOT', '!=' ])) {
    $negative  = TRUE;
    $condition = str_replace(array('NOT', '!=', ' '), '', $condition);
  } else {
    $negative  = FALSE;
  }

  $search  = array('%', '_');
  $replace = array('\%', '\_');
  $values  = array();
  switch ($condition) {
    // Use LIKE condition
    case 'LIKE':
      // Replace (* by %) and (? by _) only for LIKE condition
      $search[]  = '*'; // any string
      $replace[] = '%';
      $search[]  = '?'; // any single char
      $replace[] = '_';
    case '%LIKE%':
    case '%LIKE':
    case 'LIKE%':
      if ($negative) { $implode = ' AND '; $like = ' NOT LIKE '; }
      else           { $implode = ' OR ';  $like = ' LIKE '; }
      foreach ($value as $v) {
        if ($v === '*') {
          $values = array("ISNULL($column, 1)" . $like . "'%'");
          break;
        } elseif ($v === '') {
          $values[] = "COALESCE($column, '')" . $like . "''";
        } else {
          $v = dbEscape($v); // Escape BEFORE replace!
          $v = str_replace($search, $replace, $v);
          $v = str_replace('LIKE', $v, $condition);
          $values[] = $column . $like . "'" . $v . "'";
        }
      }
      $values = array_unique($values); // Removes duplicate values
      if (count($values)) {
        $where = ' AND (' . implode($implode, $values) . ')';
      } else {
        // Empty values
        $where  = ' AND ';
        $where .= $negative ? '1' : '0';
      }
      break;
    // Use NULL condition
    case 'NULL':
      $value = array_shift($value);
      $value = ($negative) ? !$value : (bool)$value; // When required negative null condition (NOT NULL), reverse $value sign
      //r($value);
      if ($value) {
        $where = ' AND ' . $column . ' IS NULL';
      } else {
        $where = ' AND ' . $column . ' IS NOT NULL';
      }
      break;
    // Use IN condition
    default:
      $where = '';
      $add_null = FALSE;
      foreach ($value as $v) {
        if ($v == OBS_VAR_UNSET || $v === '') {
          $add_null = TRUE; // Add check NULL values at end
          $values[] = "''";
        } else {
          $values[] = "'" . dbEscape($v) . "'";
        }
      }
      $count = count($values);
      if ($count === 1) {
        $where .= $column . ($negative ? ' != ' : ' = ') . $values[0];
      } elseif ($count) {
        $values = array_unique($values); // Removes duplicate values
        $where .= $column . ($negative ? ' NOT IN (' : ' IN (') . implode(',', $values) . ')';
      } else {
        // Empty values
        $where = $negative ? '1' : '0';
      }
      if ($add_null) {
        // Add search for empty values
        if ($negative) {
          $where .= " AND $column IS NOT NULL";
        } else {
          $where .= " OR $column IS NULL";
        }
        $where = " AND ($where)";
      } else {
        $where = " AND " . $where;
      }
      break;
  }
  if (!$leading_and) { $where = preg_replace('/^(\ )+AND/', '', $where); }

  return $where;
}

/**
 * Export current Schema DB as array or json encoded string.
 *  can used for compare 2 schema for differences
 *
 * @param string $format
 *
 * @return array|string
 */
function export_db_schema($format = 'json')
{
  // Export current schema
  $db_schema = [];
  $db_tables = dbFetchRows('SHOW TABLE STATUS;');
  //print_vars($db_tables);
  //$db_tables = dbFetchColumn('SHOW TABLES;');
  foreach ($db_tables as $db_table)
  {
    $table_name = $db_table['Name'];

    // Main table params
    $db_schema[$table_name] = [
      'table'     => $table_name,
      'engine'    => $db_table['Engine'],
      'collation' => $db_table['Collation'],
      'comment'   => $db_table['Comment'],
    ];

    //print_cli("Table: ".$table_name.PHP_EOL);
    // Indexes
    foreach (dbFetchRows('SHOW INDEX FROM `' . $table_name . '`;') as $entry)
    {
      //print_vars($entry);
      if (isset($db_schema[$table_name]['indexes'][$entry['Key_name']]))
      {
        $db_schema[$table_name]['indexes'][$entry['Key_name']]['columns'][$entry['Seq_in_index']] = [ 'column' => $entry['Column_name'], 'size' => $entry['Sub_part'] ];
      } else {
        $db_schema[$table_name]['indexes'][$entry['Key_name']] = [
          'name'      => $entry['Key_name'],
          'columns'   => [ $entry['Seq_in_index'] => [ 'column' => $entry['Column_name'], 'size' => $entry['Sub_part'] ] ],
          'unique'    => $entry['Non_unique'] ? FALSE : TRUE,
          //'type'      => $entry['Index_type'],
          //'collation' => $entry['Collation'],
          'comment'   => $entry['Index_comment'],
        ];
      }
    }

    // Columns
    foreach (dbFetchRows('SHOW FULL COLUMNS FROM `' . $table_name . '`;') as $seq => $entry)
    {
      //print_vars($entry);
      // Normalize type with size
      $types = [];
      $unsigned = str_contains($entry['Type'], 'unsigned');
      foreach (explode(' ', $entry['Type']) as $type) {
        switch ($type) {
          case 'tinyint':
            $types[] = $unsigned ? $type . '(3)' : $type . '(4)';
            break;
          case 'smallint':
            $types[] = $unsigned ? $type . '(5)' : $type . '(6)';
            break;
          case 'mediumint':
            $types[] = $type . '(8)';
            break;
          case 'int':
            $types[] = $unsigned ? $type . '(10)' : $type . '(11)';
            break;
          case 'bigint':
            $types[] = $type . '(20)';
            break;
          default:
            // Already type hint: int(11)
            // Or not necessary: unsigned
            $types[] = $type;
        }
      }
      if (is_string($entry['Default'])) {
        $entry['Default'] = str_replace('current_timestamp()', 'CURRENT_TIMESTAMP', $entry['Default']);
      }
      if (is_string($entry['Extra'])) {
        $entry['Extra'] = str_replace('current_timestamp()', 'CURRENT_TIMESTAMP', $entry['Extra']);
      }
      if (str_contains($entry['Default'], 'CURRENT_TIMESTAMP') &&
          !str_contains($entry['Extra'], 'DEFAULT_GENERATED')) {
        $entry['Extra'] = rtrim('DEFAULT_GENERATED ' . $entry['Extra']);
      }

      $type = preg_replace('/ \/\* mariadb-\S+ \*\//', '', implode(' ', $types));
      $db_schema[$table_name]['columns'][$entry['Field']] = [
        'column'    => $entry['Field'],
        'seq'       => $seq,
        //'type'      => $entry['Type'],
        'type'      => $type,
        'collation' => $entry['Collation'],
        'null'      => $entry['Null'] === 'YES',
        'key'       => $entry['Key'],
        'default'   => $entry['Default'],
        'extra'     => $entry['Extra'],
        'comment'   => $entry['Comment'],
      ];
    }

    // Data
    if ($table_name === 'observium_attribs') {
      // Add schema version
      $db_schema[$table_name]['data'][] = [ 'attrib_type' => 'dbSchema', 'attrib_value' => get_db_version() . '' ];
    }

    // Create table
    $create = dbFetchRow("SHOW CREATE TABLE `$table_name`;");
    //print_vars($create);
    $db_schema[$table_name]['create'] = str_replace([ "\n ", "\n" ], '', $create['Create Table']);
    // Remove autoincrement
    $db_schema[$table_name]['create'] = preg_replace('/(\).*) AUTO_INCREMENT=\d+/', '$1', $db_schema[$table_name]['create']);
    // Remove strange comments ' /* mariadb-5.3 */'
    $db_schema[$table_name]['create'] = preg_replace('/ \/\* mariadb-\S+ \*\//', '', $db_schema[$table_name]['create']);
  }
  print_debug_vars($db_schema);
  if ($format === 'array')
  {
    return $db_schema;
  // }
  // elseif ($format == 'yaml' && function_exists('yaml_emit'))
  // {
  //   return yaml_emit($db_schema, YAML_UTF8_ENCODING);
  }

  // Default JSON
  return safe_json_encode($db_schema, JSON_PRETTY_PRINT);
}

/**
 * Import Schema DB created by export_db_schema() and convert to MySQL SQL.
 *
 * @param string|array Array or JSON encoded string with db schema
 * @return string List of SQL commands which can used as
 */
/*
function import_db_schema($db_schema)
{
}
*/

// EOF
