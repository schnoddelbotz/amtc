
--
-- Minimal initial set of records to let amtc-web look ok after initial install
--

-- example OUs ...
INSERT INTO ou VALUES(1,NULL,NULL,'ROOT','root',0,0);
INSERT INTO ou VALUES(2,1,NULL,'Student labs','Computer rooms (empty example OU)',0,0);

-- system users ... (origin for jobs and notifications)
INSERT INTO user VALUES(1,1,1,1,1,'admin'  ,'amtc-web system account');
INSERT INTO user VALUES(2,1,1,0,1,'spooler','cron-based job spooler' );

-- example notification that will show up in dashboard
INSERT INTO notification (user_id,ntype,message) values (1,'warning','Congrats, amtc-web installed!');

-- task for scheduled monitoring
INSERT INTO job VALUES(1,3,0,20141218131717,1,'I',NULL,NULL,NULL,NULL,60,1,127,NULL,NULL,NULL,'Monitoring');
