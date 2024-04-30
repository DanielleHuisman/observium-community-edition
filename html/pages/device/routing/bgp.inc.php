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

echo generate_box_open();

?>

    <table class="table table-hover table-striped vertical-align">
        <tbody>
        <tr class="up">
            <td class="state-marker"></td>
            <td style="padding: 10px 14px;"><span style="font-size: 20px;">BGP AS<?php echo($device['human_local_as']); ?></span>
            </td>
            <td>

                <?php

                $sessions = [];
                foreach (dbFetchRows('SELECT `bgpPeer_id`,`local_as`,`bgpPeerState`,`bgpPeerAdminStatus`,`bgpPeerRemoteAs` FROM `bgpPeers` WHERE `device_id` = ?;', [$device['device_id']]) as $bgp) {
                    $sessions['count']++;
                    if ($bgp['bgpPeerAdminStatus'] === 'start' || $bgp['bgpPeerAdminStatus'] === 'running') {
                        $sessions['enabled']++;
                        if ($bgp['bgpPeerState'] !== 'established') {
                            $sessions['alerts']++;
                        } else {
                            $sessions['connected']++;
                        }
                    } else {
                        $sessions['shutdown']++;
                    }
                    if ($bgp['bgpPeerRemoteAs'] == $bgp['local_as']) {
                        $sessions['internal']++;
                    } else {
                        $sessions['external']++;
                    }
                }

                ?>
            </td>

            <td style="text-align: right;">

                <div class="btn-group" style="margin: 5px;">
                    <div class="btn btn-sm btn-default"><strong>Total Sessions</strong></div>
                    <div class="btn btn-sm btn-default"> <?php echo $sessions['count'] + 0; ?></div>
                </div>

                <div class="btn-group" style="margin: 5px;">
                    <div class="btn btn-sm btn-default"><strong>Errored Sessions</strong></div>
                    <div class="btn btn-sm btn-danger"> <?php echo $sessions['alerts'] + 0; ?></div>
                </div>

                <div class="btn-group" style="margin: 5px;">
                    <div class="btn btn-sm btn-default"><strong>iBGP</strong></div>
                    <div class="btn btn-sm btn-info"> <?php echo $sessions['internal'] + 0; ?></div>
                </div>

                <div class="btn-group" style="margin: 5px;">
                    <div class="btn btn-sm btn-default"><strong>eBGP</strong></div>
                    <div class="btn btn-sm btn-primary"> <?php echo $sessions['external'] + 0; ?></div>
                </div>
            </td>

        </tr>
        </tbody>
    </table>

<?php

echo generate_box_close();

if (!isset($vars['view'])) {
    $vars['view'] = 'details';
}

unset($navbar);
$link_array = [
  'page'    => 'device',
  'device'  => $device['device_id'],
  'tab'     => 'routing',
  'proto'   => 'bgp'
];

$types = [
  'all'      => 'All',
  'internal' => 'iBGP',
  'external' => 'eBGP'
];

foreach ($types as $option => $text) {
  $navbar['options'][$option]['text'] = $text;
  if ($vars['type'] == $option || (empty($vars['type']) && $option === 'all')) {
    $navbar['options'][$option]['class'] .= " active";
    $bgp_options = [ 'type' => NULL ];
  } else {
    $bgp_options = [ 'type' => $option ];
  }

  if ($vars['adminstatus']) {
    $bgp_options['adminstatus'] = $vars['adminstatus'];
  } elseif ($vars['state']) {
    $bgp_options['state'] = $vars['state'];
  }
  $navbar['options'][$option]['url'] = generate_url($link_array, $bgp_options);
}

// VRFs
if (dbExist('bgpPeers', '`device_id` = ? AND `virtual_name` NOT IS NULL', [ $device['device_id'] ])) {
  $navbar['options'][$option]['text'] = 'VRFs';
  $navbar['options'][$option]['url'] = generate_url($link_array, $bgp_options);
}

$statuses = [
  'stop'  => 'Shutdown',
  'start' => 'Enabled',
  'down'  => 'Down'
];
foreach ($statuses as $option => $text) {
  $status = ($option == 'down') ? 'state' : 'adminstatus';
  $navbar['options'][$option]['text'] = $text;
  if ($vars[$status] == $option) {
    $navbar['options'][$option]['class'] .= " active";
    $bgp_options = [ $status => NULL ];
  } else {
    $bgp_options = [ $status => $option ];
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
$navbar['options_right']['details']['url'] = generate_url($vars, [ 'view' => 'details', 'graph' => 'NULL' ]);

$navbar['options_right']['updates']['text'] = 'Updates';
if ($vars['graph'] === 'updates') {
  $navbar['options_right']['updates']['class'] .= ' active';
}
$navbar['options_right']['updates']['url'] = generate_url($vars, [ 'view' => 'graphs', 'graph' => 'updates' ]);

/*
$bgp_graphs = array();
foreach ($device['graphs'] as $entry)
{
  if (preg_match('/^bgp_(?<subtype>prefixes)_(?<afi>ipv[46])(?<safi>[a-z]+)/', $entry['graph'], $matches))
  {
    if (!isset($bgp_graphs[$matches['safi']]))
    {
      $bgp_graphs[$matches['safi']] = array('text' => nicecase($matches['safi']));
    }
    $bgp_graphs[$matches['safi']]['types'][$matches['subtype'].'_'.$matches['afi'].$matches['safi']] = nicecase($matches['afi']) . ' ' . nicecase($matches['safi']) . ' ' . nicecase($matches['subtype']);
  }
}
*/

$bgp_graphs                       = [
  'unicast'   => ['text' => 'Unicast'],
  'multicast' => ['text' => 'Multicast'],
  'mac'       => ['text' => 'MAC Accounting']
];
$bgp_graphs['unicast']['types']   = [
  'prefixes_ipv4unicast' => 'IPv4 Ucast Prefixes',
  'prefixes_ipv6unicast' => 'IPv6 Ucast Prefixes',
  'prefixes_ipv4vpn'     => 'VPNv4 Prefixes'
];
$bgp_graphs['multicast']['types'] = [
  'prefixes_ipv4multicast' => 'IPv4 Mcast Prefixes',
  'prefixes_ipv6multicast' => 'IPv6 Mcast Prefixes'
];
$bgp_graphs['mac']['types']       = [
  'macaccounting_bits' => 'MAC Bits',
  'macaccounting_pkts' => 'MAC Pkts'
];
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

// Pagination
$vars['pagination'] = TRUE;

//r($cache['bgp']);
print_bgp_peer_table($vars);

// EOF
