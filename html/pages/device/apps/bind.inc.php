<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     applications
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$app_sections = ['stats'   => "Server statistics",
                 'auth'    => "Authoritative",
                 'resolv'  => "Resolving",
                 'queries' => "Queries"];

$app_graphs['stats'] = ['bind_req_in'    => "Incoming requests",
                        'bind_answers'   => "Answers Given",
                        'bind_updates'   => "Dynamic Updates",
                        'bind_req_proto' => "Request protocol details",
                        'bind_cache'     => "Cache content"];

$app_graphs['auth'] = ['bind_zone_maint' => "Zone maintenance"];

$app_graphs['resolv'] = ['bind_resolv_queries' => "Queries",
                         'bind_resolv_errors'  => "Errors",
                         'bind_resolv_rtt'     => "Query RTT",
                         'bind_resolv_dnssec'  => "DNSSEC validation"];

$app_graphs['queries'] = ['bind_query_rejected' => "Rejected queries",
                          'bind_query_in'       => "Incoming queries",
                          'bind_query_out'      => "Outgoing queries"];

// EOF
