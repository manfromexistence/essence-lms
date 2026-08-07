FROM php:8.3-apache AS build

RUN apt-get update && apt-get install -y \
    git curl libicu-dev libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev unzip nodejs npm \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Cache composer deps: only lockfiles first
COPY composer.json composer.lock ./
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# Cache npm deps: only lockfiles first
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .

RUN composer dump-autoload --optimize --no-scripts \
    && npm run build

FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libicu-dev libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev unzip \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip intl \
    && a2enmod rewrite

COPY --from=build /app /var/www/html
COPY --from=build /usr/bin/composer /usr/bin/composer

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

COPY docker-apache-config.conf /etc/apache2/sites-available/000-default.conf
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html
EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
