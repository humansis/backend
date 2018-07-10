# BeneficiaryBundle

## INFOS

### Beneficiary

- **Status** : 1 means that the beneficiary is the head of household, 0 in the other case


### CSV 

In column with multiple information (like phones, you can have multiple phone numbers), with a ';' as separator.


## ROUTES


PUT ("/households")

```json
{
    "project": 1,
	"address_street": "addr",
	"address_number": "12",
	"address_postcode": "73460",
	"livelihood": 10,
	"notes": "this is just some notes",
	"latitude": "1.1544",
	"longitude": "120.12",
	"location": {
		"country_iso3": "FRA",
		"adm1": "Auvergne Rhone-Alpes",
		"adm2": "Savoie",
		"adm3": "Chambery",
		"adm4": "Ste Hélène sur Isère"
	},
	"country_specific_answers": [
		{
			"answer": "my answer",
			"country_specific": {
				"id": 1
			}
		}
	],
	"beneficiaries": [
		{
			"given_name": "name",
			"family_name": "family",
			"gender": "m",
			"status": 0,
			"date_of_birth": "1976-10-06",
			"updated_on": "2018-06-13 12:12:12",
			"profile": {
				"photo": "photo1"
			},
			"vulnerability_criteria": [
				{
					"id": 1
				}
			],
			"phones": [
				{
					"number": "0202514512",
					"type": "type1"
				}
			],
			"national_ids": [
				{
					"id_number": "1212",
					"id_type": "type1"
				}
			]
		},
		{
			"given_name": "name222",
			"family_name": "family2222",
			"gender": "f",
			"status": 0,
			"date_of_birth": "1976-10-06",
			"updated_on": "2018-06-13 12:12:12",
			"profile": {
				"photo": "photo2"
			},
			"vulnerability_criteria": [
				{
					"id": 1
				}
			],
			"phones": [
				{
					"number": "5545544584",
					"type": "type2"
				}
			],
			"national_ids": [
				{
					"id_number": "2323",
					"id_type": "type2"
				}
			]
		}
	]
}
```

POST ("/households/{id_household}")
```json
{
	"address_street": "add$*r2",
	"address_number": "12",
	"address_postcode": "73460",
	"livelihood": 10,
	"notes": "this is just some notes",
	"latitude": "1.1544",
	"longitude": "120.12",
	"location": {
		"country_iso3": "FRA",
		"adm1": "Auvergne Rhone-Alpes",
		"adm2": "Savoie",
		"adm3": "Chambery",
		"adm4": "Ste Hélène sur Isère"
	},
	"country_specific_answers": [
		{
			"answer": "my answer",
			"country_specific": {
				"id": 1
			}
		}
	],
	"beneficiaries": [
		{
			"id": 1,
			"given_name": "nameee2",
			"family_name": "family",
			"gender": "h",
			"status": 0,
			"date_of_birth": "1976-10-06",
			"updated_on": "2018-06-13 12:12:12",
			"profile": {
				"photo": "gkjghjk2"
			},
			"vulnerability_criteria": [
				{
					"id": 1
				}
			],
			"phones": [
				{
					"id": 1,
					"number": "020254512",
					"type": "type12"
				}
			],
			"national_ids": [
				{
					"id": 1,
					"id_number": "020254512",
					"id_type": "type12"
				}
			]
		}
	]
}
```


PUT ("/country_specifics")
```json
{
    "field": "field",
    "type": "type"
}
```