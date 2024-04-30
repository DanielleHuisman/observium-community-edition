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

// These functions are used to generate our boxes. It's probably easier to put this into functions.

function generate_box_open($args = [])
{
    // r($args);
    if (!is_array($args)) {
        $args = [$args];
    }

    $return = '<div ';
    if (isset($args['id'])) {
        $return .= 'id="' . $args['id'] . '" ';
    }

    $return .= 'class="' . OBS_CLASS_BOX . ($args['box-class'] ? ' ' . $args['box-class'] : '') . '" ' .
               ($args['box-style'] ? 'style="' . $args['box-style'] . '"' : '') . '>' . PHP_EOL;

    if (isset($args['title'])) {
        $return .= '  <div class="box-header' . ($args['header-border'] ? ' with-border' : '') . '">' . PHP_EOL;
        if (isset($args['url'])) {
            $return .= '<a href="' . $args['url'] . '">';
        }
        if (isset($args['icon'])) {
            $return .= get_icon($args['icon']);
        }
        $return .= '<' . ($args['title-element'] ?? 'h3') . ' class="box-title"';
        $return .= isset($args['title-style']) ? ' style="' . $args['title-style'] . '"' : '';
        $return .= '>';
        $return .= escape_html($args['title']) . '</' . ($args['title-element'] ?? 'h3') . '>' . PHP_EOL;
        if (isset($args['url'])) {
            $return .= '</a>';
        }

        if (isset($args['header-controls']) && is_array($args['header-controls']['controls'])) {
            $return .= '    <div class="box-tools pull-right">';

            foreach ($args['header-controls']['controls'] as $control) {
                $anchor = (isset($control['anchor']) && $control['anchor']) || !empty($control['config']);
                if ($anchor) {
                    $return .= ' <a role="button"';
                } else {
                    $return .= '<button type="button"';
                }
                if (!empty($control['url']) && $control['url'] !== '#') {
                    $return .= ' href="' . $control['url'] . '"';
                } elseif (!empty($control['config'])) {
                    // Check/get a config option
                    $return .= ' href="#"'; // set config url
                    if (empty($control['data']) && isset($control['value'])) {
                        $control['data'] = ['onclick' => "ajax_settings('" . $control['config'] . "', '" . $control['value'] . "');"];
                    }
                } else {
                    //$return .= ' onclick="return false;"';
                }

                // Additional class
                $return .= ' class="btn btn-box-tool';
                if (isset($control['class'])) {
                    $return .= ' ' . escape_html($control['class']);
                }
                $return .= '"';

                // Additional params (raw string or array with params
                if (isset($control['data'])) {
                    $params = is_array($control['data']) ? generate_html_attribs($control['data']) : $control['data'];
                    $return .= ' ' . $params;
                }
                $return .= '>';

                if (isset($control['icon'])) {
                    $return .= get_icon($control['icon']) . ' ';
                }
                if (isset($control['text'])) {
                    $return .= $control['text'];
                }

                if ($anchor) {
                    $return .= '</a>';
                } else {
                    $return .= '</button>';
                }
            }

            $return .= '    </div>';
        }
        $return .= '  </div>' . PHP_EOL;
    }

    $return .= '  <div class="box-body' . ($args['padding'] ? '' : ' no-padding') . '"';
    if (isset($args['body-style'])) {
        $return .= ' style="' . $args['body-style'] . '"';
    }
    $return .= '>' . PHP_EOL;

    return $return;
}

function generate_box_close($args = [])
{
    $return = '  </div>' . PHP_EOL;

    if (isset($args['footer_content'])) {
        $return .= '  <div class="box-footer';
        if (isset($args['footer_nopadding'])) {
            $return .= ' no-padding';
        }
        $return .= '">';
        $return .= $args['footer_content'];
        $return .= '  </div>' . PHP_EOL;
    }

    $return .= '</div>' . PHP_EOL;
    return $return;
}

// DOCME needs phpdoc block
function print_graph_row_port($graph_array, $port) {

    $graph_array['to'] = get_time();
    $graph_array['id'] = $port['port_id'];

    print_graph_row($graph_array);
}

