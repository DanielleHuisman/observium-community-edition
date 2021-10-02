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

if ($_SESSION['userlevel'] < 10)
{
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
  foreach ($config_subsections as $section => $subdata)
  {
    if (isset($config_sections[$section]['edition']) && $config_sections[$section]['edition'] != OBSERVIUM_EDITION)
    {
      // Skip sections not allowed for current Observium edition
      continue;
    }

    echo('  <div class="row"> <div class="col-md-12"> <!-- BEGIN SECTION '.$section.' -->' . PHP_EOL);
    if ($vars['section'] == 'all' || $vars['section'] == $section)
    {
      if ($vars['section'] == 'all')
      {
        // When printing all, also print the section name
        echo generate_box_open(array('title' => $config_sections[$section]['text'], 'header-border' => TRUE));
        echo generate_box_close();
      }

      foreach ($subdata as $subsection => $vardata)
      {

        //echo '<div class="box box-solid" style="padding:10px;">';
        //echo '<h2 style="padding: 0px 5px; color: #555;">'.$subsection.'</h2>';

        echo generate_box_open(array('title' => $subsection, 'header-border' => FALSE,
                                     'box-style' => 'margin-bottom: 30px; margin-top: 10px;',
                                     'title-style' => 'padding: 15px 10px; color: #555; font-size: 21px;',
                                     'title-element' => 'h2'));

        //echo generate_box_open(array('box-style' => 'margin-bottom: 30px; margin-top: 10px;'));

        echo '<table class="table table-striped table-cond">' . PHP_EOL;

        $cols = array(
          array(NULL, 'class="state-marker"'),
          //array(NULL, 'style="width: 0px;"'),
          array('Description', 'style="width: 40%;"'),
          array(NULL,          'style="width: 50px;"'),
          'Configuration Value',
          array(NULL,      'style="width: 75px;"'),
          //array(NULL,      'style="width: 10px;"'),
        );
        //echo(get_table_header($cols));

        foreach ($vardata as $varname => $variable) {
          if (isset($variable['edition']) && $variable['edition'] !== OBSERVIUM_EDITION) {
            // Skip variables not allowed for current Observium edition
            continue;
          }
          $content = NULL;
          $linetype = '';
          // Check if this variable is set in SQL
          if (sql_to_array($varname, $database_config) !== FALSE) {
            $sqlset = 1;
            $linetype = 'info';
            $content = sql_to_array($varname, $database_config, FALSE);
          } else {
            $sqlset = 0;
            //$linetype = "disabled";
          }

          // Check if this variable is set in the config. If so, lock it
          $locked = sql_to_array($varname, $defined_config) !== FALSE;
          $locked = $locked || (isset($variable['locked']) && $variable['locked']); // Locked in definition
          if ($locked) {
            $offtext  = "Locked";
            $offtype  = "danger";
            $linetype = 'error';
            $content  = sql_to_array($varname, $defined_config, FALSE);
          } else {
            $offtext  = "Default";
            $offtype  = "success";
          }

          $htmlname = str_replace('|','__',$varname); // JQuery et al don't like the pipes a lot, replace once here in temporary variable
          $confname = '$config[\'' . implode("']['",explode('|',$varname)) . '\']';

          echo('  <tr class="' . $linetype . ' vertical-align">' . PHP_EOL);
          echo('    <td class="state-marker"></td>');
          //echo('    <td style="width: 0px;"></td>');
          echo('    <td style="width: 40%; padding-left: 15px;"><strong style="color: #0a5f7f; font-size: 1.6rem">' . $variable['name'] . '</strong>');
          echo('<br /><small>' . escape_html($variable['shortdesc']) . '</small>' . PHP_EOL);
          echo('      </td>' . PHP_EOL);
          echo('      <td class="text-nowrap" style="width: 50px">' . PHP_EOL);
          echo('<div class="pull-right">');
          if ($locked && !isset($variable['locked']))
          {
            echo(generate_tooltip_link(NULL, '<i class="'.$config['icon']['lock'].'"></i>', 'This setting is locked because it has been set in your <strong>config.php</strong> file.'));
            echo '&nbsp;';
          }
          echo(generate_tooltip_link(NULL, '<i id="clipboard" class="'.$config['icon']['question'].'" data-clipboard-text="'.$confname.'"></i>', 'Variable name to use in <strong>config.php</strong>: ' . $confname));
          echo('      </div>' . PHP_EOL);
          echo('      </td>'. PHP_EOL);
          echo('      <td>' . PHP_EOL);

          // Split enum|foo|bar into enum  foo|bar
          list($vartype, $varparams) = explode('|', $variable['type'], 2);
          $params = array();

          // If a callback function is defined, use this to fill params.
          if ($variable['params_call'] && function_exists($variable['params_call'])) {
            $params = call_user_func($variable['params_call']);
          // Else if the params are defined directly, use these.
          } elseif (is_array($variable['params'])) {
            $params = $variable['params'];
          } elseif (!safe_empty($varparams)) {
            // Else use parameters specified in variable type (e.g. enum|1|2|5|10)
            foreach (explode('|', $varparams) as $param) {
              $params[$param] = array('name' => nicecase($param));
            }
          }

          if (sql_to_array($varname, $config) === FALSE) {
            // Variable is not configured, set $content to its default value so the form is pre-filled
            $content = sql_to_array($varname, $default_config, FALSE);
          } else {
            $content = sql_to_array($varname, $config, FALSE); // Get current value
          }
          //r($varname); r($content); r($sqlset); r($locked);

          $readonly = !($sqlset || $locked);
          $target_id = $htmlname; // default target_id for form manipulations

          echo('      <div id="' . $htmlname . '_content_div">' . PHP_EOL);

          switch ($vartype) {
            case 'bool':
            case 'boolean':
              echo('      <div>' . PHP_EOL);
              $item = array('id'       => $htmlname,
                            'size'     => 'small',
                            //'on-text'  => 'True',
                            //'off-text' => 'False',
                            'readonly' => $readonly,
                            'disabled' => (bool)$locked,
                            'value'    => $content);
              //echo(generate_form_element($item, 'switch'));
              $item['view'] = 'toggle';
              $item['size'] = 'huge';
              //$item['size'] = 'huge';
              echo(generate_form_element($item, 'toggle'));
              echo('      </div>' . PHP_EOL);
              break;
            case 'enum-array':
              //r($content);
              if ($variable['value_call'] && function_exists($variable['value_call'])) {
                $values = array();
                foreach ($content as $value) {
                  $values[] = call_user_func($variable['value_call'], $value);
                }
                $content = $values;
                unset($values);
              }
              //r($content);

            case 'enum':
              foreach ($params as $param => $entry) {
                if (isset($entry['subtext'])) { // continue
                } elseif (isset($entry['allowed'])) {
                  $params[$param]['subtext'] = "Allowed to use " . $config_variable[$entry['allowed']]['name'];
                } elseif (isset($entry['required'])) {
                  $params[$param]['subtext'] = '<strong>REQUIRED to use ' . $config_variable[$entry['required']]['name'] . '</strong>';
                }
              }
              //r($params);
              $item = array('id'       => $htmlname,
                            'title'    => 'Any', // only for enum-array
                            'width'    => '150px',
                            'readonly' => $readonly,
                            'disabled' => (bool)$locked,
                            'onchange' => 'switchDesc(\'' . $htmlname . '\')',
                            'values'   => $params,
                            'value'    => $content);
              echo(generate_form_element($item, ($vartype !== 'enum-array' ? 'select' : 'multiselect')));
              foreach ($params as $param => $entry) {
                if (isset($entry['desc'])) {
                  echo('      <div id="param_' . $htmlname . '_' .$param. '" style="' . ($content != $param ? ' display: none;' : '') . '">' . PHP_EOL);
                  echo('        ' . $entry['desc'] . PHP_EOL);
                  echo('      </div>' . PHP_EOL);
                }
              }
              break;
            case 'enum-key-value':
              //r($content);
              $target_id = $htmlname . '[';

              //$locked = FALSE; $readonly = TRUE; /// DEBUG

              // Init clone row
              echo('<div id="'.$htmlname.'_clone" style="margin: -5px 0 -5px 0;">  <!-- START clone -->'.PHP_EOL);
              // Here parse stored json/array
              if (!safe_count($content)) {
                // Create empty array for initial row
                $content = [ '' => '' ];
              }
              $i = 0;
              foreach ($content as $key => $value) {
                echo('<div id="'.$htmlname.'_clone_row" class="control-group text-nowrap" style="margin: 10px 0 10px 0;">'.PHP_EOL);
                $item = [
                  'id'       => "${htmlname}[key][]",
                  'name'     => 'Key',
                  //'width'    => '500px',
                  'class'    => 'input-large',
                  'type'     => 'text',
                  'readonly' => $readonly,
                  'disabled' => (bool)$locked,
                  'placeholder' => TRUE,
                  'value'    => $key
                ];
                if (isset($params['key'])) {
                  $item = array_merge($item, (array)$params['key']);
                }
                echo(generate_form_element($item));
                $item = [
                  'id'       => "${htmlname}[value][]",
                  'name'     => 'Value',
                  //'width'    => '500px',
                  'class'    => 'input-xlarge',
                  'type'     => 'text',
                  'readonly' => $readonly,
                  'disabled' => (bool)$locked,
                  'placeholder' => TRUE,
                  'value'    => $value
                ];
                if (isset($params['value'])) {
                  $item = array_merge($item, (array)$params['value']);
                }
                echo(generate_form_element($item));
                //echo('<button type="button" id="'.$htmlname.'_button" class="btn btn-primary"> Add</button>');
                $item = [
                  'id'          => $htmlname.'[add]',
                  'name'        => 'Add',
                  'type'        => 'button',
                  'readonly'    => $readonly,
                  'disabled'    => (bool)$locked,
                  'class'       => 'btn-primary',
                  'icon'        => ''
                ];
                echo(generate_form_element($item));
                echo('</div>'.PHP_EOL);
                $i++;
              }
              unset($i);

              // Last row with buttons
              // echo('<div id="'.$htmlname.'_button_row">'.PHP_EOL);
              // echo('<button type="button" id="'.$htmlname.'_button" class="btn btn-primary">Create New Copy</button>');
              // echo('</div>'.PHP_EOL);
              echo('</div> <!-- END clone -->'.PHP_EOL);
              // Register clone js, see options:
              // https://metallurgical.github.io/jquery-metal-clone/
              register_html_resource('js', 'jquery.metalClone.js'); // jquery.metalClone.min.js
              register_html_resource('css', 'metalClone.css');
              $clone_target = "${htmlname}_clone_row";
              $clone_button = "${htmlname}[add]";
              $clone_remove = "${htmlname}[remove]";
              $remove_text = ''; //'Remove';
              if ($readonly || (bool)$locked) {
                $clone_disabled = 'disabled: \'1\',';
              } else {
                $clone_disabled = '';
              }
              //$clone_disabled = ($readonly || (bool)$locked) ? '1' : 0;
              //r($clone_disabled);
              $icon_add = 'icon-plus';
              $icon_remove = 'icon-trash';
              $clone_defaults = 'copyValue: false, enableScrollTop: false, enableConfirmMessage: false, ' .
                                'btnRemoveText: \''.$remove_text.'\', btnRemoveClass: \'btn btn-danger\', btnRemoveId: \''.$clone_remove.'\',' .
                                'fontAwesomeTheme: \'basic\', fontAwesomeAddClass: \''.$icon_add.'\', fontAwesomeRemoveClass: \''.$icon_remove.'\', ' .
                                // Append remove buttons on start: https://github.com/metallurgical/jquery-metal-clone/issues/11
                                "
  onStart: function(element) {
    //console.log(element);
    var regex = /(metalElement\d{0,})/g;
    var eclass = element.attr('class');
    var others = \$('[id=${clone_target}]').not(element);
    $.each(others, function () {
      \$(this).addClass(eclass);
      
      // Add button icon
      //console.log(\$(this).find('#${clone_button}'));
      \$(this).find('[id=\"${clone_button}\"]').prepend(' ').prepend(\$('<i/>', { class: '${icon_add}' }));
      
      // Remove button
      \$(this).append(\$('<button/>', {
        id: '${clone_remove}',
        type: 'button',
        ${clone_disabled}
        class: eclass.match(regex) + 'BtnRemove metal-btn-remove btn btn-danger',
        text: ' ${remove_text}',
        'data-metal-ref': '.' + element.attr('class').match(regex)
      }));
      
      // Remove button icon
      \$(this).find('.metal-btn-remove').prepend(\$('<i/>', { class: '${icon_remove}' }));
    });
  }";

              register_html_resource('script', '$(\'#'.$clone_target.'\').metalClone({ btnClone: \'[id="'.$clone_button.'"]\', '.$clone_defaults.' });');
              break;

            case 'enum-freeinput':
              $item = array('id'       => $htmlname,
                            'type'     => 'tags',
                            //'title'    => 'Any',
                            'width'    => '150px',
                            'readonly' => $readonly,
                            'disabled' => (bool)$locked,
                            'onchange' => 'switchDesc(\'' . $htmlname . '\')',
                            'placeholder' => $variable['example'] ? 'Example: ' .$variable['example'] : FALSE,
                            'values'   => $params,
                            'value'    => $content);
              echo(generate_form_element($item));
              break;
            case 'password':
            case 'string':
            default:
              $item = array('id'       => $htmlname,
                            //'width'    => '500px',
                            'class'    => 'input-xlarge',
                            'type'     => 'text',
                            'readonly' => $readonly,
                            'disabled' => (bool)$locked,
                            'placeholder' => TRUE,
                            'value'    => $content);
              if ($vartype === 'password')
              {
                $item['type'] = 'password';
                $item['show_password'] = 1;
              } else {
                $item['placeholder'] = $variable['example'] ? 'Example: ' .$variable['example'] : escape_html($content);
              }
              echo(generate_form_element($item));
              //echo('         <input name="' . $htmlname . '" style="width: 500px" type="text" ' . ($locked ? 'disabled="1" ' : '') . 'value="' . escape_html($content) . '" />' . PHP_EOL);
              break;
          }

          echo('        <input type="hidden" name="varset_' . $htmlname . '" />' . PHP_EOL);
          echo('      </div>' . PHP_EOL);
          echo('    </td>' . PHP_EOL);
          echo('    <td>' . PHP_EOL);
          echo('      <div class="pull-right">' . PHP_EOL);
          $item = array('id'       => $htmlname . '_custom',
                        //'size'     => 'small',
                        //'width'    => 100,
                        //'on-color' => 'primary',
                        //'off-color' => $offtype,
                        //'on-text'  => 'Custom',
                        //'off-text' => $offtext,
                        'size'     => 'large',
                        'view'     => $locked ? 'lock' : 'square', // note this is data-tt-type, but 'type' key reserved for element type
                        'onchange' => "toggleAttrib('readonly', obj.attr('data-onchange-id'));",
                        'onchange-id' => $target_id, // target id for onchange, set attrib: data-onchange-id
                        //'onchange' => "toggleAttrib('readonly', '" . $htmlname . "')",
                        //'onchange' => '$(\'#' . $htmlname . '_content_div\').toggle()',
                        'disabled' => (bool)$locked,
                        'value'    => $sqlset && !$locked);
          echo(generate_form_element($item, 'toggle'));

          //$onchange = "toggleAttrib('readonly', '" . $htmlname . "')";


          // echo '<input type="checkbox" data-off-label="false" data-on-label="false" data-off-icon-cls="glyphicon-thumbs-down" data-on-icon-cls="glyphicon-thumbs-up" data-group-cls="btn-group-sm" data-on="primary" data-off="' . $offtype . '" data-on-label="Custom" data-off-label="' . $offtext . '" onchange="'.$onchange.'" type="checkbox" ' . ($sqlset && !$locked ? 'checked="1" ' : '') . 'name="' . $htmlname . '_custom"' . ($locked ? ' disabled="1"' : '') . '>';

          //echo '<input type="checkbox" class="tiny-toggle" data-id="' . $htmlname . '" data-tt-type="'.($locked ? 'lock' : 'square').'" data-tt-size="large" onchange="'.$onchange.'" ' . ($sqlset && !$locked ? 'checked="1" ' : '') . 'name="' . $htmlname . '_custom"' . ($locked ? ' disabled="1"' : '') . '>';


          echo '      </div>' . PHP_EOL;
          echo '    </td>' . PHP_EOL;
          //echo '    <td></td>' . PHP_EOL;
          echo '  </tr>' . PHP_EOL;
        }

        echo('  </table>' . PHP_EOL);
        echo generate_box_close();

      }
      //echo('  <br />' . PHP_EOL);
    }
    echo('  </div> </div> <!-- END SECTION '.$section.' -->' . PHP_EOL);
  }

?>
<div class="row">
  <div class="col-sm-12">

<!--    <div class="box box-solid">
      <div class="box-content no-padding">
        <div class="form-actions" style="margin: 0px;"> -->
  <?php

  // Add CSRF Token
  $item = array('type'  => 'hidden',
                'id'    => 'requesttoken',
                'value' => $_SESSION['requesttoken']);
  echo(generate_form_element($item) . PHP_EOL);

  $item = array('type'        => 'submit',
                'id'          => 'submit',
                'name'        => 'Save Changes',
                'class'       => 'btn-primary',
                'right'       => TRUE,
                'icon'        => 'icon-ok icon-white',
                'value'       => 'save');
  echo(generate_form_element($item) . PHP_EOL);
  ?>
<!--
        </div>
      </div>
    </div>
-->
  </div>
</div>

</form>

<script>
  function switchDesc(form_id) {
    var selected = $('#'+form_id+' option:selected').val();
    //console.log(selected);
    $('[id^="param_'+form_id+'_"]').each( function( index, element ) {
      if ($(this).prop('id') == 'param_'+form_id+'_'+selected) {
        $(this).css('display', '');
      } else {
        $(this).css('display', 'none');
      }
      //console.log( $(this).prop('id') );
    });
  }
</script>

<?php

// EOF

