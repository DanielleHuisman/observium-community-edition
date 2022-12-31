<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

$units = '';
$unit_text = $oid['oid_unit'];
$total_units = '';

if ($oid['oid_logy'] == 1) { $log_y = TRUE; }
if ($oid['oid_kibi'] == 1) { $kibi = 1; }

$colours='mixed';

//$scale_min = "0";
$nototal = 1;

include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");
