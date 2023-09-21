<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// We can draw more graphs from these now.
$table_defs['BLUECOAT-SG-PROXY-MIB']['sgProxyHttpPerf'] = [
  'mib'       => 'BLUECOAT-SG-PROXY-MIB',
  'mib_dir'   => 'bluecoat',
  'table'     => 'sgProxyHttpPerf', // Group sgProxyHttpPerf contains sgProxyHttpClient/sgProxyHttpServer/sgProxyHttpConnections
  'ds_rename' => ['sgProxyHttp' => '', 'Connections' => 'Conn', 'Idle' => 'Id', 'Active' => 'Ac'],
  'graphs'    => ['bluecoat_http_client', 'bluecoat_http_server', 'bluecoat_cache', 'bluecoat_server'],
  'oids'      => [

      //sgProxyHttpClient
      'sgProxyHttpClientRequests'          => ['descr' => 'The number of HTTP requests received from clients.', 'ds_min' => '0'], //203536100
      'sgProxyHttpClientHits'              => ['descr' => 'The number of HTTP hits that the proxy clients have produced.', 'ds_min' => '0'], //24340977
      'sgProxyHttpClientPartialHits'       => ['descr' => 'The number of HTTP partial (near) hits that the proxy clients have produced.', 'ds_min' => '0'], //160332
      'sgProxyHttpClientMisses'            => ['descr' => '', 'ds_min' => '0'], //177260105
      'sgProxyHttpClientErrors'            => ['descr' => '', 'ds_min' => '0'], //1781827
      'sgProxyHttpClientRequestRate'       => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //32
      'sgProxyHttpClientHitRate'           => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //12
      'sgProxyHttpClientByteHitRate'       => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //22
      'sgProxyHttpClientInBytes'           => ['descr' => '', 'ds_min' => '0'], //135298339836
      'sgProxyHttpClientOutBytes'          => ['descr' => '', 'ds_min' => '0'], //2427341431710

      //sgProxyHttpServer
      'sgProxyHttpServerRequests'          => ['descr' => '', 'ds_min' => '0'], //193132381
      'sgProxyHttpServerErrors'            => ['descr' => '', 'ds_min' => '0'], //7774954
      'sgProxyHttpServerInBytes'           => ['descr' => '', 'ds_min' => '0'], //2340862768156
      'sgProxyHttpServerOutBytes'          => ['descr' => '', 'ds_min' => '0'], //108911530133

      //sgProxyHttpConnections
      'sgProxyHttpClientConnections'       => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //173
      'sgProxyHttpClientConnectionsActive' => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //142
      'sgProxyHttpClientConnectionsIdle'   => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //31
      'sgProxyHttpServerConnections'       => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //140
      'sgProxyHttpServerConnectionsActive' => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //140
      'sgProxyHttpServerConnectionsIdle'   => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE']  //0
  ]
];

// This needs graphs
$table_defs['BLUECOAT-SG-PROXY-MIB']['sgProxyHttpResponse'] = [
  'mib'       => 'BLUECOAT-SG-PROXY-MIB',
  'mib_dir'   => 'bluecoat',
  'table'     => 'sgProxyHttpResponse', // Group sgProxyHttpResponse contains sgProxyHttpResponseTime,sgProxyHttpResponseFirstByte,sgProxyHttpResponseByteRate,sgProxyHttpResponseSize
  'ds_rename' => ['sgProxy' => '', 'Connections' => 'Conn', 'Idle' => 'Id', 'Active' => 'Ac', 'Service' => 'Svc'],
  'graphs'    => [],
  'oids'      => [

      //sgProxyHttpResponse
      'sgProxyHttpServiceTimeAll'           => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //29740
      'sgProxyHttpServiceTimeHit'           => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //177
      'sgProxyHttpServiceTimePartialHit'    => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //2426
      'sgProxyHttpServiceTimeMiss'          => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //34122
      'sgProxyHttpTotalFetchTimeAll'        => ['descr' => '', 'ds_min' => '0'], //6053086552347
      'sgProxyHttpTotalFetchTimeHit'        => ['descr' => '', 'ds_min' => '0'], //4313032486
      'sgProxyHttpTotalFetchTimePartialHit' => ['descr' => '', 'ds_min' => '0'], //388996661
      'sgProxyHttpTotalFetchTimeMiss'       => ['descr' => '', 'ds_min' => '0'], //6048531757851
      'sgProxyHttpFirstByteAll'             => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //654
      'sgProxyHttpFirstByteHit'             => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //65
      'sgProxyHttpFirstBytePartialHit'      => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //326
      'sgProxyHttpFirstByteMiss'            => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //742
      'sgProxyHttpByteRateAll'              => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //354684
      'sgProxyHttpByteRateHit'              => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //1680335
      'sgProxyHttpByteRatePartialHit'       => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //398246
      'sgProxyHttpByteRateMiss'             => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //176162
      'sgProxyHttpResponseSizeAll'          => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //11908
      'sgProxyHttpResponseSizeHit'          => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //21767
      'sgProxyHttpResponseSizePartialHit'   => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE'], //206318
      'sgProxyHttpResponseSizeMiss'         => ['descr' => '', 'ds_min' => '0', 'ds_type' => 'GAUGE']  //10497
  ]
];

// EOF
