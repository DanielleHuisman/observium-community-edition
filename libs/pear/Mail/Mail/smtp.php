<?php
/**
 * SMTP implementation of the PEAR Mail interface. Requires the Net_SMTP class.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2010-2021, Chuck Hagenbuch & Jon Parise
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Jon Parise <jon@php.net>
 * @author      Chuck Hagenbuch <chuck@horde.org>
 * @copyright   2010-2021 Chuck Hagenbuch
 * @license     http://opensource.org/licenses/BSD-3-Clause New BSD License
 * @version     CVS: $Id$
 * @link        http://pear.php.net/package/Mail/
 */

/** Error: Failed to create a Net_SMTP object */
define('PEAR_MAIL_SMTP_ERROR_CREATE', 10000);

/** Error: Failed to connect to SMTP server */
define('PEAR_MAIL_SMTP_ERROR_CONNECT', 10001);

/** Error: SMTP authentication failure */
define('PEAR_MAIL_SMTP_ERROR_AUTH', 10002);

/** Error: No From: address has been provided */
define('PEAR_MAIL_SMTP_ERROR_FROM', 10003);

/** Error: Failed to set sender */
define('PEAR_MAIL_SMTP_ERROR_SENDER', 10004);

/** Error: Failed to add recipient */
define('PEAR_MAIL_SMTP_ERROR_RECIPIENT', 10005);

/** Error: Failed to send data */
define('PEAR_MAIL_SMTP_ERROR_DATA', 10006);

/**
 * SMTP implementation of the PEAR Mail interface. Requires the Net_SMTP class.
 * @access public
 * @package Mail
 * @version $Revision$
 */
class Mail_smtp extends Mail {

    /**
     * SMTP connection object.
     *
     * @var object
     * @access private
     */
    var $_smtp = null;

    /**
     * The list of service extension parameters to pass to the Net_SMTP
     * mailFrom() command.
     *
     * @var array
     */
    var $_extparams = array();

    /**
     * The SMTP host to connect to.
     *
     * @var string
     */
    var $host = 'localhost';

    /**
     * The port the SMTP server is on.
     *
     * @var integer
     */
    var $port = 25;

    /**
     * Should STARTTLS connection be used?
     *
     * This value may be set to true or false.
     *
     * If the value is set to true, the Net_SMTP package will attempt to use
     * a STARTTLS encrypted connection.
     *
     * If the value is set to false, the Net_SMTP package will avoid
     * a STARTTLS encrypted connection.
     *
     * NULL indicates only STARTTLS if $auth is set.
     *
     * PEAR/Net_SMTP >= 1.10.0 required.
     *
     * @var boolean
     */
    var $starttls = null;

    /**
     * Should SMTP authentication be used?
     *
     * This value may be set to true, false or the name of a specific
     * authentication method.
     *
     * If the value is set to true, the Net_SMTP package will attempt to use
     * the best authentication method advertised by the remote SMTP server.
     *
     * @var mixed
     */
    var $auth = false;

    /**
     * The username to use if the SMTP server requires authentication.
     *
     * @var string
     */
    var $username = '';

    /**
     * The password to use if the SMTP server requires authentication.
     *
     * @var string
     */
    var $password = '';

    /**
     * Hostname or domain that will be sent to the remote SMTP server in the
     * HELO / EHLO message.
     *
     * @var string
     */
    var $localhost = 'localhost';

    /**
     * SMTP connection timeout value.  NULL indicates no timeout.
     *
     * @var integer
     */
    var $timeout = null;

    /**
     * Turn on Net_SMTP debugging?
     *
     * @var boolean $debug
     */
    var $debug = false;

    /**
     * Set debug_handler on Net_SMTP
     *
     * @var callable $debug_handler
     */
    var $debug_handler = null;

    /**
     * we need the greeting; from it we can extract the authorative name of the mail
     * server we've really connected to. ideal if we're connecting to a round-robin
     * of relay servers and need to track which exact one took the email
     *
     * @var string
     */
    var $greeting = null;

