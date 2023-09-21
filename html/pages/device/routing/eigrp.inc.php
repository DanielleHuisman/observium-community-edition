<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$navbar          = [];
$navbar['brand'] = "EIGRP";
$navbar['class'] = "navbar-narrow";

if (!$vars['view']) {
    $vars['view'] = "ports";
}

foreach (["vpn", "asn", "ports", "peers"] as $type) {
    $navbar['options'][$type]['url']  = generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'eigrp', 'view' => $type]);
    $navbar['options'][$type]['text'] = nicecase($type);
    if ($vars['view'] == $type) {
        $navbar['options'][$type]['class'] = "active";
    }
}

foreach (dbFetchRows("SELECT * FROM `eigrp_vpns` WHERE `device_id` = ?", [$device['device_id']]) as $entry) {

    if (!isset($vars['vpn'])) {
        $vars['vpn'] = $entry['eigrp_vpn'];
    }

    if ($vars['vpn'] == $entry['eigrp_vpn']) {
        $navbar['options']['vpn']['text'] .= " (" . $entry['eigrp_vpn_name'] . ")";
    }
    $navbar['options']['vpn']['suboptions'][$entry['eigrp_vpn_name']]['text'] = $entry['eigrp_vpn_name'];
    $navbar['options']['vpn']['suboptions'][$entry['eigrp_vpn_name']]['url']  = generate_url($vars, ['vpn' => $entry['eigrp_vpn'], 'asn' => NULL]);

}

foreach (dbFetchRows("SELECT * FROM `eigrp_ases` WHERE `device_id` = ? AND `eigrp_vpn` = ?", [$device['device_id'], $vars['vpn']]) as $entry) {

    if (!isset($vars['asn'])) {
        $vars['asn'] = $entry['eigrp_as'];
    }

    if ($vars['asn'] == $entry['eigrp_as']) {
        $navbar['options']['asn']['text'] .= " (" . $entry['eigrp_as'] . ")";
    }
    $navbar['options']['asn']['suboptions'][$entry['eigrp_as']]['text'] = "AS" . $entry['eigrp_as'];
    $navbar['options']['asn']['suboptions'][$entry['eigrp_as']]['url']  = generate_url($vars, ['asn' => $entry['eigrp_as']]);

}

$navbar['options_right']['graphs'] = ['text'  => "Graphs",
                                      'icon'  => $config['icon']['graphs'],
                                      'url'   => generate_url($vars, ['graphs' => ($vars['graphs'] == 'yes' ? NULL : 'yes')]),
                                      'class' => ($vars['graphs'] == 'yes' ? 'active' : NULL)];

print_navbar($navbar);
unset($navbar);

echo generate_box_open();

foreach (dbFetchRows("SELECT * FROM `eigrp_ases` LEFT JOIN `eigrp_vpns` USING (`device_id`, `eigrp_vpn`) WHERE `device_id` =? AND `eigrp_vpn` = ? AND `eigrp_as` = ? ", [$device['device_id'], $vars['vpn'], $vars['asn']]) as $as) {

    $port_count = dbFetchCell("SELECT COUNT(*) FROM `eigrp_ports` WHERE device_id = ? AND `eigrp_vpn` = ? AND `eigrp_as` = ?", [$as['device_id'], $as['eigrp_vpn'], $as['eigrp_as']]);
    $peer_count = dbFetchCell("SELECT COUNT(*) FROM `eigrp_peers` WHERE device_id = ? AND `eigrp_vpn` = ? AND `eigrp_as` = ?", [$as['device_id'], $as['eigrp_vpn'], $as['eigrp_as']]);

    ?>

    <table class="table table-hover table-striped vertical-align">
        <tbody>
        <tr class="up">
            <td class="state-marker"></td>
            <td style="padding: 10px 14px; width: 500px"><span
                  style="font-size: 20px; color: #193d7f;">VPN <?php echo $as['eigrp_vpn_name']; ?> / AS<?php echo($as['eigrp_as']); ?></span></td>
            <td>
                <div class="btn-group" style="margin: 5px;">
                    <div class="btn btn-sm btn-default"><strong>Router ID</strong></div>
                    <div class="btn btn-sm btn-default"> <?php echo $as['cEigrpAsRouterId']; ?></div>
                </div>
            </td>

            <td style="text-align: right;">


                <div class="btn-group" style="margin: 5px;">
                    <div class="btn btn-sm btn-default"><strong>Ports</strong></div>
                    <div class="btn btn-sm btn-danger"> <?php echo $port_count; ?></div>
                </div>

                <div class="btn-group" style="margin: 5px;">
                    <div class="btn btn-sm btn-default"><strong>Neighbours</strong></div>
                    <div class="btn btn-sm btn-info"> <?php echo $as['cEigrpNbrCount']; ?></div>
                </div>

                <div class="btn-group" style="margin: 5px;">
                    <div class="btn btn-sm btn-default"><strong>Routes</strong></div>
                    <div class="btn btn-sm btn-primary"> <?php echo $as['cEigrpTopoRoutes']; ?></div>
                </div>
            </td>

        </tr>
        </tbody>
    </table>

    <?php

}

echo generate_box_close();

switch ($vars['view']) {
    case "ports":
    case "peers":
        include("eigrp/" . $vars['view'] . ".inc.php");
        break;

}

// EOF
