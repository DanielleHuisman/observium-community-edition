<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

if ($_SESSION['userlevel'] < 5) {
    print_error_permission();
    return;
}

// Alert test display and editing page.
include($config['html_dir'] . "/includes/alerting-navbar.inc.php");

// Begin Actions
$readonly = $_SESSION['userlevel'] < 9; // Currently edit allowed only for Admins

if (!$check = dbFetchRow("SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?", [ $vars['alert_test_id'] ])) {
    print_error_permission();
    return;
}

// Hardcode Device sysContact
if (!$readonly && !dbExist('alert_contacts', '`contact_method` = ?', ['syscontact'])) {
    $syscontact = [
      'contact_descr'    => 'Device sysContact',
      'contact_method'   => 'syscontact',
      'contact_endpoint' => '{"syscontact":"device"}',
      //'contact_disabled'         => '0',
      //'contact_disabled_until'   => NULL,
      //'contact_message_custom'   => 0,
      //'contact_message_template' => NULL
    ];
    dbInsert($syscontact, 'alert_contacts');
}

// FIXME: move all actions to separate include(s) with common options!
//if (!$readonly && isset($vars['action']))
if (!$readonly && $vars['action'] && request_token_valid($vars)) {
    // We are editing. Lets see what we are editing.
    if ($vars['action'] === "check_conditions") {
        $conds      = [];
        $cond_array = [];
        foreach (explode("\n", trim($vars['check_conditions'])) as $cond) {
            [$cond_array['metric'], $cond_array['condition'], $cond_array['value']] = explode(" ", trim($cond), 3);
            $conds[] = $cond_array;
        }
        $conds        = json_encode($conds);
        $rows_updated = dbUpdate(['conditions' => $conds, 'and' => $vars['alert_and']], 'alert_tests', '`alert_test_id` = ?', [$vars['alert_test_id']]);
    } elseif ($vars['action'] === "alert_details") {
        if ($entry = dbFetchRow('SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?', [$vars['alert_test_id']])) {
            //print_vars($entry);
            $update_array = [
              'alert_name'        => $vars['alert_name'],
              'alert_message'     => $vars['alert_message'],
              'severity'          => $vars['alert_severity'],
              'delay'             => $vars['alert_delay'],
              'suppress_recovery' => isset($vars['alert_send_recovery']) ? 0 : 1
            ];
            if ($vars['entity_type'] && isset($config['entities'][$vars['entity_type']])) {
                $update_array['entity_type'] = $vars['entity_type'];
            }
            //print_vars($update_array);
            foreach ($update_array as $column => $value) {
                if ((string)$value === $entry[$column]) {
                    unset($update_array[$column]);
                }
            }
            if ($update_array) {
                $rows_updated = dbUpdate($update_array, 'alert_tests', '`alert_test_id` = ?', [$entry['alert_test_id']]);
            }
            unset($entry, $update_array);
        }
    } elseif ($vars['action'] === "assoc_add") {
        $d_conds    = [];
        $cond_array = [];
        foreach (explode("\n", trim($vars['assoc_device_conditions'])) as $cond) {
            [$cond_array['attrib'], $cond_array['condition'], $cond_array['value']] = explode(" ", trim($cond), 3);
            $d_conds[] = $cond_array;
        }
        $d_conds = json_encode($d_conds);

        $e_conds    = [];
        $cond_array = [];
        foreach (explode("\n", trim($vars['assoc_entity_conditions'])) as $cond) {
            [$cond_array['attrib'], $cond_array['condition'], $cond_array['value']] = explode(" ", trim($cond), 3);
            $e_conds[] = $cond_array;
        }
        $e_conds = json_encode($e_conds);
        $rows_id = dbInsert('alert_assoc', ['alert_test_id' => $vars['alert_test_id'], 'entity_type' => $check['entity_type'], 'device_attribs' => $d_conds, 'entity_attribs' => $e_conds]);
        if ($rows_id) {
            $rows_updated++;
        }
    } elseif ($vars['action'] === "delete_assoc" && $vars['assoc_id'] && $vars['confirm_' . $vars['assoc_id']]) {
        $rows_updated = dbDelete('alert_assoc', '`alert_assoc_id` = ?', [$vars['assoc_id']]);
    } elseif ($vars['action'] === "assoc_conditions" && $vars['assoc_id']) {
        $d_conds    = [];
        $cond_array = [];
        foreach (explode("\n", trim($vars['assoc_device_conditions_' . $vars['assoc_id']])) as $cond) {
            [$cond_array['attrib'], $cond_array['condition'], $cond_array['value']] = explode(" ", trim($cond), 3);
            $d_conds[] = $cond_array;
        }
        $d_conds = json_encode($d_conds);

        $e_conds    = [];
        $cond_array = [];
        foreach (explode("\n", trim($vars['assoc_entity_conditions_' . $vars['assoc_id']])) as $cond) {
            [$cond_array['attrib'], $cond_array['condition'], $cond_array['value']] = explode(" ", trim($cond), 3);
            $e_conds[] = $cond_array;
        }
        $e_conds      = json_encode($e_conds);
        $rows_updated = dbUpdate(['device_attribs' => $d_conds, 'entity_attribs' => $e_conds], 'alert_assoc', '`alert_assoc_id` = ?', [$vars['assoc_id']]);
    } elseif ($vars['action'] === "delete_alert_checker" && $vars['alert_test_id'] && $vars['confirm']) {
        // Maybe expand this to output more info.

        dbDelete('alert_tests', '`alert_test_id` = ?', [$vars['alert_test_id']]);
        dbDelete('alert_table', '`alert_test_id` = ?', [$vars['alert_test_id']]);
        dbDelete('alert_assoc', '`alert_test_id` = ?', [$vars['alert_test_id']]);
        dbDelete('alert_contacts_assoc', '`alert_checker_id` = ?', [$vars['alert_test_id']]);

        print_message("Deleted all traces of alert checker " . $vars['alert_test_id']);
        unset($vars['alert_test_id']);
        return;
    }

    if ($rows_updated > 0) {
        $update_message = $rows_updated . " Record(s) updated.";
        $update_type    = 'success';
        //set_obs_attrib('alerts_require_rebuild', '1'); not required anymore
    } elseif ($rows_updated = '-1') {
        $update_message = "Record(s) unchanged. No update necessary.";
        $update_type    = '';
    } else {
        $update_message = "Record(s) update error.";
        $update_type    = 'error';
    }

    print_message($update_message, $update_type);

    // Refresh the $check array to reflect the updates
    $check = dbFetchRow("SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?", [$vars['alert_test_id']]);
}

