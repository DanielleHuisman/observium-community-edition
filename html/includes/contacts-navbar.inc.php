<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

if (!is_array($alert_rules)) { $alert_rules = cache_alert_rules(); }

$navbar['class'] = 'navbar-narrow';
$navbar['brand'] = 'Contacts';

$pages = array('contacts' => 'Contact List');

foreach ($pages as $page_name => $page_desc)
{
    if ($vars['page'] == $page_name)
    {
        $navbar['options'][$page_name]['class'] = "active";
    }

    $navbar['options'][$page_name]['url'] = generate_url(array('page' => $page_name));
    $navbar['options'][$page_name]['text'] = escape_html($page_desc);
}

$navbar['options_right']['add']['url']       = '#modal-add_contact';
$navbar['options_right']['add']['link_opts'] = 'data-toggle="modal"';
$navbar['options_right']['add']['text']      = 'Add Contact';
$navbar['options_right']['add']['icon']      = $config['icon']['contact-add'];
$navbar['options_right']['add']['userlevel'] = 10;

// Print out the navbar defined above
print_navbar($navbar);
unset($navbar);

    /* Begin Add contact */

    /*
    $modal_args = array(
      'id'    => 'modal-add_contact',
      'title' => 'Add New Contact',
      'icon'  => 'oicon-sql-join-inner',
      //'hide'  => TRUE,
      //'fade'  => TRUE,
      //'role'  => 'dialog',
      //'class' => 'modal-md',
    );
    */

    $form = array('type'       => 'horizontal',
                  'userlevel'  => 10,          // Minimum user level for display form
                  'id'         => 'modal-add_contact',
                  'title'      => 'Add New Contact',
                  'icon'       => 'oicon-sql-join-inner',
                  //'modal_args' => $modal_args, // !!! This generate modal specific form
                  //'class'      => '',          // Clean default box class!
                  'url'        => 'contacts/'
                  );
    //$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
    //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

    $row = 0;
    $form_params = array();
    $form['row'][++$row]['contact_method'] = array(
                                      'type'        => 'select',
                                      'fieldset'    => 'body',
                                      'name'        => 'Transport',
                                      'width'       => '270px',
                                      'live-search' => FALSE,
                                      //'values'      => $form_params['method'],
                                      'value'       => 'email');
    $row_tmp = $row; // Store row number
    foreach (array_keys($config['transports']) as $transport)
    {
      $form_params['method'][$transport] = $config['transports'][$transport]['name'];

      $docs_link = OBSERVIUM_URL . '/docs/alerting_transports/#' . str_replace(' ', '-', strtolower($config['transports'][$transport]['name']));
      $form['row'][++$row]['contact_' . $transport . '_doc'] = array(
                                      'type'        => 'html',
                                      'fieldset'    => 'body',
                                      'offset'      => TRUE,
                                      'html'        => '<a id="contact_' . $transport . '_doc" href="' . $docs_link . '" target="_blank">See documentation for this Transport (new page)</a>');
    }
    asort($form_params['method']);
    $form['row'][$row_tmp]['contact_method']['values'] = $form_params['method'];

    $form['row'][++$row]['contact_descr'] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'body',
                                      'name'        => 'Description',
                                      'class'       => 'input-xlarge',
                                      'value'       => '');

    foreach ($config['transports'] as $transport => $data)
    {
      $row++;
      if (count($data['parameters']['required']) || count($data['parameters']['global']))
      {
        $form['row'][$row]['contact_' . $transport . '_required'] = array(
                                      'type'        => 'html',
                                      'fieldset'    => 'body',
                                      'html'        => '<h3 id="contact_' . $transport . '_required">Required parameters</h3>');
        $row++;

        if (!count($data['parameters']['global'])) { $data['parameters']['global'] = array(); } // Temporary until we separate "global" out.
        // Plan: add defaults for transport types to global settings, which we use by default, then be able to override the settings via this GUI
        // This needs supporting code in the transport to check for set variable and if not, use the global default

        foreach (array_merge($data['parameters']['required'], $data['parameters']['global']) as $parameter => $param_data) // Temporary merge req & global
        {
          $form['row'][$row]['contact_' . $transport . '_' . $parameter] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'body',
                                      'name'        => $param_data['description'],
                                      'class'       => 'input-xlarge',
                                      'value'       => isset($param_data['default']) ? $param_data['default'] : '');
          if (isset($param_data['tooltip']))
          {
            //r($param_data);
            $form['row'][$row]['contact_' . $transport . '_tooltip'] = array(
                                      'type'        => 'html',
                                      'fieldset'    => 'body',
                                      'html'        => generate_tooltip_link(NULL, '&nbsp;<i class="'.$config['icon']['question'].'"></i>', $param_data['tooltip']));
          }

          $row++;
        }
      }

      if (count($data['parameters']['optional']))
      {
        $form['row'][$row]['contact_' . $transport . '_optional'] = array(
                                      'type'        => 'html',
                                      'fieldset'    => 'body',
                                      'html'        => '<h3 id="contact_' . $transport . '_optional">Optional parameters</h3>');
        $row++;

        foreach ($data['parameters']['optional'] as $parameter => $param_data)
        {
          $form['row'][$row]['contact_' . $transport . '_' . $parameter] = array(
                                      'type'        => 'text',
                                      'fieldset'    => 'body',
                                      'name'        => $param_data['description'],
                                      'class'       => 'input-xlarge',
                                      'value'       => isset($param_data['default']) ? $param_data['default'] : '');

          if (isset($param_data['tooltip']))
          {
            $form['row'][$row]['contact_' . $transport . '_tooltip'] = array(
                                      'type'        => 'html',
                                      'fieldset'    => 'body',
                                      'html'        => generate_tooltip_link(NULL, '&nbsp;<i class="'.$config['icon']['question'].'"></i>', $param_data['tooltip']));
          }
          $row++;
        }
      }

    }

    $form['row'][$row]['close'] = array(
                                    'type'        => 'submit',
                                    'fieldset'    => 'footer',
                                    'div_class'   => '', // Clean default form-action class!
                                    'name'        => 'Close',
                                    'icon'        => '',
                                    'attribs'     => array('data-dismiss' => 'modal',
                                                           'aria-hidden'  => 'true'));
    $form['row'][$row]['action'] = array(
                                    'type'        => 'submit',
                                    'fieldset'    => 'footer',
                                    'div_class'   => '', // Clean default form-action class!
                                    'name'        => 'Add Contact',
                                    'icon'        => 'icon-ok icon-white',
                                    //'right'       => TRUE,
                                    'class'       => 'btn-primary',
                                    'value'       => 'add_contact');

    echo generate_form_modal($form);
    unset($form, $form_params);

    $script = '
$("#contact_method").change(function() {
  var select = this.value;
';

  // Generate javascript function which hides all configuration part panels except the ones for the currently chosen transport
  // Alternative would be to hide them all, then unhide the one selected. Hmm...
  $count = 0;
  foreach ($config['transports'] as $transport => $description)
  {
    if ($count == 0)
    {
      $script .= "  if (select === '" . $transport . "') {" . PHP_EOL;
    } else {
      $script .= "  } else if (select === '" . $transport . "') {" . PHP_EOL;
    }
    $script .= "    \$('[id^=\"contact_${transport}\"]').show();" . PHP_EOL;
    foreach ($config['transports'] as $ltransport => $ldescription)
    {
      if ($transport != $ltransport)
      {
        $script .= "    \$('[id^=\"contact_${ltransport}\"]').hide();" . PHP_EOL;
      }
    }

    $count++;
  }
  $script .= '  }
}).change();';

  register_html_resource('script', $script);

  // End add contact

// EOF
