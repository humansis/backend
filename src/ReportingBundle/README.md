# Reporting Module

## example json filters
```
{
    "filters": 
            {
                 "project" : ["1"],
                "distribution": ["1"]
                 
            }
     
}
```

## Command 

There are two command in the reporting bundle, one to get the reference of indicators and one to save data correspondig to the indicators in the database

 - To get the reference of indicators :
 ```
    php bin/console reporting:code-indicator:add
 ```
  - To save data correspondig to the indicators in the database :
 ```
    php bin/console reporting:data-indicator:add
 ```
## Table of database link to the reporting module

![alt text](https://raw.githubusercontent.com/ReliefApplications/bms_api/dev/src/ReportingBundle/Resources/img/BDD.png)

## Fill Database with Command and DataFillers

![alt text](https://raw.githubusercontent.com/ReliefApplications/bms_api/dev/src/ReportingBundle/Resources/img/CommandFillers.png)

### Retrieve Data

![alt text](https://raw.githubusercontent.com/ReliefApplications/bms_api/dev/src/ReportingBundle/Resources/img/BackEndConfiguration.png)


