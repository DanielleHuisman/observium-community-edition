<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     applications
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) Adam Armstrong
 *
 */

$app_graphs['default'] = ['unbound_queries' => 'DNS traffic and cache hits',
                          'unbound_queue'   => 'Queue statistics',
                          'unbound_memory'  => 'Memory statistics',
                          'unbound_qtype'   => 'Queries by Query type',
                          'unbound_rcode'   => 'Queries by Return code',
                          'unbound_opcode'  => 'Queries by Operation code',
                          'unbound_class'   => 'Queries by Query class',
                          'unbound_flags'   => 'Queries by Flags'];

// EOF
