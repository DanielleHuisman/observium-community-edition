<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage definitions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Definitions related to the Web UI

// WUI specific definitions, but can used in other code, like alert notifications

// Specific string for detect empty variable in web queries
const OBS_VAR_UNSET = '[EMPTY]';

// Default classes
const OBS_CLASS_BOX = 'box box-solid';
const OBS_CLASS_TABLE = 'table table-condensed';
// Combination of classes
const OBS_CLASS_TABLE_BOX = OBS_CLASS_BOX . ' ' . OBS_CLASS_TABLE;
const OBS_CLASS_TABLE_STRIPED = OBS_CLASS_TABLE . ' table-striped';
const OBS_CLASS_TABLE_STRIPED_TWO = OBS_CLASS_TABLE . ' table-striped-two';
const OBS_CLASS_TABLE_STRIPED_MORE = OBS_CLASS_TABLE . ' table-condensed-more table-striped';

// Colours
// FIXME, we still use this somewhere? :O
const OBS_COLOUR_LIST_A = '#ffffff'; //$list_colour_a   = "#ffffff";
const OBS_COLOUR_LIST_B = '#eeeeee'; //$list_colour_b   = "#eeeeee";
const OBS_COLOUR_LIST_B_B = '#e3e3e3'; //$list_colour_b_b = "#e3e3e3";
const OBS_COLOUR_LIST_HIGHLIGHT = '#ffcccc'; //$list_highlight  = "#ffcccc";
const OBS_COLOUR_WARN_A = '#ffeeee'; //$warn_colour_a   = "#ffeeee";
const OBS_COLOUR_WARN_B = '#ffcccc'; //$warn_colour_b   = "#ffcccc";

/* After this line keep only WUI specific definitions, not required in cli! */
//if (is_cli()) { return; }

// List of allowed (un-escaped) tags in escape_html(): <tag>..</tag>, <br/>
$config['escape_html']['tags']            = []; // prevent change by config
$config['escape_html']['tags'][]          = 'sup';
$config['escape_html']['tags'][]          = 'sub';
$config['escape_html']['tags'][]          = 'br';

// List of allowed (un-escaped) entities in escape_html(): &entity;
$config['escape_html']['entities']        = []; // prevent change by config
$config['escape_html']['entities'][]      = 'deg';    // °
$config['escape_html']['entities'][]      = 'omega';  // ω
$config['escape_html']['entities'][]      = 'Omega';  // Ω
$config['escape_html']['entities'][]      = 'mu';     // μ
$config['escape_html']['entities'][]      = 'pi';     // π
$config['escape_html']['entities'][]      = 'hellip'; // …
$config['escape_html']['entities'][]      = 'mldr';   // …
$config['escape_html']['entities'][]      = 'nldr';   // ‥
$config['escape_html']['entities'][]      = 'plusmn'; // ±
$config['escape_html']['entities'][]      = 'pm';     // ±
$config['escape_html']['entities'][]      = 'micro';  // µ
// HEX/UNICODE entities (probably need more generic way, also for emoji)
$config['escape_html']['entities'][]      = '#x200B'; // &#x200B; U+200B ZERO WIDTH SPACE (HTML &#8203;)
$config['escape_html']['entities'][]      = '#8203';  // &#x200B; U+200B ZERO WIDTH SPACE (HTML &#8203;)

$config['pages']['gridstack']['no_panel'] = TRUE;
$config['pages']['dashboard']['no_panel'] = TRUE;
$config['pages']['map']['no_panel']       = TRUE;
$config['pages']['map_traffic']['no_panel'] = TRUE;

// Refresh pages definitions
$config['wui']['refresh_times']       = [ 0, 60, 120, 300, 900, 1800 ]; // Allowed refresh times in seconds
// $vars array combination where auto-refresh page disabled by default
$config['wui']['refresh_disabled'][]  = [ 'page' => 'dashboard' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'map' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'add_alert_check' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'alert_check' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'alert_regenerate' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'alert_maintenance_add' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'group_add' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'groups_regenerate' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'group', 'view' => 'edit' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'add_alertlog_rule' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'syslog_rules' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'add_syslog_rule' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'contact' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'contacts' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'bills', 'view' => 'add' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'bill', 'view' => 'edit' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'bill', 'view' => 'delete' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'device', 'tab' => 'data' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'device', 'tab' => 'edit' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'device', 'tab' => 'port', 'view' => 'realtime' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'device', 'tab' => 'showconfig' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'device', 'tab' => 'entphysical' ]; // Inventory
$config['wui']['refresh_disabled'][]  = [ 'page' => 'addhost' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'delhost' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'delsrv' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'deleted-ports' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'user_add' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'user_edit' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'settings' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'preferences' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'logout' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'customoids' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'log' ];
$config['wui']['refresh_disabled'][]  = [ 'page' => 'pollers' ];

