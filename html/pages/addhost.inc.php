<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

if ($_SESSION['userlevel'] < 10) {
  print_error_permission("You don't have the necessary privileges to add hosts.");
  return;
}

//echo("<h2>Add Device</h2>");

if (get_var_true($vars['submit'], 'save') && $vars['hostname']) {
  if (request_token_valid($vars)) {

    if ($result = add_device_vars($vars)) {
      $device_url  = generate_device_url(array('device_id' => $result));
      $device_link = '<a href="' . $device_url . '" class="entity-popup" data-eid="' . $result . '" data-etype="device">' . escape_html($vars['hostname']) . '</a>';
      print_success("Device added (id = $result): $device_link");
    }

  }
} else {
  // Defaults
  switch ($vars['snmp_version']) {
    case 'v1':
    case 'v2c':
    case 'v3':
      $snmp_version = $vars['snmp_version'];
      break;
    default:
      $snmp_version = $config['snmp']['version'];
  }
  if (in_array($vars['snmp_transport'], $config['snmp']['transports'])) {
    $snmp_transport = $vars['snmp_transport'];
  } else {
    $snmp_transport = $config['snmp']['transports'][0];
  }
}

register_html_title("Add Device");

// Add form
$transports = array();
foreach ($config['snmp']['transports'] as $transport) {
  $transports[$transport] = strtoupper($transport);
}

$snmp_version = get_versions('snmp');
if (version_compare($snmp_version, '5.8', '<')) {
  $authclass = 'bg-warning';
  $authtext  = 'Poller required net-snmp >= 5.8';
} else {
  $authclass = 'bg-success';
  $authtext  = '';
}
$authalgo = [
  'MD5'     => [ 'name' => 'MD5' ],
  'SHA'     => [ 'name' => 'SHA' ],
  'SHA-224' => [ 'name' => 'SHA-224', 'class' => $authclass, 'subtext' => $authtext ],
  'SHA-256' => [ 'name' => 'SHA-256', 'class' => $authclass, 'subtext' => $authtext ],
  'SHA-384' => [ 'name' => 'SHA-384', 'class' => $authclass, 'subtext' => $authtext ],
  'SHA-512' => [ 'name' => 'SHA-512', 'class' => $authclass, 'subtext' => $authtext ],
];

