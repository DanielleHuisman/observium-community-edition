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

/*
thread0.num.queries=2231
thread0.num.cachehits=2098
thread0.num.cachemiss=133
thread0.num.prefetch=0
thread0.num.recursivereplies=133
thread0.requestlist.avg=1.78195
thread0.requestlist.max=43
thread0.requestlist.overwritten=0
thread0.requestlist.exceeded=0
thread0.requestlist.current.all=0
thread0.requestlist.current.user=0
thread0.recursion.time.avg=0.130315
thread0.recursion.time.median=0.0065024
total.num.queries=2231
total.num.cachehits=2098
total.num.cachemiss=133
total.num.prefetch=0
total.num.recursivereplies=133
total.requestlist.avg=1.78195
total.requestlist.max=43
total.requestlist.overwritten=0
total.requestlist.exceeded=0
total.requestlist.current.all=0
total.requestlist.current.user=0
total.recursion.time.avg=0.130315
total.recursion.time.median=0.0065024
time.now=1345738158.409360
time.up=129.622280
time.elapsed=6.775663
mem.total.sbrk=7561216
mem.cache.rrset=293070
mem.cache.message=158049
mem.mod.iterator=16532
mem.mod.validator=116833
histogram.000000.000000.to.000000.000001=3
histogram.000000.000001.to.000000.000002=0
histogram.000000.000002.to.000000.000004=0
histogram.000000.000004.to.000000.000008=0
histogram.000000.000008.to.000000.000016=0
histogram.000000.000016.to.000000.000032=0
histogram.000000.000032.to.000000.000064=0
histogram.000000.000064.to.000000.000128=0
histogram.000000.000128.to.000000.000256=0
histogram.000000.000256.to.000000.000512=0
histogram.000000.000512.to.000000.001024=4
histogram.000000.001024.to.000000.002048=39
histogram.000000.002048.to.000000.004096=4
histogram.000000.004096.to.000000.008192=46
histogram.000000.008192.to.000000.016384=17
histogram.000000.016384.to.000000.032768=6
histogram.000000.032768.to.000000.065536=0
histogram.000000.065536.to.000000.131072=2
histogram.000000.131072.to.000000.262144=7
histogram.000000.262144.to.000000.524288=10
histogram.000000.524288.to.000001.000000=10
histogram.000001.000000.to.000002.000000=4
histogram.000002.000000.to.000004.000000=0
histogram.000004.000000.to.000008.000000=0
histogram.000008.000000.to.000016.000000=0
histogram.000016.000000.to.000032.000000=0
histogram.000032.000000.to.000064.000000=0
histogram.000064.000000.to.000128.000000=0
histogram.000128.000000.to.000256.000000=0
histogram.000256.000000.to.000512.000000=0
histogram.000512.000000.to.001024.000000=0
histogram.001024.000000.to.002048.000000=0
histogram.002048.000000.to.004096.000000=0
histogram.004096.000000.to.008192.000000=0
histogram.008192.000000.to.016384.000000=0
histogram.016384.000000.to.032768.000000=0
histogram.032768.000000.to.065536.000000=0
histogram.065536.000000.to.131072.000000=0
histogram.131072.000000.to.262144.000000=0
histogram.262144.000000.to.524288.000000=0
num.query.type.A=2515
num.query.type.PTR=105
num.query.type.MX=3
num.query.type.AAAA=165
num.query.type.SRV=2
num.query.class.IN=2790
num.query.opcode.QUERY=2790
num.query.tcp=0
num.query.ipv6=0
num.query.flags.QR=0
num.query.flags.AA=0
num.query.flags.TC=0
num.query.flags.RD=2790
num.query.flags.RA=0
num.query.flags.Z=0
num.query.flags.AD=0
num.query.flags.CD=0
num.query.edns.present=0
num.query.edns.DO=0
num.answer.rcode.NOERROR=2778
num.answer.rcode.NXDOMAIN=12
num.answer.rcode.nodata=128
num.answer.secure=2
num.answer.bogus=0
num.rrset.bogus=0
unwanted.queries=0
unwanted.replies=0
*/

