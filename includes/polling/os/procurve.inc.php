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

if (preg_match('/^PROCURVE (.*) - (.*)/', $poll_device['sysDescr'], $matches)) {
    // PROCURVE J9029A - PA.03.04
    // PROCURVE J9028B - PB.03.00
    $hardware = $matches[1];
    $version  = $matches[2];
} elseif (preg_match('/^(?:HP )?ProCurve (?<hardware>\w+.+?), (?:revision )?(?<version>[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // ProCurve J4900C Switch 2626, revision H.10.67, ROM H.08.05 (/sw/code/build/fish(mkfs))
    // HP ProCurve 1810G - 24 GE, P.1.14, eCos-2.0
    $hardware = $matches['hardware'];
    $version  = $matches['version'];
} elseif (preg_match('/^(?:Aruba|HP|Hewlett-Packard Company) (?<hw>\w+) (?:(?:.*?(?<sw1>(?:Routing )?Switch) (?<hw1>[\w\-\+]+))|(?:(?<hw2>[\w\-\+]+) (?<sw2>(?:Routing )?Switch))), (?:revision|Software Version) (?<version>[\w\.]+)/', $poll_device['sysDescr'], $matches)) {
    // Aruba JL075A 3810M-16SFP+-2-slot Switch, revision KB.16.02.0013, ROM KB.16.01.0006 (/ws/swbuildm/rel_spokane_qaoff/code/build/bom(swbuildm_rel_spokane_qaoff_rel_spokane))
    // HP J4121A ProCurve Switch 4000M, revision C.09.22, ROM C.06.01 (/sw/code/build/vgro(c09))
    // HP J9091A Switch E8212zl, revision K.15.06.0008, ROM K.15.19 (/sw/code/build/btm(K_15_06)) (Formerly ProCurve)
    // HP J9138A Switch 2520-24-PoE, revision S.15.09.0022, ROM S.14.03 (/ws/swbuildm/S_rel_hartford_qaoff/code/build/elmo(S_rel_hartford_qaoff)) (Formerly ProCurve)
    // HP J9729A 2920-48G-POE+ Switch, revision WB.15.16.0005, ROM WB.15.05 (/ws/swbuildm/rel_orlando_qaoff/code/build/anm(swbuildm_rel_orlando_qaoff_rel_orlando)) (Formerly ProCurve)
    // Hewlett-Packard Company J4139A HP ProCurve Routing Switch 9304M, Software Version 08.0.01rT53 Compiled on Jul 30 2008 at 02:28:35 labeled as H2R08001r
    $hardware = $matches['hw'];
    if ($matches['hw1']) {
        $hardware .= ' ' . $matches['sw1'] . ' ' . $matches['hw1'];
    } else {
        $hardware .= ' ' . $matches['sw2'] . ' ' . $matches['hw2'];
    }
    $version = $matches['version'];
}

// EOF
