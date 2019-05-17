# ----------------------------------------------------------------------------
# Copyright © Lyon e-Sport, 2018
#
# Contributeur(s):
#     * Ortega Ludovic - ludovic.ortega@lyon-esport.fr
#
#  Ce logiciel, AdminAFK-registration, est un programme informatique servant à gérer
#  automatiquement les inscriptions des joueurs/équipes en fonction de conditions sur
#  la plateforme toornament.
#
# Ce logiciel est régi par la licence CeCILL soumise au droit français et
# respectant les principes de diffusion des logiciels libres. Vous pouvez
# utiliser, modifier et/ou redistribuer ce programme sous les conditions
# de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA
# sur le site "http://www.cecill.info".
#
# En contrepartie de l'accessibilité au code source et des droits de copie,
# de modification et de redistribution accordés par cette licence, il n'est
# offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
# seule une responsabilité restreinte pèse sur l'auteur du programme,  le
# titulaire des droits patrimoniaux et les concédants successifs.
#
# A cet égard  l'attention de l'utilisateur est attirée sur les risques
# associés au chargement,  à l'utilisation,  à la modification et/ou au
# développement et à la reproduction du logiciel par l'utilisateur étant
# donné sa spécificité de logiciel libre, qui peut le rendre complexe à
# manipuler et qui le réserve donc à des développeurs et des professionnels
# avertis possédant  des  connaissances  informatiques approfondies.  Les
# utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
# logiciel à leurs besoins dans des conditions permettant d'assurer la
# sécurité de leurs systèmes et ou de leurs données et, plus généralement,
# à l'utiliser et l'exploiter dans les mêmes conditions de sécurité.
#
# Le fait que vous puissiez accéder à cet en-tête signifie que vous avez
# pris connaissance de la licence CeCILL, et que vous en avez accepté les
# termes.
# ----------------------------------------------------------------------------

FROM php:7.2 as build

MAINTAINER Ludovic Ortega ludovic.ortega@lyon-esport.fr

# update packages
RUN apt-get update

# install git
RUN apt-get -y install git \
					   zip \
					   unzip

# download adminafk-registration project
RUN git clone https://github.com/M0NsTeRRR/AdminAFK-registration.git

# copy file to /app/
RUN mkdir -p /app/AdminAFK-registration/ && mv AdminAFK-registration/* /app/AdminAFK-registration/

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