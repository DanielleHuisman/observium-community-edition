<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) Adam Armstrong
 *
 */

if ($_SESSION['userlevel'] >= 7) {
    $dash_id = dbInsert(['dash_name' => 'Default Dashboard'], 'dashboards');
    dbUpdate(['dash_name' => 'Dashboard ' . $dash_id], 'dashboards', '`dash_id` = ?', [$dash_id]);
    redirect_to_url(generate_url(['page' => 'dashboard', 'dash' => $dash_id, 'edit' => 'yes', 'action' => NULL]));
}