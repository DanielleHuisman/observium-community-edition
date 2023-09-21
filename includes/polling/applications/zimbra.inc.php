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

if (!empty($agent_data['app']['zimbra'])) {
    $app_id = discover_app($device, 'zimbra');

    update_application($app_id, []);

    foreach ($agent_data['app']['zimbra'] as $key => $value) {
        # key is "vm", "mysql" etc, value is the csv output
        $zimbra[$key] = parse_csv($value);
    }

    if (is_array($zimbra['mtaqueue'])) {
        /*
        timestamp, KBytes, requests
        04/23/2013 18:19:30, 0, 0
        */
        rrdtool_update_ng($device, 'zimbra-mtaqueue', ['kBytes' => $zimbra['mtaqueue'][0]['KBytes'], 'requests' => $zimbra['mtaqueue'][0]['requests']]);
    }

    if (is_array($zimbra['fd'])) {
        /*
        timestamp, fd_count, mailboxd_fd_count
        04/23/2013 18:40:53, 5216, 1451
        */

        rrdtool_update_ng($device, 'zimbra-fd', ['fdSystem' => $zimbra['mtaqueue'][0]['fd_count'], 'fdMailboxd' => $zimbra['mtaqueue'][0]['mailboxd_fd_count']]);
    }

    if (is_array($zimbra['threads'])) {
        /*
        timestamp,AnonymousIoService,CloudRoutingReaderThread,GC,ImapSSLServer,ImapServer,LmtpServer,Pop3SSLServer,Pop3Server,ScheduledTask,SocketAcceptor,Thread,Timer,btpool,pool,other,total
        04/23/2013 01:03:10,0,0,0,6,1,3,0,1,2,0,3,2,0,0,72,90
        */

        rrdtool_update_ng($device, 'zimbra-threads', [
          'AnonymousIoService' => $zimbra['threads'][0]['AnonymousIoService'],
          'CloudRoutingReader' => $zimbra['threads'][0]['CloudRoutingReaderThread'],
          'GC'                 => $zimbra['threads'][0]['GC'],
          'ImapSSLServer'      => $zimbra['threads'][0]['ImapSSLServer'],
          'ImapServer'         => $zimbra['threads'][0]['ImapServer'],
          'LmtpServer'         => $zimbra['threads'][0]['LmtpServer'],
          'Pop3SSLServer'      => $zimbra['threads'][0]['Pop3SSLServer'],
          'Pop3Server'         => $zimbra['threads'][0]['Pop3Server'],
          'ScheduledTask'      => $zimbra['threads'][0]['ScheduledTask'],
          'SocketAcceptor'     => $zimbra['threads'][0]['SocketAcceptor'],
          'Thread'             => $zimbra['threads'][0]['Thread'],
          'Timer'              => $zimbra['threads'][0]['Timer'],
          'btpool'             => $zimbra['threads'][0]['btpool'],
          'pool'               => $zimbra['threads'][0]['pool'],
          'other'              => $zimbra['threads'][0]['other'],
          'total'              => $zimbra['threads'][0]['total'],
        ]);
    }

    if (is_array($zimbra['mailboxd'])) {
        /*
        timestamp,lmtp_rcvd_msgs,lmtp_rcvd_bytes,lmtp_rcvd_rcpt,lmtp_dlvd_msgs,lmtp_dlvd_bytes,db_conn_count,db_conn_ms_avg,ldap_dc_count,ldap_dc_ms_avg,mbox_add_msg_count,mbox_add_msg_ms_avg,mbox_get_count,mbox_get_ms_avg,mbox_cache,mbox_msg_cache,mbox_item_cache,soap_count,soap_ms_avg,imap_count,imap_ms_avg,pop_count,pop_ms_avg,idx_wrt_avg,idx_wrt_opened,idx_wrt_opened_cache_hit,calcache_hit,calcache_mem_hit,calcache_lru_size,idx_bytes_written,idx_bytes_written_avg,idx_bytes_read,idx_bytes_read_avg,bis_read,bis_seek_rate,db_pool_size,innodb_bp_hit_rate,lmtp_conn,lmtp_threads,pop_conn,pop_threads,pop_ssl_conn,pop_ssl_threads,imap_conn,imap_threads,imap_ssl_conn,imap_ssl_threads,http_idle_threads,http_threads,soap_sessions,mbox_cache_size,msg_cache_size,fd_cache_size,fd_cache_hit_rate,acl_cache_hit_rate,account_cache_size,account_cache_hit_rate,cos_cache_size,cos_cache_hit_rate,domain_cache_size,domain_cache_hit_rate,server_cache_size,server_cache_hit_rate,ucservice_cache_size,ucservice_cache_hit_rate,zimlet_cache_size,zimlet_cache_hit_rate,group_cache_size,group_cache_hit_rate,xmpp_cache_size,xmpp_cache_hit_rate,gc_parnew_count,gc_parnew_ms,gc_concurrentmarksweep_count,gc_concurrentmarksweep_ms,gc_minor_count,gc_minor_ms,gc_major_count,gc_major_ms,mpool_code_cache_used,mpool_code_cache_free,mpool_par_eden_space_used,mpool_par_eden_space_free,mpool_par_survivor_space_used,mpool_par_survivor_space_free,mpool_cms_old_gen_used,mpool_cms_old_gen_free,mpool_cms_perm_gen_used,mpool_cms_perm_gen_free,heap_used,heap_free
        04/24/2013 00:23:23,1,6506,1,1,6506,81,0.32098765432098764,1,0.0,1,36.0,84,0.0,100.0,50.0,34.59119496855346,0,0.0,138,10.405797101449275,0,0.0,0.0,0,0,0.0,0.0,0.0,0,0.0,0,0.0,1,0.0,0,1000,0,1,0,1,0,0,1,1,24,3,2,18,2,132,2000,1000,96.99490867673337,99.73302998524733,133,99.78571547607365,1,99.02449324324324,7,99.24445818173449,1,99.99996904482866,0,0.0,28,57.52625437572929,31,67.36491311592478,0,0.0,29250,487518,1382,103802,29250,487518,1382,103802,26258688,480000,83049792,24429248,13369344,0,216226736,186426448,125015776,9201952,312645872,210855696
        */

        foreach (array_keys($zimbra['mailboxd'][0]) as $key) {
            for ($line = 0; $line < count($zimbra['mailboxd']); $line++) {
                // zmstat writes these CSV files every 30 seconds. The agent passes us the 10 last values, so we have the full 5 minute range.
                // some of the variables should be added up to reach the total, but for most (gauges) we just want the latest value.
                switch ($key) {
                    case 'lmtp_rcvd_msgs':
                    case 'lmtp_rcvd_bytes':
                    case 'lmtp_rcvd_rcpt':
                    case 'lmtp_dlvd_msgs':
                    case 'lmtp_dlvd_bytes':
                    case 'mbox_add_msg_count':
                    case 'mbox_get_count':
                    case 'soap_count':
                    case 'imap_count':
                    case 'pop_count':
                    case 'idx_bytes_written':
                    case 'idx_bytes_read':
                    case 'bis_read':
                    case 'bis_seek_rate':
                    case 'gc_parnew_count':
                    case 'gc_parnew_ms':
                    case 'gc_concurrentmarksweep_count':
                    case 'gc_concurrentmarksweep_ms':
                        $zimbra['mailboxd-total'][$key] += $zimbra['mailboxd'][$line][$key];
                        break;
                    default:
                        $zimbra['mailboxd-total'][$key] = $zimbra['mailboxd'][$line][$key];
                        break;
                }
            }
        }

        rrdtool_update_ng($device, 'zimbra-mailboxd', [
          'lmtpRcvdMsgs'        => $zimbra['mailboxd-total']['lmtp_rcvd_msgs'],
          'lmtpRcvdBytes'       => $zimbra['mailboxd-total']['lmtp_rcvd_bytes'],
          'lmtpRcvdRcpt'        => $zimbra['mailboxd-total']['lmtp_rcvd_rcpt'],
          'lmtpDlvdMsgs'        => $zimbra['mailboxd-total']['lmtp_dlvd_msgs'],
          'lmtpDlvdBytes'       => $zimbra['mailboxd-total']['lmtp_dlvd_bytes'],
          'dbConnCount'         => $zimbra['mailboxd-total']['db_conn_count'],
          'dbConnMsAvg'         => $zimbra['mailboxd-total']['db_conn_ms_avg'],
          'ldapDcCount'         => $zimbra['mailboxd-total']['ldap_dc_count'],
          'ldapDcMsAvg'         => $zimbra['mailboxd-total']['ldap_dc_ms_avg'],
          'mboxAddMsgCount'     => $zimbra['mailboxd-total']['mbox_add_msg_count'],
          'mboxAddMsgMsAvg'     => $zimbra['mailboxd-total']['mbox_add_msg_ms_avg'],
          'mboxGetCount'        => $zimbra['mailboxd-total']['mbox_get_count'],
          'mboxGetMsAvg'        => $zimbra['mailboxd-total']['mbox_get_ms_avg'],
          'mboxCache'           => $zimbra['mailboxd-total']['mbox_cache'],
          'mboxMsgCache'        => $zimbra['mailboxd-total']['mbox_msg_cache'],
          'mboxItemCache'       => $zimbra['mailboxd-total']['mbox_item_cache'],
          'soapCount'           => $zimbra['mailboxd-total']['soap_count'],
          'soapMsAvg'           => $zimbra['mailboxd-total']['soap_ms_avg'],
          'imapCount'           => $zimbra['mailboxd-total']['imap_count'],
          'imapMsAvg'           => $zimbra['mailboxd-total']['imap_ms_avg'],
          'popCount'            => $zimbra['mailboxd-total']['pop_count'],
          'popMsAvg'            => $zimbra['mailboxd-total']['pop_ms_avg'],
          'idxWrtAvg'           => $zimbra['mailboxd-total']['idx_wrt_avg'],
          'idxWrtOpened'        => $zimbra['mailboxd-total']['idx_wrt_opened'],
          'idxWrtOpenedCacheHt' => $zimbra['mailboxd-total']['idx_wrt_opened_cache_hit'],
          'calcacheHit'         => $zimbra['mailboxd-total']['calcache_hit'],
          'calcacheMemHit'      => $zimbra['mailboxd-total']['calcache_mem_hit'],
          'calcacheLruSize'     => $zimbra['mailboxd-total']['calcache_lru_size'],
          'idxBytesWritten'     => $zimbra['mailboxd-total']['idx_bytes_written'],
          'idxBytesWrittenAvg'  => $zimbra['mailboxd-total']['idx_bytes_written_avg'],
          'idxBytesRead'        => $zimbra['mailboxd-total']['idx_bytes_read'],
          'idxBytesReadAvg'     => $zimbra['mailboxd-total']['idx_bytes_read_avg'],
          'bisRead'             => $zimbra['mailboxd-total']['bis_read'],
          'bisSeekRate'         => $zimbra['mailboxd-total']['bis_seek_rate'],
          'dbPoolSize'          => $zimbra['mailboxd-total']['db_pool_size'],
          'innodbBpHitRate'     => $zimbra['mailboxd-total']['innodb_bp_hit_rate'],
          'lmtpConn'            => $zimbra['mailboxd-total']['lmtp_conn'],
          'lmtpThreads'         => $zimbra['mailboxd-total']['lmtp_threads'],
          'popConn'             => $zimbra['mailboxd-total']['pop_conn'],
          'popThreads'          => $zimbra['mailboxd-total']['pop_threads'],
          'popSslConn'          => $zimbra['mailboxd-total']['pop_ssl_conn'],
          'popSslThreads'       => $zimbra['mailboxd-total']['pop_ssl_threads'],
          'imapConn'            => $zimbra['mailboxd-total']['imap_conn'],
          'imapThreads'         => $zimbra['mailboxd-total']['imap_threads'],
          'imapSslConn'         => $zimbra['mailboxd-total']['imap_ssl_conn'],
          'imapSslThreads'      => $zimbra['mailboxd-total']['imap_ssl_threads'],
          'httpIdleThreads'     => $zimbra['mailboxd-total']['http_idle_threads'],
          'httpThreads'         => $zimbra['mailboxd-total']['http_threads'],
          'soapSessions'        => $zimbra['mailboxd-total']['soap_sessions'],
          'mboxCacheSize'       => $zimbra['mailboxd-total']['mbox_cache_size'],
          'msgCacheSize'        => $zimbra['mailboxd-total']['msg_cache_size'],
          'fdCacheSize'         => $zimbra['mailboxd-total']['fd_cache_size'],
          'fdCacheHitRate'      => $zimbra['mailboxd-total']['fd_cache_hit_rate'],
          'aclCacheHitRate'     => $zimbra['mailboxd-total']['acl_cache_hit_rate'],
          'accountCacheSize'    => $zimbra['mailboxd-total']['account_cache_size'],
          'accountCacheHitRate' => $zimbra['mailboxd-total']['account_cache_hit_rate'],
          'cosCacheSize'        => $zimbra['mailboxd-total']['cos_cache_size'],
          'cosCacheHitRate'     => $zimbra['mailboxd-total']['cos_cache_hit_rate'],
          'domainCacheSize'     => $zimbra['mailboxd-total']['domain_cache_size'],
          'domainCacheHitRate'  => $zimbra['mailboxd-total']['domain_cache_hit_rate'],
          'serverCacheSize'     => $zimbra['mailboxd-total']['server_cache_size'],
          'serverCacheHitRate'  => $zimbra['mailboxd-total']['server_cache_hit_rate'],
          'ucsvcCacheSize'      => $zimbra['mailboxd-total']['ucservice_cache_size'],
          'ucsvcCacheHitRate'   => $zimbra['mailboxd-total']['ucservice_cache_hit_rate'],
          'zimletCacheSize'     => $zimbra['mailboxd-total']['zimlet_cache_size'],
          'zimletCacheHitRate'  => $zimbra['mailboxd-total']['zimlet_cache_hit_rate'],
          'groupCacheSize'      => $zimbra['mailboxd-total']['group_cache_size'],
          'groupCacheHitRate'   => $zimbra['mailboxd-total']['group_cache_hit_rate'],
          'xmppCacheSize'       => $zimbra['mailboxd-total']['xmpp_cache_size'],
          'xmppCacheHitRate'    => $zimbra['mailboxd-total']['xmpp_cache_hit_rate'],
          'gcParnewCount'       => $zimbra['mailboxd-total']['gc_parnew_count'],
          'gcParnewMs'          => $zimbra['mailboxd-total']['gc_parnew_ms'],
          'gcConcmarksweepCnt'  => $zimbra['mailboxd-total']['gc_concurrentmarksweep_count'],
          'gcConcmarksweepMs'   => $zimbra['mailboxd-total']['gc_concurrentmarksweep_ms'],
          'gcMinorCount'        => $zimbra['mailboxd-total']['gc_minor_count'],
          'gcMinorMs'           => $zimbra['mailboxd-total']['gc_minor_ms'],
          'gcMajorCount'        => $zimbra['mailboxd-total']['gc_major_count'],
          'gcMajorMs'           => $zimbra['mailboxd-total']['gc_major_ms'],
          'mpoolCodeCacheUsed'  => $zimbra['mailboxd-total']['mpool_code_cache_used'],
          'mpoolCodeCacheFree'  => $zimbra['mailboxd-total']['mpool_code_cache_free'],
          'mpoolParEdenSpcUsed' => $zimbra['mailboxd-total']['mpool_par_eden_space_used'],
          'mpoolParEdenSpcFree' => $zimbra['mailboxd-total']['mpool_par_eden_space_free'],
          'mpoolParSurvSpcUsed' => $zimbra['mailboxd-total']['mpool_par_survivor_space_used'],
          'mpoolParSurvSpcFree' => $zimbra['mailboxd-total']['mpool_par_survivor_space_free'],
          'mpoolCmsOldGenUsed'  => $zimbra['mailboxd-total']['mpool_cms_old_gen_used'],
          'mpoolCmsOldGenFree'  => $zimbra['mailboxd-total']['mpool_cms_old_gen_free'],
          'mpoolCmsPermGenUsed' => $zimbra['mailboxd-total']['mpool_cms_perm_gen_used'],
          'mpoolCmsPermGenFree' => $zimbra['mailboxd-total']['mpool_cms_perm_gen_free'],
          'heapUsed'            => $zimbra['mailboxd-total']['heap_used'],
          'heapFree'            => $zimbra['mailboxd-total']['heap_free'],
        ]);
    }

    if (is_array($zimbra['proc'])) {
        /*
        timestamp, system, user, sys, idle, iowait, mailbox, mailbox-total-cpu, mailbox-utime, mailbox-stime, mailbox-totalMB, mailbox-rssMB, mailbox-sharedMB, mailbox-process-count, mysql, mysql-total-cpu, mysql-utime, mysql-stime, mysql-totalMB, mysql-rssMB, mysql-sharedMB, mysql-process-count, convertd, convertd-total-cpu, convertd-utime, convertd-stime, convertd-totalMB, convertd-rssMB, convertd-sharedMB, convertd-process-count, ldap, ldap-total-cpu, ldap-utime, ldap-stime, ldap-totalMB, ldap-rssMB, ldap-sharedMB, ldap-process-count, postfix, postfix-total-cpu, postfix-utime, postfix-stime, postfix-totalMB, postfix-rssMB, postfix-sharedMB, postfix-process-count, amavis, amavis-total-cpu, amavis-utime, amavis-stime, amavis-totalMB, amavis-rssMB, amavis-sharedMB, amavis-process-count, clam, clam-total-cpu, clam-utime, clam-stime, clam-totalMB, clam-rssMB, clam-sharedMB, clam-process-count, zmstat, zmstat-total-cpu, zmstat-utime, zmstat-stime, zmstat-totalMB, zmstat-rssMB, zmstat-sharedMB, zmstat-process-count
        04/27/2013 18:05:30, system, 3.9, 1.0, 94.4, 0.7, mailbox, 0.4, 0.4, 0.0, 1074.9, 863.8, 5.8, 1, mysql, 0.1, 0.1, 0.0, 956.5, 785.3, 4.1, 1, convertd, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0, ldap, 0.0, 0.0, 0.0, 82300.6, 50.0, 4.5, 1, postfix, 0.0, 0.0, 0.0, 54.1, 1.5, 0.6, 5, amavis, 0.0, 0.0, 0.0, 150.8, 52.3, 3.4, 11, clam, 0.0, 0.0, 0.0, 42.4, 1.9, 1.0, 2, zmstat, 0.1, 0.1, 0.0, 4.0, 0.6, 0.4, 15
        */

        foreach (['mailbox', 'mysql', 'convertd', 'ldap', 'postfix', 'amavis', 'clam', 'zmstat'] as $app) {
            if ($zimbra['proc'][0][$app . '-process-count']) {
                rrdtool_update_ng($device, 'zimbra-proc', [
                  'totalCPU'     => $zimbra['proc'][0]["$app-total-cpu"],
                  'utime'        => $zimbra['proc'][0]["$app-utime"],
                  'stime'        => $zimbra['proc'][0]["$app-stime"],
                  'totalMB'      => $zimbra['proc'][0]["$app-totalMB"],
                  'rssMB'        => $zimbra['proc'][0]["$app-rssMB"],
                  'sharedMB'     => $zimbra['proc'][0]["$app-sharedMB"],
                  'processCount' => $zimbra['proc'][0]["$app-process-count"],
                ],                $app);
            }
        }
    }

    /// FIXME - All commands listed in a year of my CSVs - may be incomplete?
    $commandlist['imap'] = ['APPEND', 'AUTHENTICATE', 'BASIC', 'CAPABILITY', 'CHECK', 'CLOSE', 'CREATE', 'DELETE', 'ENABLE', 'EXAMINE', 'EXPUNGE', 'FETCH', 'GETACL', 'GETQUOTAROOT',
                            'ID', 'IDLE', 'LIST', 'LOGIN', 'LOGOUT', 'LSUB', 'MYRIGHTS', 'NAMESPACE', 'NOOP', 'RENAME', 'SEARCH', 'SELECT', 'STARTTLS', 'STATUS', 'STORE', 'SUBSCRIBE', 'UID', 'UNSELECT',
                            'UNSUBSCRIBE', 'XLIST'];

    $commandlist['ldap'] = ['CREATE_ENTRY', 'DELETE_ENTRY', 'GET_CONN', 'GET_ENTRY', 'GET_SCHEMA', 'MODIFY_ATTRS', 'OPEN_CONN', 'SEARCH_ACCOUNT_BY_ID', 'SEARCH_ACCOUNT_BY_NAME',
                            'SEARCH_ACCOUNTS_BY_GRANTS', 'SEARCH_ACCOUNTS_HOMED_ON_SERVER', 'SEARCH_ACCOUNTS_HOMED_ON_SERVER_ACCOUNTS_ONLY', 'SEARCH_ADMIN_ACCOUNT_BY_RDN', 'SEARCH_ADMIN_SEARCH',
                            'SEARCH_ALL_ALIASES', 'SEARCH_ALL_CALENDAR_RESOURCES', 'SEARCH_ALL_DATA_SOURCES', 'SEARCH_ALL_GROUPS', 'SEARCH_ALL_IDENTITIES', 'SEARCH_ALL_MIME_ENTRIES',
                            'SEARCH_ALL_NON_SYSTEM_ACCOUNTS', 'SEARCH_ALL_NON_SYSTEM_ARCHIVING_ACCOUNTS', 'SEARCH_ALL_NON_SYSTEM_INTERNAL_ACCOUNTS', 'SEARCH_ALL_SERVERS', 'SEARCH_ALL_SIGNATURES',
                            'SEARCH_ALL_UC_SERVICES', 'SEARCH_ALL_ZIMLETS', 'SEARCH_DATA_SOURCE_BY_ID', 'SEARCH_DISTRIBUTION_LIST_BY_ID', 'SEARCH_DISTRIBUTION_LIST_BY_NAME', 'SEARCH_DISTRIBUTION_LISTS_BY_MEMBER_ADDRS',
                            'SEARCH_DOMAIN_BY_ID', 'SEARCH_DOMAIN_BY_NAME', 'SEARCH_DOMAIN_BY_VIRTUAL_HOSTNAME', 'SEARCH_DYNAMIC_GROUP_BY_NAME', 'SEARCH_DYNAMIC_GROUPS_STATIC_UNIT_BY_MEMBER_ADDR',
                            'SEARCH_EXTERNAL_ACCOUNTS_HOMED_ON_SERVER', 'SEARCH_GAL_SEARCH', 'SEARCH_GROUP_BY_ID', 'SEARCH_GROUP_BY_NAME', 'SEARCH_LDAP_AUTHENTICATE', 'SEARCH_MIME_ENTRY_BY_MIME_TYPE',
                            'SEARCH_SEARCH_GRANTEE', 'SEARCH_SERVER_BY_SERVICE', 'SEARCH_SHARE_LOCATOR_BY_ID', 'SEARCH_TODO'];

    $commandlist['pop3'] = ['DELE', 'LIST', 'RETR', 'QUIT', 'STAT'];

    $commandlist['sync'] = ['FolderSync', 'GetAttachment', 'GetItemEstimate', 'ItemOperations', 'MeetingResponse', 'MoveItems', 'Ping_after_suspend', 'Ping_before_suspend',
                            'Provision', 'Search', 'Settings', 'Sync'];

    $commandlist['soap'] = ['ActivateLicenseRequest', 'AddAccountAliasRequest', 'AddDistributionListAliasRequest', 'AddDistributionListMemberRequest', 'AddMsgRequest',
                            'ApplyFilterRulesRequest', 'AuthRequest', 'AutoCompleteGalRequest', 'AutoCompleteRequest', 'BackupQueryRequest', 'BackupRequest', 'BrowseRequest', 'BulkImportAccountsRequest',
                            'CancelAppointmentRequest', 'CancelTaskRequest', 'ChangePasswordRequest', 'CheckAuthConfigRequest', 'CheckLicenseRequest', 'CheckPermissionRequest', 'CheckRecurConflictsRequest',
                            'CheckRightsRequest', 'CheckSpellingRequest', 'ClearCookieRequest', 'ComputeAggregateQuotaUsageRequest', 'ContactActionRequest.delete', 'ContactActionRequest.move', 'ContactActionRequest.update',
                            'ConvActionRequest.delete', 'ConvActionRequest.flag', 'ConvActionRequest.!flag', 'ConvActionRequest.move', 'ConvActionRequest.read', 'ConvActionRequest.!read', 'ConvActionRequest.spam',
                            'ConvActionRequest.!spam', 'ConvActionRequest.trash', 'CounterAppointmentRequest', 'CountObjectsRequest', 'CreateAccountRequest', 'CreateAppointmentExceptionRequest', 'CreateAppointmentRequest',
                            'CreateCalendarResourceRequest', 'CreateContactRequest', 'CreateDistributionListRequest', 'CreateDomainRequest', 'CreateFolderRequest', 'CreateGalSyncAccountRequest', 'CreateIdentityRequest',
                            'CreateMountpointRequest', 'CreateSignatureRequest', 'CreateTagRequest', 'CreateTaskRequest', 'CreateWaitSetRequest', 'DelegateAuthRequest', 'DeleteAccountRequest', 'DeleteCalendarResourceRequest',
                            'DeleteIdentityRequest', 'DeleteSignatureRequest', 'DestroyWaitSetRequest', 'DiscoverRightsRequest', 'DismissCalendarItemAlarmRequest', 'EndSessionRequest', 'FlushCacheRequest', 'FolderActionRequest.check',
                            'FolderActionRequest.!check', 'FolderActionRequest.color', 'FolderActionRequest.delete', 'FolderActionRequest.empty', 'FolderActionRequest.grant', 'FolderActionRequest.!grant', 'FolderActionRequest.move',
                            'FolderActionRequest.read', 'FolderActionRequest.rename', 'FolderActionRequest.retentionpolicy', 'FolderActionRequest.revokeorphangrants', 'FolderActionRequest.trash', 'FolderActionRequest.update',
                            'ForwardAppointmentRequest', 'GenerateBulkProvisionFileFromLDAPRequest', 'GetAccountDistributionListsRequest', 'GetAccountInfoRequest', 'GetAccountMembershipRequest', 'GetAccountRequest',
                            'GetAdminConsoleUICompRequest', 'GetAdminExtensionZimletsRequest', 'GetAdminSavedSearchesRequest', 'GetAggregateQuotaUsageOnServerRequest', 'GetAllConfigRequest', 'GetAllCosRequest', 'GetAllRightsRequest',
                            'GetAllServersRequest', 'GetAllSkinsRequest', 'GetAllUCProvidersRequest', 'GetAllUCServicesRequest', 'GetAllVolumesRequest', 'GetAllZimletsRequest', 'GetAppointmentRequest', 'GetAttributeInfoRequest',
                            'GetAvailableCsvFormatsRequest', 'GetAvailableLocalesRequest', 'GetAvailableSkinsRequest', 'GetBulkIMAPImportTaskListRequest', 'GetCalendarResourceRequest', 'GetCertRequest', 'GetConfigRequest',
                            'GetContactsRequest', 'GetConvRequest', 'GetCosRequest', 'GetCreateObjectAttrsRequest', 'GetCSRRequest', 'GetCurrentVolumesRequest', 'GetDataSourcesRequest', 'GetDevicesCountRequest', 'GetDeviceStatusRequest',
                            'GetDistributionListMembershipRequest', 'GetDistributionListMembersRequest', 'GetDistributionListRequest', 'GetDomainInfoRequest', 'GetDomainRequest', 'GetEffectiveRightsRequest', 'GetFilterRulesRequest',
                            'GetFolderRequest', 'GetFreeBusyRequest', 'GetGrantsRequest', 'GetHsmStatusRequest', 'GetIdentitiesRequest', 'GetInfoRequest', 'GetItemRequest', 'GetLicenseRequest', 'GetLoggerStatsRequest',
                            'GetMailboxMetadataRequest', 'GetMailboxRequest', 'GetMailQueueInfoRequest', 'GetMailQueueRequest', 'GetMiniCalRequest', 'GetMsgRequest', 'GetOutgoingFilterRulesRequest', 'GetPermissionRequest',
                            'GetPrefsRequest', 'GetQuotaUsageRequest', 'GetRightRequest', 'GetRightsRequest', 'GetServerNIfsRequest', 'GetServerRequest', 'GetServiceStatusRequest', 'GetSessionsRequest', 'GetShareInfoRequest',
                            'GetSignaturesRequest', 'GetSystemRetentionPolicyRequest', 'GetTaskRequest', 'GetVersionInfoRequest', 'GetWhiteBlackListRequest', 'GetWorkingHoursRequest', 'GrantPermissionRequest', 'GrantRightsRequest',
                            'ImportAppointmentsRequest', 'InstallLicenseRequest', 'ItemActionRequest.copy', 'ItemActionRequest.delete', 'ItemActionRequest.move', 'ItemActionRequest.read', 'ItemActionRequest.!read', 'ItemActionRequest.tag',
                            'ItemActionRequest.!tag', 'ItemActionRequest.trash', 'ItemActionRequest.update', 'MailQueueActionRequest', 'ModifyAccountRequest', 'ModifyAdminSavedSearchesRequest', 'ModifyAppointmentRequest',
                            'ModifyCalendarResourceRequest', 'ModifyConfigRequest', 'ModifyContactRequest', 'ModifyCosRequest', 'ModifyDistributionListRequest', 'ModifyDomainRequest', 'ModifyFilterRulesRequest', 'ModifyIdentityRequest',
                            'ModifyPrefsRequest', 'ModifyPropertiesRequest', 'ModifyServerRequest', 'ModifySignatureRequest', 'ModifyTaskRequest', 'ModifyWhiteBlackListRequest', 'ModifyZimletPrefsRequest', 'MsgActionRequest.delete',
                            'MsgActionRequest.flag', 'MsgActionRequest.!flag', 'MsgActionRequest.move', 'MsgActionRequest.read', 'MsgActionRequest.!read', 'MsgActionRequest.spam', 'MsgActionRequest.trash', 'MsgActionRequest.update',
                            'NoOpRequest', 'RankingActionRequest.delete', 'RemoveAccountAliasRequest', 'RemoveDeviceRequest', 'RemoveDistributionListMemberRequest', 'RenameAccountRequest', 'SaveDocumentRequest', 'SaveDraftRequest',
                            'SearchCalendarResourcesRequest', 'SearchConvRequest', 'SearchDirectoryRequest', 'SearchGalRequest', 'SearchRequest', 'SendDeliveryReportRequest', 'SendInviteReplyRequest', 'SendMsgRequest',
                            'SendShareNotificationRequest', 'SetAppointmentRequest', 'SetMailboxMetadataRequest', 'SetTaskRequest', 'SnoozeCalendarItemAlarmRequest', 'SyncGalAccountRequest', 'SyncGalRequest', 'SyncRequest',
                            'UndeployZimletRequest', 'VersionCheckRequest', 'WaitSetRequest'];

    /// FIXME ldap & soap currently disabled: command names longer than allowed DS length
    foreach (['imap', 'pop3', 'sync'] as $protocol) {
        if (is_array($zimbra[$protocol])) {
            /*
            timestamp,command,exec_count,exec_ms_avg
            04/27/2013 22:33:38,CHECK,1,0
            */

            $rrd_filename = "app-zimbra-proto-$protocol.rrd";
            unset($rrd_values, $commands, $ds_list);

            foreach ($zimbra[$protocol] as $line) {
                $commands[$line['command']]['exec_count']   = $line['exec_count'];
                $commands[$line['command']]['exec_msg_avg'] = $line['exec_ms_avg'];
            }

            foreach ($commandlist[$protocol] as $command) {
                foreach (['exec_count', 'exec_ms_avg'] as $key) {
                    $rrd_values[] = (is_numeric($commands[$command][$key]) ? $commands[$command][$key] : "0");
                }

                $ds_cmd  = str_replace('!', 'N', substr($command, 0, 18));
                $ds_list .= "DS:c$ds_cmd:GAUGE:600:0:U DS:a$ds_cmd:GAUGE:600:0:U ";
            }

            rrdtool_create($device, $rrd_filename, " "
                                                   . $ds_list);

            rrdtool_update($device, $rrd_filename, "N:" . implode(':', $rrd_values));
        }
    }
}

// EOF
