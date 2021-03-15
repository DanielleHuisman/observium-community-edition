<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage db
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

/* Specific mysqli function calls, uses procedural style. */

// Set to TRUE if MySQLnd driver is being used
define('OBS_DB_MYSQLND', function_exists('mysqli_get_client_stats'));

/**
 * Get MySQL client info
 *
 * @return string $info
 */
function dbClientInfo()
{
  return mysqli_get_client_info();
}

/**
 * Returns a string representing the type of connection used
 *
 * @return string $info
 */
function dbHostInfo()
{
  return mysqli_get_host_info($GLOBALS[OBS_DB_LINK]);
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
function dbOpen($host = 'localhost', $user, $password, $database, $charset = 'utf8')
{
  // Check host params
  $host_array = explode(':', $host);
  if (count($host_array) > 1)
  {
    if ($host_array[0] === 'p')
    {
      // p:example.com
      // p:::1
      array_shift($host_array);
      $host = implode(':', $host_array);
      $GLOBALS['config']['db_persistent'] = TRUE;
    }
    elseif (count($host_array) === 2)
    {
      // This is for compatibility with old style host option (from mysql extension)
      // IPv6 host not possible here
      $host = $host_array[0];
      if (is_numeric($host_array[1]))
      {
        // example.com:3306
        $port   = $host_array[1];
      } else {
        // example.com:/tmp/mysql.sock
        $socket = $host_array[1];
      }
    }
  }

  // Server port
  if (is_numeric($GLOBALS['config']['db_port']))
  {
    $port = $GLOBALS['config']['db_port'];
  }
  elseif (!isset($port))
  {
    $port = ini_get("mysqli.default_port");
  }

  // Server socket
  if (strlen($GLOBALS['config']['db_socket']))
  {
    $socket = $GLOBALS['config']['db_socket'];
  }
  elseif (!isset($socket))
  {
    $socket = ini_get("mysqli.default_socket");
  }

  // Prepending host by p: for open a persistent connection.
  if ($GLOBALS['config']['db_persistent'] && ini_get('mysqli.allow_persistent'))
  {
    $host = 'p:' . $host;
  }

  // Init new connection
  $connection = mysqli_init();
  if ($connection === (object)$connection)
  {
    $client_flags = 0;
    // Optionally compress connection
    if ($GLOBALS['config']['db_compress'] && defined('MYSQLI_CLIENT_COMPRESS'))
    {
      $client_flags |= MYSQLI_CLIENT_COMPRESS;
    }

    // Optionally enable SSL
    if ($GLOBALS['config']['db_ssl'])
    {
      $client_flags |= MYSQLI_CLIENT_SSL;

      if (!empty($GLOBALS['config']['db_ssl_key']) || !empty($GLOBALS['config']['db_ssl_cert']) ||
          !empty($GLOBALS['config']['db_ssl_ca'])  || !empty($GLOBALS['config']['db_ssl_ca_path']) ||
          !empty($GLOBALS['config']['db_ssl_ciphers']))
      {
        $db_ssl_key     = $GLOBALS['config']['db_ssl_key']     ? $GLOBALS['config']['db_ssl_key'] : '';
        $db_ssl_cert    = $GLOBALS['config']['db_ssl_cert']    ? $GLOBALS['config']['db_ssl_cert'] : '';
        $db_ssl_ca      = $GLOBALS['config']['db_ssl_ca']      ? $GLOBALS['config']['db_ssl_ca'] : '';
        $db_ssl_ca_path = $GLOBALS['config']['db_ssl_ca_path'] ? $GLOBALS['config']['db_ssl_ca_path'] : '';
        $db_ssl_ciphers = $GLOBALS['config']['db_ssl_ciphers'] ? $GLOBALS['config']['db_ssl_ciphers'] : '';
        mysqli_ssl_set($connection, $db_ssl_key, $db_ssl_cert, $db_ssl_ca, $db_ssl_ca_path, $db_ssl_ciphers);
      }

      // Disables SSL certificate validation on mysqlnd for MySQL 5.6 or later
      // https://bugs.php.net/bug.php?id=68344
      if (!$GLOBALS['config']['db_ssl_verify'])
      {
        $client_flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;

        mysqli_options($connection, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, $GLOBALS['config']['db_ssl_verify']);
      }
    }

    // Convert integer and float columns back to PHP numbers. Boolean returns as int. Only valid for mysqlnd.
    /*
    if (defined('OBS_DB_MYSQLND') && OBS_DB_MYSQLND)
    {
      mysqli_options($connection, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, TRUE);
    }
    */
    // Report if no index or bad index was used in a query
    //mysqli_report(MYSQLI_REPORT_INDEX);

    if (!mysqli_real_connect($connection, $host, $user, $password, $database, (int)$port, $socket, $client_flags))
    {
      $error_num = mysqli_connect_errno($connection);
      if (OBS_DEBUG)
      {
        echo('MySQLi connection error ' . $error_num . ': ' . mysqli_connect_error($connection) . PHP_EOL);
      }
      if ($error_num == 2006 && version_compare(PHP_VERSION, '7.4.0', '>=') && version_compare(PHP_VERSION, '7.4.2', '<'))
      {
        print_error("PHP version less than 7.4.2 have multiple issues with MySQL 8.0, please update your PHP to latest.");
      }

      return NULL;
    }

    if ($charset)
    {
      mysqli_set_charset($connection, $charset);
      /*
      if (version_compare(PHP_VERSION, '5.3.6', '<') && $charset == 'utf8')
      {
        // Seem as problem to set default charset on php < 5.3.6
        mysqli_query($connection, "SET NAMES 'utf8' COLLATE 'utf8_general_ci';");
      }
      */
    }
  }

  return $connection;
}

/**
 * Closes a previously opened database connection
 *
 * @param object $connection Link to resource with mysql connection, default last used connection
 *
 * @return bool Returns TRUE on success or FALSE on failure.
 */
function dbClose($connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    print_error("Call to function mysqli_close() without link identifier.");
    return;
  }

  return mysqli_close($connection);
}

