#!/usr/bin/env sh

set -e

mysql --user="${MARIADB_USER}" --password="${MARIADB_PASSWORD}" -e "show databases;"
