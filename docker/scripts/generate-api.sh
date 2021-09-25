#!/bin/bash
docker run --rm \
  -v "${PWD}":/appdir openapitools/openapi-generator-cli generate \
  -i appdir/vendor/humansis/web-api/swagger.yaml \
  -g php-symfony \
  -c appdir/app/config/generator-webapi.yml \
  -o appdir/vendor/humansis/web-api-sdk

#docker run --rm \
#  -v "${PWD}":/appdir openapitools/openapi-generator-cli config-help -g php-symfony -f yamlsample
