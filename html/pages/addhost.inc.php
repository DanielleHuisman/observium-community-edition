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

if ($_SESSION['userlevel'] < 10)
{
  print_error_permission();
  return;
}

//echo("<h2>Add Device</h2>");

if ($vars['hostname'])
{
  if ($_SESSION['userlevel'] >= '10' && request_token_valid($vars))
  {

    $result = add_device_vars($vars);

    if ($result)
    {
      $device_url  = generate_device_url(array('device_id' => $result));
      $device_link = '<a href="' . $device_url . '" class="entity-popup" data-eid="' . $result . '" data-etype="device">' . escape_html($vars['hostname']) . '</a>';
      print_success("Device added (id = $result): $device_link");
    }

  } else {
    print_error("You don't have the necessary privileges to add hosts.");
  }
} else {
  // Defaults
  switch ($vars['snmp_version'])
  {
    case 'v1':
    case 'v2c':
    case 'v3':
      $snmp_version = $vars['snmp_version'];
      break;
    default:
      $snmp_version = $config['snmp']['version'];
  }
  if (in_array($vars['snmp_transport'], $config['snmp']['transports']))
  {
    $snmp_transport = $vars['snmp_transport'];
  } else {
    $snmp_transport = $config['snmp']['transports'][0];
  }
}

register_html_title("Add Device");

