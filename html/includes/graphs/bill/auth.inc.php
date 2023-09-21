<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Authorises bill viewing and sets $ports as reference to mysql query containing ports for this bill

//include("../includes/billing.inc.php");

if (is_numeric($vars['id']) && ($auth || bill_permitted($vars['id']))) {
    $bill = dbFetchRow("SELECT * FROM `bills` WHERE `bill_id` = ?", [$vars['id']]);

    $datefrom = date('YmdHis', $vars['from']);
    $dateto   = date('YmdHis', $vars['to']);

    $rates = getRates($vars['id'], $datefrom, $dateto);

    $ports = dbFetchRows("SELECT * FROM `bill_entities` AS B, `ports` AS P, `devices` AS D WHERE B.`bill_id` = ? AND P.`port_id` = B.`entity_id` AND D.`device_id` = P.`device_id`", [$vars['id']]);

    $auth = TRUE;
}

// EOF
