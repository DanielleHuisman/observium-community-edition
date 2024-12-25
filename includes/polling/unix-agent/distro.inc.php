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

$distro = $agent_data['distro'];
unset($agent_data['distro']);

foreach (explode("\n", $distro) as $line) {
    [$field, $contents] = explode("=", $line, 2);
    $agent_data['distro'][$field] = trim($contents);
}

unset($distro);

// EOF
