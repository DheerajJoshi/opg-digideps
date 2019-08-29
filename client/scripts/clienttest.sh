#!/bin/bash

# generate behat.yml
confd -onetime -backend env

# export database config so psql CLI can be used
export PGHOST=${API_DATABASE_HOSTNAME:=postgres}
export PGPASSWORD=${API_DATABASE_PASSWORD:=api}
export PGDATABASE=${API_DATABASE_NAME:=api}
export PGUSER=${API_DATABASE_USERNAME:=api}

# wait for frontend to come up
waitforit -address=$FRONTEND_API_URL/manage/availability -timeout=$TIMEOUT -insecure

# create directories used by tests
mkdir -p /var/log/app
mkdir -p /tmp/behat

# phpunit
bin/phpunit -c tests/phpunit/

# behat
bin/behat --config=tests/behat/behat.yml --profile=${PROFILE:=headless} --stop-on-failure ${1}
