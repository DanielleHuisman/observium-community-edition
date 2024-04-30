<?php

//define('OBS_DEBUG', 1);

include(__DIR__ . '/../includes/observium.inc.php');
include(__DIR__ . '../html/includes/functions.inc.php');

class IncludesSyslogTest extends \PHPUnit\Framework\TestCase {

  /**
  * @dataProvider providerProcessSyslogLine
  * @group process
  */
  public function testProcessSyslogLine($line, $result) {
    // Create fake device array from syslog line
    $os = explode('||', $line, 2)[0];
    $device = array('hostname' => $os, 'device_id' => crc32($os), 'os' => $os);
    if (isset($GLOBALS['config']['os'][$os]['os_group'])) {
      $device['os_group'] = $GLOBALS['config']['os'][$os]['os_group'];
    }
    //var_dump($GLOBALS['config']['os'][$os]);

    // Override device cache for syslog processing:
    $host = $device['hostname'];
    $dev_cache = [];
    $dev_cache[$host]['lastchecked'] = time();
    $dev_cache[$host]['device_id']  = $device['device_id'];
    $dev_cache[$host]['os']         = $device['os'];
    if (isset($device['os_group'])) {
      $dev_cache[$host]['os_group'] = $device['os_group'];
    }
    $GLOBALS['dev_cache'] = $dev_cache;

    // Override config syslog filter
    $GLOBALS['config']['syslog']['filter'] = [ 'TEST', 'derp' ];

    if ($tmp = process_syslog_line($line)) {
      // Just custom resort array
      $entry = [];
      foreach ([ 'facility', 'priority', 'level', 'tag', // [ 'host', 'facility', 'priority', 'level', 'tag',
                 'program', 'msg', 'msg_orig' ] as $key) {
        $entry[$key] = $tmp[$key];
      }
    } else {
      $entry = $tmp; // FALSE positive
    }

    $this->assertSame($result, $entry);
  }


