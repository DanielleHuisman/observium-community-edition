/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage templates
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */
/**
 * Used keys:
 * ALERT_STATE, ALERT_URL, ALERT_MESSAGE, DURATION,
 * ENTITY_NAME, ENTITY_DESCRIPTION,
 * DEVICE_HOSTNAME, DEVICE_UPTIME
 */
{{ALERT_TIMESTAMP}}
{{ALERT_STATE}} : {{ALERT_MESSAGE}}

Device: {{DEVICE_HOSTNAME}}
{{#ENTITY_NAME}}
Entity: {{ENTITY_NAME}}
{{/ENTITY_NAME}}
{{#ENTITY_DESCRIPTION}}
Description: {{ENTITY_DESCRIPTION}}
{{/ENTITY_DESCRIPTION}}
// For syslog:
{{#SYSLOG_MESSAGE}}
Message: {{SYSLOG_MESSAGE}}
{{/SYSLOG_MESSAGE}}
//Duration: {{DURATION}}

Device Uptime: {{DEVICE_UPTIME}}

More information: {{ALERT_URL}}
