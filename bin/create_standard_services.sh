#!/bin/bash

FILENAME="/tmp/standard_services.$USER"
# zero out the file in case it's there and has contents.
/usr/bin/truncate -s 0 $FILENAME

BASEDIR=/usr/share/geni-ch

FQDN=`hostname -f`

ADMIN_EMAIL=portal-dev-admin@gpolab.bbn.com

SVC_INSERT="insert into service_registry (service_type, service_url, service_cert) values"

# Overwrite existing certs?
FORCE=0
NEW_CERTS=0

args=`getopt f $*`
# you should not use `getopt abo: "$@"` since that would parse
# the arguments differently from what the set command below does.
if [ $? != 0 ]
then
    echo 'Usage: create_standard_services.sh [-f]'
    exit 2
fi
set -- $args

for i
do
    case "$i"
        in
        -f)
            FORCE=1
            shift;;
        --)
            shift; break;;
    esac
done

function add_service {
    SVC_NAME=$1
    SVC_EMAIL=$2
    SVC_CA=$3

    SVC_CERT="${BASEDIR}/${SVC_NAME}/${SVC_NAME}-cert.pem"
    if [ ! -f "${SVC_CERT}" -o $FORCE == 1 ]; then
        echo "Creating ${SVC_NAME} certificate at ${SVC_CERT}"
        SVC_KEY="${BASEDIR}/${SVC_NAME}/${SVC_NAME}-key.pem"
        SVC_REQ="${BASEDIR}/${SVC_NAME}/${SVC_NAME}-req.pem"
        ./mk-auth-req "${SVC_KEY}" "${SVC_REQ}" ${SVC_NAME} ${SVC_EMAIL}
        ./sign-auth-req "${SVC_REQ}" "${SVC_CERT}" ${SVC_NAME} ${SVC_CA}
        NEW_CERTS=1
    else
        echo "${SVC_NAME} certificate already exists (use '-f' to overwrite)"
    fi

    if [ ! -z "$4" -a ! -z "$5" ]; then
        # insert into database
        SVC_TYPE=$4
        SVC_URL=$5
        echo "${SVC_INSERT} (${SVC_TYPE}, '$SVC_URL', '${SVC_CERT}');" >> $FILENAME
    fi
}

# Add the root cert location
echo "${SVC_INSERT} (7, '', '${BASEDIR}/CA/cacert.pem');" >> $FILENAME

# SR - not a CA, not in the database
add_service sr ${ADMIN_EMAIL} NO
add_service sa ${ADMIN_EMAIL} CA 1 "https://${FQDN}/sa/sa_controller.php"
add_service pa ${ADMIN_EMAIL} NO 2 "https://${FQDN}/pa/pa_controller.php"
add_service ma ${ADMIN_EMAIL} CA 3 "https://${FQDN}/ma/ma_controller.php"
add_service logging ${ADMIN_EMAIL} NO 5 "https://${FQDN}/logging/logging_controller.php"
add_service cs ${ADMIN_EMAIL} NO 6 "https://${FQDN}/cs/cs_controller.php"
add_service portal ${ADMIN_EMAIL} NO

# Link the MA cert to the trusted_roots for pgch
TRUSTED_MA="${BASEDIR}/portal/gcf.d/trusted_roots/ma-cert.pem"
if [ ! -L ${TRUSTED_MA} ]; then
    /bin/ln -s "${BASEDIR}/ma-cert.pem" "${TRUSTED_MA}"
fi

#----------------------------------------------------------------------
# A local aggregate manager
#----------------------------------------------------------------------
SVC_NAME="am"
SVC_EMAIL="${ADMIN_EMAIL}"
SVC_KEY="${BASEDIR}/portal/gcf.d/${SVC_NAME}-key.pem"
SVC_REQ="${BASEDIR}/portal/gcf.d/${SVC_NAME}-req.pem"
SVC_CERT="${BASEDIR}/portal/gcf.d/${SVC_NAME}-cert.pem"
SVC_CA=NO
if [ ! -f "${SVC_CERT}" -o $FORCE == 1 ]; then
    ./mk-auth-req "${SVC_KEY}" "${SVC_REQ}" ${SVC_NAME} ${SVC_EMAIL}
    ./sign-auth-req "${SVC_REQ}" "${SVC_CERT}" ${SVC_NAME} ${SVC_CA}
fi
echo "insert into service_registry (service_type, service_url, service_name, service_description, service_cert) values (0, 'https://localhost:8001/', 'Local gcf AM', 'Empty AM', '${SVC_CERT}');" >> $FILENAME

#----------------------------------------------------------------------
# Write the accumulated SQL to the database
#----------------------------------------------------------------------
sudo -u $SUDO_USER psql -U portal -h localhost portal < $FILENAME

# Delete the temp file
rm $FILENAME

if [ $NEW_CERTS == 1 ]; then
    echo ""
    echo " *** Remember to email Tom to ask him to install the new MA and CA certs on pgeni3 *** "
fi
