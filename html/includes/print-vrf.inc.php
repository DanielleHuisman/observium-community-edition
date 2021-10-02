<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

echo "<tr>";

echo '<td width=200 class=entity-title><a href="'.generate_url(array('page' => 'routing', 'protocol' => 'vrf', 'vrf' => $vrf['vrf_rd'])).'">' . $vrf['vrf_name'] . '</a></td>';
echo '<td width=150 class=small>' . $vrf['vrf_descr'] . '</td>';
echo '<td width=100 class=small>' . $vrf['vrf_rd'] . '</td>';

echo '<td class="entity">';
foreach (dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifVrf` = ?", array($device['device_id'], $vrf['vrf_id'])) as $port) {
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
