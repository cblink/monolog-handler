## monolog handler

#### 安装
`composer require cblink/monolog-handler`

#### 支持的Handler列表

- 阿里云log


#### 在laravel框架中使用

1. 修改项目中 `app/logging.php`文件，`channels`中添加`aliyun driver`，示例如下
```php
// 将
return [
    // ...
    'channels' => [
        //... 
        
        // 添加此项
        'aliyun-log' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => Cblink\Monolog\Handler\AliyunLogHandler::class,
            'handler_with' => [
                'access_id' =>  '阿里云控制台access_key_id',
                'access_key' => '阿里云控制台access_key_secret',
                'endpoint' => 'cn-shenzhen',    // 节点，无需填写 log.aliyuncs.com
                'projectName' => 'laravel-project',     // 日志服务中的项目名称
                'logName' => 'service-wechat'       // 日志名称
            ]
        ],
    ]   
];
```

2. 修改`.env`配置文件`LOG_CHANNEL`值为`aliyun-log`

```txt
LOG_CHANNEL=aliyun-log
```

#### 其他使用

。