  public function providerProcessSyslogLine()
  {
    $result = [];
    // Linux/Unix
    $result[] = array('linux||9||6||6||CRON[3196]:||2018-03-13 06:25:01|| (root) CMD (test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily ))||CRON',
                                     array('facility'  => 'cron', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'CRON[3196]', 'program' => 'CRON',
                                           'msg'       => '(root) CMD (test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily ))',
                                           'msg_orig'  => '(root) CMD (test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily ))',
                                           ));
    $result[] = array('linux||4||6||6||sshd[10809]:||2018-03-19 15:28:47|| message repeated 2 times: [ Failed password for root from 221.194.44.211 port 49810 ssh2]||sshd',
                                     array('facility'  => 'auth', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'sshd[10809]', 'program' => 'SSHD',
                                           'msg'       => 'Failed password for root from 221.194.44.211 port 49810 ssh2',
                                           'msg_orig'  => 'message repeated 2 times: [ Failed password for root from 221.194.44.211 port 49810 ssh2]',
                                           ));
    $result[] = array('linux||5||6||6||rsyslogd0:||2018-03-14 06:28:18|| action \'action 18\' resumed (module \'builtin:ompipe\') [try http://www.rsyslog.com/e/0 ]||rsyslogd0',
                                     array('facility'  => 'syslog', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'rsyslogd0', 'program' => 'RSYSLOGD',
                                           'msg'       => 'action \'action 18\' resumed (module \'builtin:ompipe\') [try http://www.rsyslog.com/e/0 ]',
                                           'msg_orig'  => 'action \'action 18\' resumed (module \'builtin:ompipe\') [try http://www.rsyslog.com/e/0 ]',
                                           ));
    $result[] = array('linux||5||4||4||rsyslogd-2007:||2018-03-14 06:55:50|| action \'action 18\' suspended, next retry is Wed Mar 14 06:56:20 2018 [try http://www.rsyslog.com/e/2007 ]||rsyslogd-2007',
                                     array('facility'  => 'syslog', 'priority' => '4', 'level' => '4',
                                           'tag'       => 'rsyslogd-2007', 'program' => 'RSYSLOGD',
                                           'msg'       => 'action \'action 18\' suspended, next retry is Wed Mar 14 06:56:20 2018 [try http://www.rsyslog.com/e/2007 ]',
                                           'msg_orig'  => 'action \'action 18\' suspended, next retry is Wed Mar 14 06:56:20 2018 [try http://www.rsyslog.com/e/2007 ]',
                                           ));
    $result[] = [ 'linux||9||5||5||run-parts(/etc/cron.hourly)[2654||2021-11-26 08:01:01|| starting 0anacron||run-parts(',
                  [ 'facility'  => 'cron', 'priority' => '5', 'level' => '5',
                    'tag'       => 'run-parts,/etc/cron.hourly', 'program' => '0ANACRON',
                    'msg'       => 'starting 0anacron',
                    'msg_orig'  => 'starting 0anacron', ]
    ];
    $result[] = [ 'linux||3||6||6||fail2ban-client[20598]:||2018-06-07 14:36:03|| 2018-06-07 14:36:03,699 fail2ban.server         [20601]: INFO    Starting Fail2ban v0.9.3||fail2ban-client',
                  [ 'facility'  => 'daemon', 'priority' => '6', 'level' => '6',
                    'tag'       => 'client,INFO', 'program' => 'FAIL2BAN',
                    'msg'       => 'Starting Fail2ban v0.9.3',
                    'msg_orig'  => '2018-06-07 14:36:03,699 fail2ban.server         [20601]: INFO    Starting Fail2ban v0.9.3', ]
    ];
    $result[] = [ 'linux||3||6||6||fail2ban-server:||2021-11-12 11:51:25|| Server ready||fail2ban-server',
                  [ 'facility'  => 'daemon', 'priority' => '6', 'level' => '6',
                    'tag'       => 'server', 'program' => 'FAIL2BAN',
                    'msg'       => 'Server ready',
                    'msg_orig'  => 'Server ready', ]
    ];
    $result[] = [ 'linux||3||5||5||fail2ban.actions[4314]:||2021-11-26 11:15:57|| NOTICE [sshd] Unban 116.98.170.132||fail2ban.actions',
                  [ 'facility'  => 'daemon', 'priority' => '5', 'level' => '5',
                    'tag'       => 'actions,NOTICE', 'program' => 'FAIL2BAN',
                    'msg'       => '[sshd] Unban 116.98.170.132',
                    'msg_orig'  => 'NOTICE [sshd] Unban 116.98.170.132', ]
    ];
    // from group definition
    $result[] = array('linux||10||5||5||sshd[9071]:||2018-03-20 17:40:43|| PAM 2 more authentication failures; logname= uid=0 euid=0 tty=ssh ruser= rhost=221.194.47.243  user=root||sshd',
                                     array('facility'  => 'authpriv', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'sshd[9071]', 'program' => 'SSHD',
                                           'msg'       => 'PAM 2 more authentication failures; logname= uid=0 euid=0 tty=ssh ruser= rhost=221.194.47.243  user=root',
                                           'msg_orig'  => 'PAM 2 more authentication failures; logname= uid=0 euid=0 tty=ssh ruser= rhost=221.194.47.243  user=root',
                                           ));
    $result[] = array('linux||10||5||5||sshd[9071]:||2018-03-20 17:40:43|| pam_unix(sshd:auth): authentication failure; logname= uid=0 euid=0 tty=ssh ruser= rhost=221.194.47.243  user=root||sshd',
                                     array('facility'  => 'authpriv', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'sshd[9071],pam_unix,auth', 'program' => 'SSHD',
                                           'msg'       => 'pam_unix(sshd:auth): authentication failure; logname= uid=0 euid=0 tty=ssh ruser= rhost=221.194.47.243  user=root',
                                           'msg_orig'  => 'pam_unix(sshd:auth): authentication failure; logname= uid=0 euid=0 tty=ssh ruser= rhost=221.194.47.243  user=root',
                                           ));
    $result[] = array('linux||10||5||5||sshd[9071]:||2018-03-20 17:40:43|| pam_krb5[sshd:auth]: authentication failure; logname=root uid=0 euid=0 tty=ssh ruser= rhost=123.213.132.231||sshd',
                                     array('facility'  => 'authpriv', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'sshd[9071],pam_krb5,auth', 'program' => 'SSHD',
                                           'msg'       => 'pam_krb5[sshd:auth]: authentication failure; logname=root uid=0 euid=0 tty=ssh ruser= rhost=123.213.132.231',
                                           'msg_orig'  => 'pam_krb5[sshd:auth]: authentication failure; logname=root uid=0 euid=0 tty=ssh ruser= rhost=123.213.132.231',
                                           ));
    $result[] = array('linux||10||5||5||sshd[9071]:||2018-03-20 17:40:43|| pam_krb5: authentication failure; logname=root uid=0 euid=0 tty=ssh ruser= rhost=123.213.132.231||sshd',
                                     array('facility'  => 'authpriv', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'sshd[9071],pam_krb5', 'program' => 'SSHD',
                                           'msg'       => 'pam_krb5: authentication failure; logname=root uid=0 euid=0 tty=ssh ruser= rhost=123.213.132.231',
                                           'msg_orig'  => 'pam_krb5: authentication failure; logname=root uid=0 euid=0 tty=ssh ruser= rhost=123.213.132.231',
                                           ));

    $result[] = array('freebsd||14||6||6||kernel:||2018-03-21 13:07:05|| Mar 21 13:07:05 somehost syslogd: exiting on signal 15||kernel',
                                     array('facility'  => 'console', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'kernel', 'program' => 'KERNEL',
                                           'msg'       => 'somehost syslogd: exiting on signal 15',
                                           'msg_orig'  => 'Mar 21 13:07:05 somehost syslogd: exiting on signal 15',
                                           ));
    $result[] = array('freebsd||9||6||6||/usr/sbin/cron[19422]:||2018-03-21 13:10:00|| (root) CMD (/usr/libexec/atrun)||',
                                     array('facility'  => 'cron', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'cron[19422]', 'program' => 'CRON',
                                           'msg'       => '(root) CMD (/usr/libexec/atrun)',
                                           'msg_orig'  => '(root) CMD (/usr/libexec/atrun)',
                                           ));
    $result[] = array('freebsd||3||6||6||transmission-daemon[56416]:||2018-03-21 14:53:11|| Сезон 1 Scrape error: Could not connect to tracker (announcer.c:1279)     ||transmission-daemon',
                                     array('facility'  => 'daemon', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'transmission-daemon[56416],announcer.c', 'program' => 'TRANSMISSION-DAEMON',
                                           'msg'       => 'Сезон 1 Scrape error: Could not connect to tracker (announcer.c:1279)',
                                           'msg_orig'  => 'Сезон 1 Scrape error: Could not connect to tracker (announcer.c:1279)',
                                           ));
    $result[] = [ 'linux||3||5||5||dbus[523]:||2021-11-26 11:07:35|| [system] Activating service name=\'org.freedesktop.problems\' (using servicehelper)||dbus',
                  [ 'facility'  => 'daemon', 'priority' => '5', 'level' => '5',
                    'tag'       => 'dbus[523],system', 'program' => 'DBUS',
                    'msg'       => '[system] Activating service name=\'org.freedesktop.problems\' (using servicehelper)',
                    'msg_orig'  => '[system] Activating service name=\'org.freedesktop.problems\' (using servicehelper)', ]
    ];
    // Another repeated message
    $result[] = array('freebsd||1||4||4||message||2018-03-21 14:50:37|| repeated 2 times||message',
                                     FALSE);

    // Windows
    $result[] = array('windows||3||3||3||Security-Auditing:||2018-03-17 15:53:43|| 4625: AUDIT_FAILURE An account failed to log on. Subject: Security ID: S-1-0-0 Account Name: - Account Domain: - Logon ID: 0x0 Logon Type: 3 Account For Which Logon Failed: Security ID: S-1-0-0 Account Name: ADMINISTRATOR Account Domain: Failure Information: Failure Reason: Unknown user name or bad password. Status: 0xc000006d Sub Status: 0xc000006a Process Information: Caller Process ID: 0x0 Caller Process Name: - Network Information: Workstation Name: Source Network Address: - Source Port: - Detailed Authentication Information: Logon Process: NtLmSsp Authentication Package: NTLM Transited Services: - Package Name (NTLM only): - Key Length: 0 This event is generated when a logon request fails. It is generated on the computer where access was attempted. The Subject fields indicate the account on the local system which requested the logon. This is most commonly a service such as the Server service, or a local process such as Winlogon.exe or Services.exe. The Logon Type field indicates the kind of logon that was requested. The most common types are 2 (interactive) and 3 (network). The Process Information fields indicate which account and process on the system requested the logon. The Network Information fields indicate where a remote logon request originated. Workstation name is not always available and may be left blank in some cases. The authentication information fields provide detailed information about this specific logon request. - Transited services indicate which intermediate services have participated in this logon request. - Package name indicates which sub-protocol was used among the NTLM protocols. - Key length indicates the length of the generated session key. This will be 0 if no session key was requested.||Security-Auditing',
                                     array('facility'  => 'daemon', 'priority' => '3', 'level' => '3',
                                           'tag'       => 'Security-Auditing', 'program' => 'SECURITY-AUDITING',
                                           'msg'       => '4625: AUDIT_FAILURE An account failed to log on. Subject: Security ID: S-1-0-0 Account Name: - Account Domain: - Logon ID: 0x0 Logon Type: 3 Account For Which Logon Failed: Security ID: S-1-0-0 Account Name: ADMINISTRATOR Account Domain: Failure Information: Failure Reason: Unknown user name or bad password. Status: 0xc000006d Sub Status: 0xc000006a Process Information: Caller Process ID: 0x0 Caller Process Name: - Network Information: Workstation Name: Source Network Address: - Source Port: - Detailed Authentication Information: Logon Process: NtLmSsp Authentication Package: NTLM Transited Services: - Package Name (NTLM only): - Key Length: 0 This event is generated when a logon request fails. It is generated on the computer where access was attempted. The Subject fields indicate the account on the local system which requested the logon. This is most commonly a service such as the Server service, or a local process such as Winlogon.exe or Services.exe. The Logon Type field indicates the kind of logon that was requested. The most common types are 2 (interactive) and 3 (network). The Process Information fields indicate which account and process on the system requested the logon. The Network Information fields indicate where a remote logon request originated. Workstation name is not always available and may be left blank in some cases. The authentication information fields provide detailed information about this specific logon request. - Transited services indicate which intermediate services have participated in this logon request. - Package name indicates which sub-protocol was used among the NTLM protocols. - Key length indicates the length of the generated session key. This will be 0 if no session key was requested.',
                                           'msg_orig'  => '4625: AUDIT_FAILURE An account failed to log on. Subject: Security ID: S-1-0-0 Account Name: - Account Domain: - Logon ID: 0x0 Logon Type: 3 Account For Which Logon Failed: Security ID: S-1-0-0 Account Name: ADMINISTRATOR Account Domain: Failure Information: Failure Reason: Unknown user name or bad password. Status: 0xc000006d Sub Status: 0xc000006a Process Information: Caller Process ID: 0x0 Caller Process Name: - Network Information: Workstation Name: Source Network Address: - Source Port: - Detailed Authentication Information: Logon Process: NtLmSsp Authentication Package: NTLM Transited Services: - Package Name (NTLM only): - Key Length: 0 This event is generated when a logon request fails. It is generated on the computer where access was attempted. The Subject fields indicate the account on the local system which requested the logon. This is most commonly a service such as the Server service, or a local process such as Winlogon.exe or Services.exe. The Logon Type field indicates the kind of logon that was requested. The most common types are 2 (interactive) and 3 (network). The Process Information fields indicate which account and process on the system requested the logon. The Network Information fields indicate where a remote logon request originated. Workstation name is not always available and may be left blank in some cases. The authentication information fields provide detailed information about this specific logon request. - Transited services indicate which intermediate services have participated in this logon request. - Package name indicates which sub-protocol was used among the NTLM protocols. - Key length indicates the length of the generated session key. This will be 0 if no session key was requested.',
                                           ));

    // Cisco IOS
    $result[] = array('ios||23||5||5||26644:||2013-11-08 07:19:24|| *Mar  1 18:48:50.483 UTC: %SYS-5-CONFIG_I: Configured from console by vty2 (10.34.195.36)||26644',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'CONFIG_I', 'program' => 'SYS',
                                           'msg'       => 'Configured from console by vty2 (10.34.195.36)',
                                           'msg_orig'  => '*Mar  1 18:48:50.483 UTC: %SYS-5-CONFIG_I: Configured from console by vty2 (10.34.195.36)',
                                           ));
    $result[] = array('ios||23||5||5||26644:||2013-11-08 07:19:24|| 00:00:48: %LINEPROTO-5-UPDOWN: Line protocol on Interface GigabitEthernet2/0/1, changed state to down 2 (Switch-2)||26644',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'UPDOWN', 'program' => 'LINEPROTO',
                                           'msg'       => 'Line protocol on Interface GigabitEthernet2/0/1, changed state to down 2 (Switch-2)',
                                           'msg_orig'  => '00:00:48: %LINEPROTO-5-UPDOWN: Line protocol on Interface GigabitEthernet2/0/1, changed state to down 2 (Switch-2)',
                                           ));

    // Unknown program and tag
    $result[] = array('ios||23||7||7||12602:||2016-10-24 11:34:02|| Oct 24 11:34:01.275: VSTACK_ERR: ||12602',
                                     array('facility'  => 'local7', 'priority' => '7', 'level' => '7',
                                           'tag'       => 'debug', 'program' => 'DEBUG',
                                           'msg'       => 'VSTACK_ERR:',
                                           'msg_orig'  => 'Oct 24 11:34:01.275: VSTACK_ERR:',
                                           ));
    $result[] = array('ios||23||7||7||12603:||2016-10-24 11:34:02||  smi_ibc_dl_handle_events : invalid message||12603',
                                     array('facility'  => 'local7', 'priority' => '7', 'level' => '7',
                                           'tag'       => 'debug', 'program' => 'DEBUG',
                                           'msg'       => 'smi_ibc_dl_handle_events : invalid message',
                                           'msg_orig'  => 'smi_ibc_dl_handle_events : invalid message',
                                           ));
    $result[] = array('ios||23||7||7||12622:||2016-10-26 10:05:37|| Oct 26 10:05:36.301: Invalid packet (too small) length=0',
                                     array('facility'  => 'local7', 'priority' => '7', 'level' => '7',
                                           'tag'       => 'debug', 'program' => 'DEBUG',
                                           'msg'       => 'Invalid packet (too small) length=0',
                                           'msg_orig'  => 'Oct 26 10:05:36.301: Invalid packet (too small) length=0',
                                           ));

    // Examples from real system
    $result[] = array('ios||23||4||4||26644:||2013-11-08 07:19:24|| 033884: Nov  8 07:19:23.993: %FW-4-TCP_OoO_SEG: Dropping TCP Segment: seq:-1169729434 1500 bytes is out-of-order; expected seq:3124765814. Reason: TCP reassembly queue overflow - session 10.10.32.37:56316 to 93.186.239.142:80 on zone-pair Local->Internet class All_Inspection||26644',
                                     array('facility'  => 'local7', 'priority' => '4', 'level' => '4',
                                           'tag'       => 'TCP_OoO_SEG', 'program' => 'FW',
                                           'msg'       => 'Dropping TCP Segment: seq:-1169729434 1500 bytes is out-of-order; expected seq:3124765814. Reason: TCP reassembly queue overflow - session 10.10.32.37:56316 to 93.186.239.142:80 on zone-pair Local->Internet class All_Inspection',
                                           'msg_orig'  => '033884: Nov  8 07:19:23.993: %FW-4-TCP_OoO_SEG: Dropping TCP Segment: seq:-1169729434 1500 bytes is out-of-order; expected seq:3124765814. Reason: TCP reassembly queue overflow - session 10.10.32.37:56316 to 93.186.239.142:80 on zone-pair Local->Internet class All_Inspection',
                                           ));
    $result[] = array('ios||23||5||5||1747:||2018-03-12 13:47:44|| 001743: *Apr 25 04:16:54.749: %SSH-5-SSH2_SESSION: SSH2 Session request from 10.12.0.251 (tty = 0) using crypto cipher \'3des-cbc\', hmac \'hmac-md5\' Succeeded||1747',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'SSH2_SESSION', 'program' => 'SSH',
                                           'msg'       => 'SSH2 Session request from 10.12.0.251 (tty = 0) using crypto cipher \'3des-cbc\', hmac \'hmac-md5\' Succeeded',
                                           'msg_orig'  => '001743: *Apr 25 04:16:54.749: %SSH-5-SSH2_SESSION: SSH2 Session request from 10.12.0.251 (tty = 0) using crypto cipher \'3des-cbc\', hmac \'hmac-md5\' Succeeded',
                                           ));
    $result[] = array('ios||23||5||5||908:||2018-03-12 13:45:15|| Mar 12 13:45:14.241: %SSH-5-SSH2_SESSION: SSH2 Session request from 10.10.10.10 (tty = 0) using crypto cipher \'3des-cbc\', hmac \'hmac-md5\' Succeeded||908',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag' => 'SSH2_SESSION', 'program' => 'SSH',
                                           'msg'       => 'SSH2 Session request from 10.10.10.10 (tty = 0) using crypto cipher \'3des-cbc\', hmac \'hmac-md5\' Succeeded',
                                           'msg_orig'  => 'Mar 12 13:45:14.241: %SSH-5-SSH2_SESSION: SSH2 Session request from 10.10.10.10 (tty = 0) using crypto cipher \'3des-cbc\', hmac \'hmac-md5\' Succeeded',
                                           ));
    $result[] = array('ios||23||5||5||2559:||2017-01-26 04:27:10|| 003174: Jan 26 04:27:09.174 MSK: %BGP-5-ADJCHANGE: neighbor 10.0.1.17 vpn vrf hostcomm-private Up ||2559',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag' => 'ADJCHANGE', 'program' => 'BGP',
                                           'msg'       => 'neighbor 10.0.1.17 vpn vrf hostcomm-private Up',
                                           'msg_orig'  => '003174: Jan 26 04:27:09.174 MSK: %BGP-5-ADJCHANGE: neighbor 10.0.1.17 vpn vrf hostcomm-private Up',
                                           ));
    $result[] = array('ios||23||4||4||12601:||2016-10-24 11:34:01|| -Traceback= BF7490z 195628z 10D3A0Cz 10D30B8z 10D8C28z 10D9B70z 10DAFE0z 10DB174z 10D34ECz 133F918z 133A9D4z||12601',
                                     array('facility'  => 'local7', 'priority' => '4', 'level' => '4',
                                           'tag'       => 'Traceback', 'program' => 'TRACEBACK',
                                           'msg'       => 'BF7490z 195628z 10D3A0Cz 10D30B8z 10D8C28z 10D9B70z 10DAFE0z 10DB174z 10D34ECz 133F918z 133A9D4z',
                                           'msg_orig'  => '-Traceback= BF7490z 195628z 10D3A0Cz 10D30B8z 10D8C28z 10D9B70z 10DAFE0z 10DB174z 10D34ECz 133F918z 133A9D4z',
                                           ));

    // Cisco IOS-XR

    $result[] = array('iosxr||23||5||5||920:||2014-11-26 17:29:48||RP/0/RSP0/CPU0:Nov 26 16:29:48.161 : bgp[1046]: %ROUTING-BGP-5-ADJCHANGE : neighbor 1.1.1.2 Up (VRF: default) (AS: 11111) ||920',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'ADJCHANGE,ROUTING,bgp[1046]', 'program' => 'BGP',
                                           'msg'       => 'neighbor 1.1.1.2 Up (VRF: default) (AS: 11111)',
                                           'msg_orig'  => 'RP/0/RSP0/CPU0:Nov 26 16:29:48.161 : bgp[1046]: %ROUTING-BGP-5-ADJCHANGE : neighbor 1.1.1.2 Up (VRF: default) (AS: 11111)',
                                           ));
    $result[] = array('iosxr||23||6||6||253:||2014-11-26 17:30:21||RP/0/RSP0/CPU0:Nov 26 16:30:21.710 : SSHD_[65755]: %SECURITY-SSHD-6-INFO_GENERAL : Client closes socket connection ||253',
                                     array('facility'  => 'local7', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'INFO_GENERAL,SECURITY,SSHD_[65755]', 'program' => 'SSHD',
                                           'msg'       => 'Client closes socket connection',
                                           'msg_orig'  => 'RP/0/RSP0/CPU0:Nov 26 16:30:21.710 : SSHD_[65755]: %SECURITY-SSHD-6-INFO_GENERAL : Client closes socket connection',
                                           ));
    $result[] = array('iosxr||23||6||6||10127:||2016-10-19 09:17:07|| LC/0/0/CPU0:Oct 19 09:17:07.433 : nfsvr[277]: %MGBL-NETFLOW-6-INFO_CACHE_SIZE_EXCEEDED : Cache size of 262144 for monitor nf_ipv4 has been exceeded ||10127',
                                     array('facility'  => 'local7', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'INFO_CACHE_SIZE_EXCEEDED,MGBL,nfsvr[277]', 'program' => 'NETFLOW',
                                           'msg'       => 'Cache size of 262144 for monitor nf_ipv4 has been exceeded',
                                           'msg_orig'  => 'LC/0/0/CPU0:Oct 19 09:17:07.433 : nfsvr[277]: %MGBL-NETFLOW-6-INFO_CACHE_SIZE_EXCEEDED : Cache size of 262144 for monitor nf_ipv4 has been exceeded',
                                           ));
    $result[] = array('iosxr||local0||err||err||83||2015-01-14 07:29:45||oly-er-01 LC/0/0/CPU0:Jan 14 07:29:45.556 CET: pfilter_ea[301]: %L2-PFILTER_EA-3-ERR_IM_CAPS : uidb set  acl failed on interface Bundle-Ether1.1501.ip43696. (null) ||94795',
                                     array('facility'  => 'local0', 'priority' => '3', 'level' => '3',
                                           'tag'       => 'ERR_IM_CAPS,L2,pfilter_ea[301]', 'program' => 'PFILTER_EA',
                                           'msg'       => 'uidb set  acl failed on interface Bundle-Ether1.1501.ip43696. (null)',
                                           'msg_orig'  => 'oly-er-01 LC/0/0/CPU0:Jan 14 07:29:45.556 CET: pfilter_ea[301]: %L2-PFILTER_EA-3-ERR_IM_CAPS : uidb set  acl failed on interface Bundle-Ether1.1501.ip43696. (null)',
                                           ));

    // Cisco NX-OS
    $result[] = array('nxos||23||5||5||:||2019-02-27 13:23:54|| 2019 Feb 27 13:23:54 GMT: %VSHD-5-VSHD_SYSLOG_CONFIG_I: Configured from vty by username on x.x.x.x@pts/1||',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'VSHD_SYSLOG_CONFIG_I', 'program' => 'VSHD',
                                           'msg'       => 'Configured from vty by username on x.x.x.x@pts/1',
                                           'msg_orig'  => '2019 Feb 27 13:23:54 GMT: %VSHD-5-VSHD_SYSLOG_CONFIG_I: Configured from vty by username on x.x.x.x@pts/1',
                                           ));
    $result[] = array('nxos||23||2||2||:||2020-02-17 11:37:03|| 2020 Feb 17 11:37:03 MSK:  %USER-2-SYSTEM_MSG: <<%USBHSD-2-USB_SWAP>> USB insertion or removal detected - usbhsd||',
                                     array('facility'  => 'local7', 'priority' => '2', 'level' => '2',
                                           'tag'       => 'SYSTEM_MSG', 'program' => 'USER',
                                           'msg'       => '<<%USBHSD-2-USB_SWAP>> USB insertion or removal detected - usbhsd',
                                           'msg_orig'  => '2020 Feb 17 11:37:03 MSK:  %USER-2-SYSTEM_MSG: <<%USBHSD-2-USB_SWAP>> USB insertion or removal detected - usbhsd',
                                           ));
    $result[] = array('nxos||23||2||2||:||2020-03-21 17:18:58|| 2020 Mar 21 17:18:58 MSK: %MTM-SLOT1-2-MTM_BUFFERS_FULL: MTM buffers are full for unit 0. MAC tables might be inconsistent. Pls use l2 consistency-checker to verify.||',
                                     array('facility'  => 'local7', 'priority' => '2', 'level' => '2',
                                           'tag'       => 'MTM_BUFFERS_FULL', 'program' => 'MTM-SLOT1',
                                           'msg'       => 'MTM buffers are full for unit 0. MAC tables might be inconsistent. Pls use l2 consistency-checker to verify.',
                                           'msg_orig'  => '2020 Mar 21 17:18:58 MSK: %MTM-SLOT1-2-MTM_BUFFERS_FULL: MTM buffers are full for unit 0. MAC tables might be inconsistent. Pls use l2 consistency-checker to verify.',
                                           ));
    $result[] = array('nxos||23||5||5||:||2020-03-11 10:50:23|| 2020 Mar 11 10:50:23 MSK: vshd: process \'28488\' recieved signal 2||',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'vshd', 'program' => 'VSHD',
                                           'msg'       => 'process \'28488\' recieved signal 2',
                                           'msg_orig'  => '2020 Mar 11 10:50:23 MSK: vshd: process \'28488\' recieved signal 2',
                                     ));

    // Cisco ASA
    // Old syslog format
    $result[] = array('asa||23||4||4||12601:||2016-10-24 11:34:01||Apr 24 2013 16:00:28 INT-FW01 : %ASA-6-106100: access-list inside denied udp inside/172.29.2.101(1039) -> outside/192.203.230.10(53) hit-cnt 1 first hit [0xd820e56a, 0x0]||12601',
                                     array('facility'  => 'local7', 'priority' => '4', 'level' => '4',
                                           'tag'       => '106100', 'program' => 'ASA',
                                           'msg'       => 'access-list inside denied udp inside/172.29.2.101(1039) -> outside/192.203.230.10(53) hit-cnt 1 first hit [0xd820e56a, 0x0]',
                                           'msg_orig'  => 'Apr 24 2013 16:00:28 INT-FW01 : %ASA-6-106100: access-list inside denied udp inside/172.29.2.101(1039) -> outside/192.203.230.10(53) hit-cnt 1 first hit [0xd820e56a, 0x0]',
                                           ));
    // New syslog format
    $result[] = array('asa||19||5||5||:||2018-03-19 15:11:57||Mar 19 15:11:57 MSK/MSD: %ASA-config-5-111008: User \'enable_15\' executed the \'logging host inside 10.12.0.251 format emblem\' command.||',
                                     array('facility'  => 'local3', 'priority' => '5', 'level' => '5',
                                           'tag'       => '111008', 'program' => 'CONFIG',
                                           'msg'       => 'User \'enable_15\' executed the \'logging host inside 10.12.0.251 format emblem\' command.',
                                           'msg_orig'  => 'Mar 19 15:11:57 MSK/MSD: %ASA-config-5-111008: User \'enable_15\' executed the \'logging host inside 10.12.0.251 format emblem\' command.',
                                           ));
    $result[] = array('asa||19||4||4||:||2018-03-19 15:12:23||Mar 19 15:12:23 MSK/MSD: %ASA--4-733100: [ Interface] drop rate-1 exceeded. Current burst rate is 0 per second, max configured rate is 8000; Current average rate is 22088 per second, max configured rate is 2000; Cumulative total count is 13253349||',
                                     array('facility'  => 'local3', 'priority' => '4', 'level' => '4',
                                           'tag'       => '733100', 'program' => 'INTERFACE',
                                           'msg'       => '[ Interface] drop rate-1 exceeded. Current burst rate is 0 per second, max configured rate is 8000; Current average rate is 22088 per second, max configured rate is 2000; Cumulative total count is 13253349',
                                           'msg_orig'  => 'Mar 19 15:12:23 MSK/MSD: %ASA--4-733100: [ Interface] drop rate-1 exceeded. Current burst rate is 0 per second, max configured rate is 8000; Current average rate is 22088 per second, max configured rate is 2000; Cumulative total count is 13253349',
                                           ));
    $result[] = array('asa||19||6||6||:||2018-03-19 15:14:53|| message repeated 27 times: [Mar 19 15:14:53 MSK/MSD: %ASA-session-6-106015: Deny TCP (no connection) from 192.168.22.6/57537 to 88.221.73.29/443 flags RST  on interface inside]||',
                                     array('facility'  => 'local3', 'priority' => '6', 'level' => '6',
                                           'tag'       => '106015', 'program' => 'SESSION',
                                           'msg'       => 'Deny TCP (no connection) from 192.168.22.6/57537 to 88.221.73.29/443 flags RST  on interface inside',
                                           'msg_orig'  => 'message repeated 27 times: [Mar 19 15:14:53 MSK/MSD: %ASA-session-6-106015: Deny TCP (no connection) from 192.168.22.6/57537 to 88.221.73.29/443 flags RST  on interface inside]',
                                           ));

    // Uniquity Unifi
    // Old syslog format
    $result[] = array('unifi||3||6||6||hostapd:||2014-07-18 11:29:35|| ath2: STA c8:dd:c9:d1:d4:aa IEEE 802.11: associated||hostapd',
                                     array('facility'  => 'daemon', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'hostapd', 'program' => 'HOSTAPD',
                                           'msg'       => 'ath2: STA c8:dd:c9:d1:d4:aa IEEE 802.11: associated',
                                           'msg_orig'  => 'ath2: STA c8:dd:c9:d1:d4:aa IEEE 802.11: associated',
                                           ));

    // New syslog format
    $result[] = array('unifi||3||6||6||(BZ2LR,00272250c1cd,v3.2.5.2791)||2014-12-12 09:36:39|| hostapd: ath2: STA dc:a9:71:1b:d6:c7 IEEE 802.11: associated||(BZ2LR,00272250c1cd,v3.2.5.2791)',
                                     array('facility'  => 'daemon', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'hostapd', 'program' => 'HOSTAPD',
                                           'msg'       => 'ath2: STA dc:a9:71:1b:d6:c7 IEEE 802.11: associated',
                                           'msg_orig'  => 'hostapd: ath2: STA dc:a9:71:1b:d6:c7 IEEE 802.11: associated',
                                           ));
    $result[] = array('unifi||1||6||6||("BZ2LR,00272250c119,v3.7.8.5016")||2016-10-06 18:20:25|| syslog: wevent.ubnt_custom_event(): EVENT_STA_LEAVE ath0: dc:a9:71:1b:d6:c7 / 3||("BZ2LR,00272250c119,v3.7.8.5016")',
                                     array('facility'  => 'user', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'syslog', 'program' => 'SYSLOG',
                                           'msg'       => 'wevent.ubnt_custom_event(): EVENT_STA_LEAVE ath0: dc:a9:71:1b:d6:c7 / 3',
                                           'msg_orig'  => 'syslog: wevent.ubnt_custom_event(): EVENT_STA_LEAVE ath0: dc:a9:71:1b:d6:c7 / 3',
                                           ));
    $result[] = array('unifi||1||6||6||("U7LR,44d9e7f618f2,v3.7.17.5220")||2016-10-06 18:21:22|| libubnt[16915]: wevent.ubnt_custom_event(): EVENT_STA_JOIN ath0: fc:64:ba:c1:7d:28 / 1||("U7LR,44d9e7f618f2,v3.7.17.5220")',
                                     array('facility'  => 'user', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'libubnt[16915]', 'program' => 'LIBUBNT',
                                           'msg'       => 'wevent.ubnt_custom_event(): EVENT_STA_JOIN ath0: fc:64:ba:c1:7d:28 / 1',
                                           'msg_orig'  => 'libubnt[16915]: wevent.ubnt_custom_event(): EVENT_STA_JOIN ath0: fc:64:ba:c1:7d:28 / 1',
                                           ));
    $result[] = array('unifi||0||4||4||("U7LR,44d9e7f618f2,v3.9.19.8123")||2018-03-12 12:55:20|| kernel: [420813.870000] wmi_unified_event_rx : no registered event handler : event id 0x901b ||("U7LR,44d9e7f618f2,v3.9.19.8123")',
                                     array('facility'  => 'kern', 'priority' => '4', 'level' => '4',
                                           'tag'       => 'kernel', 'program' => 'KERNEL',
                                           'msg'       => '[420813.870000] wmi_unified_event_rx : no registered event handler : event id 0x901b',
                                           'msg_orig'  => 'kernel: [420813.870000] wmi_unified_event_rx : no registered event handler : event id 0x901b',
                                           ));
    $result[] = [ 'unifi||1||6||6||U7LR,44d9e7f618f2,v4.3.21.11325:||2020-10-05 16:28:50|| : stahtd[2839]: [STA-TRACKER].stahtd_dump_event(): {"mac":"0c:70:4a:7d:5c:73","message_type":"STA_ASSOC_TRACKER","vap":"ath4","auth_ts":"0.0","event_type":"fixup","event_id":"1","assoc_status":"0","arp_reply_gw_seen":"yes","dns_resp_seen":"yes"}||U7LR,44d9e7f618f2,v4.3.21.11325',
                  [ 'facility'  => 'user', 'priority' => '6', 'level' => '6',
                    'tag'       => 'stahtd[2839]', 'program' => 'STAHTD',
                    'msg'       => '[STA-TRACKER].stahtd_dump_event(): {"mac":"0c:70:4a:7d:5c:73","message_type":"STA_ASSOC_TRACKER","vap":"ath4","auth_ts":"0.0","event_type":"fixup","event_id":"1","assoc_status":"0","arp_reply_gw_seen":"yes","dns_resp_seen":"yes"}',
                    'msg_orig'  => ': stahtd[2839]: [STA-TRACKER].stahtd_dump_event(): {"mac":"0c:70:4a:7d:5c:73","message_type":"STA_ASSOC_TRACKER","vap":"ath4","auth_ts":"0.0","event_type":"fixup","event_id":"1","assoc_status":"0","arp_reply_gw_seen":"yes","dns_resp_seen":"yes"}',
                  ] ];

    // Unifi v2 new format... fuck you Ubiquiti with your changes in each firmware!
    $result[] = [ 'unifi||0||4||4||44d9e7f618f2,UAP-AC-LR-6.5.28+14491:||2023-03-06 15:57:43|| kernel: [399142.041095] [wifi1] FWLOG: [6086408] WAL_DBGID_SECURITY_MCAST_KEY_SET ( 0x2 )||44d9e7f618f2,UAP-AC-LR-6.5.28+14491',
                  [ 'facility'  => 'kern', 'priority' => '4', 'level' => '4',

                    'tag'       => 'kernel', 'program' => 'KERNEL',
                    'msg'       => '[399142.041095] [wifi1] FWLOG: [6086408] WAL_DBGID_SECURITY_MCAST_KEY_SET ( 0x2 )',
                    'msg_orig'  => 'kernel: [399142.041095] [wifi1] FWLOG: [6086408] WAL_DBGID_SECURITY_MCAST_KEY_SET ( 0x2 )',
                  ] ];
    $result[] = [ 'unifi||3||6||6||44d9e7f618f2,UAP-AC-LR-6.5.28+14491:||2023-03-06 15:43:20|| hostapd[15580]: ath4: STA d4:57:63:d6:a0:c3 IEEE 802.11: sta_stats||44d9e7f618f2,UAP-AC-LR-6.5.28+14491',
                  [ 'facility'  => 'daemon', 'priority' => '6', 'level' => '6',

                    'tag'       => 'hostapd[15580]', 'program' => 'HOSTAPD',
                    'msg'       => 'ath4: STA d4:57:63:d6:a0:c3 IEEE 802.11: sta_stats',
                    'msg_orig'  => 'hostapd[15580]: ath4: STA d4:57:63:d6:a0:c3 IEEE 802.11: sta_stats',
                  ] ];
    $result[] = [ 'unifi||3||6||6||44d9e7f618f2,UAP-AC-LR-6.5.28+14491:||2023-03-06 15:43:20|| stahtd: stahtd[15573]: [STA-TRACKER].stahtd_dump_event(): {"message_type":"STA_ASSOC_TRACKER","mac":"d4:57:63:d6:a0:c3","vap":"ath4","event_type":"sta_roam","assoc_status":"0","event_id":"1"}||44d9e7f618f2,UAP-AC-LR-6.5.28+14491',
                  [ 'facility'  => 'daemon', 'priority' => '6', 'level' => '6',

                    'tag'       => 'stahtd', 'program' => 'STAHTD',
                    'msg'       => '[STA-TRACKER].stahtd_dump_event(): {"message_type":"STA_ASSOC_TRACKER","mac":"d4:57:63:d6:a0:c3","vap":"ath4","event_type":"sta_roam","assoc_status":"0","event_id":"1"}',
                    'msg_orig'  => 'stahtd: stahtd[15573]: [STA-TRACKER].stahtd_dump_event(): {"message_type":"STA_ASSOC_TRACKER","mac":"d4:57:63:d6:a0:c3","vap":"ath4","event_type":"sta_roam","assoc_status":"0","event_id":"1"}',
                  ] ];

    // JunOS/JunOSe
    $result[] = array('junos||9||6||6||/usr/sbin/cron[50991]:||2016-10-07 00:15:00|| (root) CMD (   /usr/libexec/atrun)||',
                                     array('facility'  => 'cron', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'cron[50991]', 'program' => 'CRON',
                                           'msg'       => '(root) CMD (   /usr/libexec/atrun)',
                                           'msg_orig'  => '(root) CMD (   /usr/libexec/atrun)',
                                           ));
    $result[] = array('junos||0||7||7||/kernel:||2018-02-28 16:15:37|| rts_gencfg_ifstate_free(): Removing RTS_IFSTATE_ID_PENDING_DEL flag from GENCFG Major 8 Minor 8||',
                                     array('facility'  => 'kern', 'priority' => '7', 'level' => '7',
                                           'tag'       => 'kernel', 'program' => 'KERNEL',
                                           'msg'       => 'rts_gencfg_ifstate_free(): Removing RTS_IFSTATE_ID_PENDING_DEL flag from GENCFG Major 8 Minor 8',
                                           'msg_orig'  => 'rts_gencfg_ifstate_free(): Removing RTS_IFSTATE_ID_PENDING_DEL flag from GENCFG Major 8 Minor 8',
                                           ));

    $result[] = array('junos||3||4||4||mib2d[1230]:||2015-04-08 14:30:11|| SNMP_TRAP_LINK_DOWN: ifIndex 602, ifAdminStatus up(1), ifOperStatus down(2), ifName ge-0/1/0||mib2d',
                                     array('facility'  => 'daemon', 'priority' => '4', 'level' => '4',
                                           'tag'       => 'SNMP_TRAP_LINK_DOWN', 'program' => 'MIB2D',
                                           'msg'       => 'ifIndex 602, ifAdminStatus up(1), ifOperStatus down(2), ifName ge-0/1/0',
                                           'msg_orig'  => 'SNMP_TRAP_LINK_DOWN: ifIndex 602, ifAdminStatus up(1), ifOperStatus down(2), ifName ge-0/1/0',
                                           ));
    $result[] = array('junos||3||6||6||eswd[1237]:||2018-03-11 07:25:06|| ESWD_STP_STATE_CHANGE_INFO: STP state for interface ge-0/0/8.0 context id 23 changed from FORWARDING to BLOCKING||eswd',
                                     array('facility'  => 'daemon', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'ESWD_STP_STATE_CHANGE_INFO', 'program' => 'ESWD',
                                           'msg'       => 'STP state for interface ge-0/0/8.0 context id 23 changed from FORWARDING to BLOCKING',
                                           'msg_orig'  => 'ESWD_STP_STATE_CHANGE_INFO: STP state for interface ge-0/0/8.0 context id 23 changed from FORWARDING to BLOCKING',
                                           ));
    $result[] = array('junos||23||6||6||mgd[87681]:||2017-07-27 14:45:11|| UI_AUTH_EVENT: Authenticated user \'tpetrov\' at permission level \'j-super-user\'||mgd',
                                     array('facility'  => 'local7', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'UI_AUTH_EVENT', 'program' => 'MGD',
                                           'msg'       => 'Authenticated user \'tpetrov\' at permission level \'j-super-user\'',
                                           'msg_orig'  => 'UI_AUTH_EVENT: Authenticated user \'tpetrov\' at permission level \'j-super-user\'',
                                           ));
    $result[] = array('junos||3||6||6||rpd[1247]:||2018-03-11 07:25:09|| EVENT <UpDown> ge-0/0/8.0 index 79 <Up Broadcast Multicast> address #0 3c.61.4.f1.da.b||rpd',
                                     array('facility'  => 'daemon', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'EVENT,UpDown', 'program' => 'RPD',
                                           'msg'       => 'ge-0/0/8.0 index 79 <Up Broadcast Multicast> address #0 3c.61.4.f1.da.b',
                                           'msg_orig'  => 'EVENT <UpDown> ge-0/0/8.0 index 79 <Up Broadcast Multicast> address #0 3c.61.4.f1.da.b',
                                           ));
    // Always skip this unusefull entries
    $result[] = array('junos||12||3||3||last||2018-02-16 16:15:49|| message repeated 3 times||last',
                                     FALSE);

    // FTOS
    $result[] = array('ftos||23||5||5||||2018-03-12 13:48:18|| Mar 12 14:48:18.631: spb-rad-sw1: %STKUNIT0-M:CP %SEC-5-TACACS_ACCESS_ACCEPTED: Tacacs access accepted for user "rancid"||',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'TACACS_ACCESS_ACCEPTED', 'program' => 'SEC',
                                           'msg'       => 'Tacacs access accepted for user "rancid"',
                                           'msg_orig'  => 'Mar 12 14:48:18.631: spb-rad-sw1: %STKUNIT0-M:CP %SEC-5-TACACS_ACCESS_ACCEPTED: Tacacs access accepted for user "rancid"',
                                           ));
    $result[] = array('ftos||23||5||5||||2018-03-19 05:48:18|| Mar 19 06:48:18.19: spb-rad-sw1: %STKUNIT0-M:CP %SEC-5-LOGOUT: Exec session is terminated for user rancid on line vty0 (1.1.1.1)||',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'LOGOUT', 'program' => 'SEC',
                                           'msg'       => 'Exec session is terminated for user rancid on line vty0 (1.1.1.1)',
                                           'msg_orig'  => 'Mar 19 06:48:18.19: spb-rad-sw1: %STKUNIT0-M:CP %SEC-5-LOGOUT: Exec session is terminated for user rancid on line vty0 (1.1.1.1)',
                                           ));
    $result[] = array('ftos||23||6||6||||2018-03-19 06:46:39|| Mar 19 07:46:39.430: spb-rad-sw1: %STKUNIT0-M:CP %NTP-6-INCOMP VER: Incompatible NTP Versions||',
                                     array('facility'  => 'local7', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'INCOMP VER', 'program' => 'NTP',
                                           'msg'       => 'Incompatible NTP Versions',
                                           'msg_orig'  => 'Mar 19 07:46:39.430: spb-rad-sw1: %STKUNIT0-M:CP %NTP-6-INCOMP VER: Incompatible NTP Versions',
                                           ));
    $result[] = array('ftos||23||5||5||||2018-03-19 05:48:12|| Mar 19 06:48:12.692: spb-rad-sw1: %STKUNIT0-M:CP %SEC-5-LOGIN_SUCCESS: Login successful for user rancid on line vty0 (1.1.1.1)||',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'LOGIN_SUCCESS', 'program' => 'SEC',
                                           'msg'       => 'Login successful for user rancid on line vty0 (1.1.1.1)',
                                           'msg_orig'  => 'Mar 19 06:48:12.692: spb-rad-sw1: %STKUNIT0-M:CP %SEC-5-LOGIN_SUCCESS: Login successful for user rancid on line vty0 (1.1.1.1)',
                                           ));

    // XOS
    $result[] = array('xos||20||4||4||<Warn:||2018-06-08 10:02:09||NetTools.SNTP.SrvrNameNotRslved> Slot-1: Host name timesrv1 is not DNS resolved yet||<Warn',
                                     array('facility'  => 'local4', 'priority' => '4', 'level' => '4',
                                           'tag'       => 'SNTP,SrvrNameNotRslved', 'program' => 'NETTOOLS',
                                           'msg'       => 'Slot-1: Host name timesrv1 is not DNS resolved yet',
                                           'msg_orig'  => 'NetTools.SNTP.SrvrNameNotRslved> Slot-1: Host name timesrv1 is not DNS resolved yet',
                                           ));
    $result[] = array('xos||20||6||6||<Info:||2018-06-08 10:02:09||vlan.msgs.portLinkStateDown> MSM-B: Port 4:32 link down||<Info',
                                     array('facility'  => 'local4', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'msgs,portLinkStateDown', 'program' => 'VLAN',
                                           'msg'       => 'MSM-B: Port 4:32 link down',
                                           'msg_orig'  => 'vlan.msgs.portLinkStateDown> MSM-B: Port 4:32 link down',
                                           ));
    $result[] = array('xos||20||5||5||<Noti:||2018-06-08 10:02:09||DM.Notice> Slot-1: Setting hwclock time to system time, and broadcasting time||<Noti',
                                     array('facility'  => 'local4', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'Notice', 'program' => 'DM',
                                           'msg'       => 'Slot-1: Setting hwclock time to system time, and broadcasting time',
                                           'msg_orig'  => 'DM.Notice> Slot-1: Setting hwclock time to system time, and broadcasting time',
                                           ));
    $result[] = array('xos||20||4||4||<Warn:||2018-06-08 03:32:55||NetTools.SNTP.TxReqToSrvrFail> Slot-1: Failed to send SNTP request to server 160.103.4.115||<Warn',
                                     array('facility'  => 'local4', 'priority' => '4', 'level' => '4',
                                           'tag'       => 'SNTP,TxReqToSrvrFail', 'program' => 'NETTOOLS',
                                           'msg'       => 'Slot-1: Failed to send SNTP request to server 160.103.4.115',
                                           'msg_orig'  => 'NetTools.SNTP.TxReqToSrvrFail> Slot-1: Failed to send SNTP request to server 160.103.4.115',
                                           ));
    $result[] = array('xos||20||6||6||<Info:||2018-06-08 10:02:11||vlan.msgs.portLinkStateUp> MSM-B: Port 4:32 link UP at speed 100 Mbps and full-duplex||<Info',
                                     array('facility'  => 'local4', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'msgs,portLinkStateUp', 'program' => 'VLAN',
                                           'msg'       => 'MSM-B: Port 4:32 link UP at speed 100 Mbps and full-duplex',
                                           'msg_orig'  => 'vlan.msgs.portLinkStateUp> MSM-B: Port 4:32 link UP at speed 100 Mbps and full-duplex',
                                           ));

    // Arista EOS
    $result[] = array('arista_eos||local4||5||notice||a5||2018-08-27 12:19:52||%SYS-5-CONFIG_STARTUP: Startup config saved from system:/running-config by admin on vty6 (172.21.131.237).||Cli',
                                     array('facility'  => 'local4', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'CONFIG_STARTUP', 'program' => 'SYS',
                                           'msg'       => 'Startup config saved from system:/running-config by admin on vty6 (172.21.131.237).',
                                           'msg_orig'  => '%SYS-5-CONFIG_STARTUP: Startup config saved from system:/running-config by admin on vty6 (172.21.131.237).',
                                           ));

    // HPE Switches
    $result[] = array('hh3c||local7||5||notice||bd||2018-08-27 12:21:31||Line protocol on the interface Ten-GigabitEthernet1/0/38 is up.||%10IFNET/5/LINK_UPDOWN',
                                     array('facility'  => 'local7', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'LINK_UPDOWN', 'program' => 'IFNET',
                                           'msg'       => 'Line protocol on the interface Ten-GigabitEthernet1/0/38 is up.',
                                           'msg_orig'  => 'Line protocol on the interface Ten-GigabitEthernet1/0/38 is up.',
                                           ));

    $result[] = array('hh3c||local7||6||6||bd||2018-08-27 12:21:31||Member port XGE1/0/33 of aggregation group BAGG33 changed to the inactive state, because the physical state of the port is down.||%10LAGG/6/LAGG_INACTIVE_PHYSTATE',
                                     array('facility'  => 'local7', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'LAGG_INACTIVE_PHYSTATE', 'program' => 'LAGG',
                                           'msg'       => 'Member port XGE1/0/33 of aggregation group BAGG33 changed to the inactive state, because the physical state of the port is down.',
                                           'msg_orig'  => 'Member port XGE1/0/33 of aggregation group BAGG33 changed to the inactive state, because the physical state of the port is down.',
                                           ));

        // ArubaOS
        $result[] = [ 'arubaos||1||6||6||00413||2023-10-11 17:37:28|| SNTP:  Updated time by 4 seconds from server at 192.129.28.2. Previous time was Wed Oct 11 17:37:24 2023. Current time is Wed Oct 11 17:37:28 2023.||00413',
                      [ 'facility'  => 'user', 'priority' => '6', 'level' => '6',
                        'tag'       => '00413', 'program' => 'SNTP',
                        'msg'       => 'Updated time by 4 seconds from server at 192.129.28.2. Previous time was Wed Oct 11 17:37:24 2023. Current time is Wed Oct 11 17:37:28 2023.',
                        'msg_orig'  => 'SNTP:  Updated time by 4 seconds from server at 192.129.28.2. Previous time was Wed Oct 11 17:37:24 2023. Current time is Wed Oct 11 17:37:28 2023.', ]
        ];
        $result[] = [ 'arubaos||1||6||6||00076||2023-10-11 16:13:18|| ports:  port 38 is now on-line||00076',
                      [ 'facility'  => 'user', 'priority' => '6', 'level' => '6',
                        'tag'       => '00076', 'program' => 'PORTS',
                        'msg'       => 'port 38 is now on-line',
                        'msg_orig'  => 'ports:  port 38 is now on-line', ]
        ];

        // ArubaOS-CX
        $result[] = [ 'arubaos-cx||23||6||6||log-proxyd[2872]||2023-10-06 13:50:24||Event|5209|LOG_INFO|AMM|1/5|User !raabc logged in from 10.30.2.245 through SSH session.||log-proxyd',
                        [ 'facility'  => 'local7', 'priority' => '6', 'level' => '6',
                          'tag'       => '5209,AMM,1/5', 'program' => 'LOG-PROXYD',
                          'msg'       => 'User !raabc logged in from 10.30.2.245 through SSH session.',
                          'msg_orig'  => 'Event|5209|LOG_INFO|AMM|1/5|User !raabc logged in from 10.30.2.245 through SSH session.', ]
        ];
        $result[] = [ 'arubaos-cx||23||6||6||lacpd[3920]||2023-02-23 12:26:49||Event|1307|LOG_INFO|UMM|-|LACP system ID set to 08:f1:ea:61:2a:00||lacpd',
                      [ 'facility'  => 'local7', 'priority' => '6', 'level' => '6',
                        'tag'       => '1307,UMM', 'program' => 'LACPD',
                        'msg'       => 'LACP system ID set to 08:f1:ea:61:2a:00',
                        'msg_orig'  => 'Event|1307|LOG_INFO|UMM|-|LACP system ID set to 08:f1:ea:61:2a:00', ]
        ];
        $result[] = [ 'arubaos-cx||23||2||2||crash-tools[3920]||2023-02-23 12:26:49||Event|1206|LOG_CRIT|||Module rebooted. Reason : Reboot requested by user, Version: XL.10.10.1020#012, Boot-ID : fed7f6e9e75f4d95a4fb3b4643f024d5||crash-tools',
                      [ 'facility'  => 'local7', 'priority' => '2', 'level' => '2',
                        'tag'       => '1206', 'program' => 'CRASH-TOOLS',
                        'msg'       => 'Module rebooted. Reason : Reboot requested by user, Version: XL.10.10.1020#012, Boot-ID : fed7f6e9e75f4d95a4fb3b4643f024d5',
                        'msg_orig'  => 'Event|1206|LOG_CRIT|||Module rebooted. Reason : Reboot requested by user, Version: XL.10.10.1020#012, Boot-ID : fed7f6e9e75f4d95a4fb3b4643f024d5', ]
        ];

    // NS-BSD
    $result[] = array('ns-bsd||user||5||notice||0d||2018-10-16 18:13:05||2018-10-16T18:13:03+02:00 fw.hostname.net tproxyd - - - ﻿id=firewall time=\"2018-10-16 18:13:03\" fw=\"fw.hostname.net\" tz=+0200 startime=\"2018-10-16 18:13:02\" pri=5 proto=http confid=1 slotlevel=2 ruleid=9 rulename=\"16663e6700f_5\" op=GET result=416 user=\"\" domain=\"\" src=192.168.0.148 srcport=59365 srcportname=ephemeral_fw_tcp dst=88.221.145.155 dstport=80 dstportname=http srcmac=54:27:1e:5c:22:bb dstname=2.tlu.dl.delivery.mp.microsoft.com modsrc=192.168.1.2 modsrcport=9619 origdst=88.221.145.155 origdstport=80 ipv=4 sent=484 rcvd=0 duration=0.00 dstcontinent=\"eu\" dstcountry=\"it\" action=block contentpolicy=1 urlruleid=2 cat_site=\"vpnssl_owa\" arg=\"/filestreamingservice/files/5fa99684-8931-4eff-bc5d-27ff2a406a31%3FP1%3D1539706618%26P2%3D402%26P3%3D2%26P4%3DHCvXZchoZYgzbdK0XkO2CelafFHcA%252bUrQcgT5u%252b6WDwtpi5jZJu%252fUjCHpbffRGU5iG8kuHW1C5uQ68drtUWh%252fA%253d%253d\" msg=\"Requested range not satisfiable\" logtype=\"web\"||1',
                                     array('facility'  => 'user', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'firewall', 'program' => 'TPROXYD',
                                           'msg'       => '﻿id=firewall time="2018-10-16 18:13:03" fw="fw.hostname.net" tz=+0200 startime="2018-10-16 18:13:02" pri=5 proto=http confid=1 slotlevel=2 ruleid=9 rulename="16663e6700f_5" op=GET result=416 user="" domain="" src=192.168.0.148 srcport=59365 srcportname=ephemeral_fw_tcp dst=88.221.145.155 dstport=80 dstportname=http srcmac=54:27:1e:5c:22:bb dstname=2.tlu.dl.delivery.mp.microsoft.com modsrc=192.168.1.2 modsrcport=9619 origdst=88.221.145.155 origdstport=80 ipv=4 sent=484 rcvd=0 duration=0.00 dstcontinent="eu" dstcountry="it" action=block contentpolicy=1 urlruleid=2 cat_site="vpnssl_owa" arg="/filestreamingservice/files/5fa99684-8931-4eff-bc5d-27ff2a406a31%3FP1%3D1539706618%26P2%3D402%26P3%3D2%26P4%3DHCvXZchoZYgzbdK0XkO2CelafFHcA%252bUrQcgT5u%252b6WDwtpi5jZJu%252fUjCHpbffRGU5iG8kuHW1C5uQ68drtUWh%252fA%253d%253d" msg="Requested range not satisfiable" logtype="web"',
                                           'msg_orig'  => '2018-10-16T18:13:03+02:00 fw.hostname.net tproxyd - - - ﻿id=firewall time="2018-10-16 18:13:03" fw="fw.hostname.net" tz=+0200 startime="2018-10-16 18:13:02" pri=5 proto=http confid=1 slotlevel=2 ruleid=9 rulename="16663e6700f_5" op=GET result=416 user="" domain="" src=192.168.0.148 srcport=59365 srcportname=ephemeral_fw_tcp dst=88.221.145.155 dstport=80 dstportname=http srcmac=54:27:1e:5c:22:bb dstname=2.tlu.dl.delivery.mp.microsoft.com modsrc=192.168.1.2 modsrcport=9619 origdst=88.221.145.155 origdstport=80 ipv=4 sent=484 rcvd=0 duration=0.00 dstcontinent="eu" dstcountry="it" action=block contentpolicy=1 urlruleid=2 cat_site="vpnssl_owa" arg="/filestreamingservice/files/5fa99684-8931-4eff-bc5d-27ff2a406a31%3FP1%3D1539706618%26P2%3D402%26P3%3D2%26P4%3DHCvXZchoZYgzbdK0XkO2CelafFHcA%252bUrQcgT5u%252b6WDwtpi5jZJu%252fUjCHpbffRGU5iG8kuHW1C5uQ68drtUWh%252fA%253d%253d" msg="Requested range not satisfiable" logtype="web"',
                                           ));
    $result[] = array('ns-bsd||user||1||alert||09||2018-10-16 18:14:35||2018-10-16T18:14:35+02:00 na_carfi asqd - - - ﻿id=firewall time=\"2018-10-16 18:14:35\" fw=\"na_carfi\" tz=+0200 startime=\"2018-10-16 18:14:34\" pri=1 confid=00 srcif=\"Ethernet1\" srcifname=\"in\" ipproto=udp proto=udp src=172.16.0.27 srcport=17500 srcmac=00:0c:29:09:f4:44 dst=255.255.255.255 dstport=17500 dstname=broadcast ipv=4 action=block msg=\"IP address spoofing (type=1)\" class=protocol classification=0 alarmid=1 target=dst logtype=\"alarm\"||1',
                                     array('facility'  => 'user', 'priority' => '1', 'level' => '1',
                                           'tag'       => 'firewall', 'program' => 'ASQD',
                                           'msg'       => '﻿id=firewall time="2018-10-16 18:14:35" fw="na_carfi" tz=+0200 startime="2018-10-16 18:14:34" pri=1 confid=00 srcif="Ethernet1" srcifname="in" ipproto=udp proto=udp src=172.16.0.27 srcport=17500 srcmac=00:0c:29:09:f4:44 dst=255.255.255.255 dstport=17500 dstname=broadcast ipv=4 action=block msg="IP address spoofing (type=1)" class=protocol classification=0 alarmid=1 target=dst logtype="alarm"',
                                           'msg_orig'  => '2018-10-16T18:14:35+02:00 na_carfi asqd - - - ﻿id=firewall time="2018-10-16 18:14:35" fw="na_carfi" tz=+0200 startime="2018-10-16 18:14:34" pri=1 confid=00 srcif="Ethernet1" srcifname="in" ipproto=udp proto=udp src=172.16.0.27 srcport=17500 srcmac=00:0c:29:09:f4:44 dst=255.255.255.255 dstport=17500 dstname=broadcast ipv=4 action=block msg="IP address spoofing (type=1)" class=protocol classification=0 alarmid=1 target=dst logtype="alarm"',
                                           ));

    // Test filter syslog messages
    $result[] = array('linux||12||3||3||last||2018-02-16 16:15:49|| some someTESTxx||last',
                                     FALSE);
    $result[] = array('linux||12||3||3||last||2018-02-16 16:15:49|| some some TEST xx||last',
                                     FALSE);
    $result[] = array('linux||12||3||3||last||2018-02-16 16:15:49|| some derpmygod ||last',
                                     FALSE);
    $result[] = array('linux||12||3||3||last||2018-02-16 16:15:49|| some derp xx||last',
                                     FALSE);
    // Filters case sensitive
    $result[] = array('linux||12||3||3||last||2018-02-16 16:15:49|| some some TeSTxx||last',
                                     array('facility'  => 'ntp', 'priority' => '3', 'level' => '3',
                                           'tag'       => 'last', 'program' => 'LAST',
                                           'msg'       => 'some some TeSTxx',
                                           'msg_orig'  => 'some some TeSTxx',
                                           ));

    $result[] = array('mikrotik||user||5||notice||0d||2018-03-23 07:48:39||dhcp105 assigned 192.168.58.84 to 80:BE:05:7A:73:6E||dhcp,info',
                                     array('facility'  => 'user', 'priority' => '6', 'level' => '5',
                                           'tag'       => 'info', 'program' => 'DHCP',
                                           'msg'       => 'dhcp105 assigned 192.168.58.84 to 80:BE:05:7A:73:6E',
                                           'msg_orig'  => 'dhcp105 assigned 192.168.58.84 to 80:BE:05:7A:73:6E',
                                           ));
    $result[] = array('mikrotik-swos||user||5||notice||0d||2018-03-23 08:55:41||CompDHCP assigned 10.0.0.222 to 4C:32:75:90:69:33||dhcp,info',
                                     array('facility'  => 'user', 'priority' => '6', 'level' => '5',
                                           'tag'       => 'info', 'program' => 'DHCP',
                                           'msg'       => 'CompDHCP assigned 10.0.0.222 to 4C:32:75:90:69:33',
                                           'msg_orig'  => 'CompDHCP assigned 10.0.0.222 to 4C:32:75:90:69:33',
                                           ));

    // HP
    $result[] = array('procurve||5||4||4||02672||2018-04-26 15:38:35|| FFI:  port 11-Excessive link state transitions||02672',
                                     array('facility'  => 'syslog', 'priority' => '4', 'level' => '4',
                                           'tag'       => 'FFI', 'program' => 'FFI',
                                           'msg'       => 'port 11-Excessive link state transitions',
                                           'msg_orig'  => 'FFI:  port 11-Excessive link state transitions',
                                           ));

    // Sophos
    $result[] = array('sophos||5||5||5||2018:||2018-05-04 14:44:36||05:04-12:44:36 utm syslog-ng[5202]: Syslog connection established; fd=\'34\', server=\'AF_INET(77.222.50.30:514)\', local=\'AF_INET(0.0.0.0:0)\'||2018',
                                     array('facility'  => 'syslog', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'syslog-ng[5202]', 'program' => 'SYSLOG-NG',
                                           'msg'       => 'Syslog connection established; fd=\'34\', server=\'AF_INET(77.222.50.30:514)\', local=\'AF_INET(0.0.0.0:0)\'',
                                           'msg_orig'  => '05:04-12:44:36 utm syslog-ng[5202]: Syslog connection established; fd=\'34\', server=\'AF_INET(77.222.50.30:514)\', local=\'AF_INET(0.0.0.0:0)\'',
                                           ));
    $result[] = array('sophos||22||5||5||2018:||2018-05-04 14:45:17||05:04-12:45:17 utm httpd: 213.230.218.10 - - [04/May/2018:12:45:17 +0100] "POST /webadmin.plx HTTP/1.1" 200 609||2018',
                                     array('facility'  => 'local6', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'httpd', 'program' => 'HTTPD',
                                           'msg'       => '213.230.218.10 - - [04/May/2018:12:45:17 +0100] "POST /webadmin.plx HTTP/1.1" 200 609',
                                           'msg_orig'  => '05:04-12:45:17 utm httpd: 213.230.218.10 - - [04/May/2018:12:45:17 +0100] "POST /webadmin.plx HTTP/1.1" 200 609',
                                           ));
    $result[] = array('sophos||3||6||6||2018:||2018-05-04 14:44:43||05:04-12:44:43 utm ulogd[5157]: id="2001" severity="info" sys="SecureNet" sub="packetfilter" name="Packet dropped" action="drop" fwrule="60002" initf="eth0" outitf="eth0" srcmac="d4:ca:6d:22:93:8c" dstmac="00:50:56:a1:7b:6c" srcip="5.49.1.8" dstip="1.20.20.9" proto="6" length="60" tos="0x00" prec="0x00" ttl="49" srcport="3666" dstport="23" tcpflags="SYN" ||2018',
                                     array('facility'  => 'daemon', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'ulogd[5157]', 'program' => 'ULOGD',
                                           'msg'       => 'id="2001" severity="info" sys="SecureNet" sub="packetfilter" name="Packet dropped" action="drop" fwrule="60002" initf="eth0" outitf="eth0" srcmac="d4:ca:6d:22:93:8c" dstmac="00:50:56:a1:7b:6c" srcip="5.49.1.8" dstip="1.20.20.9" proto="6" length="60" tos="0x00" prec="0x00" ttl="49" srcport="3666" dstport="23" tcpflags="SYN"',
                                           'msg_orig'  => '05:04-12:44:43 utm ulogd[5157]: id="2001" severity="info" sys="SecureNet" sub="packetfilter" name="Packet dropped" action="drop" fwrule="60002" initf="eth0" outitf="eth0" srcmac="d4:ca:6d:22:93:8c" dstmac="00:50:56:a1:7b:6c" srcip="5.49.1.8" dstip="1.20.20.9" proto="6" length="60" tos="0x00" prec="0x00" ttl="49" srcport="3666" dstport="23" tcpflags="SYN"',
                                           ));
    $result[] = array('sophos||9||6||6||2018:||2018-05-04 14:45:01||05:04-12:45:01 utm /usr/sbin/cron[11517]: (root) CMD ( /usr/local/bin/rpmdb_backup )||2018',
                                     array('facility'  => 'cron', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'cron[11517]', 'program' => 'CRON',
                                           'msg'       => '(root) CMD ( /usr/local/bin/rpmdb_backup )',
                                           'msg_orig'  => '05:04-12:45:01 utm /usr/sbin/cron[11517]: (root) CMD ( /usr/local/bin/rpmdb_backup )',
                                           ));
    $result[] = array('sophos||3||5||5||2018:||2018-05-04 14:44:37||05:04-12:44:37 utm openvpn[5068]: MANAGEMENT: CMD \'status -1\'||2018',
                                     array('facility'  => 'daemon', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'openvpn[5068],MANAGEMENT', 'program' => 'OPENVPN',
                                           'msg'       => 'CMD \'status -1\'',
                                           'msg_orig'  => '05:04-12:44:37 utm openvpn[5068]: MANAGEMENT: CMD \'status -1\'',
                                           ));

    // Brocade
    $result[] = [
      'nos||21||6||6||raslogd:||2021-03-23 16:41:27|| [log@1588 value="RASLOG"][timestamp@1588 value="2021-03-23T16:41:27.188825"][msgid@1588 value="L2SS-1032"][seqnum@1588 value="94245"][attr@1588 value=" SW/0 | Active | DCE | WWN 10:00:50:eb:1a:ce:e7:44"][severity@1588 value="INFO"][swname@1588 value="VDX_1R01S08"] BOMENS Checksum Mismatch reached maximum threshold(3) for Rbridge:21. Requesting MAC refresh from Rbridge:21.||raslogd',
      [ 'facility'  => 'local5', 'priority' => '6', 'level' => '6',
        'tag'       => 'L2SS-1032', 'program' => 'RASLOG',
        'msg'       => 'ENS Checksum Mismatch reached maximum threshold(3) for Rbridge:21. Requesting MAC refresh from Rbridge:21.',
        'msg_orig'  => '[log@1588 value="RASLOG"][timestamp@1588 value="2021-03-23T16:41:27.188825"][msgid@1588 value="L2SS-1032"][seqnum@1588 value="94245"][attr@1588 value=" SW/0 | Active | DCE | WWN 10:00:50:eb:1a:ce:e7:44"][severity@1588 value="INFO"][swname@1588 value="VDX_1R01S08"] BOMENS Checksum Mismatch reached maximum threshold(3) for Rbridge:21. Requesting MAC refresh from Rbridge:21.',
      ]
    ];
    $result[] = [
      'nos||21||6||6||raslogd:||2021-03-23 16:41:31|| [log@1588 value="RASLOG"][timestamp@1588 value="2021-03-23T16:41:31.621350"][msgid@1588 value="SEC-1203"][seqnum@1588 value="184473"][attr@1588 value=" SW/0 | Active | WWN 10:00:c4:f5:7c:65:44:f4"][severity@1588 value="INFO"][swname@1588 value="VDX_TESPOP1"][arg0@1588 value="10.101.10.8" desc="IP address"] BOMLogin information: Login successful via TELNET/SSH/RSH. IP Addr: 10.101.10.8.||raslogd',
      [ 'facility'  => 'local5', 'priority' => '6', 'level' => '6',
        'tag'       => 'SEC-1203', 'program' => 'RASLOG',
        'msg'       => 'Login information: Login successful via TELNET/SSH/RSH. IP Addr: 10.101.10.8.',
        'msg_orig'  => '[log@1588 value="RASLOG"][timestamp@1588 value="2021-03-23T16:41:31.621350"][msgid@1588 value="SEC-1203"][seqnum@1588 value="184473"][attr@1588 value=" SW/0 | Active | WWN 10:00:c4:f5:7c:65:44:f4"][severity@1588 value="INFO"][swname@1588 value="VDX_TESPOP1"][arg0@1588 value="10.101.10.8" desc="IP address"] BOMLogin information: Login successful via TELNET/SSH/RSH. IP Addr: 10.101.10.8.',
      ]
    ];
    $result[] = [
      'nos||21||6||6||raslogd:||2021-03-23 16:41:31|| [log@1588 value="AUDIT"][timestamp@1588 value="2021-03-23T16:41:31.623184"][tz@1588 value="CET"][msgid@1588 value="SEC-3020"][severity@1588 value="INFO"][class@1588 value="SECURITY"][user@1588 value="qwerty"][role@1588 value="admin"][ip@1588 value="10.101.10.8"][interface@1588 value="ssh"][application@1588 value="CLI"][swname@1588 value="VDX_TESPOP1"][arg0@1588 value="login" desc="Event Name"][arg1@1588 value="REMOTE, IP Addr: 10.101.10.8" desc="connection method and IP Address"] BOMEvent: login, Status: success, Info: Successful login attempt via REMOTE, IP Addr: 10.101.10.8.||raslogd',
      [ 'facility'  => 'local5', 'priority' => '6', 'level' => '6',
        'tag'       => 'SEC-3020,ssh,CLI', 'program' => 'AUDIT',
        'msg'       => 'Event: login, Status: success, Info: Successful login attempt via REMOTE, IP Addr: 10.101.10.8.',
        'msg_orig'  => '[log@1588 value="AUDIT"][timestamp@1588 value="2021-03-23T16:41:31.623184"][tz@1588 value="CET"][msgid@1588 value="SEC-3020"][severity@1588 value="INFO"][class@1588 value="SECURITY"][user@1588 value="qwerty"][role@1588 value="admin"][ip@1588 value="10.101.10.8"][interface@1588 value="ssh"][application@1588 value="CLI"][swname@1588 value="VDX_TESPOP1"][arg0@1588 value="login" desc="Event Name"][arg1@1588 value="REMOTE, IP Addr: 10.101.10.8" desc="connection method and IP Address"] BOMEvent: login, Status: success, Info: Successful login attempt via REMOTE, IP Addr: 10.101.10.8.',
      ]
    ];

    // Dell
    $result[] = [
      'dnos6||23||6||6||CLI_WEB[emWeb]:||2021-05-13 14:20:35|| cmd_logger_api.c(260) 577 %% [WEB:admin:172.90.1.128] User has logged out||CLI_WEB',
      [ 'facility'  => 'local7', 'priority' => '6', 'level' => '6',
        'tag'       => 'emWeb,cmd_logger_api.c', 'program' => 'CLI_WEB',
        'msg'       => '[WEB:admin:172.90.1.128] User has logged out',
        'msg_orig'  => 'cmd_logger_api.c(260) 577 %% [WEB:admin:172.90.1.128] User has logged out',
      ]
    ];
    $result[] = [
      'dnos6||23||5||5||TRAPMGR[trapTask]:||2021-05-13 14:21:33|| traputil.c(721) 580 %% \'startup-config\' has changed.||TRAPMGR',
      [ 'facility'  => 'local7', 'priority' => '5', 'level' => '5',
        'tag'       => 'trapTask,traputil.c', 'program' => 'TRAPMGR',
        'msg'       => '\'startup-config\' has changed.',
        'msg_orig'  => 'traputil.c(721) 580 %% \'startup-config\' has changed.',
      ]
    ];

    // Tests from issues
    // OBS-335
    $result[] = array('cisco-uc||9||6||6||349:||2013-05-30 16:40:29|| : crond[9538]: (root) CMD (  /etc/rc.d/init.d/fiostats show)||349',
                                     array('facility'  => 'cron', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'crond[9538]', 'program' => 'CROND',
                                           'msg'       => '(root) CMD (  /etc/rc.d/init.d/fiostats show)',
                                           'msg_orig'  => ': crond[9538]: (root) CMD (  /etc/rc.d/init.d/fiostats show)',
                                           ));
    $result[] = array('cisco-uc||1||5||5||349:||2013-05-30 16:40:29|| : logrotate: ALERT exited abnormally with [1]||349',
                                     array('facility'  => 'user', 'priority' => '5', 'level' => '5',
                                           'tag'       => 'logrotate', 'program' => 'LOGROTATE',
                                           'msg'       => 'ALERT exited abnormally with [1]',
                                           'msg_orig'  => ': logrotate: ALERT exited abnormally with [1]',
                                           ));
    $result[] = array('cisco-uc||9||6||6||349:||2013-05-30 16:40:29|| : : 7273: May 30 15:33:20.636 UTC :  %CCM_RTMT-RTMT-2-RTMT-ERROR-ALERT: RTMT Alert Name:DBChangeNotifyFailure Detail: DBChangeNotify queue delay over 2 minutes. Current DB ChangeNotify queue delay (135) is over 120-sec threshold. The alert is generated on Thu May 30 15:33:20 GMT+00:00 2013 on node CallManager. App ID:Cisco AMC Service Cluster ID: Node ID:CallManager||349',
                                     array('facility'  => 'cron', 'priority' => '6', 'level' => '6',
                                           'tag'       => 'RTMT,ERROR-ALERT', 'program' => 'RTMT',
                                           'msg'       => 'RTMT Alert Name:DBChangeNotifyFailure Detail: DBChangeNotify queue delay over 2 minutes. Current DB ChangeNotify queue delay (135) is over 120-sec threshold. The alert is generated on Thu May 30 15:33:20 GMT+00:00 2013 on node CallManager. App ID:Cisco AMC Service Cluster ID: Node ID:CallManager',
                                           'msg_orig'  => ': : 7273: May 30 15:33:20.636 UTC :  %CCM_RTMT-RTMT-2-RTMT-ERROR-ALERT: RTMT Alert Name:DBChangeNotifyFailure Detail: DBChangeNotify queue delay over 2 minutes. Current DB ChangeNotify queue delay (135) is over 120-sec threshold. The alert is generated on Thu May 30 15:33:20 GMT+00:00 2013 on node CallManager. App ID:Cisco AMC Service Cluster ID: Node ID:CallManager',
                                           ));
    // OBS-760
    $result[] = array('linux||mail||info||info||16||2014-04-02 12:03:32||A87EC201127: to=<address@hotmail.com>, relay=10.1.1.185[10.1.1.185]:25, delay=1.3, delays=0.18/0.01/0.01/1.1, dsn=2.6.0, status=sent (250 2.6.0 <20140402160331.A87EC201127@app-web1.domain.edu> [InternalId=20728197] Queued mail for delivery)||postfix/smtp',
                                     array('facility'  => 'mail', 'priority' => '6', 'level' => '6',
                                           'tag'       => '16,A87EC201127,smtp', 'program' => 'POSTFIX',
                                           'msg'       => 'A87EC201127: to=<address@hotmail.com>, relay=10.1.1.185[10.1.1.185]:25, delay=1.3, delays=0.18/0.01/0.01/1.1, dsn=2.6.0, status=sent (250 2.6.0 <20140402160331.A87EC201127@app-web1.domain.edu> [InternalId=20728197] Queued mail for delivery)',
                                           'msg_orig'  => 'A87EC201127: to=<address@hotmail.com>, relay=10.1.1.185[10.1.1.185]:25, delay=1.3, delays=0.18/0.01/0.01/1.1, dsn=2.6.0, status=sent (250 2.6.0 <20140402160331.A87EC201127@app-web1.domain.edu> [InternalId=20728197] Queued mail for delivery)',
                                           ));
    $result[] = array('linux||local0||notice||notice||85||2014-03-26 12:54:17||Wed Mar 26 12:54:17 2014 : Auth: Login incorrect (mschap: External script says Logon failure (0xc000006d)): [username] (from client 10.100.1.3 port 0 cli a4c3612a4077 via TLS tunnel)||Auth',
                                     array('facility'  => 'local0', 'priority' => '5', 'level' => '5',
                                           'tag'       => '85,mschap', 'program' => 'AUTH',
                                           'msg'       => 'Login incorrect (mschap: External script says Logon failure (0xc000006d)): [username] (from client 10.100.1.3 port 0 cli a4c3612a4077 via TLS tunnel)',
                                           'msg_orig'  => 'Wed Mar 26 12:54:17 2014 : Auth: Login incorrect (mschap: External script says Logon failure (0xc000006d)): [username] (from client 10.100.1.3 port 0 cli a4c3612a4077 via TLS tunnel)',
                                           ));

    // OBS-3614
    $result[] = [ 'netscaler||16||5||5||||2021-01-26 13:47:07|| 01/26/2021:12:47:07 GMT DCRX-ANS-N004 0-PPE-0 : default EVENT DEVICEDOWN 62431870 0 :  Device "server_serviceGroup_NSSVC_TCP_172.16.200.150:636(SVG_TST_LDAPS_ADMB?DC-BRU-150?636)" - State DOWN||',
                  [ 'facility'  => 'local0', 'priority' => '5', 'level' => '5',
                    'tag'       => 'DEVICEDOWN', 'program' => 'EVENT',
                    'msg'       => 'Device "server_serviceGroup_NSSVC_TCP_172.16.200.150:636(SVG_TST_LDAPS_ADMB?DC-BRU-150?636)" - State DOWN',
                    'msg_orig'  => '01/26/2021:12:47:07 GMT DCRX-ANS-N004 0-PPE-0 : default EVENT DEVICEDOWN 62431870 0 :  Device "server_serviceGroup_NSSVC_TCP_172.16.200.150:636(SVG_TST_LDAPS_ADMB?DC-BRU-150?636)" - State DOWN',
                  ]
    ];
    $result[] = [ 'netscaler||16||5||5||||2021-01-26 13:47:07|| 10/03/2013:16:49:07 GMT dk-lb001a PPE-4 : UI CMD_EXECUTED 10367926 : User so_readonly - Remote_ip 10.70.66.56 - Command "stat lb vserver" - Status "Success"||',
                  [ 'facility'  => 'local0', 'priority' => '5', 'level' => '5',
                    'tag'       => 'CMD_EXECUTED', 'program' => 'UI',
                    'msg'       => 'User so_readonly - Remote_ip 10.70.66.56 - Command "stat lb vserver" - Status "Success"',
                    'msg_orig'  => '10/03/2013:16:49:07 GMT dk-lb001a PPE-4 : UI CMD_EXECUTED 10367926 : User so_readonly - Remote_ip 10.70.66.56 - Command "stat lb vserver" - Status "Success"',
                  ]
    ];

    return $result;
  }
}

// EOF
