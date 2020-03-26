#!/bin/bash
cd "$(dirname "$0")"
php -S localhost:8000 -t server &
PROC_ID=$!
//echo $PROC_ID
sleep 1
echo -n 'user1 invalido - '
curl -s localhost:8000/ws/status -u user:user > /dev/null
echo -n 'user1 invalido - '
curl -s     localhost:8000/invalido -u user:user > /dev/null
echo -n 'user1 ok - '
curl -s localhost:8000/minhaclasse1 -u user1:user > /dev/null
echo -n 'user1 invalido - '
curl -s localhost:8000/minhaclasse2 -u user1:user > /dev/null
echo -n 'user2 ok - '
curl -s localhost:8000/minhaclasse2 -u user2:user > /dev/null
echo -n 'admin ok - '
curl -s localhost:8000/ws/ -u admin:admin > /dev/null
echo -n 'gerente ok - '
curl -s localhost:8000/minhaclasse2 -u gerente:gerente > /dev/null
echo -n 'gerente invalido - '
curl -s localhost:8000/ws/status -u gerente:gerente > /dev/null

pkill php
exit 0
