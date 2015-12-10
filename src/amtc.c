/*
  amtc v0.8 - Intel AMT & WS-MAN OOB mass management tool

  written by jan@hacker.ch, 2013
  http://jan.hacker.ch/projects/amtc/
*/
#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>
#include <getopt.h>
#include <string.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>
#include <pthread.h>
#include <semaphore.h>
#include <curl/curl.h>
#include <errno.h>
#include "amt.h"

#ifdef WITH_GPL_AMTTERM
// includes for amtterm
#include <fcntl.h>
#include <termios.h>
#include <signal.h>
#include <sys/ioctl.h>
#include "amtterm/redir.h"
// defines for included amtterm
#define APPNAME "amtc-amtterm"
#define BUFSIZE 512
#endif

#define THREAD_ID      pthread_self(  )
#define CMD_INFO       0
#define CMD_POWERUP    1
#define CMD_POWERDOWN  2
#define CMD_POWERRESET 3
#define CMD_POWERCYCLE 4
#define CMD_SHUTDOWN   5
#define CMD_REBOOT     6
#define CMD_ENUMERATE  7
#define CMD_MODIFY     8
#define CMD_TERMINAL   9
#define CMD_PXEBOOT    10
#define CMD_HDDBOOT    11
#define MAX_HOSTS      255
#define PORT_SSH       22
#define PORT_RDP       3389
#define SCANRESULT_NONE_OPEN   0
#define SCANRESULT_NO_SCANS    1
#define SCANRESULT_LOOKUP_FAIL 9
#define SCANRESULT_SSH_OPEN    22
#define SCANRESULT_NONE_RUN    999
#define SCANRESULT_RDP_OPEN    3389

unsigned char *acmds[] = {
  /* SOAP/XML request bodies as included via amt.h, AMT6-8 */
  cmd_info, cmd_powerup, cmd_powerdown, cmd_powerreset, cmd_powercycle,
  /* WS-MAN / DASH / AMT6-9+ versions */
  wsman_info, wsman_up, wsman_down, wsman_reset, wsman_reset,
  /* generic wsman enumerations using -E <classname> */
  wsman_shutdown_graceful, wsman_reset_graceful, wsman_xenum,
  /* AMT config settings via wsman -- cfgcmd 0..5  */
  wsman_solredir_disable, wsman_solredir_enable,
  wsman_webui_disable, wsman_webui_enable,
  wsman_ping_disable, wsman_ping_enable,
  // HAXX ! ... for boot device selection
  wsman_pxeboot, wsman_hddboot, wsman_bootconfig
};
const char *hcmds[] = {
  "INFO","POWERUP","POWERDOWN","POWERRESET","POWERCYCLE",
  "SHUTDOWN","REBOOT","ENUMERATE","MODIFY", "AMTTERM", "PXEBOOT", "HDDBOOT"
};
const char *powerstate[] = { /* AMT/ACPI */
 "S0 (on)", "S1 (cpu stop)", "S2 (cpu off)", "S3 (sleep)",
 "S4 (hibernate)", "S5 (soft-off)", "S4/S5", "MechOff",
 "amtcnoclue", "u","v","w","x","y","z", "amtreply_no_match"
};
int wsman2acpi[] = { 8,8,0,8,3,8,8,4,5,8,8,8,8,8,8,8,9 };
char portnames[3390][8];
struct host {
  int id;
  int http_result;
  int amt_result;
  int duration;
  int osport;
  char url[100];
  char hostname[100];
  char usrmsg[100];
};
struct host hostlist[MAX_HOSTS];
struct MemoryStruct {
  char *memory;
  size_t size;
};
char xenumTxt[8192];
char wsman_class_uri[100];

void build_hostlist(int,char**);
void dump_hostlist();
void process_hostlist();
void get_amt_pw();
int  get_amt_response_status(void*);
int  probe_one_hostport(int,int,int);
int  get_enum_context(void*,char*);
int  get_enum_class(char*);
void list_wsman_cmds();
struct addrinfo* lookup_host (const char*);
static size_t write_memory_callback(void*,size_t,size_t,void*);

