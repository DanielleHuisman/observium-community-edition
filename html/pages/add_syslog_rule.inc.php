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

// Global write permissions required.
if ($_SESSION['userlevel'] < 9) {
    print_error_permission();
    return;
}

include($config['html_dir'] . "/includes/alerting-navbar.inc.php");

if (isset($vars['submit']) && $vars['submit'] === "add_alertlog_rule") {
    $message = '<h4>Adding alert checker</h4> ';

    $ok = TRUE;

    foreach (['name', 'descr', 'regex'] as $var) {
        $value = trim($vars[$var]);
        if (!isset($vars[$var]) || safe_empty($value)) {
            $ok = FALSE;
        } elseif ($var === 'regex' && preg_match($value, NULL) === FALSE) {
            $ok = FALSE;
        } // Check if valid regex
        $vars[$var] = $value;
    }

    if ($ok) {
        $rule                = [];
        $rule['la_name']     = $vars['name'];
        $rule['la_descr']    = $vars['descr'];
        $rule['la_rule']     = $vars['regex'];
        $rule['la_severity'] = '8';
        $rule['la_disable']  = '0';

        $rule_id = dbInsert('syslog_rules', $rule);

        if (is_numeric($rule_id)) {
            print_success('<p>Syslog rule inserted as <a href="' . generate_url(['page' => 'syslog_rules', 'la_id' => $rule_id]) . '">' . $rule_id . '</a></p>');

            unset($vars['name'], $vars['descr'], $vars['regex']);

            set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script

        } else {
            print_error('Failed to create new rule.');
        }
    } else {
        print_error('<b>Failed to create new rule</b>: Rule name, message and valid regular expression are mandatory.');
    }
}

?>

    <div class="row">
        <div class="col-md-8">

            <?php

            $form = ['type'  => 'horizontal',
                     'id'    => 'logalert_rule',
                     'title' => 'New Syslog Rule Details',
                     //'url'       => generate_url(array('page' => 'add_alertlog_rule')),
            ];

            $form['row'][1]['name']   = [
              'type'        => 'text',
              'name'        => 'Rule Name',
              'placeholder' => TRUE,
              //'class'       => 'input-xlarge',
              'width'       => '250px',
              //'readonly'    => $readonly,
              'value'       => $vars['name']];
            $form['row'][2]['descr']  = [
              'type'        => 'textarea',
              'name'        => 'Message',
              'placeholder' => TRUE,
              'class'       => 'col-md-11 col-xs-11',
              //'width'       => '250px',
              'rows'        => 4,
              //'readonly'    => $readonly,
              'value'       => $vars['descr']];
            $form['row'][3]['regex']  = [
              'type'        => 'textarea',
              'name'        => 'Regular Expression',
              'placeholder' => TRUE,
              'class'       => 'col-md-11 col-xs-11',
              //'width'       => '250px',
              'rows'        => 4,
              //'readonly'    => $readonly,
              'value'       => $vars['regex']];
            $form['row'][7]['submit'] = [
              'type'  => 'submit',
              'name'  => 'Add Rule',
              'icon'  => 'icon-plus icon-white',
              //'right'       => TRUE,
              'class' => 'btn-success',
              //'readonly'    => $readonly,
              'value' => 'add_alertlog_rule'];

            print_form($form);
            unset($form);

            ?>

        </div>
        <div class="col-md-4">

            <?php

            $box_args = ['title'         => 'Syslog Regular Expressions',
                         'header-border' => TRUE,
                         'padding'       => TRUE,
            ];

            echo generate_box_open($box_args);

            echo <<<SYSLOG_RULES
    <p><strong>Syslog Rules</strong> are built using standard PCRE regular expressions.</p>
    <p>There are many online resources to help you learn and test regular expressions.
       Good resources include <a href="https://regex101.com/">regex101.com</a>,
       <a href="https://www.debuggex.com/cheatsheet/regex/pcre">Debuggex Cheatsheet</a>,
       <a href="http://regexr.com/">regexr.com</a> and <a href="http://www.tutorialspoint.com/php/php_regular_expression.htm">Tutorials Point</a>.
       There are many other sites with examples which can be found online.
    <p>A simple rule to match the word "error" could look like:</p>
    <code>/error/</code>
    <p>A more complex rule to match SSH authentication failures from PAM for the users root or adama might look like:</p>
    <code>/pam.+\(sshd:auth\).+failure.+user\=(root|adama)/</code>
SYSLOG_RULES;

            echo generate_box_close();

            ?>

        </div>
    </div>

<?php

//EOF
