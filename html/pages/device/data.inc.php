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

// Print permission error and exit if the user doesn't have write permissions
if (!is_entity_write_permitted($device['device_id'], 'device')) {
    print_error_permission();
    return;
}

$export_device = $device;
device_filter_secrets($export_device, $config['snmp']['hide_auth'], '***');

if (get_var_true($vars['saveas']) && $vars['filename']) {
    download_as_file(gzencode(safe_json_encode($export_device)), $vars['filename']);
} else {
    if ($config['snmp']['hide_auth']) {
        print_warning("NOTE, <strong>\$config['snmp']['hide_auth']</strong> is set to <strong>TRUE</strong>, snmp community and snmp v3 auth hidden from output and export.");
    } else {
        print_error("WARNING, <strong>\$config['snmp']['hide_auth']</strong> is set to <strong>FALSE</strong>, snmp community and snmp v3 auth <strong>NOT hidden</strong> from output and export.");
    }
    $form = ['type'  => 'rows',
             'space' => '10px',
             'url'   => generate_url($vars)];
    // Filename
    $form['row'][0]['filename'] = [
      'type'        => 'text',
      'name'        => 'Filename',
      'value'       => $device['hostname'] . '.json.txt.gz',
      //'div_class'   => 'col-md-8',
      'width'       => '100%',
      'placeholder' => TRUE];
    // Compress
    //$form['row'][0]['compress'] = array(
    //                                'type'        => 'switch',
    //                                'value'       => 1);
    // Search button
    $form['row'][0]['saveas'] = [
      'type'  => 'submit',
      'name'  => 'Export',
      'icon'  => 'icon-save',
      'right' => TRUE,
      'value' => 'yes'
    ];
    print_form($form);

    r($export_device);
}

unset($export_device, $params, $param);

// EOF
