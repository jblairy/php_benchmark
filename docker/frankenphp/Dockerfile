FROM dunglas/frankenphp:1.9.1-php8.4

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    default-mysql-client \
    unzip \
    curl \
    ca-certificates \
    git \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN install-php-extensions \
    pdo_mysql \
    zip \
    opcache \
    apcu \
    redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install docker-compose for benchmark execution
RUN curl -L https://github.com/docker/compose/releases/download/v2.23.0/docker-compose-linux-x86_64 -o /usr/local/bin/docker-compose \
    && chmod +x /usr/local/bin/docker-compose

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy PHP configuration optimizations
COPY docker/frankenphp/php.ini $PHP_INI_DIR/conf.d/99-custom.ini

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /app

# Copy application
COPY . /app

# Install dependencies (will be cached if composer.json doesn't change)
# Using --no-scripts to avoid running cache:clear which requires dev dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Set permissions
RUN chown -R www-data:www-data /app/var

# Expose HTTP and HTTPS ports
EXPOSE 80 443

# Use supervisor to manage FrankenPHP workers and Messenger consumers
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
