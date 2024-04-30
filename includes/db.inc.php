<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage db
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

/* Here common DB functions which use calls to specific api functions */

// Initial variables
$db_stats = [
    'insert'    => 0, 'insert_sec'    => 0,
    'update'    => 0, 'update_sec'    => 0,
    'delete'    => 0, 'delete_sec'    => 0,
    'fetchcell' => 0, 'fetchcell_sec' => 0,
    'fetchrow'  => 0, 'fetchrow_sec'  => 0,
    'fetchrows' => 0, 'fetchrows_sec' => 0,
    'fetchcol'  => 0, 'fetchcol_sec'  => 0
];

// Include DB api.
if (@function_exists('mysqli_connect')) {
    require($config['install_dir'] . '/includes/db/mysqli.inc.php');
} else {
    echo('ERROR. PHP mysqli extension does not exist. Execution is stopped.' . PHP_EOL);
    exit(2);
}

/**
 * Provides server status information
 *
 * @param string $scope GLOBAL or SESSION variable scope modifier
 *
 * @return array Array with server status variables
 */
function dbShowStatus($scope = 'SESSION') {
    if ($scope === 'GLOBAL') {
        $sql = 'SHOW GLOBAL STATUS';
    } else {
        $sql = 'SHOW STATUS';
    }

    //return dbFetchAssocRows('Variable_name=>Value', $sql);
    return dbFetchKeyValue($sql);
    /*
    $rows = [];
    foreach (dbFetchRows($sql) as $row) {
        $rows[$row['Variable_name']] = $row['Value'];
    }

    return $rows;
    */
}

/**
 * Shows the values of MySQL system variables
 *
 * @param string $where WHERE or LIKE clause
 * @param string $scope GLOBAL or SESSION variable scope modifier
 *
 * @return array Array with variables
 */
function dbShowVariables($where = '', $scope = 'SESSION') {
    if ($scope === 'GLOBAL') {
        $sql = 'SHOW GLOBAL VARIABLES';
    } else {
        $sql = 'SHOW VARIABLES';
    }
    if (!safe_empty($where)) {
        $sql .= ' ' . $where;
    }

    //return dbFetchAssocRows('Variable_name=>Value', $sql);
    return dbFetchKeyValue($sql);
    /*
    $rows = [];
    foreach (dbFetchRows($sql) as $row) {
        $rows[$row['Variable_name']] = $row['Value'];
    }

    return $rows;
    */
}

function dbSetVariable($var, $value, $scope = 'SESSION') {
    if (!is_string($var) || safe_empty($value) || !preg_match('/^[A-Z_]+$/i', $var)) {
        return;
    }

    if (!in_array($scope, [ 'GLOBAL', 'SESSION' ])) {
        $scope = 'SESSION';
    }

    // Define DB NAME for later use (same as in get_versions())
    if (!defined('OBS_DB_NAME')) {
        get_versions('mariadb');
    }

    // Override MySQL variable with MariaDB analog
    if ($var === 'MAX_EXECUTION_TIME' && OBS_DB_NAME === 'MariaDB') {
        $var   = 'MAX_STATEMENT_TIME';
        $value /= 1000;
    }

    if (is_float($value) || is_intnum($value)) {
        $value = [ $value ]; // pass as raw
    }

    return dbQuery("SET $scope $var=?;", [ $value ]);
}

/**
 * Provides table index list
 *
 * @param string $table      Table name
 * @param string $index_name Index name (if empty get all indexes)
 *
 * @return array Array with table indexes: array()->$key_name->$column_name
 */
function dbShowIndexes($table, $index_name = NULL) {
    $table = dbEscape($table);
    if ($index_name) {
        $sql = 'SHOW INDEX FROM `' . $table . '` WHERE ' . generate_query_values($index_name, 'Key_name');
    } else {
        $sql = 'SHOW INDEX FROM `' . $table . '`;';
    }

    return dbFetchAssocRows('Key_name->Column_name', $sql);
    /*
    $rows = [];
    foreach (dbFetchRows($sql) as $row) {
        $rows[$row['Key_name']][$row['Column_name']] = $row;
    }

    return $rows;
    */
}

/**
 * Provides table column list
 *
 * @param string $table       Table name
 * @param string $column_name Column name (if empty get all indexes)
 *
 * @return array Array with a column list
 */
function dbShowColumns($table, $column_name = NULL) {
    $table = dbEscape($table);
    if ($column_name) {
        $sql = 'SHOW COLUMNS FROM `' . $table . '` WHERE ' . generate_query_values($column_name, 'Field');
    } else {
        $sql = 'SHOW COLUMNS FROM `' . $table . '`;';
    }

    return dbFetchAssocRows('Field', $sql);
    /*
    $columns = [];
    foreach (dbFetchRows($sql) as $entry) {
        //print_vars($entry);
        $columns[$entry['Field']] = $entry;
    }

    return $columns;
    */
}

/**
 * Get next Auto Increment value for main table ID
 *
 * @param string $table Table name
 *
 * @return integer Next auti increment id
 */
function dbShowNextID($table) {
    $table = dbEscape($table);
    $sql   = "SHOW TABLE STATUS LIKE '$table';";

    $row = dbFetchRow($sql);
    //print_debug_vars($row);

    return $row['Auto_increment'] ?? FALSE;
}

/*
 * Performs a query using the given string.
 * Used by the other _query functions.
 * */
