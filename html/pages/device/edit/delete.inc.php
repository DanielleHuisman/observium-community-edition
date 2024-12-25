<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) Adam Armstrong
 *
 */

if ($readonly) { // in parent: $readonly = !is_entity_write_permitted($device['device_id'], 'device');
    print_error_permission();
    return;
}

$form = [
    'type'  => 'horizontal',
    'id'    => 'delete_host',
    //'space'     => '20px',
    'title' => 'Delete device',
    //'class'     => 'box box-solid',
    'url'   => 'delhost/'
];

$form['row'][0]['id']        = [
    'type'  => 'hidden',
    'value' => $device['device_id']
];

$form['row'][4]['deleterrd'] = [
    'type'     => 'toggle',
    'view'     => 'toggle',
    'palette'  => 'red',
    'name'     => 'Delete RRDs',
    'onchange' => "javascript: showDiv(this.checked);",
    'value'    => 'confirm'
];

$form['row'][5]['confirm']   = [
    'type'     => 'toggle',
    'view'     => 'toggle',
    'palette'  => 'red',
    'name'     => 'Confirm Deletion',
    'onchange' => "javascript: toggleAttrib('disabled', 'delete');",
    'value'    => 'confirm'
];
$form['row'][6]['delete']    = [
    'type'     => 'submit',
    'name'     => 'Delete device',
    'icon'     => 'icon-remove icon-white',
    //'right'       => TRUE,
    'class'    => 'btn-danger',
    'disabled' => TRUE
];

print_warning("<h3>Warning!</h4>
      This will delete this device from Observium including all logging entries, but will not delete the RRDs.");

print_form($form);
unset($form);

// EOF
