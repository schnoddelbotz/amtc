
# Makefile - part of amtc
# https://github.com/schnoddelbotz/amtc
#
# Toplevel Makefile for amtc and amtc-web.
# 
# Targets:
# make amtc      -- build amtc C binary
# make amtc-web  -- build amtc-web application (V2 ... incomplete yet)
# make dist      -- prepare dist/ tree for distribution
# make install   -- respects $DESTDIR
# make deb       -- build rpm package of amtc incl. amtc-web
# make rpm       -- build RPMs of amtc and amtc-web

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

install: dist
	mkdir -p $(DESTDIR)
	cp -R dist/* $(DESTDIR)
	rm -f $(DESTDIR)/usr/share/amtc-web/amtc-web2/.htaccess $(DESTDIR)/usr/share/amtc-web/amtc-web2/basic-auth/.htaccess
	mkdir -p $(DESTDIR)/etc/apache2/conf.d
	cp amtc-web2/_httpd_conf_example $(DESTDIR)/etc/amtc-web/amtc-web_httpd.conf
	ln -s ../../amtc-web/amtc-web_httpd.conf $(DESTDIR)/etc/apache2/conf.d

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

# build q+d debian .deb package (into ../)
deb: clean
	echo y | dh_make --createorig -s -p amtc_$(AMTCV) || true
	echo  "#!/bin/sh -e\nchown www-data:www-data /var/lib/amtc-web /etc/amtc-web\na2enmod headers\na2enmod rewrite\nservice apache2 restart" > debian/postinst
	perl -pi -e 's@Description: .*@Description: Intel AMT/DASH remote power management tool@' debian/control
	perl -pi -e 's@^Depends: (.*)@Depends: $$1, httpd, php5-curl, php5-sqlite|php5-mysql|php5-pgsql@' debian/control
	debuild -i -us -uc -b

debclean: clean
	rm -rf debian ../amtc_*

# build RPM package (into ~/rpmbuild/RPMS/)
rpm: clean
	(cd ..; mv amtc $(APP); tar --exclude-vcs -czf $(RPMSRC) $(APP); mv $(APP) amtc )
	rpmbuild -ba amtc.spec

rpmfixup:
	mv $(DESTDIR)/etc/apache2 $(DESTDIR)/etc/httpd
	httpd -V  | grep -q Apache/2.4 && perl -pi -e 'BEGIN{undef $$/;} s@Order allow,deny\n\s+Allow from all@Require all granted@sm' $(DESTDIR)/etc/amtc-web/amtc-web_httpd.conf
	httpd -V  | grep -q Apache/2.4 && perl -pi -e 'BEGIN{undef $$/;} s@Order allow,deny\n\s+Deny from all@Require all denied@smg' $(DESTDIR)/etc/amtc-web/amtc-web_httpd.conf

.PHONY:	amtc-web
