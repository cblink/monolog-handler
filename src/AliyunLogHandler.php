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
        $record = $this->getRecord($record);

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

    /**
     * @param array $record
     * @return array
     */
    public function getRecord(array $record)
    {
        if (is_string($record['context']) || is_int($record['context']) || is_bool($record['context'])){
            $record['context'] = [$record['context']];
        }

        // 如果message长度超128,则写入content
        if (mb_strlen($record['message']) > 128){
            $record['context']['logMessage'] = $record['message'];
            $record['message'] = mb_substr($record['message'], 0, 128);
        }

        // 处理多维数组
        foreach ($record['context'] as $key => $value){
            // 如果结果是数组，需要转换为json
            if (is_array($value)){
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            // 如果类型为null，需要转换为字符，否则官方包校验不通过
            if (is_null($value)){
                $value = "NULL";
            }

            $record['context'][$key] = $value;
        }
        return $record;
    }
}
