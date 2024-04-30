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

register_html_resource('css', 'gridstack.min.css');
register_html_resource('js', 'lodash.min.js');
register_html_resource('js', 'jquery-ui.min.js');
register_html_resource('js', 'gridstack.all.js');

// Load map stuff so that it's available to widgets.
// included in base css to control styles
//register_html_resource('css', 'leaflet.css');
register_html_resource('js', 'leaflet.js');
// IE (pre Edge) js fetch fix
$ua = detect_browser();
if ($ua['browser'] === 'MSIE' ||
    ($ua['browser'] === 'Firefox' && version_compare($ua['version'], '61', '<'))) // Also for FF ESR60 and older
{
    register_html_resource('js', 'js/compat/bluebird.min.js');
    register_html_resource('js', 'js/compat/fetch.js');
}
register_html_resource('js', 'leaflet-realtime.js');

// Allows us to detect when things are resized.
register_html_resource('js', 'ResizeSensor.js');

include_dir($config['html_dir'] . '/includes/widgets/');

if ($_SESSION['userlevel'] >= 7 && $vars['reset_dashboard'] == "yes") {
    dbDelete('dashboards', '1');
    dbDelete('dash_widgets', '1');
}

$grid_cell_height = 20;
$grid_h_margin    = 100;
$grid_v_margin    = 15;

if (!isset($vars['dash'])) {
    $vars['dash'] = '1';

    $dashboard = dbFetchRow("SELECT * FROM `dashboards` WHERE `dash_id` = ?", [$vars['dash']]);

    if (!$dashboard) {
        //No dashboard, so generate a standard one
        include("includes/dashboard-generate.inc.php");
    }
}


if (isset($vars['edit']) && $_SESSION['userlevel'] > 7) {
    $is_editing = TRUE;
}

$dashboard = dbFetchRow("SELECT * FROM `dashboards` WHERE `dash_id` = ?", [$vars['dash']]);

