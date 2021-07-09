<?php

namespace Cblink\AliyunLog;

use Cblink\AliyunLog\Request\PutLogsRequest;
use Cblink\AliyunLog\Response\PutLogsResponse;
use Exception;
use GuzzleHttp\Client;

/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */
date_default_timezone_set ( 'Asia/Shanghai' );

if(!defined('API_VERSION')){
    define('API_VERSION', '0.6.0');
}

if(!defined('USER_AGENT')){
    define('USER_AGENT', 'log-php-sdk-v-0.6.0');
}

/**
 * Aliyun_Log_Client class is the main class in the SDK. It can be used to
 * communicate with LOG server to put/get data.
 *
 * @author log_dev
 */
class AliyunLogClient {

    /**
     * @var string aliyun accessKey
     */
    protected $accessKey;

    /**
     * @var string aliyun accessKeyId
     */
    protected $accessKeyId;

    /**
     *@var string aliyun sts token
     */
    protected $stsToken;

    /**
     * @var string LOG endpoint
     */
    protected $endpoint;

    /**
     * @var string Check if the host if row ip.
     */
    protected $isRowIp;

    /**
     * @var integer Http send port. The dafault value is 80.
     */
    protected $port;

    /**
     * @var string log sever host.
     */
    protected $logHost;

    /**
     * @var string the local machine ip address.
     */
    protected $source;

    /**
     * Aliyun_Log_Client constructor
     *
     * @param string $endpoint
     *            LOG host name, for example, http://cn-hangzhou.sls.aliyuncs.com
     * @param string $accessKeyId
     *            aliyun accessKeyId
     * @param string $accessKey
     *            aliyun accessKey
     */
    public function __construct($endpoint, $accessKeyId, $accessKey, $token = "") {
        $this->port = 80;
        $this->isRowIp = AliyunLogUtil::isIp($endpoint);
        $this->logHost = $endpoint;
        $this->endpoint = sprintf("%s:%s", $endpoint, $this->port);
        $this->accessKeyId = $accessKeyId;
        $this->accessKey = $accessKey;
        $this->stsToken = $token;
        $this->source = AliyunLogUtil::getLocalIp();
    }

    /**
     * GMT format time string.
     *
     * @return string
     */
    protected function getGMT() {
        return gmdate ( 'D, d M Y H:i:s' ) . ' GMT';
    }


    /**
     * Decodes a JSON string to a JSON Object.
     * Unsuccessful decode will cause an Aliyun_Log_Exception.
     *
     * @return string
     * @throws AliyunLogException
     */
    protected function parseToJson($resBody, $requestId) {
        if (! $resBody)
          return NULL;

        $result = json_decode ( $resBody, true );
        if ($result === NULL){
          throw new AliyunLogException ( 'BadResponse', "Bad format,not json: $resBody", $requestId );
        }
        return $result;
    }

