amtc
====

`amtc` - Intel [vPro](http://de.wikipedia.org/wiki/Intel_vPro) [AMT](http://en.wikipedia.org/wiki/Intel_Active_Management_Technology) / [WS-Management](http://en.wikipedia.org/wiki/WS-Management) mass remote power management tool

features
========

* performs vital AMT operations (info, powerup, powerdown, reset...)
* brutally fast (queries 180 Core i5 PCs in a quarter of a second)
* allows mass-powerups/downs/... using a custom delay
* lightweight C application, only depends on libcurl and pthreads
* currently builds fine on linux and OSX (and windows via cygwin)
* allows quick and comfortable mass-power control via shell (works!) and...
* comes with a web interface for GUI-based
  * power-live-monitoring (works)
  * including OS tcp port probing/detection (works)
  * power/OS-monitoring logging with graphing (logging works, graph undone)
  * remote-management (works, needs cleanup before upload)
* acts as a tool for flexible and robust scheduled remote power management

The [amtc wiki](https://github.com/schnoddelbotz/amtc/wiki) features more details.

usage
=====

```

 amtc v0.5.0 - Intel AMT & WS-Man OOB mass management tool
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
alpha. just for fun. against all odds. works for me.

building
========
+ OSX: Install XCode including CommandLineTools; type make
+ Debianoid: apt-get install libcurl3 libcurl4-gnutls-dev libgnutls-dev build-essential; type make
+ Windows: install cygwin's curl and pthreads (-dev) packages, make and gcc; type make

todo
====
+ add quiet mode (error-only) (for cron / free scheduled remote power management)
+ allow cert verification for TLS
+ support hosts with AMT < v6.0 ?

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
- bootstrap your own using the [intel AMT SDK](http://software.intel.com/sites/manageability/AMT_Implementation_and_Reference_Guide)
