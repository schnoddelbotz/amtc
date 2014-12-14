% AMTC(1)
% jan@hacker.ch
% November 8, 2014

# NAME

amtc - Intel vPro/AMT remote power management tool

# SYNOPSIS

amtc [*action*] [*options*] [*host(s)*]...

# DESCRIPTION

If no *action* is specified, power state is queried (`-I`).

# ACTIONS

-I
:   Info - query power state

-U
:   Power-Up

-D
:   Power-Down

-C
:   Power-Cycle

-R
:   Reset

# OPTIONS

-r
:   port scan RDP

-s
:   port scan SSH

... to be continued ...


# ENVIRONMENT

AMT_PASSWORD
:   may be set and will be used if `-p` wasn't given.

# BUGS

Please report new ones on the github issue tracker (see URL below).
To give this manpage section more meaning, the bug that `-r` and `-s`
will both not resolve hostnames and simply fail if IPs weren't used
instead ... is documented/hidden in here.

# SEE ALSO

See *README.md* file on the github repository for mor details.

To have a web-frontend for amtc and scheduled power-management, take
a look at amtc-web. Once installed, amtc-web comes with it own
documentation available at <http://localhost/amtc-web/#/pages/about>.

amtc on github: <https://github.com/schnoddelbotz/amtc>.