    /**
     * Indicates whether or not the SMTP connection should persist over
     * multiple calls to the send() method.
     *
     * @var boolean
     */
    var $persist = false;

    /**
     * Use SMTP command pipelining (specified in RFC 2920) if the SMTP server
     * supports it. This speeds up delivery over high-latency connections. By
     * default, use the default value supplied by Net_SMTP.
     *
     * @var boolean
     */
    var $pipelining;

    /**
     * The list of socket options
     *
     * @var array
     */
    var $socket_options = array();

    /**
     * SMTP response message
     *
     * @var string
     * @since 1.6.0
     */
    var $response = null;

    /**
     * If the message ends up in the queue, on the recipient server,
     * the response will be saved here.
     * Some successfully delivered emails will include a “queued”
     * notation in the SMTP response, such as "250 OK; queued as 12345".
     * This indicates that the email was delivered to the recipient
     * as expected, but may require additional processing before it
     * lands in the recipient’s inbox.
     *
     * @var string
     */
    var $queued_as = null;

    /**
     * Constructor.
     *
     * Instantiates a new Mail_smtp:: object based on the parameters
     * passed in. It looks for the following parameters:
     *     host            The server to connect to. Defaults to localhost.
     *     port            The port to connect to. Defaults to 25.
     *     auth            SMTP authentication. Defaults to none.
     *     starttls        Should STARTTLS connection be used? No default. PEAR/Net_SMTP >= 1.10.0 required.
     *     username        The username to use for SMTP auth. No default.
     *     password        The password to use for SMTP auth. No default.
     *     localhost       The local hostname / domain. Defaults to localhost.
     *     timeout         The SMTP connection timeout. Defaults to none.
     *     verp            Whether to use VERP or not. Defaults to false.
     *                     DEPRECATED as of 1.2.0 (use setMailParams()).
     *     debug           Activate SMTP debug mode? Defaults to false.
     *     debug_handler   Set SMTP debug handler function. Defaults to null.
     *     persist         Should the SMTP connection persist?
     *     pipelining      Use SMTP command pipelining
     *     socket_options  Socket stream_context_create() options.
     *
     * If a parameter is present in the $params array, it replaces the
     * default.
     *
     * @param array Hash containing any parameters different from the
     *              defaults.
     */
    public function __construct($params)
    {
        if (isset($params['host'])) $this->host = $params['host'];
        if (isset($params['port'])) $this->port = $params['port'];
        if (isset($params['auth'])) $this->auth = $params['auth'];
        if (isset($params['starttls'])) $this->starttls = $params['starttls'];
        if (isset($params['username'])) $this->username = $params['username'];
        if (isset($params['password'])) $this->password = $params['password'];
        if (isset($params['localhost'])) $this->localhost = $params['localhost'];
        if (isset($params['timeout'])) $this->timeout = $params['timeout'];
        if (isset($params['debug'])) $this->debug = (bool)$params['debug'];
        if (isset($params['debug_handler'])) $this->debug_handler = $params['debug_handler'];
        if (isset($params['persist'])) $this->persist = (bool)$params['persist'];
        if (isset($params['pipelining'])) $this->pipelining = (bool)$params['pipelining'];
        if (isset($params['socket_options'])) $this->socket_options = $params['socket_options'];
        // Deprecated options
        if (isset($params['verp'])) {
            $this->addServiceExtensionParameter('XVERP', is_bool($params['verp']) ? null : $params['verp']);
        }
    }