if (is_array($dashboard)) {

    if ($is_editing === TRUE) {
        $form_items = ['types' => [
          'map'              => 'Map',
          'alert_table'      => 'Alert Table',
          'alert_boxes'      => "Alert Boxes",
          'alertlog'         => 'Alert Log',
          //'graph'               => 'Graph', // Doesn't work adding here
          'port_percent'     => 'Traffic Composition',
          'status_summary'   => "Status Summary",
          'old_status_table' => "Status Table (Old)",
          'old_status_boxes' => "Status Boxes (Old)",
          //'status_donuts'  => "Status Donuts",  // broke
          'syslog'           => 'Syslog',
          'syslog_alerts'    => 'Syslog Alerts',
          //'wmap'             => 'Network Weathermap',
          'eventlog'         => 'Eventlog']];

        $form = ['id'    => 'add_widget',
                 'type'  => 'rows',
                 'space' => '5px'];

        $form['row'][0]['dash_id']     = [
          'type'  => 'hidden',
          'name'  => 'Dashboard Name',
          'value' => $dashboard['dash_id'],
          'grid'  => 0
        ];
        $form['row'][0]['widget_type'] = [
          'type'   => 'select',
          'name'   => 'Widget',
          'width'  => '100%', //'180px',
          'grid'   => 2,
          //'value'       => $vars['widget_type'],
          'values' => $form_items['types']];
        $form['row'][0]['add']         = [
          'type'  => 'submit',
          'class' => 'btn-success',
          'name'  => 'Add Widget',
          'width' => '100%', //'180px',
          'value' => 'Add Widget',
          'icon'  => '',
          'grid'  => 1
        ];
        $form['row'][0]['dash_name']   = [
          'type'        => 'text',
          'width'       => '100%', //'180px',
          'placeholder' => 'Dashboard Name',
          'value'       => $dashboard['dash_name'],
          'grid'        => 3
        ];
        $form['row'][0]['dash_delete'] = [
          'type'    => 'submit',
          'class'   => 'btn-danger',
          'name'    => 'Delete Dashboard',
          'value'   => 'Delete Dashboard',
          'icon'    => '',
          'grid'    => 6,
          'right'   => TRUE,
          'onclick' => 'dashDelete();',
          // confirmation dialog
          'attribs' => ['data-toggle'            => 'confirm', // Enable confirmation dialog
                        'data-confirm-placement' => 'bottom',
                        'data-confirm-content'   => 'Are you sure?',
          ],
        ];
        print_form($form);

    }

    echo '<div class="row">';
    echo '<div class="grid-stack" id="grid">';
    echo '</div>';
    echo '</div>';

    ?>

    <!--- <textarea id="saved-data" cols="100" rows="20" readonly="readonly"></textarea> -->

    <script type="text/javascript">

        var dash_id = <?php echo $dashboard['dash_id']; ?>;

        function isNumber(n) {
            return !isNaN(parseFloat(n)) && isFinite(n);
        }

        $(function () {
            var options = {
                cellHeight: <?php echo $grid_cell_height; ?>,
                horizontalMargin: <?php echo $grid_h_margin; ?>,
                verticalMargin: <?php echo $grid_v_margin; ?>,
                resizable: {
                    autoHide: true,
                    handles: <?php if ($is_editing === TRUE) {
                        echo "'se, sw'";
                    } else {
                        echo "'none'";
                    } ?>
                },
                draggable: {
                    handle: '.drag-handle',
                }
            };
            $('.grid-stack').gridstack(options);

            var initial_grid = [

                <?php

                $data = [];
                $widgets = dbFetchRows("SELECT * FROM `dash_widgets` WHERE `dash_id` = ? ORDER BY `y`,`x`", [$dashboard['dash_id']]);
                foreach ($widgets as $widget) {
                    $data[] = '{' .
                              (is_numeric($widget['x']) ? '"x": ' . $widget['x'] . ',' : '') .
                              (is_numeric($widget['y']) ? '"y": ' . $widget['y'] . ',' : '') .
                              '"width": ' . $widget['width'] . ', "height": ' . $widget['height'] . ', "id": "' . $widget['widget_id'] . '"}';
                }

                echo implode(",", $data);

                ?>

            ];

            this.grid = $('.grid-stack').data('gridstack');

            var grid = $('.grid-stack').data('gridstack');

            ///////////////
            // LOAD GRID //
            ///////////////

            this.loadGrid = function () {
                this.grid.removeAll();
                var items = GridStackUI.Utils.sort(initial_grid);
                var self = this;
                _.each(items, function (node) {
                    node.autoposition = null;
                    self.drawWidget(node);
                }, this);
                return false;
            }.bind(this);

            ///////////////
            // SAVE GRID //
            ///////////////

            this.saveGrid = function () {
                this.initial_grid = _.map($('.grid-stack > .grid-stack-item:visible'), function (el) {
                    el = $(el);
                    var node = el.data('_gridstack_node');
                    return {
                        x: node.x,
                        y: node.y,
                        width: node.width,
                        height: node.height,
                        id: el.attr('data-gs-id'),
                    };
                }, this);
                $('#saved-data').val(JSON.stringify(this.initial_grid, null, '    '));

                var self = this;

                // We need to get a widget id via AJAX
                $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: {action: 'save_grid', grid: self.initial_grid},
                    cache: false,
                    success: function (response) {
                        //console.log(response);
                        //console.log(self.initial_grid);
                    }
                });

                return false;
            }.bind(this);

            this.clearGrid = function () {
                this.grid.removeAll();
                return false;
            }.bind(this);

            /////////////////////
            // DRAW THE WIDGET //
            /////////////////////

            this.drawWidget = function (node) {
                this.grid.addWidget($('<div><div id="widget-' + node.id + '" class="grid-stack-item-content"></div>' +
                    <?php if($is_editing == TRUE) { ?>
                        '<div class="hover-show" style="z-index: 1000; position: absolute; top:0px; right: 10px; padding: 2px 10px; padding-right: 0px; border-bottom-left-radius: 4px; border: 1px solid #e5e5e5; border-right: none; border-top: none; background: white;">' +
                        '  <i style="cursor: pointer; margin: 7px;" class="sprite-refresh" onclick="refreshWidget(' + node.id + ')"></i>' +
                        '  <i style="cursor: pointer; margin: 7px;" class="sprite-tools" onclick="configWidget(' + node.id + ')"></i></i>' +
                        '  <i style="cursor: no-drop; margin: 7px;" class="sprite-cancel" onclick="deleteWidget(' + node.id + ')"></i>' +
                        '  <i style="cursor: move; margin: 7px; margin-right: 20px" class="sprite-move drag-handle"></i>' +
                        '</div>' +
                    <?php } ?>
                        '</div>'),
                    node.x, node.y, node.width, node.height, node.autoposition, null, null, null, null, node.id);
            };

            ////////////////
            // ADD WIDGET //
            ////////////////

            addNewWidget = function (type, dash_id) {

                // We need to get a widget id via AJAX
                $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: jQuery.param([{name: 'action', value: 'add_widget'}, {name: "widget_type", value: type}, {name: "dash_id", value: dash_id}]),
                    cache: false,
                    success: function (response) {

                        if (isNumber(response.id)) {

                            // Create the initial widget array use autoposition and id from ajax
                            var node = {
                                width: 4,
                                height: 3,
                                autoposition: true,
                                id: response.id
                            };

                            // Draw the widget
                            self.drawWidget(node);

                            // Save grid
                            self.saveGrid();

                            // Redraw widgets
                            self.refreshAllWidgets(response.id);

                        }
                        console.log(response);
                    }
                });

                return false;
            }.bind(this);

            /////////////////////////
            // Refresh All Widgets //
            /////////////////////////

            refreshAllWidgets = function () {
                $('.grid-stack-item').each(function () {
                    refreshWidget($(this).attr('data-gs-id'));
                });
            };

            refreshAllUpdatableWidgets = function () {
                $('.grid-stack-item').each(function () {
                    // Do not update a widget if its child has the do-not-update class
                    // Do not update a widget if we're hovering over it with the mouse
                    if (!$(this).children('div').children('div').hasClass('do-not-update') && !$(this).is(':hover')) {
                        refreshWidget($(this).attr('data-gs-id'));
                    }
                });
            };


            refreshAllUpdatableImages = function () {

                // Add or replace nocache parameter on image src and then rewrite the image.
                var pt = /\&nocache=\d+/;

                $('.image-refresh').each(function () {
                    if (this.src) {
                        pt.test(this.src)
                            ? $(this).attr("src", this.src.replace(pt, "&nocache=" + Date.now()))
                            : $(this).attr("src", this.src + "&nocache=" + Date.now());
                    }
                    // FIXME - find a better way to update srcset
                    // If it already exists, we update it, if not, we insert it before the space.
                    if (this.srcset) {
                        pt.test(this.srcset)
                            ? $(this).attr("srcset", this.srcset.replace(pt, "&nocache=" + Date.now()))
                            : $(this).attr("srcset", this.srcset.replace(/\ /, "&nocache=" + Date.now() + ' '));
                    }
                });
            };

            ///////////////////////////
            // Refresh single widget //
            ///////////////////////////

            refreshWidget = function (id) {
                // This is the content div to be updated
                var div = $('#widget-' + id);

                // Generate array of parameters to send to the server.
                var params = {
                    width: div.innerWidth(),
                    height: div.innerHeight(),
                    id: id
                };

                // Run AJAX query and update div HTML with response.
                $.ajax({
                    type: "POST",
                    url: "ajax/widget.php",
                    data: jQuery.param(params),
                    cache: false,
                    success: function (response) {
                        div.html(response);
                    }
                });
            };

            deleteWidget = function (id) {
                var el = $(".grid-stack-item[data-gs-id='" + id + "']");

                var params = {
                    action: 'del_widget',
                    widget_id: id
                };
                // Run AJAX query and update div HTML with response.
                $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: jQuery.param(params),
                    cache: false,
                    success: function (response) {
                        grid.removeWidget(el);
                        console.log(response);
                    }
                });
            };

            configWidget = function (id) {

                var params = {
                    action: 'edit_widget',
                    widget_id: id
                };

                $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: jQuery.param(params),
                    cache: false,
                    success: function (response) {
                        $('#config-modal-body').html(response);
                        $('#config-modal').modal({show: true});
                    }
                });

            };

            // Captures Delete Dashboard button.
            dashDelete = function () {

                var dash_id = $('#dash_id').val();

                var params = {
                    action: 'dash_delete',
                    dash_id: dash_id
                };

                // Run AJAX query and update div HTML with response.
                var request = $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: jQuery.param(params),
                    cache: false,
                });

                request.success(function (json) {
                    if (json.status === 'ok') {
                        window.setTimeout(window.location.href = '<?php echo generate_url(['page' => 'dashboard']); ?>', 5000);
                    }
                });
            };

            /////////////
            // Actions //
            /////////////

            $('#add-new-widget').click(this.addNewWidget);
            $('#save-grid').click(this.saveGrid);
            $('#load-grid').click(this.loadGrid);
            $('#clear-grid').click(this.clearGrid);
            $('#refresh-widgets').click(this.refreshAllWidgets);


            // Captures Add Widget button.
            $("#add_widget").submit(function (event) {
                if ($('#widget_type').val()) {
                    addNewWidget($('#widget_type').val(), $('#dash_id').val());
                }
                event.preventDefault();
            });

            // Captures Delete Dashboard button.
            /* Moved to onclick for correct confirm
            $("#dash_delete").click(function () {

                var dash_id = $('#dash_id').val();

                var params = {
                    action: 'dash_delete',
                    dash_id: dash_id
                };

                // Run AJAX query and update div HTML with response.
                var request = $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: jQuery.param(params),
                    cache: false,
                });

                request.success(function (json) {
                    if (json.status === 'ok') {
                        window.setTimeout(window.location.href = '<?php //echo generate_url(array('page' => 'dashboard')); ?>', 5000);
                    } else {
                    }

                });
            });
            */

            // Captures Delete Dashboard button.
            $("#dash_name").change(function () {

                var dash_id = $('#dash_id').val();
                var dash_name = $('#dash_name').val();

                var params = {
                    action: 'dash_rename',
                    dash_id: dash_id,
                    dash_name: dash_name
                };

                // Run AJAX query and update div HTML with response.
                var request = $.ajax({
                    type: "POST",
                    url: "ajax/actions.php",
                    data: jQuery.param(params),
                    cache: false,
                });

                request.success(function (json) {
                    if (json.status === 'ok') {
                    } else {
                    }

                });
            });

            this.loadGrid();
            refreshAllWidgets();

            var self = this;

            // FIXME - only update changed widgets
            $('.grid-stack').on('change', function (event, items) {
                items.forEach(function (item) {
                    refreshWidget(item.el.attr('data-gs-id'));
                });
                //entity_popups();
                //popups_from_data();
                self.saveGrid();
            });

            setInterval(function () {
                refreshAllUpdatableWidgets();
            }, 20000);

            setInterval(function () {
                refreshAllUpdatableImages();
            }, 15000);

        });


        new ResizeSensor(jQuery('#main_container'), function () {
            //refreshAllUpdatableImages();
            //refreshAllUpdatableWidgets();
            refreshAllWidgets();
        });

    </script>

    <?php
