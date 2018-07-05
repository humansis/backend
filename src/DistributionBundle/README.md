# DistributionBundle


## Criteria for distribution


- **distribution_type** : 'household' (for send distribution to every beneficiaries of a household)
OR 'beneficiary' (to send distribution to a specific beneficiary)
- **criteria** : list of criterion :
    - **group** : 'beneficiary' (head of household) or 'dependent' (head of household dependents) or 'null' (both)
    - **field** : name of a column of Beneficiary table or 'idCountrySpecific' or 'idVulnerabilityCriterion'
    - **operator** : '=', '<', '>', '<=', '>='. It's the operator used for compare the wanted value with the value in database
    - **value** : value to be compare with the database column
    - **id** : *optional* '{id}' (id of the foreign key)
    
    
If you want to get create a distribution for full household :

```json
{
	"distribution_type": "household",
	"criteria": [
		{
			"group": "beneficiary",
			"field": "gender",
			"operator": "=",
			"value": "1"
		},
		{
			"group": "dependent",
			"field": "dateOfBirth",
			"operator": ">",
			"value": "1994-10-25"
		},
		{
			"group": "beneficiary",
			"field": "idVulnerabilityCriterion",
			"id": 2,
			"operator": null,
			"value": null
		},
		{
			"group": null,
			"field": "idCountrySpecific",
			"id": 1,
			"operator": "<=",
			"value": 3
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