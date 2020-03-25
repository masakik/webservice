#!/bin/bash
cd "$(dirname "$0")"
php -S localhost:8000 -t server &
PROC_ID=$!
//echo $PROC_ID
sleep 1
echo 'user1 invalido'
curl localhost:8000/ws/status -u user:user
echo 'user1 invalido'
curl localhost:8000/invalido -u user:user
echo 'user1 ok'
curl localhost:8000/minhaclasse1 -u user1:user
echo 'user1 invalido'
curl localhost:8000/minhaclasse2 -u user1:user
echo 'user2 ok'
curl localhost:8000/minhaclasse2 -u user2:user
echo 'admin ok'
curl localhost:8000/ws/ -u admin:admin
echo 'usuario3 ok'
curl localhost:8000/minhaclasse2 -u user3:user
echo 'usuario3 invalido'
curl localhost:8000/ws/status -u user3:user

pkill php
exit 0
