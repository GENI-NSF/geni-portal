#/bin/bash

FILENAME="/tmp/standard_services.$USER"
#echo $FILENAME

echo "insert into service_registry (service_type, service_url) values (1, 'https://$HOSTNAME/sa/sa_controller.php');" > $FILENAME
echo "insert into service_registry (service_type, service_url) values (2, 'https://$HOSTNAME/pa/pa_controller.php');" >> $FILENAME
echo "insert into service_registry (service_type, service_url) values (3, 'https://$HOSTNAME/ma/ma_controller.php');" >> $FILENAME
echo "insert into service_registry (service_type, service_url) values (6, 'https://$HOSTNAME/cs/cs_controller.php');" >> $FILENAME

psql -U portal -h localhost portal < $FILENAME

rm $FILENAME

