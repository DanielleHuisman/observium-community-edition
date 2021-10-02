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

$ok = FALSE;
if ($vars['editing']) {
  if ($readonly) {
    print_error_permission('You have insufficient permissions to edit settings.');
  } else {
    $update = array();
    switch ($vars['snmp_version']) {
      case 'v3':
        switch ($vars['snmp_authlevel']) {
          case 'authPriv':
            if (is_valid_param($vars['snmp_cryptoalgo'], 'snmp_cryptoalgo')) {
              $ok = TRUE;
              $update['snmp_cryptoalgo'] = strtoupper($vars['snmp_cryptoalgo']);
              $update['snmp_cryptopass'] = $vars['snmp_cryptopass'];
            } else {
              $error = 'Incorrect SNMP Crypto Algorithm';
            }
            // no break here
          case 'authNoPriv':
            if (is_valid_param($vars['snmp_authalgo'], 'snmp_authalgo')) {
              $ok = TRUE;
              $update['snmp_authalgo']   = strtoupper($vars['snmp_authalgo']);
              $update['snmp_authname']   = $vars['snmp_authname'];
              $update['snmp_authpass']   = $vars['snmp_authpass'];
            } else {
              $error = 'Incorrect SNMP Auth parameters';
              $ok = FALSE;
            }
            break;
          case 'noAuthNoPriv':
            $ok = TRUE;
            break;
        }
        if ($ok) { $update['snmp_authlevel'] = $vars['snmp_authlevel']; }
        break;
      case 'v2c':
      case 'v1':
        if (is_valid_param($vars['snmp_community'], 'snmp_community')) {
          $ok = TRUE;
          $update['snmp_community'] = $vars['snmp_community'];
        } else {
          $error = 'Incorrect SNMP Community';
        }
        break;
    }
    if ($ok) {
      $update['snmp_version'] = $vars['snmp_version'];
      if (in_array($vars['snmp_transport'], $config['snmp']['transports'])) {
        $update['snmp_transport'] = $vars['snmp_transport'];
      } else {
        $update['snmp_transport'] = 'udp';
      }
      if (is_valid_param($vars['snmp_port'], 'port')) {
        $update['snmp_port'] = (int)$vars['snmp_port'];
      } else {
        if (strlen($vars['snmp_port'])) { print_warning('Passed incorrect SNMP port ('.$vars['snmp_port'].'). Should be between 1 and 65535.'); }
        $update['snmp_port'] = 161;
      }
      if (is_valid_param($vars['snmp_timeout'], 'snmp_timeout')) {
        $update['snmp_timeout'] = (int)$vars['snmp_timeout'];
      } else {
        if (strlen($vars['snmp_timeout'])) { print_warning('Passed incorrect SNMP timeout ('.$vars['snmp_timeout'].'). Should be between 1 and 120 sec.'); }
        $update['snmp_timeout'] = array('NULL');
      }
      if (is_valid_param($vars['snmp_retries'], 'snmp_retries')) {
        $update['snmp_retries'] = (int)$vars['snmp_retries'];
      } else {
        if (strlen($vars['snmp_retries'])) { print_warning('Passed incorrect SNMP retries ('.$vars['snmp_retries'].'). Should be between 1 and 10.'); }
        $update['snmp_retries'] = array('NULL');
      }

      // SNMPbulk max repetitions, allow 0 for disable snmpbulk(walk|get)
      if (is_intnum($vars['snmp_maxrep']) && $vars['snmp_maxrep'] >= 0 && $vars['snmp_maxrep'] <= 500) {
        $update['snmp_maxrep'] = (int)$vars['snmp_maxrep'];
      } else {
        if (strlen($vars['snmp_maxrep'])) { print_warning('Passed incorrect SNMPbulk max repetitions ('.$vars['snmp_maxrep'].'). Should be between 0 and 500. When 0 - snmpbulk will disable.'); }
        $update['snmp_maxrep'] = [ 'NULL' ];
      }

      if (strlen(trim($vars['snmp_context']))) {
        $update['snmp_context'] = trim($vars['snmp_context']);
      } else {
        $update['snmp_context'] = [ 'NULL' ];
      }

      if (dbUpdate($update, 'devices', '`device_id` = ?', array($device['device_id']))) {
        print_success("Device SNMP configuration updated");
        log_event('Device SNMP configuration changed.', $device['device_id'], 'device', $device['device_id'], 5);
      } else {
        $ok = FALSE;
        print_warning("Device SNMP configuration update is not required");
      }
    }
    if (!$ok) {
      if ($error) {
        $error = "Device SNMP configuration not updated ($error)";
      }
      print_error($error);
    }

    unset($update);
  }
}

$device = device_by_id_cache($device['device_id'], $ok);
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
              //'title'     => 'General',
              //'class'     => 'box',
              );
// top row div
$form['fieldset']['edit']    = array('div'   => 'top',
                                     'title' => 'Basic Configuration',
                                     'class' => 'col-md-6');
$form['fieldset']['snmpv2']  = array('div'   => 'top',
                                     'title' => 'SNMP v1/v2c Authentication',
                                     //'right' => TRUE,
                                     'class' => 'col-md-6 col-md-pull-0');
$form['fieldset']['snmpv3']  = array('div'   => 'top',
                                     'title' => 'SNMP v3 Authentication',
                                     //'right' => TRUE,
                                     'class' => 'col-md-6 col-md-pull-0');
$form['fieldset']['snmpextra'] = array('div'   => 'top',
                                     'title' => 'Extra Configuration',
                                     //'right' => TRUE,
                                     'class' => 'col-sm-12 col-md-6 pull-right');

// bottom row div
$form['fieldset']['submit']  = array('div'   => 'bottom',
                                     'style' => 'padding: 0px;',
                                     'class' => 'col-md-12');