function generate_graph_summary_row($graph_summary_array, $state_marker = FALSE)
{
    global $config;

    $graph_array = $graph_summary_array;

    unset($graph_array['types']);

    if ($_SESSION['widescreen']) {
        if ($config['graphs']['size'] === 'big') {
            if (!$graph_array['height']) {
                $graph_array['height'] = "110";
            }
            if (!$graph_array['width']) {
                $graph_array['width'] = "372";
            }
            $limit = 4;
        } else {
            if (!$graph_array['height']) {
                $graph_array['height'] = "110";
            }
            if (!$graph_array['width']) {
                $graph_array['width'] = "287";
            }
            $limit = 5;
        }
    } else {
        if ($config['graphs']['size'] === 'big') {
            if (!$graph_array['height']) {
                $graph_array['height'] = "100";
            }
            if (!$graph_array['width']) {
                $graph_array['width'] = "323";
            }
            $limit = 3;
        } else {
            if (!$graph_array['height']) {
                $graph_array['height'] = "100";
            }
            if (!$graph_array['width']) {
                $graph_array['width'] = "228";
            }
            $limit = 4;
        }
    }

    if (!isset($graph_summary_array['period'])) {
        $graph_summary_array['period'] = "day";
    }
    if ($state_marker) {
        $graph_array['width'] -= 2;
    }
    $graph_array['to']   = get_time();
    $graph_array['from'] = get_time($graph_summary_array['period']);

    $graph_rows = [];
    foreach ($graph_summary_array['types'] as $graph_type) {

// FIX THIS LATER :DDDD
//    $hide_lg = $period[0] === '!';
//    if ($hide_lg)
//    {
//      $period = substr($period, 1);
//    }


        $graph_array['type'] = $graph_type;

        preg_match(OBS_PATTERN_GRAPH_TYPE, $graph_type, $type_parts);
        $descr = $config['graph_types'][$type_parts['type']][$type_parts['subtype']]['descr'] ?? nicecase($graph_type);

        $graph_array_zoom           = $graph_array;
        $graph_array_zoom['height'] = "175";
        $graph_array_zoom['width']  = "600";
        unset($graph_array_zoom['legend']);

        $link_array         = $graph_array;
        $link_array['page'] = "graphs";
        unset($link_array['height'], $link_array['width']);
        unset($link_array['legend']); // Remove the legend=no when we kick out to /graphs/ because it doesn't make sense there.
        $link = generate_url($link_array);

        $popup_contents = '<h3>' . $descr . '</h3>';
        $popup_contents .= generate_graph_tag($graph_array_zoom);

        $graph_link = overlib_link($link, generate_graph_tag($graph_array), $popup_contents, NULL);

//    if ($hide_lg)
//    {
        // Hide this graph on lg/xl screen since it not fit to single row (on xs always visible, since here multirow)
//      $graph_link = '<div class="visible-xs-inline visible-lg-inline visible-xl-inline">' . $graph_link . '</div>';
//    }
        if (count($graph_rows) < $limit) {
            $graph_rows[] = $graph_link;
        }

    }

    return implode(PHP_EOL, $graph_rows);
}


// DOCME needs phpdoc block
function generate_graph_row($graph_array, $state_marker = FALSE)
{
    global $config;

    if ($_SESSION['widescreen']) {
        if ($config['graphs']['size'] === 'big') {
            if (!$graph_array['height']) {
                $graph_array['height'] = "110";
            }
            if (!$graph_array['width']) {
                $graph_array['width'] = "372";
            }
            $periods = ['day', 'week', 'month', 'year'];
        } else {
            if (!$graph_array['height']) {
                $graph_array['height'] = "110";
            }
            if (!$graph_array['width']) {
                $graph_array['width'] = "287";
            }
            $periods = ['day', 'week', 'month', 'year', 'twoyear'];
        }
    } else {
        if ($config['graphs']['size'] === 'big') {
            if (!$graph_array['height']) {
                $graph_array['height'] = "100";
            }
            if (!$graph_array['width']) {
                $graph_array['width'] = "330";
            }
            $periods = ['day', 'week', '!month'];
        } else {
            if (!$graph_array['height']) {
                $graph_array['height'] = "100";
            }
            if (!$graph_array['width']) {
                $graph_array['width'] = "228";
            }
            $periods = ['day', 'week', 'month', '!year'];
        }
    }

    if ($graph_array['shrink']) {
        $graph_array['width'] -= $graph_array['shrink'];
    }

    // If we're printing the row inside a table cell with "state-marker", we need to make the graphs a tiny bit smaller to fit
    if ($state_marker) {
        $graph_array['width'] -= 2;
    }

    $graph_array['to'] = get_time();

    $graph_rows = [];
    foreach ($periods as $period) {
        $hide_lg = $period[0] === '!';
        if ($hide_lg) {
            $period = substr($period, 1);
        }
        $graph_array['from']        = get_time($period);
        $graph_array_zoom           = $graph_array;
        $graph_array_zoom['height'] = "175";
        $graph_array_zoom['width']  = "600";
        unset($graph_array_zoom['legend']);

        $link_array         = $graph_array;
        $link_array['page'] = "graphs";
        unset($link_array['height'], $link_array['width']);
        $link = generate_url($link_array);

        $graph_link = overlib_link($link, generate_graph_tag($graph_array), generate_graph_tag($graph_array_zoom), NULL);
        if ($hide_lg) {
            // Hide this graph on lg/xl screen since it not fit to single row (on xs always visible, since here multirow)
            $graph_link = '<div class="visible-xs-inline visible-lg-inline visible-xl-inline">' . $graph_link . '</div>';
        }
        $graph_rows[] = $graph_link;
    }

    return implode(PHP_EOL, $graph_rows);
}

