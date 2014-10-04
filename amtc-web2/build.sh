#!/bin/bash -e

# build.sh - part of amtc-web, part of amtc
# https://github.com/schnoddelbotz/amtc
#
# couldn't fall in love with any build tool yet, so this
# shell script shall serve the task of downloading required
# JavaScript and CSS components during ... build.

# define versions to fetch
JQUERY=2.1.1
JQUERYUI=1.11.1
BOOTSTRAP=3.2.0
HANDLEBARS=1.3.0
EMBERJS=1.7.0
EMBERDATA=1.0.0-beta.10
FONTAWESOME=4.2.0

# use curl.
RUNCURL="curl -Lso"
function FetchIfNotExists {
  CheckFile=$1
  FileURL=$2
  if [ ! -f "$CheckFile" ]; then
    echo "Retreiving $FileURL ..."
    $RUNCURL "$CheckFile" "$FileURL"
  fi
}

pushd `dirname $0` > /dev/null
SCRIPTPATH=`pwd`
popd > /dev/null

# JavaScript and CSS files to be concatenated in the end
CSSFILES="bootstrap.min.css plugins/metisMenu/metisMenu.min.css \
    plugins/timeline.css sb-admin-2.css plugins/morris.css \
    font-awesome.min.css humane-original.css"
JSFILES="jquery.min.js bootstrap.min.js handlebars.js emberjs.min.js \
    ember-data.min.js showdown.js moment.js humane.min.js \
    plugins/metisMenu/metisMenu.min.js plugins/morris/raphael.min.js \
    plugins/morris/morris.min.js jquery-ui.min.js"

# have a 'clean target' - trash any non-source / downloaded files
if [ "$1" = "clean" ]; then
  rm -rf fonts css/plugins js/plugins {.,css,js}/*.gz lib/{Slim,php-activerecord}
  rm -f css/{b,f,h,sb}*.css js/{b,e,h,jq,m,s}*.js
  exit 0
fi
if [ "$1" = "distclean" ]; then
  rm -f css/{b,f,h,sb}*.css js/{b,e,h,jq,m,s}*.js
  exit 0
fi

# create required directories for libs, data and config
mkdir -p js css lib data config
chmod 777 data config

FetchIfNotExists js/jquery.min.js http://code.jquery.com/jquery-${JQUERY}.min.js
FetchIfNotExists js/jquery.min.map http://code.jquery.com/jquery-${JQUERY}.min.map
FetchIfNotExists js/handlebars.js http://builds.handlebarsjs.com.s3.amazonaws.com/handlebars-v${HANDLEBARS}.js
FetchIfNotExists js/emberjs.min.js http://builds.emberjs.com/tags/v${EMBERJS}/ember.min.js
FetchIfNotExists js/ember-data.min.js http://builds.emberjs.com/tags/v${EMBERDATA}/ember-data.min.js
FetchIfNotExists js/showdown.js https://github.com/coreyti/showdown/raw/master/src/showdown.js
FetchIfNotExists js/moment.js http://momentjs.com/downloads/moment.js
FetchIfNotExists js/humane.min.js https://raw.githubusercontent.com/wavded/humane-js/master/humane.min.js
FetchIfNotExists css/humane-original.css https://raw.githubusercontent.com/wavded/humane-js/master/themes/original.css

if [ ! -f "js/bootstrap.min.js" ]; then
  echo "Retreiving Twitter bootstrap ..."
  mkdir tmp_$$ && cd tmp_$$
  $RUNCURL bs.zip https://github.com/twbs/bootstrap/releases/download/v${BOOTSTRAP}/bootstrap-${BOOTSTRAP}-dist.zip
  unzip -q bs.zip
  cd bootstrap-${BOOTSTRAP}-dist
  mv fonts ../..
  mv css/bootstrap.min.css ../../css
  mv js/bootstrap.min.js ../../js
  cd ../..
  rm -rf tmp_$$ bs.zip
fi

if [ ! -f "css/sb-admin-2.css" ]; then
  echo "Retreiving SB Admin 2"
  mkdir tmp2_$$ && cd tmp2_$$
  $RUNCURL sb.zip http://startbootstrap.com/downloads/sb-admin-2.zip
  unzip -q sb.zip
  cp sb-admin-2/css/sb-admin-2.css ../css
  cp -R sb-admin-2/css/plugins ../css
  cp -R sb-admin-2/js/plugins ../js
  cd ..
  rm -rf tmp2_$$ sb.zip
fi

if [ ! -f "js/jquery-ui.min.js" ]; then
  echo "Retreiving jQuery UI"
  mkdir tmp2_$$ && cd tmp2_$$
  $RUNCURL ui.zip http://jqueryui.com/resources/download/jquery-ui-1.11.1.zip
  unzip -q ui.zip
  cp jquery-ui-${JQUERYUI}/jquery-ui.min.js ../js
  #cp jquery-ui-${JQUERYUI}/jquery-ui.theme.min.css ../css
  cd ..
  rm -rf tmp2_$$ ui.zip
fi

# ./fonts directory has been created by bootstrap already
if [ ! -f "fonts/fontawesome-webfont.woff" ]; then
  echo "Retreiving fonts/icons: FontAwesome"
  FA=font-awesome-${FONTAWESOME}
  $RUNCURL fa.zip http://fortawesome.github.io/Font-Awesome/assets/${FA}.zip
  unzip -q fa.zip
  mv $FA/css/font-awesome.min.css css
  mv $FA/fonts/* fonts
  rm -rf fa.zip ${FA}
fi

# concat + gzip js and css for production ...
# (might want to add app.js... + amtc-web.css ... not yet / dev)
( cd css && cat $CSSFILES > styles.css  )
( cd js && cat $JSFILES > jslibs.js )
for src in css/styles.css js/jslibs.js index.html; do
  gzip -c --best $src > $src.gz
done
# drop any source js/css libs, as concatenated versions are used
if [ ! -z "$DISTCLEAN" ]; then
  (cd css && rm $CSSFILES)
  (cd js && rm $JSFILES)
fi

### PHP libraries

if [ ! -d "lib/Slim" ]; then
  echo "Retreiving PHP slim"
  $RUNCURL slim.zip https://github.com/codeguy/Slim/zipball/master
  unzip -q slim.zip
  mv codeguy-Slim-*/Slim lib
  rm -rf codeguy-Slim-* slim.zip
fi

if [ ! -d "lib/php-activerecord" ]; then
  echo "Retreiving PHP activerecord"
  cd lib
  git clone https://github.com/jpfuentes2/php-activerecord.git
  rm -rf php-activerecord/{examples,test,.gitignore,.travis.yml,CHANGELOG,README.md,composer.json,phpunit.xml.dist,.git}
  cd ..
fi

[ -f config/.htpasswd ] || cp config/_htpasswd.default config/.htpasswd
if [ ! -f basic-auth/.htaccess ]; then
  cp basic-auth/_htaccess.default basic-auth/.htaccess
  perl -pi -e "s@AuthUserFile .*@AuthUserFile $SCRIPTPATH/config/.htpasswd@" basic-auth/.htaccess
fi
