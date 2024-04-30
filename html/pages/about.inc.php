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

?>

    <div class="row">
        <div class="col-md-6">
            <?php

            print_versions();

            $ua = detect_browser();
            //r($ua);
            register_html_resource('js', 'moment.min.js');
            $tz = get_timezone(TRUE); // Refresh timezone, for get system TZ
            //r($tz);
            ?>
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">Browser & Timezone Information</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table table-striped table-condensed-more">
                        <tbody>
                        <tr>
                            <td><b>User-Agent</b></td>
                            <td><?php echo $ua['user_agent']; ?></td>
                        </tr>
                        <tr>
                            <td><b>Browser</b></td>
                            <td><?php echo $ua['browser_full'] . ' (' . $ua['platform'] . ')'; ?></td>
                        </tr>
                        <tr>
                            <td><b>Screen&nbsp;Resolution</b></td>
                            <td><?php echo $ua['screen_resolution']; ?></td>
                        </tr>
                        <tr>
                            <td><b>Timezone</b></td>
                            <td><?php echo $tz['system'] . ' (System), ' . $tz['php'] . ' (PHP), ' . $tz['mysql'] . ' (DB), '; ?>
                                <script>document.write(moment().format('Z') + ' (User)');</script>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-bottom: 10px; margin-top: 10px;">
                <table style="width: 100%; background: transparent;">
                    <tr>
                        <td style="width: 12%; text-align: center;"><a class="btn btn" target="_blank" href="<?php echo OBSERVIUM_URL; ?>"><i
                                  style="font-size: small;" class="icon-globe"></i>&nbsp; Web</a></td>
                        <td style="width: 12%; text-align: center;"><a class="btn btn" target="_blank" href="<?php echo OBSERVIUM_DOCS_URL; ?>"><i
                                  style="font-size: small;" class="icon-info"></i>&nbsp; Docs</a></td>
                        <td style="width: 18%; text-align: center;"><a class="btn btn" target="_blank" href="<?php echo OBSERVIUM_BUG_URL; ?>"><i
                                  style="font-size: small;" class="icon-bug"></i>&nbsp; Bugtracker</a></td>
                        <td style="width: 19%; text-align: center;"><a class="btn btn" target="_blank" href="https://discord.gg/GjpNXKWm8W"><i
                                  class="icon-gamepad"></i> Discord</a></td>
                        <td style="width: 17%; text-align: center;"><a class="btn btn" target="_blank" href="https://twitter.com/observium"><i
                                  style="font-size: small;" class="icon-twitter-sign"></i>&nbsp; Twitter</a></td>
                        <td style="width: 16%; text-align: center;"><a class="btn btn" target="_blank"
                                           href="https://www.facebook.com/pages/Observium/128354461353">
                                           <i style="font-size: small;" class="icon-facebook-sign"></i>&nbsp;
                                Facebook</a></td>
                    </tr>
                </table>
            </div>

            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title">Development Team</h3></div>
                <div class="box-body no-padding">
                    <dl class="dl-horizontal" style="margin: 0px; padding:5px;">
                        <dt style="text-align: left;"><i class="icon-user"></i> Adam Armstrong</dt>
                        <dd>Project Leader</dd>
                        <dt style="text-align: left;"><i class="icon-user"></i> Tom Laermans</dt>
                        <dd>Committer & Developer</dd>
                        <dt style="text-align: left;"><i class="icon-user"></i> Mike Stupalov</dt>
                        <dd>Committer & Developer</dd>
                    </dl>
                </div>
            </div>

            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title">Acknowledgements</h3></div>
                <div class="box-body no-padding">
                    <dl class="dl-horizontal" style="margin: 0px; padding:5px;">
                        <dt style="text-align: left;"><i class="icon-user"></i> Maarten Moerman</dt>
                        <dd>Makes the magic happen</dd>
                        <dt style="text-align: left;"><i class="icon-user"></i> Jonathan De Graeve</dt>
                        <dd>SNMP code improvements</dd>
                        <dt style="text-align: left;"><i class="icon-user"></i> Xiaochi Jin</dt>
                        <dd>Logo design</dd>
                        <dt style="text-align: left;"><i class="icon-user"></i> Akichi Ren</dt>
                        <dd>Post-steampunk observational hamster</dd>
                        <dt style="text-align: left;"><i class="icon-user"></i> Bruno Pramont</dt>
                        <dd>Collectd code</dd>
                        <dt style="text-align: left;"><i class="icon-user"></i> David Farrell</dt>
                        <dd>Help with parsing net-SNMP output in PHP</dd>
                        <dt style="text-align: left;"><i class="icon-user"></i> Dennis de Houx</dt>
                        <dd>Code contributions</dd>
                        <dt style="text-align: left;"><i class="icon-user"></i> Geert Hauwaerts</dt>
                        <dd>Code contributions</dd>
                    </dl>
                </div>
            </div>

            <?php

            if (!$_SESSION['user_limited']) {

                $cache_item = get_cache_item('stats');

                if (!ishit_cache_item($cache_item)) {
                    $stats                    = [];
                    $stats['devices']         = dbFetchCell('SELECT COUNT(*) FROM `devices`');
                    $stats['ports']           = dbFetchCell('SELECT COUNT(*) FROM `ports`');
                    $stats['syslog']          = dbFetchCell('SELECT COUNT(*) FROM `syslog`');
                    $stats['events']          = dbFetchCell('SELECT COUNT(*) FROM `eventlog`');
                    $stats['applications']    = dbFetchCell('SELECT COUNT(*) FROM `applications`');
                    $stats['storage']         = dbFetchCell('SELECT COUNT(*) FROM `storage`');
                    $stats['diskio']          = dbFetchCell('SELECT COUNT(*) FROM `ucd_diskio`');
                    $stats['processors']      = dbFetchCell('SELECT COUNT(*) FROM `processors`');
                    $stats['memory']          = dbFetchCell('SELECT COUNT(*) FROM `mempools`');
                    $stats['sensors']         = dbFetchCell('SELECT COUNT(*) FROM `sensors`');
                    $stats['sensors']         += dbFetchCell('SELECT COUNT(*) FROM `status`');
                    $stats['printersupplies'] = dbFetchCell('SELECT COUNT(*) FROM `printersupplies`');
                    $stats['hrdevice']        = dbFetchCell('SELECT COUNT(*) FROM `hrDevice`');
                    $stats['entphysical']     = dbFetchCell('SELECT COUNT(*) FROM `entPhysical`');

                    $stats['ipv4_addresses'] = dbFetchCell('SELECT COUNT(*) FROM `ipv4_addresses`');
                    $stats['ipv4_networks']  = dbFetchCell('SELECT COUNT(*) FROM `ipv4_networks`');
                    $stats['ipv6_addresses'] = dbFetchCell('SELECT COUNT(*) FROM `ipv6_addresses`');
                    $stats['ipv6_networks']  = dbFetchCell('SELECT COUNT(*) FROM `ipv6_networks`');

                    $stats['pseudowires'] = dbFetchCell('SELECT COUNT(*) FROM `pseudowires`');
                    $stats['vrfs']        = dbFetchCell('SELECT COUNT(*) FROM `vrfs`');
                    $stats['vlans']       = dbFetchCell('SELECT COUNT(*) FROM `vlans`');

                    $stats['netscaler_vservers'] = dbFetchCell('SELECT COUNT(*) FROM `netscaler_vservers`');
                    $stats['netscaler_services'] = dbFetchCell('SELECT COUNT(*) FROM `netscaler_services`');

                    $stats['vms']  = dbFetchCell('SELECT COUNT(*) FROM `vminfo`');
                    $stats['slas'] = dbFetchCell('SELECT COUNT(*) FROM `slas`');

                    $stats['db']  = get_db_size();
                    $stats['rrd'] = get_dir_size($config['rrd_dir']);

                    set_cache_item($cache_item, $stats, ['ttl' => 900]); // 15 min
                } else {
                    $stats = get_cache_data($cache_item);
                }
                // Clean cache item
                unset($cache_item);

                ?>
                <div class="box box-solid">
                    <div class="box-header"><h3 class="box-title">Statistics</h3></div>
                    <div class="box-body no-padding">

                        <table class="table table-striped table-condensed">
                            <tbody>
                            <tr>
                                <td style="width: 45%;"><i class="<?php echo $config['icon']['storage']; ?>"></i> <strong>DB size</strong></td>
                                <td><span class="pull-right"><?php echo format_bytes($stats['db']); ?></span></td>
                                <td style="width: 45%;"><i class="<?php echo $config['icon']['database']; ?>"></i> <strong>RRD size</strong></td>
                                <td><span class="pull-right"><?php echo format_bytes($stats['rrd']); ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['devices']; ?>"></i> <strong>Devices</strong></td>
                                <td><span class="pull-right"><?php echo $stats['devices']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['port']; ?>"></i> <strong>Ports</strong></td>
                                <td><span class="pull-right"><?php echo $stats['ports']; ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['ipv4']; ?>"></i> <strong>IPv4 Addresses</strong></td>
                                <td><span class="pull-right"><?php echo $stats['ipv4_addresses']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['ipv4']; ?>"></i> <strong>IPv4 Networks</strong></td>
                                <td><span class="pull-right"><?php echo $stats['ipv4_networks']; ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['ipv6']; ?>"></i> <strong>IPv6 Addresses</strong></td>
                                <td><span class="pull-right"><?php echo $stats['ipv6_addresses']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['ipv6']; ?>"></i> <strong>IPv6 Networks</strong></td>
                                <td><span class="pull-right"><?php echo $stats['ipv6_networks']; ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['service']; ?>"></i> <strong>Services</strong></td>
                                <td><span class="pull-right"><?php echo $stats['services']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['apps']; ?>"></i> <strong>Applications</strong></td>
                                <td><span class="pull-right"><?php echo $stats['applications']; ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['processor']; ?>"></i> <strong>Processors</strong></td>
                                <td><span class="pull-right"><?php echo $stats['processors']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['mempool']; ?>"></i> <strong>Memory pools</strong></td>
                                <td><span class="pull-right"><?php echo $stats['memory']; ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['storage']; ?>"></i> <strong>Storage Entries</strong></td>
                                <td><span class="pull-right"><?php echo $stats['storage']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['diskio']; ?>"></i> <strong>Disk I/O Entries</strong></td>
                                <td><span class="pull-right"><?php echo $stats['diskio']; ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['inventory']; ?>"></i> <strong>HR-MIB Entries</strong></td>
                                <td><span class="pull-right"><?php echo $stats['hrdevice']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['inventory']; ?>"></i> <strong>Entity-MIB Entries</strong></td>
                                <td><span class="pull-right"><?php echo $stats['entphysical']; ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['syslog']; ?>"></i> <strong>Syslog Entries</strong></td>
                                <td><span class="pull-right"><?php echo $stats['syslog']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['eventlog']; ?>"></i> <strong>Eventlog Entries</strong></td>
                                <td><span class="pull-right"><?php echo $stats['events']; ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['sensor']; ?>"></i> <strong>Sensors</strong></td>
                                <td><span class='pull-right'><?php echo $stats['sensors']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['printersupply']; ?>"></i> <strong>Printer Supplies</strong></td>
                                <td><span class='pull-right'><?php echo $stats['printersupplies']; ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['vserver']; ?>"></i> <strong>Netscaler VServers</strong></td>
                                <td><span class="pull-right"><?php echo $stats['netscaler_vservers']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['service']; ?>"></i> <strong>Netscaler Services</strong></td>
                                <td><span class="pull-right"><?php echo $stats['netscaler_services']; ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="<?php echo $config['icon']['virtual-machine']; ?>"></i> <strong>Virtual Machines</strong></td>
                                <td><span class="pull-right"><?php echo $stats['vms']; ?></span></td>
                                <td><i class="<?php echo $config['icon']['sla']; ?>"></i> <strong>IP SLAs</strong></td>
                                <td><span class="pull-right"><?php echo $stats['slas']; ?></span></td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>

                <?php

            } // End statistics for not limited users

            ?>
        </div>
        <div class="col-md-6">

            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title">License</h3></div>
                <div class="box-body no-padding">
                    <div style="padding:5px;"><pre class="small" style="margin:0px;">
        <?php include($config['install_dir'] . '/LICENSE.' . strtoupper(OBSERVIUM_EDITION)); ?>
      </pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php

// EOF
