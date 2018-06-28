BMS API
==============

### Infos

Set the header 'country' of your request, with ISO3 code, if you need something which depends on a specific country.
Header 'country' contains the ISO3 of a country. A listener will add it to the body of the request ('__country')
before that the controller process.