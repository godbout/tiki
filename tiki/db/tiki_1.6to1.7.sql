drop table if exists tiki_forums_queue;
create table tiki_forums_queue(
	qId integer(14) not null auto_increment,
	object char(32),
	parentId integer(14),
	timestamp integer(14),
	user varchar(200),
	title varchar(240),
	data text,
	type varchar(60),
	topic_smiley varchar(80),
	summery varchar(240),
	primary key(qId)
);

alter table tiki_forums add approval_type varchar(20);
update tiki_forums set approval_type='all_posted';

INSERT INTO users_permissions(permName,type,permDesc,level) VALUES ('tiki_p_admin_charts','charts','Can admin charts','admin');
INSERT INTO users_permissions(permName,type,permDesc,level) VALUES ('tiki_p_view_chart','charts','Can view charts','basic');
INSERT INTO users_permissions(permName,type,permDesc,level) VALUES ('tiki_p_vote_chart','charts','Can vote','basic');
INSERT INTO users_permissions(permName,type,permDesc,level) VALUES ('tiki_p_suggest_chart_item','charts','Can suggest items','basic');
INSERT INTO users_permissions(permName,type,permDesc,level) VALUES ('tiki_p_autoval_chart_suggestions','charts','Autovalidate suggestions','editor');

## Change to track duplicate file uploads in file galleries
alter table tiki_files add hash char(32);

##

### Changes in articles
alter table tiki_articles add isfloat char(1);
update tiki_articles set isfloat='n';
alter table tiki_submissions add isfloat char(1);
update tiki_submissions set isfloat='n';
###

drop table if exists tiki_user_watches;
create table tiki_user_watches(
  user varchar(200) not null,
  event varchar(40) not null,
  object varchar(200) not null,
  hash char(32),
  title varchar(250),
  type varchar(200),
  url varchar(250),
  email varchar(200),
  primary key(user,event,object)
);

alter table tiki_user_notes add parse_mode char(20);
update tiki_user_notes set parse_mode='raw';

### Forums configuration ###
alter table tiki_comments add summary varchar(240);
update  tiki_comments set summary='';
alter table tiki_comments add smiley varchar(80);
update tiki_comments set smiley='';

alter table tiki_forums add outbound_address char(1);
alter table tiki_forums add inbound_address char(1);
alter table tiki_forums add topic_smileys char(1);
alter table tiki_forums add ui_avatar char(1);
alter table tiki_forums add ui_flag char(1);
alter table tiki_forums add ui_posts char(1);
alter table tiki_forums add ui_email char(1);
alter table tiki_forums add ui_online char(1);
alter table tiki_forums add topic_summary char(1);

###

update tiki_forums set inbound_address='';
update tiki_forums set outbound_address='';
update tiki_forums set topic_smileys='n';
update tiki_forums set ui_avatar='y';
update tiki_forums set ui_flag='y';
update tiki_forums set ui_posts='n';
update tiki_forums set ui_email='n';
update tiki_forums set ui_online='y';
update tiki_forums set topic_summary='n';

alter table tiki_forums add show_description char(1);
update tiki_forums set show_description='y';
alter table tiki_forums add topics_list_replies char(1);
update tiki_forums set topics_list_replies='y';
alter table tiki_forums add topics_list_reads char(1);
update tiki_forums set topics_list_reads='y';
alter table tiki_forums add topics_list_pts char(1);
update tiki_forums set topics_list_pts='y';
alter table tiki_forums add topics_list_lastpost char(1);
update tiki_forums set topics_list_lastpost='y';
alter table tiki_forums add topics_list_author char(1);
update tiki_forums set topics_list_author='y';
alter table tiki_forums add vote_threads char(1);
update tiki_forums set vote_threads='y';

### end of forums configuration ###

### New feature: charts ###
drop table if exists tiki_charts;
create table tiki_charts(
  chartId integer(14) not null auto_increment,
  title varchar(250),
  description text,
  hits integer(14),
  singleItemVotes char(1),
  singleChartVotes char(1),
  suggestions char(1),
  autoValidate char(1),
  topN integer(6),
  maxVoteValue integer(4),
  frequency integer(14),
  showAverage char(1),
  isActive char(1),
  showVotes char(1),
  useCookies char(1),
  lastChart integer(14),
  voteAgainAfter integer(14),
  created integer(14),
  primary key(chartId)
);

drop table if exists tiki_chart_items;
create table tiki_chart_items(
  itemId integer(14) not null auto_increment,
  title varchar(250),
  description text,
  chartId integer(14) not null,
  created integer(14),
  URL varchar(250),
  votes integer(14),
  points integer(14),
  average decimal(4,2),
  primary key(itemId)
);

drop table if exists tiki_charts_rankings;
create table tiki_charts_rankings(
  chartId integer(14) not null,
  itemId integer(14) not null,
  position integer(14) not null,
  timestamp integer(14) not null,
  lastPosition integer(14) not null,
  period integer(14) not null,
  rvotes integer(14) not null,
  raverage decimal(4,2) not null,
  primary key(chartId,itemId,period)
);


drop table if exists tiki_charts_votes;
create table tiki_charts_votes(
  user varchar(200) not null,
  itemId integer(14) not null,
  timestamp integer(14),
  chartId integer(14),
  primary key(user,ItemId)  
);

