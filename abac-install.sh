#!/bin/bash

check_errs()
{
  # Function. Parameter 1 is the return code
  # Para. 2 is text to display on failure.
  if [ "${1}" -ne "0" ]; then
    echo "ERROR # ${1} : ${2}"
    # as a bonus, make our script exit with the right error code.
    exit ${1}
  fi
}

#----------------------------------------------------------------------
# Ensure running as root
#----------------------------------------------------------------------
#if [[ $EUID -ne 0 ]]; then
#    echo "This script must be run as root" 1>&2
#    exit 1
#fi


#----------------------------------------------------------------------
# Install package dependencies
#----------------------------------------------------------------------
APT_PKGS="python-dev g++ libssl-dev libgmp3c2 libgmp3-dev git-core"
APT_PKGS="${APT_PKGS} libtool automake swig autoconf-archive"
APT_PKGS="${APT_PKGS} libio-socket-ssl-perl libhttp-daemon-ssl-perl"
APT_PKGS="${APT_PKGS} librpc-xml-perl"

/usr/bin/sudo /usr/bin/apt-get update
check_errs $? "apt-get failed to update"

/usr/bin/sudo /usr/bin/apt-get install -y ${APT_PKGS}
check_errs $? "apt-get failed to install packages"

# Download and build strongswan
/usr/bin/wget http://download.strongswan.org/strongswan-4.4.0.tar.bz2
check_errs $? "wget strongswan failed"

/bin/tar xjvf strongswan-4.4.0.tar.bz2
check_errs $? "untar strongswan failed"

pushd strongswan-4.4.0
check_errs $? "strongswan dir does not exist"

STRONGSWAN_SRC_DIR=`pwd`
./configure --enable-monolithic --disable-gmp --enable-openssl
check_errs $? "strongswan configure failed"

cd src/libstrongswan
check_errs $? "strongswan src/libstrongswan does not exist"

/usr/bin/make
check_errs $? "strongswan make failed"

/usr/bin/sudo /usr/bin/make install
check_errs $? "strongswan make install failed"

popd
check_errs $? "popd failed"

# git clone abac
/usr/bin/git clone git://abac.deterlab.net/abac.git
check_errs $? "git clone abac failed"

# build abac
pushd abac
check_errs $? "abac directory does not exist"

./autogen.sh
check_errs $? "abac autogen.sh failed"

./configure --with-strongswan=$STRONGSWAN_SRC_DIR
check_errs $? "abac configure failed"

/usr/bin/make
check_errs $? "abac make failed"

/usr/bin/sudo /usr/bin/make install
check_errs $? "abac make install failed"

popd
check_errs $? "popd 2 failed"

# Add /usr/local/lib to the LD_LIBRARY_PATH
#/bin/cat > /tmp/abac.sh <<EOF
#LD_LIBRARY_PATH="\${LD_LIBRARY_PATH}":/usr/local/lib
#export LD_LIBRARY_PATH
#EOF
#/bin/chmod 0644 /tmp/abac.sh
#sudo /bin/chown root.root /tmp/abac.sh
#sudo /bin/mv /tmp/abac.sh /etc/profile.d/abac.sh

# Cache the new libraries in the dynamic library config
sudo /sbin/ldconfig

# Install abac / gcf integration dependencies while we're root
APT_PKGS="python-pyasn1 python-m2crypto python-dateutil python-openssl"
APT_PKGS="${APT_PKGS} libxmlsec1 xmlsec1 libxmlsec1-openssl libxmlsec1-dev"
/usr/bin/sudo /usr/bin/apt-get install -y ${APT_PKGS}
check_errs $? "Failed to install abac-gcf integration dependencies"

exit 0
