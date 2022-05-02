#!/usr/bin/env bash
set -e
# get app version
echo "Getting application information"
./ci/get-info.sh
echo "...done"

case ${ENVIRONMENT} in
  dev|dev1|dev2|dev3) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=dev ;;
  test) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=dev ;;
  stage) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=prod ;;
  demo) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=prod ;;
  production) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=prod ;;
esac

cat docker/Dockerfile >> "$dockerfile"

# build docker image
docker build -f $dockerfile --build-arg ENV -t build_image .
docker build -f docker/nginx.Dockerfile --build-arg ENV -t nginx_build_image .