// Search modules used by the ajax search, in order.
$config['wui']['search_modules'] = [ 'groups', 'devices', 'accesspoints', 'ports', 'slas', 'sensors', 'status',
                                     'neighbours', 'ip-addresses', 'inventory', 'loadbalancers' ];

// Default groups list (on status page and default panel)
//$config['wui']['groups_list'] = [ 'device', 'port', 'processor', 'mempool', 'sensor', 'bgp_peer' ];
$config['wui']['groups_list'] = [ 'device', 'port', 'processor', 'mempool', 'sensor' ];

// Themes (dark not exist in CE)

$config['themes']['light']    = [ 'name' => "Light Mode",     'type' => 'light',  'css' => 'observium.css',          'icon' => 'sprite-sun' ];
$config['themes']['dark']     = [ 'name' => "Dark Mode",      'type' => 'dark',   'css' => 'observium-dark.css',     'icon' => 'sprite-moon' ];
$config['themes']['darkblue'] = [ 'name' => "Dark Blue Mode", 'type' => 'dark',   'css' => 'observium-darkblue.css', 'icon' => 'sprite-moon' ];

// Define Icons used by the user interface, emoji icons see in emoji.inc.php

$config['icon']['globe']             = "sprite-globe";
$config['icon']['overview']          = "sprite-overview";

$config['icon']['settings-change']   = "sprite-sliders-2";
$config['icon']['config']            = "sprite-config";
$config['icon']['logout']            = "sprite-logout";

$config['icon']['plus']              = "sprite-plus";
$config['icon']['minus']             = "sprite-minus";
$config['icon']['success']           = "sprite-success";
$config['icon']['error']             = "sprite-error";
$config['icon']['important']         = "sprite-important";

$config['icon']['stop']              = "sprite-cancel";
$config['icon']['cancel']            = "sprite-cancel";
$config['icon']['help']              = "sprite-support";
$config['icon']['info']              = "sprite-info";
$config['icon']['exclamation']       = "sprite-exclamation-mark";

$config['icon']['critical']          = $config['icon']['exclamation']; // red exclamation mark in a circle
$config['icon']['warning']           = $config['icon']['error'];       // yellow exclamation mark in a triangle
$config['icon']['informational']     = $config['icon']['info'];        // blue exclamation mark in a circle

$config['icon']['flag']              = "sprite-flag";
$config['icon']['plus']              = "sprite-plus";
$config['icon']['export']            = "sprite-export";
$config['icon']['minus']             = "sprite-minus";
$config['icon']['filter']            = "sprite-funnel";
$config['icon']['question']          = "sprite-question";
$config['icon']['checked']           = "sprite-checked";
$config['icon']['ok']                = "sprite-ok";
$config['icon']['return']            = "sprite-return";
$config['icon']['sort']              = "sprite-sort";
$config['icon']['network']           = "sprite-routing";
$config['icon']['up']                = $config['icon']['checked'];
$config['icon']['down']              = $config['icon']['minus'];
// what is this joke? ignore/shutdown :)
$config['icon']['ignore']            = "sprite-shutdown";
$config['icon']['shutdown']          = "sprite-ignore";
$config['icon']['percent']           = "sprite-percent";

$config['icon']['or-gate']           = "sprite-logic-or";
$config['icon']['and-gate']          = "sprite-logic-and";

$config['icon']['ipv4']              = "sprite-ipv4";
$config['icon']['ipv6']              = "sprite-ipv6";

$config['icon']['connected']         = "sprite-connected";
$config['icon']['cross-connect']     = "sprite-cross-connect";
$config['icon']['merge']             = "sprite-merge";
$config['icon']['split']             = "sprite-split";

$config['icon']['group']             = "sprite-groups";
$config['icon']['groups']            = $config['icon']['group'];

$config['icon']['alert']             = "sprite-alert";
$config['icon']['alert-log']         = "sprite-alert-log";
$config['icon']['alert-rules']       = "sprite-alert-rules";
$config['icon']['alert-rule-add']    = $config['icon']['plus'];

$config['icon']['scheduled-maintenance']     = "sprite-scheduled-maintenance";
$config['icon']['scheduled-maintenance-add'] = $config['icon']['plus'];

$config['icon']['syslog']            = "sprite-syslog";
$config['icon']['syslog-alerts']     = "sprite-syslog-alerts";
$config['icon']['syslog-rules']      = "sprite-syslog-rules";
$config['icon']['syslog-rule-add']   = $config['icon']['plus'];

