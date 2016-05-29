<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class SiteController extends Controller
{
    
    public function actionIndex()
    {
		echo "site-index-".time()."-".rand(0,9999);
		echo "<pre>";
		Yii::info("-----------------site-index----123----------------------------");
        return false;
    }
	public function actionLogin()
    {
		echo "site-login-".time()."-".rand(0,9999);
        return false;
    }
    
}