    /**
     * Destructor implementation to ensure that we disconnect from any
     * potentially-alive persistent SMTP connections.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Implements Mail::send() function using SMTP.
     *
     * @param mixed $recipients Either a comma-seperated list of recipients
     *              (RFC822 compliant), or an array of recipients,
     *              each RFC822 valid. This may contain recipients not
     *              specified in the headers, for Bcc:, resending
     *              messages, etc.
     *
     * @param array $headers The array of headers to send with the mail, in an
     *              associative array, where the array key is the
     *              header name (e.g., 'Subject'), and the array value
     *              is the header value (e.g., 'test'). The header
     *              produced from those values would be 'Subject:
     *              test'.
     *
     * @param string $body The full text of the message body, including any
     *               MIME parts, etc.
     *
     * @return mixed Returns true on success, or a PEAR_Error
     *               containing a descriptive error message on
     *               failure.
     */
    public function send($recipients, $headers, $body)
    {
        $result = $this->send_or_fail($recipients, $headers, $body);

        /* If persistent connections are disabled, destroy our SMTP object. */
        if ($this->persist === false) {
            $this->disconnect();
        }

        return $result;
    }

    protected function send_or_fail($recipients, $headers, $body)
    {
        /* If we don't already have an SMTP object, create one. */
        $result = $this->getSMTPObject();
        if (PEAR::isError($result)) {
            return $result;
        }

        if (!is_array($headers)) {
            return PEAR::raiseError('$headers must be an array');
        }

        $this->_sanitizeHeaders($headers);

        $headerElements = $this->prepareHeaders($headers);
        if (is_a($headerElements, 'PEAR_Error')) {
            $this->_smtp->rset();
            return $headerElements;
        }
        list($from, $textHeaders) = $headerElements;

        /* Since few MTAs are going to allow this header to be forged
         * unless it's in the MAIL FROM: exchange, we'll use
         * Return-Path instead of From: if it's set. */
        if (!empty($headers['Return-Path'])) {
            $from = $headers['Return-Path'];
        }

        if (!isset($from)) {
            $this->_smtp->rset();
            return PEAR::raiseError('No From: address has been provided',
                                    PEAR_MAIL_SMTP_ERROR_FROM);
        }

        $params = '';
        if (!empty($this->_extparams)) {
            foreach ($this->_extparams as $key => $val) {
                $params .= ' ' . $key . (is_null($val) ? '' : '=' . $val);
            }
        }
        if (PEAR::isError($res = $this->_smtp->mailFrom($from, ltrim($params)))) {
            $error = $this->_error("Failed to set sender: $from", $res);
            $this->_smtp->rset();
            return PEAR::raiseError($error, PEAR_MAIL_SMTP_ERROR_SENDER);
        }

        $recipients = $this->parseRecipients($recipients);
        if (is_a($recipients, 'PEAR_Error')) {
            $this->_smtp->rset();
            return $recipients;
        }

        foreach ($recipients as $recipient) {
            $res = $this->_smtp->rcptTo($recipient);
            if (is_a($res, 'PEAR_Error')) {
                $error = $this->_error("Failed to add recipient: $recipient", $res);
                $this->_smtp->rset();
                return PEAR::raiseError($error, PEAR_MAIL_SMTP_ERROR_RECIPIENT);
            }
        }

        /* Send the message's headers and the body as SMTP data. */
        $res = $this->_smtp->data($body, $textHeaders);

        list($code, $args) = $this->_smtp->getResponse();

        $this->response = $code . ' ' . $args;

        if (preg_match("/ queued as (.*)/", $args, $queued)) {
            $this->queued_as = $queued[1];
        }

        /* we need the greeting; from it we can extract the authorative name of the mail server we've really connected to.
         * ideal if we're connecting to a round-robin of relay servers and need to track which exact one took the email */
        $this->greeting = $this->_smtp->getGreeting();

        if (is_a($res, 'PEAR_Error')) {
            $error = $this->_error('Failed to send data', $res);
            $this->_smtp->rset();
            return PEAR::raiseError($error, PEAR_MAIL_SMTP_ERROR_DATA);
        }

        return true;
    }