$config['icon']['eventlog']          = "sprite-eventlog";

$config['icon']['pollerlog']         = "sprite-performance";
$config['icon']['pollers']           = "sprite-module";
$config['icon']['processes']         = "sprite-processes";

$config['icon']['netmap']            = "sprite-netmap";
$config['icon']['map']            = "sprite-map";
$config['icon']['contacts']          = "sprite-mail";
$config['icon']['contact-add']       = $config['icon']['plus'];

$config['icon']['customoid']         = "sprite-customoid";
$config['icon']['customoid-add']     = $config['icon']['plus'];

$config['icon']['inventory']         = "sprite-inventory";

$config['icon']['package']           = "sprite-package";
$config['icon']['packages']          = "sprite-package";

$config['icon']['search']            = "sprite-search";

$config['icon']['devices']           = "sprite-devices";
$config['icon']['device']            = "sprite-device";
$config['icon']['device-delete']     = $config['icon']['minus'];

$config['icon']['location']          = "sprite-building";
$config['icon']['locations']         = $config['icon']['location'];
$config['icon']['port']              = "sprite-ethernet";
$config['icon']['port-core']         = "sprite-hub";
$config['icon']['port-customer']     = "sprite-user-self";
$config['icon']['port-transit']      = "sprite-transit";
$config['icon']['port-peering']      = "sprite-peering";
$config['icon']['port-peering-transit'] = "sprite-peering-transit";

$config['icon']['health']            = "sprite-health";
$config['icon']['processor']         = "sprite-processor";
$config['icon']['mempool']           = "sprite-mempool";
$config['icon']['storage']           = "sprite-database";
$config['icon']['diskio']            = "sprite-storage-io";
$config['icon']['printersupply']     = "sprite-printer-supplies";
$config['icon']['status']            = "sprite-status";
$config['icon']['sensor']            = "sprite-performance";
$config['icon']['sla']               = "sprite-sla";
$config['icon']['pseudowire']        = "sprite-cross-connect";
$config['icon']['virtual-machine']   = "sprite-virtual-machine";
$config['icon']['antenna']           = "sprite-antenna";
$config['icon']['p2pradio']          = "sprite-antenna";
$config['icon']['billing']           = "sprite-accounting";
$config['icon']['neighbours']        = "sprite-neighbours";
$config['icon']['cbqos']             = "sprite-qos";
$config['icon']['voltage']           = "sprite-voltage";
$config['icon']['pressure']          = "sprite-pressure";
$config['icon']['frequency']         = "sprite-frequency";
$config['icon']['dbm']               = "sprite-laser";
$config['icon']['counter']           = "sprite-counter";
$config['icon']['fanspeed']          = "sprite-fanspeed";
$config['icon']['current']           = "sprite-amps";
$config['icon']['power']             = "sprite-watts";
$config['icon']['illuminance']       = "sprite-light-bulb";
$config['icon']['load']              = "sprite-asterisk";
$config['icon']['progress']          = "sprite-percent";
$config['icon']['temperature']       = "sprite-temperature";
$config['icon']['humidity']          = "sprite-humidity";
$config['icon']['airflow']           = "sprite-airflow";
$config['icon']['current']           = "sprite-amps";
$config['icon']['apower']            = "sprite-voltamps";
$config['icon']['rpower']            = "sprite-voltampreactive";
$config['icon']['crestfactor']       = "sprite-lightning";
$config['icon']['powerfactor']       = "sprite-lightning";
$config['icon']['impedance']         = "sprite-ohms-2";
$config['icon']['resistance']        = "sprite-ohms";
$config['icon']['velocity']          = "sprite-performance";
$config['icon']['waterflow']         = "sprite-flowrate";
$config['icon']['volume']            = "sprite-volume";
$config['icon']['distance']          = "sprite-distance";
$config['icon']['lflux']             = "sprite-light-bulb";
$config['icon']['clock']             = "sprite-clock";
$config['icon']['fiber']             = "sprite-laser"; // FIXME need other icon
$config['icon']['wavelength']        = "sprite-laser"; // FIXME need other icon
$config['icon']['gauge']             = "sprite-data"; // FIXME need other icon
$config['icon']['distance']          = "sprite-distance";

