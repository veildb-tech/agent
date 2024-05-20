#!/bin/sh
set -e

if [ -z "$(ls -A '/app/vendor/' 2>/dev/null)" ]; then
    composer install --prefer-dist --no-progress --no-interaction
fi

if [ ! -d "/app/var/server" ]; then
    mkdir -p /app/var/server
fi

if [ ! -f "/etc/apache2/certs/ssl.crt" ] || [ ! -f "/etc/apache2/certs/ssl.key" ]; then
    openssl req -x509 -out /etc/apache2/certs/ssl.crt -keyout /etc/apache2/certs/ssl.key -newkey rsa:2048 -nodes -sha256  -new -subj "/C=UA/CN=${SERVER_URL}" \
                      -addext "subjectAltName = DNS:${SERVER_URL}, DNS:${SERVER_URL}" \
                      -addext "certificatePolicies = 1.2.3.4"
fi

#HTTPDUSER=$(ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1)

HTTPDUSER=www-data
setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX /app/var
setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX /app/var


exec docker-php-entrypoint "$@"