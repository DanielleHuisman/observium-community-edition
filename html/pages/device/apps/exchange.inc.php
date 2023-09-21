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

$app_sections = [];
$app_modules  = [
  "as"      => [
    "rrd"    => "wmi-app-exchange-as.rrd",
    "descr"  => "ActiveSync",
    "graphs" => [
      'exchange_as_pingcmd' => 'Ping Commands Pending',
      'exchange_as_syncmd'  => 'Sync Commands Pending',
      'exchange_as_curreqs' => 'Current Requests'
    ]
  ],
  "auto"    => [
    "rrd"    => "wmi-app-exchange-auto.rrd",
    "descr"  => "Autodiscover",
    "graphs" => [
      'exchange_auto_totalreqs' => 'Total Requests',
      'exchange_auto_errors'    => 'Total Error Responses'
    ]
  ],
  "oab"     => [
    "rrd"    => "wmi-app-exchange-oab.rrd",
    "descr"  => "Offline Address Book",
    "graphs" => [
      'exchange_oab_dlq'   => 'Download Tasks Queued',
      'exchange_oab_dlcom' => 'Download Tasks Completed'
    ]
  ],
  "owa"     => [
    "rrd"    => "wmi-app-exchange-owa.rrd",
    "descr"  => "Outlook Web App",
    "graphs" => [
      'exchange_owa_rtime' => 'Response Times',
      'exchange_owa_users' => 'Unique Users'
    ]
  ],
  "trans"   => [
    "rrd"    => "wmi-app-exchange-tqs.rrd",
    "descr"  => "Transport Queues",
    "graphs" => [
      'exchange_trans_queue'  => 'Total Queues',
      'exchange_trans_mbque'  => 'Active Mailbox Delivery Queues',
      'exchange_trans_subque' => 'Submission Queues'
    ]
  ],
  "smtp"    => [
    "rrd"    => "wmi-app-exchange-smtp.rrd",
    "descr"  => "SMTP",
    "graphs" => [
      'exchange_trans_smtp' => "SMTP Connections"
    ]
  ],
  "is"      => [
    "rrd"    => "wmi-app-exchange-is.rrd",
    "descr"  => "Information Store",
    "graphs" => [
      'exchange_is_active'  => 'Active Connection Count',
      'exchange_is_users'   => 'Current User Count',
      'exchange_is_rpcreq'  => 'RPC Requests',
      'exchange_is_rpcfail' => 'Failed RPC Requests'
    ]
  ],
  "mailbox" => [
    "rrd"    => "wmi-app-exchange-mailbox.rrd",
    "descr"  => "Mailbox",
    "graphs" => [
      'exchange_mb_latency' => 'RPC Average Latency',
      'exchange_mb_queued'  => 'Messages Queued for Submission',
      'exchange_mb_msgs'    => 'Messages per Second'
    ]
  ]
];

foreach ($app_modules as $module => $data) {
    if (is_file(get_rrd_path($device, $data['rrd']))) {
        $app_sections[$module] = $data['descr'];
        $app_graphs[$module]   = $data['graphs'];
    }
}

unset($app_modules);

// EOF
