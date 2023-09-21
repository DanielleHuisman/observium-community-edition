<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$table_defs['NS-ROOT-MIB']['nsHttpStatsGroup'] = [
  'table'     => 'nsHttpStatsGroup',
  'numeric'   => '.1.3.6.1.4.1.5951.4.1.1.48',
  'mib'       => 'NS-ROOT-MIB',
  'mib_dir'   => 'citrix',
  'file'      => 'nsHttpStatsGroup.rrd',
  'descr'     => 'Netscaler HTTP Statistics',
  'graphs'    => ['nsHttpRequests', 'nsHttpReqResp', 'nsHttpBytes', 'nsHttpSPDY'],
  'ds_rename' => ['http' => ''],
  'oids'      => [
    'httpTotGets'                 => ['numeric' => '45', 'descr' => 'Total number of HTTP requests received with the GET method.', 'ds_min' => '0'],
    'httpTotPosts'                => ['numeric' => '46', 'descr' => 'Total number of HTTP requests received with the POST method.', 'ds_min' => '0'],
    'httpTotOthers'               => ['numeric' => '47', 'descr' => 'Total number of HTTP requests received with methods other than GET and POST. Some of the other well-defined HTTP methods are HEAD, PUT, DELETE, OPTIONS, and TRACE. User-defined methods are also allowed.', 'ds_min' => '0'],
    'httpTotRxRequestBytes'       => ['numeric' => '48', 'descr' => 'Total number of bytes of HTTP request data received.', 'ds_min' => '0'],
    'httpTotRxResponseBytes'      => ['numeric' => '49', 'descr' => 'Total number of bytes of HTTP response data received.', 'ds_min' => '0'],
    'httpTotTxRequestBytes'       => ['numeric' => '50', 'descr' => 'Total number of bytes of HTTP request data transmitted.', 'ds_min' => '0'],
    'httpTotTxResponseBytes'      => ['numeric' => '51', 'descr' => 'Total number of bytes of HTTP response data transmitted.', 'ds_min' => '0'],
    'httpTot10Requests'           => ['numeric' => '52', 'descr' => 'Total number of HTTP/1.0 requests received. '],
    'httpTotResponses'            => ['numeric' => '53', 'descr' => 'Total number of HTTP responses sent.', 'ds_min' => '0'],
    'httpTot10Responses'          => ['numeric' => '54', 'descr' => 'Total number of HTTP/1.0 responses sent.', 'ds_min' => '0'],
    'httpTotClenResponses'        => ['numeric' => '55', 'descr' => 'Total number of HTTP responses sent in which the Content-length field of the HTTP header has been set. Content-length specifies the length of the content, in bytes, in the associated HTTP body.', 'ds_min' => '0'],
    'httpTotChunkedResponses'     => ['numeric' => '56', 'descr' => 'Total number of HTTP responses sent in which the Transfer-Encoding field of the HTTP header has been set to chunked. This setting is used when the server wants to start sending the response before knowing its total length. The server breaks the response into chunks and sends them in sequence, inserting the length of each chunk before the actual data. The message ends with a chunk of size zero.', 'ds_min' => '0'],
    'httpErrIncompleteRequests'   => ['numeric' => '57', 'descr' => 'Total number of HTTP requests received in which the header spans more than one packet.', 'ds_min' => '0'],
    'httpErrIncompleteResponses'  => ['numeric' => '58', 'descr' => 'Total number of HTTP responses received in which the header spans more than one packet.', 'ds_min' => '0'],
    'httpErrIncompleteHeaders'    => ['numeric' => '60', 'descr' => 'Total number of HTTP requests and responses received in which the HTTP header spans more than one packet.', 'ds_min' => '0'],
    'httpErrServerBusy'           => ['numeric' => '61', 'descr' => 'Total number of HTTP error responses received. Some of the error responses are: 500 Internal Server Error 501 Not Implemented 502 Bad Gateway 503 Service Unavailable 504 Gateway Timeout 505 HTTP Version Not Supported.', 'ds_min' => '0'],
    'httpTotChunkedRequests'      => ['numeric' => '62', 'descr' => 'Total number of HTTP requests in which the Transfer-Encoding field of the HTTP header has been set to chunked. '],
    'httpTotClenRequests'         => ['numeric' => '63', 'descr' => 'Total number of HTTP requests in which the Content-length field of the HTTP header has been set. Content-length specifies the length of the content, in bytes, in the associated HTTP body.', 'ds_min' => '0'],
    'httpErrLargeContent'         => ['numeric' => '64', 'descr' => 'Total number of requests and responses received with large body.', 'ds_min' => '0'],
    'httpErrLargeCtlen'           => ['numeric' => '65', 'descr' => 'Total number of requests received with large content, in which the Content-length field of the HTTP header has been set. Content-length specifies the length of the content, in bytes, in the associated HTTP body.', 'ds_min' => '0'],
    'httpErrLargeChunk'           => ['numeric' => '66', 'descr' => 'Total number of requests received with large chunk size, in which the Transfer-Encoding field of the HTTP header has been set to chunked.', 'ds_min' => '0'],
    'httpTotRequests'             => ['numeric' => '67', 'descr' => 'Total number of HTTP requests received.', 'ds_min' => '0'],
    'httpTot11Requests'           => ['numeric' => '68', 'descr' => 'Total number of HTTP/1.1 requests received.', 'ds_min' => '0'],
    'httpTot11Responses'          => ['numeric' => '69', 'descr' => 'Total number of HTTP/1.1 responses sent.', 'ds_min' => '0'],
    'httpTotNoClenChunkResponses' => ['numeric' => '70', 'descr' => 'Total number of FIN-terminated responses sent. In FIN-terminated responses, the server finishes sending the data and closes the connection.', 'ds_min' => '0'],
    'httpErrNoreuseMultipart'     => ['numeric' => '71', 'descr' => 'Total number of HTTP multi-part responses sent. In multi-part responses, one or more entities are encapsulated within the body of a single message.', 'ds_min' => '0'],
    // 'spdy2TotStreams'             => array('numeric' => '72', 'descr' => 'Total number of requests received over SPDY.'),
    'spdyTotStreams'              => ['numeric' => '73', 'descr' => 'Total number of requests received over SPDYv2 and SPDYv3'],
    'spdyv2TotStreams'            => ['numeric' => '74', 'descr' => 'Total number of requests received over SPDYv2'],
    'spdyv3TotStreams'            => ['numeric' => '75', 'descr' => 'Total number of requests received over SPDYv3.'],
    'httpTotRequestsRate'         => ['numeric' => '76', 'descr' => 'Rate at which HTTP Requests are received in the system per second.'],
    'httpTotResponsesRate'        => ['numeric' => '77', 'descr' => 'Rate at which HTTP Responses are received in the system per second.']
  ]
];

// EOF
