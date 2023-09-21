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

?>
    <div class="row">
        <div class="col-md-12">

            <?php

            foreach (dbFetchColumn('SELECT DISTINCT `ipv6_type` FROM `ipv6_addresses` WHERE `device_id` = ?', [ $device['device_id'] ]) as $type) {
                $form_items['types'][$type] = [ 'name' => $config['ip_types'][$type]['name'], 'subtext' => $config['ip_types'][$type]['subtext'] ];
            }

            $form = [
              'type'          => 'rows',
              'space'         => '5px',
              'submit_by_key' => TRUE
            ];

            $form['row'][0]['interface'] = [
              'type'   => 'select',
              'name'   => 'Interface',
              'width'  => '100%',
              'grid'   => 3,
              'value'  => $vars['interface'],
              'values' => [ '' => 'All Interfaces', 'Lo' => 'Loopbacks', 'Vlan' => 'Vlans' ]
            ];
            $form['row'][0]['type'] = [
              'type'   => 'multiselect',
              'name'   => 'IP Type',
              'width'  => '100%',
              'grid'   => 2,
              'value'  => $vars['type'],
              'values' => $form_items['types']
            ];
            $form['row'][0]['network'] = [
              'type'        => 'text',
              'name'        => 'IP Network',
              'width'       => '100%',
              'grid'        => 3,
              'placeholder' => TRUE,
              'ajax'        => TRUE,
              'ajax_vars'   => [ 'field' => 'ipv6_network' ],
              'value'       => $vars['network']
            ];
            $form['row'][0]['address'] = [
              'type'        => 'text',
              'name'        => 'IP Address',
              'width'       => '100%',
              'grid'        => 3,
              'placeholder' => TRUE,
              'value'       => $vars['address']
            ];
            // search button
            $form['row'][0]['search'] = [
              'type'  => 'submit',
              'grid'  => 1,
              //'name'        => 'Search',
              //'icon'        => 'icon-search',
              'value' => 'ipv6',
              'right' => TRUE
            ];

            print_form($form);
            unset($form, $form_items);

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
