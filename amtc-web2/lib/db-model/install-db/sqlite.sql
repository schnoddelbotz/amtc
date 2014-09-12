
-- amtc-web SQLite schema dump

-- notifications: Short messages for dashboard
CREATE TABLE "notifications" (
  "id"                INTEGER      PRIMARY KEY,
  "tstamp"            INTEGER(4)   DEFAULT (strftime('%s','now')),
  "ntype"             VARCHAR(12),
  "message"           VARCHAR(64)
);

-- organizational units / rooms
CREATE TABLE "ous" (
  "id"                INTEGER      PRIMARY KEY AUTOINCREMENT,
  "parent_id"         INTEGER      NULL,
  "optionset_id"      INT,
  "name"              VARCHAR(128) NOT NULL,
  "description"       VARCHAR(255),
  
  FOREIGN KEY(optionset_id) REFERENCES optionsets(id),
  FOREIGN KEY(parent_id) REFERENCES ous(id) ON DELETE RESTRICT
);

-- clients to be placed into ous
CREATE TABLE "hosts" (
  "id"                INTEGER      PRIMARY KEY AUTOINCREMENT,
  "ou_id"             INTEGER      NOT NULL,
  "hostname"          VARCHAR(64)  NOT NULL,

  FOREIGN KEY(ou_id) REFERENCES ous(id)
);

-- state logging of hosts. log occurs upon state change.
CREATE TABLE "statelogs" (
  "pcid"              INTEGER      DEFAULT NULL,
  "tstamp"            INTEGER(4)   DEFAULT (strftime('%s','now')),
  "open_port"         INTEGER      DEFAULT NULL,
  "state_begin"       INTEGER      DEFAULT NULL,
  "state_amt"         INTEGER, 
  "state_http"        INTEGER
);
CREATE INDEX "logdata_ld" ON "statelogs" ("tstamp");
CREATE INDEX "logdata_pd" ON "statelogs" ("pcid");

-- amt(c) option sets
CREATE TABLE "optionsets" (
  "id"                INTEGER      NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name"              VARCHAR(128) NOT NULL,
  "description"       VARCHAR(128),
  "sw_v5"             INTEGER,
  "sw_dash"           INTEGER,
  "sw_scan22"         INTEGER,
  "sw_scan3389"       INTEGER,
  "sw_usetls"         INTEGER,
  "sw_skipcertchk"    INTEGER,
  "opt_maxthreads"    INTEGER,
  "opt_timeout"       INTEGER,
  "opt_passfile"      VARCHAR(128),
  "opt_cacertfile"    VARCHAR(128)
);


-- amtc-web v1 ... tbd
-- undone... scheduled tasks should create jobs, too (not only interactive...)?
CREATE TABLE "jobs" (
  "id"                INTEGER      NOT NULL PRIMARY KEY,
  "cmd_state"         INTEGER      DEFAULT '0',
  "createdat"         INTEGER(4)   DEFAULT (strftime('%s','now')),
  "username"          TEXT,
  "amtc_cmd"          CHAR(1)      NOT NULL,  -- U/D/R/C
  "amtc_hosts"        TEXT, -- now ids of hosts...? FIXME tbd
  "startedat"         INTEGER(4)   DEFAULT NULL,
  "doneat"            INTEGER(4)   DEFAULT NULL,
  "amtc_delay"        REAL,
  "bootdevice"        CHAR(1)      DEFAULT NULL, -- tbd; no support in amtc yet
  "ou_id"             INTEGER, -- req'd to determine optionset; allow override?

  FOREIGN KEY(ou_id) REFERENCES ous(id)
);
