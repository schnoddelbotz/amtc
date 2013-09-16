
LFLAGS=-lcurl -lpthread -lgnutls -lgcrypt
CFLAGS=-I. -Wall
AMTCV=$(shell cat ../version)
APP=amtc-$(AMTCV)

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
	perl -pi -e 's/^Version:.*/Version:\t $(AMTCV)/' ../amtc.spec
	perl -pi -e 's@archive/\S+.tar.gz@archive/$(APP).tar.gz@' ../amtc.spec
	( cd .. ; git commit . ; git tag -a "v$(AMTCV)" -m "Created release $(APP)" )
	( cd .. ; git push ; git push --tags )

# build from github tagged release (from version defined in ../version)
rpm-rel: 
	@echo Building release RPM of amtc $(AMTCV) 
	wget -O $(RPMSRC) https://github.com/schnoddelbotz/amtc/archive/v$(AMTCV).tar.gz
	rpmbuild -ba ../amtc.spec

# build from local src
rpm: rpm-snap
rpm-snap: 
	@echo Building snapshot RPM of amtc $(AMTCV) 
	(cd ../..; mv amtc $(APP); tar -cvzf $(RPMSRC) $(APP); mv $(APP) amtc )
	rpmbuild -ba ../amtc.spec

install:
	mkdir -p $(DESTDIR)/$(BINDIR) $(DESTDIR)/$(WWWDIR)
	cp amtc $(DESTDIR)/$(BINDIR)
	cp -R ../amtc-web $(DESTDIR)/var/www/html

# build q&_d_ debian package (uses debian's equivs)
deb: amtc
	( cp amtc ..; cd .. ; equivs-build amtc.equivs )
	( cd .. ; perl -e 'foreach (glob("amtc-web/*/*")) {push @F, "$$_ /var/www/$$_"}; $$f=join("\n ",@F); while (<>) {s#^Files:.*#Files: $$f#;print;}' amtc-web.equivs>amtc-web.equivs.build;  equivs-build amtc-web.equivs.build )

