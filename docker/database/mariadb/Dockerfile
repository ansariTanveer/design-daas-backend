ARG MARIADB_VERSION

FROM mariadb:${MARIADB_VERSION}

COPY defaults.cnf /etc/mysql/mariadb.conf.d/default.cnf
COPY healthcheck.sh /usr/local/bin/healthcheck.sh

RUN chmod +x /usr/local/bin/healthcheck.sh

HEALTHCHECK --start-period=60s --interval=1s CMD healthcheck.sh
