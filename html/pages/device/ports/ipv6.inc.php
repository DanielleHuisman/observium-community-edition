<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

?>
<div class="row">
<div class="col-md-12">

<?php

//$ip_devices = dbFetchColumn('SELECT DISTINCT `device_id` FROM `ipv6_addresses` LEFT JOIN `ports` USING(`port_id`);');
//foreach ($cache['devices']['hostname'] as $hostname => $device_id)
//{
//  if ($cache['devices']['id'][$device_id]['disabled'] && !$config['web_show_disabled']) { continue; }
//  else if (!in_array($device_id, $ip_devices)) { continue; } // Devices only with IP addresses
//  $devices_array[$device_id] = $hostname;
//}

$form = array('type'  => 'rows',
              'space' => '5px',
              'submit_by_key' => TRUE);
//$form['row'][0]['device']   = array(
//                                'type'        => 'multiselect',
//                                'name'        => 'Device',
//                                'width'       => '100%',
//                                'value'       => $vars['device'],
//                                'values'      => $form_items['devices']);
$form['row'][0]['interface']  = array(
                                'type'        => 'select',
                                'name'        => 'Interface',
                                'width'       => '100%',
                                'grid'        => 3,
                                'value'       => $vars['interface'],
                                'values'      => array('' => 'All Interfaces', 'Lo' => 'Loopbacks', 'Vlan' => 'Vlans'));
$form['row'][0]['network'] = array(
                                'type'        => 'text',
                                'name'        => 'IP Network',
                                'width'       => '100%',
                                'grid'        => 3,
                                'placeholder' => TRUE,
                                'ajax'        => TRUE,
                                'ajax_vars'   => array('field' => 'ipv6_network'),
                                'value'       => escape_html($vars['network']));
$form['row'][0]['address']  = array(
                                'type'        => 'text',
                                'name'        => 'IP Address',
                                'width'       => '100%',
                                'grid'        => 4,
                                'placeholder' => TRUE,
                                'value'       => escape_html($vars['address']));
// search button
$form['row'][0]['search']   = array(
                                'type'        => 'submit',
                                'grid'        => 2,
                                //'name'        => 'Search',
                                //'icon'        => 'icon-search',
                                'value'       => 'ipv6',
                                'right'       => TRUE);

print_form($form);
unset($form, $form_items);

// Pagination
$vars['pagination'] = TRUE;

// Print addresses
print_addresses($vars);

register_html_title("IPv6 Addresses");

?>

  </div> <!-- col-md-12 -->

</div> <!-- row -->

<?php

// EOF