sem_t  mutex;
FILE  *pwfile;
int   verbosity = 0;
int   scan_ssh = 0;
int   scan_rdp = 0;
int   cmd = 0;
int   cfgcmd = 0;
int   do_enumerate = 0;
int   num_wsman_cmds = 261;
int   numHosts = 0;
int   quiet = 0;
int   noVerifyCert = 0;
int   amtPort = 16992;
int   useTLS = 0;
int   threadsRunning = 0;
int   connectTimeout = 10;
float waitDelay = -1;
int   maxThreads = 180;
int   produceJSON = 0;
int   useWsmanShift = 0;
char  amtpasswdfile[255];
char  amtpasswd[32];
char  gre[64];
char  *amtpasswdfilep = NULL;
char  *do_modify = NULL;
char  *cacertfilep = NULL;
char  *amtpasswdp = (char*)&amtpasswd;
char  *grep = (char*)&gre;
typedef enum { false, true } bool;
bool  amtv5 = false;
bool  enforceScans = false; // enforce SSH/RDP scan even if no AMT success

///////////////////////////////////////////////////////////////////////////////
int main(int argc,char **argv,char **envp) {
  int c;

  while ((c = getopt(argc, argv, "HXFIBUDRSCLTE:M:5gndeqvjsrp:t:w:m:c:")) != -1)
  switch (c) {
    case 'I': cmd = CMD_INFO;                break;
    case 'U': cmd = CMD_POWERUP;             break;
    case 'D': cmd = CMD_POWERDOWN;           break;
    case 'C': cmd = CMD_POWERCYCLE;          break;
    case 'R': cmd = CMD_POWERRESET;          break;
    case 'X': cmd = CMD_PXEBOOT; useWsmanShift = 9; break;
    case 'H': cmd = CMD_HDDBOOT; useWsmanShift = 9; break;
    case 'S': cmd = CMD_SHUTDOWN; useWsmanShift=5; break;
    case 'B': cmd = CMD_REBOOT; useWsmanShift=5; break;
    case 'T': cmd = CMD_TERMINAL;            break;
    case 'E': cmd = CMD_ENUMERATE; quiet=1; useWsmanShift=5; do_enumerate=get_enum_class(optarg); break;
    case 'M': cmd = CMD_MODIFY; useWsmanShift=5; do_modify=optarg; break;
    case 'L': list_wsman_cmds();             break;
    case 's': scan_ssh = 1;                  break;
    case 'r': scan_rdp = 1;                  break;
    case 'e': enforceScans = true;           break;
    case 'j': produceJSON = 1;               break;
    case 'q': quiet = 1;                     break;
    case 'g': useTLS = 1; amtPort = 16993;   break;
    case 'n': noVerifyCert = 1;              break;
    case 'm': maxThreads = atoi(optarg);     break;
    case 'p': amtpasswdfilep = optarg;       break;
    case 'c': cacertfilep = optarg;          break;
    case 't': connectTimeout = atoi(optarg); break;
    case 'v': verbosity += 1;                break;
    case 'd': useWsmanShift = 5;             break;
    case 'w': waitDelay = atof(optarg);      break;
    case '5': amtv5 = true;                  break;
  }

  strcpy(portnames[SCANRESULT_NONE_OPEN],"none"); /* no open ports found */
  strcpy(portnames[SCANRESULT_NONE_RUN], "skipped"); /* skipped, eg. pwrd off */
  strcpy(portnames[SCANRESULT_NO_SCANS], "noscan"); /* neither -S nor -W */
  strcpy(portnames[SCANRESULT_SSH_OPEN], "ssh");
  strcpy(portnames[SCANRESULT_RDP_OPEN], "rdp");

  build_hostlist(argc,argv);

  if (waitDelay==-1 && cmd == CMD_POWERUP && numHosts>1) {
    printf("#Info: No -w(wait) delay specified for powerup.\n" \
           "#Info: Using default delay of 5 seconds to prevent spikes.\n");
    waitDelay=5;
  } else if (waitDelay==-1)
    waitDelay=0;

  if (argc>MAX_HOSTS) {
    printf("No more than %d hosts allowed at once.\n", MAX_HOSTS);
    exit(1);
  }
  if (argc<2) {
    printf("%s", amtc_usage);
    exit(2);
  }
  if (cmd==CMD_INFO) {
    snprintf(grep,sizeof gre, useWsmanShift ?
      "<h:PowerState>" :
      "<b:Status>0</b:Status><b:SystemPowerState>");
    if (amtv5)
     snprintf(grep,sizeof gre, "<s0:Status>0</s0:Status><s0:SystemPowerState>");
  } else {
    snprintf(grep,sizeof gre, useWsmanShift ?
      "<g:RequestPowerStateChange_OUTPUT><g:ReturnValue>" :
      "<b:RemoteControlResponse><b:Status>");
  }
  if (cmd==CMD_ENUMERATE) {
      // construct uri and request body for enum request
      int classnum=-1;
      const char *wsmuris[] = {
        "http://intel.com/wbem/wscim/1/amt-schema/1/%s",
        "http://schemas.dmtf.org/wbem/wscim/1/cim-schema/2/%s",
        "http://intel.com/wbem/wscim/1/ips-schema/1/%s"
      };
      if (strncmp(wsman_classes[do_enumerate], "AMT_", 4) == 0)
        classnum=0;
      else if (strncmp(wsman_classes[do_enumerate], "CIM_", 4) == 0)
        classnum=1;
      else if (strncmp(wsman_classes[do_enumerate], "IPS_", 4) == 0)
        classnum=2;

      sprintf(wsman_class_uri, wsmuris[classnum], wsman_classes[do_enumerate]);
      sprintf(xenumTxt, (char*)wsman_xenum, wsman_class_uri);
  }
  if (cmd==CMD_MODIFY) {
    // yuck, make nicer...
    if (strcmp(do_modify,"sol=off")==0) {
      cfgcmd=0;
    } else if (strcmp(do_modify,"sol=on")==0) {
      cfgcmd=1;
    } else if (strcmp(do_modify,"webui=off")==0) {
      cfgcmd=2;
    } else if (strcmp(do_modify,"webui=on")==0) {
      cfgcmd=3;
    } else if (strcmp(do_modify,"ping=off")==0) {
      cfgcmd=4;
    } else if (strcmp(do_modify,"ping=on")==0) {
      cfgcmd=5;
    } else {
      printf("Bad config command\n");
      exit(1);
    }
  }

  get_amt_pw();

  if (cmd==CMD_TERMINAL) {
#ifdef WITH_GPL_AMTTERM
    return amtterm_session();
#else
    printf("This version of amtc was compiled without amtterm (GPL) support.\n");
    exit(ENOTSUP);
#endif
  }

  sem_init(&mutex, 0, 1);
  curl_global_init(CURL_GLOBAL_ALL);

  process_hostlist();

  sem_destroy(&mutex);
  curl_global_cleanup();
  dump_hostlist();
  return 0;
}

