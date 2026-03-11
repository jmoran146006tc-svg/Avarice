FROM dunglas/frankenphp:php8.4.18-bookworm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && rm -rf /var/lib/apt/lists/*

COPY php.ini /usr/local/etc/php/php.ini
COPY . /app/public

EXPOSE 8080