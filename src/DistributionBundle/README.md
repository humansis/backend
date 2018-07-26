# DistributionBundle

## Configuration

### Config.yml

Example of configuration.
Inside 'criteria', you must defined every criteria that can be used to determined if a person should be a beneficiary.
**Model** => {"**key**" : "**type**"}

Type : **text**, **number**, **bool**, **date** or **entity name (with namespace)**

```yaml
distribution:
    criteria: {
        gender: boolean,
        dateOfBirth: date,
        vulnerabilityCriteria: BeneficiaryBundle\Entity\VulnerabilityCriterion,
        countrySpecific: BeneficiaryBundle\Entity\CountrySpecific
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
    "field_string": "solo parent"
  },
  {
    "table_string": "countrySpecific",
    "id": 1,
    "field_string": "ID Poor",
    "type": "Number"
  },
  {
    "table_string": "countrySpecific",
    "id": 2,
    "field_string": "WASH",
    "type": "Text"
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
vulnerabilityCriteria: BeneficiaryBundle\Entity\VulnerabilityCriterion,
``` 

In each repositories (Household and Beneficiary), we have to create a method called whereVulnerabilityCriterion.


These methods are used to add criteria on their field.





# Routes

- **POST /distribution/criteria** : Get the list of beneficiaries of household

- **POST /distribution/criteria/number** : Get the number of beneficiaries of household


### Household Distribution

- **distribution_type** : 'household' (for send distribution to every beneficiaries of a household)
OR 'beneficiary' (to send distribution to a specific beneficiary)
- **criteria** : list of criterion :
    - **table_string** : 'default' or name used in config (the key)
    - **kind_beneficiary** : 'beneficiary' (head of household) or 'dependent' (head of household dependents) or 'null' (both)
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
      "kind_beneficiary": "beneficiary",
      "field_string": null,
      "condition_string": "=",
      "value_string": 1,
      "id": 1
    },
    {
      "table_string": "vulnerabilityCriteria",
      "kind_beneficiary": "beneficiary",
      "field_string": null,
      "condition_string": "=",
      "value_string": "saluut",
      "id": 1
    },
    {
      "table_string": "default",
      "kind_beneficiary": "beneficiary",
      "field_string": "gender",
      "condition_string": "=",
      "value_string": 0
    },
    {
      "table_string": "default",
      "kind_beneficiary": "beneficiary",
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
- **kind_beneficiary** : 'beneficiary' (for send distribution to every beneficiaries of a household)
OR 'dependent' (to send distribution to a specific beneficiary) OR 'null' (both)
- **criteria** : list of criterion :
    - **field** : name of a column of Beneficiary table or 'idCountrySpecific' or 'idVulnerabilityCriterion'
    - **operator** : '=', '<', '>', '<=', '>='. It's the operator used for compare the wanted value with the value in database
    - **value** : value to be compare with the database column
    - **id** : *optional* '{id}' (id of the foreign key)
    

Example :
```json
{
  "distribution_type": "beneficiary",
  "kind_beneficiary": "beneficiary",
  "criteria": [
    {
      "table_string": "countrySpecific",
      "kind_beneficiary": "beneficiary",
      "field_string": "",
      "condition_string": "=",
      "value_string": 1,
      "id": 1
    },
    {
      "table_string": "vulnerabilityCriteria",
      "kind_beneficiary": "beneficiary",
      "field_string": "nutritional issues",
      "condition_string": "=",
      "value_string": "saluut",
      "id": 1
    },
    {
      "table_string": "default",
      "kind_beneficiary": "beneficiary",
      "field_string": "gender",
      "condition_string": "=",
      "value_string": 0
    },
    {
      "table_string": "default",
      "kind_beneficiary": "beneficiary",
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
  "selection_criteria": {
    "table_string": "TEST UNIT_TEST",
    "field_string": "TEST UNIT_TEST FAKED",
    "value_string": "TEST UNIT_TEST FAKED",
    "condition_string": "TEST UNIT_TEST FAKED",
    "kind_beneficiary": "TEST UNIT_TEST FAKED",
    "field_id": "TEST UNIT_TEST FAKED"
  },
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
PUT(/distributionBeneficiary)

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