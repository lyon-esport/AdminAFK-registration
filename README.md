[![Docker Automated build](https://img.shields.io/docker/cloud/automated/lyonesport/adminafk-registration?style=flat-square)](https://hub.docker.com/r/lyonesport/adminafk-registration)
[![Docker Build Status](https://img.shields.io/docker/cloud/build/lyonesport/adminafk-registration?style=flat-square)](https://hub.docker.com/r/lyonesport/adminafk-registration)

The goal of this project is to accept/refuse/ignore registrations who meets requirements on a tournament managed by Toornament.
It works with toornament Webhook and registrations are managed instantly (If the Toornament servers can not contact the 
webhook URL, the servers will try to send the data again during the next 24 hours).

# This tool uses :

* [Toornament](https://www.toornament.com) - solution for organizers to run tournaments
* [Twig](https://twig.symfony.com/) - template engine
* [Bulma](https://bulma.io/) - CSS framework
* [Font Awesome](https://fontawesome.com/) - Icon library

# Requirements
#### Standard

- PHP >= 7.0.0
- Twig >= 2.0
- CURL 7.51 or newer
- SQLite3
- PDO with SQLite drivers

- Toornament API Developper

#### Docker
- Docker

# Get your API key

* [Toornament API](https://developer.toornament.com/v2/overview/get-started?_locale=en)

# Install
#### Standard

- Open your browser and setup the database by going on `setup.php`

#### Docker
Start the container `docker run -d --restart=always -p 8080:80 lyonesport/adminafk-registration:latest`

# Usage guide

#### Informations

- Toornament status : Display if Toornament API is available
- API configurations : Display if API configurations are properly configured
- Webhook : Display if your webhook, subscription are properly configured

#### Manual check

If you have registrations in pending because you forgot to update Toornament ID on the website before opening registration or
Toornament servers are busy, a button will be available to force a manual check of all registrations 
and will accept/refuse/ignore registrations who meets requirements.

#### Toornament API configuration

1. You need to create an application here : https://developer.toornament.com/applications/
2. Provide Client ID, Client secret and API key to your application
3. Provide the Toornament ID (shown in the URL)
4. Provide the Webhook name 30 characters max (one webhook name = one tournament, 
so you have to choose a different name for each tournament)
5. Put the full URL to access to webhook.php (Example : http://localhost/webhook.php)

#### Requirements

If a requirement is blank it will not be checked
If all requirements are blanks, participants will matchs directly

1. Age (If not needed leave blank)

- Minimum age : The minimum age allowed
- Match : How many participants must check this condition
- Custom field : The custom field to check

2. Country (If not needed leave blank)

- List of countries : List of allowed countries (ISO 3166-1 alpha-2 format) separate by a comma (Example 1 : FR,DE,BE or Example 2 : FR)
- Match : How many participants must check this condition
- Custom field : The custom field to check

3. If it matchs ?

- Accept : If the registration match all requirements, the registration will be accepted
- Ignore : If the registration match all requirements, the registration will stay in pending

4. If it doesn't match ?

- Refuse : If the registration doesn't match all requirements, the registration will be refused
- Ignore : If the registration doesn't match all requirements, the registration will stay in pending

# Licence

The code is under CeCILL license.

You can find all details here: https://cecill.info/licences/Licence_CeCILL_V2.1-en.html

# Credits

Copyright © Lyon e-Sport, 2019

Contributor(s):

-Ortega Ludovic - ludovic.ortega@lyon-esport.fr