// print_vars($widgets);
    ?>

    <style>

        .grid-stack > .grid-stack-item > .grid-stack-item-content {
            overflow-x: visible;
            overflow-y: visible;
        }

        .grid-stack > .grid-stack-item > .grid-stack-item-content:hover {
            overflow-x: visible;
            overflow-y: visible;
        }

        .grid-stack > .grid-stack-item:hover .hover-hide {
            display: none;
        }

        .grid-stack > .grid-stack-item .hover-show {
            display: none;
        }

        .grid-stack > .grid-stack-item:hover .hover-show {
            display: block;
        }

        .grid-stack > .grid-stack-item h4 {
            margin: 3px 6px;
        }

        .grid-stack tr {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Fix Z-index breaking dropdowns inside widgets*/
        .grid-stack > .grid-stack-item > .grid-stack-item-content {
            z-index: unset !important;
        }

        /*
                .box-content::-webkit-scrollbar-track
                {
                    background-color: #F5F5F5;
                }

                .box-content::-webkit-scrollbar
                {
                    width: 10px;
                    height: 10px;
                    background-color: #c5c5c5;
                }

                .box-content::-webkit-scrollbar-thumb
                {
                    border-radius: 10px;
                    background-color: #d5d5d5;
                    border: 2px solid #F5F5F5;
                }
        */


    </style>

    <!-- Modal -->
    <div class="modal fade" id="config-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Configure Widget</h4>
                </div>
                <div id="config-modal-body" class="modal-body">
                    <div class="te"></div>
                </div>
                <!--
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
                -->
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->

    <?php

    if ($_SESSION['userlevel'] > 7) {
        if (isset($vars['edit'])) {
            $url  = generate_url($vars, ['edit' => NULL]);
            $text = "Enable Editing Mode";
        } else {
            $url  = generate_url($vars, ['edit' => 'yes']);
            $text = "Disable Editing Mode";
        }

        $footer_entry     = '<li><a href="' . $url . '"><i class="sprite-sliders"></i></a></li>';
        $footer_entries[] = $footer_entry;
    }


} else {
    print_error('Dashboard does not exist!');
}

// EOF
