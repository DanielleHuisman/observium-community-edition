<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

if (!safe_empty($hardware)) {
    return;
}

// Printers hardware

if ($printer = snmp_get_oid($device, 'hrDeviceDescr.1', 'HOST-RESOURCES-MIB')) {
    $hardware = explode(';', $printer)[0];
} elseif (is_device_mib($device, 'HP-LASERJET-COMMON-MIB')) {
    // HP-LASERJET-COMMON-MIB::gdStatusId.0 = STRING: MFG:Hewlett-Packard;CMD:PJL,PML,PCLXL,URP,PCL,PDF,POSTSCRIPT;MDL:HP LaserJet 500 colorMFP M570dw;CLS:PRINTER;DES:Hewlett-Packard LaserJet 500 colorMFP M570dw;MEM:MEM=230MB;COMMENT:RES=600x8;LEDMDIS:USB#ff#04#01;CID:HPLJPDLV1;
    // MFG:Hewlett-Packard;CMD:PJL,MLC,BIDI-ECP,PCL,POSTSCRIPT,PCLXL;MDL:hp LaserJet 1320 series;CLS:PRINTER;DES:Hewlett-Packard LaserJet 1320 series;MEM:9MB;COMMENT:RES=1200x1;
    // MFG:HP;MDL:Officejet Pro K5400;CMD:MLC,PCL,PML,DW-PCL,DESKJET,DYN;1284.4DL:4d,4e,1;CLS:PRINTER;DES:C8185A;SN:MY82E680JG;S:038000ec840010210068eb800008fb8000041c8003844c8004445c8004d46c8003b;Z:0102,05000009000009029cc1016a81017a21025e41,0600,070000000000000
    // MFG:HP;CMD:PJL,DW-PCL,POSTSCRIPT,HP-GL/2,RTL,JPEG,TIFF,PDF,CALSRASTER,ASCII,URF;MDL:HP DesignJet T1600 PostScript Printer;CLS:PRINTER;LEDMDIS:USB#FF#CC#00;MCT:PR;MCL:DJA;MCV:3.0;DES:HP DesignJet T1600 PostScript Printer;SN:CN9541H01M;FW:CYCLOPSNEPTUNE_03_19_48.1;CID:HPDJPST;
    $jdinfo = snmp_get_oid($device, 'gdStatusId.0', 'HP-LASERJET-COMMON-MIB');
    if (preg_match('/(?:MDL|MODEL|DESCRIPTION):([^;]+);/', $jdinfo, $matches)) {
        $hardware = $matches[1];
    }
}

if ($hardware) {
    // Strip off useless brand fields
    $hardware = str_ireplace([ 'HP ', 'Hewlett-Packard ', ' Series', 'Samsung ', 'Epson ', 'Brother ', 'OKI ' ], '', $hardware);
}

unset($printer);

// EOF