/**
 * Returns the text of the error message from last MySQL operation
 *
 * @param object $connection Link to resource with mysql connection, default last used connection
 *
 * @return string $return
 */
function dbError($connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    //print_error("Call to function mysqli_error() without link identifier.");
    return mysqli_connect_error();
  }

  return mysqli_error($connection);
}

/**
 * Returns the numerical value of the error message from last MySQL operation
 *
 * @param object $connection Link to resource with mysql connection, default last used connection
 *
 * @return string $return
 */
function dbErrorNo($connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    //print_error("Call to function mysqli_errno() without link identifier.");
    return mysqli_connect_errno();
  }

  return mysqli_errno($connection);
}

function dbWarnings($connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    return;
  }

  $warning = [];
  if (mysqli_warning_count($connection)) {
    $e = mysqli_get_warnings($connection);
    do {
      //echo "Warning: $e->errno: $e->message\n";
      $warning[] = "$e->errno: $e->message";
    } while ($e->next());
  }
  return $warning;
}

function dbPing($connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  }

  return mysqli_ping($connection);
}

function dbAffectedRows($connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    print_error("Call to function mysqli_affected_rows() without link identifier.");
    return;
  }

  return mysqli_affected_rows($connection);
}

function dbCallQuery($fullSql, $connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    print_error("Call to function mysqli_query() without link identifier.");
    return;
  }

  return mysqli_query($connection, $fullSql);
  //return mysqli_query($connection, $fullSql, MYSQLI_USE_RESULT); // Unbuffered results, for speedup!
}

/**
 * Returns escaped string
 *
 * @param string $string Input string for escape in mysql query
 * @param object $connection Link to resource with mysql connection, default last used connection
 *
 * @return string
 */
