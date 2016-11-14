
# amtc-web/Makefile - part of amtc
# https://github.com/schnoddelbotz/amtc
#
# Makefile for amtc-web(2)
# Downloads, unpacks, concatenates and compresses JS/CSS dependencies

# define versions to fetch
JQUERY      = 2.2.4
JQUERYUI    = 1.12.1
BOOTSTRAP   = 3.3.7
EMBERJS     = 1.13.11
EMBERDATA   = 1.13.15
FONTAWESOME = 4.7.0

SHELL = bash
MERGE_JS    ?= YES
MERGE_CSS   ?= YES
HAVE_NODEJS ?= $(shell /usr/bin/env node -v &>/dev/null && echo YES)
TPL_COMPILER = $(shell test "$(HAVE_NODEJS)" = "YES" && echo "" || echo "js/ember-template-compiler.js")

CSSFILES = css/bootstrap.min.css css/plugins/metisMenu.min.css \
 	css/sb-admin-2.css css/plugins/morris.css \
 	css/font-awesome.min.css css/humane-original.css

JSFILES  = js/jquery.min.js js/bootstrap.min.js $(TPL_COMPILER) \
	js/emberjs.min.js js/ember-data.min.js js/showdown.min.js \
	js/moment.min.js js/humane.min.js js/metisMenu.min.js \
	js/raphael.min.js js/morris.min.js js/jquery-ui.min.js

PHPLIBS  = lib/Slim/Slim.php lib/idiorm.php lib/paris.php
ZIPSRC   = js/jquery-ui.min.js js/bootstrap.min.js css/sb-admin-2.css fonts/fontawesome-webfont.woff
FONTS    = fonts/fontawesome-webfont.woff

build: index.html download compress basic-auth/.htaccess

download: css/sb-admin-2.css $(CSSFILES) $(JSFILES) $(PHPLIBS) $(FONTS)

concat: download css/styles.css js/jslibs.js

compress: concat css/styles.css.gz js/jslibs.js.gz index.html.gz

index.html: js/ember-template-compiler.js
	if [ $(MERGE_CSS) = YES ]; then \
	  grep -v css/amtc-web.css templates/application_header.html > index.html; \
	else \
	  cat templates/application_header.html > index.html; \
	fi
	#
	# NodeJS seems available on buildhost. Pre-compile emberjs
	# templates to increase client-side GUI performance.
	if [ "$(HAVE_NODEJS)" = YES ]; then \
		cat /dev/null > js/compiled-templates.js; \
		for t in templates/*.hbs.html templates/*/*.hbs.html; do \
		  tpl=`basename $$t | cut -d. -f1`; \
		  dir=`dirname $$t`; \
		  if [ $$dir != "templates" ]; then \
		    tpl="`basename $$dir`/$$tpl"; \
		  fi; \
		  echo "// $$t ($$tpl) " >> js/compiled-templates.js; \
		  ./js/compile-template.js "$$t" "$$tpl"; \
		  echo " Pre-compiled template $$t"; \
		done ; \
	else \
	for t in templates/*.hbs.html templates/*/*.hbs.html; do \
	  tpl=`basename $$t | cut -d. -f1`; \
	  dir=`dirname $$t`; \
	  if [ $$dir != "templates" ]; then \
	    tpl="`basename $$dir`/$$tpl"; \
	  fi; \
	  echo "<!-- $$t ($$tpl) -->" >> index.html; \
	  echo '<script type="text/x-handlebars" id="'$$tpl'">' >> index.html; \
	  cat $$t >> index.html; \
	  echo -e '</script>\n' >> index.html; \
	done; \
	fi
	# ^ No NodeJS on buildhost, build index.html that will result in
	#   client-side template compilation.

	if [ $(MERGE_JS) = YES ]; then \
	  grep -v app/app.js templates/application_footer.html >> index.html; \
	else \
	  cat templates/application_footer.html >> index.html; \
	fi


# concat targets
css/styles.css: $(CSSFILES) $(ZIPSRC) css/sb-admin-2.css
	cat $(CSSFILES) > css/styles.css
	[ $(MERGE_CSS) = YES ] && cat css/amtc-web.css >> css/styles.css || true

js/jslibs.js: index.html $(JSFILES) $(ZIPSRC)
	rm -f js/jslibs.js
	for JSFILE in $(JSFILES); do cat $$JSFILE >> js/jslibs.js; echo >> js/jslibs.js; done
	# small work-around for freebsd, where make won't support $(shell ) commands / vars --
	# template compiler won't become part of $(JSFILES) there, so just append it to jslibs.js
	/usr/bin/env node -v >/dev/null 2>&1 || if [ -z "$(TPL_COMPILER_NODEJS)" ]; then echo >>js/jslibs.js; cat js/ember-template-compiler.js >> js/jslibs.js; echo >>js/jslibs.js; fi
	#
	if [ "$(HAVE_NODEJS)" = YES ]; then \
	  cat js/compiled-templates.js >> js/jslibs.js ; \
	  [ $(MERGE_JS) = YES ] && node -e 'var u=require("uglify-js"); console.log(u.minify("js/app/app.js").code);' >> js/jslibs.js || true ; \
	else \
		[ $(MERGE_JS) = YES ] && cat js/app/app.js >> js/jslibs.js || true ; \
	fi
	test `uname -s` = "Darwin" && perl -pi -e 's@/usr/bin/amtc@/usr/local/bin/amtc@' js/jslibs.js || true


# compress targets
js/jslibs.js.gz:  js/jslibs.js
	gzip -c --best js/jslibs.js > js/jslibs.js.gz
css/styles.css.gz: css/styles.css
	gzip -c --best css/styles.css > css/styles.css.gz
