#!/usr/bin/env bash
set -e

USER=$1
PASSWORD=$2

VERSION=`git describe --tags`

if [[ $CI_COMMIT_TAG =~ ^v.*$ ]]; then
  VERSION=$CI_COMMIT_TAG
fi

docker login -u ${USER} -p ${PASSWORD} ${REPOSITORY_URL}

docker tag build_image ${REPOSITORY_URL}/${REPOSITORY_NAME}/${IMAGE_NAME}:${ENVIRONMENT}
docker push ${REPOSITORY_URL}/${REPOSITORY_NAME}/${IMAGE_NAME}:${ENVIRONMENT}
docker tag nginx_build_image ${REPOSITORY_URL}/${REPOSITORY_NAME}/${NGINX_IMAGE_NAME}:${ENVIRONMENT}
docker push ${REPOSITORY_URL}/${REPOSITORY_NAME}/${NGINX_IMAGE_NAME}:${ENVIRONMENT}

if [[ ${ENVIRONMENT} == "production" ]]; then
  docker tag build_image ${REPOSITORY_URL}/${REPOSITORY_NAME}/${IMAGE_NAME}:${VERSION}
  docker push ${REPOSITORY_URL}/${REPOSITORY_NAME}/${IMAGE_NAME}:${VERSION}
  docker tag build_image ${REPOSITORY_URL}/${REPOSITORY_NAME}/${IMAGE_NAME}:latest
  docker push ${REPOSITORY_URL}/${REPOSITORY_NAME}/${IMAGE_NAME}:latest
  docker tag nginx_build_image ${REPOSITORY_URL}/${REPOSITORY_NAME}/${NGINX_IMAGE_NAME}:${VERSION}
  docker push ${REPOSITORY_URL}/${REPOSITORY_NAME}/${NGINX_IMAGE_NAME}:${VERSION}
  docker tag nginx_build_image ${REPOSITORY_URL}/${REPOSITORY_NAME}/${NGINX_IMAGE_NAME}:latest
  docker push ${REPOSITORY_URL}/${REPOSITORY_NAME}/${NGINX_IMAGE_NAME}:latest
fi
