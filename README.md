amtc
====

amtc - Intel AMT/vPro mass management tool.

A threading C implementation of the great perl [amttool](http://www.kraxel.org/cgit/amtterm/tree/amttool).

features
========

* get status and power control intel(R) vPro(tm) machines using intel AMT(tm)
* lightweight application, only depends on curl and pthreads
* threading allows parallel execution of AMT commands, making it really fast 
  (query 180 PCs in about one second)
* currently builds on linux and osx

usage
=====

```bash
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
  -t(imeout) in seconds, for curl and tcp scans
  -w(ait)    seconds after each thread created
  -T(LS)  [notyet]
  -p(asswdfile) [notyet; set AMT_PASSWORD env var]
```

todo
====
+ add TLS support
+ finish port/OS scanner; apply operations only if a given port is open
+ supply bridge for web frontend