///////////////////////////////////////////////////////////////////////////////
// returns array index in wsman_classes of given class
int get_enum_class(char* aname) {
  int x;
  for (x=0; x<num_wsman_cmds; x++)
    if (strcmp(aname,wsman_classes[x])==0)
      return x;
  printf("Invalid wsman command. Use -L to list them.\n");
  exit(1);
}

///////////////////////////////////////////////////////////////////////////////
void list_wsman_cmds() {
  int x;
  if (verbosity)
    printf("WS-MAN classes as listed in Intel SDK / docs:\n");
  for (x=0; x<num_wsman_cmds; x++) {
      printf("%s\n",wsman_classes[x]);
  }
  exit(0);
}

///////////////////////////////////////////////////////////////////////////////
static void *process_single_client(void* num) {
  int hostid = (int)(intptr_t)num;
  struct host *host = &hostlist[hostid];
  CURL *curl;
  CURLcode res;
  long http_code = 0;
  struct curl_slist *headers = NULL;
  struct MemoryStruct chunk;
  int amt_result = -1;
  chunk.memory = malloc(1);
  chunk.size = 0;
  int os_port = SCANRESULT_NO_SCANS;

  curl = curl_easy_init();

  if (useWsmanShift!=0) {

  } else if (cmd==CMD_INFO)
    headers = curl_slist_append(headers, "SOAPAction: \"http://schemas.intel" \
           ".com/platform/client/RemoteControl/2004/01#GetSystemPowerState\"");
  else
    headers = curl_slist_append(headers, "SOAPAction: \"http://schemas.intel" \
                 ".com/platform/client/RemoteControl/2004/01#RemoteControl\"");

  headers = curl_slist_append(headers, "Content-Type: text/xml; charset=utf-8");
                          // Content-Type: application/soap+xml;charset=UTF-8

  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, write_memory_callback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, (void *)&chunk);
  curl_easy_setopt(curl, CURLOPT_USERAGENT, "amtc (libcurl)");
  curl_easy_setopt(curl, CURLOPT_URL, host->url);
  curl_easy_setopt(curl, CURLOPT_VERBOSE, (verbosity>2?1:0));
  curl_easy_setopt(curl, CURLOPT_TIMEOUT, connectTimeout);
  curl_easy_setopt(curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
  curl_easy_setopt(curl, CURLOPT_USERNAME, "admin"  );
  curl_easy_setopt(curl, CURLOPT_PASSWORD, amtpasswdp);
  curl_easy_setopt(curl, CURLOPT_POST , 1);
  curl_easy_setopt(curl, CURLOPT_HTTPHEADER , headers);

  if (cmd==CMD_ENUMERATE)
    curl_easy_setopt(curl, CURLOPT_POSTFIELDS , xenumTxt);
  else
    curl_easy_setopt(curl, CURLOPT_POSTFIELDS , acmds[cmd+useWsmanShift+cfgcmd]);

#if LIBCURL_VERSION_MAJOR > 7 || (LIBCURL_VERSION_MAJOR==7 && LIBCURL_VERSION_MINOR >= 29)
  if (useTLS) {
    // http://curl.haxx.se/libcurl/c/CURLOPT_SSLVERSION.html
    // required for RHEL7+ -- will recieve "NSS error -12272 (SSL_ERROR_BAD_MAC_ALERT)" without it.
    // The page above states CURL_SSLVERSION_TLSv1_0 was introduced with curl 7.34.0.
    // But it works on RHEL with 7.29. Please report if this is an issue for you.
    curl_easy_setopt(curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_0);
  }
#endif

  if (noVerifyCert) {
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0L);
  } else if (useTLS) {
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 1);
    curl_easy_setopt(curl, CURLOPT_CAINFO, cacertfilep ?
                           cacertfilep : "/etc/amt-ca.crt");
  }
  // http://stackoverflow.com/questions/9191668/error-longjmp-causes-uninitialized-stack-frame
  curl_easy_setopt(curl, CURLOPT_NOSIGNAL, 1);

  res = curl_easy_perform(curl);
  curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);

  // (just another) hack for ws-man
  // a enumeration context needs to be requested and pulled. see section 8 of
  // http://software.intel.com/sites/manageability/AMT_Implementation_and_Reference_Guide/default.htm?turl=WordDocuments%2Fdsp0226webservicesformanagementwsmanagementspecification.htm
  char enumCtx[8192];
  char enumTxt[8192];
  if (cmd==CMD_INFO && http_code==200 && useWsmanShift!=0) {
    if (get_enum_context(chunk.memory,(char*)&enumCtx)) {
      sprintf(enumTxt, (char*)wsman_info_step2, enumCtx);
      curl_easy_setopt(curl, CURLOPT_POSTFIELDS , enumTxt);
      res = curl_easy_perform(curl);
      curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);
    } else { printf("YIKES. fixme wsman\n"); }
  } else if (cmd==CMD_ENUMERATE && http_code==200 && useWsmanShift!=0) {
    if (get_enum_context(chunk.memory,(char*)&enumCtx)) {
      sprintf(enumTxt, (char*)wsman_xenum_step2, wsman_class_uri, enumCtx);
      curl_easy_setopt(curl, CURLOPT_POSTFIELDS , enumTxt);
      res = curl_easy_perform(curl);
      curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);
    } else { printf("YUKES. fixme wsman-enum\n"); }
  }
  // 'save' boot device selection
  if ((cmd==CMD_PXEBOOT || cmd==CMD_HDDBOOT) && http_code==200) {
    curl_easy_setopt(curl, CURLOPT_POSTFIELDS , wsman_bootconfig);
    res = curl_easy_perform(curl);
  }

  char umsg[100];
  const char* umsgp = (char*)&umsg;

  if(res != CURLE_OK || http_code!=200) {
    amt_result = 16; /* as there is no such acpi/wsman state...  */
    umsgp = curl_easy_strerror(res);
  }
  else {
    amt_result = get_amt_response_status(chunk.memory);

    if(useWsmanShift)
      amt_result = wsman2acpi[amt_result];
    else
      amt_result = amt_result & 0x0f;

    snprintf((char*)&umsg, sizeof umsg, "OK %s%s",
      (cmd==CMD_INFO) ? powerstate[amt_result] : "",
      (cmd!=CMD_INFO && amt_result==0) ? "success" : ""
    );

    if (verbosity>1)
       printf("body (size:%4ld b) received: '%s'\n",
                     (long)chunk.size,chunk.memory);

    // threads will not store these results... (yet?). so print 'parseable' msg.
    if (cmd==CMD_ENUMERATE)
       printf("%s %s '%s'\n", (char*)host->hostname,
                                 wsman_classes[do_enumerate], chunk.memory);
  }

  // this would need to be duplicated in the function head to eg. reset only SSH boxes (CLI)
  if ( ((scan_ssh||scan_rdp) && (cmd==CMD_INFO && amt_result==0)) || enforceScans ) {
    os_port=SCANRESULT_NONE_OPEN;
    if (scan_rdp)
      os_port = probe_one_hostport(hostid,PORT_RDP,os_port);
    if (scan_ssh)
      os_port = probe_one_hostport(hostid,PORT_SSH,os_port);
  } else if ((scan_ssh||scan_rdp) && (cmd==CMD_INFO && amt_result!=0)) {
    os_port=SCANRESULT_NONE_RUN;
  }

  /* print results while processing, if verbose */
  if (verbosity>0)
    printf("-%s %14s AMT:%04d HTTP:%3d %s\n",
      hcmds[cmd], (char*)host->hostname, amt_result, (int)http_code, umsgp);

  sem_wait(&mutex);
  threadsRunning--;
  hostlist[hostid].http_result=(int)http_code;
  hostlist[hostid].amt_result=(int)amt_result;
  hostlist[hostid].osport = os_port;
  snprintf(hostlist[hostid].usrmsg, 100, "%s", umsgp);
  if (verbosity>1)
    printf("singleClient(%11d=%04ldb|http%03d): tr decreased to %3d by %s\n",
      (int)THREAD_ID,(long)chunk.size,(int)http_code,
      threadsRunning,(char*)host->url);
  sem_post(&mutex);

  curl_easy_cleanup(curl);
  curl_slist_free_all(headers);
  free(chunk.memory);
  return NULL;
}

