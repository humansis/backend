#!/usr/bin/env bash
# script for SLACK reporting

export STATUS=$1
export ENVIRONMENT=$2
export APP_VERSION=$( [ -z ${CI_COMMIT_TAG} ] && echo "$CI_COMMIT_REF_NAME" | sed -e 's/release\/v//g' -e 's/release\///g' || echo ${CI_COMMIT_TAG/v/} )

export ICON_EMOJI=":postal_horn:"


function print_usage {
  echo "This is a script for SLACK reporting of deploy status in Gitlab."
  echo "Currently contains 3 job status options (start, failed, success)."
  echo "Prerequisite for this script is working SLACK_WEBHOOK_URL set in environment variables"
  echo "Usage: ./report-slack.sh [job_status] [environement]"
}

function report_starting {
    export COLOR="warning"
    export STATE="starting"
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
}

function report_failed {
    export COLOR="danger"
    export STATE="FAILED"
    curl -X POST --data-urlencode "payload=
    {
      \"username\": \"${USERNAME}\",
      \"icon_emoji\": \"${ICON_EMOJI}\",
      \"channel\": \"${CHANNEL}\",
      \"attachments\": [
        {
          \"fallback\": \"${CI_PROJECT_TITLE} deployment to ${ENVIRONMENT} ${STATE}\",
          \"color\": \"${COLOR}\",
          \"pretext\": \":warning: *${CI_PROJECT_TITLE^^}* deployment to *${ENVIRONMENT}* has *${STATE}*\\n <${CI_PIPELINE_URL}|View pipeline>\",
          \"title\": \"Details\",
          \"text\": \"${ENVIRONMENT} deployment of ${CI_PROJECT_TITLE} v${APP_VERSION} ${STATE} - commit <${CI_PROJECT_URL}/-/commit/${CI_COMMIT_SHA}|${CI_COMMIT_SHORT_SHA}>\",
          \"footer\": \"Pipeline ID: <${CI_PIPELINE_URL}|${CI_PIPELINE_ID}>\",
          \"ts\": $(date +%s)
        }
      ]
    }" ${SLACK_WEBHOOK_URL}
}

function report_canceled {
    export COLOR="warning"
    export STATE="canceled"
    curl -X POST --data-urlencode "payload=
    {
      \"username\": \"${USERNAME}\",
      \"icon_emoji\": \"${ICON_EMOJI}\",
      \"channel\": \"${CHANNEL}\",
      \"attachments\": [
        {
          \"fallback\": \"${CI_PROJECT_TITLE} deployment to ${ENVIRONMENT} ${STATE}\",
          \"color\": \"${COLOR}\",
          \"pretext\": \":warning: *${CI_PROJECT_TITLE^^}* deployment to *${ENVIRONMENT}* has been *${STATE}*\\n <${CI_PIPELINE_URL}|View pipeline>\",
          \"title\": \"Details\",
          \"text\": \"${ENVIRONMENT} deployment of ${CI_PROJECT_TITLE} v${APP_VERSION} ${STATE} - commit <${CI_PROJECT_URL}/-/commit/${CI_COMMIT_SHA}|${CI_COMMIT_SHORT_SHA}>\",
          \"footer\": \"Pipeline ID: <${CI_PIPELINE_URL}|${CI_PIPELINE_ID}>\",
          \"ts\": $(date +%s)
        }
      ]
    }" ${SLACK_WEBHOOK_URL}
}

function report_success {
    export COLOR="good"
    export STATE="successful"
    curl -X POST --data-urlencode "payload=
    {
      \"username\": \"${USERNAME}\",
      \"icon_emoji\": \"${ICON_EMOJI}\",
      \"channel\": \"${CHANNEL}\",
      \"attachments\": [
        {
          \"fallback\": \"${CI_PROJECT_TITLE} deployment to ${ENVIRONMENT} ${STATE}\",
          \"color\": \"${COLOR}\",
          \"pretext\": \":white_check_mark: *${CI_PROJECT_TITLE^^}* has been deployed to *${ENVIRONMENT}* *successfully*\",
          \"title\": \"Details\",
          \"text\": \"${ENVIRONMENT} deployment of ${CI_PROJECT_TITLE} v${APP_VERSION} ${STATE} - commit <${CI_PROJECT_URL}/-/commit/${CI_COMMIT_SHA}|${CI_COMMIT_SHORT_SHA}>\",
          \"footer\": \"Pipeline ID: <${CI_PIPELINE_URL}|${CI_PIPELINE_ID}>\",
          \"ts\": $(date +%s)
        }
      ]
    }" ${SLACK_WEBHOOK_URL}
}

case "${STATUS}" in
    start) report_starting ;;
    failed) report_failed ;;
    canceled) report_failed ;;
    success) report_success ;;
    *) print_usage
        exit 1 ;;
esac
