<?php

// ******************************************
// sensible defaults
$mapdir             = 'configs';
$observium_base     = '../../';
$observium_url      = '/';
$ignore_observium   = FALSE;
$config['base_url'] = $observium_url;

include_once("../includes/observium.inc.php");

include($config['html_dir'] . "/includes/authenticate.inc.php");

// Don't run if weathermap isn't enabled
if (!$config['weathermap']['enable'] || $_SESSION['userlevel'] < 7) {
    die();
}


$config['base_url'] = (isset($config['url_path']) ? $config['url_path'] : $observium_url);
$observium_found    = TRUE;


// ******************************************

function js_escape($str)
{
    $str = str_replace('\\', '\\\\', $str);
    $str = str_replace("'", "\\\'", $str);

    $str = "'" . $str . "'";

    return ($str);
}

if (isset($_REQUEST['command']) && $_REQUEST["command"] == 'link_step2') {
    $dataid = intval($_REQUEST['dataid']);
    ?>

    <html>
    <head>
        <script type="text/javascript">
            function update_source_step2(graphid) {
                var graph_url, hover_url;

                var base_url = '<?php echo isset($config['base_url']) ? $config['base_url'] : ''; ?>';

                if (typeof window.opener == "object") {

                    graph_url = base_url + 'graph.php?height=100&width=512&device=' + graphid + '&type=device_bits&legend=no';
                    info_url = base_url + 'device/device=' + graphid + '/';

                    opener.document.forms["frmMain"].node_new_name.value = 'test';
                    opener.document.forms["frmMain"].node_label.value = 'testing';
                    opener.document.forms["frmMain"].link_infourl.value = info_url;
                    opener.document.forms["frmMain"].link_hover.value = graph_url;
                }
                self.close();
            }

            window.onload = update_source_step2(<?php echo $graphid; ?>);

        </script>
    </head>
    <body>
    This window should disappear in a moment.
    </body>
    </html>
    <?php
    // end of link step 2
}