// Add form
$transports = array();
foreach ($config['snmp']['transports'] as $transport)
{
  $transports[$transport] = strtoupper($transport);
}

      $form = array('type'      => 'horizontal',
                    'id'        => 'edit',
                    //'space'     => '20px',
                    //'title'     => 'Add Device',
                    //'icon'      => 'oicon-gear',
                    );
      // top row div
      $form['fieldset']['edit']    = array('div'   => 'top',
                                           'title' => 'Basic Configuration',
                                           'class' => 'col-md-6');
      $form['fieldset']['snmpv2']  = array('div'   => 'top',
                                           'title' => 'SNMP v1/v2c Authentication',
                                           'class' => 'col-md-6 col-md-pull-0');
      $form['fieldset']['snmpv3']  = array('div'   => 'top',
                                           'title' => 'SNMP v3 Authentication',
                                           'class' => 'col-md-6 col-md-pull-0');
      $form['fieldset']['snmpextra'] = array('div'   => 'top',
                                             'title' => 'Extra Configuration',
                                             'class' => 'col-sm-12 col-md-6 pull-right');

      // bottom row div
      $form['fieldset']['submit']  = array('div'   => 'bottom',
                                           'style' => 'padding: 0px;',
                                           'class' => 'col-md-12');

      //$form['row'][0]['editing']   = array(
      //                                'type'        => 'hidden',
      //                                'value'       => 'yes');
      // left fieldset
      $form['row'][1]['hostname'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Hostname',
                                      'width'       => '250px',
                                      'value'       => $vars['hostname']);
      $form['row'][2]['ping_skip'] = array(
                                      'type'        => 'checkbox',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Skip PING',
                                      'placeholder' => 'Skip ICMP echo checks',
                                      'value'       => '');
      $form['row'][3]['snmp_version'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Protocol Version',
                                      'width'       => '250px',
                                      'values'      => array('v1' => 'v1', 'v2c' => 'v2c', 'v3' => 'v3'),
                                      'value'       => ($vars['snmp_version'] ? $vars['snmp_version'] : $config['snmp']['version']));
      $form['row'][4]['snmp_transport'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Transport',
                                      'width'       => '250px',
                                      'values'      => $transports,
                                      'value'       => ($vars['snmp_transport'] ? $vars['snmp_transport'] : $config['snmp']['transports'][0]));
      $form['row'][5]['snmp_port'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Port',
                                      'placeholder' => '161',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_port']);
      $form['row'][6]['snmp_timeout'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Timeout',
                                      'placeholder' => '1',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_timeout']);
      $form['row'][7]['snmp_retries'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Retries',
                                      'placeholder' => '5',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_retries']);
      $form['row'][8]['ignorerrd'] = array(
                                      'type'        => 'checkbox',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Ignore existing RRDs',
                                      'placeholder' => 'Ignore pre-existing RRD directory and files',
                                      'disabled'    => $config['rrd_override'],
                                      'value'       => $config['rrd_override']);

      // Snmp v1/2c fieldset
      $form['row'][16]['snmp_community'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'snmpv2',
                                      'name'        => 'SNMP Community',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_community']); // FIXME. For passwords we should use filter instead escape!

      // Snmp v3 fieldset
      $form['row'][17]['snmp_authlevel'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Auth Level',
                                      'width'       => '250px',
                                      'values'      => array('noAuthNoPriv' => 'noAuthNoPriv',
                                                             'authNoPriv'   => 'authNoPriv',
                                                             'authPriv'     => 'authPriv'),
                                      'value'       => $vars['snmp_authlevel']);
      $form['row'][18]['snmp_authname'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Auth Username',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_authname']);
      $form['row'][19]['snmp_authpass'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Auth Password',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_authpass']); // FIXME. For passwords we should use filter instead escape!
      $form['row'][20]['snmp_authalgo'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Auth Algorithm',
                                      'width'       => '250px',
                                      'values'      => array('MD5' => 'MD5', 'SHA' => 'SHA'),
                                      'value'       => $vars['snmp_authalgo']);
      $form['row'][21]['snmp_cryptopass'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Crypto Password',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_cryptopass']); // FIXME. For passwords we should use filter instead escape!
      $form['row'][22]['snmp_cryptoalgo'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Crypto Algorithm',
                                      'width'       => '250px',
                                      'values'      => array('AES' => 'AES', 'DES' => 'DES'),
                                      'value'       => $vars['snmp_cryptoalgo']);
      $form['row'][25]['snmp_context'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'snmpextra',
                                      'name'        => 'SNMP Context',
                                      'width'       => '250px',
                                      'readonly'    => $readonly,
                                      'show_password' => !$readonly,
                                      'placeholder' => '(Optional) Context',
                                      'value'       => $vars['snmp_context']); // FIXME. For passwords we should use filter instead escape!
      $form['row'][30]['submit']    = array(
                                      'type'        => 'submit',
                                      'fieldset'    => 'submit',
                                      'name'        => 'Add device',
                                      'icon'        => 'icon-ok icon-white',
                                      //'right'       => TRUE,
                                      'class'       => 'btn-primary',
                                      'value'       => 'save');

      print_form_box($form);
      unset($form);

?>

<script type="text/javascript">
<!--
$("#snmp_version").change(function() {
   var select = this.value;
        if (select === 'v3') {
            $('#snmpv3').show();
            $("#snmpv2").hide();
        } else {
            $('#snmpv2').show();
            $('#snmpv3').hide();
        }
}).change();

$("#snmp_authlevel").change(function() {
  var select = this.value;
  if (select === 'authPriv') {
    $('[id^="snmp_authname"]').show();
    $('[id^="snmp_authpass"]').show();
    $('[id^="snmp_authalgo"]').show();
    $('[id^="snmp_cryptopass"]').show();
    $('[id^="snmp_cryptoalgo"]').show();
  } else if (select === 'authNoPriv') {
    $('[id^="snmp_authname"]').show();
    $('[id^="snmp_authpass"]').show();
    $('[id^="snmp_authalgo"]').show();
    $('[id^="snmp_cryptopass"]').hide();
    $('[id^="snmp_cryptoalgo"]').hide();
  } else {
    $('[id^="snmp_authname"]').hide();
    $('[id^="snmp_authpass"]').hide();
    $('[id^="snmp_authalgo"]').hide();
    $('[id^="snmp_cryptopass"]').hide();
    $('[id^="snmp_cryptoalgo"]').hide();
  }
}).change();
// -->
</script>

<?php

// EOF
