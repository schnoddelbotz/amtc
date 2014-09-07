
-- example (old) notifications that will show up in dashboard
INSERT INTO "notifications" VALUES(NULL,1409902233,'envelope','daily status report sent');
INSERT INTO "notifications" VALUES(NULL,1409934607,'user','greenfrog: reset all hosts in E27');
INSERT INTO "notifications" VALUES(NULL,1409934707,'comment','greenfrog commented on E27');
INSERT INTO "notifications" VALUES(NULL,1409952612,'toggle-off','E27: scheduled power down success');
INSERT INTO "notifications" VALUES(NULL,1409985633,'toggle-on','E27: scheduled power up success');
INSERT INTO "notifications" VALUES(NULL,1409988624,'envelope','daily status report sent');
INSERT INTO "notifications" VALUES(NULL,1410004353,'warning','More than 10 hosts unreachable!');

-- some amtc option sets
INSERT INTO "optionsets" VALUES(1,'DASH / No TLS','Uses DASH',0,1,1,1,0,0,200,10,'/tmp/test2.pass','');
INSERT INTO "optionsets" VALUES(2,'DASH / TLS / VerifyCertSkip','Skips TLS certificate verification',0,1,1,1,1,1,175,10,'/tmp/test2.pass','');
INSERT INTO "optionsets" VALUES(3,'DASH / TLS / VerifyCert','Most secure optionset',0,1,1,1,1,0,150,15,'/tmp/test3.pass','/tmp/my.ca.crt');
INSERT INTO "optionsets" VALUES(4,'EOI / No TLS - AMT v5','For old hardware with AMT 5.0 (around 2008)',1,0,0,0,0,0,100,10,'/tmp/testv5.pass',NULL);
INSERT INTO "optionsets" VALUES(5,'EOI / No TLS - AMT v6-8','EOI + No TLS = the fastest. But only does digest auth via http.',0,0,1,1,0,0,250,6,'/tmp/test1.pass','');

-- example OUs ...
-- comes in via minimal already: 
-- INSERT INTO "ous" VALUES(1,NULL,NULL,'ROOT','root');
-- INSERT INTO "ous" VALUES(2,1,NULL,'Student labs','Computer rooms');
INSERT INTO "ous" VALUES(3,2,NULL,'E Floor','All rooms on E floor');
INSERT INTO "ous" VALUES(4,2,NULL,'D Floor','All rooms on D floor');
INSERT INTO "ous" VALUES(5,1,NULL,'Course rooms','Playground');
INSERT INTO "ous" VALUES(6,5,NULL,'Room A1','No optionset yet');
INSERT INTO "ous" VALUES(7,5,NULL,'Room A2','Testing ... No optionset, too');
-- and some real rooms
INSERT INTO "ous" VALUES(8,3,3,'E 19','');
INSERT INTO "ous" VALUES(9,3,2,'E 26.1','');
INSERT INTO "ous" VALUES(10,3,2,'E 26.3','');
INSERT INTO "ous" VALUES(11,3,1,'E 27','');
INSERT INTO "ous" VALUES(12,4,4,'D 11','');
INSERT INTO "ous" VALUES(13,4,4,'D 12','');
INSERT INTO "ous" VALUES(14,4,4,'D 13','');
