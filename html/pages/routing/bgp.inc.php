<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

//if ($_SESSION['permissions'] < '5')
//if ($_SESSION['userlevel'] < '5') {
//  print_error_permission();
//  return;
//}

$form_items = [];
$form_limit = 250; // Limit count for multiselect (use input instead)

$form_devices          = dbFetchColumn('SELECT DISTINCT `device_id` FROM `bgpPeers`;');
$form_items['devices'] = generate_form_values('device', $form_devices);
//r($form_items['devices']);

$param  = 'peer_as';
$column = 'bgpPeerRemoteAs';
// fast query 0.0015, 0.0020, 0.0017
$query = 'SELECT COUNT(DISTINCT `' . $column . '`) FROM `bgpPeers`' . generate_where_clause($GLOBALS['cache']['where']['devices_permitted']);
$count = dbFetchCell($query);
if ($count < $form_limit) {
    $form_items[$param] = []; // Set
    // slow query: 0.0093, 0.0125, 0.0063
    $query = 'SELECT DISTINCT `' . $column . '`, `astext` FROM `bgpPeers`' . generate_where_clause($cache['where']['devices_permitted']) . ' ORDER BY `' . $column . '`';
    foreach (dbFetchRows($query) as $entry) {
        if (safe_empty($entry[$column])) {
            continue;
        }

        $form_items[$param][$entry[$column]]['name']    = 'AS' . $entry[$column];
        $form_items[$param][$entry[$column]]['subtext'] = $entry['astext'];
    }
}

$form_params = [
  'local_ip' => 'bgpPeerLocalAddr',
  'peer_ip'  => 'bgpPeerRemoteAddr',
  //'peer_as'  => 'bgpPeerRemoteAs',
];

foreach ($form_params as $param => $column) {
    $query = 'SELECT COUNT(DISTINCT `' . $column . '`) FROM `bgpPeers`' . generate_where_clause($GLOBALS['cache']['where']['devices_permitted']);
    $count = dbFetchCell($query);
    if ($count < $form_limit) {
        $query = 'SELECT DISTINCT `' . $column . '` FROM `bgpPeers`' . generate_where_clause($GLOBALS['cache']['where']['devices_permitted']) . ' ORDER BY `' . $column . '`';
        foreach (dbFetchColumn($query) as $entry) {
            if (safe_empty($entry)) {
                continue;
            }

            if (str_contains($entry, ':')) {
                $form_items[$param][$entry]['group'] = 'IPv6';
                $form_items[$param][$entry]['name']  = ip_compress($entry);
            } else {
                $form_items[$param][$entry]['group'] = 'IPv4';
                $form_items[$param][$entry]['name']  = escape_html($entry);
            }
        }
    }
}

$form                     = [
  'type'          => 'rows',
  'space'         => '5px',
  'submit_by_key' => TRUE,
  'url'           => generate_url($vars)
];
$form['row'][0]['device'] = [
  'type'   => 'multiselect',
  'name'   => 'Local Device',
  'width'  => '100%',
  'value'  => $vars['device'],
  'values' => $form_items['devices']
];
$param                    = 'local_ip';
$param_name               = 'Local address';
foreach (['local_ip' => 'Local address',
          'peer_ip'  => 'Peer address',
          'peer_as'  => 'Remote AS'] as $param => $param_name) {
    if (isset($form_items[$param])) {
        // If not so much item values, use multiselect
        $form['row'][0][$param] = [
          'type'   => 'multiselect',
          'name'   => $param_name,
          'width'  => '100%',
          'value'  => $vars[$param],
          'values' => $form_items[$param]
        ];
    } else {
        // Instead, use input with autocomplete
        $form['row'][0][$param] = [
          'type'        => 'text',
          'name'        => $param_name,
          'width'       => '100%',
          'placeholder' => TRUE,
          'ajax'        => TRUE,
          'ajax_vars'   => ['field' => 'bgp_' . $param],
          'value'       => $vars[$param]
        ];
    }
}

$form['row'][0]['type'] = [
  'type'   => 'select',
  'name'   => 'Type',
  'width'  => '100%',
  'value'  => $vars['type'],
  'values' => ['' => 'All', 'internal' => 'iBGP', 'external' => 'eBGP']
];

// search button
$form['row'][0]['search'] = [
  'type'  => 'submit',
  //'name'        => 'Search',
  //'icon'        => 'icon-search',
  'right' => TRUE
];

$panel_form = [
  'type'          => 'rows',
  'title'         => 'Search BGP',
  'space'         => '10px',
  'submit_by_key' => TRUE,
  'url'           => generate_url($vars)
];

$panel_form['row'][0]['device'] = $form['row'][0]['device'];
//$panel_form['row'][0]['device']['grid'] = 6;
$panel_form['row'][0]['local_ip'] = $form['row'][0]['local_ip'];

$panel_form['row'][1]['peer_as'] = $form['row'][0]['peer_as'];
$panel_form['row'][1]['peer_ip'] = $form['row'][0]['peer_ip'];

