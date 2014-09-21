
# Makefile - part of amtc
# https://github.com/schnoddelbotz/amtc
#
# Toplevel Makefile for amtc and amtc-web.
# 
# Targets:
# make amtc      -- build amtc C binary
# make amtc-web  -- build amtc-web application
# make dist      -- prepare dist/ tree for distribution

AMTCV=$(shell cat version)
APP=amtc-$(AMTCV)

RPMBUILD ?= $(HOME)/rpmbuild
RPMSRC ?= "$(RPMBUILD)/SOURCES/amtc-$(AMTCV).tar.gz"
DESTDIR ?= /
BINDIR  ?= usr/bin
WWWDIR  ?= var/www/html
ETCDIR  ?= etc
DATADIR ?= var/lib
AMTCWEBDIR = amtc-web2

dist: amtc amtc-web
	echo "Preparing distribution in dist/"
	mkdir -p dist/$(BINDIR) dist/$(WWWDIR) dist/$(ETCDIR) dist/$(DATADIR)
	cp src/amtc dist/$(BINDIR)
	cp -R $(AMTCWEBDIR) dist/$(WWWDIR)
	(cd dist/$(WWWDIR)/$(AMTCWEBDIR) && ./build.sh clean && mv _htaccess_example .htaccess && rm -f basic-auth/_htaccess.default config/_htpasswd.default data/amtc-web.db config/siteconfig.php build.sh)
	(cd dist && mv $(WWWDIR)/$(AMTCWEBDIR)/config $(ETCDIR)/amtc-web && mv $(WWWDIR)/$(AMTCWEBDIR)/data $(DATADIR)/amtc-web)
	(cd dist/$(WWWDIR)/$(AMTCWEBDIR) && ln -s /$(ETCDIR)/amtc-web config && ln -s /$(DATADIR)/amtc-web data)
	(cd dist/$(WWWDIR)/$(AMTCWEBDIR) && perl -pi -e "s@AuthUserFile .*@AuthUserFile /$(ETCDIR)/amtc-web/.htpasswd@" basic-auth/.htaccess)
# more to come...

amtc:
	(cd src && make)

amtc-web:
	(cd $(AMTCWEBDIR) && ./build.sh)

clean:
	rm -rf dist
	(cd src && make clean)
	(cd $(AMTCWEBDIR) && ./build.sh clean)




### NEEDS amtc-web2 - CLEANUP/FIX/TEST
# build from github tagged release (from version defined in ../version)
rpm-rel: 
	@echo Building release RPM of amtc $(AMTCV) 
	wget -O $(RPMSRC) https://github.com/schnoddelbotz/amtc/archive/v$(AMTCV).tar.gz
	rpmbuild -ba ../amtc.spec

# build from local src
rpm: rpm-snap
rpm-snap: 
	@echo Building snapshot RPM of amtc $(AMTCV) 

install:
	mkdir -p $(DESTDIR)/$(BINDIR) $(DESTDIR)/$(WWWDIR)
	cp amtc $(DESTDIR)/$(BINDIR)
	cp -R ../amtc-web $(DESTDIR)/var/www/html

# build q&_d_ debian package (uses debian's equivs)
deb: amtc
	( cp amtc ..; cd .. ; equivs-build amtc.equivs )
	( cd .. ; perl -e 'foreach (glob("amtc-web/*/*")) {push @F, "$$_ /var/www/$$_"}; $$f=join("\n ",@F); while (<>) {s#^Files:.*#Files: $$f#;print;}' amtc-web.equivs>amtc-web.equivs.build;  equivs-build amtc-web.equivs.build )

.PHONY:	amtc-web dist
