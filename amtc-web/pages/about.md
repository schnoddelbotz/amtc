<a href="https://github.com/schnoddelbotz/amtc"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/e7bbb0521b397edbd5fe43e7f760759336b5e05f/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677265656e5f3030373230302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_green_007200.png"></a>

about amtc and amtc-web
=======================

amtc-web is a web frontend for the C tool amtc. amtc-web is distributed alongside
amtc and is intended for any user willing to power manage and control a fleet of
Intel vPro/AMT or AMD DASH equipped hosts. While amtc itself is a command line tool that can be
used to quickly control hosts, amtc-web tries to visually make most out of the information
that can be gathered via vPro, lets you [monitor and control](#/monitors/1) a bunch of PCs
with just a few clicks, [logs states](#/logs) which allows drawing some approximate
[engergy consumption charts](#/energy) and allows comfortable
[scheduled power management](#/schedule).

amtc was intially developed to power-control a single room equipped with 170 PCs.
For more than a year now, it was used to monitor and control about 500 of them.
It IS a fun project, currently allowing me to play with [EmberJS](http://emberjs.com/).

contribute
==========

Please feel free to [report issues](https://github.com/schnoddelbotz/amtc/issues) or
[fork amtc on github](https://github.com/schnoddelbotz/amtc) and send
[pull requests](https://github.com/schnoddelbotz/amtc/pulls)!

amtc / amtc-web license
=======================
I chose CC-BY (accident) first, but [was told](https://wiki.creativecommons.org/FAQ#Can_I_use_a_Creative_Commons_license_for_software.3F) that it was a bad idea.
Same story for [CC0](http://creativecommons.org/publicdomain/zero/1.0/).
Also liked shortness of [wtfpl](http://www.wtfpl.net/about/) but refused
to initially assoiciate that particular word with my github repo on my own.

So, finally, starting with 0.8.3alpha github release amtc and amtc-web are,
tadaa, released under the [MIT license](https://github.com/schnoddelbotz/amtc/blob/master/version/LICENSE.txt).

bundled 3rd party components
============================

In addition to the [LICENSES-3rd-party.txt](LICENSES-3rd-party.txt) file, which
contains all individual 3rd party license texts and which is also bundled with
any amtc release package made available on github, the following list is
here to name any 3rd party component that makes up amtc-web (in random order).

 * [Slim framework](http://www.slimframework.com/)
   * Copyright © 2012 Josh Lockhart [https://github.com/codeguy](https://github.com/codeguy)
   * [MIT license](http://www.slimframework.com/license)
 * [idiorm and paris](http://j4mie.github.io/idiormandparis/)
   * Copyright © 2010, Jamie Matthews
   * [BSD license](https://github.com/j4mie/idiorm/blob/master/idiorm.php)
 * [jQuery](https://jquery.org) and [jQueryUI](http://jqueryui.com)
   * Copyright © 2005, 2014 jQuery Foundation, Inc.
   * [MIT license](https://jquery.org/license/)
 * [emberJS](http://emberjs.com)
   * Copyright © 2014 Yehuda Katz, Tom Dale and Ember.js contributors
   * [MIT license](https://github.com/emberjs/ember.js/blob/master/LICENSE)
 * [ember-data](https://github.com/emberjs/data)
   * Copyright (C) 2011-2014 Tilde, Inc. and contributors.
   * Portions Copyright (C) 2011 LivingSocial Inc.
   * [MIT? license](https://github.com/emberjs/data/blob/master/LICENSE)
 * [showdownJS](https://github.com/showdownjs)
   * Copyright © 2007, John Fraser, [http://www.attacklab.net/](http://www.attacklab.net/)
   * Original Markdown copyright (c) 2004, John Gruber, [http://daringfireball.net/](http://daringfireball.net/)
   * [MIT license](https://github.com/showdownjs/showdown/blob/master/license.txt)
 * [momentJS](http://momentjs.com/)
   * Copyright © 2011-2014 Tim Wood, Iskren Chernev, Moment.js contributors
   * [MIT license](https://github.com/moment/moment/blob/develop/LICENSE)
 * [humane-js](https://github.com/wavded/humane-js)
   * Copyright © 2014 Marc Harter, <wavded@gmail.com>
   * [MIT license](https://github.com/wavded/humane-js) (see page bottom).
 * [twitter bootstrap](http://getbootstrap.com/)
   * Copyright © 2011-2014 Twitter, Inc
   * [MIT license](https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * [FontAwesome](http://fortawesome.github.io/Font-Awesome/)
   * Dave Gandy, [@davegandy](https://twitter.com/davegandy)
   * [SIL OFL 1.1 license](http://fortawesome.github.io/Font-Awesome/license/)
 * [SB Admin 2](http://startbootstrap.com/template-overviews/sb-admin-2/)
   * Copyright © 2013-2014 Iron Summit Media Strategies, LLC
   * [Apache 2.0 license](https://github.com/IronSummitMedia/startbootstrap/blob/gh-pages/LICENSE)
   * Included within SB Admin 2:
     * [morris.js](http://morrisjs.github.io/morris.js/)
          - Copyright © 2013, Olly Smith
          - [simplified BSD license](http://morrisjs.github.io/morris.js/#license)
     * [flot](http://www.flotcharts.org/)
          - Copyright © 2007-2014 IOLA and Ole Laursen
          - [MIT license](https://github.com/flot/flot/blob/master/LICENSE.txt)
     * [metisMenu](https://github.com/onokumus/metisMenu)
          - Copyright © 2014 Osman Nuri Okumuş
          - [MIT license](https://github.com/onokumus/metisMenu/blob/master/LICENSE)
     * [raphaelJS](https://github.com/DmitryBaranovskiy/raphael/)
          - Copyright © 2008 Dmitry Baranovskiy
          - [MIT license](http://raphaeljs.com/license.html)

The amtc binary does not include any 3rd party components - but won't work without being
linked against libCURL to do all the horsework of talking to AMT hosts.

 * [cURL](http://curl.haxx.se/)
   * Copyright © 1996 - 2014, Daniel Stenberg, <daniel@haxx.se>
   * [MIT/X derivate license](http://curl.haxx.se/docs/copyright.html)

As I am as clueless concerning liceneses as one could be,
I'm grateful for any hints in case something should be fixed.
