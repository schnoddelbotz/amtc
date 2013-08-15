/*
  amtc v0.3 - Intel vPro(tm)/AMT(tm) mass management tool.

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
#include "amt.h"

#define THREAD_ID        pthread_self(  )
#define CMD_INFO 0
#define CMD_POWERUP 1
#define CMD_POWERDOWN 2
#define CMD_POWERRESET 3
#define CMD_POWERCYCLE 4
#define MAX_HOSTS 255
#define PORT_SSH 22
#define PORT_RDP 3389
#define SCANRESULT_NONE_OPEN 0
#define SCANRESULT_NO_SCANS  1
#define SCANRESULT_SSH_OPEN  22
#define SCANRESULT_NONE_RUN  999
#define SCANRESULT_RDP_OPEN  3389


unsigned char *acmds[] = {
  /* SOAP/XML request bodies as included via amt.h */
  cmd_info,cmd_powerup,cmd_powerdown,cmd_powerreset,cmd_powercycle 
}; 
const char *hcmds[] = {
  "INFO","POWERUP","POWERDOWN","POWERRESET","POWERCYCLE"
}; 
const char *powerstate[] = {
 "S0 (on)", "S1 (cpu stop)", "S2 (cpu off)", "S3 (sleep)",
 "S4 (hibernate)", "S5 (sotf-off)", "S4/S5", "MechOff"
};
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

void build_hostlist(int,char**);
void dump_hostlist();
void process_hostlist();
int  get_amt_response_status(void*);
int  probe_one_hostport(int,int,int);
static size_t write_memory_callback(void*,size_t,size_t,void*);

sem_t mutex;
int   verbosity = 0;
int   scan_ssh = 0;
int   scan_rdp = 0;
int   cmd = 0;
int   numHosts = 0;
int   threadsRunning = 0;
int   connectTimeout = 5;
int   waitDelay = -1;
int   maxThreads = 40;
int   produceJSON = 0;
char  amtpasswd[32];
char  *amtpasswdp;
char  gre[64];
char  *grep = (char*)&gre;

