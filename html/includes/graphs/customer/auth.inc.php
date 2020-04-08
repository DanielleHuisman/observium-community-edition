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

// FIXME - wtfbbq

if ($_SESSION['userlevel'] >= "5" || $auth)
{
  $id = $vars['id'];
  $title = "Customer :: ". escape_html($vars['id']);
  $auth = TRUE;

  $title_array   = array();
  $title_array[] = array('text' => 'Customer Ports', 'url' => generate_url(array('page' => 'customers')));
  $title_array[] = array('text' => escape_html($vars['id']), 'url' => generate_url(array('page' => 'customers')));

}

// EOF
