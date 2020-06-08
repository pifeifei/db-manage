#!/bin/sh

db_ver=`git describe --tags HEAD`
docker build -t db-manage:$db_ver `dirname $0`/../
