#!/bin/sh

# temp dev hack, GUI will come, i promise :-)

DB=$1

[ -z "$DB" ] && exit 1

touch $DB
cat sqlite.sql | sqlite3 $DB
cat sqlite-exampledata.sql | sqlite3 $DB

