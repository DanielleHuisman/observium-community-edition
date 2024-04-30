/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage templates
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */
/**
 * Used keys:
 * ALERT_STATE, ALERT_URL, ALERT_MESSAGE, CONDITIONS, METRICS, DURATION,
 * ENTITY_LINK, ENTITY_DESCRIPTION, ENTITY_GRAPHS,
 * DEVICE_LINK, DEVICE_HARDWARE, DEVICE_OS, DEVICE_LOCATION, DEVICE_UPTIME
 */
<html>
<head>
  <title>Observium Alert</title>
  <style type="text/css">

  body {
    margin: 15px;
    font-family: 'Source Sans Pro', 'Helvetica Neue', Helvetica, Arial, sans-serif;
    font-size: 14px;
    line-height: 20px;
    color: #333333;
    background-color: #fff;
    font-weight: 400;
  }

  .box {
    position: relative;
    -webkit-border-radius: 0px;
    -moz-border-radius: 0px;
    border-radius: 0px;
    background: #ffffff;
    /* border-top: 3px solid #d2d6de; */
    margin-bottom: 10px;
    wwidth: 100%;
    box-shadow: 0 0 2px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.24);
  }

  .no-padding {
    padding: 0 !important;
  }

  .table {
    max-width: 600px;
    background-color: #fff;
    border-collapse: collapse;
    border-spacing: 0;
    color: #333;
  }

  .table th, .table td {
    padding: 5px 10px;
    line-height: 20px;
    text-align: left;
    vertical-align: top;
    border-top: 1px solid #f4f4f4;
  }

  .table tbody > tr.ALERT > td {
    background-color: #ffebee;
  }

  .table tbody > tr:nth-child(even) > td, .table tbody > tr:nth-child(even) > th {
    background-color: #f9f9f9;
  }

  .table .state-marker, .state-marker {
    padding: 0px;
    margin: none;
    width: 7px;
  }

  .table tbody > tr > td.state-marker {
    background-color: #004774;
  }

  .table .ALERT .state-marker {
    background-color: #b71c1c;
  }

  .header { font-size: 20px; font-weight: bold; padding: 10px;}

  .ALERT {
    color: #cc0000;
  }

  </style>
</head>
<body>
<table class="table box box-solid">
  <tbody>
    <tr class="{{ALERT_STATE}}"><td class="state-marker"></td><td style="padding: 10px;" class="header">{{ALERT_STATE}}</td><td><a style="float: right;" href="{{{ALERT_URL}}}">Modify</a></td></tr>
    <tr><td colspan=2><strong>Alert</strong></td><td class="{{ALERT_STATE}}"><strong>{{ALERT_MESSAGE}}</strong></td></tr>
    {{#ENTITY_LINK}}
    <tr><td colspan=2><strong>Entity</strong></td><td><strong>{{{ENTITY_LINK}}}</strong></td></tr>
    {{/ENTITY_LINK}}
    {{#ENTITY_DESCRIPTION}}
    <tr><td colspan=2><strong>Descr</strong></td><td>{{ENTITY_DESCRIPTION}}</td></tr>
    {{/ENTITY_DESCRIPTION}}
    {{#CONDITIONS}}
    <tr><td colspan=2><strong>Conditions</strong></td><td>{{{CONDITIONS}}}</td></tr>
    {{/CONDITIONS}}
    <tr><td colspan=2><strong>Metrics</strong></td><td>{{{METRICS}}}</td></tr>
    <tr><td colspan=2><strong>Duration</strong></td><td>{{DURATION}}</td></tr>
    <tr><td colspan=3>Device</td></tr>
    <tr><td colspan=2><strong>Device</strong></td><td><strong>{{{DEVICE_LINK}}}</strong>{{#DEVICE_DESCRIPTION}} ({{DEVICE_DESCRIPTION}}){{/DEVICE_DESCRIPTION}}</td></tr>
    <tr><td colspan=2><strong>Hardware</strong></td><td>{{DEVICE_HARDWARE}}</td></tr>
    <tr><td colspan=2><strong>Operating System</strong></td><td>{{DEVICE_OS}}</td></tr>
    <tr><td colspan=2><strong>Location</strong></td><td>{{DEVICE_LOCATION}}</td></tr>
    <tr><td colspan=2><strong>Uptime</strong></td><td>{{DEVICE_UPTIME}}</td></tr>
    {{#ENTITY_GRAPHS}}
    <tr><td colspan=3><center>{{{ENTITY_GRAPHS}}}</center></td></tr>
    {{/ENTITY_GRAPHS}}
  </tbody>
</table>

{{{FOOTER}}}

</body>
</html>
