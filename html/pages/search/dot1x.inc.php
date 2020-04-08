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

$form_devices = dbFetchColumn('SELECT DISTINCT `device_id` FROM `wifi_sessions`;');
$form_items['devices'] = generate_form_values('device', $form_devices);

$form = array('type'  => 'rows',
              //'space' => '5px',
              'submit_by_key' => TRUE,
              'url'   => 'search/search=dot1x/');
//Device field
$form['row'][0]['device_id'] = array(
                                'type'        => 'multiselect',
                                'name'        => 'Device',
                                'width'       => '100%',
                                'grid'        => 3,
                                'value'       => $vars['device_id'],
                                'groups'      => array('', 'UP', 'DOWN', 'DISABLED'), // This is optgroup order for values (if required)
                                'values'      => $form_items['devices']);
//Search by field
$form['row'][0]['searchby'] = array(
                                'type'        => 'select',
                                'name'        => 'Search By',
                                'width'       => '100%',
                                'grid'        => 2,
                                'onchange'    => "$('#address').prop('placeholder', $('#searchby option:selected').text())",
                                'value'       => $vars['searchby'],
                                'values'      => array('mac' => 'MAC Address', 'ip' => 'IP Address', 'username' => 'Username'));

if ($vars['searchby'] == 'mac')
{
  $name = 'MAC Address';
}
else if ($vars['searchby'] == 'ip')
{
  $name = 'IP Address';
} else {
  $name = 'Username';
}

//Address field
$form['row'][0]['address']  = array(
                                'type'        => 'text',
                                'name'        => $name,
                                'width'       => '100%',
                                'grid'        => 4,
                                'placeholder' => TRUE,
                                'submit_by_key' => TRUE,
                                'value'       => escape_html($vars['address']));

// search button
$form['row'][0]['search']   = array(
                                'type'        => 'submit',
                                'grid'        => 3,
                                //'name'        => 'Search',
                                //'icon'        => 'icon-search',
                                'value'       => 'dot1x',
                                'right'       => TRUE);

print_form($form);
unset($form, $form_items, $form_devices);

// Pagination
$vars['pagination'] = TRUE;

print_dot1xtable($vars);

register_html_title('.1x Session Search');

?>

  </div> <!-- col-md-12 -->
</div> <!-- row -->

<?php

// EOF