function print_graph_row($graph_array, $state_marker = FALSE)
{
    echo(generate_graph_row($graph_array, $state_marker));
}

function print_graph_summary_row($graph_array, $state_marker = FALSE)
{
    echo(generate_graph_summary_row($graph_array, $state_marker));
}

function print_setting_row($varname, $variable)
{
    global $config, $config_variable, $database_config, $defined_config, $default_config;

    // required arrays:
    // config definitions
    # $config_variable; // include($config['install_dir'] . '/includes/config-variables.inc.php');
    if (safe_empty($config_variable)) {
        //print_warning("LOADED CONFIG DEF");
        include($config['install_dir'] . '/includes/config-variables.inc.php');
        //r($config_variable);
    }
    // SQL config
    # $database_config; // load_sqlconfig($database_config);
    // Defined config
    # $defined_config;  // $defined_config = get_defined_settings();
    // Default config
    # $default_config;  // $default_config = get_default_settings();

    if (isset($variable['edition']) && $variable['edition'] !== OBSERVIUM_EDITION) {
        // Skip variables not allowed for current Observium edition
        return;
    }

    $switchdescr = <<<SCRIPT
  function switchDesc(form_id) {
    var selected = $('#'+form_id+' option:selected').val();
    //console.log(selected);
    $('[id^="param_'+form_id+'_"]').each( function( index, element ) {
      if ($(this).prop('id') === 'param_'+form_id+'_'+selected) {
        $(this).css('display', '');
      } else {
        $(this).css('display', 'none');
      }
      //console.log( $(this).prop('id') );
    });
  }
SCRIPT;

    //$content = NULL;
    $linetype = '';
    // Check if this variable is set in SQL
    if (sql_to_array($varname, $database_config) !== FALSE) {
        $sqlset   = 1;
        $linetype = 'info';
        //$content = sql_to_array($varname, $database_config, FALSE);
    } else {
        $sqlset = 0;
        //$linetype = "disabled";
    }

    // Check if this variable is set in the config. If so, lock it
    $locked = sql_to_array($varname, $defined_config) !== FALSE;
    $locked = $locked || (isset($variable['locked']) && $variable['locked']); // Locked in definition
    if ($locked) {
        $linetype = 'error';
        //$content  = sql_to_array($varname, $defined_config, FALSE);
    }

    $htmlname = str_replace('|', '__', $varname); // JQuery et al don't like the pipes a lot, replace once here in temporary variable
    $confname = '$config[\'' . implode("']['", explode('|', $varname)) . '\']';

    echo('  <tr class="' . $linetype . ' vertical-align">' . PHP_EOL);
    echo('    <td class="state-marker"></td>');
    //echo('    <td style="width: 0px;"></td>');
    echo('    <td style="width: 40%; padding-left: 15px;"><strong style="color: #0a5f7f; font-size: 1.6rem">' . $variable['name'] . '</strong>');
    echo('<br /><small>' . escape_html($variable['shortdesc']) . '</small>' . PHP_EOL);
    echo('      </td>' . PHP_EOL);
    echo('      <td class="text-nowrap" style="width: 50px">' . PHP_EOL);
    echo('<div class="pull-right">');
    if ($locked && !isset($variable['locked'])) {
        echo(generate_tooltip_link(NULL, get_icon($config['icon']['lock']), 'This setting is locked because it has been set in your <strong>config.php</strong> file.'));
        echo '&nbsp;';
    }
    echo(generate_tooltip_link(NULL, get_icon($config['icon']['question'], '', [ 'id' => 'clipboard', 'data-clipboard-text' => $confname ]), 'Variable name to use in <strong>config.php</strong>: ' . $confname . '<br /><em>Click the mouse button to copy config variable to the clipboard.</em>'));
    echo('      </div>' . PHP_EOL);
    echo('      </td>' . PHP_EOL);
    echo('      <td>' . PHP_EOL);

    // Split enum|foo|bar into enum  foo|bar
    [ $vartype, $varparams ] = explode('|', $variable['type'], 2);
    $params = [];

    // If a callback function is defined, use this to fill params.
    if ($variable['params_call'] && function_exists($variable['params_call'])) {
        $params = call_user_func($variable['params_call']);
        // Else if the params are defined directly, use these.
    } elseif (is_array($variable['params'])) {
        $params = $variable['params'];
    } elseif (!safe_empty($varparams)) {
        // Else use parameters specified in variable type (e.g. enum|1|2|5|10)
        foreach (explode('|', $varparams) as $param) {
            $params[$param] = ['name' => nicecase($param)];
        }
    }

    if ($sqlset) {
        // Get DB config (can be global or user)
        $content = sql_to_array($varname, $database_config, FALSE);
        //r($content);
    } elseif (sql_to_array($varname, $config) === FALSE) {
        // Variable is not configured, set $content to its default value so the form is pre-filled
        $content = sql_to_array($varname, $default_config, FALSE);
    } else {
        $content = sql_to_array($varname, $config, FALSE); // Get current value
    }
    //r($varname); r($content); r($sqlset); r($locked);

    $readonly  = !($sqlset || $locked);
    $target_id = $htmlname; // default target_id for form manipulations

    echo('      <div id="' . $htmlname . '_content_div">' . PHP_EOL);

    switch ($vartype) {
        case 'bool':
        case 'boolean':
            echo('      <div>' . PHP_EOL);
            $item = ['id'       => $htmlname,
                     'size'     => 'small',
                     //'on-text'  => 'True',
                     //'off-text' => 'False',
                     'readonly' => $readonly,
                     'disabled' => (bool)$locked,
                     'value'    => $content];
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
                $values = [];
                foreach ($content as $value) {
                    $values[] = call_user_func($variable['value_call'], $value);
                }
                $content = $values;
                unset($values);
            }
        //r($content);

        case 'enum':
            foreach ($params as $param => $entry) {
                if (isset($entry['subtext'])) {
                    continue;
                }
                if (isset($entry['allowed'])) {
                    $params[$param]['subtext'] = "Allowed to use " . $config_variable[$entry['allowed']]['name'];
                } elseif (isset($entry['required'])) {
                    $params[$param]['subtext'] = '<strong>REQUIRED to use ' . $config_variable[$entry['required']]['name'] . '</strong>';
                }
            }
            //r($params);
            $item = ['id'       => $htmlname,
                     'title'    => 'Any', // only for enum-array
                     'width'    => '150px',
                     'readonly' => $readonly,
                     'disabled' => (bool)$locked,
                     'onchange' => 'switchDesc(\'' . $htmlname . '\')',
                     'values'   => $params,
                     'value'    => $content];
            echo(generate_form_element($item, ($vartype !== 'enum-array' ? 'select' : 'multiselect')));
            foreach ($params as $param => $entry) {
                if (isset($entry['desc'])) {
                    echo('      <div id="param_' . $htmlname . '_' . $param . '" style="' . ($content != $param ? ' display: none;' : '') . '">' . PHP_EOL);
                    echo('        ' . $entry['desc'] . PHP_EOL);
                    echo('      </div>' . PHP_EOL);
                }
            }

            register_html_resource('script', $switchdescr);
            break;
        case 'enum-key-value':
            //r($content);
            $target_id = $htmlname . '[';

            //$locked = FALSE; $readonly = TRUE; /// DEBUG

            // Init clone row
            echo('<div id="' . $htmlname . '_clone" style="margin: -5px 0 -5px 0;">  <!-- START clone -->' . PHP_EOL);
            // Here parse stored json/array
            if (!safe_count($content)) {
                // Create an empty array for initial row
                $content = ['' => ''];
            }
            $i = 0;
            foreach ($content as $key => $value) {
                echo('<div id="' . $htmlname . '_clone_row" class="control-group text-nowrap" style="margin: 10px 0 10px 0;">' . PHP_EOL);
                $item = [
                  'id'          => "{$htmlname}[key][]",
                  'name'        => 'Key',
                  //'width'    => '500px',
                  'class'       => 'input-large',
                  'type'        => 'text',
                  'readonly'    => $readonly,
                  'disabled'    => (bool)$locked,
                  'placeholder' => TRUE,
                  'value'       => $key
                ];
                if (isset($params['key'])) {
                    $item = array_merge($item, (array)$params['key']);
                }
                echo(generate_form_element($item));
                $item = [
                  'id'          => "{$htmlname}[value][]",
                  'name'        => 'Value',
                  //'width'    => '500px',
                  'class'       => 'input-xlarge',
                  'type'        => 'text',
                  'readonly'    => $readonly,
                  'disabled'    => (bool)$locked,
                  'placeholder' => TRUE,
                  'value'       => $value
                ];
                if (isset($params['value'])) {
                    $item = array_merge($item, (array)$params['value']);
                }
                echo(generate_form_element($item));
                //echo('<button type="button" id="'.$htmlname.'_button" class="btn btn-primary"> Add</button>');
                $item = [
                  'id'       => $htmlname . '[add]',
                  'name'     => 'Add',
                  'type'     => 'button',
                  'readonly' => $readonly,
                  'disabled' => (bool)$locked,
                  'class'    => 'btn-primary',
                  'icon'     => ''
                ];
                echo(generate_form_element($item));
                echo('</div>' . PHP_EOL);
                $i++;
            }
            unset($i);

            // Last row with buttons
            // echo('<div id="'.$htmlname.'_button_row">'.PHP_EOL);
            // echo('<button type="button" id="'.$htmlname.'_button" class="btn btn-primary">Create New Copy</button>');
            // echo('</div>'.PHP_EOL);
            echo('</div> <!-- END clone -->' . PHP_EOL);
            // Register clone js, see options:
            // https://metallurgical.github.io/jquery-metal-clone/
            register_html_resource('js', 'jquery.metalClone.js'); // jquery.metalClone.min.js
            register_html_resource('css', 'metalClone.css');
            $clone_target = "{$htmlname}_clone_row";
            $clone_button = "{$htmlname}[add]";
            $clone_remove = "{$htmlname}[remove]";
            $remove_text  = ''; //'Remove';
            if ($readonly || (bool)$locked) {
                $clone_disabled = 'disabled: \'1\',';
            } else {
                $clone_disabled = '';
            }
            //$clone_disabled = ($readonly || (bool)$locked) ? '1' : 0;
            //r($clone_disabled);
            $icon_add       = 'icon-plus';
            $icon_remove    = 'icon-trash';
            $clone_defaults = 'copyValue: false, enableScrollTop: false, enableConfirmMessage: false, ' .
                              'btnRemoveText: \'' . $remove_text . '\', btnRemoveClass: \'btn btn-danger\', btnRemoveId: \'' . $clone_remove . '\',' .
                              'fontAwesomeTheme: \'basic\', fontAwesomeAddClass: \'' . $icon_add . '\', fontAwesomeRemoveClass: \'' . $icon_remove . '\', ' .
                              // Append remove buttons on start: https://github.com/metallurgical/jquery-metal-clone/issues/11
                              "
  onStart: function(element) {
    //console.log(element);
    var regex = /(metalElement\d{0,})/g;
    var eclass = element.attr('class');
    var others = \$('[id={$clone_target}]').not(element);
    $.each(others, function () {
      \$(this).addClass(eclass);
      
      // Add button icon
      //console.log(\$(this).find('#{$clone_button}'));
      \$(this).find('[id=\"{$clone_button}\"]').prepend(' ').prepend(\$('<i/>', { class: '{$icon_add}' }));
      
      // Remove button
      \$(this).append(\$('<button/>', {
        id: '{$clone_remove}',
        type: 'button',
        {$clone_disabled}
        class: eclass.match(regex) + 'BtnRemove metal-btn-remove btn btn-danger',
        text: ' {$remove_text}',
        'data-metal-ref': '.' + element.attr('class').match(regex)
      }));
      
      // Remove button icon
      \$(this).find('.metal-btn-remove').prepend(\$('<i/>', { class: '{$icon_remove}' }));
    });
  }";

            register_html_resource('script', '$(\'#' . $clone_target . '\').metalClone({ btnClone: \'[id="' . $clone_button . '"]\', ' . $clone_defaults . ' });');
            break;

        case 'enum-freeinput':
            $item = ['id'          => $htmlname,
                     'type'        => 'tags',
                     //'title'    => 'Any',
                     'width'       => '150px',
                     'readonly'    => $readonly,
                     'disabled'    => (bool)$locked,
                     'onchange'    => 'switchDesc(\'' . $htmlname . '\')',
                     'placeholder' => $variable['example'] ? 'Example: ' . $variable['example'] : FALSE,
                     'values'      => $params,
                     'value'       => $content];
            echo(generate_form_element($item));

            register_html_resource('script', $switchdescr);
            break;
        case 'password':
        case 'string':
        default:
            $item = ['id'          => $htmlname,
                     //'width'    => '500px',
                     'class'       => 'input-xlarge',
                     'type'        => 'text',
                     'readonly'    => $readonly,
                     'disabled'    => (bool)$locked,
                     'placeholder' => TRUE,
                     'value'       => $content];
            if ($vartype === 'password') {
                $item['type']          = 'password';
                $item['show_password'] = 1;
            } else {
                $item['placeholder'] = $variable['example'] ? 'Example: ' . $variable['example'] : escape_html($content);
            }
            echo(generate_form_element($item));
            //echo('         <input name="' . $htmlname . '" style="width: 500px" type="text" ' . ($locked ? 'disabled="1" ' : '') . 'value="' . escape_html($content) . '" />' . PHP_EOL);
            break;
    }

    echo('        <input type="hidden" name="varset_' . $htmlname . '" value="' . $sqlset . '"/>' . PHP_EOL);
    echo('      </div>' . PHP_EOL);
    echo('    </td>' . PHP_EOL);
    echo('    <td>' . PHP_EOL);
    echo('      <div class="pull-right">' . PHP_EOL);
    $item = ['id'          => $htmlname . '_custom',
             //'size'     => 'small',
             //'width'    => 100,
             //'on-color' => 'primary',
             //'off-color' => $offtype,
             //'on-text'  => 'Custom',
             //'off-text' => $offtext,
             'size'        => 'large',
             'view'        => $locked ? 'lock' : 'square', // note this is data-tt-type, but 'type' key reserved for element type
             'onchange'    => "toggleAttrib('readonly', obj.attr('data-onchange-id'));",
             'onchange-id' => $target_id, // target id for onchange, set attrib: data-onchange-id
             //'onchange' => "toggleAttrib('readonly', '" . $htmlname . "')",
             //'onchange' => '$(\'#' . $htmlname . '_content_div\').toggle()',
             'disabled'    => (bool)$locked,
             'value'       => $sqlset && !$locked];
    echo(generate_form_element($item, 'toggle'));

    //$onchange = "toggleAttrib('readonly', '" . $htmlname . "')";


    // echo '<input type="checkbox" data-off-label="false" data-on-label="false" data-off-icon-cls="glyphicon-thumbs-down" data-on-icon-cls="glyphicon-thumbs-up" data-group-cls="btn-group-sm" data-on="primary" data-off="' . $offtype . '" data-on-label="Custom" data-off-label="' . $offtext . '" onchange="'.$onchange.'" type="checkbox" ' . ($sqlset && !$locked ? 'checked="1" ' : '') . 'name="' . $htmlname . '_custom"' . ($locked ? ' disabled="1"' : '') . '>';

    //echo '<input type="checkbox" class="tiny-toggle" data-id="' . $htmlname . '" data-tt-type="'.($locked ? 'lock' : 'square').'" data-tt-size="large" onchange="'.$onchange.'" ' . ($sqlset && !$locked ? 'checked="1" ' : '') . 'name="' . $htmlname . '_custom"' . ($locked ? ' disabled="1"' : '') . '>';


    echo '      </div>' . PHP_EOL;
    echo '    </td>' . PHP_EOL;
    //echo '    <td></td>' . PHP_EOL;
    echo '  </tr>' . PHP_EOL;

}

// EOF
