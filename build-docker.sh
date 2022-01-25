#!/usr/bin/env bash
set -e
# get app version
echo "Getting application information"
bash get_info.sh
echo "...done"

case ${ENVIRONMENT} in
  dev) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=dev ;;
  test) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=dev ;;
  stage) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=prod ;;
  demo) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=prod ;;
  prod) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=prod ;;
esac

cat docker/Dockerfile >> "$dockerfile"

# build docker image
docker build -f $dockerfile --build-arg ENV -t build_image .
docker build -f docker/nginx.Dockerfile --build-arg ENV -t nginx_build_image .
