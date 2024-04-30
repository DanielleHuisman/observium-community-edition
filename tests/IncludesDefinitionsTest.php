<?php

//define('OBS_DEBUG', 1);

$base_dir = realpath(__DIR__ . '/..');
$config['install_dir'] = $base_dir;

include(__DIR__ . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php'); // Do not include user editable config here
include(__DIR__ . "/../includes/polyfill.inc.php");
include(__DIR__ . "/../includes/autoloader.inc.php");
include(__DIR__ . "/../includes/debugging.inc.php");
require_once(__DIR__ ."/../includes/constants.inc.php");
include(__DIR__ . '/../includes/common.inc.php');
include(__DIR__ . '/../includes/definitions.inc.php');
//include(dirname(__FILE__) . '/data/test_definitions.inc.php'); // Fake definitions for testing
include(__DIR__ . '/../includes/functions.inc.php');

class IncludesDefinitionsTest extends \PHPUnit\Framework\TestCase {
    /**
    * @dataProvider providerOsRegex
    * @group regex
    */
    public function testOsRegex($type, $name, $param, $pattern) {
        $string = 'akjs//?dnasjdn28ye2384y2(&*&(*&  '; // Just fake test string
        preg_match($pattern, $string);
        $preg_error = array_flip(get_defined_constants(true)['pcre'])[preg_last_error()];

        // Additional error display
        //    if ($preg_error != 'PREG_NO_ERROR')
        //    {
        //      echo("\n$type -> $name -> $param -> $pattern\n");
        //    }

        $this->assertSame('PREG_NO_ERROR', $preg_error);
    }

    public function providerOsRegex() {
        global $config;

        $array = [];
        foreach ([ 'os_group', 'os', 'mibs'] as $type) {
            foreach ($config[$type] as $name => $entry) {
                foreach ($entry as $param => $def) {
                    if (in_array($param, [ 'sysDescr', 'sysDescr_regex', 'port_label', 'syslog_msg', 'syslog_program', 'comments' ])) {
                        // simple definitions with regex patterns
                        foreach ($def as $pattern) {
                            $array[] = [ $type, $name, $param, $pattern ];
                        }
                    } elseif ($param === 'discovery') {
                        // discovery definition, additional array level
                        foreach ($def as $disovery) {
                            foreach ($disovery as $discovery_param => $patterns) {
                                if (in_array($discovery_param, [ 'sysObjectID', 'os', 'os_group', 'type', 'vendor' ])) { continue; } // All except sysObjectID is regexp
                                foreach ((array)$patterns as $pattern) {
                                    $array[] = [ $type, $name, $param . '->' . $discovery_param, $pattern ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $array;
    }

    /**
    * @dataProvider providerDefinitionPatterns
    * @group constants
    */
    public function testDefinitionPatterns($pattern, $string, $result, $match = NULL) {
        $test = preg_match($pattern, $string, $matches);
        //var_dump($matches);
        $this->assertSame($result, (bool)$test);
        if ($test) {
            if (is_array($match)) {
                $this->assertSame($match, $matches);
            } elseif (!is_null($match)) {
                // Validate $match
                $this->assertSame($match, $matches[1]);
            }
        }
    }

    public function providerDefinitionPatterns() {
        $array = [];

        $pattern = OBS_PATTERN_IPV4_FULL;
        // IPv4 valid
        $array[] = array($pattern, '1.2.3.4',           TRUE, '1.2.3.4');
        $array[] = array($pattern, '255.255.255.255',   TRUE, '255.255.255.255');
        // IPv4 invalid
        $array[] = array($pattern, '1.2.3',             FALSE);
        $array[] = array($pattern, '1.2.3.',            FALSE);
        $array[] = array($pattern, '.1.2.3',            FALSE);
        $array[] = array($pattern, '1.2.3.4.5.6.7.8',   FALSE);
        $array[] = array($pattern, '999.999.999.999',   FALSE);
        $array[] = array($pattern, '299.299.299.299',   FALSE);
        $array[] = array($pattern, '001.002.003.004',   FALSE);
        // IPv4 in strings
        $array[] = array($pattern, '"1.2.3.4"',         TRUE, '1.2.3.4');
        $array[] = array($pattern, '(1.2.3.4)',         TRUE, '1.2.3.4');
        $array[] = array($pattern, '(1.2.3.4, tprrrr)', TRUE, '1.2.3.4');
        $array[] = array($pattern, 'PING is1.nic.local (192.168.10.110): 56 data bytes', TRUE, '192.168.10.110');
        $array[] = array($pattern, '64 bytes from 192.168.10.110: icmp_seq=0 ttl=122 time=10.643 ms', TRUE, '192.168.10.110');
        $array[] = array($pattern, 'Invalid user test from 213.149.105.28',     TRUE, '213.149.105.28');
        $array[] = array($pattern, 'Invalid user test from 213.149.105.28 hs',  TRUE, '213.149.105.28');
        $array[] = array($pattern, 'Invalid user test from 213.149.105.28. Next.', TRUE, '213.149.105.28');
        $array[] = array($pattern, 'Invalid user test from 213.149.105.28sss',  FALSE);

        $pattern = OBS_PATTERN_IPV4_NET_FULL;
        // IPv4 network valid
        $array[] = array($pattern, '1.2.3.4/0',         TRUE, '1.2.3.4/0');
        $array[] = array($pattern, '1.2.3.4/29',        TRUE, '1.2.3.4/29');
        $array[] = array($pattern, '1.2.3.4/32',        TRUE, '1.2.3.4/32');
        // IPv4 network with netmask valid
        $array[] = array($pattern, '1.2.3.4/0.0.0.0',         TRUE, '1.2.3.4/0.0.0.0');
        $array[] = array($pattern, '1.2.3.4/255.255.255.248', TRUE, '1.2.3.4/255.255.255.248');
        $array[] = array($pattern, '1.2.3.4/255.255.255.255', TRUE, '1.2.3.4/255.255.255.255');
        // IPv4 network with Cisco inverse netmask valid
        $array[] = array($pattern, '1.2.3.4/0.0.63.255',      TRUE, '1.2.3.4/0.0.63.255');
        $array[] = array($pattern, '1.2.3.4/0.0.0.1',         TRUE, '1.2.3.4/0.0.0.1');
        $array[] = array($pattern, '1.2.3.4/127.255.255.255', TRUE, '1.2.3.4/127.255.255.255');
        // IPv4 network invalid
        $array[] = array($pattern, '1.2.3.4/-1',        FALSE);
        $array[] = array($pattern, '1.2.3.4/33',        FALSE);
        $array[] = array($pattern, '1.2.3.4/123',       FALSE);
        // IPv4 network with invalid netmask
        $array[] = array($pattern, '1.2.3.4/1.2.3.4',         FALSE);
        $array[] = array($pattern, '1.2.3.4/128.128.128.300', FALSE);
        // IPv4 address (without prefix) also invalid
        $array[] = array($pattern, '1.2.3.4',           FALSE);

        $pattern = OBS_PATTERN_IPV6_FULL;
        // IPv6 valid
        $array[] = array($pattern, '1762:0:0:0:0:B03:1:AF18',     TRUE, '1762:0:0:0:0:B03:1:AF18');
        $array[] = array($pattern, 'FE80:FFFF:0:FFFF:129:144:52:38', TRUE, 'FE80:FFFF:0:FFFF:129:144:52:38');
        $array[] = array($pattern, 'FF01:0:0:0:CA:0:0:2',         TRUE, 'FF01:0:0:0:CA:0:0:2');
        $array[] = array($pattern, '0:0:0:0:0:0:0:1',             TRUE, '0:0:0:0:0:0:0:1');
        $array[] = array($pattern, '0:0:0:0:0:0:0:0',             TRUE, '0:0:0:0:0:0:0:0');
        $array[] = array($pattern, '1762::B03:1:AF18',            TRUE, '1762::B03:1:AF18');
        $array[] = array($pattern, 'FF01:7:CA:0::',               TRUE, 'FF01:7:CA:0::');
        $array[] = array($pattern, '::FF01:7:CA:0',               TRUE, '::FF01:7:CA:0');
        $array[] = array($pattern, '::1',                         TRUE, '::1');
        $array[] = array($pattern, '1::',                         TRUE, '1::');
        $array[] = array($pattern, '::',                          TRUE, '::');
        $array[] = array($pattern, '::1:2:3:4:5:6:7',             TRUE, '::1:2:3:4:5:6:7');
        $array[] = array($pattern, '1:2:3:4:5:6:7::',             TRUE, '1:2:3:4:5:6:7::');
        $array[] = array($pattern, '0:0:0:0:0:0:127.32.67.15',    TRUE, '0:0:0:0:0:0:127.32.67.15');
        $array[] = array($pattern, '0:0:0:0:0:FFFF:127.32.67.15', TRUE, '0:0:0:0:0:FFFF:127.32.67.15');
        $array[] = array($pattern, '::127.32.67.15',              TRUE, '::127.32.67.15');
        $array[] = array($pattern, '::FFFF:127.32.67.15',         TRUE, '::FFFF:127.32.67.15');
        $array[] = array($pattern, 'FFFF::127.32.67.15',          TRUE, 'FFFF::127.32.67.15');
        $array[] = array($pattern, '::1:2:3:4:5:127.32.67.15',    TRUE, '::1:2:3:4:5:127.32.67.15');
        // IPv6 invalid
        $array[] = array($pattern, '1762:0:0:0:0:B03G:1:AF18',    FALSE);
        $array[] = array($pattern, ':127.32.67.15',               FALSE);
        $array[] = array($pattern, ':1234:127.32.67.15',          FALSE);
        $array[] = array($pattern, ':1234:1234:1234',             FALSE);
        $array[] = array($pattern, '1234:1234:1234:',             FALSE);
        $array[] = array($pattern, '1234::234::234::2342',        FALSE);
        $array[] = array($pattern, '1234:1234:1234:1234:1234:1234:1234:1234:1234:1234:1234',    FALSE);
        $array[] = array($pattern, '1234:1234:1234:1234::1234:1234:1234:1234:1234:1234:1234',   FALSE);
        $array[] = array($pattern, '1234:1234:1234:1234::1234:1234:1234:1234:1234::1234:1234',  FALSE);
        // IPv6 in strings
        $array[] = array($pattern, '"FF01:7:CA:0::"',             TRUE, 'FF01:7:CA:0::');
        $array[] = array($pattern, '(FF01:7:CA:0::)',             TRUE, 'FF01:7:CA:0::');
        $array[] = array($pattern, '(FF01:7:CA:0::, hoho)',       TRUE, 'FF01:7:CA:0::');
        $array[] = array($pattern, 'PING6(56=40+8+8 bytes) 2a02:408:8093:fff2::4 --> 2a02:408:7722:41::150', TRUE, '2a02:408:8093:fff2::4');
        $array[] = array($pattern, '16 bytes from 2a02:408:7722:41::150, icmp_seq=0 hlim=62 time=1.717 ms',  TRUE, '2a02:408:7722:41::150');
        $array[] = array($pattern, 'RP/0/RSP0/CPU0:May 31 16:23:46.207 : bgp[1046]: %ROUTING-BGP-5-ADJCHANGE : neighbor 2a02:2090:e400:4400::9:2 Up (VRF: default) (AS: 43489)', TRUE, '2a02:2090:e400:4400::9:2');
        $array[] = array($pattern, 'RP/0/RSP0/CPU0:May 31 16:23:46.207 : bgp[1046]: %ROUTING-BGP-5-ADJCHANGE : neighbor 2a02:2090:e400:4400::9:2',    TRUE, '2a02:2090:e400:4400::9:2');
        $array[] = array($pattern, 'RP/0/RSP0/CPU0:May 31 16:23:46.207 : bgp[1046]: %ROUTING-BGP-5-ADJCHANGE : neighbor 2a02:2090:e400:4400::9:2Up',  FALSE);

        $pattern = OBS_PATTERN_IPV6_NET_FULL;
        // IPv6 network valid
        $array[] = array($pattern, '1762:0:0:0:0:B03:1:AF18/0',     TRUE, '1762:0:0:0:0:B03:1:AF18/0');
        $array[] = array($pattern, '1762:0:0:0:0:B03:1:AF18/29',    TRUE, '1762:0:0:0:0:B03:1:AF18/29');
        $array[] = array($pattern, '1762:0:0:0:0:B03:1:AF18/99',    TRUE, '1762:0:0:0:0:B03:1:AF18/99');
        $array[] = array($pattern, '1762:0:0:0:0:B03:1:AF18/119',   TRUE, '1762:0:0:0:0:B03:1:AF18/119');
        $array[] = array($pattern, '1762:0:0:0:0:B03:1:AF18/128',   TRUE, '1762:0:0:0:0:B03:1:AF18/128');
        // IPv6 network invalid
        $array[] = array($pattern, '1762:0:0:0:0:B03:1:AF18/-1',    FALSE);
        $array[] = array($pattern, '1762:0:0:0:0:B03:1:AF18/129',   FALSE);
        // IPv6 address (without prefix) also invalid
        $array[] = array($pattern, '1762:0:0:0:0:B03:1:AF18',       FALSE);

        $pattern = OBS_PATTERN_IP_FULL;
        // IPv4 OR IPv6 valid (combination of patterns)
        $array[] = array($pattern, '1.2.3.4',           TRUE, '1.2.3.4');
        $array[] = array($pattern, '1762:0:0:0:0:B03:1:AF18',     TRUE, '1762:0:0:0:0:B03:1:AF18');

        $pattern = OBS_PATTERN_MAC_FULL;
        // MAC valid
        $array[] = array($pattern, '0026.22eb.3bef',    TRUE, '0026.22eb.3bef');    // Cisco
        $array[] = array($pattern, '00-02-2D-11-55-4D', TRUE, '00-02-2D-11-55-4D'); // Windows
        $array[] = array($pattern, '00 0D 93 13 51 1A', TRUE, '00 0D 93 13 51 1A'); // Old Unix
        $array[] = array($pattern, '0x000E7F0D81D6',    TRUE, '0x000E7F0D81D6');    // HP-UX
        $array[] = array($pattern, '0004E25AA118',      TRUE, '0004E25AA118');      // DOS, RAW
        $array[] = array($pattern, '00:08:C7:1B:8C:02', TRUE, '00:08:C7:1B:8C:02'); // Unix/Linux
        $array[] = array($pattern, '8:0:86:b6:82:9f',   TRUE, '8:0:86:b6:82:9f');   // SNMP, Solaris
        // MAC invalid
        $array[] = array($pattern, 'F1:0:0:0:CA:0:0:2',   FALSE); // IPv6
        $array[] = array($pattern, '0026.22eb.3be',       FALSE);
        $array[] = array($pattern, '00-2-2D-11-55-4D',    FALSE);
        $array[] = array($pattern, '00 D 93 13 51 1A',    FALSE);
        $array[] = array($pattern, '0x00E7F0D81D6',       FALSE);
        $array[] = array($pattern, '004E25AA118',         FALSE);
        $array[] = array($pattern, '00:0G:C7:1B:8C:02',   FALSE);
        $array[] = array($pattern, '8::86:b6:82:9f',      FALSE);
        $array[] = array($pattern, '00 0D-93 13 51 1A',   FALSE);
        $array[] = array($pattern, '00 0D 93:13 51 1A',   FALSE);
        $array[] = array($pattern, '00 0D.93 13.51 1A',   FALSE);
        $array[] = array($pattern, '0x901b',              FALSE);

        $array[] = array($pattern, '08-00-27-00-5049',    FALSE);
        $array[] = array($pattern, '08:00:27:00:5049',    FALSE);
        $array[] = array($pattern, '08-00-27-00-50--49',  FALSE);
        $array[] = array($pattern, '08:00:27:00:50::49',  FALSE);
        $array[] = array($pattern, '08-00-27-00-50-49-',  FALSE);
        $array[] = array($pattern, '08:00:27:00:50:49:',  FALSE);
        $array[] = array($pattern, '-08-00-27-00-50-49',  FALSE);
        $array[] = array($pattern, ':08:00:27:00:50:49',  FALSE);
        $array[] = array($pattern, ':080027005049',       FALSE);
        // MAC in strings
        $array[] = array($pattern, '"0026.22eb.3bef"',         TRUE, '0026.22eb.3bef');
        $array[] = array($pattern, '(0026.22eb.3bef)',         TRUE, '0026.22eb.3bef');
        $array[] = array($pattern, '(0026.22eb.3bef, qu-qu)',  TRUE, '0026.22eb.3bef');
        $array[] = array($pattern, 'wevent.ubnt_custom_event(): EVENT_STA_IP ath3: 9c:4f:da:73:5c:cc / 10.10.35.16',     TRUE, '9c:4f:da:73:5c:cc');
        $array[] = array($pattern, 'ath0: STA 44:d9:e7:f7:18:f2 DRIVER: Sead AUTH addr=9c:4f:da:73:5c:cc status_code=0', TRUE, '44:d9:e7:f7:18:f2');
        $array[] = array($pattern, 'ath0: STA 44:d9:e7:f7:18:f2. DRIVER: Sead AUTH addr=9c:4f:da:73:5c:cc status_code=0', TRUE, '44:d9:e7:f7:18:f2');
        $array[] = array($pattern, 'wevent.ubnt_custom_event(): EVENT_STA_IP ath3: 9c:4f:da:73:5c:cccc',  FALSE);
        $array[] = array($pattern, 'Host 0016.3e2e.2b98 in vlan 400 is flapping between port Gi1/0/25 and port Te1/0/1', TRUE, '0016.3e2e.2b98');

        $pattern = OBS_PATTERN_FQDN_FULL;
        // Domain name valid
        $array[] = array($pattern, 'observium.org',               TRUE, 'observium.org');
        $array[] = array($pattern, 'my.host-name.test',           TRUE, 'my.host-name.test');
        $array[] = array($pattern, 'qq.ff.ee.my.host-name.test',  TRUE, 'qq.ff.ee.my.host-name.test');
        $array[] = array($pattern, 'localhost',                   TRUE, 'localhost');
        //                          1234567890123456789012345678901234567890123456789012345678901234
        $array[] = array($pattern, 'my-yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy-63char.name', TRUE, 'my-yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy-63char.name');
        $array[] = array($pattern, 'external.asd1230-123.asd_internal.asd.gm-_ail.com',                    TRUE, 'external.asd1230-123.asd_internal.asd.gm-_ail.com');
        // Domain name IDN
        $array[] = array($pattern, 'xn--b1agh1afp.xn--p1ai',      TRUE, 'xn--b1agh1afp.xn--p1ai'); // привет.рф
        $array[] = array($pattern, 'привет.рф',                   TRUE, 'привет.рф');              // привет.рф
        // Domain name invalid
        $array[] = array($pattern, '::127.32.67.15',              FALSE);
        $array[] = array($pattern, '1.2.3',                       FALSE);
        $array[] = array($pattern, '1.2.3.4',                     FALSE);
        $array[] = array($pattern, '.1.2.3',                      FALSE);
        $array[] = array($pattern, '1.2.3.4.5.6.7.8',             FALSE);
        $array[] = array($pattern, '999.999.999.999',             FALSE);
        $array[] = array($pattern, '299.299.299.299',             FALSE);
        $array[] = array($pattern, '001.002.003.004',             FALSE);
        $array[] = array($pattern, 'test',                        FALSE);
        $array[] = array($pattern, 'example..com',                FALSE);
        $array[] = array($pattern, 'http://example.com',          FALSE);
        $array[] = array($pattern, 'subdomain.-example.com',      FALSE);
        $array[] = array($pattern, 'example.com/parameter',       FALSE);
        $array[] = array($pattern, 'example.com?anything',        FALSE);
        $array[] = array($pattern, 'GigabitEthernet0/1.ServiceInstance.206', FALSE);
        //                          1234567890123456789012345678901234567890123456789012345678901234
        $array[] = array($pattern, 'my-yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy-64char.name', FALSE);
        // Domain name in strings
        $array[] = array($pattern, '"my.host-name.test"',         TRUE, 'my.host-name.test');
        $array[] = array($pattern, '(my.host-name.test)',         TRUE, 'my.host-name.test');
        $array[] = array($pattern, '(my.host-name.test, help)',   TRUE, 'my.host-name.test');
        $array[] = array($pattern, 'Invalid user test from my.host-name.test',     TRUE, 'my.host-name.test');
        $array[] = array($pattern, 'Invalid user test from my.host-name.test hs',  TRUE, 'my.host-name.test');
        $array[] = array($pattern, 'Invalid user test from my.host-name.test.',    TRUE, 'my.host-name.test');

        $pattern = OBS_PATTERN_EMAIL_FULL;
        // Email valid
        $array[] = array($pattern, 'president@whitehouse.gov',            TRUE, 'president@whitehouse.gov');
        $array[] = array($pattern, 'pharaoh@egyptian.museum',             TRUE, 'pharaoh@egyptian.museum');
        $array[] = array($pattern, 'john.doe+test@ee.my.host-name.test',  TRUE, 'john.doe+test@ee.my.host-name.test');
        $array[] = array($pattern, 'Mike.O\'Dell@ireland.com',            TRUE, 'Mike.O\'Dell@ireland.com');
        $array[] = array($pattern, '"Mike\\\\ O\'Dell"@ireland.com',      TRUE, '"Mike\\\\ O\'Dell"@ireland.com');
        //                          1234567890123456789012345678901234567890123456789012345678901234
        $array[] = array($pattern, 'user-----------------------------------------------------63char@my-yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy-63char.name', TRUE,
                         'user-----------------------------------------------------63char@my-yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy-63char.name');
        //$array[] = array($pattern, 'external.asd1230-123.asd_internal.asd.gm-_ail.com',                    TRUE, 'external.asd1230-123.asd_internal.asd.gm-_ail.com');
        // Email IDN
        $array[] = array($pattern, 'mike@xn--b1agh1afp.xn--p1ai',         TRUE, 'mike@xn--b1agh1afp.xn--p1ai'); // mike@привет.рф
        $array[] = array($pattern, 'майк@привет.рф',                      TRUE, 'майк@привет.рф');              // майк@привет.рф
        // Email invalid
        $array[] = array($pattern, '1024x768@60Hz',                       FALSE);
        $array[] = array($pattern, 'not.a.valid.email',                   FALSE);
        $array[] = array($pattern, 'john@example...com',                  FALSE);
        $array[] = array($pattern, 'joe@ha!ha!.com',                      FALSE);
        //                          1234567890123456789012345678901234567890123456789012345678901234
        $array[] = array($pattern, 'joe@a_domain_name_with_more_than_sixty-four_characters_is_invalid_6465.com',  FALSE);
        $array[] = array($pattern, 'a_local_part_with_more_than_sixty-four_characters_is_invalid_6465@mail.com',  FALSE);
        //$array[] = array($pattern, 'the_total_length_of_an_email_address_is_limited@two-hundred-fifty-four-characters.because-the-SMTP-protocol-for-sending-email.does-not-support-more-than-that.really-hard-to-come-up-with-a-bogus-address-as-long-as-this.still-not-long-enough.too-long-now.com', FALSE);
        // Email in strings
        $array[] = array($pattern, '"test@domain.name"',                  TRUE, 'test@domain.name');
        $array[] = array($pattern, '(test@domain.name)',                  TRUE, 'test@domain.name');
        $array[] = array($pattern, '(test@domain.name, help)',            TRUE, 'test@domain.name');
        $array[] = array($pattern, 'The email address president@whitehouse.gov is valid.',  TRUE, 'president@whitehouse.gov');
        $array[] = array($pattern, 'fabio@disapproved.solutions has a long TLD',            TRUE, 'fabio@disapproved.solutions');

        $pattern = OBS_PATTERN_EMAIL_LONG_FULL;
        // Email valid
        $array[] = array($pattern, '<test@domain.name>',                  TRUE, '<test@domain.name>');
        $array[] = array($pattern, 'Pharaoh <pharaoh@egyptian.museum>',   TRUE, 'Pharaoh <pharaoh@egyptian.museum>');
        $array[] = array($pattern, 'in Egypt "Pharaoh" <pharaoh@egyptian.museum>',          TRUE, '"Pharaoh" <pharaoh@egyptian.museum>');
        $array[] = array($pattern, 'Pharaoh of Egypt <pharaoh@egyptian.museum>',            TRUE, 'Pharaoh of Egypt <pharaoh@egyptian.museum>');
        $array[] = array($pattern, '"Mike O\'Dell" <Mike.O\'Dell@ireland.com>',             TRUE, '"Mike O\'Dell" <Mike.O\'Dell@ireland.com>');
        // Email invalid
        $array[] = array($pattern, '<domain.name>',                       FALSE);
        $array[] = array($pattern, 'Pharaoh <pharaoh@>',                  FALSE);
        $array[] = array($pattern, 'Test Title test@example.com',         FALSE);

        /*
        $pattern = OBS_PATTERN_URL_FULL;
        // URL IDN
        $array[] = array($pattern, 'https://www.get.no/v3/bredb%C3%A5nd/tr%C3%A5dl%C3%B8st-modem', TRUE,
                                   'https://www.get.no/v3/bredb%C3%A5nd/tr%C3%A5dl%C3%B8st-modem');
        // URL in string
        $array[] = array($pattern, '<a href="https://www.get.no/v3/bredb%C3%A5nd/tr%C3%A5dl%C3%B8st-modem" rel="nofollow">https://www.get.no/v3/bredbånd/trådløst-modem</a>', TRUE,
                                   'https://www.get.no/v3/bredb%C3%A5nd/tr%C3%A5dl%C3%B8st-modem');
        */

        $pattern = OBS_PATTERN_LATLON;
        $array[] = [ $pattern, 'Some location [33.234, -56.22]', TRUE,
                     [ 0 => '[33.234, -56.22]', 'lat' => '33.234', 1 => '33.234', 'lon' => '-56.22', 2 => '-56.22' ] ];
        $array[] = [ $pattern, 'Some location (33.234 -56.22)', TRUE,
                     [ 0 => '(33.234 -56.22)',  'lat' => '33.234', 1 => '33.234', 'lon' => '-56.22', 2 => '-56.22' ] ];
        $array[] = [ $pattern, ' Some location [33.234;-56.22]', TRUE,
                     [ 0 => '[33.234;-56.22]',  'lat' => '33.234', 1 => '33.234', 'lon' => '-56.22', 2 => '-56.22' ] ];
        $array[] = [ $pattern, '33.234,-56.22', TRUE,
                     [ 0 => '33.234,-56.22',    'lat' => '33.234', 1 => '33.234', 'lon' => '-56.22', 2 => '-56.22' ] ];
        $array[] = [ $pattern, "'33.234','-56.22'", TRUE,
                     [ 0 => "'33.234','-56.22'", 'lat' => '33.234', 1 => '33.234', 'lon' => '-56.22', 2 => '-56.22' ] ];
        $array[] = [ $pattern, 'Some location|47.616380|-122.341673', FALSE ];

        $pattern = OBS_PATTERN_LATLON_ALT;
        $array[] = [ $pattern, 'Some location|47.616380|-122.341673', TRUE,
                     [ 0 => '|47.616380|-122.341673', 'lat' => '47.616380', 1 => '47.616380', 'lon' => '-122.341673', 2 => '-122.341673' ] ];
        $array[] = [ $pattern, "Some location|'47.616380'|'-122.341673'", TRUE,
                     [ 0 => "|'47.616380'|'-122.341673'", 'lat' => '47.616380', 1 => '47.616380', 'lon' => '-122.341673', 2 => '-122.341673' ] ];
        $array[] = [ $pattern, 'Some location [33.234, -56.22]', FALSE ];

        $pattern = OBS_PATTERN_NOPRINT;
        // Not printable chars
        $array[] = array($pattern, "ABC \n",                  TRUE);
        $array[] = array($pattern, "ABC \r",                  TRUE);
        $array[] = array($pattern, "ABC \t",                  TRUE);
        // All printable
        $array[] = array($pattern, "ABC ËЙЦ 10 œ∑√∫Ω≈∆µ \"',.:`~!@#$%^&*()_+-=<>?/[]{}|\\", FALSE);

        return $array;
    }

    /**
     * @dataProvider providerDefinitionXss
     * @group security
     */
    public function testDefinitionXss($string, $result = TRUE) {
        $test = preg_match(OBS_PATTERN_XSS, $string);
        $this->assertSame($result, (bool)$test);
    }

    public function providerDefinitionXss() {
        $array = [];

        // Examples:
        // https://notes.offsec-journey.com/owasp-top-10-exploitation/cross-site-scripting-xss
        // https://github.com/swisskyrepo/PayloadsAllTheThings/tree/master/XSS%20Injection

        // script
        $array[] = [ 'javascript:alert(document.domain);' ];
        $array[] = [ '<a href=\'javascript:alert(1)\'' ];
        $array[] = [ 'sCripT>alert(1)</sCripT>' ];
        $array[] = [ '<scr<script>ipt>alert(1)</scri</script>pt>' ];
        $array[] = [ '<script<script >alert(1)</script</script> >' ];
        $array[] = [ '<script>prompt("XSS")</script>' ];
        $array[] = [ '"<script/src=data:,alert(1)>' ];
        $array[] = [ '<h1> test </h1> <script> alert(1) </script>' ];
        $array[] = [ '<sCrIpT> < / s c r i p t >' ];
        $array[] = [ 'javascript:alert("Hello world");/' ];

        // on*
        $array[] = [ '<a onmouseover="alert(1)">TEST</a>' ];
        $array[] = [ '<a onmouseout=alert(1)>TEST</a>' ];
        $array[] = [ '<a onmousemove=alert(1)>TEST</a>' ];
        $array[] = [ '<a onmouseclick=alert(1)>TEST</a>' ];
        $array[] = [ '<img src=\'zzzz\' onerror=alert(1)></img>' ];
        $array[] = [ '<svg onload=alert(document.domain)>' ];
        $array[] = [ '<div> onmouseover=alert(1)></div>' ];
        $array[] = [ '<style/onload=alert(document.domain)>' ];

        // iframe
        $array[] = [ '><iframe src="data:text/html;base64,base64_data"></iframe>' ];

        // eval
        $array[] = [ 'eval(String.fromCharCode(97,108,101,114,116,40,49,41))' ];
        $array[] = [ '<script>eval("al"%2b"ert(1)")</script>' ];
        $array[] = [ '><details ontoggle=eval(atob(\'YWxlcnQoMSk=\')) open>' ];
        $array[] = [ 'eval(atob(\'PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==\'));' ];

        return $array;
    }
}

// EOF
