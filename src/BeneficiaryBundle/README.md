# BeneficiaryBundle

PUT ("/households")

```json
{
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
	"beneficiaries": [
		{
			"given_name": "nameee",
			"family_name": "family",
			"gender": "h",
			"status": 0,
			"date_of_birth": "1976-10-06",
			"updated_on": "2018-06-13 12:12:12",
			"profiles": [
				{
					"photo": "gkjghjk"
				}
			],
			"vulnerability_criterions": [
				{
					"id": 1
				}
			],
			"phones": [
				{
					"number": "020254512",
					"type": "type1"
				}
			],
			"national_ids": [
				{
					"id_number": "020254512",
					"id_type": "type1"
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
	"beneficiaries": [
		{
			"id": 1,
			"given_name": "nameee2",
			"family_name": "family",
			"gender": "h",
			"status": 0,
			"date_of_birth": "1976-10-06",
			"updated_on": "2018-06-13 12:12:12",
			"profiles": [
				{
					"id": 1,
					"photo": "gkjghjk2"
				}
			],
			"vulnerability_criterions": [
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