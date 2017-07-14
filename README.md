# docker-lnmp
docker lnmp 多容器部署方案。完全基于docker官方镜像，遵循最佳实践，一容器一进程。

## docker基础
docker的基础用法请参考官方文档

[中文文档参考](https://github.com/yeasy/docker_practice/blob/master/SUMMARY.md)。

## docker-compose

docker-compose是用来管理编排多个容器协作的。

通过docker-compose.yml来编排nginx、php、mysql之间的通信和协作。

在docker-lnmp目录下通过命令 docker-compose up 启动容器

然后通过 localhost或者localhost:8000就可以访问index.php了

## docker-compose.yml简单介绍

### Mysql

先看Mysql，因为这个Mysql镜像直接来自与官方,没有做任何修改。

```yml
    mysql: ### 容器名称
      image: mysql:5.7 ### 官方镜像 版本号5.7
      volumes:
        - mysql-data:/var/lib/mysql ### 数据卷，mysql数据就存放在这里
      ports:
        - "3306:3306" ###端口映射，主机端口:容器对外端口
      environment:
        - MYSQL_ROOT_PASSWORD=123456  ### 设置环境变量，这个变量名是官方镜像定义的。
    
```
[官方Mysql镜像构建参考](https://github.com/dockerfile/mysql)。

### PHP

PHP镜像也来自与官方，但是官方镜像并没有提供连接Mysql相关的pdo_mysql扩展，这里做了一点修改，所以不能直接用image来依赖官方镜像，需要单独写一个Dockerfile来自定义PHP镜像。

```yml
    php-fpm:
      build:
        context: ./php ### 自定义PHP镜像的配置目录
      volumes:
        - ./www:/var/www/html ### 主机文件与容器文件映射共享，PHP代码存这里
      expose:
        - "9000" ### 容器对外暴露的端口
      depends_on:
        - mysql ### 依赖并链接Mysql容器，这样在PHP容器就可以通过mysql作为主机名来访问Mysql容器了
```

自定义PHP镜像的配置文件 Dockerfile

```
### 来自官方的PHP镜像版本为7.1-fpm.
### 该版本只包含FPM不包括CLI,所以这里并不能执行composer
### 如果需要用PHP-CLI 需要再开一个CLI容器，或者安装同时包含FPM和CLI的版本
FROM php:7.1-fpm 

### 设置环境变量
ENV TZ=Asia/Shanghai

### 执行bash命令安装php所需的扩展
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
    ### 这里是docker提供的安装php扩展的方法，在这里安装了pdo_mysql扩展还有GD库等
    && docker-php-ext-install -j$(nproc) iconv mcrypt mysqli pdo_mysql \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd
### 扩展php.ini
COPY ./php.ini /usr/local/etc/php/conf.d/php.ini
```

## Nginx

Nginx需要配置一个server,所以也需要一点简单的定制

```yml
    nginx:
      build:
        context: ./nginx ### 自定义Nginx镜像的配置目录
      volumes:
          - ./www:/var/www/html 主机文件与容器文件映射共享，PHP代码存这里
      ports:
          - "80:80" ### 端口映射，如果你主机80端口被占用，可以用8000:80
          - "443:443"
      depends_on:
          - php-fpm ### 依赖并连接PHP容器，这样在Nginx容器就可以通过php-fpm作为主机名来访问PHP容器了
```

自定义Nginx镜像的配置文件Dockerfile

```yml
FROM nginx:1.11 ### 官方镜像

ENV TZ=Asia/Shanghai ### 环境变量

COPY ./nginx.conf /etc/nginx/conf.d/default.conf ### server配置
```

Nginx server 配置

```
server {

    listen 80 default_server;
    listen [::]:80 default_server ipv6only=on;

    server_name localhost;
    root /var/www/html;
    index index.php index.html index.htm;

    location / {
         try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        try_files $uri /index.php =404;
        
        ### 主要是这里用 php-fpm:9000来访问PHP容器
        fastcgi_pass php-fpm:9000;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

}
```

## volumes 数据卷

数据卷独立与容器之外专门存放数据的

```
### 这里定义了mysql所用到的数据卷
volumes:
    mysql-data:
```

## php测试代码

```php
<?php
// 建立连接
try{
        //这里的mysql:host=mysql,后面这个mysql就是我们的mysql容器
        //用户名 root 是默认的
        //密码 123456 就是我们在mysql容器设置的环境变量
	$dbh = new PDO("mysql:host=mysql;dbname=mysql", "root", 123456);
	$dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$dbh->exec("SET CHARACTER SET utf8");
	$dbh=null; //断开连接	
}catch(PDOException $e){
	print"Error!:".$e->getMessage()."<br/>";
	die();
}
// 错误检查S
// 输出成功连接
echo "<h1>成功通过 PDO 连接 MySQL 服务器</h1>" . PHP_EOL;

phpinfo();

?>
```