$panel_form['row'][2]['type']   = $form['row'][0]['type'];
$panel_form['row'][2]['search'] = $form['row'][0]['search'];

// Register custom panel
register_html_panel(generate_form($panel_form));

echo '<div class="hidden-xl">';
print_form($form);
echo '</div>';

unset($form, $panel_form, $form_items, $navbar);

if (!isset($vars['view'])) {
    $vars['view'] = 'details';
}

$link_array = ['page' => 'routing', 'protocol' => 'bgp'];

$types = [
  'all'      => 'All',
  'internal' => 'iBGP',
  'external' => 'eBGP'
];
foreach ($types as $option => $text) {
    $navbar['options'][$option]['text'] = $text;
    if ($vars['type'] == $option || (empty($vars['type']) && $option === 'all')) {
        $navbar['options'][$option]['class'] .= " active";
    }
    $bgp_options = ['type' => $option];
    if ($vars['adminstatus']) {
        $bgp_options['adminstatus'] = $vars['adminstatus'];
    } elseif ($vars['state']) {
        $bgp_options['state'] = $vars['state'];
    }
    $navbar['options'][$option]['url'] = generate_url($link_array, $bgp_options);
}

$statuses = [
  'stop'  => 'Shutdown',
  'start' => 'Enabled',
  'down'  => 'Down'
];
foreach ($statuses as $option => $text) {
    $status                             = ($option === 'down') ? 'state' : 'adminstatus';
    $navbar['options'][$option]['text'] = $text;
    if ($vars[$status] == $option) {
        $navbar['options'][$option]['class'] .= " active";
        $bgp_options                         = [$status => NULL];
    } else {
        $bgp_options = [$status => $option];
    }
    if ($vars['type']) {
        $bgp_options['type'] = $vars['type'];
    }
    $navbar['options'][$option]['url'] = generate_url($link_array, $bgp_options);
}

$navbar['options_right']['details']['text'] = 'No Graphs';
if ($vars['view'] === 'details') {
    $navbar['options_right']['details']['class'] .= ' active';
}
$navbar['options_right']['details']['url'] = generate_url($vars, ['view' => 'details', 'graph' => 'NULL']);

$navbar['options_right']['updates']['text'] = 'Updates';
if ($vars['graph'] === 'updates') {
    $navbar['options_right']['updates']['class'] .= ' active';
}
$navbar['options_right']['updates']['url'] = generate_url($vars, ['view' => 'graphs', 'graph' => 'updates']);

/*
$bgp_graphs = array();
foreach ($cache['graphs'] as $entry)
{
  if (preg_match('/^bgp_(?<subtype>prefixes)_(?<afi>ipv[46])(?<safi>[a-z]+)/', $entry, $matches))
  {
    if (!isset($bgp_graphs[$matches['safi']]))
    {
      $bgp_graphs[$matches['safi']] = array('text' => nicecase($matches['safi']));
    }
    $bgp_graphs[$matches['safi']]['types'][$matches['subtype'].'_'.$matches['afi'].$matches['safi']] = nicecase($matches['afi']) . ' ' . nicecase($matches['safi']) . ' ' . nicecase($matches['subtype']);
  }
}
*/

$bgp_graphs                       = ['unicast'   => ['text' => 'Unicast'],
                                     'multicast' => ['text' => 'Multicast'],
                                     'mac'       => ['text' => 'MAC Accounting']];
$bgp_graphs['unicast']['types']   = ['prefixes_ipv4unicast' => 'IPv4 Ucast Prefixes',
                                     'prefixes_ipv6unicast' => 'IPv6 Ucast Prefixes',
                                     'prefixes_ipv4vpn'     => 'VPNv4 Prefixes'];
$bgp_graphs['multicast']['types'] = ['prefixes_ipv4multicast' => 'IPv4 Mcast Prefixes',
                                     'prefixes_ipv6multicast' => 'IPv6 Mcast Prefixes'];

$bgp_graphs['mac']          = ['text' => 'MAC Accounting'];
$bgp_graphs['mac']['types'] = ['macaccounting_bits' => 'MAC Bits',
                               'macaccounting_pkts' => 'MAC Pkts'];
foreach ($bgp_graphs as $bgp_graph => $bgp_options) {
    $navbar['options_right'][$bgp_graph]['text'] = $bgp_options['text'];
    foreach ($bgp_options['types'] as $option => $text) {
        if ($vars['graph'] == $option) {
            $navbar['options_right'][$bgp_graph]['class']                        .= ' active';
            $navbar['options_right'][$bgp_graph]['suboptions'][$option]['class'] = 'active';
        }
        $navbar['options_right'][$bgp_graph]['suboptions'][$option]['text'] = $text;
        $navbar['options_right'][$bgp_graph]['suboptions'][$option]['url']  = generate_url($vars, ['view' => 'graphs', 'graph' => $option]);
    }
}

$navbar['class'] = "navbar-narrow";
$navbar['brand'] = "BGP";
print_navbar($navbar);
unset($navbar);

//r($cache['bgp']);
print_bgp_peer_table($vars);

// EOF
