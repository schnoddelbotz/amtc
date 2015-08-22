
-- example (old) notifications that will show up in dashboard
INSERT INTO notification VALUES(NULL,NULL,2,'envelope','daily status report sent');
INSERT INTO notification VALUES(NULL,NULL,2,'user','greenfrog: reset all hosts in E27');
INSERT INTO notification VALUES(NULL,NULL,2,'comment','greenfrog commented on E27');
INSERT INTO notification VALUES(NULL,NULL,2,'toggle-off','E27: scheduled power down success');
INSERT INTO notification VALUES(NULL,NULL,2,'toggle-on','E27: scheduled power up success');
INSERT INTO notification VALUES(NULL,NULL,2,'envelope','daily status report sent');
INSERT INTO notification VALUES(NULL,NULL,2,'warning','More than 10 hosts unreachable!');

-- some amtc option sets
INSERT INTO optionset VALUES(1,'DASH / No TLS','Uses DASH',0,1,1,1,0,0,10,'/tmp/test2.pass','');
INSERT INTO optionset VALUES(2,'DASH / TLS / VerifyCertSkip','Skips TLS certificate verification',0,1,1,1,1,1,10,'/tmp/test2.pass','');
INSERT INTO optionset VALUES(3,'DASH / TLS / VerifyCert','Most secure optionset',0,1,1,1,1,0,15,'/tmp/test3.pass','/tmp/my.ca.crt');
INSERT INTO optionset VALUES(4,'EOI / No TLS - AMT v5','For old hardware with AMT 5.0 (around 2008)',1,0,0,0,0,0,10,'/tmp/testv5.pass',NULL);
INSERT INTO optionset VALUES(5,'EOI / No TLS - AMT v6-8','EOI + No TLS = the fastest. But only does digest auth via http.',0,0,1,1,0,0,6,'/tmp/test1.pass','');

-- example OUs ...
-- comes in via minimal already:
-- INSERT INTO ous VALUES(1,NULL,NULL,'ROOT','root');
-- INSERT INTO ous VALUES(2,1,NULL,'Student labs','Computer rooms');
INSERT INTO ou VALUES(3,2,NULL,'E Floor','All rooms on E floor',0,0);
INSERT INTO ou VALUES(4,2,NULL,'D Floor','All rooms on D floor',0,0);
INSERT INTO ou VALUES(5,1,NULL,'Course rooms','Playground',0,0);
INSERT INTO ou VALUES(6,5,NULL,'WOS D 12.1','No optionset yet',0,0);
INSERT INTO ou VALUES(7,5,NULL,'WIT G 14','Testing ... No optionset, too',0,0);
-- and some real rooms
INSERT INTO ou VALUES(8,3,3,'E 19','',24.5,0);
INSERT INTO ou VALUES(9,3,2,'E 20','',32.3,0);
INSERT INTO ou VALUES(10,4,4,'D 11','',24.5,0);
INSERT INTO ou VALUES(11,3,1,'E 27','',32.3,0);

-- put some hosts into two of the rooms
INSERT INTO host VALUES(1,11,'labpc-e27-160',1);
INSERT INTO host VALUES(2,11,'labpc-e27-161',1);
INSERT INTO host VALUES(3,11,'labpc-e27-162',1);
INSERT INTO host VALUES(4,11,'labpc-e27-163',1);
INSERT INTO host VALUES(5,11,'labpc-e27-164',1);
INSERT INTO host VALUES(6,11,'labpc-e27-165',1);
INSERT INTO host VALUES(7,11,'labpc-e27-166',1);
INSERT INTO host VALUES(8,11,'labpc-e27-167',1);
INSERT INTO host VALUES(9,11,'labpc-e27-168',1);
INSERT INTO host VALUES(10,11,'labpc-e27-169',1);
INSERT INTO host VALUES(11,8,'labpc-e19-18',1);
INSERT INTO host VALUES(12,8,'labpc-e19-19',1);
INSERT INTO host VALUES(13,8,'labpc-e19-20',1);
INSERT INTO host VALUES(14,8,'labpc-e19-21',0);
INSERT INTO host VALUES(15,8,'labpc-e19-22',0);
INSERT INTO host VALUES(16,9,'labpc-e20-01',0);
INSERT INTO host VALUES(17,9,'labpc-e20-02',0);
INSERT INTO host VALUES(18,9,'labpc-e20-03',0);
INSERT INTO host VALUES(19,9,'labpc-e20-04',0);
INSERT INTO host VALUES(20,9,'labpc-e20-05',0);
INSERT INTO host VALUES(21,9,'labpc-e20-06',0);
INSERT INTO host VALUES(22,9,'labpc-e20-07',0);
INSERT INTO host VALUES(23,9,'labpc-e20-08',0);
INSERT INTO host VALUES(24,9,'labpc-e20-09',0);
INSERT INTO host VALUES(25,9,'labpc-e20-10',0);

