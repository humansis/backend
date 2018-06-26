# BeneficiaryBundle

PUT ("/households")

```json
{
	"photo": "photo.png",
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
			"profile": [
				{
					"photo": 1
				}
			],
			"vulnerability_criterions": [
				{
					"id": 1,
					"value": "1"
				}
			],
			"phones": [
				{
					"number": "020254512",
					"type": "type1"
				}
			],
			"national_id": [
				{
					"id_number": "020254512",
					"id_type": "type1"
				}
			]
		}
	]
}
```