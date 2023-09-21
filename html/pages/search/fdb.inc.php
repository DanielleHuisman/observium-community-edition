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

?>
    <div class="row">
        <div class="col-md-12">

            <?php

            $where = ' WHERE 1 ';
            $where .= generate_query_permitted(['device'], ['device_table' => 'F']);

            // Select devices and vlans only with FDB tables
            foreach (dbFetchRows('SELECT F.`device_id`, `vlan_vlan`, `vlan_name` FROM `vlans_fdb` AS F
                     LEFT JOIN `vlans` as V ON V.`vlan_vlan` = F.`vlan_id` AND V.`device_id` = F.`device_id`' .
                                 $where . 'GROUP BY `device_id`, `vlan_vlan`;') as $data) {
                $form_devices[] = $data['device_id'];
                if (is_numeric($data['vlan_vlan'])) {
                    $form_items['vlans'][$data['vlan_vlan']] = 'Vlan ' . $data['vlan_vlan'];
                }
                if (strlen($data['vlan_name'])) {
                    $form_items['vlan_name'][$data['vlan_name']] = $data['vlan_name'];
                }
            }
            if (is_array($form_items['vlans'])) {
                ksort($form_items['vlans']);
                natcasesort($form_items['vlan_name']);
            }

            // Select the devices with FDB tables
            //$form_devices = dbFetchColumn('SELECT DISTINCT `device_id` FROM `vlans_fdb`');
            $form_items['devices'] = generate_form_values('device', $form_devices);

            $form                        = [
              'type'          => 'rows',
              'space'         => '5px',
              'submit_by_key' => TRUE,
              'url'           => 'search/search=fdb/'
            ];
            $form['row'][0]['device_id'] = [
              'type'   => 'multiselect',
              'name'   => 'Device',
              'width'  => '100%',
              'value'  => $vars['device_id'],
              'groups' => ['', 'UP', 'DOWN', 'DISABLED'], // This is optgroup order for values (if required)
              'values' => $form_items['devices']
            ];

            $form['row'][0]['vlan_id']   = [
              'type'   => 'multiselect',
              'name'   => 'VLAN IDs',
              'width'  => '100%',
              'value'  => $vars['vlan_id'],
              'values' => $form_items['vlans']
            ];
            $form['row'][0]['vlan_name'] = [
              'type'   => 'multiselect',
              'name'   => 'VLAN Name',
              'width'  => '100%',
              'value'  => $vars['vlan_name'],
              'values' => $form_items['vlan_name']
            ];
            $form['row'][0]['address']   = [
              'type'          => 'text',
              'name'          => 'MAC Address',
              'width'         => '100%',
              'grid'          => 3,
              'placeholder'   => TRUE,
              'submit_by_key' => TRUE,
              'value'         => $vars['address']
            ];
            $form['row'][0]['deleted']   = [
              'type'     => 'switch-ng',
              //'on-text'       => 'Disabled',
              'on-color' => 'primary',
              'on-icon'  => 'icon-eye-close',
              //'off-text'      => 'Enabled',
              'off-icon' => 'icon-eye-open',
              'grid'     => 2,
              //'size'          => 'large',
              //'height'        => '15px',
              'title'    => 'Show Deleted',
              //'placeholder'   => 'Disabled',
              //'readonly'      => TRUE,
              //'disabled'      => TRUE,
              //'submit_by_key' => TRUE,
              'value'    => $vars['deleted']
            ];
            // search button
            $form['row'][0]['search'] = [
              'type'  => 'submit',
              'grid'  => 1,
              //'name'        => 'Search',
              //'icon'        => 'icon-search',
              'value' => 'fdb',
              'right' => TRUE
            ];

            print_form($form);
            unset($form, $form_items, $form_devices);

            // Pagination
            $vars['pagination'] = TRUE;

            print_fdbtable($vars);

            register_html_title("FDB Search");

            ?>

        </div> <!-- col-md-12 -->
    </div> <!-- row -->

<?php

// EOF
