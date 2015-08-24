#ifndef __REDIRECTION_CONSTANTS__
#define __REDIRECTION_CONSTANTS__

#define STATUS_SUCCESS                  0x00
#define SOL_FIRMWARE_REV_MAJOR          0x01
#define SOL_FIRMWARE_REV_MINOR          0x00

//Session Manager Messages Formats
#define START_REDIRECTION_SESSION       0x10
#define START_REDIRECTION_SESSION_REPLY 0x11
#define END_REDIRECTION_SESSION         0x12
#define AUTHENTICATE_SESSION            0x13
#define AUTHENTICATE_SESSION_REPLY      0x14

#define START_REDIRECTION_SESSION_LENGTH        8
#define START_REDIRECTION_SESSION_REPLY_LENGTH  13
#define END_REDIRECTION_SESSION_LENGTH          4

//SOL Messages Formats
#define START_SOL_REDIRECTION               0x20
#define START_SOL_REDIRECTION_REPLY         0x21
#define END_SOL_REDIRECTION                 0x22
#define END_SOL_REDIRECTION_REPLY           0x23
#define SOL_KEEP_ALIVE_PING                 0x24  //Console to Host
#define SOL_KEEP_ALIVE_PONG                 0x25  //Host to Console
#define SOL_DATA_TO_HOST                    0x28  //Console to host
#define SOL_CONTROLS_FROM_HOST              0x29  //Host to Console

#define SOL_DATA_FROM_HOST                  0x2A  //Host to Console
#define SOL_HEARTBEAT                       0x2B

#define HEARTBEAT_LENGTH                        8
#define START_SOL_REDIRECTION_LENGTH           24
#define START_SOL_REDIRECTION_REPLY_LENGTH     23 //TODO: There is a OEM Defined data field that we are assuming to be 0 bytes..
#define END_SOL_REDIRECTION_LENGTH             8
#define END_SOL_REDIRECTION_REPLY_LENGTH       8

// Control message control bits (message 0x29)
#define RTS_CONTROL                            1
#define DTR_CONTROL                            2 
#define BREAK_CONTROL                          4

// Control message status bits (message 0x29)
#define TX_OVERFLOW                            1
#define LOOPBACK_ACTIVE                        2
#define SYSTEM_POWER_STATE                     4
#define RX_FLUSH_TIMEOUT                       8
#define TESTMODE_ACTIVE                       16


//IDER Messages Formats
#define START_IDER_REDIRECTION              0x40
#define START_IDER_REDIRECTION_REPLY        0x41
#define END_IDER_REDIRECTION                0x42
#define END_IDER_REDIRECTION_REPLY          0x43
#define IDER_KEEP_ALIVE_PING                0x44  //Console to Host
#define IDER_KEEP_ALIVE_PONG                0x45  //Host to Console
#define IDER_RESET_OCCURED                  0x46
#define IDER_RESET_OCCURED_RESPONSE         0x47
#define IDER_DISABLE_ENABLE_FEATURES        0x48
#define IDER_DISABLE_ENABLE_FEATURES_REPLY  0x49
#define IDER_HEARTBEAT                      0x4B
#define IDER_COMMAND_WRITTEN                0x50
#define IDER_COMMAND_END_RESPONSE           0x51
#define IDER_GET_DATA_FROM_HOST             0x52
#define IDER_DATA_FROM_HOST                 0x53
#define IDER_DATA_TO_HOST                   0x54

#define START_IDER_REDIRECTION_LENGTH                 18
#define START_IDER_REDIRECTION_REPLY_LENGTH           30 //TODO: There is a OEM Defined data field that we are assuming to be 0 bytes..
#define END_IDER_REDIRECTION_LENGTH                   8
#define END_IDER_REDIRECTION_REPLY_LENGTH             8
#define IDER_RESET_OCCURED_LENGTH                     9
#define IDER_RESET_OCCURED_RESPONSE_LENGTH            8
#define IDER_DISABLE_ENABLE_FEATURES_REPLY_LENGTH     13
#define IDER_COMMAND_END_RESPONSE_LENGTH              31
#define IDER_GET_DATA_FROM_HOST_LENGTH                31

static const unsigned int SOL_SESSION = 0x204C4F53;
static const unsigned int IDER_SESSION = 0x52454449;

static const unsigned short MAX_TRANSMIT_BUFFER = 1000;
static const unsigned short TRANSMIT_BUFFER_TIMEOUT = 100;
static const unsigned short TRANSMIT_OVERFLOW_TIMEOUT = 0;
static const unsigned short HOST_SESSION_RX_TIMEOUT = 10000;
static const unsigned short HOST_FIFO_RX_FLUSH_TIMEOUT = 0;
static const unsigned short HEARTBEAT_INTERVAL = 5000;

static const unsigned int SESSION_MANAGER_OEM_IANA_NUMBER = 0x5555; //TODO: Test 
static const unsigned int SOL_OEM_IANA_NUMBER = 0x6666;  //TODO: Test

static const unsigned short RECEIVE_BUFFER_SIZE = 0x100;

#endif