function dbQuery($sql, $parameters = [], $print_query = FALSE) {
    global $fullSql;

    //r($_REQUEST);
    // if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'print_query')) {
    //   $print_query = TRUE;
    // }

    $fullSql = dbMakeQuery($sql, $parameters);

    // if (!$print_query && $GLOBALS['config']['devel'] &&
    //     PHP_SAPI !== 'cli' && str_contains($_SERVER['REQUEST_URI'], '/print_query')) {
    //     $print_query = TRUE;
    // }
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
        $time_start = microtime(TRUE);
        $debug_msg  = '';
    }

    $result = dbCallQuery($fullSql); // sets $this->result

    if ($debug > 0 || $GLOBALS['config']['profile_sql']) {
        $runtime   = elapsed_time($time_start, 8);
        $debug_msg .= 'SQL RUNTIME[' . ($runtime > 0.05 ? '%r' : '%g') . $runtime . 's%n]';
        if ($GLOBALS['config']['profile_sql']) {
            $backtrace                        = debug_backtrace();
            $first_database_function          = NULL;
            $first_database_function_location = NULL;

            // Loop through the call stack in reverse order
            for ($i = count($backtrace) - 1; $i >= 0; $i--) {
                if (isset($backtrace[$i]['function']) && str_starts_with($backtrace[$i]['function'], 'db')) {
                    $function = $backtrace[$i]['function'];
                    $location = $backtrace[$i]['file'];
                    $line     = $backtrace[$i]['line'];
                    break;
                }
            }


            $GLOBALS['sql_profile'][] = [
              'sql'      => $fullSql,
              'time'     => $runtime,
              'function' => $function,
              'location' => $location,
              'line'     => $line
            ];
        }
    }

    if ($debug > 0) {
        if ($result === FALSE && (error_reporting() & 1)) {
            $error_msg = 'Error in query: (' . dbError() . ') ' . dbErrorNo();
            $debug_msg .= PHP_EOL . 'SQL ERROR[%r' . $error_msg . '%n]';
        }
        if ($debug > 1 && $warnings = dbWarnings()) {
            $debug_msg .= PHP_EOL . "SQL WARNINGS[\n %m" . implode("%n\n %m", $warnings) . "%n\n]";
        }

        if (is_cli()) {
            if ($debug > 1) {
                $rows      = dbAffectedRows();
                $debug_msg = 'ROWS[' . ($rows < 1 ? '%r' : '%g') . $rows . '%n]' . PHP_EOL . $debug_msg;
            }
            // After query debug output for cli
            print_message($debug_msg, 'console', FALSE);
        } else {
            print_error($error_msg);
        }
    }

    if ($result === FALSE && isset($GLOBALS['config']['db']['debug']) && $GLOBALS['config']['db']['debug']) {
        logfile('db.log', 'Failed dbQuery (#' . dbErrorNo() . ' - ' . dbError() . '), Query: ' . $fullSql);
    }

    return $result;
}

/**
 * This method is quite different from fetchCell(), actually
 * It fetches one cell from each row and places all the values in 1 array
 */
function dbFetchColumnNg($sql, $parameters = [], $print_query = FALSE) {
    $func = static function($row, $i) {
        return [ $i => reset($row) ];
    };
    return dbFetchFunc($func, $sql, $parameters, $print_query);
}

function dbFetchColumn($sql, $parameters = [], $print_query = FALSE) {
    $time_start = microtime(TRUE);
    $cells      = [];
    foreach (dbFetchRows($sql, $parameters, $print_query) as $row) {
        $cells[] = array_shift($row);
    }

    $GLOBALS['db_stats']['fetchcol_sec'] += elapsed_time($time_start, 8);
    $GLOBALS['db_stats']['fetchcol']++;

    return $cells;
}

/**
 * Should be passed a query that fetches two fields
 * The first will become the array key
 * The second the key's value
 */
function dbFetchKeyValue($sql, $parameters = [], $print_query = FALSE) {

    $func = static function($row) {
        if (count($row) === 2) {
            return [ reset($row) => next($row) ];
        }
        // dbFetchKeyRows
        return [ reset($row) => $row ];
    };
    return dbFetchFunc($func, $sql, $parameters, $print_query);
}

function dbFetchKeyRows($sql, $parameters = [], $print_query = FALSE) {

    $func = static function($row) {
        return [ reset($row) => $row ];
    };
    return dbFetchFunc($func, $sql, $parameters, $print_query);
}

/**
 * Examples of usage.
 * Same results:
 * $query = "SELECT `sysObjectID`, COUNT(*) AS `count` FROM `devices` WHERE `os` = 'generic' GROUP BY `sysObjectID`";
 * // 1. Double foreach(). High memory usage
 * $stats = [];
 * foreach (dbFetchRows($query) as $data) {
 *     $stats[$data['sysObjectID']] = $data['count'];
 * }
 *
 * // 2. Single foreach(). Less memory usage. NOT checking of keys on association
 * $stats = dbFetchKeyValue($query);
 *
 * // 3. Single foreach(). Less memory usage. Associate key=>value
 * $stats = dbFetchAssocRows('sysObjectID=>count', $query);
 *
 * @param string $keys
 * @param string $sql
 * @param array $parameters
 * @param bool $print_query
 *
 * @return array
 */
