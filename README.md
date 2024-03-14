# 兼容likeadmin用户的webman中间件

## 初衷

复用likeadmin的基础功能和手机端uniapp源码，提高开发效率；

使用常驻内存的webman来开发新功能。



## 安装插件

`composer require ledc/likeadmin`



## Nginx伪静态


### 使用命令生成nginx配置文件：
```shell
php webman likeadmin:nginx likeadmin_proxy
```


### 生成的配置文件示例：
```conf
location ~ ^/(likeadmin|like)
{
  proxy_set_header X-Real-IP $remote_addr;
  proxy_set_header Host $host;
  proxy_set_header X-Forwarded-Proto $scheme;
  proxy_http_version 1.1;
  proxy_set_header Connection "";
  if (!-f $request_filename){
    proxy_pass http://127.0.0.1:8787;
  }
}
```


## 原理

配置Nginx伪静态后，所有请求地址以`likeadmin`或`like`开头的接口，都由webman来处理。


## 自定义前缀

修改`likeadmin`或`like`：

1.修改`config/plugin/ledc/likeadmin/middleware.php`

2.使用命令重新生成nginx配置文件



## 其他

仓库地址：https://github.com/ledccn/likeadmin
