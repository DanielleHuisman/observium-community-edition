<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

$ksm = $agent_data['ksm'];
unset($agent_data['ksm']);

foreach (explode("\n", $ksm) as $line) {
    [$field, $contents] = explode("=", $line, 2);
    $agent_data['ksm'][$field] = trim($contents);
}

rrdtool_update_ng($device, 'ksm-pages', [
  'pagesShared'   => $agent_data['ksm']['pages_shared'],
  'pagesSharing'  => $agent_data['ksm']['pages_sharing'],
  'pagesUnshared' => $agent_data['ksm']['pages_unshared'],
]);

$graphs['ksm_pages'] = TRUE;

unset($ksm);

// EOF
