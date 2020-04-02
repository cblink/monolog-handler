<?php
namespace Cblink\Monolog\Handler;

use Closure;
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
        if (is_string($record['context'])){
            $record['context'] = [$record['context']];
        }

        // 处理多维数组
        $data = [];
        foreach ($record['context'] as $key => $value){
            if (is_array($value)){
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $data[$key] = $value;
        }

        $logItem = new \Aliyun_Log_Models_LogItem();
        $logItem->setTime(time());
        $logItem->setContents($data);

        $this->client->putLogs(new \Aliyun_Log_Models_PutLogsRequest(
            $this->projectName,
            $this->logName,
            $record['message'],
            "",
            [$logItem]
        ));
    }
}
