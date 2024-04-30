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

include($config['html_dir'] . "/includes/alerting-navbar.inc.php");

// Page to display list of configured alert checks

$alert_check = cache_alert_rules($vars);
#$alert_assoc = cache_alert_assoc($vars);
$where = ' WHERE 1' . generate_query_permitted(['alert']);

// Build header menu

foreach (dbFetchRows("SELECT * FROM `alert_assoc` WHERE 1") as $entry) {
    $alert_assoc[$entry['alert_test_id']][$entry['alert_assoc_id']]['entity_type']    = $entry['entity_type'];
    $alert_assoc[$entry['alert_test_id']][$entry['alert_assoc_id']]['entity_attribs'] = safe_json_decode($entry['entity_attribs']);
    $alert_assoc[$entry['alert_test_id']][$entry['alert_assoc_id']]['device_attribs'] = safe_json_decode($entry['device_attribs']);
}

$navbar['class'] = "navbar-narrow";
$navbar['brand'] = "Alert Checks";

$types       = dbFetchRows("SELECT DISTINCT `entity_type` FROM `alert_table`" . $where);
$types_count = count($types);

$navbar['options']['all']['url']  = generate_url($vars, ['page' => 'alert_checks', 'entity_type' => NULL]);
$navbar['options']['all']['text'] = escape_html(nicecase('all'));
if (!isset($vars['entity_type'])) {
    $navbar['options']['all']['class'] = "active";
    $navbar['options']['all']['url']   = generate_url($vars, ['page' => 'alert_checks', 'entity_type' => NULL]);
}

foreach ($types as $thing) {
    if ($vars['entity_type'] == $thing['entity_type']) {
        $navbar['options'][$thing['entity_type']]['class'] = "active";
        $navbar['options'][$thing['entity_type']]['url']   = generate_url($vars, ['page' => 'alert_checks', 'entity_type' => NULL]);
    } else {
        if ($types_count > 6) {
            $navbar['options'][$thing['entity_type']]['class'] = "icon";
        }
        $navbar['options'][$thing['entity_type']]['url'] = generate_url($vars, ['page' => 'alert_checks', 'entity_type' => $thing['entity_type']]);
    }
    $navbar['options'][$thing['entity_type']]['icon'] = $config['entities'][$thing['entity_type']]['icon'];
    $navbar['options'][$thing['entity_type']]['text'] = escape_html(nicecase($thing['entity_type']));
}

$navbar['options']['export']['text']      = 'Export';
$navbar['options']['export']['icon']      = $config['icon']['export'];
$navbar['options']['export']['url']       = generate_url($vars, ['page' => 'alert_checks', 'export' => 'yes']);
$navbar['options']['export']['right']     = TRUE;
$navbar['options']['export']['userlevel'] = 7;

// Print out the navbar defined above
print_navbar($navbar);

// Generate contacts cache array for use in table
$contacts    = [];
$sql         = "SELECT * FROM `alert_contacts_assoc` LEFT JOIN `alert_contacts` ON `alert_contacts`.`contact_id` = `alert_contacts_assoc`.`contact_id`";
$contacts_db = dbFetchRows($sql, $params);
foreach ($contacts_db as $db_contact) {
    $contacts[$db_contact['alert_checker_id']][] = $db_contact;
}

if (get_var_true($vars['export'])) {
    // Export requested
    if (!get_var_true($vars['saveas'])) {
        $export_filename = 'observium_alerts_';
        $export_filename .= $vars['entity_type'] ?: 'all';
        $export_filename .= '.json';

        $form = ['type'  => 'rows',
                 'space' => '10px',
                 'url'   => generate_url($vars)];
        // Filename
        $form['row'][0]['filename'] = [
          'type'        => 'text',
          'name'        => 'Filename',
          'value'       => $export_filename,
          'grid_xs'     => 8,
          'width'       => '100%',
          'placeholder' => TRUE
        ];
        // Save as human formatted XML
        $form['row'][0]['formatted'] = [
          'type'    => 'select',
          'grid_xs' => 4,
          'value'   => 'yes',
          'values'  => [ 'yes' => 'Formatted',
                         'no'  => 'Unformatted' ]
        ];
        // search button
        $form['row'][0]['saveas'] = [
          'type'  => 'submit',
          'name'  => 'Save as..',
          'icon'  => 'icon-save',
          'right' => TRUE,
          'value' => 'yes'
        ];
        print_form($form);
    }

    json_export('alerts', $vars);

    unset($export_filename, $form);
}

foreach (dbFetchRows("SELECT * FROM `alert_table`" . $where) as $entry) {
    $alert_table[$entry['alert_test_id']][$entry['alert_table_id']] = $entry;
}

echo generate_box_open();

