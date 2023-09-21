<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

?>
  <div class="row">
    <div class="col-md-12">

      <?php

      $form_items['devices'] = generate_form_values('device');

      $form                        = ['type'          => 'rows',
                                      'space'         => '5px',
                                      'submit_by_key' => TRUE,
                                      'url'           => 'search/search=mac/'];
      $form['row'][0]['device_id'] = [
        'type'   => 'multiselect',
        'name'   => 'Device',
        'width'  => '100%',
        'value'  => $vars['device_id'],
        'groups' => ['', 'UP', 'DOWN', 'DISABLED'], // This is optgroup order for values (if required)
        'values' => $form_items['devices']];

      $form['row'][0]['interface'] = [
        'type'   => 'select',
        'name'   => 'Interfaces',
        'width'  => '100%',
        'value'  => $vars['interface'],
        'values' => ['' => 'All Interfaces', 'Loopback' => 'Loopbacks', 'Vlan' => 'Vlans']];

      $form['row'][0]['address'] = [
        'type'          => 'text',
        'name'          => 'MAC Address',
        'width'         => '100%',
        'grid'          => 4,
        'placeholder'   => TRUE,
        'submit_by_key' => TRUE,
        'value'         => escape_html($vars['address'])];
      // search button
      $form['row'][0]['search'] = [
        'type'  => 'submit',
        'grid'  => 4,
        //'name'        => 'Search',
        //'icon'        => 'icon-search',
        'value' => 'mac',
        'right' => TRUE];

      print_form($form);
      unset($form, $form_items);

      // Pagination
      $vars['pagination'] = TRUE;

      // Print MAC addresses
      print_mac_addresses($vars);

      register_html_title('MAC addresses');

      ?>

    </div> <!-- col-md-12 -->

  </div> <!-- row -->

<?php

// EOF