if (isset($_REQUEST['command']) && $_REQUEST["command"] == 'link_step1') {
    ?>
    <html>
    <head>
        <script type="text/javascript" src="editor-resources/jquery-latest.min.js"></script>
        <script type="text/javascript">

            function filterlist(previous) {
                var filterstring = $('input#filterstring').val();

                if (filterstring == '') {
                    $('ul#dslist > li').show();
                    if ($('#ignore_desc').is(':checked')) {
                        $("ul#dslist > li:contains('Desc::')").hide();
                    }


                } else if (filterstring != previous) {
                    $('ul#dslist > li').hide();
                    $("ul#dslist > li:contains('" + filterstring + "')").show();
                    if ($('#ignore_desc').is(':checked')) {
                        $("ul#dslist > li:contains('Desc::')").hide();
                    }

                } else if (filterstring == previous) {
                    if ($('#ignore_desc').is(':checked')) {
                        $("ul#dslist > li:contains('Desc::')").hide();
                    } else {
                        $('ul#dslist > li').hide();
                        $("ul#dslist > li:contains('" + filterstring + "')").show();
                    }
                }

            }

            function filterignore() {
                if ($('#ignore_desc').is(':checked')) {
                    $("ul#dslist > li:contains('Desc::')").hide();
                } else {
                    //$('ul#dslist > li').hide();
                    $("ul#dslist > li:contains('" + previous + "')").show();
                }
            }

            $(document).ready(function () {
                $('span.filter').keyup(function () {
                    var previous = $('input#filterstring').val();
                    setTimeout(function () {
                        filterlist(previous)
                    }, 500);
                }).show();
                $('span.ignore').click(function () {
                    var previous = $('input#filterstring').val();
                    setTimeout(function () {
                        filterlist(previous)
                    }, 500);
                });
            });

            function update_source_step2(graphid, name, portid, ifAlias, ifDesc, ifIndex) {
                var graph_url, hover_url;

                var base_url = '<?php echo isset($config['base_url']) ? $config['base_url'] : ''; ?>';

                if (typeof window.opener == "object") {

                    graph_url = base_url + 'graph.php?height=100&width=512&id=' + portid + '&type=port_bits&legend=no';
                    info_url = base_url + 'graphs/type=port_bits/id=' + portid + '/';

                    opener.document.forms["frmMain"].node_new_name.value = 'test';
                    opener.document.forms["frmMain"].node_label.value = 'testing';
                    opener.document.forms["frmMain"].link_infourl.value = info_url;
                    opener.document.forms["frmMain"].link_hover.value = graph_url;
                }
                self.close();
            }

            function update_source_step1(dataid, name, portid, ifAlias, ifDesc, ifIndex) {
                // This must be the section that looks after link properties
                var newlocation;
                var fullpath;

                var rra_path = <?php echo js_escape($config['install_dir'] . '/rrd/'); ?>+name + '/port-';

                if (typeof window.opener == "object") {
                    //fullpath = rra_path + ifIndex + '.rrd:INOCTETS:OUTOCTETS';
                    fullpath = 'obs_port:'+portid;
                    //if (document.forms['mini'].aggregate.checked) {
                    //    opener.document.forms["frmMain"].link_target.value = opener.document.forms["frmMain"].link_target.value + " " + fullpath;
                    //} else {
                        opener.document.forms["frmMain"].link_target.value = fullpath;
                    //}
                }
                if (document.forms['mini'].overlib.checked) {

                    window.onload = update_source_step2(dataid, name, portid, ifAlias, ifDesc, ifIndex);

                } else {
                    self.close();
                }
            }

            function applyDSFilterChange(objForm) {
                strURL = '?host_id=' + objForm.host_id.value;
                strURL = strURL + '&command=link_step1';
                if (objForm.overlib.checked) {
                    strURL = strURL + "&overlib=1";
                } else {
                    strURL = strURL + "&overlib=0";
                }
                // document.frmMain.link_bandwidth_out_cb.checked
                if (objForm.aggregate.checked) {
                    strURL = strURL + "&aggregate=1";
                } else {
                    strURL = strURL + "&aggregate=0";
                }
                document.location = strURL;
            }

        </script>
        <style type="text/css">
            body {
                font-family: sans-serif;
                font-size: 10pt;
            }

            ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            ul {
                border: 1px solid black;
            }

            ul li.row0 {
                background: #ddd;
            }

            ul li.row1 {
                background: #ccc;
            }

            ul li {
                border-bottom: 1px solid #aaa;
                border-top: 1px solid #eee;
                padding: 2px;
            }

            ul li a {
                text-decoration: none;
                color: black;
            }
        </style>
        <title>Pick a data source</title>
    </head>
    <body>
    <?php

    $host_id   = -1;
    $overlib   = TRUE;
    $aggregate = FALSE;

    if (isset($_REQUEST['aggregate'])) {
        $aggregate = ($_REQUEST['aggregate'] == 0 ? FALSE : TRUE);
    }
    if (isset($_REQUEST['overlib'])) {
        $overlib = ($_REQUEST['overlib'] == 0 ? FALSE : TRUE);
    }

    if (isset($_REQUEST['host_id'])) {
        $host_id = intval($_REQUEST['host_id']);
    }

    $hosts = dbFetchRows("SELECT `device_id`,`hostname` FROM `devices` ORDER BY `hostname`");

    ?>

    <h3>Pick an Observium port:</h3>

    <form name="mini">
        <?php
        if (count($hosts) > 0) {
            print 'Host: <select name="host_id"  onChange="applyDSFilterChange(document.mini)">';

            print '<option ' . ($host_id == -1 ? 'SELECTED' : '') . ' value="-1">Any</option>';
            print '<option ' . ($host_id == 0 ? 'SELECTED' : '') . ' value="0">None</option>';
            foreach ($hosts as $host) {
                print '<option ';
                if ($host_id == $host['device_id']) {
                    print " SELECTED ";
                }
                print 'value="' . $host['device_id'] . '">' . $host['hostname'] . '</option>';
            }
            print '</select><br />';
        }

        print '<span class="filter" style="display: none;">Filter: <input id="filterstring" name="filterstring" size="20"> (case-sensitive)<br /></span>';
        print '<input id="overlib" name="overlib" type="checkbox" value="yes" ' . ($overlib ? 'CHECKED' : '') . '> <label for="overlib">Also set OVERLIBGRAPH and INFOURL.</label><br />';
        print '<input id="aggregate" name="aggregate" type="checkbox" value="yes" ' . ($aggregate ? 'CHECKED' : '') . '> <label for="aggregate">Append TARGET to existing one (Aggregate)</label><br />';
        print '<span class="ignore"><input id="ignore_desc" name="ignore_desc" type="checkbox" value="yes"> <label for="ignore_desc">Ignore blank interface descriptions</label></span>';

        print '</form><div class="listcontainer"><ul id="dslist">';

        $query = "SELECT devices.device_id,hostname,ports.port_id,ports.ifAlias,ports.ifIndex,ports.ifDescr FROM devices LEFT JOIN ports ON devices.device_id=ports.device_id WHERE ports.disabled=0";

        if ($host_id > 0) {
            $query .= " AND devices.device_id='$host_id'";
        }

        $query .= " ORDER BY hostname,ports.ifAlias";

        $ports = dbFetchRows($query);


        $i = 0;
        if (count($ports) > 0) {
            foreach ($ports as $port) {
                echo "<li class=\"row" . ($i % 2) . "\">";
                $key = $port['device_id'] . "','" . $port['hostname'] . "','" . $port['port_id'] . "','" . addslashes($port['ifAlias']) . "','" . addslashes($port['ifDescr']) . "','" . $port['ifIndex'];
                echo "<a href=\"#\" onclick=\"update_source_step1('$key')\">
                                <span style='color:darkred'>" . $port['hostname'] . "</span> <b>|</b>
                                <span style='color:darkblue'>" . $port['ifDescr'] . "</span> <b>|</b> " .
                     $port['ifAlias'] . "</a>";
                echo "</li>\n";

                $i++;
            }
        } else {
            print "<li>No results...</li>";
        }

        ?>
        </ul>
        </div>
    </body>
    </html>
    <?php
} // end of link step 1