$form['row'][0]['editing']   = array(
                                'type'        => 'hidden',
                                'value'       => 'yes');
// left fieldset
$form['row'][1]['snmp_version'] = array(
                                'type'        => 'select',
                                'fieldset'    => 'edit',
                                'name'        => 'Protocol Version',
                                'width'       => '250px',
                                'readonly'    => $readonly,
                                'values'      => array('v1' => 'v1', 'v2c' => 'v2c', 'v3' => 'v3'),
                                'value'       => $device['snmp_version']);
$form['row'][2]['snmp_transport'] = array(
                                'type'        => 'select',
                                'fieldset'    => 'edit',
                                'name'        => 'Transport',
                                'width'       => '250px',
                                'readonly'    => $readonly,
                                'values'      => $transports,
                                'value'       => $device['snmp_transport']);
$form['row'][3]['snmp_port'] = array(
                                'type'        => 'text',
                                'fieldset'    => 'edit',
                                'name'        => 'Port',
                                'width'       => '250px',
                                'placeholder' => '1-65535. Default 161.',
                                'readonly'    => $readonly,
                                'value'       => $device['snmp_port']);
$form['row'][4]['snmp_timeout'] = array(
                                'type'        => 'text',
                                'fieldset'    => 'edit',
                                'name'        => 'Timeout',
                                'width'       => '250px',
                                'placeholder' => '1-120 sec. Default 1 sec.',
                                'readonly'    => $readonly,
                                'value'       => $device['snmp_timeout']);
$form['row'][5]['snmp_retries'] = array(
                                'type'        => 'text',
                                'fieldset'    => 'edit',
                                'name'        => 'Retries',
                                'width'       => '250px',
                                'placeholder' => '1-10. Default 5.',
                                'readonly'    => $readonly,
                                'value'       => $device['snmp_retries']);

$form['row'][6]['snmp_maxrep'] = array(
                                'type'        => 'text',
                                'fieldset'    => 'edit',
                                'name'        => 'Max Repetitions',
                                'width'       => '250px',
                                'placeholder' => '0-500. Default 10. 0 for disable snmpbulk.',
                                'readonly'    => $readonly,
                                'value'       => $device['snmp_maxrep']);
// Snmp v1/2c fieldset
$form['row'][7]['snmp_community'] = array(
                                'type'        => 'password',
                                'fieldset'    => 'snmpv2',
                                'name'        => 'SNMP Community',
                                'width'       => '250px',
                                'readonly'    => $readonly,
                                'show_password' => !$readonly,
                                'value'       => $device['snmp_community']);  // FIXME. For passwords we should use filter instead escape!

// Snmp v3 fieldset
$form['row'][8]['snmp_authlevel'] = array(
                                'type'        => 'select',
                                'fieldset'    => 'snmpv3',
                                'name'        => 'Auth Level',
                                'width'       => '250px',
                                'readonly'    => $readonly,
                                'values'      => array('noAuthNoPriv' => 'noAuthNoPriv',
                                                       'authNoPriv'   => 'authNoPriv',
                                                       'authPriv'     => 'authPriv'),
                                'value'       => $device['snmp_authlevel']);

$form['row'][9]['snmp_authname'] = array(
                                'type'        => 'password',
                                'fieldset'    => 'snmpv3',
                                'name'        => 'Auth Username',
                                'width'       => '250px',
                                'readonly'    => $readonly,
                                'show_password' => !$readonly,
                                'value'       => $device['snmp_authname']);

$form['row'][10]['snmp_authpass'] = array(
                                'type'        => 'password',
                                'fieldset'    => 'snmpv3',
                                'name'        => 'Auth Password',
                                'width'       => '250px',
                                'readonly'    => $readonly,
                                'show_password' => !$readonly,
                                'value'       => $device['snmp_authpass']); // FIXME. For passwords we should use filter instead escape!

$form['row'][11]['snmp_authalgo'] = array(
                                'type'        => 'select',
                                'fieldset'    => 'snmpv3',
                                'name'        => 'Auth Algorithm',
                                'width'       => '250px',
                                'readonly'    => $readonly,
                                'values'      => $authalgo,
                                'value'       => $device['snmp_authalgo']);

$form['row'][12]['snmp_cryptopass'] = array(
                                'type'        => 'password',
                                'fieldset'    => 'snmpv3',
                                'name'        => 'Crypto Password',
                                'width'       => '250px',
                                'readonly'    => $readonly,
                                'show_password' => !$readonly,
                                'value'       => $device['snmp_cryptopass']); // FIXME. For passwords we should use filter instead escape!
$form['row'][13]['snmp_cryptoalgo'] = array(
                                'type'        => 'select',
                                'fieldset'    => 'snmpv3',
                                'name'        => 'Crypto Algorithm',
                                'width'       => '250px',
                                'readonly'    => $readonly,
                                'values'      => $cryptoalgo,
                                'value'       => $device['snmp_cryptoalgo']);

$form['row'][15]['snmp_context'] = array(
                              'type'        => 'password',
                              'fieldset'    => 'snmpextra',
                              'name'        => 'SNMP Context',
                              'width'       => '250px',
                              'readonly'    => $readonly,
                              'show_password' => !$readonly,
                              'placeholder' => '(Optional) Context',
                              'value'       => $device['snmp_context']); // FIXME. For passwords we should use filter instead escape!

$form['row'][20]['submit']    = array(
                                'type'        => 'submit',
                                'fieldset'    => 'submit',
                                'name'        => 'Save Changes',
                                'icon'        => 'icon-ok icon-white',
                                //'right'       => TRUE,
                                'class'       => 'btn-primary',
                                'readonly'    => $readonly,
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