    /**
     * Connect to the SMTP server by instantiating a Net_SMTP object.
     *
     * @return mixed Returns a reference to the Net_SMTP object on success, or
     *               a PEAR_Error containing a descriptive error message on
     *               failure.
     *
     * @since  1.2.0
     */
    public function getSMTPObject()
    {
        if (is_object($this->_smtp) !== false) {
            return $this->_smtp;
        }

        include_once 'Net/SMTP.php';
        $this->_smtp = new Net_SMTP($this->host,
                                     $this->port,
                                     $this->localhost,
                                     $this->pipelining,
                                     0,
                                     $this->socket_options);

        /* If we still don't have an SMTP object at this point, fail. */
        if (is_object($this->_smtp) === false) {
            return PEAR::raiseError('Failed to create a Net_SMTP object',
                                    PEAR_MAIL_SMTP_ERROR_CREATE);
        }

        /* Configure the SMTP connection. */
        if ($this->debug) {
            $this->_smtp->setDebug(true, $this->debug_handler);
        }

        /* Attempt to connect to the configured SMTP server. */
        if (PEAR::isError($res = $this->_smtp->connect($this->timeout))) {
            $error = $this->_error('Failed to connect to ' .
                                   $this->host . ':' . $this->port,
                                   $res);
            return PEAR::raiseError($error, PEAR_MAIL_SMTP_ERROR_CONNECT);
        }

        /* Attempt to authenticate if authentication has been enabled. */
        if ($this->auth) {
            $method = is_string($this->auth) ? $this->auth : '';

            $tls = $this->starttls === false ? false : true;

            if (PEAR::isError($res = $this->_smtp->auth($this->username,
                                                        $this->password,
                                                        $method,
                                                        $tls))) {
                $error = $this->_error("$method authentication failure",
                                       $res);
                $this->_smtp->rset();
                return PEAR::raiseError($error, PEAR_MAIL_SMTP_ERROR_AUTH);
            }
        }

        /* Attempt to establish a TLS encrypted connection. PEAR/Net_SMTP >= 1.10.0 required. */
        if ($this->starttls && !$this->auth) {
            $starttls = $this->_smtp->starttls();
            if (PEAR::isError($starttls)) {
                return PEAR::raiseError($starttls);
            } elseif ($starttls === false) {
                return PEAR::raiseError('STARTTLS failed');
            }
        }

        return $this->_smtp;
    }

    /**
     * Add parameter associated with a SMTP service extension.
     *
     * @param string Extension keyword.
     * @param string Any value the keyword needs.
     *
     * @since 1.2.0
     */
    public function addServiceExtensionParameter($keyword, $value = null)
    {
        $this->_extparams[$keyword] = $value;
    }

    /**
     * Disconnect and destroy the current SMTP connection.
     *
     * @return boolean True if the SMTP connection no longer exists.
     *
     * @since  1.1.9
     */
    public function disconnect()
    {
        /* If we have an SMTP object, disconnect and destroy it. */
        if (is_object($this->_smtp) && $this->_smtp->disconnect()) {
            $this->_smtp = null;
        }

        /* We are disconnected if we no longer have an SMTP object. */
        return ($this->_smtp === null);
    }

    /**
     * Returns the SMTP response message after sending.
     *
     * @return string SMTP response message or NULL.
     *
     * @since  1.6.0
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Returns the SMTP response message if includes "queue" after sending.
     *
     * @return string SMTP queue message or NULL.
     *
     * @since  1.6.0
     */
    public function getQueuedAs()
    {
        return $this->queued_as;
    }

    /**
     * Build a standardized string describing the current SMTP error.
     *
     * @param string $text  Custom string describing the error context.
     * @param object $error Reference to the current PEAR_Error object.
     *
     * @return string       A string describing the current SMTP error.
     *
     * @since  1.1.7
     */
    protected function _error($text, $error)
    {
        /* Split the SMTP response into a code and a response string. */
        list($code, $response) = $this->_smtp->getResponse();

        /* Build our standardized error string. */
        return $text
            . ' [SMTP: ' . $error->getMessage()
            . " (code: $code, response: $response)]";
    }

}
