#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
if [ -f .env ]; then set -a; . ./.env; set +a; fi
name=team-task-manager-mysql
image='mysql:8.4@sha256:0744ee5ef89ce6ccfa13de3e579fe6b9e27f93dd70da9c06d2c908b1b193fb8d'
case "${1:-start}" in
  start)
    if docker container inspect "$name" >/dev/null 2>&1; then
      docker start "$name"
    else
      docker run -d --name "$name" --restart unless-stopped \
        -p "127.0.0.1:${MYSQL_HOST_PORT:-3306}:3306" \
        -e MYSQL_DATABASE="${MYSQL_DATABASE:-team_tasks}" \
        -e MYSQL_USER="${MYSQL_USER:-team_tasks}" \
        -e MYSQL_PASSWORD="${MYSQL_PASSWORD:-local-password}" \
        -e MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-local-root-password}" \
        -v "${MYSQL_VOLUME:-team-task-manager_mysql_data}:/var/lib/mysql" \
        --health-cmd='MYSQL_PWD="$MYSQL_PASSWORD" mysqladmin ping -h 127.0.0.1 -u"$MYSQL_USER" --silent' \
        --health-interval=5s --health-timeout=5s --health-retries=20 "$image"
    fi
    ;;
  stop) docker stop "$name" ;;
  test-db)
    docker exec "$name" sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "CREATE DATABASE IF NOT EXISTS team_tasks_test; GRANT ALL ON team_tasks_test.* TO team_tasks;"'
    ;;
  *) echo 'Usage: sh scripts/database.sh [start|stop|test-db]' >&2; exit 1 ;;
esac
