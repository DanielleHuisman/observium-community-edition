<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

if ($readonly) { return; } // Currently edit allowed only for 7+

$widget = dbFetchRow("SELECT * FROM `dash_widgets` WHERE widget_id = ?", array($vars['widget_id']));

$widget['widget_config'] = safe_json_decode($widget['widget_config']);

switch ($widget['widget_type']) {

  case "graph":

    if (safe_count($widget['widget_config'])) {

      echo '
      <form onsubmit="return false">
        Title  <input name="widget-config-input" data-field="title" value="'.$widget['widget_config']['title'].'" data-id="'.$widget['widget_id'].'"></input> 
      </form>
      ';

    } else {

      print_message('To add a graph to this widget, navigate to the required graph and use the "Add To Dashboard" function on the graph page.');

      echo '<h3>Step 1. Locate Graph and click for Graph Browser.</h3>';
      echo '<img class="img img-thumbnail" src="images/doc/add_graph_1">';

      echo '<h3>Step 2. Select Add to Dashboard in Graph Browser.</h3>';
      echo '<img class="img" src="images/doc/add_graph_2">';
    }
    break;

  default:
    print_vars($widget);

}

// EOF
