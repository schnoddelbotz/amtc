Name:		amtc
Version:	#AMTCV#
Release:	1%{?dist}
Summary:	Threaded remote power management commandline tool for intel vPro/AMT&DASH hosts

Group:		Applications/System
License:	CC BY 3.0
URL:		https://github.com/schnoddelbotz/amtc
Source0:	https://github.com/schnoddelbotz/amtc/archive/amtc-#AMTCV#.tar.gz
BuildRoot:	%(mktemp -ud %{_tmppath}/%{name}-%{version}-%{release}-XXXXXX)

BuildRequires:  libcurl-devel
BuildRequires:  gnutls-devel
BuildRequires:  vim-common
Requires:       libcurl
Requires:       gnutls

################################################################################
# binary RPM: amtc

%description
amtc is a simple command line tool, implemented in C, that can quickly
control PCs that have out-of-band remote power management capabilities
in the form of intel vPro/AMT or AMD DASH. amtc's key focus is not
to support all SOAP operations AMT/DASH is aware of -- instead
it concentrates only on vital OOB operations (on/off/reset/...).
amtc can be combined with amtc-web to have a fluffy web GUI
for power management tasks and power state logging/graphing.
Combining amtc (or amtc-web) with cron makes scheduled power management.

%prep
%setup -q

%build
make

%install
rm -rf %{buildroot}
make install DESTDIR=%{buildroot}
make rpmfixup DESTDIR=%{buildroot}

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
%doc
/usr/bin/amtc
/usr/share/man/man1/amtc.1.gz

%changelog

################################################################################
# binary RPM: amtc-web

%package web
Summary:	Remote power management Web-GUI for intel vPro/AMT&DASH hosts, using amtc
Group:		Applications/Internet
BuildArch:  noarch
Requires: httpd,php,php-pdo

%description web
amtc-web is not only a fluffy web-GUI for amtc, brewed in PHP --
its basic duty of managing lists of hosts to control via amtc-web
can also be used to effectively power control these hosts using the CLI.
It uses jQuery client-side and supports PHP PDO databases server-side.

%files web
%defattr(-,root,root,-)
/etc/httpd/conf.d
/usr/share/amtc-web
%dir %attr(0770,apache,amtc-web) /etc/amtc-web
%dir %attr(2770,apache,amtc-web) /var/lib/amtc-web
%config(noreplace) %attr(0644,root,root) %{_sysconfdir}/cron.d/amtc-web
%config(noreplace) %{_sysconfdir}/amtc-web/.htpasswd
%config(noreplace) %{_sysconfdir}/amtc-web/amtc-web_httpd.conf
%attr(0755,root,root) /var/lib/amtc-web/.htaccess

%pre web
/usr/bin/getent group amtc-web >/dev/null || \
  /usr/sbin/groupadd -r amtc-web
/usr/bin/getent passwd amtc-web >/dev/null || \
  /usr/sbin/useradd -r -g amtc-web -d /var/lib/amtc-web -s /sbin/nologin \
    -c "amtc-web user" amtc-web
exit 0

%post web
chcon -R -t httpd_sys_rw_content_t /etc/amtc-web
service httpd reload || service httpd start
exit 0
