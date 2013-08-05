amtc
====

amtc - Intel [vPro](http://de.wikipedia.org/wiki/Intel_vPro)/[AMT](http://en.wikipedia.org/wiki/Intel_Active_Management_Technology) mass remote power management tool

features
========

* get status and power control AMT machines using 
* lightweight application, only depends on curl and pthreads
* really fast (queries 180 PCs in about one second)
* currently builds on linux and OSX

usage
=====

```bash

 amtc v0.2.1 - Intel AMT(tm) mass management tool 
                               jan@hacker.ch/2013

 usage:
  amtc [actions] [options] host [host ...]

 actions:
  -i(nfo) query powerstate via AMT (default)
  -u(p)   powerup given host(s) 
  -d(own) powerdown
  -r      powercycle
  -R      powerreset
  -s(can) [port] - TCP port scan [notyet]
 options:
  -t(imeout) in seconds, for curl and tcp scans [ 5]
  -w(ait)    seconds after each thread created  [ 0]
  -m(aximum) number of parallel workers to use  [40]
  -T(LS)  [notyet]
  -p(asswdfile) [notyet; export AMT_PASSWORD ]

```

status
======
alpha. just for fun. against all odds. works for me.

todo
====
+ add TLS support
+ finish port/OS scanner; apply operations only if a given port is open
+ supply bridge for web frontend
+ add quiet mode (error-only) (for cron / free scheduled remote power management)
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
