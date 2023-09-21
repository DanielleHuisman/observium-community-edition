<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($vars['editing']) {
  if ($readonly) {
    print_error_permission('You have insufficient permissions to edit settings.');
  } else {

    if ($vars['wmi_override']) {
      set_entity_attrib('device', $device, 'wmi_override', $vars['wmi_override']);
    } else {
      del_entity_attrib('device', $device, 'wmi_override');
    }
    if (!empty($vars['wmi_hostname'])) {
      set_entity_attrib('device', $device, 'wmi_hostname', $vars['wmi_hostname']);
    } else {
      del_entity_attrib('device', $device, 'wmi_hostname');
    }
    if (!empty($vars['wmi_domain'])) {
      set_entity_attrib('device', $device, 'wmi_domain', $vars['wmi_domain']);
    } else {
      del_entity_attrib('device', $device, 'wmi_domain');
    }
    if (!empty($vars['wmi_username'])) {
      set_entity_attrib('device', $device, 'wmi_username', $vars['wmi_username']);
    } else {
      del_entity_attrib('device', $device, 'wmi_username');
    }
    if (!empty($vars['wmi_password'])) {
      set_entity_attrib('device', $device, 'wmi_password', $vars['wmi_password']);
    } else {
      del_entity_attrib('device', $device, 'wmi_password');
    }

    $update_message = "Device WMI data updated.";
    $updated        = 1;

    if ($vars['toggle_poller'] && isset($GLOBALS['config']['wmi']['modules'][$vars['toggle_poller']])) {
      $module = $vars['toggle_poller'];
      if (isset($attribs['wmi_poll_' . $module]) && $attribs['wmi_poll_' . $module] != $GLOBALS['config']['wmi']['modules'][$vars['toggle_poller']]) {
        del_entity_attrib('device', $device, 'wmi_poll_' . $module);
      } elseif ($GLOBALS['config']['wmi']['modules'][$vars['toggle_poller']] == 0) {
        set_entity_attrib('device', $device, 'wmi_poll_' . $module, "1");
      } else {
        set_entity_attrib('device', $device, 'wmi_poll_' . $module, "0");
      }
    }

    $attribs = get_entity_attribs('device', $device['device_id'], TRUE);
  }
}

if (!$readonly) {
  include_once($GLOBALS['config']['install_dir'] . "/includes/wmi.inc.php");
  // Validate cmd path
  $wmi_ok = TRUE;

  if (!wmi_cmd($device)) {
    print_warning("The wmic binary (or script) was not found at the configured path (" . $config['wmic'] . "). WMI polling will not work.");
    $wmi_ok = FALSE;
  }

  // Validate WMI poller module
  if ($wmi_ok && !is_module_enabled($device, 'wmi', 'poller')) {
    $modules_link = generate_device_link($device, 'only on this device here', ['tab' => 'edit', 'section' => 'modules']);
    $global_link  = generate_link('globally here', ['page' => 'settings', 'section' => 'polling']);
    print_warning("WMI module not enabled. Enable <strong>Poller</strong> module WMI $modules_link, or $global_link.");
    //$wmi_ok = FALSE;
  }

  // Validate WMI access
  if ($wmi_ok) {
    $wql      = "SELECT Name FROM Win32_ComputerSystem";
    $wmi_name = wmi_get($device, $wql, "Name");
    if (is_null($wmi_name)) {
      $docs_link = '<a target="_blank" href="' . OBSERVIUM_DOCS_URL . '/device_windows/' . '">here</a>';
      print_error("Invalid security credentials or insufficient WMI security permissions. Read documentation $docs_link.");
      $wmi_ok = FALSE;
    } else {
      print_success("WMI successfully connected, remote device name is: <strong>$wmi_name<strong>.");
    }
  }
}

