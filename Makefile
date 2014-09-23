
# Makefile - part of amtc
# https://github.com/schnoddelbotz/amtc
#
# Toplevel Makefile for amtc and amtc-web.
# 
# Targets:
# make amtc      -- build amtc C binary
# make amtc-web  -- build amtc-web application (V2 ... incomplete yet)
# make dist      -- prepare dist/ tree for distribution

AMTCV=$(shell cat version)
APP=amtc-$(AMTCV)

RPMBUILD ?= $(HOME)/rpmbuild
RPMSRC ?= "$(RPMBUILD)/SOURCES/amtc-$(AMTCV).tar.gz"
DESTDIR ?= /
BINDIR  ?= usr/bin
WWWDIR  ?= usr/share/amtc-web
ETCDIR  ?= etc
DATADIR ?= var/lib
AMTCWEBDIR = amtc-web2

all: amtc amtc-web dist

amtc:
	(cd src && make)

amtc-web:
	(cd $(AMTCWEBDIR) && ./build.sh)

clean:
	rm -rf dist amtc amtc*.deb *.build debian/amtc
	(cd src && make clean)
	(cd $(AMTCWEBDIR) && ./build.sh clean)

# q+d .debian package

install: dist
	cp -R dist/* $(DESTDIR)

deb:
	echo y | dh_make --createorig -s -p amtc_$(AMTCV) || true
	echo  "#!/bin/sh -e\nchown www-data:www-data /var/lib/amtc-web /etc/amtc-web" > debian/postinst
	debuild

debclean: clean
	rm -rf debian ../amtc_*

dist: amtc amtc-web
	echo "Preparing clean distribution in dist/"
	rm -rf dist
	mkdir -p dist/$(BINDIR) dist/$(WWWDIR) dist/$(ETCDIR) dist/$(DATADIR)
	cp src/amtc dist/$(BINDIR)
	cp -R $(AMTCWEBDIR) dist/$(WWWDIR)
	(cd dist/$(WWWDIR)/$(AMTCWEBDIR) && ./build.sh distclean && mv _htaccess_example .htaccess && rm -f basic-auth/_htaccess.default config/_htpasswd.default data/amtc-web.db config/siteconfig.php build.sh)
	(cd dist && mv $(WWWDIR)/$(AMTCWEBDIR)/config $(ETCDIR)/amtc-web && mv $(WWWDIR)/$(AMTCWEBDIR)/data $(DATADIR)/amtc-web)
	(cd dist/$(WWWDIR)/$(AMTCWEBDIR) && ln -s /$(ETCDIR)/amtc-web config && ln -s /$(DATADIR)/amtc-web data)
	(cd dist/$(WWWDIR)/$(AMTCWEBDIR) && perl -pi -e "s@AuthUserFile .*@AuthUserFile /$(ETCDIR)/amtc-web/.htpasswd@" basic-auth/.htaccess)
	# more to come...


.PHONY:	amtc-web



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


