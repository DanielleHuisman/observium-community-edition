<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$g_i = 0;
foreach ($datas as $type) {
    if ($type != "overview") {
        $filename = $config['html_dir'] . '/pages/routing/overview/' . $type . '.inc.php';
        if (is_file($filename)) {
            $g_i++;
            $row_colour = !is_intnum($g_i / 2) ? OBS_COLOUR_LIST_A : OBS_COLOUR_LIST_B;

            echo('<div style="background-color: ' . $row_colour . ';">');
            echo('<div style="padding:4px 0px 0px 8px;"><span class=graphhead>' . $type_text[$type] . '</span>');

            include($filename);

            echo('</div>');
            echo('</div>');
        } else {
            $graph_title = $type_text[$type];
            $graph_type  = "device_" . $type;

            include($config['html_dir'] . "/includes/print-device-graph.php");
        }
    }
}

// EOF