### End of charts feature ###


### Modifications for blog improvements
drop table if exists tiki_blog_posts_images;
create table tiki_blog_posts_images(
  imgId integer(14) not null auto_increment,
  postId integer(14) not null,
  filename varchar(80),
  filetype varchar(80),
  filesize integer(14),
  data longblob,
  primary key(imgId)
);

alter table tiki_blogs add heading text;
alter table tiki_blogs add use_find char(1);
alter table tiki_blog_posts add trackbacks_to text;
update tiki_blog_posts set trackbacks_to='';
alter table tiki_blog_posts add trackbacks_from text;
update tiki_blog_posts set trackbacks_from='';
alter table tiki_blogs add use_title char(1);
alter table tiki_blogs add add_date char(1);
alter table tiki_blogs add add_poster char(1);
alter table tiki_blogs add allow_comments char(1);
alter table tiki_blog_posts add title varchar(80);
### End of modifications for blog improvements


alter table tiki_pages add creator varchar(200);

#### Workflow tables and permissions
## Workflow Roles, each role must be asociated with a process
## since roles are not shared among processes. (You can have
## a role with the same name in two processes with two different
## meanings and different users mapped)
drop table if exists galaxia_roles;
create table galaxia_roles(
  roleId integer(14) not null auto_increment,
  pId integer(14) not null,
  lastModif integer(14),
  name varchar(80),
  description text,
  primary key(roleId)
);

## Mapping from users to process roles
drop table if exists galaxia_user_roles;
create table galaxia_user_roles(
  pId integer(14) not null,
  roleId integer(14) not null auto_increment,
  user varchar(200) not null,
  primary key(roleId, user)
);

## Workflow processes.
drop table if exists galaxia_processes;
create table galaxia_processes(
  pId integer(14) not null auto_increment,
  name varchar(80),
  isValid char(1),
  isActive char(1),
  version varchar(12),
  description text,
  lastModif integer(14),
  normalized_name varchar(80),
  primary key(pId)
);

## Process activities
drop table if exists galaxia_activities;
create table galaxia_activities(
  activityId integer(14) not null auto_increment,
  name varchar(80),
  normalized_name varchar(80),
  pId integer(14) not null,
  type enum('start','end','split','switch','join','activity','standalone'),
  isAutoRouted char(1),
  flowNum integer(10),
  isInteractive char(1),
  lastModif integer(14), 
  description text ,
 primary key(activityId)
);

## transitions
drop table if exists galaxia_transitions;
create table galaxia_transitions(
  pId integer(14) not null,
  actFromId integer(14) not null,
  actToId integer(14) not null,
  primary key(actFromId, actToId)
);

## activity roles
drop table if exists galaxia_activity_roles;
create table galaxia_activity_roles(
  activityId integer(14) not null,
  roleId integer(14) not null,
  primary key(activityId, roleId)
);


## instances
## status can be: active,exception,waiting,aborted
drop table if exists galaxia_instances;
create table galaxia_instances(
  instanceId integer(14) not null auto_increment,
  pId integer(14) not null,
  started integer(14),
  owner varchar(200),
  nextActivity integer(14),
  nextUser varchar(200),
  ended integer(14),
  status enum('active','exception','aborted','completed'),
  properties longblob,
  primary key(instanceId)
);

## instance_activities
## tracks where each instance is (can be in two places at the same time!)
drop table if exists galaxia_instance_activities;
create table galaxia_instance_activities(
	instanceId integer(14) not null,
	activityId integer(14) not null,
	started integer(14) not null,
	ended integer(14) not null,
	user varchar(200),
	status enum('running','completed'),
	primary key(instanceId, activityId)
);

## workitems
drop table if exists galaxia_workitems;
create table galaxia_workitems(
  itemId integer(14) not null auto_increment,
  instanceId integer(14) not null,
  orderId integer(14) not null,
  activityId integer(14) not null,
  properties longblob,
  started integer(14),
  ended integer(14),
  user varchar(200),
  primary key(itemId)
);


## Permissions

INSERT INTO users_permissions(permName,type,permDesc,level) VALUES ('tiki_p_admin_workflow','workflow','Can admin workflow processes','admin');
INSERT INTO users_permissions(permName,type,permDesc,level) VALUES ('tiki_p_abort_instance','workflow','Can abort a process instance','editor');
INSERT INTO users_permissions(permName,type,permDesc,level) VALUES ('tiki_p_use_workflow','workflow','Can execute workflow activities','registered');
INSERT INTO users_permissions(permName,type,permDesc,level) VALUES ('tiki_p_exception_instance','workflow','Can declare an instance as exception','registered');
INSERT INTO users_permissions(permName,type,permDesc,level) VALUES ('tiki_p_send_instance','workflow','Can send instances after completion','registered');


### Instance comments
drop table if exists galaxia_instance_comments;
create table galaxia_instance_comments(
  cId integer(14) not null auto_increment,
  instanceId integer(14) not null,
  user varchar(200),
  activityId integer(14),
  hash char(32),
  title varchar(250),
  comment text,
  activity varchar(80),
  timestamp integer(14),
  primary key(cId)
);

#### Workflow schema ends
