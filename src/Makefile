
# Makefile - part of amtc
# https://github.com/schnoddelbotz/amtc
#
# Makefile for amtc

LDLIBS=-L/usr/local/lib -lcurl -lpthread
CFLAGS=-I/usr/local/include -I. -Wall
AMTCV=$(shell cat ../version)
APP=amtc-$(AMTCV)
OS=$(shell uname -s)
WITH_GPL_AMTTERM ?= Yes
AMTTERM_TARGETS=$(shell test "${WITH_GPL_AMTTERM}" =  "Yes" && echo amtterm/tcp.c amtterm/redir.c || echo '' )
AMTTERM_DEFINE=$(shell test "${WITH_GPL_AMTTERM}" = "Yes" && echo '-DWITH_GPL_AMTTERM' || echo '' )

HEADERS=amtc_usage cmd_powerdown cmd_powerup cmd_info cmd_powerreset \
	cmd_powercycle wsman_info wsman_info_step2 wsman_up wsman_down \
	wsman_reset wsman_xenum wsman_xenum_step2 wsman_pxeboot wsman_hddboot wsman_bootconfig \
	wsman_solredir_enable wsman_solredir_disable \
	wsman_webui_enable wsman_webui_disable \
	wsman_ping_enable wsman_ping_disable \
	wsman_shutdown_graceful wsman_reset_graceful

all: amtc

amtc: amtc-amtterm.c amtc.c $(AMTTERM_TARGETS)
	$(CC) $(CFLAGS) $(AMTTERM_DEFINE) $(AMTTERM_TARGETS) amtc.c $(LDLIBS) -o amtc

amtterm/tcp.c: amtterm/.git
amtterm/redir.c: amtterm/.git
amtterm/.git:
	if [ -d ../.git ]; then \
		cd .. ; git submodule init; git submodule update; cd src; \
	else \
		rm -rf amtterm; git clone git://git.kraxel.org/amtterm; \
	fi

amtc-amtterm.c: amtterm/.git
	sed -e '1,/* ----/d' -e '/static void usage/,1000d' amtterm/amtterm.c > amtc-amtterm.c

amtc.c: amt.h

amt.h:
	cp amtc_usage amtc_usage.bak
	perl -pi -e 's/#AMTCV#/$(AMTCV)/' amtc_usage
	for H in $(HEADERS); do xxd -i $$H $$H.h; done
	mv amtc_usage.bak amtc_usage
	cat amtc_usage.h cmd_*.h wsman_*.h wsman.h > amt.h
	perl -pi -e 's/(0x\S\S)$$/$$1, 0x00/' amt.h
	perl -pi -e 's/(\d+);$$/$$1 + 1 .";"/e' amt.h

# the file is on the repo to avoid pandoc build dependency.
# upon changes to .md version, .1 should be deleted+rebuilt+comitted
man/man1/amtc.1:
	mkdir -p man/man1
	pandoc amtc.1.md -s -t man > man/man1/amtc.1

clean:
	rm -f cmd_*.h wsman_*.h amtc_usage.h amt.h amtc *.o

