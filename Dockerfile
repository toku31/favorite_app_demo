FROM php:8.2-apache

# 必要なPHP拡張をインストール（MySQL使う場合など）
RUN docker-php-ext-install pdo pdo_mysql

# アプリのコードをApacheの公開ディレクトリへコピー
COPY . /var/www/html/

# Apacheが読み取れるようにパーミッション調整
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
