# Use the official FrankenPHP image
FROM dunglas/frankenphp:8.4-php8.4-bookworm

# Install required PHP extensions
# Tip: Using install-php-extensions is more reliable and handles dependencies automatically
ADD https://github.com /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql mysqli

# Set the working directory
WORKDIR /app

# Copy application files
COPY . /app

# Railway provides a dynamic $PORT. 
# We configure FrankenPHP to listen on this port.
ENV SERVER_NAME=":${PORT:-8080}"

# Expose is documentation only; Railway uses the $PORT variable
EXPOSE 8080

# The base image already has an ENTRYPOINT that starts FrankenPHP
