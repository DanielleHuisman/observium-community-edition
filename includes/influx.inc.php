<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     influx
 * @author         Bill Fenner <fenner@gmail.com>
 * @copyright  (C) 2017 Observium Limited
 *
 */

/* This is very much a feature in testing. Please monitor servers closely after enabling.

/**
 * Escapes a string for the InfluxDB Line Protocol.
 * https://docs.influxdata.com/influxdb/v1.2/write_protocols/line_protocol_tutorial/
 *
 * @param string str
**/
function influxdb_escape($str)
{
    $str = str_replace(',', '\,', $str);
    $str = str_replace('=', '\=', $str);
    $str = str_replace(' ', '\ ', $str);
    return $str;
}

/**
 * Quotes an array for the InfluxDB Line Protocol.
 * https://docs.influxdata.com/influxdb/v1.2/write_protocols/line_protocol_tutorial/
 *
 * @param array data
 **/
function influxdb_format_data($data)
{
    $values = [];
    foreach ($data as $key => $value) {
        $values[] = influxdb_escape($key) . "=" . influxdb_escape($value);
    }
    return implode(",", $values);
}

/**
 * Posts an update to InfluxDB.
 *
 * @param string database
 * @param array  tags
 * @param array  data
 **/
function influxdb_update_data($database, $tags, $data)
{
    global $config;

    if (!$config['influxdb']['enabled']) {
        return;
    }

    $influx_update = $database;
    if ($tags) {
        $influx_update = $influx_update . "," . influxdb_format_data($tags);
    }
    $influx_update = $influx_update . " " . influxdb_format_data($data);

    // This code posts each update to influx individually.
    // Influx can accept multiple updates in one post, but
    // is very equivocal about how many -- the documentation
    // says you "may" have to split up your request if you
    // have more than 5,000 updates.  We should be able to
    // build up a queue of updates, and flush them to the
    // server if the queue gets too long, or at the end of the
    // poll, but until it becomes a real problem, we post the
    // udpates one by one.
    if ($config['influxdb']['debug']) {
        $f = fopen('/tmp/influx-updates.txt', 'a');
        fwrite($f, $influx_update . "\n");
        fclose($f);
        return;
    }
    $url = 'http://' . $config['influxdb']['server'] . '/write?db=' . $config['influxdb']['db'];
    $c   = curl_init();
    curl_setopt_array($c, [
      CURLOPT_URL           => $url,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS    => $influx_update,
    ]);
    $response = curl_exec($c);
    $httpcode = curl_getinfo($c, CURLINFO_HTTP_CODE);
    $error    = 'cURL error num: ' . curl_errno($c) . ' => ' . curl_error($c);
    print_debug('INFLUXDB POST: ' . $httpcode . ' ' . $error);
    if (OBS_DEBUG > 1) {
        print_message('INFLUXDB REQUEST ' . print_r(curl_getinfo($c), TRUE), 'console');
        print_message('INFLUXDB RESPONSE ' . print_r($response, TRUE), 'console');
    }
    curl_close($c);
}

/**
 * Pushes a set of data points that we want to add to an rrd file
 * to influxdb too.
 *
 * @param array  device
 * @param string filename
 * @param array  ds
 * @param array  definition
 **/
function influxdb_update($device, $filename, $ds, $definition = NULL, $index = NULL)
{
    global $config;

    $start = microtime(TRUE);

    // This happens when called from obsolete rrdtool_update
    // This code can go away when rrdtool_update is deleted.
    if (!is_array($ds)) {
        $tmpds = explode(':', $ds);
        // get rid of the timestamp (it's always "N")
        array_shift($tmpds);
        $ds = [];
        if (count($tmpds) == 1) {
            $ds['value'] = $tmpds[0];
        } else {
            foreach ($tmpds as $idx => $value) {
                $ds['value' . (string)($idx + 1)] = $value;
            }
        }
    }
    $influx_tags = [];
    if (!is_null($device)) {
        $influx_tags['host'] = $device['hostname'];
    }
    if (!is_null($index)) {
        // If we have an index, then we have a definition too.
        $filename             = $definition['file'];
        $filename             = str_replace('-%index%', '', $filename);
        $influx_tags['index'] = $index;
    }
    $filename = str_replace('.rrd', '', $filename);
    $filename = influxdb_escape($filename);

    // Disable debugging for now
    //print_vars(array($filename, $ds, $definition, $index, $influx_tags, $ds));


    influxdb_update_data($filename, $influx_tags, $ds);

    $runtime                           = microtime(TRUE) - $start;
    $GLOBALS['influxdb_stats']['time'] += $runtime;
    $GLOBALS['influxdb_stats']['count']++;
}

// EOF
