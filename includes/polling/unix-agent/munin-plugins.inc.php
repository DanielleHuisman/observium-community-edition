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

// Init to avoid PHP warnings
$plugins    = [];
$plugins_ds = [];

// Plugins
if (!empty($agent_data['munin'])) {
    echo("Munin Plugins:");
    if (OBS_DEBUG) {
        print_vars($agent_data['munin']);
    }

    // Build cache of plugins we already know about

    foreach (dbFetchRows('SELECT * FROM `munin_plugins` WHERE `device_id` = ?', [$device['device_id']]) as $plugin) {
        $plugins[$plugin['mplug_type']] = $plugin;
    }

    // Build cache of plugin datasources we already know about
    foreach (dbFetchRows('SELECT * FROM `munin_plugins_ds` AS D, `munin_plugins` AS P WHERE P.`mplug_id` = D.`mplug_id` AND P.`device_id` = ?', [$device['device_id']]) as $plugin_ds) {
        $plugins_ds[$plugin_ds['mplug_id']][$plugin_ds['ds_name']] = $plugin_ds;
    }

    $old_plugins_rrd_dir = $host_rrd . "/plugins";
    $plugins_rrd_dir     = $host_rrd . "/munin";
    if (!is_dir($plugins_rrd_dir)) {
        mkdir($plugins_rrd_dir);
        echo("Created directory : $plugins_rrd_dir\n");
    }
    $plugin = [];
    foreach ($agent_data['munin'] as $plugin_type => $plugin_data) {
        $plugin = [];

        echo("\nPlugin: $plugin_type");
        $plugin_rrd  = "munin/" . $plugin_type;
        $plugin_uniq = $plugin_type . "_";

        if (OBS_DEBUG > 1) {
            echo("\n[$plugin_data]\n");
        }

        foreach (explode("\n", $plugin_data) as $line) {
            [$key, $value] = explode(" ", $line, 2);
            if (preg_match("/^graph_/", $key)) {
                [, $key] = explode("_", $key);
                $plugin['graph'][$key] = $value;
            } else {
                [$metric, $key] = explode(".", $key);
                $plugin['values'][$metric][$key] = $value;
            }
        }

        if (!is_array($plugin['values']['multigraph'])) {
            if (is_array($plugins[$plugin_type])) {
                $mplug_id = $plugins[$plugin_type]['mplug_id'];
                // FIXME - check and update
            } else {
                $insert   = ['device_id'      => $device['device_id'], 'mplug_type' => $plugin_type,
                             'mplug_instance' => ($instance == NULL ? ['NULL'] : $instance),
                             'mplug_category' => ($plugin['graph']['category'] == NULL ? 'general' : strtolower($plugin['graph']['category'])),
                             'mplug_title'    => ($plugin['graph']['title'] == NULL ? ['NULL'] : $plugin['graph']['title']),
                             'mplug_vlabel'   => ($plugin['graph']['vlabel'] == NULL ? ['NULL'] : $plugin['graph']['vlabel']),
                             'mplug_args'     => ($plugin['graph']['args'] == NULL ? ['NULL'] : $plugin['graph']['args']),
                             'mplug_info'     => ($plugin['graph']['info'] == NULL ? ['NULL'] : $plugin['graph']['info']),
                ];
                $mplug_id = dbInsert($insert, 'munin_plugins');
            }

            if ($mplug_id) {
                echo(" ID: $mplug_id");

                foreach ($plugin['values'] as $name => $data) {
                    if (strlen($name)) {
                        echo(" $name");
                        if (empty($data['type'])) {
                            $data['type'] = "GAUGE";
                        }
                        if (empty($data['graph'])) {
                            $data['graph'] = "yes";
                        }
                        if (empty($data['label'])) {
                            $data['label'] = $name;
                        }
                        if (empty($data['draw'])) {
                            $data['draw'] = "LINE1.5";
                        }

                        $cmd      = " DS:val:" . $data['type'] . ":600:0:U ";
                        $ds_uniq  = $mplug_id . "_" . $name;
                        $filename = $plugin_rrd . "_" . $name . ".rrd";
                        rrdtool_create($device, $filename, $cmd);
                        rrdtool_update($device, $filename, "N:" . $data['value']);

                        if (empty($plugins_ds[$mplug_id][$name])) {
                            $insert = ['mplug_id'   => $mplug_id, 'ds_name' => $name,
                                       'ds_type'    => $data['type'], 'ds_label' => $data['label'],
                                       'ds_cdef'    => $data['cdef'], 'ds_draw' => $data['draw'],
                                       'ds_info'    => $data['info'], 'ds_extinfo' => $data['extinfo'],
                                       'ds_min'     => $data['min'], 'ds_max' => $data['max'],
                                       'ds_graph'   => $data['graph'], 'ds_negative' => $data['negative'],
                                       'ds_warning' => $data['warning'], 'ds_critical' => $data['critical'],
                                       'ds_colour'  => $data['colour'], 'ds_sum' => $data['sum'],
                                       'ds_stack'   => $data['stack'], 'ds_line' => $data['line'],
                            ];
                            $ds_id  = dbInsert($insert, 'munin_plugins_ds');
                        } else {
                            // FIXME - check and update.
                            unset ($plugins_ds[$mplug_id][$name]);
                        }
                    }
                    unset ($plugins[$plugin_type]);
                }
            } else {
                echo("No ID!\n");
            }
        }
    }
}

foreach ($plugins as $plugin) {
    dbDelete('munin_plugins', "`mplug_id` =  ?", [$plugin['mplug_id']]);
    echo("plug- ");
}

foreach ($plugins_ds as $plugin) {
    foreach ($plugin as $plugin_ds) {
        dbDelete('munin_plugins_ds', "`mplug_ds_id` =  ?", [$plugin_ds['mplug_ds_id']]);
        echo("ds- ");
    }
}

// EOF
