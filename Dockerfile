FROM php:8.2-apache

# 必要な拡張機能
RUN apt-get update && apt-get install -y \
  default-mysql-server \
  default-mysql-client \
  zip unzip libzip-dev libonig-dev libxml2-dev \
  && docker-php-ext-install pdo pdo_mysql mysqli

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリ
WORKDIR /var/www/html

COPY . .

RUN composer install

# MySQL初期化スクリプト実行のためにentrypoint変更
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

ENTRYPOINT ["/docker-entrypoint.sh"]