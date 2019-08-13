#!/bin/bash

# generate configuration files
confd -onetime -backend env

# wait for frontend to come up
waitforit -address=$FRONTEND_API_URL/manage/availability -timeout=$TIMEOUT -insecure

# create directories used by tests
mkdir -p /var/log/app
mkdir -p /tmp/behat

# phpunit
bin/phpunit -c tests/phpunit/

# behat
bin/behat --config=tests/behat/behat.yml --profile=${PROFILE:=headless} --stop-on-failure ${1}
