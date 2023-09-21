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

$overview = 1;

$ports['total']    = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE device_id = ?", [$device['device_id']]);
$ports['up']       = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE device_id = ? AND `ifAdminStatus` = 'up' AND (`ifOperStatus` = 'up' OR `ifOperStatus` = 'monitoring')", [$device['device_id']]);
$ports['down']     = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE device_id = ? AND `ifAdminStatus` = 'up' AND (`ifOperStatus` = 'lowerLayerDown' OR `ifOperStatus` = 'down')", [$device['device_id']]);
$ports['disabled'] = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE device_id = ? AND `ifAdminStatus` = 'down'", [$device['device_id']]);

if ($ports['down']) {
    $ports_colour = OBS_COLOUR_WARN_A;
} else {
    $ports_colour = OBS_COLOUR_LIST_A;
}
?>

    <div class="row">
        <div class="col-md-6">

            <?php
            /* Begin Left Pane */

            include("overview/information.inc.php");

            include("overview/ports.inc.php");

            include("overview/alertlog.inc.php");

            if ($config['enable_syslog']) {
                include("overview/syslog.inc.php");
            }

            echo("</div>");
            /* End Left Pane */

            /* Begin Right Pane */
            echo('<div class="col-md-6">');

            if ($device['os_group'] == "unix") {
                include("overview/processors-unix.inc.php");
            } else {
                include("overview/processors.inc.php");
            }

            if (is_array($device_state['ucd_mem'])) {
                include("overview/ucd_mem.inc.php");
            } else {
                include("overview/mempools.inc.php");
            }

            include("overview/storage.inc.php");

            if (is_array($entity_state['group']['c6kxbar'])) {
                include("overview/c6kxbar.inc.php");
            }

            include("overview/printersupplies.inc.php");
            include("overview/status.inc.php");
            include("overview/counter.inc.php");
            include("overview/sensors.inc.php");

            include("overview/events.inc.php");

            /* End Right Pane */

            ?>

        </div>
    </div>

<?php

// EOF
