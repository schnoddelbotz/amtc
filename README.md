amtc [![Build Status](https://travis-ci.org/schnoddelbotz/amtc.svg?branch=master)](https://travis-ci.org/schnoddelbotz/amtc)
================================

`amtc` - Intel [vPro](http://de.wikipedia.org/wiki/Intel_vPro) [AMT](http://en.wikipedia.org/wiki/Intel_Active_Management_Technology) / [WS-Management](http://en.wikipedia.org/wiki/WS-Management) mass remote power management tool

features
========

* performs vital AMT operations (info, powerup, powerdown, reset...)
* threaded, thus fast (queries 180 Core i5 PCs in a quarter of a second (using EOI and no TLS))
* allows mass-powerups/downs/... using a custom delay
* lightweight C application, only depends on libcurl, gnutls and pthreads
* currently builds fine on linux and OSX (and windows via cygwin; unverified since 0.4.0)
* allows quick and comfortable mass-power control via shell and...
* comes with a ajaxish PHP-based web interface called `amtc-web`,
  that depends on PHP's PDO database layer to provide a GUI for...
  * realtime power state monitoring via AMT© including OS TCP port probing/detection (#screenshot)
  * anachronous OOB power control using a database-driven job queue (#screenshot)
  * power/OS-monitoring logging with graphing (#screenshot)
  * management of master file data like rooms and hosts to control
  * setup (of atmc-web itself, i.e. providing database connection details etc.)
* acts as a tool for flexible and robust scheduled remote power management (which is true for amtc itself and amtc-web; amtc-web just adds another layer of comfort regarding shell interaction with your many hosts).

The [amtc wiki](https://github.com/schnoddelbotz/amtc/wiki) features more details.


usage
=====

```
 amtc v0.8.4 - Intel AMT & WS-Man OOB mass management tool
                     https://github.com/schnoddelbotz/amtc
 usage
  amtc [-actions] [-options] host [host ...]

 actions
  -I(nfo)     query powerstate via AMT [default]
  -U(p)       powerup given host(s)
  -D(own)     powerdown
  -C(ycle)    powercycle
  -R(eset)    reset
  -S(hutdown) using AMT graceful shutdown (AMT 9.0+)
  -L(ist)  valid wsman <classname>s for -E(numeration)
  -E(numerate)<classname>       enumerate/list settings
  -M(odify)   <setting>=<value> modify wsman settings
              where supported settings: webui,sol or ping
              and supported values    : on or off
 options
  -5          for AMT 5.0 hosts
  -d          for AMT 9.0+ hosts - use WS-Man/DASH
  -m(aximum)  number of parallel workers to use [40]
  -p(asswdfile) specify file containing AMT password
  -j(son)     produces JSON output of host states
  -q(uiet)    only report unsuccessful operations
  -r(DP)-scan probe TCP port 3389 for OS detection
  -s(SH)-scan probe TCP port 22   for OS detection
  -e(nforce)  rdp/ssh probes, regardless of AMT state
  -t(imeout)  in seconds, for amt and tcp scans [5]
  -g(nutls)   will use TLS and port 16993 [notls/16992]
  -c(acert)   specify TLS CA cert file [/etc/amt-ca.crt]
  -n(oVerify) will skip cert verification for TLS
  -v(erbose)  detailed progress, debug by using -vvv
  -w(ait)     in seconds / float, after each pc. one thread.

 examples
  query powerstate of <AMT-9.0-hosts named host-a and host-b
   $ amtc host-a host-b
  power up some AMT 9.0 hosts using wsman and 5-second-delay
   $ amtc -dUw 5 host-c host-d host-e
  enable SOL (Serial over LAN on TCP port 16994)
   $ amtc -M sol=on host-f

```

status
======
ever-pre-1.0. just for fun. against all odds. works for me.

amtc 0.8.0 introduced the -E option, which serves for retreiving system
configuration and asset management data. Currently, amtc will not parse
those replies and just dump the raw SOAP reply. amtc-web currently
offers no way yet to retreive/display those values. This will improve,
sooner or later... stay tuned.

Honestly, in some aspects, `amtc` [still] is a hack. The most obvious
one is: amtc has no clue of SOAP. It dumbly replays control commands
I once wiresharked (see the cmd_* and wsman_* files in src dir).
Other tools available most likely do the right thing™ and use
a real SOAP library like [gSOAP](http://www.cs.fsu.edu/~engelen/soap.html).
If you're hit by this hack, please file a bug of an amtc-run
using -vvvv option -- thanks!

Give `amtc-web` a testdrive here:
<a href="http://jan.hacker.ch/projects/amtc/demo">amtc - vPro/AMT GUI demo site</a>,
The account required for admin-access (e.g. room management) is:
```
username: joe
password: foo
AMT password: C@mp1eXsuxx
```
Note that the demosite's config file is read-only, which means you can
happily create/edit rooms and pcs but NOT change basic config settings
like which DB or password file to use. This also means if you want to
test amtc-web not only by clicking around but by turning your own home
PC on or off using that demo website, you have to set your AMT password 
to the one stated above. Also note that every full hour, a sane default 
test database will be restored.

I'll try to put some more time into amtc-web2 to get the new GUI ready soon...


building
========
```
# OSX: Install XCode including CommandLineTools,
#      Install gnutls, [homebrew](http://brew.sh) recommended.
make

# debianoid: 
sudo apt-get install libcurl3 libcurl4-gnutls-dev libgnutls-dev build-essential
make
sudo make install

# ... or, create a .deb package (has additional build requirements)
sudo apt-get install dh-make devscripts
make deb
sudo dpkg -i ../amtc_xxx.deb

# RHELoid: 
sudo yum install libcurl-devel gnutls-devel libcurl gnutls
make

# ... or, create a .rpm package - requires ....
make rpm

# Windows: install cygwin's curl,gnutls and pthreads (-dev) packages, make and gcc
make
```

license
=======
This project is published under the [MIT license](../master/LICENSE.txt).
It heavily relies on bundled 3rd party OSS components that are listed in the
in-app ['about' page](../master/amtc-web2/pages/about.md) of amtc-web;
their individual license texts have been bundled into 
[LICENSES-3rd-party.txt](../master/amtc-web2/LICENSES-3rd-party.txt). That file is also
distributed with any [release of amtc](https://github.com/schnoddelbotz/amtc/releases).

alternatives
============
- [amttool](http://www.kraxel.org/cgit/amtterm/tree/amttool):
  Without amttool, there would be no amtc. Thanks! 
  amttool is implemented in perl and intended for interactive, verbose single-host operation.
  amtc is implemented in C, and by using threads optimized for quick, succinct (non-)interactive mass-operation.
- [amttool_tng](http://sourceforge.net/projects/amttool-tng):
  The next generation. Even more config stuff.
- [vTul](https://github.com/Tulpep/vTul):
  A windows powershell based GUI. Again, completely different story.
- for DASH-only use, the best choice for windows CLI scenarios is most likely AMD's [dashcli](http://developer.amd.com/tools-and-sdks/cpu-development/client-management-tools-for-dmtf-dash/). Find MS SCCM plugins there, too.
- bootstrap your own using the [intel AMT SDK](http://software.intel.com/sites/manageability/AMT_Implementation_and_Reference_Guide)
