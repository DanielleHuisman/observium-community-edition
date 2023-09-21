<?php

register_html_resource('js', 'd3.v3.min.js');
register_html_resource('js', 'donut-chart.js');

?>
<div class="col-md-12" style="padding: 5px 20px 20px 20px;">
    <table style="width: 100%; background: none;">
        <tr style="align: center;">
            <td>
                <div class="donut-chart" id="devices_chart"></div>
            </td>
            <td>
                <div class="donut-chart" id="ports_chart"></div>
            </td>
            <td>
                <div class="donut-chart" id="sensors_chart"></div>
            </td>
            <td>
                <div class="donut-chart" id="status_chart"></div>
            </td>
            <td>
                <div class="donut-chart" id="alerts_chart"></div>
            </td>
        </tr>
    </table>
</div>

<script>
    // Object.create() polyfill
    if (typeof Object.create !== 'function') {
        Object.create = function (obj) {
            function F() {
            }

            F.prototype = obj;
            return new F();
        };
    }

    // Select containers
    var chartContainer_devices = document.querySelector('#devices_chart');
    var chartContainer_ports = document.querySelector('#ports_chart');
    var chartContainer_sensors = document.querySelector('#sensors_chart');
    var chartContainer_status = document.querySelector('#status_chart');
    var chartContainer_alerts = document.querySelector('#alerts_chart');

    // Data
    var chartData_ports = {
        total: <?php echo $cache['ports']['stat']['count']; ?>,
        wedges: [
            {id: 'up', color: '#A0D468', value: <?php echo $cache['ports']['stat']['up']; ?> },
            {id: 'ignored', color: '#4FC1E9', value: <?php echo $cache['ports']['stat']['ignored']; ?> },
            {id: 'dev_ignored', color: '#AC92EC', value: <?php echo $cache['ports']['stat']['device_ignored']; ?> },
            {id: 'shutdown', color: '#dddddd', value: <?php echo $cache['ports']['stat']['shutdown']; ?> },
            {id: 'down', color: '#ED5565', value: <?php echo $cache['ports']['stat']['down']; ?> },
        ]
    };

    var chartData_devices = {
        total: <?php echo $cache['devices']['stat']['count']; ?>,
        wedges: [
            {id: 'up', color: '#A0D468', value: <?php echo $cache['devices']['stat']['up']; ?> },
            {id: 'ignored', color: '#4FC1E9', value: <?php echo $cache['devices']['stat']['ignored']; ?> },
            {id: 'disabled', color: '#e5e5e5', value: <?php echo $cache['devices']['stat']['disabled']; ?> },
            {id: 'down', color: '#ED5565', value: <?php echo $cache['devices']['stat']['down']; ?> },
        ]
    };

    var chartData_sensors = {
        total: <?php echo $cache['sensors']['stat']['count']; ?>,
        wedges: [
            {id: 'ok', color: '#A0D468', value: <?php echo $cache['sensors']['stat']['ok']; ?> },
            {id: 'ignored', color: '#4FC1E9', value: <?php echo $cache['sensors']['stat']['ignored']; ?> },
            {id: 'disabled', color: '#e5e5e5', value: <?php echo $cache['sensors']['stat']['disabled']; ?> },
            {id: 'alert', color: '#ED5565', value: <?php echo $cache['sensors']['stat']['alert']; ?> },
        ]
    };

    var chartData_status = {
        total: <?php echo $cache['statuses']['stat']['count']; ?>,
        wedges: [
            {id: 'ok', color: '#A0D468', value: <?php echo $cache['statuses']['stat']['ok']; ?> },
            {id: 'ignored', color: '#4FC1E9', value: <?php echo $cache['statuses']['stat']['ignored']; ?> },
            {id: 'disabled', color: '#e5e5e5', value: <?php echo $cache['statuses']['stat']['disabled']; ?> },
            {id: 'alert', color: '#ED5565', value: <?php echo $cache['statuses']['stat']['alert']; ?> },
        ]
    };

    var chartData_alerts = {
        total: <?php echo $cache['alert_entries']['count']; ?>,
        wedges: [
            {id: 'up', color: '#A0D468', value: <?php echo $cache['alert_entries']['up']; ?> },
            {id: 'down', color: '#4FC1E9', value: <?php echo $cache['alert_entries']['down']; ?> },
            {id: 'delay', color: '#e5e5e5', value: <?php echo $cache['alert_entries']['delay']; ?> },
            {id: 'suppress', color: '#ED5565', value: <?php echo $cache['alert_entries']['suppress']; ?> },
            {id: 'other', color: '#e5e5e5', value: <?php echo $cache['alert_entries']['unknown']; ?> },
        ]
    };


    // Create new chart objects
    var Chart_devices = Object.create(DonutChart);
    var Chart_ports = Object.create(DonutChart);
    var Chart_sensors = Object.create(DonutChart);
    var Chart_status = Object.create(DonutChart);
    var Chart_alerts = Object.create(DonutChart);


    Chart_devices.init({
        container: chartContainer_devices,
        data: chartData_devices,
        label: 'Devices'
    });

    Chart_ports.init({
        container: chartContainer_ports,
        data: chartData_ports,
        label: 'Ports'
    });

    Chart_sensors.init({
        container: chartContainer_sensors,
        data: chartData_sensors,
        label: 'Sensors'
    });

    Chart_status.init({
        container: chartContainer_status,
        data: chartData_status,
        label: 'Status'
    });

    Chart_alerts.init({
        container: chartContainer_alerts,
        data: chartData_alerts,
        label: 'Alerts'
    });


</script>

