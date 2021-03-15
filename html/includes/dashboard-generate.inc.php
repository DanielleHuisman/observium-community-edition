<?php
dbInsert(array('dash_id' => '1', 'dash_name' => 'Default Dashboard'), 'dashboards');
$y = 0;

// Migrate an existing front page arrangement if it exists. Remove this after next CE.
if (!isset($config['frontpage']['order']) || FALSE)
{

    $height =   round((100 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
    dbInsert(array('dash_id'       => '1', 'widget_type' => 'welcome', 'widget_config' => $blank, 'x' => '6', 'y' => $y, 'width' => '12', 'height' => $height ), 'dash_widgets');
    $y += $height;

    $height = round(240 / ($grid_cell_height + $grid_v_margin));
    dbInsert(array('dash_id'       => '1',
        'widget_type'   => 'map',
        'widget_config' => $blank,
        'x'             => '0',
        'y'             => $y,
        'width'         => '6',
        'height'        => $height), 'dash_widgets'
    );
    dbInsert(array('dash_id'       => '1',
        'widget_type'   => 'status_summary',
        'widget_config' => $blank,
        'x'             => '6',
        'y'             => $y,
        'width'         => '6',
        'height'        => $height), 'dash_widgets'
    );
    $y += $height;

    $height = ceil((90 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
    dbInsert(array('dash_id'       => '1',
        'widget_type'   => 'alert_boxes',
        'widget_config' => $blank,
        'x'             => '0',
        'y'             => $y,
        'width'         => '12',
        'height'        => $height), 'dash_widgets'
    );
    $y += $height;

    $height = ceil((280 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
    dbInsert(array('dash_id'       => '1',
        'widget_type'   => 'eventlog',
        'widget_config' => $blank,
        'x'             => '0',
        'y'             => $y,
        'width'         => '6',
        'height'        => $height), 'dash_widgets'
    );
    dbInsert(array('dash_id'       => '1',
        'widget_type'   => 'alertlog',
        'widget_config' => $blank,
        'x'             => '6',
        'y'             => $y,
        'width'         => '6',
        'height'        => $height), 'dash_widgets'
    );
    $y += $height;

}
else
{

    $height = ceil((80 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
    dbInsert(array('dash_id'       => '1',
        'widget_type'   => 'welcome',
        'widget_config' => json_encode(array('converted' => TRUE)),
        'x'             => '6',
        'y'             => $y,
        'width'         => '12',
        'height'        => $height), 'dash_widgets'
    );

    $x = 0;
    $y += $height;

    foreach ($config['frontpage']['order'] AS $entry)
    {

        switch ($entry)
        {
            case "map":
                $height = ceil((250 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                dbInsert(array('dash_id'       => '1',
                    'widget_type'   => 'map',
                    'widget_config' => $blank,
                    'x'             => '0',
                    'y'             => $y,
                    'width'         => '12',
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                break;

            case "portpercent":
                $height = ceil((240 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                dbInsert(array('dash_id'       => '1',
                    'widget_type'   => 'port_percent',
                    'widget_config' => $blank,
                    'x'             => '0',
                    'y'             => $y,
                    'width'         => '12',
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                break;

            case "status_summary":
                $height = ceil((120 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                dbInsert(array('dash_id'       => '1',
                    'widget_type'   => 'status_summary',
                    'widget_config' => $blank,
                    'x'             => '0',
                    'y'             => $y,
                    'width'         => '12',
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                break;

            case "alert_table":
                $height = ceil((240 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                dbInsert(array('dash_id'       => '1',
                    'widget_type'   => 'alert_table',
                    'widget_config' => $blank,
                    'x'             => '0',
                    'y'             => $y,
                    'width'         => '12',
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                break;

            case "device_status_boxes":
                $height = ceil((90 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                dbInsert(array('dash_id'       => '1',
                    'widget_type'   => 'old_status_boxes',
                    'widget_config' => $blank,
                    'x'             => '0',
                    'y'             => $y,
                    'width'         => '12',
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                break;

            case "eventlog":
                $height = ceil((240 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                dbInsert(array('dash_id'       => '1',
                    'widget_type'   => 'eventlog',
                    'widget_config' => $blank,
                    'x'             => '0',
                    'y'             => $y,
                    'width'         => '12',
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                break;

            case "syslog":
                $height = ceil((240 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                dbInsert(array('dash_id'       => '1',
                    'widget_type'   => 'syslog',
                    'widget_config' => $blank,
                    'x'             => '0',
                    'y'             => $y,
                    'width'         => '12',
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                break;

            case "device_status":
                $height = ceil((240 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                dbInsert(array('dash_id'       => '1',
                    'widget_type'   => 'old_status_table',
                    'widget_config' => $blank,
                    'x'             => '0',
                    'y'             => $y,
                    'width'         => '12',
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                break;

            case "splitlog":
                $height = ceil((240 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                dbInsert(array('dash_id'       => '1',
                    'widget_type'   => 'syslog',
                    'widget_config' => $blank,
                    'x'             => '0',
                    'y'             => $y,
                    'width'         => '6',
                    'height'        => $height), 'dash_widgets'
                );
                dbInsert(array('dash_id'       => '1',
                    'widget_type'   => 'eventlog',
                    'widget_config' => $blank,
                    'x'             => '6',
                    'y'             => $y,
                    'width'         => '6',
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                break;

            case "overall_traffic":

                //$peering_count = dbFetchCell("SELECT COUNT(port_id) FROM `ports` WHERE `port_descr_type` = 'peering'");
                //$transit_count = dbFetchCell("SELECT COUNT(port_id) FROM `ports` WHERE `port_descr_type` = 'transit'");
                $peering_exist = dbExist('ports', '`port_descr_type` = ?', array('peering'));
                $transit_exist = dbExist('ports', '`port_descr_type` = ?', array('transit'));

                $height = ceil((120 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                if ($transit_exist)
                {
                    $graph_array = array('type' => 'global_bits', 'port_type' => 'transit', 'title' => 'Transit Traffic', 'separate' => 'yes');
                    $widget_id = dbInsert(array('dash_id'       => '1',
                        'widget_config' => json_encode($graph_array),
                        'widget_type'   => 'graph',
                        'x'             => $x,
                        'y'             => $y,
                        'width'         => 6,
                        'height'        => $height), 'dash_widgets'
                    );
                    $x         += 6;
                }
                if ($peering_exist)
                {
                    $graph_array = array('type' => 'global_bits', 'port_type' => 'peering', 'title' => 'Peering Traffic', 'separate' => 'yes');
                    $widget_id = dbInsert(array('dash_id'       => '1',
                        'widget_config' => json_encode($graph_array),
                        'widget_type'   => 'graph',
                        'x'             => $x,
                        'y'             => $y,
                        'width'         => 6,
                        'height'        => $height), 'dash_widgets'
                    );
                    $x         += 6;
                }
                $y +=$height;

                $height = ceil((160 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                $graph_array = array('type' => 'global_bits_types', 'type_a' => 'transit', 'type_b' => 'peering',  'from' => '-1m', 'title' => 'Monthly Transit and Peering Traffic');
                $widget_id = dbInsert(array('dash_id'       => '1',
                    'widget_config' => json_encode($graph_array),
                    'widget_type'   => 'graph',
                    'x'             => $x,
                    'y'             => $y,
                    'width'         => 12,
                    'height'        => $height), 'dash_widgets'
                );
                $x = 0;
                $y += $height;
                break;

            case "custom_traffic":

                if (isset($config['frontpage']['custom_traffic']['title'])) { $title = $config['frontpage']['custom_traffic']['title']; } else { $title = "Custom Traffic"; }

                $height = ceil((120 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                $graph_array = array('type' => 'multi-port_bits', 'id' => $config['frontpage']['custom_traffic']['ids'], 'from' => '-1d', 'title' => $title . ' Today');
                $widget_id = dbInsert(array('dash_id'       => '1',
                    'widget_config' => json_encode($graph_array),
                    'widget_type'   => 'graph',
                    'x'             => $x,
                    'y'             => $y,
                    'width'         => 6,
                    'height'        => $height), 'dash_widgets'
                );
                $x         += 6;
                $graph_array = array('type' => 'multi-port_bits', 'id' => $config['frontpage']['custom_traffic']['ids'], 'from' => '-7d', 'title' => $title . ' This Week');
                $widget_id = dbInsert(array('dash_id'       => '1',
                    'widget_config' => json_encode($graph_array),
                    'widget_type'   => 'graph',
                    'x'             => $x,
                    'y'             => $y,
                    'width'         => 6,
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                $x = 0;

                $graph_array = array('type' => 'multi-port_bits', 'id' => $config['frontpage']['custom_traffic']['ids'], 'from' => '-1m', 'title' => $title . ' This Month');
                $widget_id = dbInsert(array('dash_id'       => '1',
                    'widget_config' => json_encode($graph_array),
                    'widget_type'   => 'graph',
                    'x'             => $x,
                    'y'             => $y,
                    'width'         => 12,
                    'height'        => $height), 'dash_widgets'
                );
                $y += $height;
                break;

            case "micrographs":

                $height = ceil((40 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));

                foreach ($config['frontpage']['micrographs'] as $row)
                {
                    foreach (explode(';', $row['ids']) as $graph)
                    {
                        if (!$graph)
                        {
                            continue;
                        }
                        list($device, $type, $header) = explode(',', $graph, 3);
                        $graph_array = array('type' => $type, 'id' => $device, 'title' => $header);
                        $widget_id   = dbInsert(array('dash_id'       => '1',
                            'widget_config' => json_encode($graph_array),
                            'widget_type'   => 'graph',
                            'x'             => $x,
                            'y'             => $y,
                            'width'         => 2,
                            'height'        => $height), 'dash_widgets'
                        );
                        $x           += 2;
                    }
                    $y += $height;
                    $x = 0;

                }
                break;

            case "minigraphs":
                $height = ceil((100 + $grid_v_margin) / ($grid_cell_height + $grid_v_margin));
                $width = 3;

                foreach (explode(';', $config['frontpage']['minigraphs']['ids']) as $graph)
                {
                    if (!$graph)
                    {
                        continue;
                    }

                    if($x+$width > 12) { $x = 0; $y += $height; }

                    list($id, $type, $header) = explode(',', $graph, 3);
                    $id = str_replace("%2C", ",", $id); // Replace the HTML code for comma with a comma.

                    $graph_array = array('type' => $type, 'id' => $id, 'title' => $header);
                    $widget_id   = dbInsert(array('dash_id'       => '1',
                        'widget_config' => json_encode($graph_array),
                        'widget_type'   => 'graph',
                        'x'             => $x,
                        'y'             => $y,
                        'width'         => $width,
                        'height'        => $height), 'dash_widgets'
                    );
                    $x += 3;
                }
                $y += $height;
                $x = 0;

                break;
        }
    }
}