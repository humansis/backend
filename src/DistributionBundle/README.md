# DistributionBundle

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