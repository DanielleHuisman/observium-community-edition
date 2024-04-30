<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage alerting
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

$message_keys = array_keys($message_tags);

// Export all tags for external program usage
foreach ($message_keys as $key) {
    putenv("OBSERVIUM_$key=" . $message_tags[$key]);
}

// Execute given script
external_exec(escapeshellcmd($endpoint['script']), $exec_status);

// If script's exit code is 0, success. Otherwise, we mark it as failed.
if ($exec_status['exitcode'] === 0) {
    $notify_status['success'] = TRUE;
} else {
    $notify_status['success'] = FALSE;
}

// Clean out all set environment variables we set before execution
foreach ($message_keys as $key) {
    putenv("OBSERVIUM_$key");
}

unset($exec_status);

// EOF
