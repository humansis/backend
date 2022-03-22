#!/usr/bin/env bash

envsubst <  src/NewApiBundle/Resources/config/parameters.yml.template > src/NewApiBundle/Resources/config/parameters.yml
echo "Current import batch size is $IMPORT_BATCH_SIZE"