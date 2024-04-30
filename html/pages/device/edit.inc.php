<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

if ($_SESSION['userlevel'] < 7 && !is_entity_write_permitted($device['device_id'], 'device')) {
    print_error_permission();
    return;
}

// User level 7-9 only can see config
//$readonly = $_SESSION['userlevel'] < 10;

// Allow write for users with write permission to this entity
$readonly = !is_entity_write_permitted($device['device_id'], 'device');

$link_array = ['page'   => 'device',
               'device' => $device['device_id'],
               'tab'    => 'edit'];

$panes['device'] = 'Device Settings';
$panes['snmp']   = 'SNMP';
if ($config['geocoding']['enable']) {
    $panes['geo'] = 'Geolocation';
}
$panes['mibs']   = 'MIBs';
$panes['graphs'] = 'Graphs';
$panes['alerts'] = 'Alerts';
$panes['ports']  = 'Ports';
if ($cache['health_exist'][$device['device_id']]['sensors']) {
    $panes['sensors'] = 'Sensors';
}
if ($cache['health_exist'][$device['device_id']]['status']) {
    $panes['status'] = 'Statuses';
}
// if ($cache['health_exist'][$device['device_id']]['counter']) {
//     $panes['counter'] = 'Counters';
// }
if (safe_count($config['os'][$device['os']]['icons'])) {
    $panes['icon'] = 'Icon';
}

$panes['modules'] = 'Modules';

//if ($config['enable_services'])
//{
//$panes['services'] = 'Services';
//}

// $panes['probes'] = 'Probes';

if ($device_loadbalancer_count['netscaler_vsvr']) {
    $panes['netscaler_vsvrs'] = 'NS vServers';
}
if ($device_loadbalancer_count['netscaler_services']) {
    $panes['netscaler_svcs'] = 'NS Services';
}

if ($device['os'] === 'windows') {
    $panes['wmi'] = 'WMI';
}

if ($config['os'][$device['os']]['ipmi']) {
    $panes['ipmi'] = 'IPMI';
}
if (($config['enable_libvirt'] && $device['os'] === 'linux') ||                             // libvirt-vminfo discovery module
    $device['os_group'] === 'unix' || is_module_enabled($device, 'unix-agent', 'poller')) { // unix-agent
    $panes['ssh'] = 'SSH';
}
if ($device['os_group'] === 'unix' || $device['os'] === 'generic') {
    $panes['agent'] = 'Agent';
}
if ($device['os_group'] === 'unix' || $device['os'] === 'windows') {
    $panes['apps'] = 'Applications'; /// FIXME. Deprecated?
}

if ($_SESSION['userlevel'] >= 9) {
    // Detect (possible) duplicates
    $duplicates = [];
    get_device_duplicated($device, $duplicates);
    if (count($duplicates)) {
        $panes['duplicates'] = 'Duplicates';
    }
}

$navbar['brand'] = "Edit";
$navbar['class'] = "navbar-narrow";

foreach ($panes as $type => $text) {
    if (!isset($vars['section'])) {
        $vars['section'] = $type;
    }

    if ($vars['section'] == $type) {
        $navbar['options'][$type]['class'] = "active";
    }
    $navbar['options'][$type]['url']  = generate_url($link_array, ['section' => $type]);
    $navbar['options'][$type]['text'] = $text;
}
$navbar['options_right']['delete']['url']  = generate_url($link_array, ['section' => 'delete']);
$navbar['options_right']['delete']['text'] = 'Delete';
$navbar['options_right']['delete']['icon'] = ':wastebasket:';//$config['icon']['device-delete'];
if ($vars['section'] === 'delete') {
    $navbar['options_right']['delete']['class'] = 'active';
}
print_navbar($navbar);

$filename = $config['html_dir'] . '/pages/device/edit/' . $vars['section'] . '.inc.php';
if (is_file($filename)) {
    $vars    = get_vars('POST'); // Note, on edit pages use only method POST!
    $attribs = get_dev_attribs($device['device_id']);
    $model   = get_model_array($device);

    register_html_resource('js', 'js/jquery.serializejson.min.js');
    include($filename);
} else {
    print_error('<h3>Page does not exist</h4>
The requested page does not exist. Please correct the URL and try again.');
}

unset($filename, $navbar, $panes, $link_array);

register_html_title("Settings");

// EOF
