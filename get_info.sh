#!/usr/bin/env bash

cp build_info.json.dist web/build_info.json

COMMIT=$CI_COMMIT_SHORT_SHA
TAG=`git describe --tags`
BRANCH=$CI_COMMIT_REF_NAME

if [[ $CI_COMMIT_TAG =~ ^v.*$ ]]; then
  BRANCH="master"
  TAG=$CI_COMMIT_TAG
fi

if [[ $BRANCH == "master" ]]; then
    APPVERSION=$TAG
else
    APPVERSION=$COMMIT
fi

sed -i -e "s|__COMMIT_HASH__|$COMMIT|g" web/build_info.json
sed -i -e "s|__APP_VERSION__|$APPVERSION|g" web/build_info.json
sed -i -e "s|__TAG__|$TAG|g" web/build_info.json
sed -i -e "s|__BRANCH__|$BRANCH|g" web/build_info.json

cat web/build_info.json

export VERSION=$TAG
