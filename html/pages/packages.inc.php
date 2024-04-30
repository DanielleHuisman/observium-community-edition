<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

$where = [];

foreach ($vars as $var => $value) {
    if ($value != "") {
        switch ($var) {
            case 'name':
                $where[] = generate_query_values($value, $var);
                break;
        }
    }
}

echo generate_box_open();

echo '<table class="table table-condensed table-striped">';
echo '  <thead>';
echo '    <tr>';
echo '      <th style="width: 300px;">Package</th>';
echo '      <th>Version</th>';
echo '    </tr>';
echo '  </thead>';
echo '  <tbody>';


foreach (dbFetchRows("SELECT * FROM `packages` " . generate_where_clause($where, generate_query_permitted_ng('device'))) as $v) {
    $packages[$v['name']][$v['version']][$v['build']][] = $v;

    //$all[]=$v;
}

//r($all);

ksort($packages);


foreach ($packages as $name => $package) {
    echo '    <tr>' . PHP_EOL;
    echo '      <td><a href="' . generate_url($vars, ['name' => $name]) . '" class="entity">' . $name . '</a></td>' . PHP_EOL;
    echo '      <td>';

    $vers    = [];
    $content = "";
    $table   = '';


    foreach ($package as $version => $builds) {

        foreach ($builds as $build => $devices) {
            if ($build) {
                $dbuild = '-' . $build;
            } else {
                $dbuild = '';
            }
            $content .= $version . $dbuild;

            foreach ($devices as $entry) {
                $this_device = ['device_id' => $entry['device_id'], 'hostname' => $GLOBALS['cache']['devices']['hostname_map'][$entry['device_id']]];

                $arch_classes        = [
                  'amd64' => 'label-success',
                  'i386'  => 'label-info'
                ];

                $entry['arch_class'] = $arch_classes[$entry['arch']] ?? '';

                $manager_classes        = [
                  'deb' => 'label-warning',
                  'rpm' => 'label-important'
                ];

                $entry['manager_class'] = $manager_classes[$entry['manager']] ?? '';

                $dbuild = !empty($entry['build']) ? '-' . $entry['build'] : '';

                if (!empty($this_device['hostname'])) {
                    if (!empty($vars['name'])) {

                        $table .= '<tr>';
                        $table .= '<td>' . $entry['version'] . $dbuild . '</td><td><span class="label ' . $entry['arch_class'] . '">' . $entry['arch'] . '</span></td>
                  <td><span class="label ' . $entry['manager_class'] . '">' . $entry['manager'] . '</span></td>
                  <td class="entity">' . generate_device_link($this_device) . '</td><td></td><td>' . format_si($entry['size']) . '</td>';
                        $table .= '</tr>';
                    } else {
                        $hosts[] = '<span class="entity">' . $this_device['hostname'] . '</span>';
                    }
                }
            }
        }


        if (empty($vars['name'])) {
            $hosts  = implode('<br />', $hosts);
            $vers[] = generate_tooltip_link('', $version . $dbuild, $hosts);
        }
        unset($hosts);
    }

    if (!empty($vars['name'])) {
        echo '<table class="' . OBS_CLASS_TABLE_STRIPED . '">';
        echo '<tbody>';
        echo $table;
        echo '</tbody>';
        echo '</table>';
    } else {
        echo implode(' - ', $vers);
    }

    unset($vers);

    echo '      </td>' . PHP_EOL;
    echo '    </tr>' . PHP_EOL;
}

echo '  </tbody>';
echo '</table>';

echo generate_box_close();

// EOF