function dbFetchAssocRows($keys, $sql, $parameters = [], $print_query = FALSE) {

    $func = static function($row) use ($keys) {
        $keys = explode('->', $keys);
        $len = count($keys) - 1;
        $array = [];
        $current = &$array;
        foreach ($keys as $i => $key) {
            if ($last = ($i === $len)) {
                // last key with value association, ie: device_id=>sensor_value
                [ $key, $key_value ] = explode('=>', $key, 2);
            }
            if (!array_key_exists($key, (array)$row)) {
                break;
            }
            $value = $row[$key];
            if ($last) {
                // last key
                $current[$value] = !is_null($key_value) ? $row[$key_value] : $row;
                break;
            }

            $current = &$current[$value];
        }
        return $array;
    };

    return dbFetchFunc($func, $sql, $parameters, $print_query);
}

/**
 * Passed an array and a table name, it attempts to insert the data into the table.
 * Check for boolean false to determine whether insert failed
 */
function dbInsert($data, $table, $print_query = FALSE) {
    global $fullSql;

    // the following block swaps the parameters if they were given in the wrong order.
    // it allows the method to work for those that would rather it (or expect it to)
    // follow closer with SQL convention:
    // insert into the TABLE this DATA
    if (is_string($data) && is_array($table)) {
        $tmp   = $data;
        $data  = $table;
        $table = $tmp;

        print_debug('Parameters passed to dbInsert() were in reverse order.');
    }

    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', array_keys($data)) . '`)  VALUES (' . implode(',', dbPlaceHolders($data)) . ')';

    $time_start = microtime(TRUE);
    //dbBeginTransaction();
    $result = dbQuery($sql, $data, $print_query);
    if ($result) {
        // This should return true if insert succeeded, but no ID was generated
        $id = dbLastID();
        //dbCommitTransaction();
    } else {
        //dbRollbackTransaction();
        $id = FALSE;
    }

    $GLOBALS['db_stats']['insert_sec'] += elapsed_time($time_start, 8);
    $GLOBALS['db_stats']['insert']++;

    return $id;
}

function dbInsertRowMulti($row, $table, $id_key = NULL) {
    global $cache_db;

    if (is_string($id_key) && isset($row[$id_key])) {
        print_error("Incorrect insert db table '$table' entry passed (not should be have ID '$id_key'cell).");
        print_debug_vars($row);
    } else {
        // no validations here
        $cache_db[$table]['insert'][] = $row;
    }
}

/**
 * Passed an array and a table name, it attempts to insert the data into the table.
 * Check for boolean false to determine whether insert failed
 */
function dbInsertMulti($data, $table, $columns = NULL, $print_query = FALSE) {
    global $fullSql;

    // the following block swaps the parameters if they were given in the wrong order.
    // it allows the method to work for those that would rather it (or expect it to)
    // follow closer with SQL convention:
    // insert into the TABLE this DATA
    if (is_string($data) && is_array($table)) {
        $tmp   = $data;
        $data  = $table;
        $table = $tmp;

        print_debug('Parameters passed to dbInsertMulti() were in reverse order.');
    }

    // Detect if data is multiarray
    $first_data = reset($data);
    if (!is_array($first_data)) {
        $first_data = $data;
        $data       = [ $data ];
    }

    if (safe_empty($data)) {
        print_debug("Passed empty data array to dbInsertMulti().");
        return FALSE;
    }

    // Columns, if not passed, use keys from a first element
    if (empty($columns)) {
        $columns = array_keys($first_data);
    }

    $values = [];
    // Multiarray data
    foreach ($data as $entry) {
        $entry = dbPrepareData($entry); // Escape data

        // Keep the same columns order as in the first entry
        $entries = [];
        foreach ($columns as $column) {
            $entries[$column] = $entry[$column];
        }

        $values[] = '(' . implode(',', $entries) . ')';
    }

    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`)  VALUES ' . implode(',', $values);

    $time_start = microtime(TRUE);
    //dbBeginTransaction();
    if ($result = dbQuery($sql, NULL, $print_query)) {
        // This should return true if insert succeeded, but no ID was generated
        $id = dbLastID();
        //dbCommitTransaction();
    } else {
        //dbRollbackTransaction();
        $id = FALSE;
    }

    $GLOBALS['db_stats']['insert_sec'] += elapsed_time($time_start, 8);
    $GLOBALS['db_stats']['insert']++;

    return $id;
}

/*
 * Passed an array, table name, WHERE clause, and placeholder parameters, it attempts to update a record.
 * Returns the number of affected rows
 * */
function dbUpdate($data, $table, $where = NULL, $parameters = [], $print_query = FALSE) {
    global $fullSql;

    // the following block swaps the parameters if they were given in the wrong order.
    // it allows the method to work for those that would rather it (or expect it to)
    // follow closer with SQL convention:
    // update the TABLE with this DATA
    if (is_string($data) && is_array($table)) {
        $tmp   = $data;
        $data  = $table;
        $table = $tmp;
        //trigger_error('QDB - The first two parameters passed to update() were in reverse order, but it has been allowed', E_USER_NOTICE);
    }

    // need field name and placeholder value
    // but how merge these field placeholders with actual $parameters array for the WHERE clause
    $sql = 'UPDATE `' . $table . '` set ';
    foreach ($data as $key => $value) {
        $sql .= "`" . $key . "` " . '=:' . $key . ',';
    }
    $sql = substr($sql, 0, -1); // strip off last comma

    if ($where) {
        // Remove WHERE clause at the beginning and ; at end
        $where = preg_replace(['/^\s*WHERE\s+/i', '/\s*;\s*$/'], '', $where);
        $sql   .= ' WHERE ' . $where;
        $data  = array_merge($data, $parameters);
    }

    $time_start = microtime(TRUE);
    if (dbQuery($sql, $data, $print_query)) {
        $return = dbAffectedRows();
    } else {
        $return = FALSE;
    }

    $GLOBALS['db_stats']['update_sec'] += elapsed_time($time_start, 8);
    $GLOBALS['db_stats']['update']++;

    return $return;
}