///////////////////////////////////////////////////////////////////////////////
int get_amt_response_status(void* chunk) {
  int response = -9;
  char *pos = NULL;
    pos = strstr(chunk, grep);
    if (pos==NULL) {
      response = 15; // no match -- may be wrong amt version
    } else {
      pos = pos + strlen(grep);
      response = atoi(pos);
    }
  return response;
}

///////////////////////////////////////////////////////////////////////////////
int get_enum_context(void* chunk,char* result) {
  char *pos = NULL;
  pos = strstr(chunk, "<g:EnumerateResponse><g:EnumerationContext>"/*len=44*/);
  if (pos==NULL)
    return 0;
  else
    strncpy(result, pos+43, 36 /*ctx str len,fixme: hopefully*/ );
  return 1;
}

///////////////////////////////////////////////////////////////////////////////
// simply http://curl.haxx.se/libcurl/c/getinmemory.html
static size_t write_memory_callback(void *contents, size_t size, size_t nmemb, void *userp) {
  size_t realsize = size * nmemb;
  struct MemoryStruct *mem = (struct MemoryStruct *)userp;

  mem->memory = realloc(mem->memory, mem->size + realsize + 1);
  if(mem->memory == NULL) {
    /* out of memory! */
    printf("not enough memory (realloc returned NULL)\n");
    return 0;
  }

  memcpy(&(mem->memory[mem->size]), contents, realsize);
  mem->size += realsize;
  mem->memory[mem->size] = 0;

  return realsize;
}

