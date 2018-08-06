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

## Schema of Reporting Module

![alt text](https://raw.githubusercontent.com/ReliefApplications/bms_api/dev/src/ReportingBundle/Resources/img/SchemaReportingModule.png)

## Fill Database with Command and DataFillers

![alt text](https://raw.githubusercontent.com/ReliefApplications/bms_api/dev/src/ReportingBundle/Resources/img/CommandFillers.png)

- Two command file: ReportingCodeIndicatorAddCommand.php && ReportingDataIndicatorAddCommand.php

**Command ReportingCodeIndicatorAddCommand.php** :

Create the commande php bin/console reporting:code-indicator: add which call dataFillersIndicator to fill reportingIndicator. 
* DataFillersIndicator :  read a csv file and add data in it in the reportingIndicator table.

**Command ReportingDataIndicatorAddCommand.php** :

Create the commande php bin/console reporting:data-indicator:add which call dataFillers to fill reportingCountry, reportingProject, reportingDistribution and reportingValue

* DefautDatafillers (service) : call DataFillers when the indicator in parameter match with a regular expression and call the corresponding function (indicator code must always be the name of the function to call).
	* begin by BMS_C or BMSU_C : call CountryDataFillers to set data in ReportingCountry and ReportingValue
	* begin by BMS_P or BMSU_P : call ProjectDataFillers to set data in ReportingProject and ReportingValue
	* begin by BMS_D or BMSU_D : call DistributionDataFillers to set data in ReportingDistribution and ReportingValue


Insertion and retrieve request in the database are in DQL (Doctrine Query Langage)
A code beginning with BMS_ is display in the front and a code beginnning with BMSU_ is an indicator use to calculate other indicator and don't display.


## Retrieve Data

![alt text](https://raw.githubusercontent.com/ReliefApplications/bms_api/dev/src/ReportingBundle/Resources/img/BackEndConfiguration.png)
![alt text](https://raw.githubusercontent.com/ReliefApplications/bms_api/dev/src/ReportingBundle/Resources/img/SchemaBackEndConfiguration.png)


* Controller :  
    * @Post /indicators/serve/{{id}}, send data formatted corresponding to code to display it in front
	* @Get /indicators, send list of all indicators to display in front

* Computer (link with an interface) : call DataRetrievers when the indicator in parameter match with a regular expression and call the corresponding function (indicator code must always be the name of the function to call).

* DataRetriever (link with an interface) : Contains request to retrieve data in the database. Function will be called by function compute in computer.

* Formatter (link with an interface) : Use to know which format is mandatory for the graph then return data in the good format.

* Finder  (link with an interface) : Search all indicator and return them.


