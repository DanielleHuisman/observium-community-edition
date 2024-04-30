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

/* Specific mysqli function calls, uses procedural style. */

// Set to TRUE if MySQLnd driver is being used
define('OBS_DB_MYSQLND', function_exists('mysqli_get_client_stats'));

/**
 * Get MySQL client info
 *
 * @return string
 */
function dbClientInfo() {
    return mysqli_get_client_info();
}

/**
 * Returns a string representing the type of connection used
 *
 * @return string $info
 */
function dbHostInfo($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        if (!OBS_DB_SKIP) {
            print_error("Call to function mysqli_get_host_info() without link identifier.");
        }
        return '';
    }

    return mysqli_get_host_info($connection);
}

/**
 * Open connection to mysql server
 *
 * @param string $database Database name
 * @param string $user     Username for connect to mysql server
 * @param string $password Username password
 * @param string $host     Hostname for mysql server, default 'localhost'
 * @param string $charset  Charset used for mysql connection, default 'utf8'
 *
 * @return object $connection
 */
function dbOpen($host, $user, $password, $database, $charset = 'utf8') {

    // Server port
    $port = $GLOBALS['config']['db_port'] ?? ini_get("mysqli.default_port");

    // Server socket
    $socket = $GLOBALS['config']['db_socket'] ?? ini_get("mysqli.default_socket");

    // Prepending host by p: for open a persistent connection.
    if (isset($GLOBALS['config']['db_persistent']) && $GLOBALS['config']['db_persistent'] &&
        ini_get('mysqli.allow_persistent')) {
        $host = 'p:' . $host;
    }

    // Init new connection
    $time_start                     = utime();
    $GLOBALS['db_stats']['connect'] = 1;
    $connection                     = mysqli_init();
    if ($connection === (object)$connection) {
        $client_flags = 0;
        // Optionally compress connection
        if ($GLOBALS['config']['db_compress'] && defined('MYSQLI_CLIENT_COMPRESS')) {
            $client_flags |= MYSQLI_CLIENT_COMPRESS;
        }

        // Optionally enable SSL
        if ($GLOBALS['config']['db_ssl']) {
            $client_flags |= MYSQLI_CLIENT_SSL;

            if (!empty($GLOBALS['config']['db_ssl_key']) || !empty($GLOBALS['config']['db_ssl_cert']) ||
                !empty($GLOBALS['config']['db_ssl_ca']) || !empty($GLOBALS['config']['db_ssl_ca_path']) ||
                !empty($GLOBALS['config']['db_ssl_ciphers'])) {
                $db_ssl_key     = $GLOBALS['config']['db_ssl_key'] ?: '';
                $db_ssl_cert    = $GLOBALS['config']['db_ssl_cert'] ?: '';
                $db_ssl_ca      = $GLOBALS['config']['db_ssl_ca'] ?: '';
                $db_ssl_ca_path = $GLOBALS['config']['db_ssl_ca_path'] ?: '';
                $db_ssl_ciphers = $GLOBALS['config']['db_ssl_ciphers'] ?: '';
                mysqli_ssl_set($connection, $db_ssl_key, $db_ssl_cert, $db_ssl_ca, $db_ssl_ca_path, $db_ssl_ciphers);
            }

            // Disables SSL certificate validation on mysqlnd for MySQL 5.6 or later
            // https://bugs.php.net/bug.php?id=68344
            if (!$GLOBALS['config']['db_ssl_verify']) {
                $client_flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;

                mysqli_options($connection, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, $GLOBALS['config']['db_ssl_verify']);
            }
        }

        // Connection timeout
        //$timeout = (isset($GLOBALS['config']['db_timeout']) && $GLOBALS['config']['db_timeout'] >= 1) ? (int) $GLOBALS['config']['db_timeout'] : 30;
        //mysqli_options($connection, MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);

        // Convert integer and float columns back to PHP numbers. Boolean returns as int. Only valid for mysqlnd.
        /*
        if (defined('OBS_DB_MYSQLND') && OBS_DB_MYSQLND)
        {
          mysqli_options($connection, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, TRUE);
        }
        */
        // Report if no index or bad index was used in a query
        //mysqli_report(MYSQLI_REPORT_INDEX);

        // Set default ignore errors on php8.1
        // https://php.watch/versions/8.1/mysqli-error-mode
        mysqli_report(MYSQLI_REPORT_OFF);

        if (!mysqli_real_connect($connection, $host, $user, $password, $database, (int)$port, $socket, $client_flags)) {
            $error_num = @mysqli_connect_errno();
            if (defined('OBS_DEBUG') && OBS_DEBUG) {
                echo('MySQLi connection error ' . $error_num . ': ' . @mysqli_connect_error() . PHP_EOL);
            }
            if ($error_num == 2006 && PHP_VERSION_ID >= 70400 && PHP_VERSION_ID < 70402) {
                print_error("PHP version less than 7.4.2 have multiple issues with MySQL 8.0, please update your PHP to latest.");
            }

            $GLOBALS['db_stats']['connect_sec'] = elapsed_time($time_start);

            return NULL;
        }

        $GLOBALS['db_stats']['connect_sec'] = elapsed_time($time_start);
        //print_vars($GLOBALS['db_stats']['connect_sec']);

        if ($charset) {
            mysqli_set_charset($connection, $charset);
        }
    }

    return $connection;
}

