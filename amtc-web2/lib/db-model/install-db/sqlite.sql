--
-- sqlite.sql - part of amtc-web, part of amtc
-- https://github.com/schnoddelbotz/amtc
--
-- amtc-web SQLite schema-only dump
--

-- notifications: Short messages for dashboard
CREATE TABLE "notifications" (
  "id"                INTEGER      PRIMARY KEY,
  "tstamp"            INTEGER(4)   DEFAULT (strftime('%s','now')),
  "user_id"           INT          NOT NULL,
  "ntype"             VARCHAR(12),
  "message"           VARCHAR(64),

  FOREIGN KEY(user_id) REFERENCES users(id)
);

-- organizational units / rooms
CREATE TABLE "ous" (
  "id"                INTEGER      PRIMARY KEY AUTOINCREMENT,
  "parent_id"         INTEGER      NULL,
  "optionset_id"      INT,
  "name"              VARCHAR(128) NOT NULL,
  "description"       VARCHAR(255),
  "idle_power"        REAL,
  "logging"           INT          DEFAULT 1,
  
  FOREIGN KEY(optionset_id) REFERENCES optionsets(id),
  FOREIGN KEY(parent_id) REFERENCES ous(id) ON DELETE RESTRICT
);

-- clients to be placed into ous
CREATE TABLE "users" (
  "id"                INTEGER      PRIMARY KEY AUTOINCREMENT,
  "ou_id"             INTEGER      NOT NULL,    -- currently only one related (top) OU; no distinct permissions
  "is_enabled"        INTEGER      DEFAULT 1,
  "is_admin"          INTEGER      DEFAULT 0,
  "can_control"       INTEGER      DEFAULT 1,
  "name"              VARCHAR(64)  NOT NULL,
  "fullname"          VARCHAR(64)  NOT NULL,

  FOREIGN KEY(ou_id) REFERENCES ous(id)
);

-- clients to be placed into ous
CREATE TABLE "hosts" (
  "id"                INTEGER      PRIMARY KEY AUTOINCREMENT,
  "ou_id"             INTEGER      NOT NULL,
  "hostname"          VARCHAR(64)  NOT NULL,
  "enabled"           INTEGER      DEFAULT 1,

  FOREIGN KEY(ou_id) REFERENCES ous(id)
);

-- state logging of hosts. log occurs upon state change.
CREATE TABLE "statelogs" (
  "host_id"           INTEGER      NOT NULL,
  "state_begin"       INTEGER(4)   DEFAULT (strftime('%s','now')),
  "open_port"         INTEGER      DEFAULT NULL,
  "state_amt"         INTEGER(1), 
  "state_http"        INTEGER(2),

  FOREIGN KEY(host_id) REFERENCES hosts(id)
);
CREATE INDEX "logdata_ld" ON "statelogs" ("state_begin"); 
CREATE INDEX "logdata_pd" ON "statelogs" ("host_id");
CREATE VIEW  "laststates"  AS   -- ... including fake id column to make e-d happy
  SELECT host_id AS id, host_id,max(state_begin) AS state_begin,open_port,state_amt,state_http 
  FROM statelogs GROUP BY host_id;

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
