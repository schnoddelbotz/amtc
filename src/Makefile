#LFLAGS=-lcurl -lssl -lcrypto -lpthread
LFLAGS=-lcurl -lpthread
CFLAGS=-I. -Wall

HEADERS=amtc_usage cmd_powerdown cmd_powerup cmd_info cmd_powerreset cmd_powercycle

amtc: amt.h 
	$(CC) -o amtc amtc.c $(CFLAGS) $(LFLAGS)

amt.h:
	for H in $(HEADERS); do xxd -i $$H $$H.h; done
	cat amtc_usage.h cmd_*.h > amt.h
	perl -pi -e 's/(0x\S\S)$$/$$1, 0x00/' amt.h
	perl -pi -e 's/(\d+);$$/$$1 + 1 .";"/e' amt.h

clean:
	rm -f cmd_*.h amtc_usage.h amt.h amtc *.o