///////////////////////////////////////////////////////////////////////////////
int main(int argc,char **argv,char **envp) {
  int c;
  amtpasswdp = (char*)&amtpasswd;
    
  while ((c = getopt(argc, argv, "viudrRSWjs:t:w:m:")) != -1)
  switch (c) {
    case 'v': verbosity += 1;         break;
    case 'j': produceJSON = 1;        break;
    case 'i': cmd = CMD_INFO;         break; 
    case 'u': cmd = CMD_POWERUP;      break; 
    case 'd': cmd = CMD_POWERDOWN;    break; 
    case 'r': cmd = CMD_POWERCYCLE;   break; 
    case 'R': cmd = CMD_POWERRESET;   break; 
    case 'S': scan_ssh = 1;           break; 
    case 'W': scan_rdp = 1;           break; 
    case 't': connectTimeout = atoi(optarg); break; 
    case 'm': maxThreads = atoi(optarg); break; 
    case 'w': waitDelay = atoi(optarg); break; 
  }

  if (waitDelay==-1 && cmd == CMD_POWERUP) {
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
  if (!(amtpasswdp=getenv("AMT_PASSWORD"))) {
    printf("Set your AMT_PASSWORD environment variable [or use -p].\n");
    exit(3);
  }

  strcpy(portnames[SCANRESULT_NONE_OPEN],"none"); /* no open ports found */
  strcpy(portnames[SCANRESULT_NONE_RUN], "skipped"); /* skipped, eg. pwrd off */
  strcpy(portnames[SCANRESULT_NO_SCANS], "noscan"); /* neither -S nor -W */
  strcpy(portnames[SCANRESULT_SSH_OPEN], "ssh"); 
  strcpy(portnames[SCANRESULT_RDP_OPEN], "rdp");

  if (cmd==CMD_INFO) {
    snprintf(grep,sizeof gre,"<b:Status>0</b:Status><b:SystemPowerState>");
  } else {
    snprintf(grep,sizeof gre,"<b:RemoteControlResponse><b:Status>");
  }

  build_hostlist(argc,argv);
  sem_init(&mutex, 0, 1);
  curl_global_init(CURL_GLOBAL_ALL);
  process_hostlist();
  sem_destroy(&mutex);
  curl_global_cleanup();
  dump_hostlist();
  return 0;
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
  // FIXME add TLS support...
  // http://curl.haxx.se/libcurl/c/threaded-ssl.html
  // --> produces lots of openssl deprecation warnings...

  if (cmd==CMD_INFO)
    headers = curl_slist_append(headers, "SOAPAction: \"http://schemas.intel" \
           ".com/platform/client/RemoteControl/2004/01#GetSystemPowerState\"");
  else
    headers = curl_slist_append(headers, "SOAPAction: \"http://schemas.intel" \
                 ".com/platform/client/RemoteControl/2004/01#RemoteControl\"");

  headers = curl_slist_append(headers, "Content-Type: text/xml; charset=utf-8");

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
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS , acmds[cmd]);
  curl_easy_setopt(curl, CURLOPT_HTTPHEADER , headers);
  // http://stackoverflow.com/questions/9191668/error-longjmp-causes-uninitialized-stack-frame
  curl_easy_setopt(curl, CURLOPT_NOSIGNAL, 1); 
  
  res = curl_easy_perform(curl);
  curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);

  char umsg[100];
  const char* umsgp = (char*)&umsg;

  if(res != CURLE_OK || http_code!=200) {
    amt_result = -999;
    umsgp = curl_easy_strerror(res);
  }
  else {
    amt_result = get_amt_response_status(chunk.memory);
    snprintf((char*)&umsg, sizeof umsg, "OK %s%s", 
      (cmd==CMD_INFO) ? powerstate[amt_result & 0x0f] : "",
      (cmd!=CMD_INFO && amt_result==0) ? "success" : "" 
    );

    if (verbosity>1)
       printf("body (size:%4ld b) received: '%s'\n",
                     (long)chunk.size,chunk.memory);
  }

 // fixme: this needs to be duplicated in the function head to eg. reset only SSH boxes (CLI)
  if ( (scan_ssh||scan_rdp) && (cmd==CMD_INFO && (amt_result & 0x0f)==0) ) {
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
    response = -99; // no match -- may be wrong amt version, too
  } else {
    pos = pos + strlen(grep);
    response = atoi(pos);
  }
  return response;
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
  timeout.tv_sec = connectTimeout;
  timeout.tv_usec = 0;

  if (alreadyFound)
    return alreadyFound;

  sockfd=socket(AF_INET,SOCK_STREAM,0);
  if (setsockopt (sockfd, SOL_SOCKET, SO_RCVTIMEO, (char *)&timeout, sizeof(timeout)) < 0)
    printf("setsockopt failed\n");
  if (setsockopt (sockfd, SOL_SOCKET, SO_SNDTIMEO, (char *)&timeout, sizeof(timeout)) < 0)
    printf("setsockopt failed\n");

  bzero(&servaddr,sizeof(servaddr));
  servaddr.sin_family = AF_INET;
  servaddr.sin_addr.s_addr=inet_addr(hostlist[hostid].hostname);
  servaddr.sin_port=htons(port);

  c=connect(sockfd, (struct sockaddr *)&servaddr, sizeof(servaddr));
  if (verbosity>1)
    printf("SCAN %d on %15s - %d\n", port, hostlist[hostid].hostname, c);

  return (c==0) ? port : SCANRESULT_NONE_OPEN;
}

///////////////////////////////////////////////////////////////////////////////
/* FIXME error-handling -- not only here :-/ */
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
      sleep(waitDelay);
  }

  for(b = 0; b < numHosts; b++) {
      pthread_join(tid[b], NULL);
  }
}

///////////////////////////////////////////////////////////////////////////////
void build_hostlist(int argc,char **argv) {
  int a;
  // http://www.logix.cz/michal/devel/various/getaddrinfo.c.xp
  // resolve IP/hostnames FIXME
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
    sprintf(hostlist[h].url,"http://%s:16992/RemoteControlService",argv[i]);
    sprintf(hostlist[h].hostname,"%s",argv[i]);
    // FIXME should create ip here for scan
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
      printf("%s %-15s OS:%-7s AMT:%04d HTTP:%03d %s\n",
        hcmds[cmd], hostlist[a].hostname, portnames[hostlist[a].osport],
         hostlist[a].amt_result, hostlist[a].http_result, hostlist[a].usrmsg);
    }
  }
}

