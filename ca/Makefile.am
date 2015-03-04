svcdatadir = $(pkgdatadir)/CA

# apache_user and apache_group are set in the configure script.
INSTALL_WWW = $(INSTALL) -o $(apache_user) -g $(apache_group)
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
