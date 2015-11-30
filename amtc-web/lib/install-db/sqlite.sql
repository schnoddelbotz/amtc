--
-- sqlite.sql - part of amtc-web, part of amtc
-- https://github.com/schnoddelbotz/amtc
--
-- amtc-web SQLite schema-only dump
--

-- notifications: Short messages for dashboard
CREATE TABLE "notification" (
  "id"                INTEGER      PRIMARY KEY,
  "tstamp"            INTEGER(4)   DEFAULT (strftime('%s','now')),
  "user_id"           INT          NOT NULL,
  "ntype"             VARCHAR(12),
  "message"           VARCHAR(64),

  FOREIGN KEY(user_id) REFERENCES user(id)
);

-- organizational units / rooms
CREATE TABLE "ou" (
  "id"                INTEGER      PRIMARY KEY AUTOINCREMENT,
  "parent_id"         INTEGER      NULL,
  "optionset_id"      INT,
  "name"              VARCHAR(128) NOT NULL,
  "description"       VARCHAR(255),
  "idle_power"        REAL,
  "logging"           INT          DEFAULT 1,

  FOREIGN KEY(optionset_id) REFERENCES optionset(id),
  FOREIGN KEY(parent_id) REFERENCES ou(id) ON DELETE RESTRICT
);

-- clients to be placed into ous
CREATE TABLE "user" (
  "id"                INTEGER      PRIMARY KEY AUTOINCREMENT,
  "ou_id"             INTEGER      NOT NULL,    -- currently only one related (top) OU; no distinct permissions
  "is_enabled"        INTEGER      DEFAULT 1,
  "is_admin"          INTEGER      DEFAULT 0,
  "can_control"       INTEGER      DEFAULT 1,
  "name"              VARCHAR(64)  NOT NULL,
  "fullname"          VARCHAR(64)  NOT NULL,

  FOREIGN KEY(ou_id) REFERENCES ou(id)
);

-- clients to be placed into ous
CREATE TABLE "host" (
  "id"                INTEGER      PRIMARY KEY AUTOINCREMENT,
  "ou_id"             INTEGER      NOT NULL,
  "hostname"          VARCHAR(64)  NOT NULL,
  "enabled"           INTEGER      DEFAULT 1,

  FOREIGN KEY(ou_id) REFERENCES ou(id) ON DELETE RESTRICT
);

-- state logging of hosts. log occurs upon state change.
CREATE TABLE "statelog" (
  "host_id"           INTEGER      NOT NULL,
  "state_begin"       INTEGER(4)   DEFAULT (strftime('%s','now')),
  "open_port"         INTEGER      DEFAULT NULL,
  "state_amt"         INTEGER(1),
  "state_http"        INTEGER(2),

  FOREIGN KEY(host_id) REFERENCES host(id) ON DELETE CASCADE
);
CREATE INDEX "logdata_ld" ON "statelog" ("state_begin");
CREATE INDEX "logdata_pd" ON "statelog" ("host_id");
CREATE TABLE "laststate" (
  "id"                INTEGER      PRIMARY KEY AUTOINCREMENT,
  "host_id"           INTEGER      NOT NULL,
  "hostname"          VARCHAR(64)  NOT NULL,
  "state_begin"       INTEGER(4)   DEFAULT (strftime('%s','now')),
  "open_port"         INTEGER      DEFAULT NULL,
  "state_amt"         INTEGER(1),
  "state_http"        INTEGER(2),

  FOREIGN KEY(host_id) REFERENCES host(id) ON DELETE CASCADE
);
CREATE VIEW "logday" AS
  SELECT DISTINCT(date(state_begin,'unixepoch','localtime')) AS id
  FROM statelog;


-- amt(c) option sets
CREATE TABLE "optionset" (
  "id"                INTEGER      NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name"              VARCHAR(128) NOT NULL,
  "description"       VARCHAR(128),
  "sw_v5"             INTEGER,
  "sw_dash"           INTEGER,
  "sw_scan22"         INTEGER,
  "sw_scan3389"       INTEGER,
  "sw_usetls"         INTEGER,
  "sw_skipcertchk"    INTEGER,
  "opt_timeout"       INTEGER,
  "opt_passfile"      VARCHAR(128),
  "opt_cacertfile"    VARCHAR(128)
);

-- monitoring / scheduled tasks / interactive jobs
CREATE TABLE "job" (
  "id"                INTEGER      NOT NULL PRIMARY KEY,
  "job_type"          INTEGER,     -- 1=interactive, 2=scheduled, 3=monitor
  "job_status"        INTEGER      DEFAULT '0',
  "createdat"         INTEGER(4)   DEFAULT (strftime('%s','now')),
  "user_id"           INTEGER      NOT NULL,

  "amtc_cmd"          CHAR(1)      NOT NULL,  -- U/D/R/C
  "amtc_delay"        REAL,
  "amtc_bootdevice"   CHAR(1)      DEFAULT NULL, -- tbd; no support in amtc yet

  "amtc_hosts"        TEXT, -- now ids of hosts...? FIXME tbd
  "ou_id"             INTEGER, -- req'd to determine optionset; allow override?

  "start_time"        INTEGER(4)   DEFAULT NULL, -- start time at day; tbd= minutes?
  "repeat_interval"   INTEGER, -- minutes
  "repeat_days"       INTEGER, -- pow(2, getdate()[wday])
  "last_started"      INTEGER(4)   DEFAULT NULL,
  "last_done"         INTEGER(4)   DEFAULT NULL,
  "proc_pid"          INTEGER, -- process id of currently running job

  "description"       VARCHAR(32), -- to reference it e.g. in logs (insb. sched)
  FOREIGN KEY(ou_id) REFERENCES ou(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES user(id)
);

--- SYNC MySQL !!!
