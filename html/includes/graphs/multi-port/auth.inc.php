<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// FIXME - expand $vars['data']['groups'] for auth. For now only allow for >5

if (!is_array($vars['id'])) { $vars['id'] = array($vars['id']); }

$is_permitted = FALSE;

foreach ($vars['id'] as $port_id)
{
  if (is_numeric($port_id) && port_permitted($port_id))
  {
    $is_permitted = TRUE;
  } else {
    $is_permitted = FALSE;
    // Bail on first reject.
    break;
  }
}

if ($auth || $is_permitted || $_SESSION['userlevel'] >= 5)
{
  $title_array   = array();
  $title_array[] = array('text' => 'Multiple Ports');
  $title_array[] = array('text' => safe_count($vars['id']) . ' Ports');

  $auth = TRUE;
}

unset($is_permitted);

// EOF
