/*
 *  TCP helper functions.
 *
 *  Copyright (C) 2007 Gerd Hoffmann <kraxel@redhat.com
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <stdio.h>
#include <stdlib.h>
#include <errno.h>
#include <string.h>
#include <unistd.h>
#include <fcntl.h>

#include "tcp.h"

int tcp_verbose;

/* ------------------------------------------------------------------ */

static char *strfamily(int family)
{
    switch (family) {
    case PF_INET6: return "ipv6";
    case PF_INET:  return "ipv4";
    case PF_UNIX:  return "unix";
    }
    return "????";
}

int tcp_connect(struct addrinfo *ai,
		char *addr, char *port,
		char *host, char *serv)
{
    struct addrinfo *res,*e;
    struct addrinfo *lres, ask;
    char uaddr[INET6_ADDRSTRLEN+1];
    char uport[33];
    char uhost[INET6_ADDRSTRLEN+1];
    char userv[33];
    int sock,rc,opt=1;

    /* lookup peer */
    ai->ai_flags = AI_CANONNAME;
    if (0 != (rc = getaddrinfo(host, serv, ai, &res))) {
	if (tcp_verbose)
	    fprintf(stderr,"getaddrinfo (peer): %s\n", gai_strerror(rc));
	return -1;
    }
    for (e = res; e != NULL; e = e->ai_next) {
	if (0 != getnameinfo((struct sockaddr*)e->ai_addr,e->ai_addrlen,
			     uhost,INET6_ADDRSTRLEN,userv,32,
			     NI_NUMERICHOST | NI_NUMERICSERV)) {
	    if (tcp_verbose)
		fprintf(stderr,"getnameinfo (peer): oops\n");
	    continue;
	}
	if (-1 == (sock = socket(e->ai_family, e->ai_socktype,
				 e->ai_protocol))) {
	    if (tcp_verbose)
		fprintf(stderr,"socket (%s): %s\n",
			strfamily(e->ai_family),strerror(errno));
	    continue;
	}
        setsockopt(sock,SOL_SOCKET,SO_REUSEADDR,&opt,sizeof(opt));
	if (NULL != addr || NULL != port) {
	    /* bind local port */
	    memset(&ask,0,sizeof(ask));
	    ask.ai_flags    = AI_PASSIVE;
	    ask.ai_family   = e->ai_family;
	    ask.ai_socktype = e->ai_socktype;
	    if (0 != (rc = getaddrinfo(addr, port, &ask, &lres))) {
		if (tcp_verbose)
		    fprintf(stderr,"getaddrinfo (local): %s\n",
			    gai_strerror(rc));
		continue;
	    }
	    if (0 != getnameinfo((struct sockaddr*)lres->ai_addr,
				 lres->ai_addrlen,
				 uaddr,INET6_ADDRSTRLEN,uport,32,
				 NI_NUMERICHOST | NI_NUMERICSERV)) {
		if (tcp_verbose)
		    fprintf(stderr,"getnameinfo (local): oops\n");
		continue;
	    }
	    if (-1 == bind(sock, lres->ai_addr, lres->ai_addrlen)) {
		if (tcp_verbose)
		    fprintf(stderr,"%s [%s] %s bind: %s\n",
			    strfamily(lres->ai_family),uaddr,uport,
			    strerror(errno));
		continue;
	    }
	}
	/* connect to peer */
	if (-1 == connect(sock,e->ai_addr,e->ai_addrlen)) {
	    if (tcp_verbose)
		fprintf(stderr,"%s %s [%s] %s connect: %s\n",
			strfamily(e->ai_family),e->ai_canonname,uhost,userv,
			strerror(errno));
	    close(sock);
	    continue;
	}
	if (tcp_verbose)
	    fprintf(stderr,"%s %s [%s] %s open\n",
		    strfamily(e->ai_family),e->ai_canonname,uhost,userv);
	fcntl(sock,F_SETFL,O_NONBLOCK);
	return sock;
    }
    return -1;
}

int tcp_listen(struct addrinfo *ai, char *addr, char *port)
{
    struct addrinfo *res,*e;
    char uaddr[INET6_ADDRSTRLEN+1];
    char uport[33];
    int slisten,rc,opt=1;

    /* lookup */
    ai->ai_flags = AI_PASSIVE;
    if (0 != (rc = getaddrinfo(addr, port, ai, &res))) {
	if (tcp_verbose)
	    fprintf(stderr,"getaddrinfo: %s\n",gai_strerror(rc));
	exit(1);
    }

    /* create socket + bind */
    for (e = res; e != NULL; e = e->ai_next) {
	getnameinfo((struct sockaddr*)e->ai_addr,e->ai_addrlen,
		    uaddr,INET6_ADDRSTRLEN,uport,32,
		    NI_NUMERICHOST | NI_NUMERICSERV);
	if (-1 == (slisten = socket(e->ai_family, e->ai_socktype,
				    e->ai_protocol))) {
	    if (tcp_verbose)
		fprintf(stderr,"socket (%s): %s\n",
			strfamily(e->ai_family),strerror(errno));
	    continue;
	}
	opt = 1;
        setsockopt(slisten,SOL_SOCKET,SO_REUSEADDR,&opt,sizeof(opt));
	if (-1 == bind(slisten, e->ai_addr, e->ai_addrlen)) {
	    if (tcp_verbose)
		fprintf(stderr,"%s [%s] %s bind: %s\n",
			strfamily(e->ai_family),uaddr,uport,
			strerror(errno));
	    continue;
	}
	listen(slisten,1);
	break;
    }
    if (NULL == e)
	return -1;

    /* wait for a incoming connection */
    if (tcp_verbose)
	fprintf(stderr,"listen on %s [%s] %s ...\n",
		strfamily(e->ai_family),uaddr,uport);
    fcntl(slisten,F_SETFL,O_NONBLOCK);
    return slisten;
}
