#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>

extern int tcp_verbose;

int tcp_connect(struct addrinfo *ai,
		char *addr, char *port,
		char *host, char *serv);

int tcp_listen(struct addrinfo *ai, char *addr, char *port);
