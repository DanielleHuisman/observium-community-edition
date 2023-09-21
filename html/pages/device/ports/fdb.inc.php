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
            unset($search, $vlans, $vlan_names, $port_names);

            // Select ports only present in FDB tables
            $form_items = [];
            foreach (dbFetchRows('SELECT `port_id`, `device_id`, `ifDescr`, `ifName`, `ifAlias`
                        FROM `vlans_fdb` AS F
                        LEFT JOIN `ports` as P USING (`port_id`, `device_id`)
                        WHERE `device_id` = ? AND `port_id` != 0 GROUP BY `port_id`;', [$device['device_id']]) as $data) {
                humanize_port($data);
                $form_items['ports'][$data['port_id']] = $data['port_label'];
            }
            if (is_array($form_items['ports'])) {
                natcasesort($form_items['ports']);
            }

            foreach (dbFetchRows('SELECT `vlan_vlan`, `vlan_name`
                     FROM `vlans_fdb` AS F
                     LEFT JOIN `vlans` as V ON V.`vlan_vlan` = F.`vlan_id` AND V.`device_id` = F.`device_id`
                     WHERE F.`device_id` = ?
                     GROUP BY `vlan_vlan`', [$device['device_id']]) as $data) {
                $form_items['vlans'][$data['vlan_vlan']]     = 'Vlan ' . $data['vlan_vlan'];
                $form_items['vlan_name'][$data['vlan_name']] = $data['vlan_name'];
            }
            if (is_array($form_items['vlans'])) {
                ksort($form_items['vlans']);
                natcasesort($form_items['vlan_name']);
            }

            $form = [
              'type'          => 'rows',
              'space'         => '5px',
              'submit_by_key' => TRUE,
              //'url'           => 'search/search=fdb/'
            ];

            $form['row'][0]['port']      = [
              'type'   => 'multiselect',
              'name'   => 'Ports',
              'width'  => '100%',
              'value'  => $vars['port'],
              'values' => $form_items['ports']
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
              'grid'          => 4,
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
              'grid'     => 1,
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

            ?>

        </div> <!-- col-md-12 -->
    </div> <!-- row -->
<?php

// EOF
