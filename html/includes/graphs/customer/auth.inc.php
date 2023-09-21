<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// FIXME - wtfbbq

if (!safe_empty($vars['id']) && ($_SESSION['userlevel'] >= "5" || $auth)) {
    $id    = $vars['id'];

    $auth  = TRUE;

    $title_array   = [];
    $title_array[] = ['text' => 'Customer Ports', 'url' => generate_url(['page' => 'customers'])];
    $title_array[] = ['text' => escape_html($vars['id']), 'url' => generate_url(['page' => 'customers'])];

    $graph_title = "Customer :: " . $vars['id'];
}

// EOF