// Process the alert checker to add classes and colours and count status.
humanize_alert_check($check);

/// End bit to go in to function

?>

    <div class="row">
    <div class="col-md-12">

<?php

echo generate_box_open($box_args);

$where  = '`aca_type` = ? AND `alert_checker_id` = ?';
$params = ['alert', $vars['alert_test_id']];
if ($config['email']['default_syscontact']) {
    $where    = "($where) OR `contact_method` = ?";
    $params[] = 'syscontact';
}

$sql      = "SELECT * FROM `alert_contacts_assoc`
          LEFT JOIN `alert_contacts` ON `alert_contacts`.`contact_id` = `alert_contacts_assoc`.`contact_id`
          WHERE $where";
$contacts = dbFetchRows($sql, $params);

echo('
        <table class="' . OBS_CLASS_TABLE_STRIPED . '">
         <thead>
          <tr>
            <th class="state-marker"></th>
            <!--<th style="width: 5%;">Test ID</th>-->
            <th style="width: 15%;">Name / Type</th>
            <th>Message</th>
            <th style="width: 5%;">Criteria</th>
            <th style="width: 25%;">Test Conditions</th>
            <th style="width: 7%;">Options</th>
            <th style="width: 10%;">Status / Contacts</th>
          </tr>
        </thead>
        <tbody>
          <tr class="' . $check['html_row_class'] . '">
            <td class="state-marker"></td>
            <!--<td>' . $check['alert_test_id'] . '</td>-->
            <td><b>' . escape_html($check['alert_name']) . '</b><br />
                <i class="' . $config['entities'][$check['entity_type']]['icon'] . '"></i> ' . nicecase($check['entity_type']) . '</td>
            <td><i><small>' . escape_html($check['alert_message']) . '</small></i></td>
            <td>');


echo ($check['and'] ? '<i class="sprite-logic-and"></i> <span class="label label-primary">AND (ALL)</span>' : '<i class="sprite-logic-and"></i> <span class="label label-success">OR (ANY)</span>') . '<br />';

echo '</td>';

//r($check);
$conditions      = safe_json_decode($check['conditions']);
$allowed_metrics = array_keys($config['entities'][$check['entity_type']]['metrics']);

// FIXME. Currently hardcoded in check_entity(), need rewrite to timeranges.
$allowed_metrics[] = 'time';
$allowed_metrics[] = 'weekday';

$condition_text_block = [];
$suggest_entity_types = [$check['entity_type'] => ['name' => $config['entities'][$check['entity_type']]['name'],
                                                   'icon' => $config['entities'][$check['entity_type']]['icon']]];


echo '<td>';
echo '<table class="' . OBS_CLASS_TABLE_STRIPED_MORE . '">';

foreach ($conditions as $condition) {
    // Detect incorrect metric used
    if (!in_array($condition['metric'], $allowed_metrics, TRUE)) {
        print_error("Unknown condition metric '" . escape_html($condition['metric']) . "' for Entity type '" . escape_html($check['entity_type']) . "'");

        foreach (array_keys($config['entities']) as $suggest_entity) {
            if (isset($config['entities'][$suggest_entity]['metrics'][$condition['metric']])) {
                print_warning("Suggested Entity type: '$suggest_entity'. Change Entity in Edit Alert below.");
                $suggest_entity_types[$suggest_entity] = ['name' => $config['entities'][$suggest_entity]['name'],
                                                          'icon' => $config['entities'][$suggest_entity]['icon']];
            }
        }
    }

    echo '<tr><td>' . escape_html($condition['metric']) . '</td><td>' . escape_html($condition['condition']) .
        '</td><td>' . escape_html(str_replace(",", ", ", $condition['value'])) . '</td></tr>';

    $condition_text_block[] = $condition['metric'] . ' ' . $condition['condition'] . ' ' . $condition['value'];
    //str_replace(',', ',&#x200B;', $condition['value']); // Add hidden space char (&#x200B;) for wrap long lists
}

echo '</table>';

//echo '<code style="white-space: pre-wrap; word-wrap: break-word;">' . implode('<br />', array_map('escape_html', $condition_text_block)) . '</code>';
echo '</td>';


echo('
            <td>');

// Show used Severity
echo('<span class="label label-' . $config['alerts']['severity'][$check['severity']]['label-class'] . '" title="Severity">' . $config['alerts']['severity'][$check['severity']]['name'] . '</span><br />');

if ($check['suppress_recovery']) {
    echo('<span class="label label-suppressed" title="Recovery notification suppressed">No Recovery</span><br />');
}

if ($check['delay'] > 0) {
    echo '<span class="label label-delayed">Delay ' . escape_html($check['delay']) . '</span><br />';
}

echo '
            </td>
            <td><i>' . $check['status_numbers'] . '</i><br />';

if ($count = safe_count($contacts)) {
    $content = "";
    foreach ($contacts as $contact) {
        $content .= '<span class="label">' . escape_html($contact['contact_method']) . '</span> ' . escape_html($contact['contact_descr']) . '<br />';
    }
    echo generate_tooltip_link('', '<span class="label label-success">' . $count . ' Notifiers</span>', $content);
} else {
    echo '<span class="label label-primary">Default Notifier</span>';
}


echo '</td>
          </tr>
        </tbody></table>' . PHP_EOL;


echo generate_box_close();
echo('
  </div>
</div>');


// Build group-specific navbar

$navbar = ['brand' => escape_html($check['alert_name']), 'class' => "navbar-narrow"];

$navbar['options']['entries'] = ['text' => 'Alert Entries'];
$navbar['options']['assoc']   = ['text' => 'Associations', 'icon' => $config['icon']['bgp'], 'right' => TRUE];


if (!$readonly) {
    $navbar['options_right']['edit_test']  = ['text' => 'Edit Conditions', 'icon' => $config['icon']['config'], 'url' => '#modal-edit_conditions', 'link_opts' => 'data-toggle="modal"'];
    $navbar['options_right']['edit_alert'] = ['text' => 'Edit Check', 'icon' => $config['icon']['tools'], 'url' => '#modal-edit_alert', 'link_opts' => 'data-toggle="modal"'];
    $navbar['options_right']['dupe_alert'] = ['text' => 'Duplicate Check', 'icon' => $config['icon']['plus'], 'url' => generate_url(['page' => 'add_alert_check', 'entity_type' => $check['entity_type'], 'duplicate_id' => $check['alert_test_id']])];
    $navbar['options_right']['delete']     = ['text' => 'Delete', 'icon' => $config['icon']['cancel'], 'url' => '#modal-delete_alert', 'link_opts' => 'data-toggle="modal"'];
}

foreach ($navbar['options'] as $option => $array) {
    if (!isset($vars['view'])) {
        $vars['view'] = $option;
    }
    if ($vars['view'] == $option) {
        $navbar['options'][$option]['class'] .= " active";
    }
    $navbar['options'][$option]['url'] = generate_url($page_array, ['view' => $option]);
}

$page_array = ['page' => 'alert_check', 'alert_test_id' => $check['alert_test_id']];

foreach ($navbar['options'] as $option => $array) {
    if (!isset($vars['view'])) {
        $vars['view'] = $option;
    }
    if ($vars['view'] == $option) {
        $navbar['options'][$option]['class'] .= " active";
    }
    $navbar['options'][$option]['url'] = generate_url($page_array, ['view' => $option]);
}

print_navbar($navbar);
unset($navbar);

$modals = '';
if (!$readonly) {
    /* Begin edit conditions */
    /*
    $modal_args = array(
      'id'    => 'modal-edit_conditions',
      'title' => 'Edit Checker Conditions',
      //'icon'  => 'oicon-target',
      //'hide'  => TRUE,
      //'fade'  => TRUE,
      //'role'  => 'dialog',
      //'class' => 'modal-md',
    );
    */

    $form = [
        'type'      => 'horizontal',
        'userlevel' => 9,          // Minimum user level for display form
        'id'        => 'modal-edit_conditions',
        'title'     => 'Edit Checker Conditions',
        //'modal_args' => $modal_args, // !!! This generate modal specific form
        //'class'      => '',          // Clean default box class!
        //'help'       => 'Please exercise care when editing here.',
        //'url'        => generate_url(array('page' => 'alert_check')),
    ];
    $form['fieldset']['body'] = [//'class' => 'modal-body',    // Required this class for modal body!
                                 'offset' => FALSE];          // Do not add 'controls' class, disable 180px margin for form element
    //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

    $form_params                 = [];
    $form_params['alert_and'][0] = ['name' => 'Require any condition', 'icon' => $config['icon']['or-gate']];
    $form_params['alert_and'][1] = ['name' => 'Require all conditions', 'icon' => $config['icon']['and-gate']];

    //r($condition_text_block);

    $form['row'][5]['alert_and']        = [
      'type'        => 'select',
      'fieldset'    => 'body',
      //'name'        => 'All/Any',
      'width'       => '320px',
      //'class'       => 'col-md-12',
      //'offset'      => FALSE, // Do not add 'controls' class, disable 180px margin for form element
      'live-search' => FALSE,
      'values'      => $form_params['alert_and'],
      'value'       => $check['and']];
    $form['row'][6]['check_conditions'] = [
      'type'     => 'textarea',
      'fieldset' => 'body',
      'name'     => 'Conditions',
      //'width'       => '270px',
      'class'    => 'col-md-12',
      //'offset'      => FALSE, // Do not add 'controls' class, disable 180px margin for form element
      'rows'     => 3,
      //'placeholder' => 'sysReboots.0',
      'value'    => implode(PHP_EOL, $condition_text_block)];

    $form['row'][7]['metrics'] = [
      'type'     => 'html',
      'fieldset' => 'body',
      'class'    => 'col-md-5',
      //'name'     => 'List of known metrics:',
      'html'     => generate_alert_metrics_table($check['entity_type'])
    ];

    $form['row'][99]['close']  = [
      'type'      => 'submit',
      'fieldset'  => 'footer',
      'div_class' => '', // Clean default form-action class!
      'name'      => 'Close',
      'icon'      => '',
      'attribs'   => ['data-dismiss' => 'modal',
                      'aria-hidden'  => 'true']];
    $form['row'][99]['action'] = [
      'type'      => 'submit',
      'fieldset'  => 'footer',
      'div_class' => '', // Clean default form-action class!
      'name'      => 'Save Changes',
      'icon'      => 'icon-ok icon-white',
      //'right'       => TRUE,
      'class'     => 'btn-primary',
      'value'     => 'check_conditions'];

    $modals .= generate_form_modal($form);
    unset($form, $form_params);
    /* End edit conditions */

    /* Begin edit alert */
    /*
    $modal_args = array(
      'id'    => 'modal-edit_alert',
      'title' => 'Edit Checker Details',
      //'icon'  => 'oicon-target',
      //'hide'  => TRUE,
      //'fade'  => TRUE,
      //'role'  => 'dialog',
      'class' => 'modal-md',
    );
    */

    $form = [
        'type'      => 'horizontal',
        'userlevel' => 9,          // Minimum user level for display form
        'id'        => 'modal-edit_alert',
        'title'     => 'Edit Checker Details',
        //'modal_args' => $modal_args, // !!! This generate modal specific form
        //'class'      => 'modal-lg',          // Clean default box class!
        //'help'       => 'Please exercise care when editing here.',
        //'url'        => generate_url(array('page' => 'alert_check')),
    ];
    //$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
    //                                    //'offset'=> FALSE);          // Do not add 'controls' class, disable 180px margin for form element
    //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

    $form_params                   = [];
    $form_params['alert_severity'] = $config['alerts']['severity'];

    $form['row'][3]['entity_type']         = [
      'type'     => 'select',
      'fieldset' => 'body',
      'name'     => 'Entity Type',
      'width'    => '320px',
      'readonly' => count($suggest_entity_types) <= 1, // Do not change without suggested types
      'value'    => $check['entity_type'],
      'values'   => $suggest_entity_types];
    $form['row'][4]['alert_test_id']       = [
      'type'     => 'hidden',
      'fieldset' => 'body',
      'value'    => $check['alert_test_id']];
    $form['row'][5]['alert_name']          = [
      'type'        => 'text',
      'fieldset'    => 'body',
      'name'        => 'Alert Name',
      //'class'       => 'input-xlarge',
      'width'       => '320px',
      'placeholder' => TRUE,
      'value'       => $check['alert_name']];
    $form['row'][6]['alert_message']       = [
      'type'        => 'textarea',
      'fieldset'    => 'body',
      'name'        => 'Alert message',
      //'class'       => 'input-xlarge',
      'width'       => '320px',
      'rows'        => 3,
      'placeholder' => 'Alert message',
      'value'       => $check['alert_message']];
    $form['row'][7]['alert_delay']         = [
      'type'        => 'text',
      'fieldset'    => 'body',
      'name'        => 'Alert Delay',
      //'class'       => 'input-xlarge',
      'width'       => '320px',
      'placeholder' => "&#8470; of checks to delay alert",
      'value'       => $check['delay']];
    $form['row'][8]['alert_send_recovery'] = [
      'type'     => 'toggle',
      'fieldset' => 'body',
      'name'     => 'Send recovery notification',
      'class'    => 'text-nowrap',
      'view'     => 'toggle',
      'size'     => 'big',
      'palette'  => 'blue',
      'value'    => !$check['suppress_recovery']];
    /* There is no database field for this, so we hardcode this */
    $form['row'][9]['alert_severity'] = [
      'type'        => 'select',
      'fieldset'    => 'body',
      'name'        => 'Severity',
      'width'       => '320px',
      'live-search' => FALSE,
      'values'      => $form_params['alert_severity'],
      'value'       => $check['severity']];

    $form['row'][99]['close']  = [
      'type'      => 'submit',
      'fieldset'  => 'footer',
      'div_class' => '', // Clean default form-action class!
      'name'      => 'Close',
      'icon'      => '',
      'attribs'   => ['data-dismiss' => 'modal',
                      'aria-hidden'  => 'true']];
    $form['row'][99]['action'] = [
      'type'      => 'submit',
      'fieldset'  => 'footer',
      'div_class' => '', // Clean default form-action class!
      'name'      => 'Save Changes',
      'icon'      => 'icon-ok icon-white',
      //'right'       => TRUE,
      'class'     => 'btn-primary',
      'value'     => 'alert_details'];

    $modals .= generate_form_modal($form);
    unset($form, $form_params);
    /* End edit alert */

    /* Begin delete alert */
    /*
    $modal_args = array(
      //'hide'  => TRUE,
      //'fade'  => TRUE,
      //'role'  => 'dialog',
      //'class' => 'modal-md',
    );
    */

    $form = [
        'type'      => 'horizontal',
        'userlevel' => 9,          // Minimum user level for display form
        'id'        => 'modal-delete_alert',
        'title'     => 'Delete Alert Checker' . ' (Id: ' . $check['alert_test_id'] . ', ' . escape_html($check['alert_name']) . ')',
        //'modal_args' => $modal_args,
        //'help'     => 'This will completely delete the alert checker and all device/entity associations.',
        //'class'      => '', // Clean default box class!
        //'url'       => ''
    ];
    //$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
    //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

    $form['row'][0]['alert_test_id'] = [
      'type'     => 'hidden',
      'fieldset' => 'body',
      'value'    => $check['alert_test_id']];
    $form['row'][0]['action']        = [
      'type'     => 'hidden',
      'fieldset' => 'body',
      'value'    => 'delete_alert_checker'];

    $form['row'][6]['confirm']       = [
      'type'        => 'checkbox',
      'fieldset'    => 'body',
      'name'        => 'Confirm',
      //'offset'      => FALSE,
      'placeholder' => 'Yes, please delete this alert checker!',
      'onchange'    => "javascript: toggleAttrib('disabled', 'delete_alert_checker'); showDiv(!this.checked, 'warning_alert_div');",
      'value'       => 'confirm'];
    $form['row'][7]['warning_alert'] = [
      'type'      => 'html',
      'fieldset'  => 'body',
      'html'      => '<h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>' .
                     ' This checker and its associations will be completely deleted!',
      'div_style' => 'display: none', // hide initially
      'div_class' => 'alert alert-warning'];

    $form['row'][99]['close']                = [
      'type'      => 'submit',
      'fieldset'  => 'footer',
      'div_class' => '', // Clean default form-action class!
      'name'      => 'Close',
      'icon'      => '',
      'attribs'   => ['data-dismiss' => 'modal',  // dismiss modal
                      'aria-hidden'  => 'true']]; // do not sent any value
    $form['row'][99]['delete_alert_checker'] = [
      'type'      => 'submit',
      'fieldset'  => 'footer',
      'div_class' => '', // Clean default form-action class!
      'name'      => 'Delete Alert',
      'icon'      => 'icon-trash icon-white',
      //'right'       => TRUE,
      'class'     => 'btn-danger',
      'disabled'  => TRUE,
      'value'     => 'delete_alert_checker'];

    $modals .= generate_form_modal($form);
    unset($form);
    /* End delete alert */
}

if ($vars['view'] === "assoc") {
    register_html_title('Alert Associations');

    $assocs = dbFetchRows("SELECT * FROM `alert_assoc` WHERE `alert_test_id` = ?", [$vars['alert_test_id']]);

    ?>
    <div class="row">
    <div class="col-md-8">

        <?php

        if (!is_null($check['alert_assoc'])) {

            register_html_resource('css', 'query-builder.default.css');
            register_html_resource('js', 'jQuery.extendext.min.js');
            register_html_resource('js', 'doT.min.js');
            register_html_resource('js', 'query-builder.js');
            register_html_resource('js', 'bootbox.min.js');
            register_html_resource('js', 'bootstrap-select.min.js');
            register_html_resource('js', 'interact.min.js');

            //r($check);

            echo generate_box_open(['title' => 'Entity Association Ruleset', 'padding' => FALSE, 'header-border' => TRUE]);

            $form_id = 'rules-' . random_string(8);

            //echo '<div id="' . $form_id . '"></div>';
            //echo '<div id="output"></div>';

            generate_querybuilder_form($check['entity_type'], 'attribs', $form_id, $check['alert_assoc']);

// FIXME. Duplicated scripts for #btn-save, #btn-reset
            $script = "<script>
  $('#btn-save').on('click', function() {
    var result = $('#" . $form_id . "').queryBuilder('getRules');
    var div = $('#output');

    if (!$.isEmptyObject(result)) {

      var formData = JSON.stringify({
                                action: 'alert_assoc_edit',
                                alert_assoc: JSON.stringify(result),
                                alert_test_id: '" . $check['alert_test_id'] . "',
                                requesttoken: document.getElementById('requesttoken').value
      });
      
      var request = $.ajax({
        type: 'POST',
        url: 'ajax/actions.php',
        data: formData,
        dataType: 'json',
        contentType : 'application/json',
      });
      
      request.success(  function(json) {
      
        if (json.status === 'ok') {
            div.html('<div class=\"alert alert-success\">Update Succeeded. Redirecting!</div>')
            reload_timeout(5000, json.redirect);
        } else if (json.status === 'warning') {
            div.html('<div class=\"alert alert-warning\">Warning: ' + json.message + '</div>')
            delay_empty(div, 10000);
        } else {
            div.html('<div class=\"alert alert-danger\">Update Failed: ' + json.message + '</div>')
            delay_empty(div);
        }
      });
    }
  });
  
  $('#btn-reset').on('click', function() {
    $('#" . $form_id . "').queryBuilder('reset');
  });
  
  
  </script>
  ";

            $footer_content
              = '
<div class="btn-group pull-right">
    <button class="btn btn-danger" id="btn-reset" data-target="' . $form_id . '"><i class="icon-trash"></i> Clear Rules</button>
    <button class="btn btn-primary" id="btn-set" data-target="' . $form_id . '"><i class="icon-refresh"></i> Restore Rules</button>
    <button class="btn btn-success" id="btn-save" data-target="' . $form_id . '"><i class="icon-ok"></i> Save Changes</button>
</div>' . $script;

            echo generate_box_close(['footer_content' => $footer_content]);

        } else {

            $box_args = ['title'         => 'Associations',
                         'header-border' => TRUE,
            ];

            if ($_SESSION['userlevel'] >= 9) {
                $box_args['header-controls'] = ['controls' => ['edit' => ['text' => 'Add',
                                                                          'icon' => $config['icon']['plus'],
                                                                          'url'  => '#modal-add_assoc',
                                                                          'data' => 'data-toggle="modal"']]];
            }

            echo generate_box_open($box_args);

            echo '<table class="' . OBS_CLASS_TABLE_STRIPED . '">';

            ?>
            <thead>
            <tr>
                <th style="width: 2%;">ID</th>
                <th style="width: 30%;">Device Match</th>
                <th style="">Entity Match</th>
                <th style="width: 10%;"></th>
            </tr>
            </thead>

            <?php

            foreach ($assocs as $assoc_id => $assoc) {
                echo('<tr>' . PHP_EOL);
                echo('<td>' . $assoc['alert_assoc_id'] . '</td>' . PHP_EOL);
                echo('<td>');
                echo('<code>');
                $assoc['device_attribs'] = json_decode($assoc['device_attribs'], TRUE);
                $assoc_dev_text          = [];
                if (is_array($assoc['device_attribs'])) {
                    foreach ($assoc['device_attribs'] as $attribute) {
                        echo(escape_html($attribute['attrib']) . ' ');
                        echo(escape_html($attribute['condition']) . ' ');
                        echo(escape_html($attribute['value']));
                        echo('<br />');
                        $assoc_dev_text[] = $attribute['attrib'] . ' ' . $attribute['condition'] . ' ' . $attribute['value'];
                    }
                } else {
                    echo("*");
                }

                echo("</code>");
                echo('</td>');
                echo('<td><code style="white-space: pre-wrap;">');
                $assoc['entity_attribs'] = json_decode($assoc['entity_attribs'], TRUE);
                $assoc_entity_text       = [];
                if (is_array($assoc['entity_attribs'])) {
                    foreach ($assoc['entity_attribs'] as $attribute) {
                        echo(escape_html($attribute['attrib']) . ' ');
                        echo(escape_html($attribute['condition']) . ' ');
                        echo(str_replace(',', ',&#x200B;',
                                         escape_html($attribute['value']))); // add empty whitespace to commas for wrap
                        echo('<br />');
                        $assoc_entity_text[] = $attribute['attrib'] . ' ' . $attribute['condition'] . ' ' . $attribute['value'];
                    }
                } else {
                    echo("*");
                }
                echo '</code></td>';
                echo '<td style="text-align: right;">';

                if ($_SESSION['userlevel'] >= 9) {
                    echo('<a href="#modal-assoc_edit_' . $assoc['alert_assoc_id'] . '" data-toggle="modal"><i class="' . $config['icon']['tools'] . '"></i></a>&nbsp;');

                    $form = [
                        'type'      => 'simple',
                        'userlevel' => 9,          // Minimum user level for display form
                        'id'        => 'assoc_del_' . $assoc['alert_assoc_id'],
                        //'title'      => 'Delete Association Rule (Id: '. $assoc['alert_assoc_id'] . ')',
                        'style'     => 'display:inline;',
                    ];
                    $form['row'][0]['assoc_id']                            = [
                      'type'  => 'hidden',
                      'value' => $assoc['alert_assoc_id']];
                    $form['row'][0]['confirm_' . $assoc['alert_assoc_id']] = [
                      'type'  => 'hidden',
                      'value' => 1];

                    $form['row'][99]['action'] = [
                        //$form['row'][99]['submit'] = array(
                        'type'      => 'submit',
                        'icon_only' => TRUE, // hide button styles
                        'name'      => '',
                        'icon'      => $config['icon']['cancel'],
                        //'right'       => TRUE,
                        //'class'       => 'btn-small',
                        // confirmation dialog
                        'attribs'   => ['data-toggle'            => 'confirmation', // Enable confirmation dialog
                                        'data-confirm-placement' => 'left',
                                        'data-confirm-content'   => '<div class="alert alert-warning"><h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>
                                                                                        This association will be deleted!</div>'],
                        'value'     => 'delete_assoc'];

                    print_form($form);
                    unset($form);
                }

                echo('</td>');
                echo('</tr>');

                /* Begin Edit association */
                /*
                $modal_args = array(
                  'id'    => 'modal-edit_alert',
                  'title' => 'Edit Checker Details',
                  //'icon'  => 'oicon-target',
                  //'hide'  => TRUE,
                  //'fade'  => TRUE,
                  //'role'  => 'dialog',
                  'class' => 'modal-md',
                );
                */

                $form = ['type'      => 'horizontal',
                         'userlevel' => 9,          // Minimum user level for display form
                         'id'        => 'modal-assoc_edit_' . $assoc['alert_assoc_id'],
                         'title'     => 'Edit Association Conditions (Id: ' . $assoc['alert_assoc_id'] . ')',
                         //'modal_args' => $modal_args, // !!! This generate modal specific form
                         //'class'      => '',          // Clean default box class!
                         //'help'       => 'Please exercise care when editing here.',
                         //'url'        => generate_url(array('page' => 'alert_check')),
                ];
                //$form['fieldset']['body']   = array(//'class' => 'modal-body');   // Required this class for modal body!
                //                                    'offset'=> FALSE);          // Do not add 'controls' class, disable 180px margin for form element
                //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

                $form['row'][0]['assoc_id']                                            = [
                  'type'     => 'hidden',
                  'fieldset' => 'body',
                  'value'    => $assoc['alert_assoc_id']];
                $form['row'][6]['assoc_device_conditions_' . $assoc['alert_assoc_id']] = [
                  'type'        => 'textarea',
                  'fieldset'    => 'body',
                  'name'        => 'Device match',
                  //'class'       => 'input-xlarge',
                  'width'       => '320px',
                  'rows'        => 3,
                  'placeholder' => TRUE,
                  'value'       => implode("\n", $assoc_dev_text)];
                $form['row'][7]['assoc_entity_conditions_' . $assoc['alert_assoc_id']] = [
                  'type'        => 'textarea',
                  'fieldset'    => 'body',
                  'name'        => 'Entity match',
                  //'class'       => 'input-xlarge',
                  'width'       => '320px',
                  'rows'        => 3,
                  'placeholder' => TRUE,
                  'value'       => implode("\n", $assoc_entity_text)];

                $form['row'][99]['close']  = [
                  'type'      => 'submit',
                  'fieldset'  => 'footer',
                  'div_class' => '', // Clean default form-action class!
                  'name'      => 'Close',
                  'icon'      => '',
                  'attribs'   => ['data-dismiss' => 'modal',
                                  'aria-hidden'  => 'true']];
                $form['row'][99]['action'] = [
                  'type'      => 'submit',
                  'fieldset'  => 'footer',
                  'div_class' => '', // Clean default form-action class!
                  'name'      => 'Save Changes',
                  'icon'      => 'icon-ok icon-white',
                  //'right'       => TRUE,
                  'class'     => 'btn-primary',
                  'value'     => 'assoc_conditions'];

                $modals .= generate_form_modal($form);
                unset($form, $form_params);
                /* End Edit association */

                /* Begin delete association */
                /*
                $modal_args = array(
                  //'hide'  => TRUE,
                  //'fade'  => TRUE,
                  //'role'  => 'dialog',
                  //'class' => 'modal-md',
                );
                */

                /* switched to confirm dialog
                $form = array('type'       => 'horizontal',
                              'userlevel'  => 9,          // Minimum user level for display form
                              'id'         => 'modal-assoc_del_'.$assoc['alert_assoc_id'],
                              'title'      => 'Delete Association Rule (Id: '. $assoc['alert_assoc_id'] . ')',
                              //'modal_args' => $modal_args,
                              //'help'     => 'This will delete the selected association rule.',
                              //'class'      => '', // Clean default box class!
                              //'url'       => ''
                              );
                //$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
                //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

                $form['row'][0]['assoc_id'] = array(
                                                'type'        => 'hidden',
                                                'fieldset'    => 'body',
                                                'value'       => $assoc['alert_assoc_id']);
                $form['row'][0]['action']     = array(
                                                  'type'        => 'hidden',
                                                  'fieldset'    => 'body',
                                                  'value'       => 'delete_assoc');

                $form['row'][6]['confirm_'.$assoc['alert_assoc_id']] = array(
                                                'type'        => 'checkbox',
                                                'fieldset'    => 'body',
                                                'name'        => 'Confirm',
                                                //'offset'      => FALSE,
                                                'placeholder' => 'Yes, please delete this association rule!',
                                                'onchange'    => "javascript: toggleAttrib('disabled', 'delete_assoc_".$assoc['alert_assoc_id']."'); showDiv(!this.checked, 'warning_assoc_".$assoc['alert_assoc_id']."_div');",
                                                'value'       => 'confirm');
                $form['row'][7]['warning_assoc_'.$assoc['alert_assoc_id']] = array(
                                                'type'        => 'html',
                                                'fieldset'    => 'body',
                                                'html'        => '<h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>' .
                                                                 ' This association will be deleted!',
                                                'div_style'   => 'display: none', // hide initially
                                                'div_class'   => 'alert alert-warning');

                $form['row'][99]['close'] = array(
                                                'type'        => 'submit',
                                                'fieldset'    => 'footer',
                                                'div_class'   => '', // Clean default form-action class!
                                                'name'        => 'Close',
                                                'icon'        => '',
                                                'attribs'     => array('data-dismiss' => 'modal',  // dismiss modal
                                                                       'aria-hidden'  => 'true')); // do not sent any value
                $form['row'][99]['delete_assoc_'.$assoc['alert_assoc_id']] = array(
                                                'type'        => 'submit',
                                                'fieldset'    => 'footer',
                                                'div_class'   => '', // Clean default form-action class!
                                                'name'        => 'Delete Association',
                                                'icon'        => 'icon-trash icon-white',
                                                //'right'       => TRUE,
                                                'class'       => 'btn-danger',
                                                'disabled'    => TRUE,
                                                'value'       => 'delete_assoc');

                $modals .= generate_form_modal($form);
                unset($form);
                */
                /* End delete association */

            } // End assocation loop

            echo('</table>');

            echo generate_box_close();

            /* Begin Add association */
            /*
            $modal_args = array(
              'id'    => 'modal-edit_alert',
              'title' => 'Edit Checker Details',
              //'icon'  => 'oicon-target',
              //'hide'  => TRUE,
              //'fade'  => TRUE,
              //'role'  => 'dialog',
              'class' => 'modal-md',
            );
            */

            $form = ['type'      => 'horizontal',
                     'userlevel' => 9,          // Minimum user level for display form
                     'id'        => 'modal-add_assoc',
                     'title'     => 'Add Association Conditions',
                     //'modal_args' => $modal_args, // !!! This generate modal specific form
                     //'class'      => '',          // Clean default box class!
                     //'help'       => 'Please exercise care when editing here.',
                     //'url'        => generate_url(array('page' => 'alert_check')),
            ];
            //$form['fieldset']['body']   = array(//'class' => 'modal-body');   // Required this class for modal body!
            //                                    'offset'=> FALSE);          // Do not add 'controls' class, disable 180px margin for form element
            //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

            $form['row'][6]['assoc_device_conditions'] = [
              'type'        => 'textarea',
              'fieldset'    => 'body',
              'name'        => 'Device match',
              //'class'       => 'input-xlarge',
              'width'       => '320px',
              'rows'        => 3,
              'placeholder' => TRUE,
              'value'       => ''];
            $form['row'][7]['assoc_entity_conditions'] = [
              'type'        => 'textarea',
              'fieldset'    => 'body',
              'name'        => 'Entity match',
              //'class'       => 'input-xlarge',
              'width'       => '320px',
              'rows'        => 3,
              'placeholder' => TRUE,
              'value'       => ''];

            $form['row'][99]['close']  = [
              'type'      => 'submit',
              'fieldset'  => 'footer',
              'div_class' => '', // Clean default form-action class!
              'name'      => 'Close',
              'icon'      => '',
              'attribs'   => ['data-dismiss' => 'modal',
                              'aria-hidden'  => 'true']];
            $form['row'][99]['action'] = [
              'type'      => 'submit',
              'fieldset'  => 'footer',
              'div_class' => '', // Clean default form-action class!
              'name'      => 'Add Assocation',
              'icon'      => 'icon-ok icon-white',
              //'right'       => TRUE,
              'class'     => 'btn-primary',
              'value'     => 'assoc_add'];

            $modals .= generate_form_modal($form);
            unset($form, $form_params);
            /* End Add association */

        }

        ?>

    </div>

    <div class="col-md-4">
    <?php

    $box_args = [
        'title'         => 'Contacts',
        'header-border' => TRUE,
    ];

    if ($_SESSION['userlevel'] >= 9) {
        $box_args['header-controls'] = [ 'controls' => [
                'all'   => [ 'text' => 'Add All',
                             'icon' => $config['icon']['plus'],
                             'url'  => '#modal-contacts_add_all',
                             'data' => 'data-toggle="modal"'
                ],
                'clear' => [ 'text' => 'Remove All',
                             'icon' => $config['icon']['cancel'],
                             'url'  => '#modal-contacts_delete_all',
                             'data' => 'data-toggle="modal"' ] ]
        ];

        /* Begin to add all contacts */

        $form = [
            'type'      => 'horizontal',
            'userlevel' => 9,          // Minimum user level for display form
            'id'        => 'modal-contacts_add_all',
            'title'     => 'Add All unassociated contacts to Checker',
            //'modal_args' => $modal_args,
            //'help'     => 'This will delete the selected association rule.',
            //'class'      => '', // Clean default box class!
            //'url'       => ''
        ];
        //$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
        //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

        $form['row'][0]['alert_test_id'] = [
          'type'     => 'hidden',
          'fieldset' => 'body',
          'value'    => $check['alert_test_id']];
        $form['row'][0]['contact_id']    = [
          'type'     => 'hidden',
          'fieldset' => 'body',
          'value'    => 'all'];
        $form['row'][0]['action']        = [
          'type'     => 'hidden',
          'fieldset' => 'body',
          'value'    => 'contact_alert_checker_addall'
        ];

        $form['row'][6]['confirm_add_all'] = [
          'type'        => 'checkbox',
          'fieldset'    => 'body',
          'name'        => 'Confirm',
          //'offset'      => FALSE,
          'placeholder' => 'Yes, please add all contacts to this checker!',
          'onchange'    => "javascript: toggleAttrib('disabled', 'contact_alert_checker_addall');",
          'value'       => 'confirm'];

        $form['row'][99]['close']                        = [
          'type'      => 'submit',
          'fieldset'  => 'footer',
          'div_class' => '', // Clean default form-action class!
          'name'      => 'Close',
          'icon'      => '',
          'attribs'   => ['data-dismiss' => 'modal',  // dismiss modal
                          'aria-hidden'  => 'true']
        ]; // do not sent any value
        $form['row'][99]['contact_alert_checker_addall'] = [
          'type'      => 'submit',
          'fieldset'  => 'footer',
          'div_class' => '', // Clean default form-action class!
          'name'      => 'Add Contacts Association',
          'icon'      => 'icon-ok icon-white',
          //'right'       => TRUE,
          'class'     => 'btn-primary',
          'disabled'  => TRUE,
          'value'     => 'contact_alert_checker_addall'
        ];

        $modals .= generate_form_modal($form);
        unset($form);
        /* End add all contacts */

        /* Begin delete all contacts */

        $form = ['type'      => 'horizontal',
                 'userlevel' => 9,          // Minimum user level for display form
                 'id'        => 'modal-contacts_delete_all',
                 'title'     => 'Delete All associated contacts from Checker',
                 //'modal_args' => $modal_args,
                 //'help'     => 'This will delete the selected association rule.',
                 //'class'      => '', // Clean default box class!
                 //'url'       => ''
        ];
        //$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
        //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

        $form['row'][0]['alert_test_id'] = [
          'type'     => 'hidden',
          'fieldset' => 'body',
          'value'    => $check['alert_test_id']];
        $form['row'][0]['contact_id']    = [
          'type'     => 'hidden',
          'fieldset' => 'body',
          'value'    => 'all'];
        $form['row'][0]['action']        = [
          'type'     => 'hidden',
          'fieldset' => 'body',
          'value'    => 'contact_alert_checker_deleteall'
        ];

        $form['row'][6]['confirm_delete_all'] = [
          'type'        => 'checkbox',
          'fieldset'    => 'body',
          'name'        => 'Confirm',
          //'offset'      => FALSE,
          'placeholder' => 'Yes, please delete all contacts from this checker!',
          'onchange'    => "javascript: toggleAttrib('disabled', 'contact_alert_checker_deleteall');",
          'value'       => 'confirm'
        ];

        $form['row'][99]['close']                           = [
          'type'      => 'submit',
          'fieldset'  => 'footer',
          'div_class' => '', // Clean default form-action class!
          'name'      => 'Close',
          'icon'      => '',
          'attribs'   => ['data-dismiss' => 'modal',  // dismiss modal
                          'aria-hidden'  => 'true']
        ]; // do not sent any value
        $form['row'][99]['contact_alert_checker_deleteall'] = [
          'type'      => 'submit',
          'fieldset'  => 'footer',
          'div_class' => '', // Clean default form-action class!
          'name'      => 'Delete Contacts Association',
          'icon'      => 'icon-trash icon-white',
          //'right'       => TRUE,
          'class'     => 'btn-danger',
          'disabled'  => TRUE,
          'value'     => 'contact_alert_checker_deleteall'
        ];

        $modals .= generate_form_modal($form);
        unset($form);
        /* End delete all contacts */
    }

    echo generate_box_open($box_args);

    echo '<table class="' . OBS_CLASS_TABLE_STRIPED . '">';

    if (count($contacts)) {

        echo '
  <thead><tr>
    <th style="width: 25%;">Transport</th>
    <th style="">Contact Description</th>
    <th style="width: 30px;"></th>
  </tr></thead>
  <tbody>';

        foreach ($contacts as $contact) {

            $c_exist[$contact['contact_id']] = TRUE;

            $form = [
                'type'      => 'simple',
                'userlevel' => 9,          // Minimum user level for display form
                'id'        => 'contact_alert_checker_delete',
                //'title'      => 'Delete Alert Contact Association',
            ];
            $form['row'][0]['contact_id'] = [
                'type'  => 'hidden',
                'value' => $contact['contact_id']
            ];
            $form['row'][0]['confirm_' . $contact['contact_id']] = [
              'type'  => 'hidden',
              'value' => 1
            ];
            $form['row'][99]['action'] = [
                //$form['row'][99]['submit'] = array(
                'type'      => 'submit',
                //'icon_only' => TRUE, // hide button styles
                //'fieldset'    => 'footer',
                //'div_class'   => '', // Clean default form-action class!
                'name'      => '',
                'icon'      => 'icon-trash',
                //'right'       => TRUE,
                'class'     => 'btn-xs btn-danger',
                // confirmation dialog
                'attribs'   => [ 'data-toggle'            => 'confirmation', // Enable confirmation dialog
                                 'data-confirm-placement' => 'left',
                                 'data-confirm-content'   => 'Delete contact \'' . escape_html($contact['contact_descr']) . '\' association?' ],
                'value'     => 'contact_alert_checker_delete'
            ];

            echo '
    <tr>
      <td><span class="label">' . $contact['contact_method'] . '</span></td>
      <td>' . escape_html($contact['contact_descr']) . '</td>
      <td>' . generate_form($form) . '</td>
    </tr>';
            unset($form);

        }

        echo '</tbody>';

    } else {

        echo '<tr class="info">
                <td style="padding:5px; text-align: center; color: #555;"><i><small>This alert check is not assigned to any contacts</i></td>
          </tr>';

    }
    echo '</table>';

    $all_contacts = dbFetchRows('SELECT * FROM `alert_contacts` WHERE `contact_disabled` = 0 ORDER BY `contact_method`, `contact_descr`');

    if (safe_count($all_contacts)) {
        $form_items = [];
        foreach ($all_contacts as $contact) {
            if (!isset($c_exist[$contact['contact_id']])) {
                $form_items['contacts'][$contact['contact_id']] = [
                  'name'    => $contact['contact_descr'],
                  'subtext' => $contact['contact_method']
                ];
            }
        }

        $form = [
            'type'      => 'simple',
            'userlevel' => 9,          // Minimum user level for display form
            'style'     => 'margin:5px;',
            'right'     => TRUE,
        ];
        $form['row'][0]['type']       = [
          'type'  => 'hidden',
          'value' => 'alert_checker'];
        $form['row'][0]['contact_id'] = [
          'type'        => 'multiselect',
          'live-search' => FALSE,
          'width'       => '220px',
          'readonly'    => $readonly,
          'values'      => $form_items['contacts']];

        $form['row'][99]['action'] = [
            //$form['row'][99]['submit'] = array(
            'type'     => 'submit',
            //'icon_only'   => TRUE, // hide button styles
            'name'     => 'Associate',
            'icon'     => 'icon-plus-sign',
            'readonly' => $readonly,
            //'right'       => TRUE,
            'class'    => 'btn-primary',
            'value'    => 'contact_alert_checker_add'
        ];

        $box_close['footer_content']   = generate_form($form);
        $box_close['footer_nopadding'] = TRUE;
        unset($form, $form_items);
    } else {
        // print_warning('No unassociated alert checkers.');
    }

    echo generate_box_close($box_close);

    echo '
  </div>

</div>';

} elseif ($vars['view'] === 'entries') {
    echo '
<div class="row" style="margin-top: 10px;">
  <div class="col-md-12">';

    if ($vars['view'] === 'alert_log') {
        register_html_title('Alert Logs');
        print_alert_log($vars);
    } else {
        register_html_title('Alert Entries');
        $vars['pagination'] = TRUE;
        print_alert_table($vars);
    }

    echo '
  </div>
</div>';

}

echo $modals;

// EOF
