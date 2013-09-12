
LFLAGS=-lcurl -lpthread -lgnutls -lgcrypt
CFLAGS=-I. -Wall
AMTCV=$(shell cat ../version)

RPMBUILD ?= $(HOME)/rpmbuild
RPMSRC ?= "$(RPMBUILD)/SOURCES/amtc-$(AMTCV).tar.gz"
DESTDIR ?= /
BINDIR ?= usr/bin
WWWDIR ?= var/www/html

HEADERS=amtc_usage cmd_powerdown cmd_powerup cmd_info cmd_powerreset cmd_powercycle wsman_info wsman_info_step2 wsman_up wsman_down wsman_reset

amtc: amt.h 
	$(CC) -o amtc amtc.c $(CFLAGS) $(LFLAGS)

amt.h:
	perl -pi -e 's/v(\d\.\d\.\d)/v$(AMTCV)/' amtc_usage
	for H in $(HEADERS); do xxd -i $$H $$H.h; done
	cat amtc_usage.h cmd_*.h wsman_*.h > amt.h
	perl -pi -e 's/(0x\S\S)$$/$$1, 0x00/' amt.h
	perl -pi -e 's/(\d+);$$/$$1 + 1 .";"/e' amt.h

clean:
	rm -f cmd_*.h wsman_*.h amtc_usage.h amt.h amtc *.o

# create a tagged release and push it
# fixme: should update README.md usage
tag-rel-push:
	( cd .. ; git commit . ; git tag -a "v$(AMTCV)" -m "Created release $(AMTCV)" )
	( cd .. ; git push ; git push --tags )

# build from github tagged release
rpm-rel:
	@echo Building release RPM of amtc $(AMTCV) 
	wget -P $(RPMBUILD)/SOURCES https://github.com/schnoddelbotz/amtc/archive/v$(AMTCV).tar.gz
	cp ../amtc.spec $(RPMBUILD)/SPECS
	perl -pi -e 's/^Version:.*/Version:\t $(AMTCV)/' $(RPMBUILD)/SPECS/amtc.spec
	perl -pi -e 's@archive/\S+.tar.gz@archive/amtc-$(AMTCV).tar.gz@' $(RPMBUILD)/SPECS/amtc.spec
	rpmbuild -ba $(RPMBUILD)/SPECS/amtc.spec

install:
	
# build from local src
rpm: rpm-snap
rpm-snap:
	@echo Building snapshot RPM of amtc $(AMTCV) 
	(cd ../..; tar -cvzf $(RPMSRC) amtc)
	cp ../amtc.spec $(RPMBUILD)/SPECS
	perl -pi -e 's/^Version:.*/Version:\t $(AMTCV)/' $(RPMBUILD)/SPECS/amtc.spec
	perl -pi -e 's@archive/\S+.tar.gz@archive/amtc-$(AMTCV).tar.gz@' $(RPMBUILD)/SPECS/amtc.spec
	rpmbuild -ba $(RPMBUILD)/SPECS/amtc.spec

install:
	mkdir -p $(DESTDIR)/$(BINDIR) $(DESTDIR)/$(WWWDIR)
	cp amtc $(DESTDIR)/$(BINDIR)
	cp -R ../amtc-web $(DESTDIR)/var/www/html