///////////////////////////////////////////////////////////////////////////////
int probe_one_hostport(int hostid, int port, int alreadyFound) {
  int sockfd,c;
  struct sockaddr_in servaddr;
  struct timeval timeout;
  struct addrinfo *query;
  timeout.tv_sec = connectTimeout;
  timeout.tv_usec = 0;

  if (alreadyFound)
    return alreadyFound;

  sockfd=socket(AF_INET,SOCK_STREAM,0);
  if (setsockopt (sockfd, SOL_SOCKET, SO_RCVTIMEO, (char *)&timeout, sizeof(timeout)) < 0)
    printf("setsockopt failed\n");
  if (setsockopt (sockfd, SOL_SOCKET, SO_SNDTIMEO, (char *)&timeout, sizeof(timeout)) < 0)
    printf("setsockopt failed\n");
  // has no effect on OSX -- use TCP_CONNECTIONTIMEOUT; man tcp(4) ?

  if ((query = lookup_host(hostlist[hostid].hostname)) == NULL) {
    if (verbosity>1)
      printf("Error resolving host %s for portscan (%d)!\n",
              hostlist[hostid].hostname, port);
    return SCANRESULT_LOOKUP_FAIL;
  }
  struct in_addr *ptr;
  ptr = &((struct sockaddr_in *) query->ai_addr)->sin_addr;

  if (verbosity>1)
    printf ("SCAN IP ADDR: IPv%d address: %s => %s\n",
        query->ai_family == PF_INET6 ? 6 : 4,
        query->ai_canonname, inet_ntoa((struct in_addr)*ptr));

  bzero(&servaddr,sizeof(servaddr));
  servaddr.sin_family = query->ai_family;
  //servaddr.sin_addr.s_addr=inet_addr(hostlist[hostid].hostname);
  servaddr.sin_addr.s_addr=ptr->s_addr;
  servaddr.sin_port=htons(port);

  c=connect(sockfd, (struct sockaddr *)&servaddr, sizeof(servaddr));
  close(sockfd);
  if (verbosity>1)
    printf("SCAN %d on %15s - %d\n", port, hostlist[hostid].hostname, c);

  // read ssh greeting?
  return (c==0) ? port : SCANRESULT_NONE_OPEN;
}

