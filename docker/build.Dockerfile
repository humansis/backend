FROM docker:dind

RUN mkdir /build

WORKDIR /build

RUN apk update && \
  apk add bash git openssh python3 py3-pip rsync mysql-client && \
  pip3 install --upgrade pip && \
  pip3 install awscli && \
  rm -rf /var/cache/apk/*
