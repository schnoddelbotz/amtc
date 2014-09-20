
-- example (old) notifications that will show up in dashboard
INSERT INTO notifications VALUES(NULL,NULL,2,'envelope','daily status report sent');
INSERT INTO notifications VALUES(NULL,NULL,2,'user','greenfrog: reset all hosts in E27');
INSERT INTO notifications VALUES(NULL,NULL,2,'comment','greenfrog commented on E27');
INSERT INTO notifications VALUES(NULL,NULL,2,'toggle-off','E27: scheduled power down success');
INSERT INTO notifications VALUES(NULL,NULL,2,'toggle-on','E27: scheduled power up success');
INSERT INTO notifications VALUES(NULL,NULL,2,'envelope','daily status report sent');
INSERT INTO notifications VALUES(NULL,NULL,2,'warning','More than 10 hosts unreachable!');

-- some amtc option sets
INSERT INTO optionsets VALUES(1,'DASH / No TLS','Uses DASH',0,1,1,1,0,0,200,10,'/tmp/test2.pass','');
INSERT INTO optionsets VALUES(2,'DASH / TLS / VerifyCertSkip','Skips TLS certificate verification',0,1,1,1,1,1,175,10,'/tmp/test2.pass','');
INSERT INTO optionsets VALUES(3,'DASH / TLS / VerifyCert','Most secure optionset',0,1,1,1,1,0,150,15,'/tmp/test3.pass','/tmp/my.ca.crt');
INSERT INTO optionsets VALUES(4,'EOI / No TLS - AMT v5','For old hardware with AMT 5.0 (around 2008)',1,0,0,0,0,0,100,10,'/tmp/testv5.pass',NULL);
INSERT INTO optionsets VALUES(5,'EOI / No TLS - AMT v6-8','EOI + No TLS = the fastest. But only does digest auth via http.',0,0,1,1,0,0,250,6,'/tmp/test1.pass','');

-- example OUs ...
-- comes in via minimal already:
-- INSERT INTO ous VALUES(1,NULL,NULL,'ROOT','root');
-- INSERT INTO ous VALUES(2,1,NULL,'Student labs','Computer rooms');
INSERT INTO ous VALUES(3,2,NULL,'E Floor','All rooms on E floor',0,0);
INSERT INTO ous VALUES(4,2,NULL,'D Floor','All rooms on D floor',0,0);
INSERT INTO ous VALUES(5,1,NULL,'Course rooms','Playground',0,0);
INSERT INTO ous VALUES(6,5,NULL,'WOS D 12.1','No optionset yet',0,0);
INSERT INTO ous VALUES(7,5,NULL,'WIT G 14','Testing ... No optionset, too',0,0);
-- and some real rooms
INSERT INTO ous VALUES(8,3,3,'E 19','',24.5,1);
INSERT INTO ous VALUES(9,3,2,'E 20','',32.3,0);
INSERT INTO ous VALUES(10,4,4,'D 11','',24.5,0);
INSERT INTO ous VALUES(11,3,1,'E 27','',32.3,1);

-- put some hosts into two of the rooms
INSERT INTO hosts VALUES(1,11,'labpc-e27-160',1);
INSERT INTO hosts VALUES(2,11,'labpc-e27-161',1);
INSERT INTO hosts VALUES(3,11,'labpc-e27-162',1);
INSERT INTO hosts VALUES(4,11,'labpc-e27-163',1);
INSERT INTO hosts VALUES(5,11,'labpc-e27-164',1);
INSERT INTO hosts VALUES(6,11,'labpc-e27-165',1);
INSERT INTO hosts VALUES(7,11,'labpc-e27-166',1);
INSERT INTO hosts VALUES(8,11,'labpc-e27-167',1);
INSERT INTO hosts VALUES(9,11,'labpc-e27-168',1);
INSERT INTO hosts VALUES(10,11,'labpc-e27-169',1);
INSERT INTO hosts VALUES(11,8,'labpc-e19-18',1);
INSERT INTO hosts VALUES(12,8,'labpc-e19-19',1);
INSERT INTO hosts VALUES(13,8,'labpc-e19-20',1);
INSERT INTO hosts VALUES(14,8,'labpc-e19-21',0);
INSERT INTO hosts VALUES(15,8,'labpc-e19-22',0);
INSERT INTO hosts VALUES(16,9,'labpc-e20-01',0);
INSERT INTO hosts VALUES(17,9,'labpc-e20-02',0);
INSERT INTO hosts VALUES(18,9,'labpc-e20-03',0);
INSERT INTO hosts VALUES(19,9,'labpc-e20-04',0);
INSERT INTO hosts VALUES(20,9,'labpc-e20-05',0);
INSERT INTO hosts VALUES(21,9,'labpc-e20-06',0);
INSERT INTO hosts VALUES(22,9,'labpc-e20-07',0);
INSERT INTO hosts VALUES(23,9,'labpc-e20-08',0);
INSERT INTO hosts VALUES(24,9,'labpc-e20-09',0);
INSERT INTO hosts VALUES(25,9,'labpc-e20-10',0);

-- have some statelogs for example pcs
-- ... in the past ...
INSERT INTO statelogs VALUES(1,null,0,4,200);
INSERT INTO statelogs VALUES(2,null,0,16,0);
INSERT INTO statelogs VALUES(3,null,0,3,200);
INSERT INTO statelogs VALUES(3,null,0,5,200); -- two entries for id 3
-- none for id 4
INSERT INTO statelogs VALUES(5,null,0,3,200);
INSERT INTO statelogs VALUES(6,null,0,5,200);
INSERT INTO statelogs VALUES(7,null,3389,0,200);
INSERT INTO statelogs VALUES(8,null,0,3,200);
INSERT INTO statelogs VALUES(9,null,0,16,0);
INSERT INTO statelogs VALUES(10,null,0,16,0);
INSERT INTO statelogs VALUES(11,null,0,4,200);
INSERT INTO statelogs VALUES(12,null,22,0,200);
INSERT INTO statelogs VALUES(13,null,22,0,200);
INSERT INTO statelogs VALUES(14,null,0,16,0);
INSERT INTO statelogs VALUES(15,null,0,16,0);
INSERT INTO statelogs VALUES(15,null,0,0,200);
-- ... and some current ones ...
INSERT INTO statelogs (host_id,open_port,state_amt,state_http) VALUES (16,22,0,200);
INSERT INTO statelogs (host_id,open_port,state_amt,state_http) VALUES (17,22,0,200);
INSERT INTO statelogs (host_id,open_port,state_amt,state_http) VALUES (18,22,0,200);
INSERT INTO statelogs (host_id,open_port,state_amt,state_http) VALUES (19,22,0,200);
INSERT INTO statelogs (host_id,open_port,state_amt,state_http) VALUES (20,22,0,200);
INSERT INTO statelogs (host_id,open_port,state_amt,state_http) VALUES (21,22,0,200);
INSERT INTO statelogs (host_id,open_port,state_amt,state_http) VALUES (22,0,0,200);
INSERT INTO statelogs (host_id,open_port,state_amt,state_http) VALUES (23,22,0,200);
INSERT INTO statelogs (host_id,open_port,state_amt,state_http) VALUES (24,22,0,200);
INSERT INTO statelogs (host_id,open_port,state_amt,state_http) VALUES (25,22,0,200);