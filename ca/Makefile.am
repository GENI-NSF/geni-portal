svcdatadir = $(pkgdatadir)/CA

# A bunch of files get installed as user www-data which is great for
# Ubuntu, but won't work on other Linux distributions, like Red Hat.
# We need to do it this way so that certificates can be issued via
# the web site user.
WWW_OWNER=www-data
WWW_GROUP=www-data
INSTALL_WWW = $(INSTALL) -o $(WWW_OWNER) -g $(WWW_GROUP)
SERIAL_FILE = $(svcdatadir)/serial
INDEX_FILE = $(svcdatadir)/index.txt
OPENSSL_CONF = $(svcdatadir)/openssl.cnf

# This target gets run after "install-data"
install-data-local: $(svcdatadir) $(OPENSSL_CONF) $(SERIAL_FILE) $(INDEX_FILE)
	$(INSTALL_WWW) -d $(svcdatadir)/certs
	$(INSTALL_WWW) -d $(svcdatadir)/crl
	$(INSTALL_WWW) -d $(svcdatadir)/newcerts
	$(INSTALL_WWW) -d $(svcdatadir)/private

$(svcdatadir):
	$(INSTALL_WWW) -d $(svcdatadir)

$(OPENSSL_CONF):
	$(INSTALL_WWW) -m 644 openssl.cnf $(OPENSSL_CONF)

$(SERIAL_FILE):
	$(INSTALL_WWW) -m 644 /dev/null $(SERIAL_FILE)
	echo "00" > $(SERIAL_FILE)

$(INDEX_FILE):
	$(INSTALL_WWW) -m 644 /dev/null $(INDEX_FILE)

# empty target for compatibility
cleandb:
