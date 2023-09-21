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

### FIXME - this shit is ancient.

// BD6808 - Version 7.8.3 (Build 5) by Release_Master 03/15/10 14:27:35
// Summit48 - Version 4.1.19 (Build 2) by Release_Master  Wed 08/09/2000  6:09p
// Summit24e3 - Version 6.2e.1 (Build 20) by Release_Master_ABU Tue 05/27/2003 16:46:08
// Summit48 - 1720 Garry - Version 4.1.19 (Build 2) by Release_Master  Wed 08/09/2000  6:09p
// Summit48(Yonetan) - Version 4.1.19 (Build 2) by Release_Master  Wed 08/09/2000  6:09p
// Alpine3808 - Version 7.2.0 (Build 33) by Release_Master 07/09/04 14:05:12

[, $datas] = explode(' - ', $poll_device['sysDescr']);
$datas = str_replace('(', '', $datas);
$datas = str_replace(')', '', $datas);
[$a, $b, $c, $d, $e, $f, $g, $h] = explode(' ', $datas);

if ($a == 'Version') {
    $version  = $b;
    $features = $c . ' ' . $d . ' ' . $g;
}

$hardware = rewrite_extreme_hardware($poll_device['sysObjectID']);
if ($hardware == $poll_device['sysObjectID']) {
    unset($hardware);
}

$version  = str_replace('"', '', $version);
$features = str_replace('"', '', $features);
$hardware = str_replace('"', '', $hardware);

// EOF
