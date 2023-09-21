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

if (!empty($wmi['exchange']['services'])) {
    /* TODO:
        - Perform more testing with Exchange 2003, 2007, 2013
        - Review CAS counters
        - Review Information Store counters
        - Review Transport Role counters
        - Add Unified Messaging counters
    */
    echo(" Exchange:\n   ");

    // Exchange Client Access - Active Sync

    $wql                                  = "SELECT * FROM Win32_PerfFormattedData_MSExchangeActiveSync_MSExchangeActiveSync";
    $wmi['exchange']['cas']['activesync'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['exchange']['cas']['activesync']) {
        $app_found['exchange'] = TRUE;
        echo("Active Sync; ");

        rrdtool_update_ng($device, 'exchange-as', [
          'synccommandspending' => $wmi['exchange']['cas']['activesync']['SyncCommandsPending'],
          'pingcommandspending' => $wmi['exchange']['cas']['activesync']['PingCommandsPending'],
          'currentrequests'     => $wmi['exchange']['cas']['activesync']['CurrentRequests'],
        ]);

        unset($wmi['exchange']['cas']['activesync']);
    }

    // Exchange Client Access - Autodiscover

    $wql                                    = "SELECT * FROM Win32_PerfFormattedData_MSExchangeAutodiscover_MSExchangeAutodiscover";
    $wmi['exchange']['cas']['autodiscover'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['exchange']['cas']['autodiscover']) {
        $app_found['exchange'] = TRUE;
        echo("Auto Discover; ");

        rrdtool_update_ng($device, 'exchange-as', [
          'totalrequests'  => $wmi['exchange']['cas']['autodiscover']['TotalRequests'],
          'errorresponses' => $wmi['exchange']['cas']['autodiscover']['ErrorResponses'],
        ]);

        unset($wmi['exchange']['cas']['autodiscover']);
    }

    // Exchange Client Access - Offline Address Book

    $wql                           = "SELECT * FROM Win32_PerfFormattedData_MSExchangeFDSOAB_MSExchangeFDSOAB WHERE Name='_total'";
    $wmi['exchange']['cas']['oab'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['exchange']['cas']['oab']) {
        $app_found['exchange'] = TRUE;
        echo("OAB; ");

        rrdtool_update_ng($device, 'exchange-oab', [
          'dltasksqueued'    => $wmi['exchange']['cas']['oab']['DownloadTaskQueued'],
          'dltaskscompleted' => $wmi['exchange']['cas']['oab']['DownloadTasksCompleted'],
        ]);

        unset($wmi['exchange']['cas']['oab']);
    }

    // Exchange Client Access - Outlook Web App

    $wql                           = "SELECT * FROM Win32_PerfFormattedData_MSExchangeOWA_MSExchangeOWA";
    $wmi['exchange']['cas']['owa'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['exchange']['cas']['owa']) {
        $app_found['exchange'] = TRUE;
        echo("OWA; ");

        rrdtool_update_ng($device, 'exchange-owa', [
          'currentuniqueusers' => $wmi['exchange']['cas']['owa']['CurrentUniqueUsers'],
          'avgresponsetime'    => $wmi['exchange']['cas']['owa']['AverageResponseTime'],
          'avgsearchtime'      => $wmi['exchange']['cas']['owa']['AverageSearchTime'],
        ]);

        unset($wmi['exchange']['cas']['owa']);
    }

    // Exchange Hub Transport - Queues

    $wql                                    = "SELECT * FROM Win32_PerfFormattedData_MSExchangeTransportQueues_MSExchangeTransportQueues WHERE Name='_total'";
    $wmi['exchange']['transport']['queues'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['exchange']['transport']['queues']) {
        $app_found['exchange'] = TRUE;
        echo("Transport Queues; ");

        rrdtool_update_ng($device, 'exchange-tqs', [
          'aggregatequeue'  => $wmi['exchange']['transport']['queues']['AggregateDeliveryQueueLengthAllQueues'],
          'deliveryqpersec' => $wmi['exchange']['transport']['queues']['ItemsQueuedforDeliveryPerSecond'],
          'mbdeliverqueue'  => $wmi['exchange']['transport']['queues']['ActiveMailboxDeliveryQueueLength'],
          'submissionqueue' => $wmi['exchange']['transport']['queues']['SubmissionQueueLength'],
        ]);

        unset($wmi['exchange']['transport']['queues']);
    }

    // Exchange Hub Transport - SMTP SEND

    $wql                                  = "SELECT * FROM Win32_PerfFormattedData_MSExchangeTransportSmtpSend_MSExchangeTransportSmtpSend WHERE Name='_total'";
    $wmi['exchange']['transport']['smtp'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['exchange']['transport']['smtp']) {
        $app_found['exchange'] = TRUE;
        echo("SMTP; ");

        rrdtool_update_ng($device, 'exchange-smtp', [
          'currentconnections' => $wmi['exchange']['transport']['smtp']['ConnectionsCurrent'],
          'msgsentpersec'      => $wmi['exchange']['transport']['smtp']['MessagesSentPersec'],
        ]);

        unset($wmi['exchange']['transport']['queues']);
    }

    // Exchange Information Store

    $wql                              = "SELECT * FROM Win32_PerfFormattedData_MSExchangeIS_MSExchangeIS";
    $wmi['exchange']['mailbox']['is'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['exchange']['mailbox']['is']) {
        $app_found['exchange'] = TRUE;
        echo("IS; ");

        rrdtool_update_ng($device, 'exchange-is', [
          'activeconcount'    => $wmi['exchange']['mailbox']['is']['ActiveConnectionCount'],
          'usercount'         => $wmi['exchange']['mailbox']['is']['UserCount'],
          'rpcrequests'       => $wmi['exchange']['mailbox']['is']['RPCRequests'],
          'rpcavglatency'     => $wmi['exchange']['mailbox']['is']['RPCAveragedLatency'],
          'clientrpcfailbusy' => $wmi['exchange']['mailbox']['is']['ClientRPCsFailedServerTooBusy'],
        ]);

        unset($wmi['exchange']['mailbox']['is']);
    }

    // Exchange Information Store - Mailbox Data

    $wql                                   = "SELECT * FROM Win32_PerfFormattedData_MSExchangeIS_MSExchangeISMailbox WHERE Name='_total'";
    $wmi['exchange']['mailbox']['mailbox'] = wmi_parse(wmi_query($wql, $override), TRUE);

    if ($wmi['exchange']['mailbox']['mailbox']) {
        $app_found['exchange'] = TRUE;
        echo("Mailbox; ");

        rrdtool_update_ng($device, 'exchange-mailbox', [
          'rpcavglatency' => $wmi['exchange']['mailbox']['mailbox']['RPCAverageLatency'],
          'msgqueued'     => $wmi['exchange']['mailbox']['mailbox']['MessagesQueuedForSubmission'],
          'msgsentsec'    => $wmi['exchange']['mailbox']['mailbox']['MessagesSentPersec'],
          'msgdeliversec' => $wmi['exchange']['mailbox']['mailbox']['MessagesDeliveredPersec'],
          'msgsubmitsec'  => $wmi['exchange']['mailbox']['mailbox']['MessagesSubmittedPersec'],
        ]);
    }

    echo("\n");
}

if ($app_found['exchange'] == TRUE) {
    $app_id = discover_app($device, 'exchange');
    update_application($app_id, []);
}

unset ($wmi['exchange']);

// EOF