index.html.gz: index.html
	gzip -c --best index.html > index.html.gz

# remove any non-repo / downloaded / built files
# Improve: rm {a,b}*.xxx fails on freebsd
clean:
	rm -rf index.html fonts css/plugins js/plugins {.,css,js}/*.gz lib/Slim
	rm -f css/{b,f,h,sb,st}*.css js/{b,e,h,j,m,s}*.js js/jq*.map \
	 lib/{paris,idiorm}.php js/compiled-templates.js

# rebuild without nodejs and merging js/css
debug:
	rm -f index.html js/jslibs.js css/styles.css
	HAVE_NODEJS=no MERGE_CSS=no MERGE_JS=no make

# remove single js/css files (to keep concat+compress version only)
distclean:
	rm -f css/{b,f,h,sb}*.css js/{compile,b,e,h,jq,m,s}*.js
	rm -rf {css,js}/plugins templates
	[ $(MERGE_JS) = YES ] && rm -rf js/app || true
	[ $(MERGE_CSS) = YES ] && rm -f css/amtc-web.css || true

# download any CSS/JSFILES from URLs previously specified in Makefile.Sources (bsd make compat)
js/jquery.min.js:
	curl -Lso js/jquery.min.js http://code.jquery.com/jquery-$(JQUERY).min.js
js/jquery.min.map:
	curl -Lso js/jquery.min.map http://code.jquery.com/jquery-$(JQUERY).min.map
js/emberjs.min.js:
	curl -Lso js/emberjs.min.js http://builds.emberjs.com/tags/v$(EMBERJS)/ember.min.js
js/ember-template-compiler.js:
	curl -Lso js/ember-template-compiler.js http://builds.emberjs.com/tags/v$(EMBERJS)/ember-template-compiler.js
js/ember-data.min.js:
	curl -Lso js/ember-data.min.js http://builds.emberjs.com/tags/v$(EMBERDATA)/ember-data.min.js
js/showdown.min.js:
	curl -Lso js/showdown.min.js https://raw.githubusercontent.com/showdownjs/showdown/master/dist/showdown.min.js
js/moment.min.js:
	curl -Lso js/moment.min.js http://momentjs.com/downloads/moment.min.js
js/humane.min.js:
	curl -Lso js/humane.min.js https://raw.githubusercontent.com/wavded/humane-js/master/humane.min.js
# Styles (CSS / Fonts)
css/humane-original.css:
	curl -Lso css/humane-original.css https://raw.githubusercontent.com/wavded/humane-js/master/themes/original.css
# PHP libs
lib/idiorm.php:
	curl -Lso lib/idiorm.php https://raw.githubusercontent.com/j4mie/idiorm/master/idiorm.php
lib/paris.php:
	curl -Lso lib/paris.php https://raw.githubusercontent.com/j4mie/paris/master/paris.php

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
	cd bootstrap-$(BOOTSTRAP)-dist; mv css/bootstrap.min.css ../css; mv js/bootstrap.min.js ../js; cd ..
	rm -rf bootstrap-$(BOOTSTRAP)-dist bs.zip

css/sb-admin-2.css:
	curl -Lso sb.zip https://github.com/BlackrockDigital/startbootstrap-sb-admin-2/archive/v3.3.7+1.zip
	unzip -q sb.zip
	cp startbootstrap-sb-admin-2-3.3.7-1/dist/css/sb-admin-2.css css
	mkdir -p css/plugins
	cp startbootstrap-sb-admin-2-3.3.7-1/vendor/morrisjs/morris.css css/plugins
	cp startbootstrap-sb-admin-2-3.3.7-1/vendor/metisMenu/metisMenu.min.css css/plugins
	cp startbootstrap-sb-admin-2-3.3.7-1/vendor/morrisjs/morris.min.js js
	cp startbootstrap-sb-admin-2-3.3.7-1/vendor/raphael/raphael.min.js js
	cp startbootstrap-sb-admin-2-3.3.7-1/vendor/metisMenu/metisMenu.min.js js
	rm -rf startbootstrap-sb-admin-2-3.3.7-1 sb.zip

fonts/fontawesome-webfont.woff:
	curl -Lso fa.zip http://fortawesome.github.io/Font-Awesome/assets/font-awesome-$(FONTAWESOME).zip
	unzip -q fa.zip
	mv font-awesome-$(FONTAWESOME)/css/font-awesome.min.css css
	mkdir -p fonts
	mv font-awesome-$(FONTAWESOME)/fonts/* fonts
	rm -rf fa.zip font-awesome-$(FONTAWESOME)

lib/Slim/Slim.php:
	curl -Lso slim.zip https://github.com/slimphp/Slim/archive/2.x.zip
	unzip -q slim.zip
	mv Slim-2.x/Slim lib
	rm -rf Slim-2.x slim.zip

# install example/default .htpasswd + .htaccess
basic-auth/.htaccess:
	chmod 777 data config
	[ -f config/.htpasswd ] || cp config/_htpasswd.default config/.htpasswd
	if [ ! -f basic-auth/.htaccess ]; then \
	  cp basic-auth/_htaccess.default basic-auth/.htaccess ; \
	  perl -pi -e "s@AuthUserFile .*@AuthUserFile `pwd`/config/.htpasswd@" basic-auth/.htaccess ; \
	fi

# named files are concat targets (CSSFILES/JSFILES) but included in ZIPSRC target downloads
js/metisMenu.min.js js/morris.min.js js/raphael.min.js css/font-awesome.min.css css/plugins/metisMenu.min.css css/plugins/morris.css css/bootstrap.min.css:
	@# nothing to do

.PHONY: build concat compress download
