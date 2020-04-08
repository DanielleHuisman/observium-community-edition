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

$serial   = implode(', ',explode("\n",snmp_walk($device, 'productIdentificationServiceTag', '-Ovq', 'Dell-Vendor-MIB')));

// EOF
