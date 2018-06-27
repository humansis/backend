# DistributionBundle

## Create
PUT(/distributions)

## Body

```json
{
    "name": "example name",
    "updateOn": "2018-04-01 11:20:13",
    "location": {
        "country_iso3": "FRA",
		"adm1": "Auvergne Rhone-Alpes",
		"adm2": "Savoie",
		"adm3": "Chambery",
		"adm4": "Ste Hélène sur Isère"
    },
    "project":  
    [
        {
            "name": "example name",
            "start_date": "2018-02-01",
            "end_date": "2018-03-03",
            "number_of_households": 2,
            "value": 5,
            "notes": "This is a note",
            "iso3": "FR",
            "donors": 
            [
                {
                    "fullname": "example name",
                    "shortname": "example name",
                    "date_added": "2018-04-01 11:20:13",
                    "notes": "This is a note"
                }
            ],
            "sectors": 
            [
                {
                    "name": "example name"
                }
            ]
        }
    ],
    "selectionCriteria":  [
        {
            "tableString": "beneficiary",
            "fieldString": "gender",
            "valueString": "f",
            "conditionString": "="
        }
    ],
}
```
