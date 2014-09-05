
-- example (old) notifications that will show up in dashboard
INSERT INTO "notifications" VALUES(1,'2014-07-31 19:45:06','user','greenfrog: reset all hosts in E27');
INSERT INTO "notifications" VALUES(2,'2014-07-31 19:54:04','comment','greenfrog commented on E27');
INSERT INTO "notifications" VALUES(3,'2014-08-01 07:51:03','envelope','daily status report sent');
INSERT INTO "notifications" VALUES(4,'2014-08-01 07:30:43','toggle-on','E27: scheduled power up success');
INSERT INTO "notifications" VALUES(5,'2014-08-01 21:46:13','toggle-off','E27: scheduled power down success');
INSERT INTO "notifications" VALUES(6,'2014-08-02 03:46:38','warning','More than 10 hosts unreachable!');
INSERT INTO "notifications" VALUES(7,'2014-08-02 07:50:51','envelope','daily status report sent');

-- example OUs ...
INSERT INTO "ous" VALUES(1,'NULL','ROOT','root');
INSERT INTO "ous" VALUES(2,1,'Student labs','Computer rooms');
INSERT INTO "ous" VALUES(3,2,'E-Stock','Stockwerk E');
INSERT INTO "ous" VALUES(4,2,'D-Stock','Stockwerk D');
INSERT INTO "ous" VALUES(5,1,'Course rooms','Playground');
INSERT INTO "ous" VALUES(6,5,'My room A','Homebase');
INSERT INTO "ous" VALUES(7,5,'My room B','Homebase');
-- and some real rooms
INSERT INTO "ous" VALUES(8,3,'E 19','');
INSERT INTO "ous" VALUES(9,3,'E 26.1','');
INSERT INTO "ous" VALUES(10,3,'E 26.3','');
INSERT INTO "ous" VALUES(11,3,'E 27','');
INSERT INTO "ous" VALUES(12,4,'D 11','');
INSERT INTO "ous" VALUES(13,4,'D 12','');
INSERT INTO "ous" VALUES(14,4,'D 13','');
-- and some amtc option sets
INSERT INTO "optionsets" VALUES(1,'AMT v6 - No TLS','Uses EOI',0,0,1,1,0,0,40,5,'/tmp/test1.pass','');
INSERT INTO "optionsets" VALUES(2,'AMT v9 - No TLS','Uses DASH',0,1,1,1,0,0,40,5,'/tmp/test2.pass','');
INSERT INTO "optionsets" VALUES(3,'AMT v9 - with TLS, no cert check','Uses DASH',0,1,1,1,1,1,40,5,'/tmp/test2.pass','');
INSERT INTO "optionsets" VALUES(4,'AMT v9 - with TLS, verify cert','Uses DASH',0,1,1,1,1,0,40,5,'/tmp/test2.pass','/tmp/my.ca.crt');
