/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage templates
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */
/**
 * https://core.telegram.org/bots/api#sendmessage
 * Used keys:
 * ALERT_STATUS, ALERT_TIMESTAMP, ALERT_STATE, ALERT_URL, ALERT_MESSAGE,
 * SYSLOG_MESSAGE, CONDITIONS, METRICS, DURATION,
 * ENTITY_NAME, ENTITY_DESCRIPTION,
 * DEVICE_HOSTNAME, DEVICE_UPTIME
 */
// Keep more actual information in first 2 lines, for 
{{#ALERT_EMOJI}}{{{ALERT_EMOJI}}}&#160;{{/ALERT_EMOJI}}<strong><a href="{{{ALERT_URL}}}">{{ALERT_MESSAGE}}</a></strong>
//<b>{{ALERT_STATE}} :</b> {{ALERT_MESSAGE}} (<a href="{{{ALERT_URL}}}">Modify</a>)
{{#ENTITY_LINK}}

<b>Entity:</b> <strong>{{{ENTITY_LINK}}}</strong>
{{/ENTITY_LINK}}
<em>{{ALERT_TIMESTAMP}}</em>

{{#ENTITY_DESCRIPTION}}
<b>Description:</b> {{ENTITY_DESCRIPTION}}
{{/ENTITY_DESCRIPTION}}
// For syslog:
{{#SYSLOG_MESSAGE}}
<b>Message:</b> {{SYSLOG_MESSAGE}}
{{/SYSLOG_MESSAGE}}
// Common alerts:
{{^SYSLOG_MESSAGE}}
{{#CONDITIONS}}
<b>Conditions:</b> {{{CONDITIONS}}}
{{/CONDITIONS}}
<b>Metrics:</b> {{{METRICS}}}
{{/SYSLOG_MESSAGE}}
//<b>Duration:</b> {{DURATION}}

<b>Device:</b> <strong>{{{DEVICE_LINK}}}</strong>{{#DEVICE_DESCRIPTION}} ({{DEVICE_DESCRIPTION}}){{/DEVICE_DESCRIPTION}}

<b>Location:</b> {{DEVICE_LOCATION}}
<b>Device Uptime:</b> {{DEVICE_UPTIME}}
