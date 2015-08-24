% AMTC(1)
% jan@hacker.ch
% November 8, 2014

# NAME

amtc - Intel vPro/AMT remote power management tool

# SYNOPSIS

amtc [*action*] [*options*] [*host(s)*]...

# DESCRIPTION

amtc uses libcurl and pthreads to talk to intel vPro
equipped client machines in a parallel fashion.
It can be used to efficently query and control powerstate
of a bunch of machines with a single command call.

If no *action* is specified, power state is queried (`-I`).

# ACTIONS

-I
:   Info - query power state

-U
:   Power-Up - power-on from any sleep- or power-off state

-D
:   Power-Down - performs an unclean shutdown, power off

-C
:   Power-Cycle - equivalent for turning off and on again

-R
:   Reset - mimics pressing a workstation's reset button

-S
:   Shutdown - For AMT 9.0+ systems running windows, request shutdown

-B
:   reBoot - For AMT 9.0+ systems running windows, initiate reboot

-L
:   List WS-Man classes available

-E
:   Enumerate given WS-Man class (rudimentary, undecoded output)

-M
:   Modify boolean configuration parameters

-T
:   Start AMT SOL terminal session for a single host

# OPTIONS

-5
:   for AMT 5.0 hosts

-d
:   for AMT 9.0+ hosts - use WS-Man/DASH

-m(aximum)
:   number of parallel workers to use [40]

-p(asswdfile)
:   specify file containing AMT password

-j(son)
:   produces JSON output of host states

-q(uiet)
:   only report unsuccessful operations

-r(DP)-scan
:   probe TCP port 3389 for OS detection

-s(SH)-scan
:   probe TCP port 22   for OS detection

-e(nforce)
:   rdp/ssh probes, regardless of AMT state

-t(imeout)
:  in seconds, for amt and tcp scans [5]

-g(nutls)
:   will use TLS and port 16993 [notls/16992]

-c(acert)
:   specify TLS CA cert file [/etc/amt-ca.crt]

-n(oVerify)
:   will skip cert verification for TLS

-v(erbose)
:   detailed progress, debug by using -vvv

-w(ait)
:   in seconds / float, after each pc. one thread.


# ENVIRONMENT

AMT_PASSWORD
:   may be set and will be used if `-p` wasn't given.


# BUGS

Please report new ones on the github issue tracker (see URL below).
On OSX, connection timeout does currently not affect OS probes.
IPv6 OS probes and name resolution need to be fixed.


# SEE ALSO

See *README.md* file on the github repository for mor details.

To have a web-frontend for amtc and scheduled power-management, take
a look at amtc-web. Once installed, amtc-web comes with it own
documentation available at <http://localhost/amtc-web/#/pages/about>.

amtc on github: <https://github.com/schnoddelbotz/amtc>.

