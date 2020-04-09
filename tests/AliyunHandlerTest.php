<?php
namespace Cblink\Monolog\Handler\Tests;

use Cblink\Monolog\Handler\AliyunLogHandler;
use Monolog\Logger;
use Monolog\Test\TestCase;

class AliyunHandlerTest extends TestCase
{
    public function testAiyunHandler()
    {
        $accessId = "";
        $accessKey = "";
        // 节点
        $endpoint = "cn-shenzhen";
        // 项目名称
        $projectName = "cblink-test";
        // 日志名称
        $logName = "handler-test";

        $logger = new Logger('test',
            [new AliyunLogHandler($accessId, $accessKey, $endpoint, $projectName, $logName)],
        );

        $data = [
            'request' => [
                'method' => 'get',
                "id"=> "A0E69CC5-1485-4A1E-8912-7BCD5FAA4FB8"
            ],
            "body" => [
                "grant_type" =>  "client_credentials",
                "scope" => "all",
                "client_id" => "1086944261",
                "timestamp" => 1585813920,
                "sign" => "e9a1cacf486f6a52c3a62c191cd3a1ab",
            ]
        ];

        $logger->info('test message', $data);


        $this->assertTrue(true);
    }
}
