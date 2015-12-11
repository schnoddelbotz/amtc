v0.8.5
======

Released December 11, 2015.

## amtc ##

- integrate boot device selection support provided by [maratoid](https://github.com/maratoid/amtc) (`-X` and `-H`)
- avoid 'NSS error -12272 (SSL_ERROR_BAD_MAC_ALERT)' for TLS connection attempts on RHEL7+
- integrated [amtterm](https://www.kraxel.org/cgit/amtterm/) functionality (if compiled with WITH_GPL_AMTTERM=Yes) (`-T`)
- OS X El Capitan: Install amtc in /usr/local/bin due to [SIP](https://en.wikipedia.org/wiki/System_Integrity_Protection)
- add AMT 9.0 graceful reboot command (`-B`, only works with Windows as OS)
- add a very basic manpage

## amtc-web ##

- fix performance issues of the power state log DB tables
- use more generic Ember.onerror to handle errors
- add possibility to correctly delete hosts and rooms through GUI


v0.8.5~alpha3
=============

Released May 5, 2015.

## amtc-web ##

- amtc-web1-style statelogs available again, now as SVGs!
- drop redundant rest-api routes using catch-many functions
- re-coloured host states in host control view, streamlined icons

The 'logdays' view has been added to the DB to support statelog.
There is no auto-update feature for the DB yet(?) - update manually.


v0.8.5~alpha2
=============

Released Apr 25, 2015.

## amtc-web ##

- Update to latest emberjs/ember-data releases
- Use emberjs template compiler if nodejs is present on build host,
  improves app performance
- Add some infos on the /#systemhealth page
- Reworked login redirect to support bookmarks into any route/view
- Only rely on FontAwesome - no more bootstrap icon mix
- Add buttons on /#systemhealth page to let users reset
  monitoring job status and flush state logs

Fixes:

- Fix/cleanup many template issues, split index.html into .hbs templates
- Prevent flashing bolt CSS3 animation from breaking amtc-web on some
  mobile devices (e.g. iOS)
- Scheduler used invalid AMT optionset when querying more than 180
  clients of same optionset


v0.8.5~alpha1
=============

Released on Mar 15, 2015.

This is a first ALPHA release coming with pre-built binaries/packages for
* RHEL 7 / CentOS7 / Debian 7 / Ubuntu 14.04 (x86_64)
* OS X Yosemite
* Raspbian (arm)

Changelog below lists major changes since release v0.8.2.

## amtc ##

* Supports some simple configuration items for AMT non-enterprise boxes:
  - enable/disable ping replies in power-off state,
  - enable/disbale SOL / Serial Over LAN
  - enable/disable AMT Web UI
* support for AMT 9.0 graceful shutdown added
* RDP/SSH portscans resolve hostnames, can be enforced if AMT unavailable

## amtc-web ##

* Web-GUI based on EmberJS, Twitter BootStrap ...
* scheduled tasks fully configurable via GUI
* state logging aggregates rooms to make most of amtc's threading capability
* amtc execution decoupled from amtc-web through DB
* binary packages will use cron.d or a LaunchDaemon (OSX) for background exec
* [RPM] user 'amtc-web' is created for background amtc exec
* [OSX/DEB] the webserver user is used for amtc exec

## Known issues (as of v0.8.5~alpha1) ##

* No statelog graphs as in amtc-web1 yet
* Current DB-stored monitoring interval of 1 minute is not GUI-accessible
* In contrast to RPM, the (single) DEB will install amtc and amtc-web (dto OSX)

