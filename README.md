# 数据库主从自动配置

> `docker-compose` 启动不能自动配置`MySQL`主从，故有这个小功能。
>
> PS：网址**可能**会修改为：database-manager

## 特性

* mysql 主从自动配置。
  * 一主多从，多少从服务器均可设置。
* _根据`docker-compose` 自动识别主从服务器，master 的 server_id 必须小于100，将来实现多主（未实现）_



## 使用

```shell
# TODO
# 配置参考 `config.yaml.sample` 文件，复制改名为 `config.yaml` 即可 。
docker run -itd --name db-config pifeifei/database-config
docker cp ./config.yaml db-config:/config.yaml.sample
docker stop db-config && docker rm db-config
# mysql 绑定
docker run pifeifei/database-config /bin/database-config config
# 帮助
docker run pifeifei/database-config /bin/database-config list
```



### 其他特性待定

* 