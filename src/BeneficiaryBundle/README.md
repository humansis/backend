# BeneficiaryBundle

PUT ("/households")

```json
{
	"location": {
		"country_iso3": "FRA"
	},
	"project": {
		"id": 1
	},
	"address_street": "addr",
	"address_number": "12",
	"address_postcode": "73460",
	"livelihood": 10,
	"notes": "this is just some notes",
	"lat": "1.1544",
	"long": "120.12",
	"beneficiaries": [
		{
			"given_name": "nameee",
			"family_name": "family",
			"gender": "h",
			"date_of_birth": "1976-10-06",
			"updated_on": "2018-06-13",
			"profile": [
				{
					"photo": "photo.png"
				}
			],
			"vulnerability_criterion": [
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