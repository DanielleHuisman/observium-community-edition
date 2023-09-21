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

// Included in: html/pages/front/default.php, html/includes/panels/default.php

if ($cache['devices']['stat']['down']) {
    $cache['devices']['stat']['class'] = "error";
} else {
    $cache['devices']['stat']['class'] = "";
}
if ($cache['ports']['stat']['down']) {
    $ports_class = "error";
} else {
    $ports_class = "";
}

?>

    <div class="<?php echo($div_class); ?>" style="margin-bottom: 10px;">
        <div class="box box-solid">
            <table class="table table-condensed-more table-striped">
                <thead>
                <tr>
                    <th class="state-marker"></th>
                    <th></th>
                    <th style="width: 10%">Total</th>
                    <th style="width: 14%">Up</th>
                    <th style="width: 14%">Alert</th>
                    <th style="width: 20%">Ignored (Dev)</th>
                    <th style="width: 20%">Disabled / Shut</th>
                </tr>
                </thead>
                <tbody>
                <tr class="<?php echo($cache['devices']['stat']['class']); ?>">
                    <td class="state-marker"></td>
                    <td><strong><a href="<?php echo(generate_url(['page' => 'devices'])); ?>">Devices</a></strong></td>
                    <td><a href="<?php echo(generate_url(['page' => 'devices'])); ?>"><?php echo($cache['devices']['stat']['count']) ?></a></td>
                    <td><a class="green"
                           href="<?php echo(generate_url(['page' => 'devices', 'status' => '1'])); ?>"><?php echo($cache['devices']['stat']['up']) ?> up</a>
                    </td>
                    <td><a class="red"
                           href="<?php echo(generate_url(['page' => 'devices', 'status' => '0', 'ignore' => '0'])); ?>"><?php echo($cache['devices']['stat']['down']) ?>
                            down</a></td>
                    <td><a class="black"
                           href="<?php echo(generate_url(['page' => 'devices', 'ignore' => '1'])); ?>"><?php echo($cache['devices']['stat']['ignored']) ?>
                            ignored</a></td>
                    <td><a class="grey"
                           href="<?php echo(generate_url(['page' => 'devices', 'disabled' => '1'])); ?>"><?php echo($cache['devices']['stat']['disabled']) ?>
                            disabled</a></td>
                </tr>
                <tr class="<?php echo($ports_class) ?>">
                    <td class="state-marker"></td>
                    <td><strong><a href="<?php echo(generate_url(['page' => 'ports'])); ?>">Ports</a></strong></td>
                    <td><a href="<?php echo(generate_url(['page' => 'ports', 'ignore' => '0'])); ?>"><?php echo($cache['ports']['stat']['count']) ?></a></td>
                    <td><a class="green"
                           href="<?php echo(generate_url(['page' => 'ports', 'state' => 'up', 'ignore' => '0'])); ?>"><?php echo($cache['ports']['stat']['up']) ?>
                            up</a></td>
                    <td><a class="red"
                           href="<?php echo(generate_url(['page' => 'ports', 'state' => 'down', 'ignore' => '0'])); ?>"><?php echo($cache['ports']['stat']['down']) ?>
                            down</a></td>
                    <td><a class="black"
                           href="<?php echo(generate_url(['page' => 'ports', 'ignore' => '1'])); ?>"><?php echo($cache['ports']['stat']['ignored']); ?>
                            (<?php echo($cache['ports']['stat']['device_ignored']) ?>) ignored</a></td>
                    <td><a class="grey"
                           href="<?php echo(generate_url(['page' => 'ports', 'state' => 'admindown', 'ignore' => '0'])); ?>"><?php echo($cache['ports']['stat']['shutdown']) ?>
                            shutdown</a></td>
                </tr>
                <?php
                // Sensors
                if ($cache['sensors']['stat']['count']) {
                    if ($cache['sensors']['stat']['alert']) {
                        $cache['sensors']['stat']['class'] = "error";
                        $alert_msg                         = $cache['sensors']['stat']['alert'] . ' alerts';
                        if ($cache['sensors']['stat']['warning']) {
                            $alert_msg .= ', ' . $cache['sensors']['stat']['warning'] . ' warnings';
                        }
                    } elseif ($cache['sensors']['stat']['warning']) {
                        $cache['sensors']['stat']['class'] = "warning";
                        $alert_msg                         = $cache['sensors']['stat']['warning'] . ' warnings';
                    } else {
                        $cache['sensors']['stat']['class'] = "";
                        $alert_msg                         = '0 alerts';
                    }
                    ?>
                    <tr class="<?php echo($cache['sensors']['stat']['class']) ?>">
                        <td class="state-marker"></td>
                        <td><strong><a href="<?php echo(generate_url(['page' => 'health', 'metric' => 'sensors'])); ?>">Sensors</a></strong></td>
                        <td><a
                              href="<?php echo(generate_url(['page' => 'health', 'metric' => 'sensors'])); ?>"><?php echo($cache['sensors']['stat']['count']) ?></a>
                        </td>
                        <td><a class="green"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'sensors', 'event' => 'ok'])); ?>"><?php echo($cache['sensors']['stat']['ok']) ?>
                                ok</a></td>
                        <td><a class="red"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'sensors', 'event' => 'alert,warning'])); ?>"><?php echo($alert_msg) ?></a>
                        </td>
                        <td><a class="black"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'sensors', 'event' => 'ignore'])); ?>"><?php echo($cache['sensors']['stat']['ignored']) ?>
                                ignored</a></td>
                        <td><a class="grey"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'sensors'])); ?>"><?php echo($cache['sensors']['stat']['disabled']) ?>
                                disabled</a></td>
                    </tr>
                    <?php
                } # end if sensors
                if ($cache['statuses']['stat']['count']) {
                    //if ($cache['statuses']['stat']['alert']) { $cache['statuses']['stat']['class'] = "error"; } else { $cache['statuses']['stat']['class'] = ""; }
                    if ($cache['statuses']['stat']['alert']) {
                        $cache['statuses']['stat']['class'] = "error";
                        $alert_msg                          = $cache['statuses']['stat']['alert'] . ' alerts';
                        if ($cache['statuses']['stat']['warning']) {
                            $alert_msg .= ', ' . $cache['statuses']['stat']['warning'] . ' warnings';
                        }
                    } elseif ($cache['statuses']['stat']['warning']) {
                        $cache['statuses']['stat']['class'] = "warning";
                        $alert_msg                          = $cache['statuses']['stat']['warning'] . ' warnings';
                    } else {
                        $cache['statuses']['stat']['class'] = "";
                        $alert_msg                          = '0 alerts';
                    }
                    ?>
                    <tr class="<?php echo($cache['statuses']['stat']['class']) ?>">
                        <td class="state-marker"></td>
                        <td><strong><a href="<?php echo(generate_url(['page' => 'health', 'metric' => 'status'])); ?>">Statuses</a></strong></td>
                        <td><a
                              href="<?php echo(generate_url(['page' => 'health', 'metric' => 'status'])); ?>"><?php echo($cache['statuses']['stat']['count']) ?></a>
                        </td>
                        <td><a class="green"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'status', 'event' => 'ok'])); ?>"><?php echo($cache['statuses']['stat']['ok']) ?>
                                ok</a></td>
                        <td><a class="red"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'status', 'event' => 'alert,warning'])); ?>"><?php echo($alert_msg) ?></a>
                        </td>
                        <td><a class="black"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'status', 'event' => 'ignore'])); ?>"><?php echo($cache['statuses']['stat']['ignored']) ?>
                                ignored</a></td>
                        <td><a class="grey"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'status'])); ?>"><?php echo($cache['statuses']['stat']['disabled']) ?>
                                disabled</a></td>
                    </tr>
                    <?php
                } # end if statuses

                if ($cache['counters']['stat']['count']) {
                    //if ($cache['counters']['stat']['alert']) { $cache['counters']['stat']['class'] = "error"; } elseif ($cache['counters']['stat']['warning']) { $cache['counters']['stat']['class'] = "warning"; } else { $cache['counters']['stat']['class'] = ""; }
                    if ($cache['counters']['stat']['alert']) {
                        $cache['counters']['stat']['class'] = "error";
                        $alert_msg                          = $cache['counters']['stat']['alert'] . ' alerts';
                        if ($cache['counters']['stat']['warning']) {
                            $alert_msg .= ', ' . $cache['counters']['stat']['warning'] . ' warnings';
                        }
                    } elseif ($cache['counters']['stat']['warning']) {
                        $cache['counters']['stat']['class'] = "warning";
                        $alert_msg                          = $cache['counters']['stat']['warning'] . ' warnings';
                    } else {
                        $cache['counters']['stat']['class'] = "";
                        $alert_msg                          = '0 alerts';
                    }
                    ?>
                    <tr class="<?php echo($cache['counters']['stat']['class']) ?>">
                        <td class="state-marker"></td>
                        <td><strong><a href="<?php echo(generate_url(['page' => 'health', 'metric' => 'counter'])); ?>">Counters</a></strong></td>
                        <td><a
                              href="<?php echo(generate_url(['page' => 'health', 'metric' => 'counter'])); ?>"><?php echo($cache['counters']['stat']['count']) ?></a>
                        </td>
                        <td><a class="green"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'counter', 'event' => 'ok'])); ?>"><?php echo($cache['counters']['stat']['ok']) ?>
                                ok</a></td>
                        <td><a class="red"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'counter', 'event' => 'alert,warning'])); ?>"><?php echo($alert_msg) ?></a>
                        </td>
                        <td><a class="black"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'counter', 'event' => 'ignore'])); ?>"><?php echo($cache['counters']['stat']['ignored']) ?>
                                ignored</a></td>
                        <td><a class="grey"
                               href="<?php echo(generate_url(['page' => 'health', 'metric' => 'counter'])); ?>"><?php echo($cache['counters']['stat']['disabled']) ?>
                                disabled</a></td>
                    </tr>
                    <?php
                } # end if counters

                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php