function dbConnectionValid(&$connection) {
    // Observium uses $observium_link global variable name for db link
    if ($connection === (object)$connection) {
        return TRUE;
    }
    if (isset($GLOBALS[OBS_DB_LINK]) && $GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK]) {
        $connection = $GLOBALS[OBS_DB_LINK];
        return TRUE;
    }

    return FALSE;
}

/**
 * Closes a previously opened database connection
 *
 * @param mysqli $connection Link to resource with mysql connection, default last used connection
 *
 * @return bool Returns TRUE on success or FALSE on failure.
 */
function dbClose($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        if (!OBS_DB_SKIP) {
            print_error("Call to function mysqli_close() without link identifier.");
        }
        return FALSE;
    }

    return mysqli_close($connection);
}

/**
 * Returns the text of the error message from last MySQL operation
 *
 * @param mysqli $connection Link to resource with mysql connection, default last used connection
 *
 * @return string $return
 */
function dbError($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        // if (!OBS_DB_SKIP) {
        //     print_error("Call to function mysqli_error() without link identifier.");
        // }
        return mysqli_connect_error();
    }

    return mysqli_error($connection);
}

/**
 * Returns the numerical value of the error message from last MySQL operation
 *
 * @param mysqli $connection Link to resource with mysql connection, default last used connection
 *
 * @return int $return
 */
function dbErrorNo($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        // if (!OBS_DB_SKIP) {
        //     print_error("Call to function mysqli_errno() without link identifier.");
        // }
        return mysqli_connect_errno();
    }

    return mysqli_errno($connection);
}

/**
 * Returns array with a list of warnings.
 *
 * @param mysqli $connection Link to resource with mysql connection, default last used connection
 *
 * @return array|false
 */
function dbWarnings($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        // if (!OBS_DB_SKIP) {
        //     print_error("Call to function mysqli_get_warnings() without link identifier.");
        // }
        return FALSE;
    }

    $warning = [];
    if (mysqli_warning_count($connection)) {
        $e = mysqli_get_warnings($connection);
        do {
            //echo "Warning: $e->errno: $e->message\n";
            $warning[] = "$e->errno: $e->message";
        } while ($e -> next());
    }

    return $warning;
}

function dbPing($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        // if (!OBS_DB_SKIP) {
        //     print_error("Call to function mysqli_ping() without link identifier.");
        // }
        return FALSE;
    }

    return mysqli_ping($connection);
}

function dbAffectedRows($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        if (!OBS_DB_SKIP) {
            print_error("Call to function mysqli_affected_rows() without link identifier.");
        }
        return FALSE;
    }

    return mysqli_affected_rows($connection);
}

function dbCallQuery($fullSql, $connection = NULL) {

    if (!dbConnectionValid($connection)) {
        if (!OBS_DB_SKIP) {
            print_error("Call to function mysqli_query() without link identifier.");
        }
        return FALSE;
    }

    return mysqli_query($connection, $fullSql);
    //return mysqli_query($connection, $fullSql, MYSQLI_USE_RESULT); // Unbuffered results, for speedup!
}

/**
 * Returns escaped string
 *
 * @param string $string     Input string for escape in mysql query
 * @param mysqli $connection Link to resource with mysql connection, default last used connection
 *
 * @return string
 */
function dbEscape($string, $connection = NULL) {

    if (!dbConnectionValid($connection)) {
        if (!OBS_DB_SKIP) {
            print_error("Call to function mysqli_real_escape_string() without link identifier.");
        }
        //return FALSE;

        // FIXME. I really not know why, but in unittests $connection object is lost!
        //print_debug("Mysql connection lost, in dbEscape() used escape alternative!");
        $search  = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];
        return str_replace($search, $replace, $string);
    }

    $return = mysqli_real_escape_string($connection, $string);
    if (!isset($return[0]) && isset($string[0])) {
        // If character set empty, use escape alternative
        // FIXME. I really not know why, but in unittests $connection object is lost!
        print_debug("Mysql connection lost, in dbEscape() used escape alternative!");
        $search  = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];
        $return  = str_replace($search, $replace, $string);
    }
    return $return;
}

/**
 * Returns the auto generated id used in the last query
 *
 * @param mysqli $connection Link to resource with mysql connection, default last used connection
 *
 * @return string
 */
