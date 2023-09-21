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
 * https://core.telegram.org/bots/api#sendmessage
 * Used keys:
 * ALERT_STATE, ALERT_URL, ALERT_MESSAGE, DURATION,
 * ENTITY_NAME, ENTITY_DESCRIPTION,
 * DEVICE_HOSTNAME, DEVICE_UPTIME
 */
*{{ALERT_STATE}} : {{ALERT_MESSAGE}}*
_{{ALERT_TIMESTAMP}}_

{{#ENTITY_NAME}}
*Entity:* <{{ENTITY_URL}}|{{ENTITY_NAME}}>
{{/ENTITY_NAME}}
{{#ENTITY_DESCRIPTION}}
*Description:* {{ENTITY_DESCRIPTION}}
{{/ENTITY_DESCRIPTION}}
// For syslog:
{{#SYSLOG_MESSAGE}}
*Message:* {{SYSLOG_MESSAGE}}
{{/SYSLOG_MESSAGE}}
// Common alerts:
{{^SYSLOG_MESSAGE}}
{{#CONDITIONS}}
*Conditions:* {{{CONDITIONS}}}
{{/CONDITIONS}}
*Metrics:* {{{METRICS}}}
{{/SYSLOG_MESSAGE}}
*Duration:* {{DURATION}}

*Device:* <{{DEVICE_URL}}|{{DEVICE_HOSTNAME}}{{#DEVICE_DESCRIPTION}} ({{DEVICE_DESCRIPTION}}){{/DEVICE_DESCRIPTION}}>
*Location:* {{DEVICE_LOCATION}}
*Device Uptime:* {{DEVICE_UPTIME}}

*<{{ALERT_URL}}|More information>*
