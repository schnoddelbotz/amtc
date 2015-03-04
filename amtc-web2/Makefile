
# amtc-web/Makefile - part of amtc
# https://github.com/schnoddelbotz/amtc
#
# Makefile for amtc-web(2)
# Downloads, unpacks, concatenates and compresses JS/CSS dependencies

# define versions to fetch
export JQUERY      = 2.1.1
export JQUERYUI    = 1.11.1
export BOOTSTRAP   = 3.2.0
export HANDLEBARS  = 1.3.0
export EMBERJS     = 1.7.0
export EMBERDATA   = 1.0.0-beta.14.1
export FONTAWESOME = 4.3.0

SHELL = bash

CSSFILES = css/bootstrap.min.css css/plugins/metisMenu/metisMenu.min.css \
 	css/plugins/timeline.css css/sb-admin-2.css css/plugins/morris.css \
 	css/font-awesome.min.css css/humane-original.css

JSFILES  = js/jquery.min.js js/bootstrap.min.js js/handlebars.js js/emberjs.min.js \
	js/ember-data.min.js js/showdown.js js/moment.js js/humane.min.js \
	js/plugins/metisMenu/metisMenu.min.js js/plugins/morris/raphael.min.js \
	js/plugins/morris/morris.min.js js/jquery-ui.min.js

PHPLIBS  = lib/Slim/Slim.php lib/idiorm.php lib/paris.php
ZIPSRC   = js/jquery-ui.min.js js/bootstrap.min.js css/sb-admin-2.css fonts/fontawesome-webfont.woff
FONTS    = fonts/fontawesome-webfont.woff

build: download compress basic-auth/.htaccess

download: $(CSSFILES) $(JSFILES) $(PHPLIBS) $(FONTS)

concat: download css/styles.css js/jslibs.js

compress: concat css/styles.css.gz js/jslibs.js.gz index.html.gz

# concat targets
css/styles.css: $(CSSFILES) $(ZIPSRC)
	cat $(CSSFILES) > css/styles.css

js/jslibs.js: $(JSFILES) $(ZIPSRC)
	cat $(JSFILES) > js/jslibs.js

# compress targets
%.gz: js/jslibs.js css/styles.css
	gzip -c --best $* > $*.gz

# remove any non-repo / downloaded / built files
clean:
	rm -rf fonts css/plugins js/plugins {.,css,js}/*.gz lib/Slim
	rm -f css/{b,f,h,sb,st}*.css js/{b,e,h,j,m,s}*.js js/jq*.map lib/{paris,idiorm}.php

# remove single js/css files (to keep concat+compress version only)
distclean:
	rm -f css/{b,f,h,sb}*.css js/{b,e,h,jq,m,s}*.js
	rm -rf {css,js}/plugins

# download any CSS/JSFILES from URLs specified in Makefile.Sources
%:
	@URL1=`grep '$*' Makefile.Sources | awk '{print $$2}'`; \
		URL=`eval echo $$URL1`; \
		echo "curl -Lso $* $$URL"; \
		curl -Lso $* $$URL

# ZIPSRC downloads
js/jquery-ui.min.js:
	curl -Lso jq.zip http://jqueryui.com/resources/download/jquery-ui-$(JQUERYUI).zip
	unzip -q jq.zip
	cp jquery-ui-$(JQUERYUI)/jquery-ui.min.js js
	rm -rf jquery-ui-$(JQUERYUI) jq.zip

js/bootstrap.min.js:
	curl -Lso bs.zip https://github.com/twbs/bootstrap/releases/download/v$(BOOTSTRAP)/bootstrap-$(BOOTSTRAP)-dist.zip
	unzip -q bs.zip
	mkdir -p fonts
	cd bootstrap-$(BOOTSTRAP)-dist; mv css/bootstrap.min.css ../css; mv js/bootstrap.min.js ../js; mv fonts/* ../fonts
	rm -rf bootstrap-$(BOOTSTRAP)-dist bs.zip

css/sb-admin-2.css:
	curl -Lso sb.zip https://github.com/IronSummitMedia/startbootstrap-sb-admin-2/archive/v1.0.0.zip
	unzip -q sb.zip
	cp startbootstrap-sb-admin-2-1.0.0/css/sb-admin-2.css css
	cp -R startbootstrap-sb-admin-2-1.0.0/css/plugins css
	cp -R startbootstrap-sb-admin-2-1.0.0/js/plugins js
	rm -rf startbootstrap-sb-admin-2-1.0.0 sb.zip

fonts/fontawesome-webfont.woff:
	curl -Lso fa.zip http://fortawesome.github.io/Font-Awesome/assets/font-awesome-$(FONTAWESOME).zip
	unzip -q fa.zip
	mv font-awesome-$(FONTAWESOME)/css/font-awesome.min.css css
	mkdir -p fonts
	mv font-awesome-$(FONTAWESOME)/fonts/* fonts
	rm -rf fa.zip font-awesome-$(FONTAWESOME)

lib/Slim/Slim.php:
	curl -Lso slim.zip https://github.com/slimphp/Slim/zipball/master
	unzip -q slim.zip
	mv slimphp-Slim-*/Slim lib
	rm -rf slimphp-Slim-* slim.zip

# install example/default .htpasswd + .htaccess
basic-auth/.htaccess:
	chmod 777 data config
	[ -f config/.htpasswd ] || cp config/_htpasswd.default config/.htpasswd
	if [ ! -f basic-auth/.htaccess ]; then \
	  cp basic-auth/_htaccess.default basic-auth/.htaccess ; \
	  perl -pi -e "s@AuthUserFile .*@AuthUserFile `pwd`/config/.htpasswd@" basic-auth/.htaccess ; \
	fi

# named files are concat targets (CSSFILES/JSFILES) but included in ZIPSRC target downloads
js/plugins/metisMenu/metisMenu.min.js js/plugins/morris/morris.min.js js/plugins/morris/raphael.min.js css/font-awesome.min.css css/plugins/metisMenu/metisMenu.min.css css/plugins/morris.css css/plugins/timeline.css css/bootstrap.min.css:
	@# nothing to do

.PHONY: build concat compress download
