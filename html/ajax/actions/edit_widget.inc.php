<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($readonly) {
    return;
} // Currently edit allowed only for 7+

$widget = dbFetchRow("SELECT * FROM `dash_widgets` WHERE `widget_id` = ?", [$vars['widget_id']]);

$widget['widget_config'] = safe_json_decode($widget['widget_config']);

switch ($widget['widget_type']) {

    case "graph":

        if (safe_count($widget['widget_config'])) {

//      echo '
//      <form onsubmit="return false">
//        Title  <input name="widget-config-input" data-field="title" value="'.$widget['widget_config']['title'].'" data-id="'.$widget['widget_id'].'"></input>
//      </form>
//      ';

            //r($widget['widget_config']);

            //r(isset($widget['widget_config']['legend']) && $widget['widget_config']['legend'] === 'no');

            $modal_args = [
              'id'    => 'modal-edit_widget_' . $widget['widget_id'],
              'title' => 'Configure Widget',
              //'hide'  => TRUE,
              //'fade'  => TRUE,
              //'role'  => 'dialog',
              //'class' => 'modal-md',
            ];

            $form                       = [
              'form_only'  => TRUE, // Do not add modal open/close divs (it's generated outside)
              'type'       => 'horizontal',
              'id'         => 'edit_widget_' . $widget['widget_id'],
              'userlevel'  => 7,          // Minimum user level for display form
              'modal_args' => $modal_args, // !!! This generate modal specific form
              //'help'     => 'This will completely delete the rule and all associations and history.',
              'class'      => '', // Clean default box class!
              //'url'       => generate_url([ 'page' => 'syslog_rules' ]),
              'onsubmit'   => "return false",
            ];
            $form['fieldset']['body']   = ['class' => 'modal-body'];   // Required this class for modal body!
            $form['fieldset']['footer'] = ['class' => 'modal-footer']; // Required this class for modal footer!

            $form['row'][1]['widget-config-title']  = [
              'type'        => 'text',
              'fieldset'    => 'body',
              'name'        => 'Title',
              'placeholder' => 'Graph Title',
              'class'       => 'input-xlarge',
              'attribs'     => [
                'data-id'    => $widget['widget_id'],
                'data-field' => 'title',
                'data-type'  => 'text'
              ],
              'value'       => $widget['widget_config']['title']
            ];
            $form['row'][2]['widget-config-legend'] = [
              'type'     => 'checkbox',
              'fieldset' => 'body',
              'name'     => 'Show Legend',
              //'placeholder' => 'Yes, please delete this rule.',
              //'onchange'    => "javascript: toggleAttrib('disabled', 'delete_button_".$la['la_id']."'); showDiv(!this.checked, 'warning_".$la['la_id']."_div');",
              'attribs'  => [
                'data-id'    => $widget['widget_id'],
                'data-field' => 'legend',
                'data-type'  => 'checkbox'
              ],
              'value'    => safe_empty($widget['widget_config']['legend']) ? 'yes' : $widget['widget_config']['legend'] //'legend'
            ];


            $form['row'][8]['close'] = [
              'type'      => 'submit',
              'fieldset'  => 'footer',
              'div_class' => '', // Clean default form-action class!
              'name'      => 'Close',
              'icon'      => '',
              'attribs'   => [
                'data-dismiss' => 'modal',
                'aria-hidden'  => 'true'
              ]
            ];

            echo generate_form_modal($form);
            unset($form);

            /*
            echo '
      <form onsubmit="return false" class="form form-horizontal" style="margin-bottom: 0px;">
        <fieldset>
        <div id="purpose_div" class="control-group" style="margin-bottom: 10px;"> <!-- START row-1 -->
          <label class="control-label" for="purpose">Title</label>
          <div id="purpose_div" class="controls">
            <input type="text" placeholder="Graph Title" name="widget-config-title" class="input" data-field="title" style="width: 100%;" value="'.$widget['widget_config']['title'].'" data-id="'.$widget['widget_id'].'">
          </div>
        </div>

        <div id="ignore_div" class="control-group" style="margin-bottom: 10px;"> <!-- START row-6 -->
          <label class="control-label" for="ignore">Show Legend</label>
          <div id="ignore_div" class="controls">
            <input type="checkbox" name="widget-config-legend" data-field="legend" data-type="checkbox" value="legend" '.(isset($widget['widget_config']['legend']) && $widget['widget_config']['legend'] === 'no' ? '' : 'checked').' data-id="'.$widget['widget_id'].'">
          </div>
        </div>
      </fieldset>  <!-- END fieldset-body -->

      <div class="modal-footer">
         <fieldset>
            <button id="close" name="close" type="submit" class="btn btn-default text-nowrap" value="" data-dismiss="modal" aria-hidden="true">Close</button>
            <!-- <button id="action" name="action" type="submit" class="btn btn-primary text-nowrap" value="add_contact"><i style="margin-right: 0px;" class="icon-ok icon-white"></i>&nbsp;&nbsp;Add Contact</button> -->
         </fieldset>
      </div>

      </form>';
            */


        } else {

            print_message('To add a graph to this widget, navigate to the required graph and use the "Add To Dashboard" function on the graph page.');

            echo '<h3>Step 1. Locate Graph and click for Graph Browser.</h3>';
            echo '<img class="img img-thumbnail" src="images/doc/add_graph_1">';

            echo '<h3>Step 2. Select Add to Dashboard in Graph Browser.</h3>';
            echo '<img class="img" src="images/doc/add_graph_2">';
        }
        break;

    default:
        r($widget['widget_config']);
}

// EOF
