#!/usr/bin/env bash
set -e
USER=$1
PASSWORD=$2
# get app version
echo "Getting application information"
./ci/get-info.sh
echo "...done"

case ${ENVIRONMENT} in
  dev|dev1|dev2|dev3) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=dev ;;
  test) export dockerfile="docker/prod/php/Dockerfile";
    sed -i -e "s|^memory_limit = 256M|memory_limit = 4096M|g" docker/dev/php/php.ini # TEMPORARY, REMOVE AFTER instance is downgraded again
    export ENV=dev ;;
  stage|stage2) export dockerfile="docker/prod/php/Dockerfile";
    sed -i -e "s|^memory_limit = 256M|memory_limit = 4096M|g" docker/prod/php/php.ini # TEMPORARY, REMOVE AFTER instance is downgraded again
    export ENV=prod ;;
  demo) export dockerfile="docker/prod/php/Dockerfile";
    export ENV=prod ;;
  production) export dockerfile="docker/prod/php/Dockerfile";
    sed -i -e "s|^memory_limit = 256M|memory_limit = 4096M|g" docker/prod/php/php.ini # TEMPORARY, REMOVE AFTER instance is downgraded again
    export ENV=prod ;;
esac

cat docker/Dockerfile >> "$dockerfile"

docker login -u ${USER} -p ${PASSWORD} ${REPOSITORY_URL}
# select builder
docker buildx use pin
# build docker image
if [[ ${ENVIRONMENT} == "production" ]]; then
  VERSION=`git describe --tags`

  if [[ $CI_COMMIT_TAG =~ ^v.*$ ]]; then
    VERSION=$CI_COMMIT_TAG
  fi
  docker buildx build -f $dockerfile --build-arg ENV --pull --push --platform linux/amd64,linux/arm64/v8 \
    -t ${REPOSITORY_URL}/${REPOSITORY_NAME}/${IMAGE_NAME}:${ENVIRONMENT} \
    -t ${REPOSITORY_URL}/${REPOSITORY_NAME}/${IMAGE_NAME}:latest \
    -t ${REPOSITORY_URL}/${REPOSITORY_NAME}/${IMAGE_NAME}:${VERSION} .
  docker buildx build -f docker/nginx.Dockerfile --build-arg ENV --pull --push --platform linux/amd64,linux/arm64/v8 \
    -t ${REPOSITORY_URL}/${REPOSITORY_NAME}/${NGINX_IMAGE_NAME}:${ENVIRONMENT} \
    -t ${REPOSITORY_URL}/${REPOSITORY_NAME}/${NGINX_IMAGE_NAME}:latest \
    -t ${REPOSITORY_URL}/${REPOSITORY_NAME}/${NGINX_IMAGE_NAME}:${VERSION} .
else
  docker buildx build -f $dockerfile --build-arg ENV --pull --push --platform linux/amd64,linux/arm64/v8 \
    -t ${REPOSITORY_URL}/${REPOSITORY_NAME}/${IMAGE_NAME}:${ENVIRONMENT} .
  docker buildx build -f docker/nginx.Dockerfile --build-arg ENV --pull --push --platform linux/amd64,linux/arm64/v8 \
    -t ${REPOSITORY_URL}/${REPOSITORY_NAME}/${NGINX_IMAGE_NAME}:${ENVIRONMENT} .
fi
