Name:		amtc
Version:	0.8.4
Release:	1%{?dist}
Summary:	Threaded remote power management commandline tool for intel vPro/AMT&DASH hosts

Group:		Applications/System
License:	CC BY 3.0
URL:		https://github.com/schnoddelbotz/amtc
Source0:	https://github.com/schnoddelbotz/amtc/archive/amtc-0.8.4.tar.gz
BuildRoot:	%(mktemp -ud %{_tmppath}/%{name}-%{version}-%{release}-XXXXXX)

BuildRequires:  libcurl-devel,gnutls-devel
Requires: libcurl,gnutls

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
/usr/share/amtc-web
/etc/amtc-web
/etc/httpd/conf.d
/var/lib/amtc-web

%post web
chown apache /etc/amtc-web /var/lib/amtc-web
chcon -R -t httpd_sys_rw_content_t /etc/amtc-web
service httpd reload || service httpd start
