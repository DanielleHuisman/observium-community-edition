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

            // Select the devices only with ARP/NDP tables
            $form_devices          = dbFetchColumn('SELECT DISTINCT `device_id` FROM `ip_mac` LEFT JOIN `ports` USING(`port_id`)');
            $form_items['devices'] = generate_form_values('device', $form_devices);

            $form                        = ['type'          => 'rows',
                                            'space'         => '5px',
                                            'submit_by_key' => TRUE,
                                            'url'           => 'search/search=arp/'];
            $form['row'][0]['device_id'] = [
              'type'   => 'multiselect',
              'name'   => 'Device',
              'width'  => '100%',
              'value'  => $vars['device_id'],
              'groups' => ['', 'UP', 'DOWN', 'DISABLED'], // This is optgroup order for values (if required)
              'values' => $form_items['devices']];

            $form['row'][0]['ip_version'] = [
              'type'   => 'select',
              'name'   => 'IP',
              'width'  => '100%',
              'value'  => $vars['ip_version'],
              'values' => ['' => 'IPv4 & IPv6', '4' => 'IPv4 only', '6' => 'IPv6 only']];
            $form['row'][0]['searchby']   = [
              'type'     => 'select',
              'name'     => 'Search By',
              'width'    => '100%',
              'onchange' => "$('#address').prop('placeholder', $('#searchby option:selected').text())",
              'value'    => $vars['searchby'],
              'values'   => ['mac' => 'MAC Address', 'ip' => 'IP Address']];
            $form['row'][0]['address']    = [
              'type'          => 'text',
              'name'          => ($vars['searchby'] == 'ip' ? 'IP Address' : 'MAC Address'),
              'width'         => '100%',
              'grid'          => 3,
              'placeholder'   => TRUE,
              'submit_by_key' => TRUE,
              'value'         => escape_html($vars['address'])];
            // search button
            $form['row'][0]['search'] = [
              'type'  => 'submit',
              'grid'  => 3,
              //'name'        => 'Search',
              //'icon'        => 'icon-search',
              'value' => 'arp',
              'right' => TRUE];

            print_form($form);
            unset($form, $form_items, $form_devices);

            // Pagination
            $vars['pagination'] = TRUE;

            print_arptable($vars);

            register_html_title('ARP/NDP Search');

            ?>

        </div> <!-- col-md-12 -->
    </div> <!-- row -->

<?php

// EOF
