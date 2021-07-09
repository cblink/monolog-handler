<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */
namespace Cblink\AliyunLog\Response;

/**
 * The response of the PutLogs API from log service.
 *
 * @author log service dev
 */
class PutLogsResponse extends Response {
    /**
     * Aliyun_Log_Models_PutLogsResponse constructor
     *
     * @param array $header
     *            PutLogs HTTP response header
     */
    public function __construct($headers) {
        parent::__construct ( $headers );
    }
}
