<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @copyright  (C) Adam Armstrong
 *
 */

if (empty($graph_array['type'])) {
    $graph_array['type'] = $graph_type;
}
if (empty($graph_array['device'])) {
    $graph_array['device'] = $device['device_id'];
}

echo('<tr><td>');

echo('<h4>' . $graph_title . '</h4>');

print_graph_row($graph_array);

echo('</td></tr>');

// EOF
