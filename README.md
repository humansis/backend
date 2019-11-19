[![Build Status](https://travis-ci.org/ReliefApplications/bms_api.svg?branch=dev)](https://travis-ci.org/ReliefApplications/bms_api)
[![GitHub version](https://badge.fury.io/gh/ReliefApplications%2Fbms_api.svg)](https://badge.fury.io/gh/ReliefApplications%2Fbms_api)

BENEFICIARY MANAGEMENT SYSTEM
==============

# About

A platform that allows humanitarian organisations to manage relief items (Food, Non Food Items, CASH) to people in needs for life-saving humanitarian responses to emergency situations.

BMS is the first fully open-source relief platform for humanitarian actors to efficiently manage relief operations after a disaster, during a war or in response to long term crises. 

If you're an experienced dev and you'd like to get involved, contact us on Twitter by DM : @reliefapps https://twitter.com/Reliefapps

The global project documentation is in README.md of the frontend [repository](https://github.com/ReliefApplications/bms_front)

# Documentation
 
#### Infos

Set the header `country` of your request, with ISO3 code, if you need something which depends on a specific country.
Header `country` contains the ISO3 of a country. A listener will add it to the body of the request (`__country`)
before that the controller process.

#### Specific Documentation
- [Beneficiary Bundle](src/BeneficiaryBundle/README.md)
- [Distribution Bundle](src/DistributionBundle/README.md)
- [Project Bundle](src/ProjectBundle/README.md)
- [Reporting Bundle](src/ReportingBundle/README.md)

#### General Information

We are using the doctrine extension `LevenshteinFunction`, from the package `jrk/levenshtein-bundle`
- The Git repository : https://github.com/jr-k/JrkLevenshteinBundle
To trick Levenshtein activation, run: `php bin/console jrk:levenshtein:install`

#### Docker

- `docker-compose up --build` : build and run the docker image
- `docker-compose exec php bash` : access to the container bash of PHP image

#### Inside Docker

- `cleanAndTest` : Delete your database, create a new one, migrate migrations, load fixtures, clean cache of import CSV, start the cron service and execute unit tests
- `clean` : Delete your database, create a new one, migrate migrations, load fixtures, start the cron service and clean cache of import CSV
- `cron-launch` : Start the cron service

#### Git Hooks

Just after installation, don't forget to set your hook directory in order to enable the custom hooks (pre-commit and pre-push):

`git config core.hooksPath hooks`

#### Useful Commands

- `php bin/console r:c:c` : clear cache files created for the import process of households
- `php bin/console r:i:t` : test and display execution time of import process of households

#### AWS

- The API is hosted on AWS EC2 and the database on AWS RDS

- When the database is dumped, you need to create the Levenshtein function manually in the RDS database :
```
CREATE DEFINER=`bms_user`@`%` FUNCTION `LEVENSHTEIN`(`s1` VARCHAR(255), `s2` VARCHAR(255)) RETURNS INT(11) NOT DETERMINISTIC CONTAINS SQL SQL SECURITY DEFINER BEGIN
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
