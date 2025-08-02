#!/bin/bash

# MySQL起動
service mysql start

# 初期化（必要に応じて）
mysql -uroot -ppassword -e "CREATE DATABASE IF NOT EXISTS myapp;"
mysql -uroot -ppassword myapp < /var/www/html/init.sql

# Apache起動
apache2-foreground