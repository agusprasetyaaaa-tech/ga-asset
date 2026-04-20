FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    unzip \
    git \
    curl \
    oniguruma-dev \
    libxml2-dev \
    npm \
    icu-dev \
    zlib-dev \
    libxml2-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl xml

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy existing application directory contents
COPY . /app

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /app

# Install dependencies (Added --ignore-platform-reqs to bypass strict environment checks)
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts --ignore-platform-reqs
RUN npm install
RUN npm run build

# Remove node_modules after build to keep image small
RUN rm -rf node_modules

# Nginx configuration
COPY ./docker/nginx/default.conf /etc/nginx/http.d/default.conf

# PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Create necessary storage directories
RUN mkdir -p /app/storage/app \
             /app/storage/framework/cache \
             /app/storage/framework/sessions \
             /app/storage/framework/views \
             /app/storage/logs

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Expose port 80
EXPOSE 80

# Start Nginx & PHP-FPM
CMD sh -c "php-fpm -D && nginx -g 'daemon off;'"
