# DistributionBundle

## Definition

**Distribution type** : 0 is for Household, 1 for Beneficiary

For **vulnerabilityCriteria** : true or false in 'condition_string' (either the beneficiary has the vulnerability or not)

## Import & Export

It's now possible to import or export data in the application. You can export data in the app in different formats : CSV, XLSX or ODS.

Note that during the import part, all beneficiaries you modify in the imported file will be updated. Moreover, if a beneficiary is missing in the distribution but is present in all the beneficiaries of the project, he'll be removed from the distribution. 
The same process goes for beneficiaries added in the imported file. Finally, if you add a beneficiary that is not part of the project (in the database), he'll be added in "errors" array that shows all users that won't be added to the distribution.

## Configuration

### Config.yml

#### Example of configuration.

**retriever** : You must defined a retriever class which implement the AbstractRetriever of DistributionBundle.
In this class, you can define some preFinder function to reformat your data between the request and the SQL execution.
Please specified the full name of the class (with namespace).

Inside 'criteria', you must defined every criteria that can be used to determined if a person should be a beneficiary.
**Model** => {"**key**" : "**type**"}

Type : **text**, **number**, **bool**, **date** or **entity name (with namespace)**

```yaml
distribution:
    retriever: BeneficiaryBundle\Utils\Distribution\DefaultRetriever
    criteria: {
        gender: boolean,
        dateOfBirth: date,
        vulnerabilityCriteria: NewApiBundle\Entity\VulnerabilityCriterion,
        countrySpecific: NewApiBundle\Entity\CountrySpecific
        }
```

If you set a class name in the type, when you will get the list of criteria, it will return the list of data inside the table
of the entity.


Example of criteria list :
```json
[
  {
    "field_string": "gender",
    "type": "boolean"
  },
  {
    "field_string": "dateOfBirth",
    "type": "date"
  },
  {
    "table_string": "vulnerabilityCriteria",
    "id": 1,
    "field_string": "disabled"
  },
  {
    "table_string": "vulnerabilityCriteria",
    "id": 2,
    "field_string": "soloParent"
  },
  {
    "table_string": "countrySpecific",
    "id": 1,
    "field_string": "IDPoor",
    "type": "number"
  },
  {
    "table_string": "countrySpecific",
    "id": 2,
    "field_string": "equityCardNo",
    "type": "text"
  }
]
```


### Repository

There is two types of distribution :
- by person
- by household

So we have two repositories which implement the AbstractCriteriaRepository.
Inside these repositories, you must implement methods from the InterfaceCriteriaRepository and a method for each key
specified in the config.yml file, with the pattern : whereClassName.

**Example :**

Config :
```yaml
vulnerabilityCriteria: NewApiBundle\Entity\VulnerabilityCriterion,
``` 

In each repositories (Household and Beneficiary), we have to create a method called whereVulnerabilityCriterion.


These methods are used to add criteria on their field.





# Routes

- **POST /distribution/criteria/project/{id_of_project}** : Get the list of beneficiaries of household

- **POST /distribution/criteria/project/{id_of_project}/number** : Get the number of beneficiaries of household


### Household Distribution

- **distribution_type** : 'household' (for send distribution to every beneficiaries of a household)
OR 'beneficiary' (to send distribution to a specific beneficiary)
- **criteria** : list of criterion :
    - **table_string** : 'default' or name used in config (the key)
    - **target** : 'beneficiary' (head of household) or 'dependent' (head of household dependents) or 'null' (both)
    - **field_string** : name of a column of Beneficiary table or null 
    - **condition_string** : '=', '<', '>', '<=', '>=', '!='. It's the operator used for compare the wanted value with the value in database
    - **value_string** : value to be compare with the database column
    - **id** : *optional* '{id}' (id of the foreign key)
    
    
Example :
```json
{
  "distribution_type": "household",
  "criteria": [
    {
      "table_string": "countrySpecific",
      "target": "beneficiary",
      "field_string": null,
      "condition_string": "=",
      "value_string": 1,
      "id_field": 1
    },
    {
      "table_string": "vulnerabilityCriteria",
      "target": "beneficiary",
      "field_string": null,
      "condition_string": true,
      "value_string": null,
      "id_field": 1
    },
    {
      "table_string": "default",
      "target": "beneficiary",
      "field_string": "gender",
      "condition_string": "=",
      "value_string": 0
    },
    {
      "table_string": "default",
      "target": "beneficiary",
      "field_string": "dateOfBirth",
      "condition_string": "=",
      "value_string": "1975-11-30"
    }
  ]
}
```
    
    

### Beneficiary Distribution


- **distribution_type** : 'household' (for send distribution to every beneficiaries of a household)
OR 'beneficiary' (to send distribution to a specific beneficiary)
- **criteria** : list of criterion :
    - **field** : name of a column of Beneficiary table or 'idCountrySpecific' or 'idVulnerabilityCriterion'
    - **operator** : '=', '<', '>', '<=', '>='. It's the operator used for compare the wanted value with the value in database
    - **value** : value to be compare with the database column
    - **id** : *optional* '{id}' (id of the foreign key)
    

Example :
```json
{
  "distribution_type": "beneficiary",
  "criteria": [
    {
      "table_string": "countrySpecific",
      "field_string": "",
      "condition_string": "=",
      "value_string": 1,
      "id_field": 1
    },
    {
      "table_string": "vulnerabilityCriteria",
      "field_string": null,
      "condition_string": false,
      "value_string": null,
      "id_field": 1
    },
    {
      "table_string": "default",
      "field_string": "gender",
      "condition_string": "=",
      "value_string": 0
    },
    {
      "table_string": "default",
      "field_string": "dateOfBirth",
      "condition_string": "<",
      "value_string": "1975-11-30"
    }
  ]
}
```




## Create
PUT(/distributions)

## Body

```json
{
  "name": "example name",
  "type": 0,
  "updated_on": "2018-04-01 11:20:13",
  "project": {
    "id": 1
  },
  "location": {
    "country_iso3": "KHM",
    "adm1": "ADMIN FAKED",
    "adm2": "ADMIN FAKED",
    "adm3": "ADMIN FAKED",
    "adm4": "ADMIN FAKED"
  },
  "selection_criteria": [
    {
      "table_string": "default",
      "target": "beneficiary",
      "field_string": "gender",
      "condition_string": "=",
      "value_string": 0
    },
    {
      "table_string": "default",
      "target": "beneficiary",
      "field_string": "dateOfBirth",
      "condition_string": "=",
      "value_string": "1975-11-30"
    }
    ],
  "commodities": [
    {
      "modality_type": {
        "id": 1
      },
      "unit": "kg",
      "value": 10
    }
  ]
}
```

## Create
PUT(/assistanceBeneficiary)

## Body

```json
{
    "distribution_data": 
    {
        "id":4
    },
    "project_beneficiary":  
    {
        "id":1
    }
}
```
