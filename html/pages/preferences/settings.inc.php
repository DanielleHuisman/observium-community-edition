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

if (!is_intnum($_SESSION['user_id'])) {
  print_error('<h4>User unknown</h4>
Please correct the URL and try again.');
  return;
}

register_html_resource('js', 'js/jquery.serializejson.min.js');

register_html_resource('js', 'clipboard.min.js');
register_html_resource('script', 'new Clipboard("#clipboard");');

$user_id = $_SESSION['user_id'];
//$prefs = get_user_prefs($user_id);
//r($prefs);

// Load SQL config into $database_config
//load_sqlconfig($database_config);
load_user_config($database_config, $user_id);
//r($database_config);

// cache default and config.php-defined values
$defined_config = get_defined_settings();
$default_config = get_default_settings();

echo '<form id="edit-settings" name="edit-settings" method="post" action="" class="form form-inline">' . PHP_EOL;
echo '  <input type="hidden" name="action" value="settings_edit">' . PHP_EOL;
echo '  <input type="hidden" name="user_id" value="' . $user_id . '">' . PHP_EOL;

//echo '<div class="box box-solid" style="padding:10px;">';

// Pretty inefficient looping everything if section != all, but meh
// This is only done on this page, so there is no performance issue for the rest of Observium
include($config['install_dir'] . '/includes/config-variables.inc.php');
// Loop all variables and build an array with sections, subsections and variables
// This is only done on this page, so there is no performance issue for the rest of Observium
$config_subsections = [];
foreach ($config_variable as $varname => $variable) {
  if (isset($config_sections[$variable['section']]['edition']) &&
      $config_sections[$variable['section']]['edition'] !== OBSERVIUM_EDITION) {
    // Skip sections not allowed for current Observium edition
    //r($config_sections[$variable['section']]);
    continue;
  }
  if (isset($variable['edition']) && $variable['edition'] !== OBSERVIUM_EDITION) {
    // Skip variable not allowed for current Observium edition
    //r($varname);
    //r($variable);
    continue;
  }

  if (isset($variable['useredit']) && $variable['useredit']) {
    // List only user editable settings
    $config_subsections[$variable['section']][$variable['subsection']][$varname] = $variable;
  }
}
//r($config_subsections);

foreach ($config_subsections as $section => $subdata) {

  echo('  <div class="row"> <div class="col-md-12"> <!-- BEGIN SECTION ' . $section . ' -->' . PHP_EOL);
  //if ($vars['section'] === 'all' || $vars['section'] === $section) {
  // When printing all, also print the section name
  $title = $config_sections[$section]['text'] . ' :: ';

  foreach ($subdata as $subsection => $vardata) {

    //echo '<div class="box box-solid" style="padding:10px;">';
    //echo '<h2 style="padding: 0px 5px; color: #555;">'.$subsection.'</h2>';

    echo generate_box_open(['title'         => $title . $subsection, 'header-border' => FALSE,
                            'box-style'     => 'margin-bottom: 30px; margin-top: 10px;',
                            'title-style'   => 'padding: 15px 10px; color: #555; font-size: 21px;',
                            'title-element' => 'h2']);

    //echo generate_box_open(array('box-style' => 'margin-bottom: 30px; margin-top: 10px;'));

    echo '<table class="table table-striped table-cond">' . PHP_EOL;

    $cols = [
      [NULL, 'class="state-marker"'],
      //array(NULL, 'style="width: 0px;"'),
      ['Description', 'style="width: 40%;"'],
      [NULL, 'style="width: 50px;"'],
      'Configuration Value',
      [NULL, 'style="width: 75px;"'],
      //array(NULL,      'style="width: 10px;"'),
    ];
    //echo(get_table_header($cols));

    foreach ($vardata as $varname => $variable) {
      print_setting_row($varname, $variable);
    }

    echo('  </table>' . PHP_EOL);
    echo generate_box_close();

  }
  //echo('  <br />' . PHP_EOL);
  //}
  echo('  </div> </div> <!-- END SECTION ' . $section . ' -->' . PHP_EOL);
}

?>
  <div class="row">
    <div class="col-sm-12">

      <div class="box box-solid">
        <div class="box-content no-padding">
          <div class="form-actions" style="margin: 0px;">
            <?php

            // Add CSRF Token
            $item = ['type'  => 'hidden',
                     'id'    => 'requesttoken',
                     'value' => $_SESSION['requesttoken']];
            echo(generate_form_element($item) . PHP_EOL);

            $item = ['type'  => 'submit',
                     'id'    => 'submit',
                     'name'  => 'Save Changes',
                     'class' => 'btn-primary',
                     'right' => TRUE,
                     'icon'  => 'icon-ok icon-white',
                     'value' => 'save'];
            echo(generate_form_element($item) . PHP_EOL);

            ?>

          </div>
        </div>
      </div>

    </div>
  </div>

  </form>

<?php

register_html_resource('script', '$("#edit-settings").submit(processAjaxForm);');

// EOF
