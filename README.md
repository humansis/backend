[![Build Status](https://travis-ci.org/ReliefApplications/bms_api.svg?branch=dev)](https://travis-ci.org/ReliefApplications/bms_api)
[![GitHub version](https://badge.fury.io/gh/ReliefApplications%2Fbms_api.svg)](https://badge.fury.io/gh/ReliefApplications%2Fbms_api)

BMS API
==============

### Infos

Set the header 'country' of your request, with ISO3 code, if you need something which depends on a specific country.
Header 'country' contains the ISO3 of a country. A listener will add it to the body of the request ('__country')
before that the controller process.


# DOCKER

#### On your computer

- `docker-compose up --build` : build and run the docker image
- `docker-compose exec php bash` : access to the container bash of PHP image

#### Inside Docker

- `cleanAndTest` : Delete your database, create a new one, migrate migrations, load fixtures, clean cache of import CSV and execute unit tests
- `clean` : Delete your database, create a new one, migrate migrations, load fixtures and clean cache of import CSV