<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'redis' => [
	        'class' => 'yii\redis\Connection',
	        'hostname' => 'localhost',
	        'port' => 6379,
	        'database' => 0,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['warning','info'],
                    'logVars'=>[],//关闭$_SERVER等自动日志输出
                ],
                [
	                'class' => 'yii\log\FileTarget',
	                'levels' => ['error'],
	                'logVars'=>[],
	                'logFile' => '@runtime/logs/error.log',
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            	'app/<controller:[\w-]+>/<action:[\w-]+>' => '<controller>/<action>',
            ],
        ],
        
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
