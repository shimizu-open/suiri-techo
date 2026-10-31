FROM php:8.4-fpm-bookworm

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Node.js は画面の CSS（Tailwind）と JavaScript を Vite でビルドするために入れる
RUN apt-get update && \
    apt-get -y install --no-install-recommends git unzip curl libzip-dev libicu-dev libonig-dev && \
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs && \
    apt-get clean && rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install intl pdo_mysql zip bcmath

# PHP の設定（エラーの中身を画面に出さない・時刻は日本時間など）。conf.d に置くと自動で読み込まれる
COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini
# PHP-FPM の設定の上乗せ（php-fpm.d の .conf は自動で読み込まれる）。上書きできない形で危ない機能を止める
COPY zz-security.conf /usr/local/etc/php-fpm.d/zz-security.conf

RUN groupadd -g 1000 appuser && useradd -u 1000 -g appuser -m appuser
USER appuser
WORKDIR /data
