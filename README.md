# loeye
php framework


## Installation
```
composer require loeyae/loeye 
```

## Quick Start
初始化应用
```
vendor/bin/loeye loeye:create-app
```
运行应用
```
vendor/bin/loeye loeye:run-app
```
或者直接运行根目录下的App.php
```
php App.php
```
Demo默认监听80端口，如需修改，编辑app/config/app/master.yml
```
server.port=80
```
Demo在php安装有Swoole扩展的情况下，会默认使用Swoole\Http\Server作为服务启动，如果没有安装Swoole，会使用React\Http\Server作为服务启动

开发应用前，将app目录加入到自动加载的namespace，比如编辑composer.json，增加psr-4自动加载规则
```
"autoload": {
    "psr-4": {
        "app\\": "app/"
    }
}
```
更新autoload
```
composer update
```
### 根据已有数据库快速搭建service应用
* 初始化service应用
```
vendor/bin/loeye loeye:create-app -d service
```
* 修改默认监听端口
* 增加app目录namespace自动加载规则
* 修改conf/database/master.yml中数据配置
* 生成数据表对应实体
```
vendor\bin\loeye loeye:generate-entity
```
> 生成好的实体自带简单的验证规则，可根据实际情况进行调整
* 生成实体类对应server
```
vendor\bin\loeye loeye:generate-server
```
* 生成默认service
```
vendor\bin\loeye loeye:create-service
```
> service中会生成client的配置文件和client类，可用于其它应用访问该service使用，
> 使用时复制conf/clien,service/client目录下的文件到相应应用，注意修改conf/client/master.yml
> 中的service.server_url地址,以及service/client目录下各文件的namespace。
> 如果需要创建相应的plugin，可使用命令 ```  vendor\bin\loeye loeye:generate-client-plugin ```

* 运行应用
```
vendor\bin\loeye loeye:run-app
```
## License
Licensed under <a href="http://www.apache.org/licenses/LICENSE-2.0.html">Apache 2 license</a>.