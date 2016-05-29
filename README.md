yii2 框架应用swoole
压测机器参数:
单核:Intel(R) Core(TM) i5-4300U CPU @ 1.90GHz,1G内存
未开启日志输出:
ab -n1000 -c10 qps 800-1000
开启日志输出:
ab -n1000 -c10 qps 150-200