?>

  <div class="row">
    <div class="col-md-6">

      <?php
      $wmi_override = get_dev_attrib($device, 'wmi_override');
      $form         = ['type'     => 'horizontal',
                       'id'       => 'edit',
                       //'space'     => '20px',
                       'title'    => 'WMI Settings',
                       //'icon'      => 'oicon-gear',
                       //'class'     => 'box box-solid',
                       'fieldset' => ['edit' => ''],
      ];

      $form['row'][0]['editing']      = [
        'type'  => 'hidden',
        'value' => 'yes'
      ];
      $form['row'][1]['wmi_override'] = [
        'type'     => 'toggle',
        'name'     => 'Override WMI Config',
        'readonly' => $readonly,
        'onchange' => "toggleAttrib('disabled', [ 'wmi_hostname', 'wmi_domain', 'wmi_username', 'wmi_password' ])",
        'value'    => $wmi_override
      ];
      $form['row'][2]['wmi_hostname'] = [
        'type'     => 'text',
        'name'     => 'WMI Hostname',
        'width'    => '250px',
        'readonly' => $readonly,
        'disabled' => !$wmi_override,
        'value'    => get_dev_attrib($device, 'wmi_hostname')
      ];
      $form['row'][3]['wmi_domain']   = [
        'type'     => 'text',
        'name'     => 'WMI Domain',
        'width'    => '250px',
        'readonly' => $readonly,
        'disabled' => !$wmi_override,
        'value'    => get_dev_attrib($device, 'wmi_domain')
      ];
      $form['row'][4]['wmi_username'] = [
        'type'     => 'text',
        'name'     => 'WMI Username',
        'width'    => '250px',
        'readonly' => $readonly,
        'disabled' => !$wmi_override,
        'value'    => get_dev_attrib($device, 'wmi_username')
      ];
      $form['row'][5]['wmi_password'] = [
        'type'          => 'password',
        'name'          => 'WMI Password',
        'width'         => '250px',
        'readonly'      => $readonly,
        'disabled'      => !$wmi_override,
        'show_password' => !$readonly,
        'value'         => get_dev_attrib($device, 'wmi_password')
      ];

      $form['row'][7]['submit'] = [
        'type'     => 'submit',
        'name'     => 'Save Changes',
        'icon'     => 'icon-ok icon-white',
        'class'    => 'btn-primary',
        'readonly' => $readonly,
        'value'    => 'save'
      ];
      print_form($form);
      unset($form);
      ?>
    </div>
    <div class="col-md-6">
      <div class="box box-solid">
        <div class="box-header with-border">
          <h3 class="box-title">WMI Poller Modules</h3>
        </div>
        <div class="box-body no-padding">
          <table class="table  table-striped table-condensed ">
            <thead>
            <tr>
              <th>Module</th>
              <th style="width: 80;">Global</th>
              <th style="width: 80;">Device</th>
              <th style="width: 80;"></th>
            </tr>
            </thead>
            <tbody>
            <?php

            foreach ($GLOBALS['config']['wmi']['modules'] as $module => $module_status) {
              echo('<tr><td><b>' . $module . '</b></td><td>');

              echo(($module_status ? '<span class="label label-success">enabled</span>' : '<span class="label label-important">disabled</span>'));

              echo('</td><td>');

              if (isset($attribs['wmi_poll_' . $module])) {
                if ($attribs['wmi_poll_' . $module]) {
                  echo('<span class="label label-success">enabled</span>');
                  $toggle    = "Disable";
                  $btn_class = "btn-danger";
                } else {
                  echo('<span class="label label-important">disabled</span>');
                  $toggle    = "Enable";
                  $btn_class = "btn-success";
                }
              } else {
                if ($module_status) {
                  echo('<span class="label label-success">enabled</span>');
                  $toggle    = "Disable";
                  $btn_class = "btn-danger";
                } else {
                  echo('<span class="label label-important">disabled</span>');
                  $toggle    = "Enable";
                  $btn_class = "btn-success";
                }
              }

              echo('</td><td>');

              $form = ['type' => 'simple'];
              // Elements
              $form['row'][0]['toggle_poller'] = ['type'  => 'hidden',
                                                  'value' => $module];
              $form['row'][0]['editing']       = ['type'     => 'submit',
                                                  'name'     => $toggle,
                                                  'class'    => 'btn-mini ' . $btn_class,
                                                  //'icon'     => $btn_icon,
                                                  'right'    => TRUE,
                                                  'readonly' => $readonly,
                                                  'value'    => 'toggle_poller'];
              print_form($form);
              unset($form);

              echo('</td></tr>');
            }

            ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php

// EOF
