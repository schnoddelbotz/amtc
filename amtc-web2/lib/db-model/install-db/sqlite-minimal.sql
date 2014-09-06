
--
-- Minimal initial set of records to let amtc-web look ok after initial install
--

-- example notification that will show up in dashboard
INSERT INTO "notifications" (ntype,message) values ('warning','Congrats, amtc-web installed!');

-- example OUs ...
INSERT INTO "ous" VALUES(1,NULL,NULL,'ROOT','root');
INSERT INTO "ous" VALUES(2,1,NULL,'Student labs','Computer rooms (empty example OU)');

