FROM php:8.2-fpm

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    libonig-dev \
    nodejs \
    npm \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure intl \
  && docker-php-ext-install -j"$(nproc)" \
    intl \
    mbstring \
    pdo_mysql \
    zip \
    gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY ./docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

ENTRYPOINT ["/usr/local/bin/app-entrypoint"]
CMD ["php-fpm"]
