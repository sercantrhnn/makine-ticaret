FROM php:8.3-fpm

COPY docker/php/php.ini /usr/local/etc/php/conf.d/docker-php-config.ini

# Sistem paketlerini güncelleyin ve gerekli paketleri yükleyin
RUN apt-get update && apt-get install -y \
    libpq-dev \
    gnupg \
    g++ \
    procps \
    openssl \
    git \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libfreetype6-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libicu-dev  \
    libonig-dev \
    libxslt1-dev \
    acl \
    libxml2-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    python-is-python3 \
    python3-pip \
    fabric \
    && docker-php-ext-install pdo pdo_pgsql pgsql pdo_mysql zip xsl gd intl opcache exif mbstring soap \
    && echo 'alias sf="php bin/console"' >> ~/.bashrc

# GD'yi FreeType, JPEG, WebP ve TrueType font desteği ile yapılandırın
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Redis ve MongoDB uzantılarını yükleyin
RUN pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

RUN pecl install mongodb && docker-php-ext-enable mongodb

# Zaman dilimini ayarlayın
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"

# Composer'ı yükleyin
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www
