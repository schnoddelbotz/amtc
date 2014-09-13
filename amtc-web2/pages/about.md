<a href="https://github.com/schnoddelbotz/amtc"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/e7bbb0521b397edbd5fe43e7f760759336b5e05f/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677265656e5f3030373230302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_green_007200.png"></a>

About amtc-web
==============

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

Feedback
========

Send email to [jan at hacker.ch](mailto:jan@hacker.ch?subject=amtc)

Contribute
==========

[Fork me on github](https://github.com/schnoddelbotz/amtc)

Third party components
======================

amtc-web heavily depends on many open source tools. They are not
included in my github repository, but fetched during the build process
of amtc-web. Complete list of thirdparty components used in amtc-web:

 * [jQuery](http://jquery.com)
 * [jQuery](http://jqueryui.com)
 * [emberJS](http://emberjs.com)
 * [handlebars](http://handlebarsjs.com)
 * [showdown](https://github.com/coreyti/showdown)
 * [momentJS](http://momentjs.com)
 * [humane](https://github.com/wavded/humane-js)
 * [Twitter Bootstrap](http://getbootstrap.com/)
 * [Font Awesome](http://fortawesome.github.io/Font-Awesome)
 * [SB Admin 2](http://startbootstrap.com/template-overviews/sb-admin-2/)
  * includes: ... tbd

