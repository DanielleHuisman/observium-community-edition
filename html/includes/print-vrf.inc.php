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

echo "<tr>";

echo '<td style="width: 200px" class=entity-title><a href="' . generate_url(['page' => 'routing', 'protocol' => 'vrf', 'vrf' => $vrf['vrf_rd']]) . '">' . $vrf['vrf_name'] . '</a><br />';
echo '<span class=small>' . $vrf['vrf_descr'] . '</span></td>';
echo '<td style="width: 75px" class=small><span class="label label-primary">' . $vrf['vrf_rd'] . '</span></td>';

echo '<td class="entity">';
foreach (dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifVrf` = ?", [$device['device_id'], $vrf['vrf_id']]) as $port) {
    if ($vars['view'] == "graphs") {
        $graph_type = "port_" . $vars['graph'];
        print_port_minigraph($port, $graph_type, 'twoday');
    } else {
        echo($vrf['port_sep'] . generate_port_link_short($port));
        $vrf['port_sep'] = ", ";
    }
}

echo "</td>";
echo "</tr>";

// EOF
