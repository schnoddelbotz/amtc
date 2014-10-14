
# Makefile - part of amtc
# https://github.com/schnoddelbotz/amtc
#
# Makefile for amtc-web(2)

# define versions to fetch
export JQUERY      = 2.1.1
export JQUERYUI    = 1.11.1
export BOOTSTRAP   = 3.2.0
export HANDLEBARS  = 1.3.0
export EMBERJS     = 1.7.0
export EMBERDATA   = 1.0.0-beta.10
export FONTAWESOME = 4.2.0

CSSFILES=css/bootstrap.min.css css/plugins/metisMenu/metisMenu.min.css \
 	css/plugins/timeline.css css/sb-admin-2.css css/plugins/morris.css \
 	css/font-awesome.min.css css/humane-original.css
JSFILES=js/jquery.min.js js/bootstrap.min.js js/handlebars.js js/emberjs.min.js \
  js/ember-data.min.js js/showdown.js js/moment.js js/humane.min.js \
	js/plugins/metisMenu/metisMenu.min.js js/plugins/morris/raphael.min.js \
	js/plugins/morris/morris.min.js js/jquery-ui.min.js
PHPLIBS=lib/Slim/Slim.php lib/idiorm.php lib/paris.php
FONTS=fonts/fontawesome-webfont.woff

SCRIPTPATH = $(shell pushd `dirname $0` > /dev/null; P=`pwd`; popd > /dev/null; echo $P)

install: download compress
	[ -f config/.htpasswd ] || cp config/_htpasswd.default config/.htpasswd
	if [ ! -f basic-auth/.htaccess ]; then \
	  cp basic-auth/_htaccess.default basic-auth/.htaccess ; \
	  perl -pi -e "s@AuthUserFile .*@AuthUserFile $(SCRIPTPATH)/config/.htpasswd@" basic-auth/.htaccess ; \
	fi

download: $(CSSFILES) $(JSFILES) $(PHPLIBS) $(FONTS)
	@echo All dependencies downloaded and unpacked.

clean:
	rm -rf fonts css/plugins js/plugins {.,css,js}/*.gz lib/Slim
	rm -f css/{b,f,h,sb,st}*.css js/{b,e,h,j,m,s}*.js lib/{paris,idiorm}.php

distclean:
	rm -f css/{b,f,h,sb}*.css js/{b,e,h,jq,m,s}*.js
	rm -rf {css,js}/plugins

# how to avoid ...?
%.js:
	@URL1=`grep '$*.js'  Makefile.Sources | awk '{print $$2}'`; URL=`eval echo $$URL1`; echo "Fetch $*.js from $$URL"; curl -Lso $*.js $$URL
%.css:
	@URL1=`grep '$*.css' Makefile.Sources | awk '{print $$2}'`; URL=`eval echo $$URL1`; echo "Fetch $*.css from $$URL"; curl -Lso $*.css $$URL
%.php:
	@URL1=`grep '$*.php' Makefile.Sources | awk '{print $$2}'`; URL=`eval echo $$URL1`; echo "Fetch $*.php from $$URL"; curl -Lso $*.php $$URL

js/jquery-ui.min.js:
	curl -Lso jq.zip http://jqueryui.com/resources/download/jquery-ui-${JQUERYUI}.zip
	unzip -q jq.zip
	cp jquery-ui-${JQUERYUI}/jquery-ui.min.js js
	rm -rf jquery-ui-${JQUERYUI} jq.zip

js/bootstrap.min.js:
	curl -Lso bs.zip https://github.com/twbs/bootstrap/releases/download/v${BOOTSTRAP}/bootstrap-${BOOTSTRAP}-dist.zip
	unzip -q bs.zip
	mkdir -p fonts
	cd bootstrap-${BOOTSTRAP}-dist; mv css/bootstrap.min.css ../css; mv js/bootstrap.min.js ../js; mv fonts/* ../fonts
	rm -rf bootstrap-${BOOTSTRAP}-dist bs.zip

css/sb-admin-2.css:
	curl -Lso sb.zip http://startbootstrap.com/downloads/sb-admin-2.zip
	unzip -q sb.zip
	cp sb-admin-2/css/sb-admin-2.css css
	cp -R sb-admin-2/css/plugins css
	cp -R sb-admin-2/js/plugins js
	rm -rf sb-admin-2 sb.zip

fonts/fontawesome-webfont.woff:
	curl -Lso fa.zip http://fortawesome.github.io/Font-Awesome/assets/font-awesome-${FONTAWESOME}.zip
	unzip -q fa.zip
	mv font-awesome-${FONTAWESOME}/css/font-awesome.min.css css
	mkdir -p fonts
	mv font-awesome-${FONTAWESOME}/fonts/* fonts
	rm -rf fa.zip font-awesome-${FONTAWESOME}

lib/Slim/Slim.php:
	curl -Lso slim.zip https://github.com/codeguy/Slim/zipball/master
	unzip -q slim.zip
	mv codeguy-Slim-*/Slim lib
	rm -rf codeguy-Slim-* slim.zip

concat: download
	( cat $(CSSFILES) > css/styles.css  )
	( cat $(JSFILES) > js/jslibs.js )

compress: concat
	for src in css/styles.css js/jslibs.js index.html; do \
	  gzip -c --best $$src > $$src.gz; \
	done

# void targets (but globally listed for (con)cat purposes)
css/font-awesome.min.css js/plugins/metisMenu/metisMenu.min.js js/plugins/morris/morris.min.js js/plugins/morris/raphael.min.js css/plugins/metisMenu/metisMenu.min.css css/plugins/morris.css css/plugins/timeline.css css/bootstrap.min.css:
	@# nothing to do, included elsewhere

# htaccess stuff..
install-examples:
