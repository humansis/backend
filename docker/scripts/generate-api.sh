#!/bin/bash
docker run --rm \
  -v "${PWD}":/appdir openapitools/openapi-generator-cli generate \
  -i appdir/vendor/humansis/web-api/swagger.yaml \
  -g php-symfony \
  -c appdir/app/config/generator-webapi.yml \
  -o appdir/src/HumansisWebApiBundle

docker run --rm \
  -v "${PWD}":/appdir openapitools/openapi-generator-cli generate \
  -i appdir/vendor/humansis/vendor-app-api/swagger.yaml \
  -g php-symfony \
  -c appdir/app/config/generator-vendorapp-api.yml \
  -o appdir/src/HumansisVendorAppApiBundle

docker run --rm \
  -v "${PWD}":/appdir openapitools/openapi-generator-cli generate \
  -i appdir/vendor/humansis/user-app-api/swagger.yaml \
  -g php-symfony \
  -c appdir/app/config/generator-userapp-api.yml \
  -o appdir/src/HumansisUserAppApiBundle

docker run --rm \
  -v "${PWD}":/appdir openapitools/openapi-generator-cli generate \
  -i appdir/vendor/humansis/vendor-app-legacy-api/swagger.yaml \
  -g php-symfony \
  -c appdir/app/config/generator-vendorapp-legacy-api.yml \
  -o appdir/src/HumansisLegacyVendorAppApiBundle

docker run --rm \
  -v "${PWD}":/appdir openapitools/openapi-generator-cli generate \
  -i appdir/vendor/humansis/user-app-legacy-api/swagger.yaml \
  -g php-symfony \
  -c appdir/app/config/generator-userapp-legacy-api.yml \
  -o appdir/src/HumansisLegacyUserAppApiBundle
