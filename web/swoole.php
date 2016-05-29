<?php

class HttpServer
{
    public static $instance;
    public $http;
    public static $get;
    public static $post;
    public static $header;
    public static $server;
    private $application;
    public $response = null;
    public $config;
    
    public function __construct()
    {
        $http = new swoole_http_server("0.0.0.0", 9501);
        $http->set(
            array(
                'worker_num'        => 16, //worker process num
		        'backlog'           => 128, //listen backlog
		        'max_request'       => 5000,
		        'dispatch_mode'     => 1,
		        'open_tcp_nodelay'  => 1,
		        'enable_reuse_port' => 1,
		        'task_worker_num'   => 32,
		        'task_worker_max'   => 256,
			    'daemonize' => false,
			    'log_file' => './swoole_http_server.log',
            )
        );
        $http->on('WorkerStart', array($this, 'onWorkerStart'));
        $http->on('Request', array($this, 'onRequest'));
        $http->on('Task',array($this, 'onTask'));
        $http->on('Finish', array($this, 'onFinish'));
        $http->start();
    }
    public function onRequest($request,$response){
    	
    	
    	//请求过滤
    	if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
    		return $response->end();
    	}
    	$this->response = $response;
    	if (isset($request->server)) {
    		HttpServer::$server = $request->server;
    		foreach ($request->server as $key => $value) {
    			$_SERVER[strtoupper($key)] = $value;
    		}
    	}
    	if (isset($request->header)) {
    		HttpServer::$header = $request->header;
    	}
    	if (isset($request->get)) {
    		HttpServer::$get = $request->get;
    		foreach ($request->get as $key => $value) {
    			$_GET[$key] = $value;
    		}
    	}
    	if (isset($request->post)) {
    		HttpServer::$post = $request->post;
    		foreach ($request->post as $key => $value) {
    			$_POST[$key] = $value;
    		}
    	}
    	
    	ob_start();
    	try {
    		//清空request对象
    		$this->application->clearRequest();
    		//run框架
    		$this->application->run();
    		//输出日志到文件,必须强制执行,开启flush之后,qps降低5倍,待研究
    		Yii::getLogger()->flush(true);
    		//关闭数据库连接,防止mysql空闲超时
    		$this->application->db->close();
   
    	} catch (Exception $e) {
    		echo "app-Exception:".$e->getMessage();
    	}   	
    	$result = ob_get_contents();
    	ob_end_clean();
    	$response->end($result);
    	unset($result);
    	
    }
    public function onWorkerStart()
    {
    	//捕获异常
    	register_shutdown_function(array($this, 'handleFatal'));
    	//关闭yii错误注册,防止意外exit
    	define('YII_ENABLE_ERROR_HANDLER', false);
        defined('YII_DEBUG') or define('YII_DEBUG', true);
		defined('YII_ENV') or define('YII_ENV', 'dev');
		require(__DIR__ . '/../vendor/autoload.php');
		require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
		$this->config = require(__DIR__ . '/../config/web.php');
		//预加载yii基类
		foreach (Yii::$classMap as $className){
			Yii::autoload($className);
		}
		//定义script等,防止yii/web/request,url解析错误
		$_SERVER['PHP_SELF']        = '/index.php';
		$_SERVER['SCRIPT_NAME']     = '/index.php';
		$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
		//workerStart启动框架,相比在onRequest启动,性能可提升5倍
		$this->application = new \yii\web\Application($this->config);
		
    }
    /**
     * Fatal Error的捕获
     *
     */
    public function handleFatal()
    {
    	 
    	$error = error_get_last();
    	if (!isset($error['type'])) {
    		return;
    	}
    	switch ($error['type']) {
    		case E_ERROR:
    		case E_PARSE:
    		case E_DEPRECATED:
    		case E_CORE_ERROR:
    		case E_COMPILE_ERROR:
    			break;
    		default:
    			return;
    	}
    	$message = $error['message'];
    	$file    = $error['file'];
    	$line    = $error['line'];
    	$log     = "\n异常提示：$message ($file:$line)\nStack trace:\n";
    	$trace   = debug_backtrace(1);
    	foreach ($trace as $i => $t) {
    		if (!isset($t['file'])) {
    			$t['file'] = 'unknown';
    		}
    		if (!isset($t['line'])) {
    			$t['line'] = 0;
    		}
    		if (!isset($t['function'])) {
    			$t['function'] = 'unknown';
    		}
    		$log .= "#$i {$t['file']}({$t['line']}): ";
    		if (isset($t['object']) && is_object($t['object'])) {
    			$log .= get_class($t['object']) . '->';
    		}
    		$log .= "{$t['function']}()\n";
    	}
    	if (isset($_SERVER['REQUEST_URI'])) {
    		$log .= '[QUERY] ' . $_SERVER['REQUEST_URI'];
    	}
    	Yii::error($log);
    	if ($this->response) {
    		$this->response->status(500);
    		$this->response->end($log);
    	}
    }
    public function onTask($serv, $taskId, $fromId, $data){
    	echo "do task\n";
    	var_dump($data);
    	return true;
    }
    public function onFinish($serv, $taskId, $data){
    	echo "do finish\n";
    	var_dump($data);
    	return true;
    }
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new HttpServer;
        }
        return self::$instance;
    }

}

HttpServer::getInstance();