function dbEscape($string, $connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    print_error("Call to function mysqli_real_escape_string() without link identifier.");
    return;
  }

  $return = mysqli_real_escape_string($connection, $string);
  if (!isset($return[0]) && isset($string[0]))
  {
    // If character set empty, use escape alternative
    // FIXME. I really not know why, but in unittests $connection object is lost!
    print_debug("Mysql connection lost, in dbEscape() used escape alternative!");
    $search = array("\\", "\x00", "\n", "\r", "'", '"', "\x1a");
    $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");
    $return = str_replace($search, $replace, $string);
  }
  return $return;
}

/**
 * Returns the auto generated id used in the last query
 *
 * @param object $connection Link to resource with mysql connection, default last used connection
 *
 * @return string
 */
function dbLastID($connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    print_error("Call to function mysqli_insert_id() without link identifier.");
    return;
  }

  if ($id = mysqli_insert_id($connection))
  {
    //print_debug("DEBUG ID by function");
    //var_dump($id);
    return $id;
  } else {
    // mysqli_insert_id() not return last id, after non empty warnings
    //print_debug("DEBUG ID by query");
    //var_dump($id);
    return dbFetchCell('SELECT last_insert_id();');
  }
  //return mysqli_insert_id($connection);
}

/*
 * Fetches all of the rows (associatively) from the last performed query.
 * Most other retrieval functions build off this
 * */
function dbFetchRows($sql, $parameters = array(), $print_query = FALSE)
{
  $time_start = utime();
  $result = dbQuery($sql, $parameters, $print_query);

  $rows = array();
  if ($result instanceof mysqli_result)
  {
    if (OBS_DB_MYSQLND)
    {
      // MySQL Native Driver
      $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
      while ($row = mysqli_fetch_assoc($result))
      {
        $rows[] = $row;
      }
    }
    mysqli_free_result($result);

    $time_end = utime();
    $GLOBALS['db_stats']['fetchrows_sec'] += $time_end - $time_start;
    $GLOBALS['db_stats']['fetchrows']++;
  }

  // no records, thus return empty array
  // which should evaluate to false, and will prevent foreach notices/warnings
  return $rows;
}

/*
 * Like fetch(), accepts any number of arguments
 * The first argument is an sprintf-ready query stringTypes
 * */
function dbFetchRow($sql = NULL, $parameters = array(), $print_query = FALSE)
{
  $time_start = utime();
  $result = dbQuery($sql, $parameters, $print_query);
  if ($result)
  {
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    $time_end = utime();

    $GLOBALS['db_stats']['fetchrow_sec'] += $time_end - $time_start;
    $GLOBALS['db_stats']['fetchrow']++;

    return $row;
  } else {
    return NULL;
  }
}

/*
 * Fetches the first call from the first row returned by the query
 * */
function dbFetchCell($sql, $parameters = array(), $print_query = FALSE)
{
  $time_start = utime();
  //$row = dbFetchRow($sql, $parameters);
  $result = dbQuery($sql, $parameters, $print_query);
  if ($result)
  {
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    $time_end = utime();

    $GLOBALS['db_stats']['fetchcell_sec'] += $time_end - $time_start;
    $GLOBALS['db_stats']['fetchcell']++;

    if (is_array($row))
    {
      return array_shift($row); // shift first field off first row
    }
  }

  return NULL;
}

function dbBeginTransaction($connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    print_error("Call to begin db transaction without link identifier.");
    return;
  }

  mysqli_autocommit($connection, FALSE); // Set autocommit to off
}

function dbCommitTransaction($connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    print_error("Call to commmit db transaction without link identifier.");
    return;
  }

  mysqli_commit($connection);
  mysqli_autocommit($connection, TRUE); // Restore autocommit to on
}

function dbRollbackTransaction($connection = NULL)
{
  // Observium uses $observium_link global variable name for db link
  if      ($connection === (object)$connection) {}
  else if ($GLOBALS[OBS_DB_LINK] === (object)$GLOBALS[OBS_DB_LINK])
  {
    $connection = $GLOBALS[OBS_DB_LINK];
  } else {
    print_error("Call to rollback db transaction without link identifier.");
    return;
  }

  mysqli_rollback($connection);
  mysqli_autocommit($connection, TRUE); // Restore autocommit to on
}

// EOF
