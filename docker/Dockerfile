FROM php:8.4-apache
RUN apt-get update && apt-get upgrade -y
RUN apt-get update && apt-get --no-install-recommends install -y \
    curl \
    git \
    unzip \
    wget \
    zip \
    nano \
    libapache2-mod-geoip
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /var/cache/apt/archives/*
RUN docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli
RUN docker-php-ext-enable \
        pdo \
        pdo_mysql \
        mysqli
RUN a2enmod rewrite
RUN a2enmod geoip