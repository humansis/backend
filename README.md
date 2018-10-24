[![Build Status](https://travis-ci.org/ReliefApplications/bms_api.svg?branch=dev)](https://travis-ci.org/ReliefApplications/bms_api)
[![GitHub version](https://badge.fury.io/gh/ReliefApplications%2Fbms_api.svg)](https://badge.fury.io/gh/ReliefApplications%2Fbms_api)

BENEFICIARY MANAGEMENT SYSTEM
==============

## About

A platform that allows humanitarian organisations to manage relief items (Food, Non Food Items, CASH) to people in needs for life-saving humanitarian responses to emergency situations.

BMS is the first fully open-source relief platform for humanitarian actors to efficiently manage relief operations after a disaster, during a war or in response to long term crises. 

If you're an experienced dev and you'd like to get involved, contact us [here](https://reliefapps.org/career.php).

The global project documentation is in README.md of the frontend [repository](https://github.com/ReliefApplications/bms_front)

If you're an experienced dev and you'd like to get involved, contact us [here](https://reliefapps.org/career.php) 

API TECH doc
==============
 
#### Infos

Set the header `country` of your request, with ISO3 code, if you need something which depends on a specific country.
Header `country` contains the ISO3 of a country. A listener will add it to the body of the request (`__country`)
before that the controller process.

#### Documentation
- [Beneficiary Bundle](src/BeneficiaryBundle/README.md)
- [Distribution Bundle](src/DistributionBundle/README.md)
- [Project Bundle](src/ProjectBundle/README.md)
- [Reporting Bundle](src/ReportingBundle/README.md)

# Docker

#### Informations

We are using the doctrine extension `LevenshteinFunction`, from the package `jrk/levenshtein-bundle`
- The Git repository : https://github.com/jr-k/JrkLevenshteinBundle

##### Trick Levenshtein activation

`php bin/console jrk:levenshtein:install`

#### On your computer

- `docker-compose up --build` : build and run the docker image
- `docker-compose exec php bash` : access to the container bash of PHP image

#### Inside Docker

- `cleanAndTest` : Delete your database, create a new one, migrate migrations, load fixtures, clean cache of import CSV and execute unit tests
- `clean` : Delete your database, create a new one, migrate migrations, load fixtures and clean cache of import CSV

#### Git Hooks

Just after installation, don't forget to set your hook directory in order to enable the custom hooks (pre-commit and pre-push):

`git config core.hooksPath hooks`

# Command

- `php bin/console r:c:c` : clear cache files created for the import process of households
- `php bin/console r:i:t` : test and display execution time of import process of households

# AWS

- The API is hosted on AWS Elastic Beanstalk and the database on AWS RDS
- To initialize Elastic Beanstalk on your computer run `eb init --interactive`, to deploy the app run `eb deploy`

- Symfony configuration for AWS is defined in [symfony.config](.ebextensions/symfony.config) : the commands execute databases migrations and clean the cache

- When the database is dumped, you need to create the Levenshtein function manually in the RDS database :
```
CREATE DEFINER=`reliefapps`@`%` FUNCTION `LEVENSHTEIN`(s1 VARCHAR(255), s2 VARCHAR(255)) RETURNS int(11)
BEGIN
    DECLARE s1_len, s2_len, i, j, c, c_temp, cost INT;
    DECLARE s1_char CHAR;
    DECLARE cv0, cv1 VARBINARY(256);
    SET s1_len = CHAR_LENGTH(s1), s2_len = CHAR_LENGTH(s2), cv1 = 0x00, j = 1, i = 1, c = 0;
    IF s1 = s2 THEN
        RETURN 0;
    ELSEIF s1_len = 0 THEN
        RETURN s2_len;
    ELSEIF s2_len = 0 THEN
        RETURN s1_len;
    ELSE
        WHILE j <= s2_len DO
            SET cv1 = CONCAT(cv1, UNHEX(HEX(j))), j = j + 1;
        END WHILE;
        WHILE i <= s1_len DO
            SET s1_char = SUBSTRING(s1, i, 1), c = i, cv0 = UNHEX(HEX(i)), j = 1;
            WHILE j <= s2_len DO
                SET c = c + 1;
                IF s1_char = SUBSTRING(s2, j, 1) THEN SET cost = 0; ELSE SET cost = 1; END IF;
                SET c_temp = CONV(HEX(SUBSTRING(cv1, j, 1)), 16, 10) + cost;
                IF c > c_temp THEN SET c = c_temp; END IF;
                SET c_temp = CONV(HEX(SUBSTRING(cv1, j+1, 1)), 16, 10) + 1;
                IF c > c_temp THEN SET c = c_temp; END IF;
                SET cv0 = CONCAT(cv0, UNHEX(HEX(c))), j = j + 1;
            END WHILE;
            SET cv1 = cv0, i = i + 1;
        END WHILE;
    END IF;
    RETURN c;
END
```

# Import & Export

It's now possible to import or export data in the application. You can export data in the app in different formats : CSV, XLS or ODS.

Note that during the import part, all beneficiaries you modify in the imported file will be updated. Moreover, if a beneficiary is missing in the distribution but is present in all the beneficiaries of the project, he'll be removed from the distribution. 
The same process goes for beneficiaries added in the imported file. Finally, if you add a beneficiary that is not part of the project (in the database), he'll be added in "errors" array that shows all users that won't be added to the distirbution.
