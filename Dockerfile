FROM php:7.2 as build

LABEL maintainer="Ludovic Ortega ludovic.ortega@lyon-esport.fr"

# update packages
RUN apt-get update

# install git
RUN apt-get -y install zip \
					   unzip

# copy file to /app/AdminAFK-registration/
COPY * /app/AdminAFK-registration/

# install composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# install dependencies
RUN cd /app/AdminAFK-registration/ && composer require "twig/twig:^2.0" --no-plugins

FROM php:7.2-apache

# clean html directory
RUN rm -Rf /var/www/html/*

# copy adminafk-registration
COPY --from=build /app/AdminAFK-registration/ /var/www/html/

# update packages
RUN apt-get update

# install required packages
RUN apt-get -y install curl

# set workdir
WORKDIR /var/www/html/

# create database
RUN php -f setup.php