function dbLastID($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        if (!OBS_DB_SKIP) {
            print_error("Call to function mysqli_insert_id() without link identifier.");
        }
        return FALSE;
    }

    if ($id = mysqli_insert_id($connection)) {
        //print_debug("DEBUG ID by function");
        //var_dump($id);
        return $id;
    }

    // mysqli_insert_id() not return last id, after non empty warnings
    //print_debug("DEBUG ID by query");
    //var_dump($id);
    return dbFetchCell('SELECT last_insert_id();');
    //return mysqli_insert_id($connection);
}

/**
 * Fetches all the rows (associatively) from the last performed query.
 * Most other retrieval functions build off this
 *
 * @param string $sql
 * @param array $parameters
 * @param bool $print_query
 *
 * @return array
 */
function dbFetchRows($sql, $parameters = [], $print_query = FALSE) {
    $time_start = utime();
    $result     = dbQuery($sql, $parameters, $print_query);

    $rows = [];
    if ($result instanceof mysqli_result) {
        if (OBS_DB_MYSQLND) {
            // MySQL Native Driver
            $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        mysqli_free_result($result);

        $parent = @debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];
        //var_dump($parent);

        if ($parent !== 'dbFetchColumn') {
            $GLOBALS['db_stats']['fetchrows_sec'] += elapsed_time($time_start);
            $GLOBALS['db_stats']['fetchrows']++;
        }
    }

    // no records, thus return empty array
    // which should evaluate to false, and will prevent foreach notices/warnings
    return $rows;
}

/**
 * Process all the rows (associatively) from the last performed query.
 * Passed anonymous function to each row.
 * It Can be used to skip get a full array of a query and make specific operations.
 * NOTE. Do not use it for collect arrays, only for process data
 *
 * @param callable $func
 * @param string $sql
 * @param array $parameters
 * @param bool $print_query
 *
 * @return array
 */
function dbFetchFunc($func, $sql, $parameters = [], $print_query = FALSE) {
    $time_start = utime();
    $result     = dbQuery($sql, $parameters, $print_query);

    $rows = [];
    if ($result instanceof mysqli_result) {

        $i = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            if ($return = $func($row, $i++)) {
                //$rows += $func($row, $i++); // not correct recursive addition
                $rows = array_replace_recursive($rows, (array)$return);
            }
            if ($return === FALSE) {
                // Break query walk on FALSE return
                print_debug("DEBUG: dbFetchFunc() stopped while loop. Query:\n".$sql);
                break;
            }
        }
        mysqli_free_result($result);
    }

    $parent = @debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];
    //var_dump($parent);

    if ($parent === 'dbFetchColumn') {
        $GLOBALS['db_stats']['fetchcol_sec'] += elapsed_time($time_start);
        $GLOBALS['db_stats']['fetchcol']++;
    } else {
        $GLOBALS['db_stats']['fetchfunc_sec'] += elapsed_time($time_start);
        $GLOBALS['db_stats']['fetchfunc']++;
    }

    // no records, thus return empty array
    // which should evaluate to false, and will prevent foreach notices/warnings
    return $rows;
}

/*
 * Like fetch(), accepts any number of arguments
 * The first argument is an sprintf-ready query stringTypes
 * */
function dbFetchRow($sql = NULL, $parameters = [], $print_query = FALSE) {
    $time_start = utime();
    $result     = dbQuery($sql, $parameters, $print_query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        $GLOBALS['db_stats']['fetchrow_sec'] += elapsed_time($time_start);
        $GLOBALS['db_stats']['fetchrow']++;

        return $row;
    }
    return [];
}

/*
 * Fetches the first call from the first row returned by the query
 * */
function dbFetchCell($sql, $parameters = [], $print_query = FALSE) {

    $time_start = utime();
    //$row = dbFetchRow($sql, $parameters);
    $result = dbQuery($sql, $parameters, $print_query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        $GLOBALS['db_stats']['fetchcell_sec'] += elapsed_time($time_start);
        $GLOBALS['db_stats']['fetchcell']++;

        if (is_array($row)) {
            return array_shift($row); // shift first field off first row
        }
    }

    return NULL; // or ''?
}

function dbBeginTransaction($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        if (!OBS_DB_SKIP) {
            print_error("Call to begin db transaction without link identifier.");
        }
        return FALSE;
    }

    mysqli_autocommit($connection, FALSE); // Set autocommit to off
}

function dbCommitTransaction($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        if (!OBS_DB_SKIP) {
            print_error("Call to commit db transaction without link identifier.");
        }
        return FALSE;
    }

    mysqli_commit($connection);
    mysqli_autocommit($connection, TRUE); // Restore autocommit to on
}

function dbRollbackTransaction($connection = NULL) {

    if (!dbConnectionValid($connection)) {
        if (!OBS_DB_SKIP) {
            print_error("Call to rollback db transaction without link identifier.");
        }
        return FALSE;
    }

    mysqli_rollback($connection);
    mysqli_autocommit($connection, TRUE); // Restore autocommit to on
}

// EOF
