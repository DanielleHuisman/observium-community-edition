<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     alerting
 * @copyright  (C) Adam Armstrong
 *
 */

// CLEANME. Who still use it?

$message = $message_tags['TITLE'] . ' [' . $message_tags['ALERT_URL'] . ']' . PHP_EOL;
$message .= str_replace("             ", "", $message_tags['METRICS']);

use Fabiang\Xmpp\Options;
use Fabiang\Xmpp\Client;
use Fabiang\Xmpp\Protocol\Message;

if (isset($endpoint['server'])) {
    // Set server hostname if specified by user
    $hostname = $endpoint['server'];
} elseif (str_contains($endpoint['username'], '@')) {
    [, $xmppdomain] = explode('@', $endpoint['username'], 2);

    $resolver = new Net_DNS2_Resolver();

    $maxprio = -1;

    // Find and use highest priority server only. Could be improved to cycle if there are multiple?
    try {
        $response = $resolver->query("_xmpp-client._tcp.$xmppdomain", 'SRV');
        if ($response) {
            foreach ($response->answer as $answer) {
                if ($answer->priority > $maxprio) {
                    $hostname = $answer->target;
                }
            }
        }
    } catch (Exception $e) {
        print_debug("Error while resolving: " . $e->getMessage());
    } // Continue when error resolving
}

if (empty($hostname)) {
    // reason: Could not determine server hostname!
    $notify_status['success'] = FALSE;
    unset($message);

    return;
}

// Default to port to 5222 unless specified by endpoint data
$port = $endpoint['port'] ?: 5222;

[ $username, $xmppdomain ] = explode('@', $endpoint['username']); // Username is only the part before @
$password = $endpoint['password'];

$options = new Options("tcp://$hostname:$port");
$options->setUsername($username);
$options->setPassword($password);

[ $rusername, $rxmppdomain ] = explode('@', $endpoint['recipient']);
if ($rxmppdomain != '') {
    $options->setTo($rxmppdomain);
} // Set destination domain to the recipient's part after the @

$client = new Client($options);

try {
    $client->connect();

    $xmessage = new Message;
    $xmessage->setMessage($message);
    $xmessage->setTo($endpoint['recipient']);

    $client->send($xmessage);
    $client->disconnect();

    $notify_status['success'] = TRUE;
} catch (Exception $e) {
    // reason:  $e->getMessage()
    $notify_status['success'] = FALSE;
}

unset($message);

// EOF
