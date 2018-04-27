FROM php:7.1-cli

COPY ./composer.phar /usr/local/bin/composer
RUN apt-get update && apt-get install -y git \
		libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
	&& docker-php-ext-install -j$(nproc) iconv mcrypt mysqli pdo_mysql \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
	&& chmod 755 /usr/local/bin/composer 