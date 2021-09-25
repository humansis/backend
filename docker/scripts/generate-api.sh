#!/bin/bash
docker run --rm \
  -v "${PWD}":/appdir openapitools/openapi-generator-cli generate \
  -i appdir/vendor/humansis/web-api/swagger.yaml \
  -g php-symfony \
  -c appdir/app/config/generator-webapi.yml \
  -o appdir/vendor/humansis/web-api-sdk

docker run --rm \
  -v "${PWD}":/appdir openapitools/openapi-generator-cli generate \
  -i appdir/vendor/humansis/vendor-app-api/swagger.yaml \
  -g php-symfony \
  -c appdir/app/config/generator-vendorapp-api.yml \
  -o appdir/vendor/humansis/vendor-app-api-sdk

docker run --rm \
  -v "${PWD}":/appdir openapitools/openapi-generator-cli generate \
  -i appdir/vendor/humansis/user-app-api/swagger.yaml \
  -g php-symfony \
  -c appdir/app/config/generator-userapp-api.yml \
  -o appdir/vendor/humansis/user-app-api-sdk

#docker run --rm \
#  -v "${PWD}":/appdir openapitools/openapi-generator-cli config-help -g php-symfony -f yamlsample