switch (TRUE) {
    case ($cache['alert_entries']['up'] == $cache['alert_entries']['count']):
        $check['class']            = "green";
        $check['table_tab_colour'] = "#194b7f";
        $check['html_row_class']   = "";
        break;
    case ($cache['alert_entries']['down'] > 0):
        $check['class']            = "red";
        $check['table_tab_colour'] = "#cc0000";
        $check['html_row_class']   = "error";
        break;
    case ($cache['alert_entries']['delay'] > 0):
        $check['class']            = "orange";
        $check['table_tab_colour'] = "#ff6600";
        $check['html_row_class']   = "warning";
        break;
    case ($cache['alert_entries']['suppress'] > 0):
        $check['class']            = "purple";
        $check['table_tab_colour'] = "#740074";
        $check['html_row_class']   = "suppressed";
        break;
    case ($cache['alert_entries']['up'] > 0):
        $check['class']            = "green";
        $check['table_tab_colour'] = "#194b7f";
        $check['html_row_class']   = "";
        break;
    default:
        $check['class']            = "gray";
        $check['table_tab_colour'] = "#555555";
        $check['html_row_class']   = "disabled";
}

$check['status_numbers'] = '
          <span class="green">' . $cache['alert_entries']['up'] . '</span>/
          <span class="purple">' . $cache['alert_entries']['suppress'] . '</span>/
          <span class="red">' . $cache['alert_entries']['down'] . '</span>/
          <span class="orange">' . $cache['alert_entries']['delay'] . '</span>/
          <span class="gray">' . $cache['alert_entries']['unknown'] . '</span>';
