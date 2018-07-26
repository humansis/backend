# DistributionBundle


## Criteria for distribution

# Configuration

### Config.yml


### Repository


### Retriever




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
    - **id_field** : *optional* '{id}' (id of the foreign key)
    
    
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
      "id_field": 1
    },
    {
      "table_string": "vulnerabilityCriteria",
      "kind_beneficiary": "beneficiary",
      "field_string": null,
      "condition_string": "=",
      "value_string": "saluut",
      "id_field": 1
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
      "id_field": 1
    },
    {
      "table_string": "vulnerabilityCriteria",
      "kind_beneficiary": "beneficiary",
      "field_string": "nutritional issues",
      "condition_string": "=",
      "value_string": "saluut",
      "id_field": 1
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
    "updated_on": "2018-04-01 11:20:13",
    "location": 
    {
        "id":2
    },
    "project":  
    {
        "id":1
    },
    "selection_criteria": 
    {
        "id":1
    }
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