if (!empty($agent_data['app']['unbound'])) {
    $app_id = discover_app($device, 'unbound');

    foreach (explode("\n", $agent_data['app']['unbound']) as $line) {
        [$key, $value] = explode("=", $line, 2);
        $unbound[$key] = $value;
    }

    while (1) {
        if (!isset($threadnum)) {
            $thread    = 'total';
            $threadnum = -1; # Incremented below, we want to check thread0 next, so we put this to -1. Yes, ugly... ;-(
        } else {
            $thread = 'thread' . $threadnum;
        }

        if (isset($unbound["$thread.num.queries"])) {
            rrdtool_update_ng($device, 'unbound-thread', [
              'numQueries'          => $unbound["$thread.num.queries"],
              'cacheHits'           => $unbound["$thread.num.cachehits"],
              'cacheMiss'           => $unbound["$thread.num.cachemiss"],
              'prefetch'            => $unbound["$thread.num.prefetch"],
              'recursiveReplies'    => $unbound["$thread.num.recursivereplies"],
              'reqListAvg'          => $unbound["$thread.requestlist.avg"],
              'reqListMax'          => $unbound["$thread.requestlist.max"],
              'reqListOverwritten'  => $unbound["$thread.requestlist.overwritten"],
              'reqListExceeded'     => $unbound["$thread.requestlist.exceeded"],
              'reqListCurrentAll'   => $unbound["$thread.requestlist.current.all"],
              'reqListCurrentUser'  => $unbound["$thread.requestlist.current.user"],
              'recursionTimeAvg'    => $unbound["$thread.recursion.time.avg"],
              'recursionTimeMedian' => $unbound["$thread.recursion.time.median"],
            ],                "$app_id-$thread");

            $threadnum++;
        } else {
            break;
        }
    }

    unset($threadnum);

    rrdtool_update_ng($device, 'unbound-memory', [
      'memTotal'        => $unbound['mem.total.sbrk'],
      'memCacheRRset'   => $unbound['mem.cache.rrset'],
      'memCacheMessage' => $unbound['mem.cache.message'],
      'memModIterator'  => $unbound['mem.mod.iterator'],
      'memModValidator' => $unbound['mem.mod.validator'],
    ],                $app_id);

    $data = [
        # We return 0 in the following entries because unbound does not show these values if they are 0.
        # They're not unknown (U) in this case, so it's ok to return 0.
        'qTypeA'           => (is_numeric($unbound['num.query.type.A']) ? $unbound['num.query.type.A'] : 0),
        'qTypeA6'          => (is_numeric($unbound['num.query.type.A6']) ? $unbound['num.query.type.A6'] : 0),
        'qTypeAAAA'        => (is_numeric($unbound['num.query.type.AAAA']) ? $unbound['num.query.type.AAAA'] : 0),
        'qTypeAFSDB'       => (is_numeric($unbound['num.query.type.AFSDB']) ? $unbound['num.query.type.AFSDB'] : 0),
        'qTypeANY'         => (is_numeric($unbound['num.query.type.ANY']) ? $unbound['num.query.type.ANY'] : 0),
        'qTypeAPL'         => (is_numeric($unbound['num.query.type.APL']) ? $unbound['num.query.type.APL'] : 0),
        'qTypeATMA'        => (is_numeric($unbound['num.query.type.ATMA']) ? $unbound['num.query.type.ATMA'] : 0),
        'qTypeAXFR'        => (is_numeric($unbound['num.query.type.AXFR']) ? $unbound['num.query.type.AXFR'] : 0),
        'qTypeCERT'        => (is_numeric($unbound['num.query.type.CERT']) ? $unbound['num.query.type.CERT'] : 0),
        'qTypeCNAME'       => (is_numeric($unbound['num.query.type.CNAME']) ? $unbound['num.query.type.CNAME'] : 0),
        'qTypeDHCID'       => (is_numeric($unbound['num.query.type.DHCID']) ? $unbound['num.query.type.DHCID'] : 0),
        'qTypeDLV'         => (is_numeric($unbound['num.query.type.DLV']) ? $unbound['num.query.type.DLV'] : 0),
        'qTypeDNAME'       => (is_numeric($unbound['num.query.type.DNAME']) ? $unbound['num.query.type.DNAME'] : 0),
        'qTypeDNSKEY'      => (is_numeric($unbound['num.query.type.DNSKEY']) ? $unbound['num.query.type.DNSKEY'] : 0),
        'qTypeDS'          => (is_numeric($unbound['num.query.type.DS']) ? $unbound['num.query.type.DS'] : 0),
        'qTypeEID'         => (is_numeric($unbound['num.query.type.EID']) ? $unbound['num.query.type.EID'] : 0),
        'qTypeGID'         => (is_numeric($unbound['num.query.type.GID']) ? $unbound['num.query.type.GID'] : 0),
        'qTypeGPOS'        => (is_numeric($unbound['num.query.type.GPOS']) ? $unbound['num.query.type.GPOS'] : 0),
        'qTypeHINFO'       => (is_numeric($unbound['num.query.type.HINFO']) ? $unbound['num.query.type.HINFO'] : 0),
        'qTypeIPSECKEY'    => (is_numeric($unbound['num.query.type.IPSECKEY']) ? $unbound['num.query.type.IPSECKEY'] : 0),
        'qTypeISDN'        => (is_numeric($unbound['num.query.type.ISDN']) ? $unbound['num.query.type.ISDN'] : 0),
        'qTypeIXFR'        => (is_numeric($unbound['num.query.type.IXFR']) ? $unbound['num.query.type.IXFR'] : 0),
        'qTypeKEY'         => (is_numeric($unbound['num.query.type.KEY']) ? $unbound['num.query.type.KEY'] : 0),
        'qTypeKX'          => (is_numeric($unbound['num.query.type.KX']) ? $unbound['num.query.type.KX'] : 0),
        'qTypeLOC'         => (is_numeric($unbound['num.query.type.LOC']) ? $unbound['num.query.type.LOC'] : 0),
        'qTypeMAILA'       => (is_numeric($unbound['num.query.type.MAILA']) ? $unbound['num.query.type.MAILA'] : 0),
        'qTypeMAILB'       => (is_numeric($unbound['num.query.type.MAILB']) ? $unbound['num.query.type.MAILB'] : 0),
        'qTypeMB'          => (is_numeric($unbound['num.query.type.MB']) ? $unbound['num.query.type.MB'] : 0),
        'qTypeMD'          => (is_numeric($unbound['num.query.type.MD']) ? $unbound['num.query.type.MD'] : 0),
        'qTypeMF'          => (is_numeric($unbound['num.query.type.MF']) ? $unbound['num.query.type.MF'] : 0),
        'qTypeMG'          => (is_numeric($unbound['num.query.type.MG']) ? $unbound['num.query.type.MG'] : 0),
        'qTypeMINFO'       => (is_numeric($unbound['num.query.type.MINFO']) ? $unbound['num.query.type.MINFO'] : 0),
        'qTypeMR'          => (is_numeric($unbound['num.query.type.MR']) ? $unbound['num.query.type.MR'] : 0),
        'qTypeMX'          => (is_numeric($unbound['num.query.type.MX']) ? $unbound['num.query.type.MX'] : 0),
        'qTypeNAPTR'       => (is_numeric($unbound['num.query.type.NAPTR']) ? $unbound['num.query.type.NAPTR'] : 0),
        'qTypeNIMLOC'      => (is_numeric($unbound['num.query.type.NIMLOC']) ? $unbound['num.query.type.NIMLOC'] : 0),
        'qTypeNS'          => (is_numeric($unbound['num.query.type.NS']) ? $unbound['num.query.type.NS'] : 0),
        'qTypeNSAP'        => (is_numeric($unbound['num.query.type.NSAP']) ? $unbound['num.query.type.NSAP'] : 0),
        'qTypeNSAP_PTR'    => (is_numeric($unbound['num.query.type.NSAP_PTR']) ? $unbound['num.query.type.NSAP_PTR'] : 0),
        'qTypeNSEC'        => (is_numeric($unbound['num.query.type.NSEC']) ? $unbound['num.query.type.NSEC'] : 0),
        'qTypeNSEC3'       => (is_numeric($unbound['num.query.type.NSEC3']) ? $unbound['num.query.type.NSEC3'] : 0),
        'qTypeNSEC3PARAMS' => (is_numeric($unbound['num.query.type.NSEC3PARAMS']) ? $unbound['num.query.type.NSEC3PARAMS'] : 0),
        'qTypeNULL'        => (is_numeric($unbound['num.query.type.NULL']) ? $unbound['num.query.type.NULL'] : 0),
        'qTypeNXT'         => (is_numeric($unbound['num.query.type.TXT']) ? $unbound['num.query.type.TXT'] : 0),
        'qTypeOPT'         => (is_numeric($unbound['num.query.type.OPT']) ? $unbound['num.query.type.OPT'] : 0),
        'qTypePTR'         => (is_numeric($unbound['num.query.type.PTR']) ? $unbound['num.query.type.PTR'] : 0),
        'qTypePX'          => (is_numeric($unbound['num.query.type.PX']) ? $unbound['num.query.type.PX'] : 0),
        'qTypeRP'          => (is_numeric($unbound['num.query.type.RP']) ? $unbound['num.query.type.RP'] : 0),
        'qTypeRRSIG'       => (is_numeric($unbound['num.query.type.RRSIG']) ? $unbound['num.query.type.RRSIG'] : 0),
        'qTypeRT'          => (is_numeric($unbound['num.query.type.RT']) ? $unbound['num.query.type.RT'] : 0),
        'qTypeSIG'         => (is_numeric($unbound['num.query.type.SIG']) ? $unbound['num.query.type.SIG'] : 0),
        'qTypeSINK'        => (is_numeric($unbound['num.query.type.SINK']) ? $unbound['num.query.type.SINK'] : 0),
        'qTypeSOA'         => (is_numeric($unbound['num.query.type.SOA']) ? $unbound['num.query.type.SOA'] : 0),
        'qTypeSRV'         => (is_numeric($unbound['num.query.type.SRV']) ? $unbound['num.query.type.SRV'] : 0),
        'qTypeSSHFP'       => (is_numeric($unbound['num.query.type.SSHFP']) ? $unbound['num.query.type.SSHFP'] : 0),
        'qTypeTSIG'        => (is_numeric($unbound['num.query.type.TSIG']) ? $unbound['num.query.type.TSIG'] : 0),
        'qTypeTXT'         => (is_numeric($unbound['num.query.type.TXT']) ? $unbound['num.query.type.TXT'] : 0),
        'qTypeUID'         => (is_numeric($unbound['num.query.type.UID']) ? $unbound['num.query.type.UID'] : 0),
        'qTypeUINFO'       => (is_numeric($unbound['num.query.type.UINFO']) ? $unbound['num.query.type.INFO'] : 0),
        'qTypeUNSPEC'      => (is_numeric($unbound['num.query.type.UNSPEC']) ? $unbound['num.query.type.UNSPEC'] : 0),
        'qTypeWKS'         => (is_numeric($unbound['num.query.type.WKS']) ? $unbound['num.query.type.WKS'] : 0),
        'qTypeX25'         => (is_numeric($unbound['num.query.type.X25']) ? $unbound['num.query.type.X25'] : 0),
        'classANY'         => (is_numeric($unbound['num.query.class.ANY']) ? $unbound['num.query.class.ANY'] : 0),
        'classCH'          => (is_numeric($unbound['num.query.class.CH']) ? $unbound['num.query.class.CH'] : 0),
        'classHS'          => (is_numeric($unbound['num.query.class.HS']) ? $unbound['num.query.class.HS'] : 0),
        'classIN'          => (is_numeric($unbound['num.query.class.IN']) ? $unbound['num.query.class.IN'] : 0),
        'classNONE'        => (is_numeric($unbound['num.query.class.NONE']) ? $unbound['num.query.class.NONE'] : 0),
        'rcodeFORMERR'     => (is_numeric($unbound['num.query.rcode.FORMERR']) ? $unbound['num.query.rcode.FORMERR'] : 0),
        'rcodeNOERROR'     => (is_numeric($unbound['num.query.rcode.NOERROR']) ? $unbound['num.query.rcode.NOERROR'] : 0),
        'rcodeNOTAUTH'     => (is_numeric($unbound['num.query.rcode.NOTAUTH']) ? $unbound['num.query.rcode.NOTAUTH'] : 0),
        'rcodeNOTIMPL'     => (is_numeric($unbound['num.query.rcode.NOTIMPL']) ? $unbound['num.query.rcode.NOTIMPL'] : 0),
        'rcodeNOTZONE'     => (is_numeric($unbound['num.query.rcode.NOTZONE']) ? $unbound['num.query.rcode.NOTZONE'] : 0),
        'rcodeNXDOMAIN'    => (is_numeric($unbound['num.query.rcode.NXDOMAIN']) ? $unbound['num.query.rcode.NXDOMAIN'] : 0),
        'rcodeNXRRSET'     => (is_numeric($unbound['num.query.rcode.NXRRSET']) ? $unbound['num.query.rcode.NXRRSET'] : 0),
        'rcodeREFUSED'     => (is_numeric($unbound['num.query.rcode.REFUSED']) ? $unbound['num.query.rcode.REFUSED'] : 0),
        'rcodeSERVFAIL'    => (is_numeric($unbound['num.query.rcode.SERVFAIL']) ? $unbound['num.query.rcode.SERVFAIL'] : 0),
        'rcodeYXDOMAIN'    => (is_numeric($unbound['num.query.rcode.YXDOMAIN']) ? $unbound['num.query.rcode.YXDOMAIN'] : 0),
        'rcodeYXRRSET'     => (is_numeric($unbound['num.query.rcode.YXRRSET']) ? $unbound['num.query.rcode.YXRRSET'] : 0),
        'rcodenodata'      => (is_numeric($unbound['num.query.rcode.nodata']) ? $unbound['num.query.rcode.nodata'] : 0),
        'flagQR'           => (is_numeric($unbound['num.query.flag.QR']) ? $unbound['num.query.flag.QR'] : 0),
        'flagAA'           => (is_numeric($unbound['num.query.flag.AA']) ? $unbound['num.query.flag.AA'] : 0),
        'flagTC'           => (is_numeric($unbound['num.query.flag.TC']) ? $unbound['num.query.flag.TC'] : 0),
        'flagRD'           => (is_numeric($unbound['num.query.flag.RD']) ? $unbound['num.query.flag.RD'] : 0),
        'flagRA'           => (is_numeric($unbound['num.query.flag.RA']) ? $unbound['num.query.flag.RA'] : 0),
        'flagZ'            => (is_numeric($unbound['num.query.flag.Z']) ? $unbound['num.query.flag.Z'] : 0),
        'flagAD'           => (is_numeric($unbound['num.query.flag.AD']) ? $unbound['num.query.flag.AD'] : 0),
        'flagCD'           => (is_numeric($unbound['num.query.flag.CD']) ? $unbound['num.query.flag.CD'] : 0),
        'opcodeQUERY'      => (is_numeric($unbound['num.query.opcode.QUERY']) ? $unbound['num.query.opcode.QUERY'] : 0),
        'opcodeIQUERY'     => (is_numeric($unbound['num.query.opcode.IQUERY']) ? $unbound['num.query.opcode.IQUERY'] : 0),
        'opcodeSTATUS'     => (is_numeric($unbound['num.query.opcode.STATUS']) ? $unbound['num.query.opcode.STATUS'] : 0),
        'opcodeNOTIFY'     => (is_numeric($unbound['num.query.opcode.NOTIFY']) ? $unbound['num.query.opcode.NOTIFY'] : 0),
        'opcodeUPDATE'     => (is_numeric($unbound['num.query.opcode.UPDATE']) ? $unbound['num.query.opcode.UPDATE'] : 0),
        # These are generic and should be set - if they're not, we'll set U in the update function.
        'numQueryTCP'      => $unbound['num.query.tcp'],
        'numQueryIPv6'     => $unbound['num.query.ipv6'],
        'numQueryUnwanted' => $unbound['unwanted.queries'],
        'numReplyUnwanted' => $unbound['unwanted.replies'],
        'numAnswerSecure'  => $unbound['num.answer.secure'],
        'numAnswerBogus'   => $unbound['num.answer.bogus'],
        'numRRSetBogus'    => $unbound['num.rrset.bogus'],
        'ednsPresent'      => $unbound['num.query.edns.present'],
        'ednsDO'           => $unbound['num.query.edns.DO'],
    ];

    rrdtool_update_ng($device, 'unbound-queries', $data, $app_id);

    update_application($app_id, $data);

    unset($unbound, $app_id);
}

// EOF
