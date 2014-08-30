#!/bin/bash -e

# download required JS libraries.
JQUERY=2.1.1
BOOTSTRAP=3.2.0
HANDLEBARS=1.3.0
EMBERJS=1.7.0
EMBERDATA=1.0.0-beta.9

function FetchIfNotExists {
  CheckFile=$1
  FileURL=$2
  if [ ! -f "$CheckFile" ]; then 
    echo "Retreiving $FileURL ..."
    curl --insecure -Lo "$CheckFile" "$FileURL"
  fi
}

mkdir -p js css
FetchIfNotExists js/jquery.min.js http://code.jquery.com/jquery-${JQUERY}.min.js
FetchIfNotExists js/jquery.min.map http://code.jquery.com/jquery-${JQUERY}.min.map
FetchIfNotExists js/handlebars.js http://builds.handlebarsjs.com.s3.amazonaws.com/handlebars-v${HANDLEBARS}.js
FetchIfNotExists js/emberjs.min.js http://builds.emberjs.com/tags/v${EMBERJS}/ember.min.js
FetchIfNotExists js/ember-data.min.js http://builds.emberjs.com/tags/v${EMBERDATA}/ember-data.min.js
FetchIfNotExists js/showdown.js https://github.com/coreyti/showdown/raw/master/src/showdown.js
FetchIfNotExists js/moment.js http://momentjs.com/downloads/moment.js
FetchIfNotExists js/humane.min.js https://raw.githubusercontent.com/wavded/humane-js/master/humane.min.js
FetchIfNotExists css/humane-original.css https://raw.githubusercontent.com/wavded/humane-js/master/themes/original.css

if [ ! -f "js/bootstrap.js" ]; then 
  echo "Retreiving Twitter bootstrap ..."
  mkdir tmp_$$ && cd tmp_$$
  curl --insecure -Lso bs.zip https://github.com/twbs/bootstrap/releases/download/v${BOOTSTRAP}/bootstrap-${BOOTSTRAP}-dist.zip
  unzip bs.zip
  cd bootstrap-${BOOTSTRAP}-dist
  mv fonts ../..
  mv css/* ../../css
  mv js/* ../../js 
  cd ../..
  rm -rf tmp_$$ bs.zip
fi

if [ ! -f "js/sb-admin-2.js" ]; then
  echo "Retreiving SB Admin 2"
  mkdir tmp2_$$ && cd tmp2_$$ 
  curl --insecure -Lso sb.zip http://startbootstrap.com/downloads/sb-admin-2.zip
  unzip sb.zip
  cp sb-admin-2/css/sb-admin-2.css ../css
  cp -R sb-admin-2/css/plugins ../css
  cp -R sb-admin-2/js/plugins ../js
  cp sb-admin-2/js/sb-admin-2.js ../js
  cd ..
  rm -rf tmp2_$$ sb.zip
fi
