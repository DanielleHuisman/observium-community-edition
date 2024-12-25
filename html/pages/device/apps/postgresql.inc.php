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

$app_sections = ['stats' => "Stats",
                 'live'  => "Live"];

$app_graphs['stats'] = ['postgresql_xact'         => 'Commit Count',
                        'postgresql_blks'         => 'Blocks Count',
                        'postgresql_tuples'       => 'Tuples Count',
                        'postgresql_tuples_query' => 'Tuples Count per Query'];

$app_graphs['live'] = ['postgresql_connects' => 'Connection Count',
                       'postgresql_queries'  => 'Query Types'];

// EOF
