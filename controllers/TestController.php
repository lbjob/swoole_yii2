<?php 

namespace app\controllers;

use Yii;
use yii\web\Controller;

class TestController extends Controller
{
	
	function actionIndex(){
		
		echo "test-index-".rand(0,99999);
		$db = Yii::$app->db;
		$res = $db->createCommand("select * from jdb_company_info ORDER BY RAND() limit 1");
		$data = $res->queryOne();
		echo "<pre>";
		var_dump($data);
		return false;
	}
	function actionLogin(){
	
		echo "test-login-".rand(0,99999);
		return false;
	}
	function actionLogin2(){
		echo "test-login2-".rand(0,99999);
		return false;
	}
	
}