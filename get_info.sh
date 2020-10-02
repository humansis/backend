#!/usr/bin/env bash

cp build_info.json.dist web/build_info.json

COMMIT=`git rev-parse --short HEAD`
TAG=`git describe --tags`

if [[ $TAG =~ ^v.*$ ]]; then
  BRANCH="master"
elif [[ $TAG =~ ^deploy.*$ ]]; then
  BRANCH="dev deploy"
else
  BRANCH=`git symbolic-ref HEAD | cut -d/ -f3-`
fi

if [[ $BRANCH == "master" ]]; then
    APPVERSION=$TAG
elif [[ $BRANCH == "dev" ]]; then
    APPVERSION=$COMMIT
elif [[ $BRANCH =~ ^release\/.*$ ]]; then
    APPVERSION=$COMMIT
elif [[ $TAG =~ ^deploy.*$ ]]; then
    APPVERSION=$COMMIT
else
    APPVERSION=$BRANCH
fi

sed -i -e "s|__COMMIT_HASH__|$COMMIT|g" web/build_info.json
sed -i -e "s|__APP_VERSION__|$APPVERSION|g" web/build_info.json
sed -i -e "s|__TAG__|$TAG|g" web/build_info.json
sed -i -e "s|__BRANCH__|$BRANCH|g" web/build_info.json

cat web/build_info.json
