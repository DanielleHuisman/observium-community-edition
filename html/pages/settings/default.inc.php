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

if ($_SESSION['userlevel'] < 10) {
  print_error_permission();
  return;
}

register_html_resource('js', 'clipboard.min.js');
register_html_resource('script', 'new Clipboard("#clipboard");');

// Load SQL config into $database_config
load_sqlconfig($database_config);

// cache default and config.php-defined values
$defined_config = get_defined_settings();
$default_config = get_default_settings();

echo '<form id="settings" name="settings" method="post" action="" class="form form-inline">' . PHP_EOL;

//echo '<div class="box box-solid" style="padding:10px;">';

// Pretty inefficient looping everything if section != all, but meh
// This is only done on this page, so there is no performance issue for the rest of Observium
foreach ($config_subsections as $section => $subdata) {
  if (isset($config_sections[$section]['edition']) && $config_sections[$section]['edition'] != OBSERVIUM_EDITION) {
    // Skip sections not allowed for a current Observium edition
    continue;
  }

  echo('  <div class="row"> <div class="col-md-12"> <!-- BEGIN SECTION ' . $section . ' -->' . PHP_EOL);
  if ($vars['section'] === 'all' || $vars['section'] === $section) {
    // When printing all, also print the section name
    $title = $vars['section'] === 'all' ? $config_sections[$section]['text'] . ' :: ' : '';

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
  }
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

// EOF