function dbUpdateRowMulti($row, $table, $id_key = NULL) {
    global $cache_db;

    if (is_string($id_key) && isset($row[$id_key])) {
        // id_key used for validation that entry already exist
        $cache_db[$table]['update'][$row[$id_key]] = $row;
    } else {
        // no validations here
        $cache_db[$table]['update'][] = $row;
    }
}

/**
 * Passed an array and a table name, it attempts to update the data in the table.
 * Check for boolean false to determine whether update failed
 * This is really INSERT with ODKU update
 * For key really better use only ID field!
 * https://stackoverflow.com/questions/25674737/mysql-update-multiple-rows-with-different-values-in-one-query/25674827
 */
function dbUpdateMulti($data, $table, $columns = NULL, $print_query = FALSE) {
    global $fullSql;

    // the following block swaps the parameters if they were given in the wrong order.
    // it allows the method to work for those that would rather it (or expect it to)
    // follow closer with SQL convention:
    // insert into the TABLE this DATA
    if (is_string($data) && is_array($table)) {
        $tmp   = $data;
        $data  = $table;
        $table = $tmp;

        print_debug('Parameters passed to dbUpdateMulti() were in reverse order.');
    }

    // Detect if data is multiarray
    $first_data = reset($data);
    if (!is_array($first_data)) {
        $first_data = $data;
        $data       = [$data];
    }
    if (safe_empty($data)) {
        print_debug("Passed empty data array to dbUpdateMulti().");
        return FALSE;
    }

    if (!defined('OBS_DB_NAME')) {
        get_versions('mysql');
    }
    $ng = OBS_DB_NAME === 'MySQL' && version_compare(get_versions('mysql'), '8.0', '>=');
    if ($ng) {
        // https://stackoverflow.com/questions/63609570/mysql-values-function-is-deprecated
        print_debug('dbUpdateMulti() used new style of values insert for MySQL 8+.');
    }

    // Columns, if not passed, use keys from a first element
    $all_columns = array_keys($first_data); // All columns data and UNIQUE indexes
    if (!empty($columns)) {
        // Update only passed columns from param
        $update_columns = $columns;
    } else {
        // Fallback for all columns (also indexes),
        // this is normal, UNIQUE indexes not updated anyway
        $update_columns = $all_columns;
    }

    // Columns which will updated
    $update_keys = [];
    foreach ($update_columns as $key) {
        if ($ng) {
            $update_keys[] = '`' . $key . '`=`mvalues`.`' . $key . '`';
        } else {
            $update_keys[] = '`' . $key . '`=VALUES(`' . $key . '`)';
        }
    }

    $values = [];
    // Multiarray data
    foreach ($data as $entry) {
        $entry = dbPrepareData($entry); // Escape data

        // Keep same columns order as in first entry
        $entries = [];
        foreach ($all_columns as $column) {
            $entries[$column] = $entry[$column];
        }

        $values[] = '(' . implode(',', $entries) . ')';
    }

    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $all_columns) . '`)  VALUES ' . implode(',', $values);
    if ($ng) {
        $sql .= ' AS `mvalues`';
    }

    // This is an only way for update multiple rows at once
    $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(',', $update_keys);

    $time_start = microtime(TRUE);
    //dbBeginTransaction();
    if (dbQuery($sql, NULL, $print_query)) {
        $return = dbAffectedRows(); // This value should be divided into two for innodb
    } else {
        $return = FALSE;
    }

    $GLOBALS['db_stats']['update_sec'] += elapsed_time($time_start, 8);
    $GLOBALS['db_stats']['update']++;

    return $return;
}

function dbProcessMulti($table, $print_query = FALSE) {
    global $cache_db;

    print_debug_vars($cache_db);
    $clean = FALSE;

    // Multi insert
    if (isset($cache_db[$table]['insert'])) {
        dbInsertMulti($cache_db[$table]['insert'], $table, NULL, $print_query);

        $clean = TRUE;
    }

    // Multi update
    if (isset($cache_db[$table]['update'])) {
        dbUpdateMulti($cache_db[$table]['update'], $table, NULL, $print_query);

        $clean = TRUE;
    }

    // Clean
    if ($clean) {
        unset($cache_db[$table]);
    }
}

function dbExist($table, $where = NULL, $parameters = [], $print_query = FALSE) {
    $sql = 'SELECT EXISTS (SELECT 1 FROM `' . $table . '`';
    if ($where) {
        // Remove WHERE clause at the beginning and ; at end
        $where = preg_replace(['/^\s*WHERE\s+/i', '/\s*;\s*$/'], '', $where);
        $sql   .= ' WHERE ' . $where;
    }
    $sql .= ')';

    $return = dbFetchCell($sql, $parameters, $print_query);
    //print_vars($return);

    return (bool)$return;
}

