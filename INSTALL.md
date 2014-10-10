# Installing amtc and amtc-web

```bash
 * BIG FAT WARNING *

 This document is a draft for work-in-progress-thing-amtc-web2.
 It +/- reflects current state of the packaging and installation
 process, but amtc-web2 hasn't full amtc-web1 functionality
 yet -- ALPHA SOFTWARE - You've been warned!
```

amtc is a C program, it needs to be compiled before use.
amtc-web consists of a bunch of PHP, JavaScript and CSS files that
depend on third-party libraries that have to be fetched
from the net prior to first use of amtc-web upon github clone.

### Installation Options

 * Installing binary release packages
 * Building and installing (packages) from amtc/amtc-web sources

Both (all three) variants have dependencies, listed below, that
need to be satisfied prior to installation.

# Dependencies
amtc-web will download any JS/PHP library dependencies during build;
when installing binary packages, this step will already have been
done for you. However, amtc still depends on libcurl to run and
amtc-web won't web without a server.

As amtc binary packages are not repository-hosted, dependencies
should be met prior to installation (at least true for debian).
Building from source obviously has more dependencies than just
using the binary package - and creating packages has most.
Probably means: Use the binary packages to give amtc-web a try,
go the build route to look behind the scenes.

The section below lists dependencies for each OS and installation
route. For all systems, a vanilla, basic installation is assumed.


# Installation of binary packages

Generic instructions:

 * Install your webserver of choice - with PHP support
 * The example configuration files included with amtc-web assume apache
 * Install PHP with PDO support for your favourite database, supported by amtc-web (i.e. MySQL, SQLite ... more tbd).
 * When using something else than SQLite, prepare a DB to be used by amtc-web.
 * Install the binary package as downloaded from the
   [github amtc releases page](https://github.com/schnoddelbotz/amtc/releases)

## Binary package installation preconditions

amtc-web does not depend on a specific webserver, it just has
to support execution of PHP scripts. The binary packages assume
apache - for all others, you'll most likely have to touch
some config files to get it going. Use the commands below to
meet runtime dependencies of amtc and amtc-web:

 * `OSX` None. Simply install the amtc binary package!
 * `RPM` `sudo yum install httpd php-pdo`
 * `DEB` `sudo apt-get install libapache2-mod-php5 php5-curl php5-sqlite`

... where `RPM` stands for commands to be executed on RPM-based
systems (like RHEL/CentOS, Fedora...), `DEB` for debian and
`OSX` for the big cat.

## Binary package installation

```bash
# OSX. Click the .pkg *or*
$ sudo installer -tgt / -pkg /path/to/downloads/amtc_*.pkg

# RPM-based OS
# yum is able to catch and resolve any dependency problems here
$ sudo yum localinstall /path/to/downloads/amtc*.rpm

# DEBian
# the second command should fix dependency issues, if any
$ sudo dpkg -i /path/to/downloads/amtc*.deb
$ sudo apt-get install -f
```

The package (post-)installations try to
 * enable mod_alias ... (via a2enmod, only on DEB)
 * enable mod_php5 in httpd.conf (only on OSX)
 * set SELinux policy context on /etc/amtc-web to allow httpd to write there (RPM only)
 * restart apache to let amtc-web_httpd.conf take effect

Ideally, after successful package installation, you should be
able to visit [http://localhost/amtc-web/](http://localhost/amtc-web/) in your web browser now...


# Setup - Finalizing installation

Visit [http://localhost/amtc-web/](http://localhost/amtc-web/).
Hopefully, the setup of amtc-web should come up and ask you
for your database type and credentials etc. You will also be able
to specify the authenticator URL there -- a http basic
authentication protected web page that may then serve as
a authentication source for amtc-web.
When using the default 'integrated' authenticator, you
will be able to manage passwords using htpasswd against
/etc/amtc-web/users.ht. The file contains the default account,
that can be used to login after successfully finishing setup:
Login as user: admin, password: amtc.

If it went OK so far, try creating a room with some hosts
that reflect your desired setup. Play around ... and if you
want to keep amtc-web, just make sure to enable SSL for your
web server. Have fun!


# Building from source, packaging

For the courious...

## Build preconditions

Building amtc requires libcurl development headers and xdd, which comes as
part of vim. Building amtc-web relies on curl itself to fetch JS/PHP
libraries during 'build'. Build preconditions per platform:

 * `OSX` Install XCode via AppStore
 * `RPM` `sudo yum groupinstall "Development tools"`
 * `RPM` `sudo yum install git vim-common libcurl-devel gnutls-devel libcurl gnutls`
 * `DEB` `sudo apt-get install git curl vim-common libcurl4-gnutls-dev libgnutls-dev build-essential`

## Package build preconditions

The regular system packaging tools will be needed in addition to the build tools installed above.

 * `OSX` Included with XCode (pkgbuild). No action required.
 * `RPM` `sudo yum install rpm-build`
 * `DEB` `sudo apt-get install dh-make devscripts`

## Building, building package, installing package

After having satisfied above build dependencies, try:
```bash
$ git clone https://github.com/schnoddelbotz/amtc
$ cd amtc
```
the most interesting [Makefile](../blob/master/Makefile) targets available are:

 * `make amtc` to only build the C target
 * `make amtc-web` to only create a usable dev-environment in amtc-web/
 * `make package` create a package of amtc for current platform
 * `make install-package` creates and installs a package
 * `make purge` is _DANGEROUS_. Will not only uninstall, but trash config and (SQLite-)DB.


# Disclaimer

I can not be held responsible for the correctness, completeness or quality
of the information provided in this document. Therefore, liability claims
regarding any damages caused by the use of any information provided, including
information which is incomplete or incorrect, will be discarded.
