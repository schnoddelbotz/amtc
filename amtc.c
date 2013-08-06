/*
  amtc v0.2 - Intel vPro(tm)/AMT(tm) mass management tool.

  written by jan@hacker.ch, 2013 
  http://jan.hacker.ch/projects/amtc/
*/
#include <stdlib.h>
#include <getopt.h>
#include <string.h>
#include <pthread.h>
#include <semaphore.h>
#include <stdio.h>
#include <unistd.h>
#include <curl/curl.h>
#include "amt.h"

#define THREAD_ID        pthread_self(  )
#define CMD_INFO 0
#define CMD_POWERUP 1
#define CMD_POWERDOWN 2
#define CMD_POWERRESET 3
#define CMD_POWERCYCLE 4
#define CMD_SCAN 5
#define MAX_HOSTS 255

unsigned char *acmds[] = {
  /* SOAP/XML request bodies as included via amt.h */
  cmd_info,cmd_powerup,cmd_powerdown,cmd_powerreset,cmd_powercycle 
}; 
const char *hcmds[] = {
  "INFO","POWERUP","POWERDOWN","POWERRESET","POWERCYCLE"
}; 
const char *powerstate[] = {
 "S0 (ON)", "S1", "S2", "S3 (sleep)", "S4", "S5 (off)", "S4/S5", "Off"
};
struct host {
  int hostnumber;
  int started;
  int stopped;
  int result;
  char url[100];
  char hostname[100];
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
static size_t write_memory_callback(void*,size_t,size_t,void*);

sem_t mutex;
int   verbosity = 0;
int   scan_port = 16992;
int   cmd = 0;
int   numHosts = 0;
int   threadsRunning = 0;
int   connectTimeout = 5;
int   waitDelay = 0;
int   maxThreads = 40;
char  amtpasswd[32];
char  *amtpasswdp;

///////////////////////////////////////////////////////////////////////////////
int main(int argc,char **argv,char **envp) {
  int c;
  amtpasswdp = (char*)&amtpasswd;
    
  while ((c = getopt(argc, argv, "viudrRs:t:w:m:")) != -1)
  switch (c) {
    case 'v': verbosity += 1;         break; // verbosity
    case 'i': cmd = CMD_INFO;         break; 
    case 'u': cmd = CMD_POWERUP;      break; 
    case 'd': cmd = CMD_POWERDOWN;    break; 
    case 'r': cmd = CMD_POWERCYCLE;    break; 
    case 'R': cmd = CMD_POWERRESET;    break; 
    case 's': cmd = CMD_SCAN; scan_port = atoi(optarg); break; 
    case 't': connectTimeout = atoi(optarg); break; 
    case 'm': maxThreads = atoi(optarg); break; 
    case 'w': waitDelay = atoi(optarg); break; 
  }
  
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

  build_hostlist(argc,argv);
  //dump_hostlist();
  sem_init(&mutex, 0, 1);
  curl_global_init(CURL_GLOBAL_ALL);
  process_hostlist();
  sem_destroy(&mutex);
  curl_global_cleanup();
  return 0;
}

///////////////////////////////////////////////////////////////////////////////
//static void *pull_one_url(void* num) {
static void *pull_one_url(void* num) {
  struct host *host = &hostlist[(int)num];
  CURL *curl;
  CURLcode res;
  long http_code = 0;
  struct curl_slist *headers = NULL;
  struct MemoryStruct chunk;
  int amt_result = -1;
  chunk.memory = malloc(1);
  chunk.size = 0; 

  curl = curl_easy_init();
  // FIXME add TLS support...
  // http://curl.haxx.se/libcurl/c/threaded-ssl.html
  // --> produces lots of openssl deprecation warnings...
  // CURLPROTO_HTTP CURLPROTO_HTTPS
  // http://marc.info/?l=php-doc-cvs&m=124127960522738&q=raw

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
  // http://stackoverflow.com/questions/9191668/error-longjmp-causes-uninitialized-stack-frame ->
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

  /* print results */
  printf("%s %15s AMT:%04d HTTP:%3d %s\n",
    hcmds[cmd], (char*)host->hostname, amt_result, (int)http_code, umsgp);

  sem_wait(&mutex);
  threadsRunning--;
  if (verbosity>0) 
    printf("pull_one(%11d=%04ldb|http%03d): tr decreased to %3d by %s\n",
     (int)THREAD_ID,(long)chunk.size,(int)http_code,threadsRunning,(char*)host->url);
  sem_post(&mutex);

  curl_easy_cleanup(curl);
  free(headers);
  free(chunk.memory);
  return NULL;
}

///////////////////////////////////////////////////////////////////////////////
int get_amt_response_status(void* chunk) {
  int response = -9;
  char *pos = NULL;
  char gre[64]; // FIXME global
  char *grep = (char*)&gre;
  if (cmd==CMD_INFO) {
    snprintf((char*)&gre,sizeof gre,"<b:Status>0</b:Status><b:SystemPowerState>");
  } else {
    snprintf((char*)&gre,sizeof gre,"<b:RemoteControlResponse><b:Status>");
  }
  pos = strstr(chunk, grep);
  if (pos==NULL) {
    response = -99; // may be wrong amt version, too
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
static void *scan_one_hostport(void *host) {
  sem_wait(&mutex);
  threadsRunning--;
  printf("scan_one(%11d): tr %3d host %s port %d\n",
            (int)THREAD_ID, threadsRunning, (char*)host,scan_port);
  sem_post(&mutex);
  pthread_exit(0);
  return NULL;
}

///////////////////////////////////////////////////////////////////////////////
/* FIXME error-handling -- not only here :-/ */
void process_hostlist() {
  int a, b;
  pthread_t tid[MAX_HOSTS];

  //printf("Firing max %d threads for %d hosts...\n",maxThreads, numHosts);
  for(a = 0; a < numHosts; a++) {

    if (cmd == CMD_SCAN)
      pthread_create(&tid[a], NULL, scan_one_hostport,
        (void *)hostlist[a].hostname);
    else
      pthread_create(&tid[a], NULL, pull_one_url, a);

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
  for(a = 0; a < MAX_HOSTS; a++) {
    hostlist[a].started = 0;
    hostlist[a].stopped = 0;
    hostlist[a].result = 0;
  }
  int i,h=0;
  for (i=optind; i<argc; i++) {
    hostlist[h].started = 1;
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
  for(a = 0; a < numHosts; a++) {
    printf("dumphost %04d: %14s start=%08d stop=%08d res=%d url='%s'\n",
           a, hostlist[a].hostname, hostlist[a].started,hostlist[a].stopped,
           hostlist[a].result, hostlist[a].url);
  }
}