// Status classes
$config['icon']['battery']           = "sprite-capacity";
$config['icon']['mains']             = "sprite-network";
$config['icon']['outlet']            = "sprite-power";
$config['icon']['linkstate']         = $config['icon']['port'];
$config['icon']['fan']               = $config['icon']['fanspeed'];
$config['icon']['blower']            = $config['icon']['airflow'];
$config['icon']['chassis']           = "sprite-nic";          // FIXME need other icon
$config['icon']['contact']           = "sprite-connected";    // FIXME need other icon
$config['icon']['output']            = "sprite-merge";        // FIXME need other icon
$config['icon']['powersupply']       = "sprite-power";        // FIXME need other icon
$config['icon']['rectifier']         = "sprite-frequency-2";  // FIXME need other icon

$config['icon']['service']           = "sprite-service";
$config['icon']['servicegroup']      = $config['icon']['service'];
$config['icon']['vserver']           = "sprite-device";

$config['icon']['runtime']           = "sprite-runtime";
$config['icon']['apps']              = "sprite-applications";
$config['icon']['capacity']          = "sprite-capacity";
$config['icon']['collectd']          = "sprite-collectd";
$config['icon']['munin']             = "sprite-munin";
$config['icon']['smokeping']         = "sprite-paper-plane";
$config['icon']['wifi']              = "sprite-wifi";
$config['icon']['hypervisor']        = "sprite-virtual-machine";
$config['icon']['logs']              = "sprite-logs";
$config['icon']['loadbalancer']      = "sprite-loadbalancer-2";
$config['icon']['routing']           = "sprite-routing";
$config['icon']['vrf']               = "sprite-vrf";
$config['icon']['cef']               = "sprite-cef";
$config['icon']['ospf']              = "sprite-ospf";
$config['icon']['eigrp']             = "sprite-eigrp";
$config['icon']['ipsec_tunnel']      = "sprite-tunnel";
$config['icon']['vlan']              = "sprite-vlan";
$config['icon']['switching']         = "sprite-switching";
$config['icon']['crossbar']          = $config['icon']['switching'];

$config['icon']['nfsen']             = "sprite-funnel";
$config['icon']['device-data']       = "sprite-data";

$config['icon']['device-poller']     = "sprite-performance";
$config['icon']['techsupport']       = "sprite-support";
$config['icon']['tools']             = "sprite-tools";
$config['icon']['device-tools']      = "sprite-cog";
$config['icon']['device-settings']   = "sprite-settings-3";
$config['icon']['hardware']          = "sprite-cogs"; // FIXME

$config['icon']['linecard']          = "sprite-nic";

$config['icon']['firewall']          = "sprite-firewall";

$config['icon']['settings']          = "sprite-settings";
$config['icon']['refresh']           = "sprite-refresh";
$config['icon']['rebuild']           = $config['icon']['refresh'];

$config['icon']['graphs']            = "sprite-graphs";
$config['icon']['graphs-line']       = "sprite-graphs-line";
$config['icon']['graphs-stacked']    = "sprite-graphs-stacked";
$config['icon']['graphs-small']      = "sprite-graphs-small";
$config['icon']['graphs-large']      = "sprite-graphs-large";

$config['icon']['bgp']               = "sprite-bgp";
$config['icon']['bgp-internal']      = "sprite-bgp-internal";
$config['icon']['bgp-external']      = "sprite-bgp-external";
$config['icon']['bgp-alert']         = "sprite-bgp-alerts";
$config['icon']['bgp-afi']           = "sprite-bgp-afi";

$config['icon']['users']             = "sprite-users";
$config['icon']['user-self']         = "sprite-user-self";
$config['icon']['user-add']          = "sprite-user-add";
$config['icon']['user-delete']       = "sprite-user-delete";
$config['icon']['user-edit']         = "sprite-user-edit";
$config['icon']['user-log']          = "sprite-user-log";
$config['icon']['lock']              = "sprite-lock";

//$config['icon']['database']          = "sprite-storage-test2";
$config['icon']['databases']         = "sprite-databases";
$config['icon']['database']          = "sprite-database";
$config['icon']['mibs']              = "sprite-map-2";
$config['icon']['notes']             = "sprite-note";

// Font icons
$config['icon']['info-sign']         = "icon-info-sign";
$config['icon']['edit']              = "icon-cog";
$config['icon']['delete']            = "icon-trash";
$config['icon']['add']               = "icon-plus-sign";
$config['icon']['remove']            = "icon-minus-sign";

$config['icon']['arrow-up']          = "icon-circle-arrow-up";
$config['icon']['arrow-down']        = "icon-circle-arrow-down";
$config['icon']['arrow-right']       = "icon-circle-arrow-right";
$config['icon']['arrow-left']        = "icon-circle-arrow-left";

$config['icons'] = [ 'sprite-device', 'sprite-network', 'sprite-virtual-machine' ];

// EOF