///////////////////////////////////////////////////////////////////////////////
void process_hostlist() {
  int a, b;
  pthread_t tid[MAX_HOSTS];

  //printf("Firing max %d threads for %d hosts...\n",maxThreads, numHosts);
  for(a = 0; a < numHosts; a++) {
    pthread_create(&tid[a], NULL,
              process_single_client, (void*)(intptr_t)a);

    sem_wait(&mutex);
    threadsRunning++;
    sem_post(&mutex);

    while ((threadsRunning+1)>maxThreads) {
        if (verbosity>3)
          printf(" ... threads waiting for free slot (%d/%d running) ...\n",
                   threadsRunning, maxThreads);

        usleep(10000);
    }

    if (waitDelay)
      usleep(waitDelay * 1000000);
  }

  for(b = 0; b < numHosts; b++) {
      pthread_join(tid[b], NULL);
  }
}

///////////////////////////////////////////////////////////////////////////////
void build_hostlist(int argc,char **argv) {
  int a;
  for(a = 0; a < MAX_HOSTS; a++) {
    hostlist[a].id = a;
    hostlist[a].http_result = -1;
    hostlist[a].amt_result = -1;
    hostlist[a].duration = -1;
    hostlist[a].osport = -99;
  }
  int i,h=0;
  for (i=optind; i<argc; i++) {
    hostlist[h].http_result = -2;
    sprintf(hostlist[h].hostname,"%s",argv[i]);
    sprintf(hostlist[h].url,"http%s://%s:%d/%s",
              useTLS ? "s" : "", argv[i], amtPort,
              useWsmanShift ? "wsman" : "RemoteControlService");
    h++;
  }
  numHosts = h;
}

