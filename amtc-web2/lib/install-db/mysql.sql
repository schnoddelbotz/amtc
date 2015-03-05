--
-- mysql.sql - part of amtc-web, part of amtc
-- https://github.com/schnoddelbotz/amtc
--
-- amtc-web SQLite schema-only dump
--

SET foreign_key_checks = 0;

-- notifications: Short messages for dashboard
CREATE TABLE IF NOT EXISTS notification (
  id                INTEGER      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tstamp            TIMESTAMP,
  user_id           INT          NOT NULL,
  ntype             VARCHAR(12),
  message           VARCHAR(64),

  FOREIGN KEY(user_id) REFERENCES user(id)
);

-- organizational units / rooms
CREATE TABLE IF NOT EXISTS ou (
  id                INTEGER      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  parent_id         INTEGER      NULL,
  optionset_id      INT,
  name              VARCHAR(128) NOT NULL,
  description       VARCHAR(255),
  idle_power        REAL,
  logging           INT          DEFAULT 1,

  FOREIGN KEY(optionset_id) REFERENCES optionset(id),
  FOREIGN KEY(parent_id) REFERENCES ou(id) ON DELETE RESTRICT
);

-- clients to be placed into ous
CREATE TABLE IF NOT EXISTS user (
  id                INTEGER      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ou_id             INTEGER      NOT NULL,    -- currently only one related (top) OU; no distinct permissions
  is_enabled        INTEGER      DEFAULT 1,
  is_admin          INTEGER      DEFAULT 0,
  can_control       INTEGER      DEFAULT 1,
  name              VARCHAR(64)  NOT NULL,
  fullname          VARCHAR(64)  NOT NULL,

  FOREIGN KEY(ou_id) REFERENCES ou(id)
);

-- clients to be placed into ous
CREATE TABLE IF NOT EXISTS host (
  id                INTEGER      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ou_id             INTEGER      NOT NULL,
  hostname          VARCHAR(64)  NOT NULL,
  enabled           INTEGER      DEFAULT 1,

  FOREIGN KEY(ou_id) REFERENCES ou(id)
);

-- state logging of hosts. log occurs upon state change.
CREATE TABLE IF NOT EXISTS statelog (
  host_id           INTEGER      NOT NULL,
  state_begin       TIMESTAMP,
  open_port         INTEGER      DEFAULT NULL,
  state_amt         INTEGER(1),
  state_http        INTEGER(2),

  FOREIGN KEY(host_id) REFERENCES host(id)
);
CREATE INDEX logdata_ld ON statelog (state_begin);
CREATE INDEX logdata_pd ON statelog (host_id);
CREATE VIEW  laststate  AS   -- ... including fake id column to make e-d happy
  SELECT host_id AS id, host_id,max(state_begin) AS state_begin,open_port,state_amt,state_http
  FROM statelog GROUP BY host_id;

-- amt(c) option sets
CREATE TABLE IF NOT EXISTS optionset (
  id                INTEGER      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(128) NOT NULL,
  description       VARCHAR(128),
  sw_v5             INTEGER,
  sw_dash           INTEGER,
  sw_scan22         INTEGER,
  sw_scan3389       INTEGER,
  sw_usetls         INTEGER,
  sw_skipcertchk    INTEGER,
  opt_timeout       INTEGER,
  opt_passfile      VARCHAR(128),
  opt_cacertfile    VARCHAR(128)
);


-- amtc-web v1 ... tbd
-- undone... scheduled tasks should create jobs, too (not only interactive...)?
CREATE TABLE job (
  id                INTEGER      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  cmd_state         INTEGER      DEFAULT '0',
  createdat         TIMESTAMP,
  username          TEXT,
  amtc_cmd          CHAR(1)      NOT NULL,  -- U/D/R/C
  amtc_hosts        TEXT, -- now ids of hosts...? FIXME tbd
  startedat         INTEGER(4)   DEFAULT NULL,
  doneat            INTEGER(4)   DEFAULT NULL,
  amtc_delay        REAL,
  bootdevice        CHAR(1)      DEFAULT NULL, -- tbd; no support in amtc yet
  ou_id             INTEGER, -- req'd to determine optionset; allow override?

  FOREIGN KEY(ou_id) REFERENCES ou(id)
);
