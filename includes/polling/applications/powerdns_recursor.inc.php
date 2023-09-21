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

# <<<app-powerdns-recursor>>>
# all-outqueries  7371846
# dlg-only-drops  0
# dont-outqueries 32
# outgoing-timeouts       193883
# tcp-outqueries  8036
# throttled-out   39438
# throttled-outqueries    39438
# unreachables    12848
# answers-slow    170580
# answers0-1      549381
# answers1-10     692066
# answers10-100   2945477
# answers100-1000 1346860
# case-mismatches 0
# chain-resends   91977
# client-parse-errors     0
# edns-ping-matches       0
# edns-ping-mismatches    0
# ipv6-outqueries 818068
# no-packet-error 2596648
# noedns-outqueries       7379851
# noerror-answers 6718125
# noping-outqueries       0
# nsset-invalidations     2641
# nxdomain-answers        4369596
# over-capacity-drops     0
# qa-latency      33332
# questions       11097751
# resource-limits 2314
# server-parse-errors     0
# servfail-answers        10038
# spoof-prevents  0
# tcp-client-overflow     0
# tcp-questions   20830
# unauthorized-tcp        0
# unauthorized-udp        0
# unexpected-packets      0
# cache-entries   710696
# cache-hits      548700
# cache-misses    5155665
# concurrent-queries      1
# negcache-entries        45659
# nsspeeds-entries        3023
# packetcache-entries     271504
# packetcache-hits        5393402
# packetcache-misses      5683536
# sys-msec        1600408
# tcp-clients     0
# throttle-entries        56
# uptime  4231654
# user-msec       3423357

if (!empty($agent_data['app']['powerdns_recursor'])) {
    $app_id = discover_app($device, 'powerdns_recursor');

    foreach (explode("\n", $agent_data['app']['powerdns_recursor']) as $line) {
        [$key, $value] = explode("\t", $line, 2);
        $powerdns_recursor[$key] = $value;
    }

    $data = [
      'outQ_all'           => $powerdns_recursor['all-outqueries'],
      'outQ_dont'          => $powerdns_recursor['dont-outqueries'],
      'outQ_tcp'           => $powerdns_recursor['tcp-outqueries'],
      'outQ_throttled'     => $powerdns_recursor['throttled-out'],
      'outQ_ipv6'          => $powerdns_recursor['ipv6-outqueries'],
      'outQ_noEDNS'        => $powerdns_recursor['noedns-outqueries'],
      'outQ_noPing'        => $powerdns_recursor['noping-outqueries'],
      'drop_reqDlgOnly'    => $powerdns_recursor['dlg-only-drops'],
      'drop_overCap'       => $powerdns_recursor['over-capacity-drops'],
      'timeoutOutgoing'    => $powerdns_recursor['outgoing-timeouts'],
      'unreachables'       => $powerdns_recursor['unreachables'],
      'answers_1s'         => $powerdns_recursor['answers-slow'],
      'answers_1ms'        => $powerdns_recursor['answers0-1'],
      'answers_10ms'       => $powerdns_recursor['answers1-10'],
      'answers_100ms'      => $powerdns_recursor['answers10-100'],
      'answers_1000ms'     => $powerdns_recursor['answers100-1000'],
      'answers_noerror'    => $powerdns_recursor['noerror-answers'],
      'answers_nxdomain'   => $powerdns_recursor['nxdomain-answers'],
      'answers_servfail'   => $powerdns_recursor['servfail-answers'],
      'caseMismatch'       => $powerdns_recursor['case-mismatches'],
      'chainResends'       => $powerdns_recursor['chain-resends'],
      'clientParseErrors'  => $powerdns_recursor['client-parse-errors'],
      'ednsPingMatch'      => $powerdns_recursor['edns-ping-matches'],
      'ednsPingMismatch'   => $powerdns_recursor['edns-ping-mismatches'],
      'noPacketError'      => $powerdns_recursor['no-packet-error'],
      'nssetInvalidations' => $powerdns_recursor['nsset-invalidations'],
      'qaLatency'          => $powerdns_recursor['qa-latency'],
      'questions'          => $powerdns_recursor['questions'],
      'resourceLimits'     => $powerdns_recursor['resource-limits'],
      'serverParseErrors'  => $powerdns_recursor['server-parse-errors'],
      'spoofPrevents'      => $powerdns_recursor['spoof-prevents'],
      'tcpClientOverflow'  => $powerdns_recursor['tcp-client-overflow'],
      'tcpQuestions'       => $powerdns_recursor['tcp-questions'],
      'tcpUnauthorized'    => $powerdns_recursor['unauthorized-tcp'],
      'udpUnauthorized'    => $powerdns_recursor['unauthorized-udp'],
      'cacheEntries'       => $powerdns_recursor['cache-entries'],
      'cacheHits'          => $powerdns_recursor['cache-hits'],
      'cacheMisses'        => $powerdns_recursor['cache-misses'],
      'negcacheEntries'    => $powerdns_recursor['negcache-entries'],
      'nsSpeedsEntries'    => $powerdns_recursor['nsspeeds-entries'],
      'packetCacheEntries' => $powerdns_recursor['packetcache-entries'],
      'packetCacheHits'    => $powerdns_recursor['packetcache-hits'],
      'packetCacheMisses'  => $powerdns_recursor['packetcache-misses'],
      'unexpectedPkts'     => $powerdns_recursor['unexpected-packets'],
      'concurrentQueries'  => $powerdns_recursor['concurrent-queries'],
      'tcpClients'         => $powerdns_recursor['tcp-clients'],
      'throttleEntries'    => $powerdns_recursor['throttle-entries'],
      'uptime'             => $powerdns_recursor['uptime'],
      'cpuTimeSys'         => $powerdns_recursor['sys-msec'],
      'cpuTimeUser'        => $powerdns_recursor['user-msec']];

    rrdtool_update_ng($device, 'powerdns-recursor', $data, $app_id);

    update_application($app_id, $data);

    unset($powerdns_recursor);
}

// EOF