///////////////////////////////////////////////////////////////////////////////
void dump_hostlist() {
  int a;
  if (produceJSON) {
    printf("{");
    for(a = 0; a < numHosts; a++)
      printf("%s\"%s\":{\"amt\":\"%d\",\"http\":\"%d\",\"oport\":\"%s\",\"msg\":\"%s\"}",
         a==0?"":",", hostlist[a].hostname, hostlist[a].amt_result,
         hostlist[a].http_result, portnames[hostlist[a].osport], hostlist[a].usrmsg);
    printf("}\n");
  } else {
    for(a = 0; a < numHosts; a++) {
      if (!quiet || (quiet && hostlist[a].http_result!=200))
      printf("%s %-15s OS:%-7s AMT:%02d HTTP:%03d %s\n", hcmds[cmd],
        hostlist[a].hostname, portnames[hostlist[a].osport] ,
        hostlist[a].amt_result, hostlist[a].http_result, hostlist[a].usrmsg);
    }
  }
}

///////////////////////////////////////////////////////////////////////////////
void get_amt_pw() {
  int l;
  if (amtpasswdfilep!=NULL) {
    pwfile = fopen(amtpasswdfilep, "r");
    if(pwfile == NULL) {
      printf("Error opening password file\n");
      exit(4);
    }
    if( fgets (amtpasswd, sizeof amtpasswd, pwfile)!=NULL ) {
      l=strlen(amtpasswd);
      amtpasswd[l-1]=0x00;
    }
    fclose(pwfile);
    return;
  }
  if (!(amtpasswdp=getenv("AMT_PASSWORD"))) {
    printf("Set your AMT_PASSWORD environment variable [or use -p].\n");
    exit(3);
  }
}

///////////////////////////////////////////////////////////////////////////////
struct addrinfo* lookup_host (const char *host) {
  // more or less just http://www.logix.cz/michal/devel/various/getaddrinfo.c.xp
  // resolve hostnames (for OS probing)
  struct addrinfo hints, *res;
  int errcode;
  void *ptr;

  memset (&hints, 0, sizeof (hints));
  hints.ai_family = PF_UNSPEC;
  hints.ai_socktype = SOCK_STREAM;
  hints.ai_flags |= AI_CANONNAME;

  errcode = getaddrinfo (host, NULL, &hints, &res);
  if (errcode != 0) {
    return NULL;
  }

  while (res) {
    switch (res->ai_family) {
      case AF_INET:
        ptr = &((struct sockaddr_in *) res->ai_addr)->sin_addr;
        break;
      case AF_INET6:
        ptr = &((struct sockaddr_in6 *) res->ai_addr)->sin6_addr;
        break;
    }
    // totally untested with v6 + needs cleanup
    return res;
    res = res->ai_next;
  }

  return NULL;
}

/* ------------------------------------------------------------------ */

// adapted amtterm.c main() ...
#ifdef WITH_GPL_AMTTERM
#include "amtc-amtterm.c"
int amtterm_session()
{
  struct host *host = &hostlist[0];
  struct redir r;

  memset(&r, 0, sizeof(r));
  r.verbose = 1;
  memcpy(r.type, "SOL ", 4);
  strcpy(r.user, "admin");

  r.cb_data  = &r;
  r.cb_recv  = recv_tty;
  r.cb_state = state_tty;

  snprintf(r.pass, sizeof(r.pass), "%s", amtpasswdp);

  r.verbose = verbosity == 0 ? 0 : 1;
  snprintf(r.host, sizeof(r.host), "%s", (char*)host->hostname);

  tty_save();

  if (-1 == redir_connect(&r)) {
    tty_restore();
    exit(1);
  }

  tty_raw();
  redir_start(&r);
  redir_loop(&r);
  tty_restore();

  exit(0);
}
#endif
