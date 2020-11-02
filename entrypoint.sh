#!/bin/sh

DB_TIMEOUT=${DB_TIMEOUT:-45}

# Check MySQL up
MYSQL_CMD="mysql -h ${MYSQL_HOST} -P ${MYSQL_PORT:-3306} -u ${MYSQL_USER:-root}"
echo "Waiting ${DB_TIMEOUT}s for MySQL database to be ready..."
counter=1
while ! ${MYSQL_CMD} -e "show databases;" > /dev/null 2>&1; do
  sleep 1
  counter=$((counter + 1))
  if [ ${counter} -gt "${DB_TIMEOUT}" ]; then
    >&2 echo "ERROR: Failed to connect to MySQL database on $MYSQL_HOST"
    exit 1
  fi;
done
echo "MySQL database ready!"

exec "$@"
