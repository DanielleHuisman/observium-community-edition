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

foreach (dbFetchRows("SELECT * FROM `p2p_radios` WHERE `device_id` = ?", [$device['device_id']]) as $radio) {
    $GLOBALS['cache']['p2p_radios'][$radio['radio_mib']][$radio['radio_index']] = $radio;
}

$include_dir = "includes/polling/p2p-radios";
include("includes/include-dir-mib.inc.php");

foreach ($GLOBALS['cache']['p2p_radios'] as $mib_radios) {

    foreach ($mib_radios as $radio) {

        if (!$GLOBALS['valid']['p2p_radio'][$radio['radio_mib']][$radio['radio_index']]) {
            dbDelete('p2p_radios', '`radio_id` = ?', [$radio['radio_id']]);
            echo('-');
        }

    }

}

// EOF
