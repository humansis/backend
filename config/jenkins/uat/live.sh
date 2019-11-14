#!/bin/bash -ex
exit 0
WEBSITE_URL=""
SELENIUM_URL="http://10.10.10.120:4444/wd/hub"

echo "Execute sitespeed.io"
ansible localhost -m shell -a "sitespeed.io --browsertime.selenium.url ${SELENIUM_URL} --outputFolder build/logs ${WEBSITE_URL}"
