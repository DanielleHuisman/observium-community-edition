<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$hardware = rewrite_definition_hardware($device, $poll_device['sysObjectID']);

//preg_match('/(?:Alcatel-Lucent\ |)(?P<hardware>[\w\-\ ]*)(?P<version>(?:\d+\.){2,}\w+)/', $poll_device['sysDescr'], $matches);
//$hardware = trim($matches['hardware']);
//if ($hardware === '') { $hardware = 'Generic'; }
//$version  = $matches['version'];

// EOF
