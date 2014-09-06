
-- amtc-web SQLite schema dump

-- notifications: Short messages for dashboard
CREATE TABLE "notifications" (
  "id"                INTEGER     PRIMARY KEY AUTOINCREMENT,
  "tstamp"            DATETIME    DEFAULT CURRENT_TIMESTAMP,
  "ntype"             VARCHAR(12),
  "message"           VARCHAR(64)
);

-- organizational units / rooms
CREATE TABLE "ous" (
  "id"                INTEGER      PRIMARY KEY AUTOINCREMENT,
  "parent"            INTEGER      NULL,
  "optionset_id"      INT,
  "name"              VARCHAR(128) NOT NULL,
  "description"       VARCHAR(255),
  
  FOREIGN KEY(optionset_id) REFERENCES optionsets(id)
);

-- hosts to be placed into ous
CREATE TABLE "pcs" (
  "id"                INTEGER     PRIMARY KEY AUTOINCREMENT,
  "ou_id"             INTEGER     NOT NULL,
  "hostname"          VARCHAR(64) NOT NULL,

  FOREIGN KEY(ou_id) REFERENCES ous(id)
);

-- state logging of hosts. log occurs upon state change.
CREATE TABLE "statelog" (
  "pcid"              INTEGER     DEFAULT NULL,
  "logdate"           TIMESTAMP   NOT NULL,
  "open_port"         INTEGER     DEFAULT NULL,
  "state_begin"       INTEGER     DEFAULT NULL,
  "state_amt"         INTEGER, 
  "state_http"        INTEGER
);
CREATE INDEX "logdata_ld" ON "statelog" ("logdate");
CREATE INDEX "logdata_pd" ON "statelog" ("pcid");

-- amt(c) option sets
CREATE TABLE "optionsets" (
  "id"                INTEGER       NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name"              VARCHAR(128)  NOT NULL,
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
  "id"                INTEGER NOT NULL PRIMARY KEY,
  "cmd_state"         INTEGER DEFAULT '0',
  "createdat"         DATETIME DEFAULT NULL,
  "username"          TEXT,
  "amtc_cmd"          CHAR(1) NOT NULL,
  "amtc_hosts"        TEXT, -- now ids of hosts...? FIXME tbd
  "startedat"         TEXT,
  "doneat"            TEXT,
  "amtc_delay"        REAL,
  "ou_id"             INTEGER, -- req'd to determine optionset; allow override?

  FOREIGN KEY(ou_id) REFERENCES ous(id)
);