$cryptoalgo = [
  'DES'       => [ 'name' => 'DES' ],
  'AES'       => [ 'name' => 'AES' ],
  'AES-192'   => [ 'name' => 'AES-192',       'class' => 'bg-warning', 'subtext' => 'Poller required net-snmp >= 5.8 compiled with --enable-blumenthal-aes' ],
  'AES-192-C' => [ 'name' => 'AES-192 Cisco', 'class' => 'bg-warning', 'subtext' => 'Poller required net-snmp >= 5.8 compiled with --enable-blumenthal-aes' ],
  'AES-256'   => [ 'name' => 'AES-256',       'class' => 'bg-warning', 'subtext' => 'Poller required net-snmp >= 5.8 compiled with --enable-blumenthal-aes' ],
  'AES-256-C' => [ 'name' => 'AES-256 Cisco', 'class' => 'bg-warning', 'subtext' => 'Poller required net-snmp >= 5.8 compiled with --enable-blumenthal-aes' ],
];

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
      $form['fieldset']['extra']   = array('div'   => 'top',
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
      $form['row'][0]['hostname'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Hostname',
                                      'width'       => '250px',
                                      'value'       => $vars['hostname']
      );
      if (dbExist('pollers')) {
        $poller_list = [];
        $poller_list[0] = [ 'name' => 'Default Poller' ];
        if ($config['poller_id'] != 0) {
          $poller_list[0]['group'] = 'External';
        }
        foreach(dbFetchRows("SELECT * FROM `pollers`") as $poller) {
          $poller_list[$poller['poller_id']] = [
            'name'    => $poller['poller_name'],
            'subtext' => $poller['host_id']
            //'subtext' => $poller['host_uname']
          ];
          if ($config['poller_id'] != $poller['poller_id']) {
            $poller_list[$poller['poller_id']]['group'] = 'External';
          }
        }
        $form['row'][1]['poller_id']      = array(
          'community'   => FALSE, // not available on community edition
          'type'        => 'select',
          //'fieldset'    => 'extra',
          'fieldset'    => 'edit',
          'name'        => 'Poller',
          'width'       => '250px',
          'disabled'    => !(count($poller_list) > 1),
          'values'      => $poller_list,
          'value'       => isset($vars['poller_id']) ? $vars['poller_id'] : $config['poller_id']
        );
      }
      $form['row'][2]['ping_skip'] = array(
                                      'type'        => 'toggle',
                                      'view'        => 'toggle',
                                      'palette'     => 'yellow',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Skip PING',
                                      'placeholder' => 'Skip ICMP echo checks',
                                      'value'       => ''
      );
      $form['row'][3]['snmp_version'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Protocol Version',
                                      'width'       => '250px',
                                      'values'      => array('v1' => 'v1', 'v2c' => 'v2c', 'v3' => 'v3'),
                                      'value'       => $vars['snmp_version'] ?: $config['snmp']['version']
      );
      $form['row'][4]['snmp_transport'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Transport',
                                      'width'       => '250px',
                                      'values'      => $transports,
                                      'value'       => $vars['snmp_transport'] ?: $config['snmp']['transports'][0]
      );
      $form['row'][5]['snmp_port'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Port',
                                      'placeholder' => '1-65535. Default 161.',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_port']
      );
      $form['row'][6]['snmp_timeout'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Timeout',
                                      'placeholder' => '1-120 sec. Default 1 sec.',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_timeout']
      );
      $form['row'][7]['snmp_retries'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Retries',
                                      'placeholder' => '1-10. Default 5.',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_retries']
      );
      $form['row'][8]['snmp_maxrep'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Max Repetitions',
                                      'width'       => '250px',
                                      'placeholder' => '0-500. Default 10. 0 for disable snmpbulk.',
                                      'value'       => $vars['snmp_maxrep']
      );
      $form['row'][9]['ignorerrd'] = array(
                                      'type'        => 'checkbox',
                                      'fieldset'    => 'edit',
                                      'name'        => 'Ignore existing RRDs',
                                      'placeholder' => 'Ignore pre-existing RRD directory and files',
                                      'disabled'    => $config['rrd_override'],
                                      'value'       => $config['rrd_override']
      );

      // Snmp v1/2c fieldset
      $form['row'][16]['snmp_community'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'snmpv2',
                                      'name'        => 'SNMP Community',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_community'] // FIXME. For passwords we should use filter instead escape!
      );

      // Snmp v3 fieldset
      $form['row'][17]['snmp_authlevel'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Auth Level',
                                      'width'       => '250px',
                                      'values'      => array('noAuthNoPriv' => 'noAuthNoPriv',
                                                             'authNoPriv'   => 'authNoPriv',
                                                             'authPriv'     => 'authPriv'),
                                      'value'       => $vars['snmp_authlevel']
      );
      $form['row'][18]['snmp_authname'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Auth Username',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_authname']
      );
      $form['row'][19]['snmp_authpass'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Auth Password',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_authpass'] // FIXME. For passwords we should use filter instead escape!
      );
      $form['row'][20]['snmp_authalgo'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Auth Algorithm',
                                      'width'       => '250px',
                                      'values'      => $authalgo,
                                      'value'       => $vars['snmp_authalgo']
      );
      $form['row'][21]['snmp_cryptopass'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Crypto Password',
                                      'width'       => '250px',
                                      'value'       => $vars['snmp_cryptopass'] // FIXME. For passwords we should use filter instead escape!
      );
      $form['row'][22]['snmp_cryptoalgo'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'snmpv3',
                                      'name'        => 'Crypto Algorithm',
                                      'width'       => '250px',
                                      'values'      => $cryptoalgo,
                                      'value'       => $vars['snmp_cryptoalgo']);

      $form['row'][25]['snmp_context'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'extra',
                                      'name'        => 'SNMP Context',
                                      'width'       => '250px',
                                      //'show_password' => !$readonly,
                                      'placeholder' => '(Optional) Context',
                                      'value'       => $vars['snmp_context'] // FIXME. For passwords we should use filter instead escape!
      );

      $form['row'][30]['submit']    = array(
                                      'type'        => 'submit',
                                      'fieldset'    => 'submit',
                                      'name'        => 'Add device',
                                      'icon'        => 'icon-ok icon-white',
                                      //'right'       => TRUE,
                                      'class'       => 'btn-primary',
                                      'value'       => 'save'
      );

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
