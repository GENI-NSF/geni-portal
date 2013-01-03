
INSTALL=/usr/bin/install
SBIN=$(DESTDIR)/usr/sbin
PYTHON = python2.6
CFLAGS = -g -Wall -I/usr/include/$(PYTHON)
LDFLAGS = -L/usr/lib/$(PYTHON)/config
LDLIBS = -l$(PYTHON)

default: geni-pgch

install:
	echo "destdir = $(DESTDIR)"
	$(INSTALL) -d $(SBIN)
	$(INSTALL) geni-pgch $(SBIN)

clean:
	$(RM) *~

distclean: clean
	$(RM) geni-pgch