if (isset($_REQUEST['command']) && $_REQUEST["command"] == 'node_step1') {
    $host_id = -1;

    $overlib   = TRUE;
    $aggregate = FALSE;

    if (isset($_REQUEST['aggregate'])) {
        $aggregate = ($_REQUEST['aggregate'] == 0 ? FALSE : TRUE);
    }
    if (isset($_REQUEST['overlib'])) {
        $overlib = ($_REQUEST['overlib'] == 0 ? FALSE : TRUE);
    }

    if (isset($_REQUEST['host_id'])) {
        $host_id = intval($_REQUEST['host_id']);
    }

    $hosts = dbFetchRows("SELECT `device_id` AS `id`,`hostname` as `name` FROM `devices` ORDER BY `hostname`");

    ?>
    <html>
    <head>
        <script type="text/javascript" src="editor-resources/jquery-latest.min.js"></script>
        <script type="text/javascript">

            function filterlist(previous) {
                var filterstring = $('input#filterstring').val();

                if (filterstring == '') {
                    $('ul#dslist > li').show();
                    return;
                }

                if (filterstring != previous) {
                    $('ul#dslist > li').hide();
                    $("ul#dslist > li:contains('" + filterstring + "')").show();
                    //$('ul#dslist > li').contains(filterstring).show();
                }
            }

            $(document).ready(function () {
                $('span.filter').keyup(function () {
                    var previous = $('input#filterstring').val();
                    setTimeout(function () {
                        filterlist(previous)
                    }, 500);
                }).show();
            });

            function applyDSFilterChange(objForm) {
                strURL = '?host_id=' + objForm.host_id.value;
                strURL = strURL + '&command=node_step1';
                if (objForm.overlib.checked) {
                    strURL = strURL + "&overlib=1";
                } else {
                    strURL = strURL + "&overlib=0";
                }

                document.location = strURL;
            }

        </script>
        <script type="text/javascript">

            function update_source_step1(graphid, name) {
                // This is the section that sets the Node Properties
                var graph_url, hover_url;

                var base_url = '<?php echo(isset($config['base_url']) ? $config['base_url'] : ''); ?>';

                if (typeof window.opener == "object") {

                    graph_url = base_url + 'graph.php?height=100&width=512&device=' + graphid + '&type=device_bits&legend=no';
                    info_url = base_url + 'device/device=' + graphid + '/';

                    // only set the overlib URL unless the box is checked
                    if (document.forms['mini'].overlib.checked) {
                        opener.document.forms["frmMain"].node_infourl.value = info_url;
                    }
                    opener.document.forms["frmMain"].node_hover.value = graph_url;
                    opener.document.forms["frmMain"].node_new_name.value = name;
                    opener.document.forms["frmMain"].node_label.value = name;
                }
                self.close();
            }
        </script>

        <style type="text/css">
            body {
                font-family: sans-serif;
                font-size: 10pt;
            }

            ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            ul {
                border: 1px solid black;
            }

            ul li.row0 {
                background: #ddd;
            }

            ul li.row1 {
                background: #ccc;
            }

            ul li {
                border-bottom: 1px solid #aaa;
                border-top: 1px solid #eee;
                padding: 2px;
            }

            ul li a {
                text-decoration: none;
                color: black;
            }
        </style>
        <title>Pick a graph</title>
    </head>
    <body>

    <h3>Pick a graph:</h3>

    <form name="mini">
        <?php
        if (count($hosts) > 0) {
            print 'Host: <select name="host_id"  onChange="applyDSFilterChange(document.mini)">';

            print '<option ' . ($host_id == -1 ? 'SELECTED' : '') . ' value="-1">Any</option>';
            print '<option ' . ($host_id == 0 ? 'SELECTED' : '') . ' value="0">None</option>';
            foreach ($hosts as $host) {
                print '<option ';
                if ($host_id == $host['id']) {
                    print " SELECTED ";
                }
                print 'value="' . $host['id'] . '">' . $host['name'] . '</option>';
            }
            print '</select><br />';
        }

        print '<span class="filter" style="display: none;">Filter: <input id="filterstring" name="filterstring" size="20"> (case-sensitive)<br /></span>';
        print '<input id="overlib" name="overlib" type="checkbox" value="yes" ' . ($overlib ? 'CHECKED' : '') . '> <label for="overlib">Set both OVERLIBGRAPH and INFOURL.</label><br />';

        print '</form><div class="listcontainer"><ul id="dslist">';




        foreach(dbFetchRows($SQL_picklist) AS $queryrows) {
                echo "<li>";
                $key  = $queryrows['id'];
                $name = $queryrows['name'];
                echo "<a href=\"#\" onclick=\"update_source_step1('$key','$name')\">" . $queryrows['name'] . "</a>";
                echo "</li>\n";
                $i++;
        }

        //..} else {
        //    print "No results...";
       // }

        ?>
        </ul>
    </body>
    </html>
    <?php
} // end of node step 1
// vim:ts=4:sw=4:
?>
