#!/bin/bash

FILENAME="/tmp/addpgeni3am.$USER"
#echo "-- Renumber existing aggregates:" > $FILENAME
#echo "update service_registry set service_type = 100 where service_type = 0;" >> $FILENAME

echo "-- Now create the entry for pgeni3:" >> $FILENAME
echo "insert into service_registry (service_type, service_url, service_name, service_description) values (0, 'https://www.pgeni3.gpolab.bbn.com:12369/protogeni/xmlrpc/am/2.0', 'pgeni3 AM', 'pgeni3.gpolab AM');" >> $FILENAME

psql -U portal -h localhost portal < $FILENAME

rm $FILENAME
