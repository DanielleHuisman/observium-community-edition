<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!empty($agent_data['app']['kamailio'])) {
    $app_id = discover_app($device, 'kamailio');

    $key_trans_table = [
      'core:bad_URIs_rcvd'              => 'corebadURIsrcvd',
      'core:bad_msg_hdr'                => 'corebadmsghdr',
      'core:drop_replies'               => 'coredropreplies',
      'core:drop_requests'              => 'coredroprequests',
      'core:err_replies'                => 'coreerrreplies',
      'core:err_requests'               => 'coreerrrequests',
      'core:fwd_replies'                => 'corefwdreplies',
      'core:fwd_requests'               => 'corefwdrequests',
      'core:rcv_replies'                => 'corercvreplies',
      'core:rcv_requests'               => 'corercvrequests',
      'core:unsupported_methods'        => 'coreunsupportedmeth',
      'dns:failed_dns_request'          => 'dnsfaileddnsrequest',
      'mysql:driver_errors'             => 'mysqldrivererrors',
      'registrar:accepted_regs'         => 'registraraccregs',
      'registrar:default_expire'        => 'registrardefexpire',
      'registrar:default_expires_range' => 'registrardefexpirer',
      'registrar:max_contacts'          => 'registrarmaxcontact',
      'registrar:max_expires'           => 'registrarmaxexpires',
      'registrar:rejected_regs'         => 'registrarrejregs',
      'shmem:fragments'                 => 'shmemfragments',
      'shmem:free_size'                 => 'shmemfreesize',
      'shmem:max_used_size'             => 'shmemmaxusedsize',
      'shmem:real_used_size'            => 'shmemrealusedsize',
      'shmem:total_size'                => 'shmemtotalsize',
      'shmem:used_size'                 => 'shmemusedsize',
      'siptrace:traced_replies'         => 'siptracetracedrepl',
      'siptrace:traced_requests'        => 'siptracetracedreq',
      'sl:1xx_replies'                  => 'sl1xxreplies',
      'sl:200_replies'                  => 'sl200replies',
      'sl:202_replies'                  => 'sl202replies',
      'sl:2xx_replies'                  => 'sl2xxreplies',
      'sl:300_replies'                  => 'sl300replies',
      'sl:301_replies'                  => 'sl301replies',
      'sl:302_replies'                  => 'sl302replies',
      'sl:3xx_replies'                  => 'sl3xxreplies',
      'sl:400_replies'                  => 'sl400replies',
      'sl:401_replies'                  => 'sl401replies',
      'sl:403_replies'                  => 'sl403replies',
      'sl:404_replies'                  => 'sl404replies',
      'sl:407_replies'                  => 'sl407replies',
      'sl:408_replies'                  => 'sl408replies',
      'sl:483_replies'                  => 'sl483replies',
      'sl:4xx_replies'                  => 'sl4xxreplies',
      'sl:500_replies'                  => 'sl500replies',
      'sl:5xx_replies'                  => 'sl5xxreplies',
      'sl:6xx_replies'                  => 'sl6xxreplies',
      'sl:failures'                     => 'slfailures',
      'sl:received_ACKs'                => 'slreceivedACKs',
      'sl:sent_err_replies'             => 'slsenterrreplies',
      'sl:sent_replies'                 => 'slsentreplies',
      'sl:xxx_replies'                  => 'slxxxreplies',
      'tcp:con_reset'                   => 'tcpconreset',
      'tcp:con_timeout'                 => 'tcpcontimeout',
      'tcp:connect_failed'              => 'tcpconnectfailed',
      'tcp:connect_success'             => 'tcpconnectsuccess',
      'tcp:current_opened_connections'  => 'tcpcurrentopenedcon',
      'tcp:current_write_queue_size'    => 'tcpcurrentwrqsize',
      'tcp:established'                 => 'tcpestablished',
      'tcp:local_reject'                => 'tcplocalreject',
      'tcp:passive_open'                => 'tcppassiveopen',
      'tcp:send_timeout'                => 'tcpsendtimeout',
      'tcp:sendq_full'                  => 'tcpsendqfull',
      'tmx:2xx_transactions'            => 'tmx2xxtransactions',
      'tmx:3xx_transactions'            => 'tmx3xxtransactions',
      'tmx:4xx_transactions'            => 'tmx4xxtransactions',
      'tmx:5xx_transactions'            => 'tmx5xxtransactions',
      'tmx:6xx_transactions'            => 'tmx6xxtransactions',
      'tmx:UAC_transactions'            => 'tmxUACtransactions',
      'tmx:UAS_transactions'            => 'tmxUAStransactions',
      'tmx:inuse_transactions'          => 'tmxinusetransaction',
      'tmx:local_replies'               => 'tmxlocalreplies',
      'usrloc:location-contacts'        => 'usrlocloccontacts',
      'usrloc:location-expires'         => 'usrloclocexpires',
      'usrloc:location-users'           => 'usrloclocusers',
      'usrloc:registered_users'         => 'usrlocregusers',
    ];

    $data = [];

    foreach (explode("\n", $agent_data['app']['kamailio']) as $line) {
        [$key, $val] = explode("=", $line);
        $key = trim($key);

        if (substr($key, 0, 6) == 'usrloc') {
            $tmp = substr($key, strpos($key, '-') + 1);
            switch ($tmp) {
                case 'contacts':
                case 'expires':
                case 'users':
                    $key = 'usrloc:location-' . $tmp;
                    break;
            }
        }

        if (isset($key_trans_table[$key])) {
            $data[$key_trans_table[$key]] = (int)trim($val);
        } else {
            print_debug("nick - key is not : $key");
        }
    }

    update_application($app_id, $data);

    rrdtool_update_ng($device, 'kamailio', $data, $app_id);
}

// EOF
