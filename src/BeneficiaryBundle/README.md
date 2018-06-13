# BeneficiaryBundle

PUT ("/beneficiaries")

```json
{
	"given_name": "nameee",
	"family_name": "family",
	"gender": "h",
	"date_of_birth": "1976-10-06",
	"updated_on": "2018-06-13",
	"household": {
		"photo": "photo.png",
		"address_street": "addr",
		"address_number": "12",
		"address_postcode": "73460",
		"livelihood": 10,
		"notes": "this is just some notes",
		"lat": "1.1544",
		"long": "120.12",
		"location": {
			"country_iso3": "FRA",
			"adm1": "Auvergne Rhone-Alpes",
			"adm2": "Savoie",
			"adm3": "Chambery",
			"adm4": "Ste Hélène sur Isère"
		}
	},
	"vulnerability_criterion": {
		"pregnant": 0,
		"lactating": 0,
		"disabled": 1,
		"nutritionalissue": 0,
		"soloParent": 0
	},
	"hh_members": [
		{
			"gender": "f",
			"date_of_birth": "1979-04-19",
			"vulnerability_criterion": {
				"pregnant": 1,
				"lactating": 0,
				"disabled": 1,
				"nutritionalissue": 0,
				"soloParent": 0
			}
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
```