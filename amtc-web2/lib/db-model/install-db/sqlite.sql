
-- amtc-web SQLite schema dump

-- notifications: Short messages for dashboard
CREATE TABLE notifications (
  id                INTEGER PRIMARY KEY, 
  tstamp            DATETIME DEFAULT CURRENT_TIMESTAMP, 
  ntype             VARCHAR(12), 
  message           VARCHAR(64)
);

-- organizational units / rooms
CREATE TABLE ous (
  id                INTEGER      NOT NULL PRIMARY KEY AUTOINCREMENT,
  parent            INTEGER      NULL,
  optionset_id      INT,
  name              VARCHAR(128) NOT NULL,
  description       VARCHAR(255),
  
  FOREIGN KEY(optionset_id) REFERENCES optionsets(id)
);

-- amt(c) option sets
CREATE TABLE optionsets (
  id                INTEGER      NOT NULL PRIMARY KEY AUTOINCREMENT,
  name              VARCHAR(128) NOT NULL,
  description       VARCHAR(128),
  sw_v5             INTEGER,
  sw_dash           INTEGER,
  sw_scan22         INTEGER,
  sw_scan3389       INTEGER,
  sw_usetls         INTEGER,
  sw_skipcertchk    INTEGER,
  opt_maxthreads    INTEGER,
  opt_timeout       INTEGER,
  opt_passfile      VARCHAR(128),
  opt_cacertfile    VARCHAR(128)
);

