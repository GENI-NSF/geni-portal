#!/bin/bash

# Exit on error
set -e
# Echo commands with variables expanded
set -x

#RRH this gave me errors - so you might want to do it by hand for the rest to succeed
wget http://pkgs.repoforge.org/rpmforge-release/rpmforge-release-0.5.3-1.el6.rf.x86_64.rpm 
sudo rpm -ivh rpmforge-release-0.5.3-1.el6.rf.x86_64.rpm 
#UP TO HERE

sudo yum clean all
sudo yum update
sudo yum -y groupinstall "Development tools"
sudo yum -y install zlib-devel bzip2-devel openssl-devel ncurses-devel sqlite-devel readline-devel tk-devel gdbm-devel db4-devel libpcap-devel xz-devel

#install Python2.7.6
wget http://python.org/ftp/python/2.7.6/Python-2.7.6.tar.xz
tar xf Python-2.7.6.tar.xz
cd Python-2.7.6
./configure --prefix=/usr/local --enable-unicode=ucs4 --enable-shared LDFLAGS="-Wl,-rpath /usr/local/lib"
make
sudo make altinstall

#install  pip
wget https://bitbucket.org/pypa/setuptools/raw/bootstrap/ez_setup.py
sudo python2.7 ez_setup.py
sudo easy_install-2.7 pip


sudo yum -y install python-devel.x86_64 swig.x86_64 libxslt-devel.x86_64 php-xmlrpc.x86_64 \
          libffi-devel libffi openssl-devel perl-devel \
          perl-ExtUtils-Embed xmlsec1-devel.x86_64 xmlsec1-openssl-devel.x86_64 \
          postgresql-server.x86_64 postgresql.x86_64 postgresql-devel.x86_64 php-pgsql.x86_64 \
          mod_fastcgi.x86_64 rsyslog.x86_64 php-pear-MDB2-Driver-pgsql \
          java-1.6.0-openjdk.x86_64 ant.x86_64 perl-devel


sudo pip2.7 install pyOpenSSL==0.14 pycparser==2.10 python-dateutil==2.2 pytz==2014.1.1 \
    six==1.5.2 wsgiref==0.1.2 Flask==0.10.1 Flask-XML-RPC==0.1.2 Jinja2==2.7.2 M2Crypto==0.22.3 \
    MarkupSafe==0.18 SQLAlchemy==0.9.3 Werkzeug==0.9.4 blinker==1.3 cffi==0.8.1 cryptography==0.2.2 \
    flup==1.0.2 itsdangerous==0.23 lxml==3.3.3 pika==0.9.13 psycopg2==2.5.3

sudo service rsyslog restart
