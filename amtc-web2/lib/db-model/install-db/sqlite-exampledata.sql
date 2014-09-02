
-- example (old) notifications that will show up in dashboard
INSERT INTO "notifications" VALUES(1,'2014-07-31 19:45:06','user','greenfrog: reset all hosts in E27');
INSERT INTO "notifications" VALUES(2,'2014-07-31 19:54:04','comment','greenfrog commented on E27');
INSERT INTO "notifications" VALUES(3,'2014-08-01 07:51:03','envelope','daily status report sent');
INSERT INTO "notifications" VALUES(4,'2014-08-01 07:30:43','toggle-on','E27: scheduled power up success');
INSERT INTO "notifications" VALUES(5,'2014-08-01 21:46:13','toggle-off','E27: scheduled power down success');
INSERT INTO "notifications" VALUES(6,'2014-08-02 03:46:38','warning','More than 10 hosts unreachable!');
INSERT INTO "notifications" VALUES(7,'2014-08-02 07:50:51','envelope','daily status report sent');

-- example 
INSERT INTO "ous" VALUES(1,'NULL','ROOT','root');
INSERT INTO "ous" VALUES(2,1,'Student labs','Computer rooms');
INSERT INTO "ous" VALUES(3,2,'E-Stock','Stockwerk E');
INSERT INTO "ous" VALUES(4,2,'D-Stock','Stockwerk D');
INSERT INTO "ous" VALUES(5,1,'Course rooms','Playground');
INSERT INTO "ous" VALUES(6,5,'My room A','Homebase');
INSERT INTO "ous" VALUES(7,5,'My room B','Homebase');