?>

    <div class="<?php echo($div_class); ?>">
        <div class="box box-solid">
            <table class="table  table-condensed-more  table-striped">
                <thead>
                <tr>
                    <th class="state-marker"></th>
                    <th></th>
                    <th>Ok</th>
                    <th>Fail</th>
                    <th>Delay</th>
                    <th>Suppress</th>
                    <th>Other</th>
                </tr>
                </thead>
                <tbody>
                <tr class="<?php echo($check['html_row_class']); ?>">
                    <td class="state-marker"></td>
                    <td><a href="/alerts/"><strong>Alerts</strong></a></td>
                    <td><span class="green"><?php echo $cache['alert_entries']['up']; ?></span></td>
                    <td><span class="red"><?php echo $cache['alert_entries']['down']; ?></span></td>
                    <td><span class="orange"><?php echo $cache['alert_entries']['delay']; ?></span></td>
                    <td><span class="purple"><?php echo $cache['alert_entries']['suppress']; ?></span></td>
                    <td><span class="gray"><?php echo $cache['alert_entries']['unknown']; ?></span></td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php


        if ($_SESSION['userlevel'] >= 5 && !isset($hide_group_bar)) {

            $navbar              = [];
            $navbar['class']     = 'navbar-narrow';
            $navbar['brand']     = 'Groups';
            $navbar['style']     = 'margin-top: 10px';
            $navbar['community'] = FALSE;

            $groups = get_groups_by_type($config['wui']['groups_list']);

            foreach ($config['wui']['groups_list'] as $entity_type) {
                if (!isset($config['entities'][$entity_type])) {
                    continue;
                } // Skip unknown types

                $navbar['options'][$entity_type]['icon'] = $config['entities'][$entity_type]['icon'];
                $navbar['options'][$entity_type]['text'] = nicecase($entity_type);
                foreach ($groups[$entity_type] as $group) {
                    $navbar['options'][$entity_type]['suboptions'][$group['group_id']]['text'] = escape_html($group['group_name']);
                    $navbar['options'][$entity_type]['suboptions'][$group['group_id']]['icon'] = $config['entities'][$entity_type]['icon'];
                    $navbar['options'][$entity_type]['suboptions'][$group['group_id']]['url']  = generate_url(['page' => 'group', 'group_id' => $group['group_id']]);
                    if ($vars['group_id'] == $group['group_id']) {
                        $navbar['options'][$entity_type]['suboptions'][$group['group_id']]['class'] = "active";
                        $navbar['options'][$entity_type]['class']                                   = "active";
                    }
                }
            }

            print_navbar($navbar);
            unset($navbar);
        }
        ?>

    </div>
<?php

// EOF
