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

            $form_devices          = dbFetchColumn('SELECT DISTINCT `device_id` FROM `ipv6_addresses`;');
            $form_items['devices'] = generate_form_values('device', $form_devices);
            foreach (dbFetchColumn('SELECT DISTINCT `ipv6_type` FROM `ipv6_addresses`;') as $type) {
                $form_items['types'][$type] = ['name' => $config['ip_types'][$type]['name'], 'subtext' => $config['ip_types'][$type]['subtext']];
            }

            $form                        = ['type'          => 'rows',
                                            'space'         => '5px',
                                            'submit_by_key' => TRUE,
                                            'url'           => 'search/search=ipv6/'];
            $form['row'][0]['device']    = [
              'type'   => 'multiselect',
              'name'   => 'Device',
              'width'  => '100%',
              'value'  => $vars['device'],
              'groups' => ['', 'UP', 'DOWN', 'DISABLED'], // This is optgroup order for values (if required)
              'values' => $form_items['devices']];
            $form['row'][0]['interface'] = [
              'type'   => 'select',
              'name'   => 'Interface',
              'width'  => '100%',
              'value'  => $vars['interface'],
              'values' => ['' => 'All Interfaces', 'Lo' => 'Loopbacks', 'Vlan' => 'Vlans']];
            $form['row'][0]['type']      = [
              'type'   => 'multiselect',
              'name'   => 'IP Type',
              'width'  => '100%',
              'value'  => $vars['type'],
              'values' => $form_items['types']];
            $form['row'][0]['network']   = [
              'type'        => 'text',
              'name'        => 'IP Network',
              'width'       => '100%',
              'placeholder' => TRUE,
              'ajax'        => TRUE,
              'ajax_vars'   => ['field' => 'ipv6_network'],
              'value'       => $vars['network']];
            $form['row'][0]['address']   = [
              'type'        => 'text',
              'name'        => 'IP Address',
              'width'       => '100%',
              'grid'        => 3,
              //'div_class'   => 'col-lg-3 col-md-3 col-sm-3',
              'placeholder' => TRUE,
              'value'       => $vars['address']];
            // search button
            $form['row'][0]['search'] = [
              'type'  => 'submit',
              'grid'  => 1,
              //'div_class'   => 'col-lg-3 col-md-3 col-sm-3',
              //'name'        => 'Search',
              //'icon'        => 'icon-search',
              'value' => 'ipv6',
              'right' => TRUE];

            print_form($form);
            unset($form, $form_items, $form_devices);

            // Pagination
            $vars['pagination'] = TRUE;

            // Print addresses
            print_addresses($vars);

            register_html_title("IPv6 Addresses");

            ?>

        </div> <!-- col-md-12 -->

    </div> <!-- row -->

<?php

// EOF
