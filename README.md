amtc
====

amtc - Intel AMT/vPro mass management tool.

A threading C implementation of the great perl [amttool](http://www.kraxel.org/cgit/amtterm/tree/amttool).

features
========

* get status and power control [intel(R) vPro(tm)](http://de.wikipedia.org/wiki/Intel_vPro) machines using [intel(R) AMT(tm)](http://en.wikipedia.org/wiki/Intel_Active_Management_Technology)
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

todo
====
+ add TLS support
+ finish port/OS scanner; apply operations only if a given port is open
+ supply bridge for web frontend
