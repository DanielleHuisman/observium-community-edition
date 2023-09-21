/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage templates
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */
/**
 * HTML formatting style: https://core.telegram.org/bots/api#html-style
 * Supports only numeric html entities (&nbsp; == &#160;)
 */
{{#ENTITY_LINK}}
<b>Entity:</b> <b>{{{ENTITY_LINK}}}</b>
{{/ENTITY_LINK}}

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
<b>Duration:</b> {{DURATION}}

<b>Device:</b> <b>{{{DEVICE_LINK}}}</b>{{#DEVICE_DESCRIPTION}} ({{DEVICE_DESCRIPTION}}){{/DEVICE_DESCRIPTION}}

<b>Location:</b> {{DEVICE_LOCATION}}
<b>Device Uptime:</b> {{DEVICE_UPTIME}}