echo '<table class="table table-striped table-hover">
  <thead>
    <tr>
    <th class="state-marker"></th>
    <th style="width: 1px;"></th>
    <th style="width: 250px">Name</th>
    <th style="width: 40px"></th>
    <th style="width: 300px">Tests</th>
    <th>Device Match / Entity Match</th>
    <th style="width: 40px">Entities</th>
    </tr>
  </thead>
  <tbody>', PHP_EOL;

// FIXME -- make sort order configurable

$alert_check = array_sort($alert_check, 'alert_name');

foreach ($alert_check as $check) {

    // Process the alert checker to add classes and colours and count status.
    humanize_alert_check($check);

    echo('<tr class="' . $check['html_row_class'] . '">');

    echo('
    <td class="state-marker"></td>
    <td style="width: 1px;"></td>');

    // Print the conditions applied by this alert

    echo '<td><strong>';
    echo '<a href="', generate_url(['page' => 'alert_check', 'alert_test_id' => $check['alert_test_id']]), '">' . escape_html($check['alert_name']) . '</a></strong><br />';
    echo '<small>', escape_html($check['alert_message']), '</small>';
    echo '</td>';

    echo '<td><i class="' . $config['entities'][$check['entity_type']]['icon'] . '"></i></td>';

    // Loop the tests used by this alert
    echo '<td>';
    $text_block = [];
    //r($check);
    foreach ($check['conditions'] as $condition) {
        $text_block[] = escape_html($condition['metric'] . ' ' . $condition['condition'] . ' ' . $condition['value']);
    }
    echo ($check['and'] ? '<i class="sprite-logic-and"></i> <span class="label label-primary">AND (ALL)</span>' : '<i class="sprite-logic-and"></i> <span class="label label-success">OR (ANY)</span>') . '<br />';
    echo '<table class="' . OBS_CLASS_TABLE_STRIPED_MORE . '">';

    $allowed_metrics = array_keys($config['entities'][$check['entity_type']]['metrics']);

    // FIXME. Currently hardcoded in check_entity(), need rewrite to timeranges.
    $allowed_metrics[] = 'time';
    $allowed_metrics[] = 'weekday';

    foreach ($check['conditions'] as $condition) {
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
    echo('</td>');

    echo('<td>');

    if (!is_null($check['alert_assoc'])) {

        $check['assoc'] = safe_json_decode($check['alert_assoc']);
        echo render_qb_rules($check['entity_type'], $check['assoc']);

    } else {

        echo generate_box_open();
        echo('<table class="table table-condensed-more table-striped" style="margin-bottom: 0px;">');

        // Loop the associations which link this alert to this device
        foreach ($alert_assoc[$check['alert_test_id']] as $assoc_id => $assoc) {

            echo('<tr>');
            echo('<td style="width: 50%">');
            if (is_array($assoc['device_attribs'])) {
                $text_block = [];
                foreach ($assoc['device_attribs'] as $attribute) {
                    $text_block[] = escape_html($attribute['attrib'] . ' ' . $attribute['condition'] . ' ' . $attribute['value']);
                }
                echo('<code>' . implode('<br />', $text_block) . '</code>');
            } else {
                echo '<code>*</code>';
            }

            echo('</td>');

            echo('<td>');
            if (is_array($assoc['entity_attribs'])) {
                $text_block = [];
                foreach ($assoc['entity_attribs'] as $attribute) {
                    $text_block[] = escape_html($attribute['attrib'] . ' ' . $attribute['condition'] . ' ' . $attribute['value']);
                }
                echo('<code>' . implode('<br />', $text_block) . '</code>');
            } else {
                echo '<code>*</code>';
            }
            echo('</td>');

            echo('</tr>');

        }
        // End loop of associations

        echo '</table>';
        echo generate_box_close();
    }
    echo '</td>';

    // Print the count of entities this alert applies to and a popup containing a list and Print breakdown of entities by status.
    // We assume each row here is going to be two lines, so we just <br /> them.
    echo '<td style="text-align: right;">';
    #echo overlib_link('#', count($entities), $entities_content,  NULL));
    echo '<span class="label label-primary">', $check['num_entities'], '</span>&nbsp;';
    //echo '<br />';
    echo $check['status_numbers'];

    echo '<br />';

    if ($notifiers_count = safe_count($contacts[$check['alert_test_id']])) {
        $content = "";
        foreach ($contacts[$check['alert_test_id']] as $contact) {
            $content .= '<span class="label">' . $contact['contact_method'] . '</span> ' . escape_html($contact['contact_descr']) . '<br />';
        }
        echo generate_tooltip_link('', '<span class="label label-success">' . $notifiers_count . ' Notifiers</span>', $content);
    } else {
        echo '<span class="label label-primary">Default Notifier</span>';
    }

    echo '</td>';

}

echo '</table>';

echo generate_box_close();

register_html_title('Alert checkers');

// EOF
