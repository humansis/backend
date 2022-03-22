#!/usr/bin/env bash

COLOR="warning"
STATE="starting"
CHANNEL="#pin_deployment"
USERNAME="PIN Deploy"
ICON_EMOJI=":postal_horn:"

apk update && apk upgrade && apk add curl

curl -X POST --data-urlencode "payload=
    {
      \"username\": \"${USERNAME}\",
      \"icon_emoji\": \"${ICON_EMOJI}\",
      \"channel\": \"${CHANNEL}\",
      \"attachments\": [
        {
          \"fallback\": \"${CI_PROJECT_TITLE} deployment to ${ENVIRONMENT} ${STATE}\",
          \"color\": \"${COLOR}\",
          \"pretext\": \":mega: *${CI_PROJECT_TITLE^^}* deployment to *${ENVIRONMENT}* is *${STATE}* triggered by ${GITLAB_USER_NAME}\",
          \"title\": \"Details\",
          \"text\": \"${ENVIRONMENT} deployment of ${CI_PROJECT_TITLE} v${APP_VERSION} ${STATE} - commit <${CI_PROJECT_URL}/-/commit/${CI_COMMIT_SHA}|${CI_COMMIT_SHORT_SHA}>\",
          \"footer\": \"Pipeline ID: <${CI_PIPELINE_URL}|${CI_PIPELINE_ID}>\",
          \"ts\": $(date +%s)
        }
      ]
    }" ${SLACK_WEBHOOK_URL}