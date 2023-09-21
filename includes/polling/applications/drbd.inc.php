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

$outerregex = '~
                ^version:\s
                (?P<version>[\d.]+)\s
                \(
                    api:(?P<api>\d+).+?
                    proto:(?P<proto>[-\d]+)
                \)\R
                (GIT\-hash)?(srcversion)?:\s(?P<srcversion>.+)\R{1,2}
                (?P<devices>(?:\s\d(?:.+\R){2,4})+)
              ~xm';

$innerregex = '~
                ^\s(?P<devno>\d+):\s+
                cs:(?P<cs>\w+)\s+
                ro:(?P<ro>\S+)\s+
                ds:(?P<ds>\S+)\s+
                (?P<rep>\S+)\s+
                (?P<io>\S+)\R\s+
                ns:(?P<ns>\S+)\s+
                nr:(?P<nr>\S+)\s+
                dw:(?P<dw>\S+)\s+
                dr:(?P<dr>\S+)\s+
                al:(?P<al>\S+)\s+
                bm:(?P<bm>\S+)\s+
                lo:(?P<lo>\S+)\s+
                pe:(?P<pe>\S+)\s+
                ua:(?P<ua>\S+)\s+
                ap:(?P<ap>\S+)\s+
                ep:(?P<ep>\S+)\s+
                wo:(?P<wo>\S+)\s+
                oos:(?P<oos>\S+)\R
               ~xm';

$outerkeys = ["version", "api", "proto", "srcversion"];
$innerkeys = ["devno", "cs", "ro", "ds", "rep", "io", 'ns', 'nr', 'dw', 'dr', 'al', 'bm', 'lo', 'pe', 'ua', 'ap', 'ep', 'wo', 'oos'];

$output = [];
preg_match_all($outerregex, $agent_data['app']['drbd'] . PHP_EOL, $matches, PREG_SET_ORDER);

foreach ($matches as $match) {
    foreach ($outerkeys as $key) {
        $arr[$key] = $match[$key];
    }

    preg_match_all($innerregex, $match["devices"], $innermatches, PREG_SET_ORDER);
    $arr["devices"] = [];

    foreach ($innermatches as $innermatch) {
        $tmp = [];
        foreach ($innerkeys as $key) {
            $tmp[$key] = $innermatch[$key];
        }
        $arr["devices"][] = $tmp;
    }

    $output = $arr;
}

foreach ($output['devices'] as $drbd_dev) {
    $app_instance = "drbd" . $drbd_dev['devno'];
    $app_id       = discover_app($device, 'drbd', $app_instance);

    update_application($app_id, $drbd_dev);

    rrdtool_update_ng($device, 'drbd', $drbd_dev, $app_instance);

    unset($drbd_dev);
}

// EOF
