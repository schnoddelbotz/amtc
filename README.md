amtc
====

`amtc` - Intel [vPro](http://de.wikipedia.org/wiki/Intel_vPro) [AMT](http://en.wikipedia.org/wiki/Intel_Active_Management_Technology) / [WS-Management](http://en.wikipedia.org/wiki/WS-Management) mass remote power management tool

features
========

* performs vital AMT operations (info, powerup, powerdown, reset...)
* threaded, thus fast (queries 180 Core i5 PCs in a quarter of a second (using EOI))
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

 amtc v0.6.0 - Intel AMT & WS-Man OOB mass management tool
                     https://github.com/schnoddelbotz/amtc
 usage
  amtc [-actions] [-options] host [host ...]

 actions
  -I(nfo)  query powerstate via AMT (default)
  -U(p)    powerup given host(s)
  -D(own)  powerdown
  -C(ycle) powercycle
  -R(eset) reset

 options
  -d          for AMT 9.0+ hosts - use WS-Man/DASH
  -b(oot)     specify boot device ('pxe' or 'hdd') [notyet]
  -m(aximum)  number of parallel workers to use [40]
  -p(asswdfile) specify file containing AMT password
  -j(son)     produces JSON output of host states
  -q(uiet)    only report unsuccessful operations
  -r(DP)-scan probe TCP port 3389 for OS detection
  -s(SH)-scan probe TCP port 22   for OS detection
  -t(imeout)  in seconds, for amt and tcp scans [5]
  -g(nutls)   will use TLS and port 16993 [notls/16992]
  -n(oVerify) will skip cert verification for TLS
  -v(erbose)  detailed progress, debug by using -vvv
  -w(ait)     in seconds, after each pc. one thread.

```

status
======
ever-pre-1.0. just for fun. against all odds. works for me.

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

building
========
```
# OSX: Install XCode including CommandLineTools,
#      Install gnutls, [homebrew](http://brew.sh) recommended.
cd src
make

# debianoid: 
apt-get install libcurl3 libcurl4-gnutls-dev libgnutls-dev build-essential
cd src ; make

# RHELoid: 
yum install libcurl-devel gnutls-devel libcurl gnutls
cd src ; make
# or
rpmbuild -ba amtc.spec

# Windows: install cygwin's curl,gnutls and pthreads (-dev) packages, make and gcc
cd src
make
```

license
=======
gnah. `amtc` didn't make it to github by accident. it's open. it's yours. take it.
i'll most likely survive, even if you don't [CC BY](http://creativecommons.org/licenses/by/3.0/)
me - in case you really intend to recycle some of these bits ...

alternatives
============
- [amttool](http://www.kraxel.org/cgit/amtterm/tree/amttool):
  Without amttool, there would be no amtc. Thanks! Also supports configuration, which amtc doesn't.
  amttool is implemented in perl and intended for interactive, verbose single-host operation.
  amtc is implemented in C, and by using threads optimized for quick, succinct (non-)interactive mass-operation.
- [amttool_tng](http://sourceforge.net/projects/amttool-tng):
  The next generation. Even more config stuff.
- [vTul](https://github.com/Tulpep/vTul):
  A windows powershell based GUI. Again, completely different story.
- for DASH-only use, the best choice for windows CLI scenarios is most likely AMD's [dashcli](http://developer.amd.com/tools-and-sdks/cpu-development/client-management-tools-for-dmtf-dash/). Find MS SCCM plugins there, too.
- bootstrap your own using the [intel AMT SDK](http://software.intel.com/sites/manageability/AMT_Implementation_and_Reference_Guide)
