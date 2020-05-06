# loeye
php framework

## Install
```
composer require loeyae/loeye 
```
初始化应用
```
vendor/bin/loeye loeye:create-app
```
运行应用
```
vendor/bin/loeye loeye:run-app
```
Demo默认监听80端口，如需修改，编辑app/config/app/master.yml
```
server.port=80
```
Demo在php安装有Swoole扩展的情况下，会默认使用Swool\Http\Server作为服务启动，如果没有安装Swoole，会使用React\Http作为服务启动

开发应用前，将app目录加入到自动加载的namespace，比如编辑composer.json，增加psr-4自动加载规则
```
"autoload": {
    "psr-4": {
        "app\\": "app/"
    }
}
```