    /**
     * @param $method
     * @param $url
     * @param $body
     * @param $headers
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getHttpResponse($method, $url, $body, $headers) {

        $client = new Client();

        return $client->request($method, $url, [
            'verify' => false,
            'http_errors' => false,
            'body' => $body,
            'headers' => $headers
        ]);
    }

    /**
     * @param $method
     * @param $url
     * @param $body
     * @param $headers
     * @return array
     * @throws AliyunLogException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function sendRequest($method, $url, $body, $headers) {

        $response = $this->getHttpResponse ( $method, $url, $body, $headers );
        $content = $response->getBody()->getContents();

        $header = $response->getHeaders();
        $requestId = $header ['x-log-requestid'] ?? '';

        if ($response->getStatusCode() == 200) {
          return array ($content, $header);
        } else {

            $exJson = $this->parseToJson ( $content, $requestId );

            if (isset($exJson ['error_code']) && isset($exJson ['error_message'])) {
                throw new AliyunLogException ( $exJson ['error_code'],
                        $exJson ['error_message'], $requestId );
            } else {
                if ($exJson) {
                    $exJson = ' The return json is ' . json_encode($exJson);
                } else {
                    $exJson = '';
                }
                throw new AliyunLogException ( 'RequestError',
                        "Request is failed. Http code is {$response->getStatusCode()}.$exJson", $requestId );
            }
        }
    }

    /**
     * @return array
     * @throws AliyunLogException|\GuzzleHttp\Exception\GuzzleException
     */
    private function send($method, $project, $body, $resource, $params, $headers) {
        if ($body) {
            $headers ['Content-Length'] = strlen ($body);
            if(isset($headers["x-log-bodyrawsize"])==false) {
                $headers ["x-log-bodyrawsize"] = 0;
            }
            $headers ['Content-MD5'] = AliyunLogUtil::calMD5 ($body);
        } else {
            $headers ['Content-Length'] = 0;
            $headers ["x-log-bodyrawsize"] = 0;
            $headers ['Content-Type'] = '';
        }

        $headers ['x-log-apiversion'] = API_VERSION;
        $headers ['x-log-signaturemethod'] = 'hmac-sha1';
        if(strlen($this->stsToken) >0)
            $headers ['x-acs-security-token'] = $this -> stsToken;
        if(is_null($project))$headers ['Host'] = $this->logHost;
        else $headers ['Host'] = "$project.$this->logHost";
        $headers ['Date'] = $this->GetGMT ();
        $signature = AliyunLogUtil::getRequestAuthorization ( $method, $resource, $this->accessKey,$this->stsToken, $params, $headers );
        $headers ['Authorization'] = "LOG $this->accessKeyId:$signature";

        $url = $resource;
        if ($params)
            $url .= '?' . AliyunLogUtil::urlEncode ( $params );
        if ($this->isRowIp)
            $url = "http://$this->endpoint$url";
        else{
          if(is_null($project))
              $url = "http://$this->endpoint$url";
          else  $url = "http://$project.$this->endpoint$url";
        }
        return $this->sendRequest ( $method, $url, $body, $headers );
    }

    /**
     * Put logs to Log Service.
     * Unsuccessful opertaion will cause an Aliyun_Log_Exception.
     *
     * @param PutLogsRequest $request the PutLogs request parameters class
     * @throws AliyunLogException
     * @return PutLogsResponse
     */
    public function putLogs(PutLogsRequest $request) {
        if (count ( $request->getLogitems () ) > 4096)
            throw new AliyunLogException ( 'InvalidLogSize', "logItems' length exceeds maximum limitation: 4096 lines." );

        $logGroup = new LogGroup ();
        $topic = $request->getTopic () !== null ? $request->getTopic () : '';
        $logGroup->setTopic ( $request->getTopic () );
        $source = $request->getSource ();

        if ( ! $source )
            $source = $this->source;
        $logGroup->setSource ( $source );
        $logitems = $request->getLogitems ();
        foreach ( $logitems as $logItem ) {
            $log = new Log ();
            $log->setTime ( $logItem->getTime () );
            $content = $logItem->getContents ();
            foreach ( $content as $key => $value ) {
                $content = new LogContent ();
                $content->setKey ( $key );
                $content->setValue ( $value );
                $log->addContents ( $content );
            }

            $logGroup->addLogs ( $log );
        }

        $body = AliyunLogUtil::toBytes( $logGroup );
        unset ( $logGroup );

        $bodySize = strlen ( $body );
        if ($bodySize > 3 * 1024 * 1024) // 3 MB
            throw new AliyunLogException ( 'InvalidLogSize', "logItems' size exceeds maximum limitation: 3 MB." );
        $params = array ();
        $headers = array ();
        $headers ["x-log-bodyrawsize"] = $bodySize;
        $headers ['x-log-compresstype'] = 'deflate';
        $headers ['Content-Type'] = 'application/x-protobuf';
        $body = gzcompress ( $body, 6 );

        $logstore = $request->getLogstore () !== null ? $request->getLogstore () : '';
        $project = $request->getProject () !== null ? $request->getProject () : '';
        $shardKey = $request -> getShardKey();
        $resource = "/logstores/" . $logstore.($shardKey== null?"/shards/lb":"/shards/route");
        if($shardKey)
            $params["key"]=$shardKey;
        list ( $resp, $header ) = $this->send ( "POST", $project, $body, $resource, $params, $headers );
        $requestId = isset ( $header ['x-log-requestid'] ) ? $header ['x-log-requestid'] : '';
        $resp = $this->parseToJson ( $resp, $requestId );
        return new PutLogsResponse( $header );
    }
}

