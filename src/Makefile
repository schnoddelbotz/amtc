#LFLAGS=-lcurl -lssl -lcrypto -lpthread
LFLAGS=-lcurl -lpthread -lgnutls -lgcrypt
CFLAGS=-I. -Wall
AMTCV=$(shell cat ../version)

HEADERS=amtc_usage cmd_powerdown cmd_powerup cmd_info cmd_powerreset cmd_powercycle wsman_info wsman_info_step2 wsman_up wsman_down wsman_reset

amtc: amt.h 
	$(CC) -o amtc amtc.c $(CFLAGS) $(LFLAGS)

amt.h:
	perl -pi -e 's/v(\d\.\d\.\d)/v$(AMTCV)/' amtc_usage
	for H in $(HEADERS); do xxd -i $$H $$H.h; done
	cat amtc_usage.h cmd_*.h wsman_*.h > amt.h
	perl -pi -e 's/(0x\S\S)$$/$$1, 0x00/' amt.h
	perl -pi -e 's/(\d+);$$/$$1 + 1 .";"/e' amt.h

clean:
	rm -f cmd_*.h wsman_*.h amtc_usage.h amt.h amtc *.o
