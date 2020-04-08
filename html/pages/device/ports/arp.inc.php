<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage webui
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

?>
<div class="row">
<div class="col-md-12">

<?php

$form = array('type'          => 'rows',
              'space'         => '5px',
              'submit_by_key' => TRUE,
              'url'           => generate_url($vars));

$form['row'][0]['ip_version'] = array(
                                'type'        => 'select',
                                'name'        => 'IP',
                                'width'       => '100%',
                                'value'       => $vars['ip_version'],
                                'values'      => array('' => 'IPv4 & IPv6', '4' => 'IPv4 only', '6' => 'IPv6 only'));
$form['row'][0]['searchby'] = array(
                                'type'        => 'select',
                                'name'        => 'Search By',
                                'width'       => '100%',
                                'onchange'    => "$('#address').prop('placeholder', $('#searchby option:selected').text())",
                                'value'       => $vars['searchby'],
                                'values'      => array('mac' => 'MAC Address', 'ip' => 'IP Address'));
$form['row'][0]['address']  = array(
                                'type'        => 'text',
                                'name'        => ($vars['searchby'] == 'ip' ? 'IP Address' : 'MAC Address'),
                                'width'       => '100%',
                                'grid'        => 4,
                                'placeholder' => TRUE,
                                'submit_by_key' => TRUE,
                                'value'       => escape_html($vars['address']));
// search button
$form['row'][0]['search']   = array(
                                'type'        => 'submit',
                                'grid'        => 4,
                                //'name'        => 'Search',
                                //'icon'        => 'icon-search',
                                'value'       => 'arp',
                                'right'       => TRUE);

print_form($form);
unset($form, $form_items);

// Pagination
$vars['pagination'] = TRUE;

print_arptable($vars);

?>

  </div> <!-- col-md-12 -->

</div> <!-- row -->
<?php

// EOF