function dbDelete($table, $where = NULL, $parameters = [], $print_query = FALSE) {
    $sql = 'DELETE FROM `' . $table . '`';
    if ($where) {
        // Remove WHERE clause at the beginning and ; at end
        $where = preg_replace(['/^\s*WHERE\s+/i', '/\s*;\s*$/'], '', $where);
        $sql   .= ' WHERE ' . $where;
    }

    $time_start = microtime(TRUE);
    if (dbQuery($sql, $parameters, $print_query)) {
        $return = dbAffectedRows();
    } else {
        $return = FALSE;
    }

    $GLOBALS['db_stats']['delete_sec'] += elapsed_time($time_start, 8);
    $GLOBALS['db_stats']['delete']++;

    return $return;
}

/*
 * This combines a query and parameter array into a final query string for execution
 * PDO drivers don't need to use this
 */
function dbMakeQuery($sql, $parameters) {
    // bypass extra logic if we have no parameters

    if (safe_count($parameters) === 0) {
        return $sql;
    }

    $parameters = dbPrepareData($parameters);
    // separate the two types of parameters for easier handling
    $questionParams = [];
    $namedParams    = [];
    foreach ($parameters as $key => $value) {
        if (is_numeric($key)) {
            $questionParams[] = $value;
        } else {
            $namedParams[':' . $key] = $value;
        }
    }

    if (safe_count($namedParams) === 0) {
        // use a simple pattern if named params not used (this broke some queries)
        $pattern = '/(\?)/';
    } else {
        // sort namedParams in reverse to stop substring squashing
        krsort($namedParams);
        // full pattern
        $pattern = '/(\?|:[a-zA-Z0-9_-]+)/';
    }

    // split on question-mark and named placeholders
    $result = preg_split($pattern, $sql, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    // every-other item in $result will be the placeholder that was found

    $query = '';
    for ($i = 0; $i < safe_count($result); $i += 2) {
        $query .= $result[$i];

        $j = $i + 1;
        if (array_key_exists($j, $result)) {
            $test = $result[$j];
            if ($test === '?') {
                $query .= array_shift($questionParams);
            } else {
                $query .= $namedParams[$test];
            }
        }
    }

    return $query;
}

function dbPrepareData($data) {
    $values = [];

    foreach ($data as $key => $value) {
        $escape = TRUE;
        // don't quote if value is an array, we treat it
        // as a "decorator" that tells us not to escape the
        // value contained in the array IF there is one item in the array
        if (is_array($value) && !is_object($value)) {
            $escape = FALSE; // we'll escape on our own, thanks.
            if (count($value) === 1) {
                $value  = array_shift($value);
                $value  = dbEscape($value);
                // Probably we use this only for pass NULL,
                // but currently allow to pass any other values and quote if space found
                if (preg_match('/\s+/', $value)) {
                    $value = "'" . $value . "'";
                }
            } else {
                // if this is a multi-value array, implode this as it's probably
                // (hopefully) used in an IN statement.
                $escaped = [];
                foreach ($value as $entry) {
                    $escaped[] = "'" . dbEscape($entry) . "'";
                }
                $value = implode(",", $escaped);
            }
        }

        // it's not right to worry about invalid fields in this method because we may be operating on fields
        // that are aliases, or part of other tables through joins
        //if (!in_array($key, $columns)) // skip invalid fields
        //  continue;
        if ($escape) {
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
function dbPlaceHolders($values) {
    $data = [];
    foreach ($values as $key => $value) {
        if (is_numeric($key)) {
            $data[] = '?';
        } else {
            $data[] = ':' . $key;
        }
    }
    return $data;
}

function db_config() {
    global $config;

    // Check host params
    if (str_contains($config['db_host'], ':')) {
        $host_array = explode(':', $config['db_host']);
        if ($host_array[0] === 'p') {
            // p:example.com
            // p:::1
            array_shift($host_array);
            $config['db_host']       = implode(':', $host_array);
            $config['db_persistent'] = TRUE;
        } elseif (count($host_array) === 2) {
            // This is for compatibility with an old style host option (from mysql extension)
            // IPv6 host not possible here
            $config['db_host'] = $host_array[0];
            if (is_valid_param($host_array[1], 'port')) {
                // example.com:3306
                $config['db_port'] = $host_array[1];
            } elseif (is_valid_param($host_array[1], 'path')) {
                // example.com:/tmp/mysql.sock
                $config['db_socket'] = $host_array[1];
            }
        }
    }

    // Server port
    if (!isset($config['db_port']) || !is_numeric($config['db_port'])) {
        if ($port = ini_get("mysqli.default_port")) {
            $config['db_port'] = $port;
        }
    }
}

/**
 * This function generates WHERE condition string from array with values
 * NOTE, value should be exploded by comma before use generate_query_values(), for example in get_vars().
 *
 * NOTE, WHERE condition always preceded with leading AND!
 *
 * @param mixed  $value     Values
 * @param string $column    Table column name
 * @param string $condition Compare condition, known: =, !=, NOT, NULL, NOT NULL, LIKE (and variants %LIKE%, %LIKE, LIKE%)
 * @param array  $options   ifnull - add IFNULL(column, '')
 *
 * @return string             Generated query
 */
function generate_query_values_and($value, $column, $condition = NULL, $options = []) {
    $options[] = 'leading_and';
    return generate_query_values($value, $column, $condition, $options);
}

/**
 * This function generates WHERE condition string from array with values
 * NOTE, value should be exploded by comma before use generate_query_values(), for example in get_vars()
 *
 * @param mixed  $value       Values
 * @param string $column      Table column name
 * @param string $condition   Compare condition, known: =, !=, NOT, NULL, NOT NULL, LIKE (and variants %LIKE%, %LIKE, LIKE%)
 * @param array  $options     leading_and - Add leading AND to result query,
 *                            ifnull - add IFNULL(column, '')
 *
 * @return string             Generated query
 */
function generate_query_valuesxxx($value, $column, $condition = NULL, $options = [])
{

    global $int_stats;

    $start = utime();

    if (!is_array($value)) {
        $value = [ (string)$value ];
    }
    $column    = '`' . str_replace([ '`', '.' ], [ '', '`.`' ], $column) . '`'; // I.column -> `I`.`column`
    $condition = $condition === TRUE ? 'LIKE' : strtoupper(trim($condition));
    if (str_contains_array($condition, [ 'NOT', '!=' ])) {
        $negative  = TRUE;
        $condition = str_replace([ 'NOT', '!=', ' ' ], '', $condition);
    } else {
        $negative = FALSE;
    }

    // Options
    $leading_and = in_array('leading_and', (array)$options, TRUE);
    $ifnull      = in_array('ifnull', (array)$options, TRUE);

    $search  = [ '%', '_' ];
    $replace = [ '\%', '\_' ];
    $values  = [];

    // Pre-check to avoid unnecessary processing for default case
    $auto_condition = in_array($condition, ['LIKE', '%LIKE%', '%LIKE', 'LIKE%', 'REGEXP']);

    foreach ($value as $v) {
        // Determine the condition for the current value
        $current_condition = $condition;

        if ($auto_condition) {
            // Determine the condition for the current value
            if (strpos($v, '*') !== false || strpos($v, '?') !== false) {
                if ($current_condition === '%LIKE%') {
                    $current_condition = 'LIKE';
                }
            }

            // Check for regex pattern and switch condition to REGEXP
            if (preg_match('/^\/.*\/$/', $v)) {
                $current_condition = 'REGEXP';
                $v = substr($v, 1, -1); // Remove the surrounding slashes
            }
        }

        switch ($current_condition) {
            case 'LIKE':
            case '%LIKE%':
            case '%LIKE':
            case 'LIKE%':
                $searchLike  = $search;
                $replaceLike = $replace;
                // Replace (* by %) and (? by _) only for LIKE condition
                if ($current_condition === 'LIKE' || $current_condition === '%LIKE%' || $current_condition === '%LIKE' || $current_condition === 'LIKE%') {
                    $searchLike[]  = '*'; // any string
                    $replaceLike[] = '%';
                    $searchLike[]  = '?'; // any single char
                    $replaceLike[] = '_';
                }
                if ($negative) {
                    $implode = ' AND ';
                    $cond    = ' NOT LIKE ';
                } else {
                    $implode = ' OR ';
                    $cond    = ' LIKE ';
                }
                $v        = dbEscape($v); // Escape BEFORE replace!
                $v        = str_replace($searchLike, $replaceLike, $v);
                $v        = str_replace('LIKE', $v, $current_condition);
                $values[] = $column . $cond . "'" . $v . "'";
                break;

            case 'REGEXP':
                if ($negative) {
                    $implode = ' AND ';
                    $cond    = ' NOT REGEXP ';
                } else {
                    $implode = ' OR ';
                    $cond    = ' REGEXP ';
                }
                $values[] = $column . $cond . "'" . dbEscape($v) . "'";
                break;

            // Use NULL condition
            case 'NULL':
                $value = array_shift($value);
                $value = ($negative) ? !$value : (bool)$value; // When required negative null condition (NOT NULL), reverse $value sign
                //r($value);
                if ($value) {
                    $where = $column . ' IS NULL';
                } else {
                    $where = $column . ' IS NOT NULL';
                }
                //$where = ($leading_and ? ' AND ' : ' ') . $where;
                break;

            // Use IN condition
            default:
                $where    = '';
                $add_null = FALSE;
                foreach ($value as $v) {
                    if ($v === OBS_VAR_UNSET || $v === '') {
                        $add_null = TRUE; // Add check NULL values at end
                        $values[] = "''";
                    } else {
                        $values[] = is_intnum($v) ? $v : "'" . dbEscape($v) . "'";
                    }
                }
                $count = count($values);
                if ($count === 1) {
                    $where .= ($add_null || $ifnull) ? "IFNULL($column, '')" : $column;
                    $where .= ($negative ? ' != ' : ' = ') . $values[0];
                } elseif ($count) {
                    $values = array_unique($values); // Removes duplicate values
                    $where  .= ($add_null || $ifnull) ? "IFNULL($column, '')" : $column;
                    $where  .= ($negative ? ' NOT IN (' : ' IN (') . implode(',', $values) . ')';
                } else {
                    // Empty values
                    $where = $negative ? '1' : '0';
                }
                break;
        }
    }

    if ($leading_and) {
        $where = 'AND ' . $where;
    }

    $elapsed = elapsed_time($start, 5);
    $int_stats['gen_query_values_sec'] += $elapsed;
    $int_stats['gen_query_values']++;

    r($elapsed);
    r($where);

    return ' ' . $where;
}

/**
 * This function generates WHERE condition string from array with values
 * NOTE, value should be exploded by comma before use generate_query_values(), for example in get_vars()
 *
 * @param mixed  $value       Values
 * @param string $column      Table column name
 * @param string $condition   Compare condition, known:
 *                              =, !=, NOT, NULL, NOT NULL, REGEXP, NOT REGEXP
 *                              LIKE (and variants %LIKE%, %LIKE, LIKE%)
 * @param array  $options     leading_and - Add leading AND to result query,
 *                            ifnull      - Add IFNULL(column, '')
 *
 * @return string             Generated query
 */
function generate_query_values($value, $column, $condition = NULL, $options = []) {

    if (!is_array($value)) {
        $value = [ (string)$value ];
    }
    $column    = '`' . str_replace([ '`', '.' ], [ '', '`.`' ], $column) . '`'; // I.column -> `I`.`column`
    $condition = $condition === TRUE ? 'LIKE' : strtoupper(trim($condition));
    if ($negative = str_contains_array($condition, [ 'NOT', '!=' ])) {
        $condition = trim(str_replace([ 'NOT', '!=' ], '', $condition));
    }

    // Options
    $leading_and = in_array('leading_and', (array)$options, TRUE);
    $ifnull      = in_array('ifnull', (array)$options, TRUE);

    $search  = [ '%', '_' ];
    $replace = [ '\%', '\_' ];
    $values  = [];
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
            if ($negative) {
                $implode = ' AND ';
                $cond    = ' NOT LIKE ';
            } else {
                $implode = ' OR ';
                $cond    = ' LIKE ';
            }
            foreach ($value as $v) {
                if ($v === '*') {
                    $values = [ "ISNULL($column, 1)" . $cond . "'%'" ];
                    break;
                }
                if ($v === '') {
                    $values[] = "COALESCE($column, '')" . $cond . "''";
                } else {
                    $v        = dbEscape($v); // Escape BEFORE replace!
                    $v        = str_replace($search, $replace, $v);
                    $v        = str_replace('LIKE', $v, $condition);
                    $values[] = $column . $cond . "'" . $v . "'";
                }
            }
            $values = array_unique($values); // Removes duplicate values
            if (count($values)) {
                $where = '(' . implode($implode, $values) . ')';
            } else {
                // Empty values
                $where = $negative ? '1' : '0';
            }
            break;

        // Use REGEXP condition
        case 'REGEX':
        case 'REGEXP':
            if ($negative) {
                $implode = ' AND ';
                $cond    = ' NOT REGEXP ';
            } else {
                $implode = ' OR ';
                $cond    = ' REGEXP ';
            }

            foreach ($value as $v) {
                $values[] = $column . $cond . "'" . dbEscape($v) . "'"; // FIXME. not sure about escape, but need fixate!
            }
            $values = array_unique($values); // Removes duplicate values
            if (count($values)) {
                $where = '(' . implode($implode, $values) . ')';
            } else {
                // Empty values
                $where = $negative ? '1' : '0';
            }
            break;

        // Use NULL condition
        case 'NULL':
            $value = array_shift($value);
            $value = ($negative) ? !$value : (bool)$value; // When required negative null condition (NOT NULL), reverse $value sign
            //r($value);
            if ($value) {
                $where = $column . ' IS NULL';
            } else {
                $where = $column . ' IS NOT NULL';
            }
            break;

        // Numeric compare conditions
        case '>':
        case '>=':
        case '<':
        case '<=':
            $num   = str_starts_with($condition, '<') ? min($value) : max($value);
            $value = array_shift($value);
            if (is_numeric($num)) {
                $where = $column . " $condition " . $num;
            } elseif (!empty($value) && !str_contains($value, ';')) {
                $where = $column . " $condition " . dbEscape($value);
            } else {
                // Empty values
                $where = $negative ? '1' : '0';
            }
            break;

        // Use IN or = or != condition
        default:
            $where    = '';
            $add_null = FALSE;
            foreach ($value as $v) {
                if ($v === OBS_VAR_UNSET || $v === '') {
                    $add_null = TRUE; // Add check NULL values at end
                    $values[] = "''";
                } else {
                    $values[] = is_intnum($v) ? $v : "'" . dbEscape($v) . "'";
                }
            }
            $count = count($values);
            if ($count === 1) {
                $where .= ($add_null || $ifnull) ? "IFNULL($column, '')" : $column;
                $where .= ($negative ? ' != ' : ' = ') . $values[0];
            } elseif ($count) {
                $values = array_unique($values); // Removes duplicate values
                $where  .= ($add_null || $ifnull) ? "IFNULL($column, '')" : $column;
                $where  .= ($negative ? ' NOT IN (' : ' IN (') . implode(',', $values) . ')';
            } else {
                // Empty values
                $where = $negative ? '1' : '0';
            }
            // if ($add_null) {
            //   // Add search for empty values
            //   if ($negative) {
            //     $where .= " AND $column IS NOT NULL";
            //   } else {
            //     $where .= " OR $column IS NULL";
            //   }
            //   $where = " AND ($where)";
            // } else {
            //$where = " AND " . $where;
            // }
            break;
    }

    if ($leading_and) {
        // Append leading AND
        return ' AND ' . $where;
    }
    return ' ' . $where;
}


/**
 * Generate ORDER BY query string.
 *
 * @param string|array $columns list of columns
 * @param string $sort_order ASC, DESC, ''
 * @param string|null $default_type Force cast column as datatype: number/decimal, integer
 *
 * @return string
 */
function generate_query_sort($columns, $sort_order = '', $default_type = NULL) {
    if (!is_array($columns)) {
        $columns = explode(',', $columns);
    }

    $order_array = [];
    foreach ($columns as $column) {
        $null_sort = '';
        if ($column[0] === '-') {
            $null_sort = '-'; // this trick sort null values at last
            $column    = substr($column, 1);
        }
        // Column with sort order, when multiple columns with own order
        $column_order = $sort_order;
        if (str_contains($column, ' ')) {
            [ $column, $column_order ] = explode(' ', $column, 2);
        }
        // Column type: integer/decimal
        $column_type = $default_type;
        if (str_contains($column, ':')) {
            [ $column, $column_type ] = explode(':', $column, 2);
        }
        $column_array = explode('.', $column);
        $sort_column  = $null_sort . '`' . implode('`.`', $column_array) . '`';

        if ($column_type === 'number' || $column_type === 'decimal') {
            // https://dba.stackexchange.com/questions/21156/sort-a-varchar-field-numerically-in-mysql
            //$sort_column .= ' + 0';
            $sort_column = 'CAST(' . $sort_column . ' AS DECIMAL)';
        } elseif ($column_type === 'integer') {
            //$sort_column .= ' + 0';
            $sort_column = 'CAST(' . $sort_column . ' AS SIGNED INTEGER)';
        }
        $order_array[] = $sort_column . ($column_order ? ' ' . strtoupper($column_order) : '');
    }

    if (!empty($order_array)) {
        return ' ORDER BY ' . implode(', ', $order_array);
    }

    return '';
}

/**
 * Get sort order key from vars: ASC, DESC, ''
 *
 * @param array $vars
 * @param bool $invert
 *
 * @return string
 */
function get_sort_order(&$vars = [], $invert = FALSE) {
    switch ($vars['sort_order']) {
        case 'desc':
            $sort_neg = 'ASC';
            return $invert ? 'ASC' : 'DESC';
        case 'reset':
            unset($vars['sort'], $vars['sort_order']);
            return '';
        default:
            $sort_neg = 'DESC';
            return $invert ? 'DESC' : 'ASC';
    }
}

function generate_query_limit(&$vars, $total = 0) {
    if (!isset($vars['pageno'])) {
        pagination($vars, $total, TRUE); // Get default pagesize/pageno
    }
    $start    = $vars['pagesize'] * $vars['pageno'] - $vars['pagesize'];
    $pagesize = $vars['pagesize'];

    return " LIMIT $start,$pagesize";
}

/**
 * Export current Schema DB as array or json encoded string.
 *  can used for compare 2 schema for differences
 *
 * @param string $format
 *
 * @return array|string
 */
function export_db_schema($format = 'json') {
    // Export current schema
    $db_schema = [];
    $db_tables = dbFetchRows('SHOW TABLE STATUS;');
    //print_vars($db_tables);
    //$db_tables = dbFetchColumn('SHOW TABLES;');
    foreach ($db_tables as $db_table) {
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
        foreach (dbFetchRows('SHOW INDEX FROM `' . $table_name . '`;') as $entry) {
            //print_vars($entry);
            if (isset($db_schema[$table_name]['indexes'][$entry['Key_name']])) {
                $db_schema[$table_name]['indexes'][$entry['Key_name']]['columns'][$entry['Seq_in_index']] = [
                    'column' => $entry['Column_name'],
                    'size' => $entry['Sub_part']
                ];
            } else {
                $db_schema[$table_name]['indexes'][$entry['Key_name']] = [
                    'name'    => $entry['Key_name'],
                    'columns' => [ $entry['Seq_in_index'] => [ 'column' => $entry['Column_name'], 'size' => $entry['Sub_part'] ] ],
                    'unique'  => !$entry['Non_unique'],
                    //'type'      => $entry['Index_type'],
                    //'collation' => $entry['Collation'],
                    'comment' => $entry['Index_comment'],
                ];
            }
        }

        // Columns
        foreach (dbFetchRows('SHOW FULL COLUMNS FROM `' . $table_name . '`;') as $seq => $entry) {
            //print_vars($entry);
            // Normalize type with size
            $types    = [];
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
        $db_schema[$table_name]['create'] = str_replace(["\n ", "\n"], '', $create['Create Table']);
        // Remove autoincrement
        $db_schema[$table_name]['create'] = preg_replace('/(\).*) AUTO_INCREMENT=\d+/', '$1', $db_schema[$table_name]['create']);
        // Remove strange comments ' /* mariadb-5.3 */'
        $db_schema[$table_name]['create'] = preg_replace('/ \/\* mariadb-\S+ \*\//', '', $db_schema[$table_name]['create']);
    }
    print_debug_vars($db_schema);
    if ($format === 'array') {
        return $db_schema;
    }

    // Default JSON
    return safe_json_encode($db_schema, JSON_PRETTY_PRINT);
}

/**
 * Import Schema DB created by export_db_schema() and convert to MySQL SQL.
 *
 * @param string|array Array or JSON encoded string with db schema
 *
 * @return string List of SQL commands which can used as
 */
/*
function import_db_schema($db_schema)
{
}
*/

// EOF
