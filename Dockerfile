FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apk update && apk add --no-cache \
    build-base \
    shadow \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    unzip \
    bash \
    oniguruma-dev \
    libxml2-dev \
    nodejs \
    npm

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd

# Copy composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP-FPM to allow environment variables (needed for docker-compose env vars)
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

# Copy application files
COPY . /var/www

# Configure entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
