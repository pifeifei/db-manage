FROM php:7.3-cli-alpine

COPY . /db-manage/

RUN php -r "copy('https://install.phpcomposer.com/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /bin/composer \
    && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/ \
    && sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/' /etc/apk/repositories \
    && cd /db-manage/ \
    && composer install \
    && rm -Rf ~/.composer \
    # && cp config.yaml.sample config.yaml \ # 配置文件
    && ln -s /db-manage/bin/db-manage /bin/db-manage \
    && chmod +x /db-manage/bin/db-manage \
    && docker-php-ext-install pdo_mysql

VOLUME /db-manage/config.yaml

WORKDIR /db-manage

CMD ["php", "bin/db-manage", "db:manage"]
