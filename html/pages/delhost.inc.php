<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/* Global write permissions required.
if ($_SESSION['userlevel'] < 10) {
  print_error_permission();
  return;
}
*/

register_html_title("Delete devices");

if (is_intnum($vars['id']) && is_entity_write_permitted($vars['id'], 'device')) {
    $device = device_by_id_cache($vars['id']);

    if ($device && get_var_true($vars['confirm'], 'confirm')) {
        $delete_rrd = get_var_true($vars['deleterrd'], 'confirm');
        print_message(delete_device($vars['id'], $delete_rrd), 'console');
        //echo('<div class="btn-group ">
        //        <button type="button" class="btn btn-default"><a href="/"><i class="oicon-globe-model"></i> Overview</a></button>
        //        <button type="button" class="btn btn-default"><a href="/devices/"><i class="oicon-servers"></i> Devices List</a></button>
        //      </div>');
    } else {
        print_warning("Are you sure you want to delete device <strong>" . $device['hostname'] . "</strong>?");

        $form = ['type'  => 'horizontal',
                 'id'    => 'delete_host',
                 //'space'     => '20px',
                 'title' => 'Delete device <strong>' . $device['hostname'] . '</strong>',
                 //'class'     => 'box box-solid',
                 'url'   => 'delhost/'];

        $form['row'][0]['id']        = [
          'type'  => 'hidden',
          'value' => $vars['id']];
        $form['row'][4]['deleterrd'] = [
          'type'  => 'checkbox',
          'name'  => 'Delete RRDs',
          'value' => (bool)$vars['deleterrd']];
        $form['row'][5]['confirm']   = [
          'type'     => 'checkbox',
          'name'     => 'Confirm Deletion',
          'onchange' => "javascript: toggleAttrib('disabled', 'delete');",
          'value'    => 'confirm'];
        $form['row'][6]['delete']    = [
          'type'     => 'submit',
          'name'     => 'Delete device',
          'icon'     => 'icon-remove icon-white',
          //'right'       => TRUE,
          'class'    => 'btn-danger',
          'disabled' => TRUE];
        print_form($form);
        unset($form);
    }
} elseif ($_SESSION['userlevel'] >= 9) {

    $form_items['devices'] = generate_form_values('device', NULL, NULL, [ 'show_disabled' => TRUE ]);

    $form = ['type'  => 'horizontal',
             'id'    => 'delete_host',
             //'space'     => '20px',
             'title' => 'Delete device',
             //'class'     => 'box box-solid',
             'url'   => 'delhost/'
    ];

    $form['row'][1]['id']        = [
      'type'   => 'select',
      'name'   => 'Device',
      'groups' => ['DISABLED', 'DOWN'],
      'values' => $form_items['devices']];
    $form['row'][4]['deleterrd'] = [
      'type'     => 'checkbox',
      'name'     => 'Delete RRDs',
      'onchange' => "javascript: showDiv(this.checked);",
      'value'    => 'confirm'];
    $form['row'][5]['confirm']   = [
      'type'     => 'checkbox',
      'name'     => 'Confirm Deletion',
      'onchange' => "javascript: toggleAttrib('disabled', 'delete');",
      'value'    => 'confirm'];
    $form['row'][6]['delete']    = [
      'type'     => 'submit',
      'name'     => 'Delete device',
      'icon'     => 'icon-remove icon-white',
      //'right'       => TRUE,
      'class'    => 'btn-danger',
      'disabled' => TRUE];

    print_warning("<h4>Warning!</h4>
      This will delete this device from Observium including all logging entries, but will not delete the RRDs.");

    print_form($form);
    unset($form, $form_items);
} else {
    print_error_permission();
}

// EOF
