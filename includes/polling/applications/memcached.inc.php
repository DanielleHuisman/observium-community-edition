<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!empty($agent_data['app']['memcached'])) {
    foreach ($agent_data['app']['memcached'] as $memcached_host => $memcached_data) {

        // Only run if we have a valid host with a : separating host:port
        if (strpos($memcached_host, ":")) {
            echo(" memcached(" . $memcached_host . ") ");

            $app_id = discover_app($device, 'memcached', $memcached_host);

            // These are the keys we expect. If we fall back to the old value-only
            // data (instead of the new key:value data) we expect them exactly in
            // this order.
            $keys = ['accepting_conns', 'auth_cmds', 'auth_errors', 'bytes',
                     'bytes_read', 'bytes_written', 'cas_badval', 'cas_hits',
                     'cas_misses', 'cmd_flush', 'cmd_get', 'cmd_set',
                     'conn_yields', 'connection_structures',
                     'curr_connections', 'curr_items', 'decr_hits',
                     'decr_misses', 'delete_hits', 'delete_misses',
                     'evictions', 'get_hits', 'get_misses', 'incr_hits',
                     'incr_misses', 'limit_maxbytes', 'listen_disabled_num',
                     'pid', 'pointer_size', 'rusage_system', 'rusage_user',
                     'threads', 'time', 'total_connections', 'total_items',
                     'uptime', 'version'];

            // Initialise the expected values
            $values = [];
            foreach ($keys as $key) {
                $values[$key] = '0';
            }

            // Parse the data, first try key:value format
            $lines                   = explode("\n", $memcached_data);
            $fallback_to_values_only = FALSE;
            foreach ($lines as $line) {
                // Fall back to values only if we don't see a : separator
                if (!strstr($line, ':')) {
                    $fallback_to_values_only = TRUE;
                    break;
                }

                // Parse key:value line
                [$key, $value] = explode(':', $line, 2);
                $values[$key] = $value;
            }

            if ($fallback_to_values_only) {
                // See if we got the expected data
                if (count($keys) != count($lines)) {
                    // Skip this one, we don't know how to handle this data
                    echo("<- [skipped, incompatible data received] ");
                    continue;
                }

                // Combine keys and values
                echo("<- [old data format received, please upgrade agent] ");
                $values = array_combine($keys, $lines);
            }

            update_application($app_id, $values);
            rrdtool_update_ng($device, 'memcached', $values, $memcached_host);
        }
    }
}

// EOF