-- have some statelogs for example pcs
-- ... in the past ...
INSERT INTO statelog VALUES(1,null,0,4,200);
INSERT INTO statelog VALUES(2,null,0,16,0);
INSERT INTO statelog VALUES(3,null,0,3,200);
INSERT INTO statelog VALUES(3,null,0,5,200); -- two entries for id 3
-- none for id
INSERT INTO statelog VALUES(5,null,0,3,200);
INSERT INTO statelog VALUES(6,null,0,5,200);
INSERT INTO statelog VALUES(7,null,3389,0,200);
INSERT INTO statelog VALUES(8,null,0,3,200);
INSERT INTO statelog VALUES(9,null,0,16,0);
INSERT INTO statelog VALUES(10,null,0,16,0);
INSERT INTO statelog VALUES(11,null,0,4,200);
INSERT INTO statelog VALUES(12,null,22,0,200);
INSERT INTO statelog VALUES(13,null,22,0,200);
INSERT INTO statelog VALUES(14,null,0,16,0);
INSERT INTO statelog VALUES(15,null,0,16,0);
INSERT INTO statelog VALUES(15,null,0,0,200);
--
INSERT INTO statelog VALUES(16,1410828494,0,5,200);
INSERT INTO statelog VALUES(17,1410828494,0,5,200);
INSERT INTO statelog VALUES(18,1410828494,0,5,200);
INSERT INTO statelog VALUES(19,1410828494,0,5,200);
INSERT INTO statelog VALUES(20,1410828494,0,5,200);
INSERT INTO statelog VALUES(21,1410828494,0,5,200);
INSERT INTO statelog VALUES(22,1410828494,0,5,200);
INSERT INTO statelog VALUES(23,1410828494,0,5,200);
INSERT INTO statelog VALUES(24,1410828494,0,5,200);
INSERT INTO statelog VALUES(25,1410828494,0,5,200);
--
INSERT INTO statelog VALUES(16,1410829494,3389,0,200);
INSERT INTO statelog VALUES(17,1410829494,3389,0,200);
INSERT INTO statelog VALUES(18,1410829494,3389,0,200);
INSERT INTO statelog VALUES(19,1410829454,3389,0,200);
INSERT INTO statelog VALUES(20,1410829464,3389,0,200);
INSERT INTO statelog VALUES(21,1410829474,3389,0,200);
INSERT INTO statelog VALUES(22,1410829494,0,16,0);
INSERT INTO statelog VALUES(23,1410829494,0,3,200);
INSERT INTO statelog VALUES(24,1410829494,0,4,200);
INSERT INTO statelog VALUES(25,1410829494,0,5,200);
INSERT INTO statelog VALUES(25,1410829594,0,0,200);
INSERT INTO statelog VALUES(25,1410829694,22,0,200);
-- ... and some current ones ...
INSERT INTO statelog (host_id,open_port,state_amt,state_http) VALUES (16,22,0,200);
INSERT INTO statelog (host_id,open_port,state_amt,state_http) VALUES (17,22,0,200);
INSERT INTO statelog (host_id,open_port,state_amt,state_http) VALUES (18,22,0,200);
INSERT INTO statelog (host_id,open_port,state_amt,state_http) VALUES (19,22,0,200);
INSERT INTO statelog (host_id,open_port,state_amt,state_http) VALUES (20,22,0,200);
INSERT INTO statelog (host_id,open_port,state_amt,state_http) VALUES (21,22,0,200);
INSERT INTO statelog (host_id,open_port,state_amt,state_http) VALUES (22,0,0,200);
INSERT INTO statelog (host_id,open_port,state_amt,state_http) VALUES (23,22,0,200);
INSERT INTO statelog (host_id,open_port,state_amt,state_http) VALUES (24,22,0,200);
INSERT INTO statelog (host_id,open_port,state_amt,state_http) VALUES (25,22,0,200);

INSERT INTO laststate (host_id,open_port,state_amt,state_http,hostname) VALUES (16,22,0,200,'labpc-e20-01');
INSERT INTO laststate (host_id,open_port,state_amt,state_http,hostname) VALUES (17,22,0,200,'labpc-e20-02');
INSERT INTO laststate (host_id,open_port,state_amt,state_http,hostname) VALUES (18,22,0,200,'labpc-e20-03');
INSERT INTO laststate (host_id,open_port,state_amt,state_http,hostname) VALUES (19,22,0,200,'labpc-e20-04');
INSERT INTO laststate (host_id,open_port,state_amt,state_http,hostname) VALUES (20,22,0,200,'labpc-e20-05');
INSERT INTO laststate (host_id,open_port,state_amt,state_http,hostname) VALUES (21,22,0,200,'labpc-e20-06');
INSERT INTO laststate (host_id,open_port,state_amt,state_http,hostname) VALUES (22,0,0,200,'labpc-e20-07');
INSERT INTO laststate (host_id,open_port,state_amt,state_http,hostname) VALUES (23,22,0,200,'labpc-e20-08');
INSERT INTO laststate (host_id,open_port,state_amt,state_http,hostname) VALUES (24,22,0,200,'labpc-e20-09');
INSERT INTO laststate (host_id,open_port,state_amt,state_http,hostname) VALUES (25,22,0,200,'labpc-e20-10');
