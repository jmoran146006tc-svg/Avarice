FROM dunglas/frankenphp:php8.4.18-bookworm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && rm -rf /var/lib/apt/lists/*

RUN echo ':8080 { root * /app }' > /etc/caddy/Caddyfile

COPY . /app

EXPOSE 8080