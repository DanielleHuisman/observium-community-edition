<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage alerting
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Export all tags for external program usage
foreach (array_keys($message_tags) as $key) {
    putenv("OBSERVIUM_$key=" . $message_tags[$key]);
}

// Execute given script
external_exec($endpoint['script'], $exec_status);

// If script's exit code is 0, success. Otherwise we mark it as failed.
if ($exec_status['exitcode'] === 0) {
    $notify_status['success'] = TRUE;
} else {
    $notify_status['success'] = FALSE;
}

// Clean out all set environment variable we set before execution
foreach (array_keys($message_tags) as $key) {
    putenv("OBSERVIUM_$key");
}

unset($message, $output, $exitcode);

// EOF
