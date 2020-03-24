<?php
namespace Cblink\Monolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Class AliyunHandler
 * @package App\Logging
 */
class AliyunLogHandler extends AbstractProcessingHandler
{
    /**
     * @var \Aliyun_Log_Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var string
     */
    protected $logName;

    public function __construct(
        string $access_id,
        string $access_key,
        string $endpoint,
        string $projectName,
        string $logName,
        $level = Logger::DEBUG,
        bool $bubble = true
    )
    {
        $this->client = new \Aliyun_Log_Client($endpoint.'.log.aliyuncs.com', $access_id, $access_key);
        $this->projectName = $projectName;
        $this->logName = $logName;
        parent::__construct($level, $bubble);
    }

    /**
     * @param array $record
     * @throws \Aliyun_Log_Exception
     */
    public function write(array $record): void
    {
        $logItem = new \Aliyun_Log_Models_LogItem();
        $logItem->setTime(time());
        $logItem->setContents($record['context']);

        $this->client->putLogs(new \Aliyun_Log_Models_PutLogsRequest(
            $this->projectName,
            $this->logName,
            $record['message'],
            "",
            [$logItem]
        ));
    }
}
