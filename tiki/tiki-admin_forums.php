<?php
// Initialization
require_once('tiki-setup.php');

if(!isset($_REQUEST["forumId"])) {
  $_REQUEST["forumId"] = 0;
}
$smarty->assign('forumId',$_REQUEST["forumId"]);

if($feature_forums != 'y') {
  $smarty->assign('msg',tra("This feature is disabled"));
  $smarty->display("styles/$style_base/error.tpl");
  die;  
}


$smarty->assign('individual','n');
if($userlib->object_has_one_permission($_REQUEST["forumId"],'forum')) {
  $smarty->assign('individual','y');
  if($tiki_p_admin != 'y') {
    $perms = $userlib->get_permissions(0,-1,'permName_desc','','forums');
    foreach($perms["data"] as $perm) {
      $permName=$perm["permName"];
      if($userlib->object_has_permission($user,$_REQUEST["forumId"],'forum',$permName)) {
        $$permName = 'y';
        $smarty->assign("$permName",'y');
      } else {
        $$permName = 'n';
        $smarty->assign("$permName",'n');
      }
    }
  }
}

if($tiki_p_admin_forum != 'y') {
    $smarty->assign('msg',tra("You dont have permission to use this feature"));
    $smarty->display("styles/$style_base/error.tpl");
    die;
}




include_once("lib/commentslib.php");
$commentslib = new Comments($dbTiki);

if($_REQUEST["forumId"]) {
  $info = $commentslib->get_forum($_REQUEST["forumId"]);
} else {
  $info = Array();
  $info["name"]='';
  $info["description"]='';
  $info["controlFlood"]='n';
  $info["floodInterval"]=120;
  $info["moderator"]='admin';
  $info["section"]='';
  $info["mail"]='';
  $info["topicsPerPage"]='20';
  $info["useMail"]='n';
  $info["topicOrdering"]='commentDate_desc';
  $info["threadOrdering"]='commentDate_desc';
  $info["usePruneUnreplied"]='n';
  $info["topics_list_replies"]='y';
  $info["show_description"]='n';
  
  $info["outbound_address"]='';
  $info["inbound_address"]='';
  $info["topic_summary"]='n';
  $info["topic_smileys"]='n';
  $info["ui_avatar"]='y';
  $info["ui_flag"]='y';
  $info["ui_posts"]='n';
  $info["ui_email"]='n';
  $info["ui_online"]='n';
  $info["approval_type"]='all_posted';
  $info["moderator_group"]='';
          
  $info["topics_list_reads"]='y';
  $info["topics_list_pts"]='y';
  $info["topics_list_lastpost"]='y';
  $info["topics_list_author"]='y';        
  $info["vote_threads"]='y';        
  $info["pruneUnrepliedAge"]=60*60*24*30;
  $info["usePruneOld"]='n';
  $info["pruneMaxAge"]= 60*60*24*30;
}
$smarty->assign('name',$info["name"]);
$smarty->assign('description',$info["description"]);
$smarty->assign('controlFlood',$info["controlFlood"]);
$smarty->assign('floodInterval',$info["floodInterval"]);
$smarty->assign('topicOrdering',$info["topicOrdering"]);
$smarty->assign('threadOrdering',$info["threadOrdering"]);
$smarty->assign('moderator',$info["moderator"]);
$smarty->assign('section',$info["section"]);
$smarty->assign('topicsPerPage',$info["topicsPerPage"]);
$smarty->assign('mail',$info["mail"]);
$smarty->assign('useMail',$info["useMail"]);
$smarty->assign('topics_list_replies',$info['topics_list_replies']);
$smarty->assign('show_description',$info['show_description']);

$smarty->assign('outbound_address',$info['outbound_address']);
$smarty->assign('inbound_address',$info['inbound_address']);
$smarty->assign('topic_smileys',$info['topic_smileys']);
$smarty->assign('topic_summary',$info['topic_summary']);
$smarty->assign('ui_avatar',$info['ui_avatar']);
$smarty->assign('ui_flag',$info['ui_flag']);
$smarty->assign('ui_posts',$info['ui_posts']);
$smarty->assign('ui_email',$info['ui_email']);
$smarty->assign('ui_online',$info['ui_online']);
$smarty->assign('approval_type',$info['approval_type']);
$smarty->assign('moderator_group',$info['moderator_group']);

$smarty->assign('topics_list_reads',$info['topics_list_reads']);
$smarty->assign('topics_list_pts',$info['topics_list_pts']);
$smarty->assign('topics_list_lastpost',$info['topics_list_lastpost']);
$smarty->assign('topics_list_author',$info['topics_list_author']);
$smarty->assign('vote_threads',$info['vote_threads']);
$smarty->assign('usePruneUnreplied',$info["usePruneUnreplied"]);
$smarty->assign('pruneUnrepliedAge',$info["pruneUnrepliedAge"]);
$smarty->assign('usePruneOld',$info["usePruneOld"]);
$smarty->assign('pruneMaxAge',$info["pruneMaxAge"]);

$users = $userlib->get_users(0,-1,'login_desc', '');
$smarty->assign_by_ref('users',$users["data"]);

if(isset($_REQUEST["remove"])) {
  $commentslib->remove_forum($_REQUEST["remove"]);
}

if(isset($_REQUEST["save"])) {
  if(isset($_REQUEST["controlFlood"])&&$_REQUEST["controlFlood"]=='on') {
    $controlFlood='y';
  } else {
    $controlFlood='n';
  }
  if(isset($_REQUEST["useMail"])&&$_REQUEST["useMail"]=='on') {
    $useMail='y';
  } else {
    $useMail='n';
  }
  if(isset($_REQUEST["usePruneUnreplied"])&&$_REQUEST["usePruneUnreplied"]=='on') {
    $usePruneUnreplied='y';
  } else {
    $usePruneUnreplied='n';
  }
  if(isset($_REQUEST["usePruneOld"])&&$_REQUEST["usePruneOld"]=='on') {
    $usePruneOld='y';
  } else {
    $usePruneOld='n';
  }
  $_REQUEST['vote_threads']=isset($_REQUEST['vote_threads'])?'y':'n';	
  $_REQUEST['topics_list_reads']=isset($_REQUEST['topics_list_reads'])?'y':'n';
  $_REQUEST['topics_list_replies']=isset($_REQUEST['topics_list_replies'])?'y':'n';
  $_REQUEST['show_description']=isset($_REQUEST['show_description'])?'y':'n';
  
  $_REQUEST['topic_summary']=isset($_REQUEST['topic_summary'])?'y':'n';
  $_REQUEST['topic_smileys']=isset($_REQUEST['topic_smileys'])?'y':'n';
  $_REQUEST['ui_avatar']=isset($_REQUEST['ui_avatar'])?'y':'n';
  $_REQUEST['ui_flag']=isset($_REQUEST['ui_flag'])?'y':'n';
  $_REQUEST['ui_email']=isset($_REQUEST['ui_email'])?'y':'n';
  $_REQUEST['ui_posts']=isset($_REQUEST['ui_posts'])?'y':'n';
  $_REQUEST['ui_online']=isset($_REQUEST['ui_online'])?'y':'n';
  
  $_REQUEST['topics_list_pts']=isset($_REQUEST['topics_list_pts'])?'y':'n';
  $_REQUEST['topics_list_lastpost']=isset($_REQUEST['topics_list_lastpost'])?'y':'n';
  $_REQUEST['topics_list_author']=isset($_REQUEST['topics_list_author'])?'y':'n';

  if($_REQUEST["section"]=='__new__') $_REQUEST["section"]=$_REQUEST["new_section"];
  
  $fid = $commentslib->replace_forum($_REQUEST["forumId"], $_REQUEST["name"], $_REQUEST["description"], $controlFlood,$_REQUEST["floodInterval"],$_REQUEST["moderator"], $_REQUEST["mail"], $useMail, $usePruneUnreplied, $_REQUEST["pruneUnrepliedAge"], $usePruneOld, $_REQUEST["pruneMaxAge"], $_REQUEST["topicsPerPage"], $_REQUEST["topicOrdering"], $_REQUEST["threadOrdering"], $_REQUEST["section"],$_REQUEST['topics_list_replies'],$_REQUEST['topics_list_reads'],$_REQUEST['topics_list_pts'],$_REQUEST['topics_list_lastpost'],$_REQUEST['topics_list_author'],$_REQUEST['vote_threads'],$_REQUEST['show_description'],
  $_REQUEST['inbound_address'],$_REQUEST['outbound_address'],$_REQUEST['topic_smileys'],$_REQUEST['topic_summary'],$_REQUEST['ui_avatar'],$_REQUEST['ui_flag'],$_REQUEST['ui_posts'],$_REQUEST['ui_email'],$_REQUEST['ui_online'],$_REQUEST['approval_type'],$_REQUEST['moderator_group']);                         
  
  $cat_type='forum';
  $cat_objid = $fid;
  $cat_desc = substr($_REQUEST["description"],0,200);
  $cat_name = $_REQUEST["name"];
  $cat_href="tiki-view_forum.php?forumId=".$cat_objid;
  include_once("categorize.php");
  
  $info["name"]='';
  $info["description"]='';
  $info["controlFlood"]='n';
  $info["floodInterval"]=120;
  $info["moderator"]='admin';
  $info["topicOrdering"]='commentDate_desc';
  $info["threadOrdering"]='commentDate_desc';
  $info["mail"]='';
  $info["topicsPerPage"]='20';
  $info["useMail"]='n';
  $info["usePruneUnreplied"]='n';
  $info["pruneUnrepliedAge"]=60*60*24*30;
  $info["usePruneOld"]='n';
  $info["pruneMaxAge"]= 60*60*24*30;
  $info["forumId"] = 0;
  $info["topics_list_replies"]='y';
  $info["show_description"]='n';
  $info["outbound_address"]='';
  $info["inbound_address"]='';
  $info["topic_summary"]='n';
  $info["topic_smileys"]='n';
  $info["ui_avatar"]='y';
  $info["ui_flag"]='y';
  $info["ui_posts"]='n';
  $info["ui_email"]='n';
  $info["ui_online"]='n';
  $info["approval_type"]='all_posted';
  $info["moderator_group"]='';

  $info["topics_list_reads"]='y';
  $info["topics_list_pts"]='y';
  $info["topics_list_lastpost"]='y';
  $info["topics_list_author"]='y';        
  $info["vote_threads"]='y';        
  $smarty->assign('forumId',$info["forumId"]);
  $smarty->assign('name',$info["name"]);
  $smarty->assign('description',$info["description"]);
  $smarty->assign('controlFlood',$info["controlFlood"]);
  $smarty->assign('floodInterval',$info["floodInterval"]);
  $smarty->assign('moderator',$info["moderator"]);
  $smarty->assign('topicsPerPage',$info["topicsPerPage"]);
  $smarty->assign('mail',$info["mail"]);
  $smarty->assign('useMail',$info["useMail"]);
  $smarty->assign('topics_list_replies',$info['topics_list_replies']);
  $smarty->assign('show_description',$info['show_description']);
  $smarty->assign('topics_list_reads',$info['topics_list_reads']);
  $smarty->assign('topics_list_pts',$info['topics_list_pts']);
  $smarty->assign('topics_list_lastpost',$info['topics_list_lastpost']);
  $smarty->assign('topics_list_author',$info['topics_list_author']);
  $smarty->assign('vote_threads',$info['vote_threads']);
  $smarty->assign('usePruneUnreplied',$info["usePruneUnreplied"]);
  $smarty->assign('pruneUnrepliedAge',$info["pruneUnrepliedAge"]);
  $smarty->assign('usePruneOld',$info["usePruneOld"]);
  $smarty->assign('topicOrdering',$info["topicOrdering"]);
  $smarty->assign('threadOrdering',$info["threadOrdering"]);
  $smarty->assign('pruneMaxAge',$info["pruneMaxAge"]);
  $smarty->assign('outbound_address',$info['outbound_address']);
  $smarty->assign('inbound_address',$info['inbound_address']);
  $smarty->assign('topic_smileys',$info['topic_smileys']);
  $smarty->assign('topic_summary',$info['topic_summary']);
  $smarty->assign('ui_avatar',$info['ui_avatar']);
  $smarty->assign('ui_flag',$info['ui_flag']);
  $smarty->assign('ui_posts',$info['ui_posts']);
  $smarty->assign('ui_email',$info['ui_email']);
  $smarty->assign('ui_online',$info['ui_online']);
  $smarty->assign('approval_type',$info['approval_type']);  
  $smarty->assign('moderator_group',$info['moderator_group']);


}

if(!isset($_REQUEST["sort_mode"])) {
  $sort_mode = 'name_desc'; 
} else {
  $sort_mode = $_REQUEST["sort_mode"];
} 

if(!isset($_REQUEST["offset"])) {
  $offset = 0;
} else {
  $offset = $_REQUEST["offset"]; 
}
$smarty->assign_by_ref('offset',$offset);

if(isset($_REQUEST["find"])) {
  $find = $_REQUEST["find"];  
} else {
  $find = ''; 
}
$smarty->assign('find',$find);

$smarty->assign_by_ref('sort_mode',$sort_mode);
$channels = $commentslib->list_forums($offset,$maxRecords,$sort_mode,$find);
for($i=0;$i<count($channels["data"]);$i++) {
  if($userlib->object_has_one_permission($channels["data"][$i]["forumId"],'forum')) {
    $channels["data"][$i]["individual"]='y';
    
    if($userlib->object_has_permission($user,$channels["data"][$i]["forumId"],'forum','tiki_p_forum_read')) {
      $channels["data"][$i]["individual_tiki_p_forum_read"]='y';
    } else {
      $channels["data"][$i]["individual_tiki_p_forum_read"]='n';
    }
    if($userlib->object_has_permission($user,$channels["data"][$i]["forumId"],'forum','tiki_p_forum_post')) {
      $channels["data"][$i]["individual_tiki_p_forum_post"]='y';
    } else {
      $channels["data"][$i]["individual_tiki_p_forum_post"]='n';
    }
    if($userlib->object_has_permission($user,$channels["data"][$i]["forumId"],'forum','tiki_p_forum_vote')) {
      $channels["data"][$i]["individual_tiki_p_forum_vote"]='y';
    } else {
      $channels["data"][$i]["individual_tiki_p_forum_vote"]='n';
    }
    if($userlib->object_has_permission($user,$channels["data"][$i]["forumId"],'forum','tiki_p_forum_post_topic')) {
      $channels["data"][$i]["individual_tiki_p_forum_post_topic"]='y';
    } else {
      $channels["data"][$i]["individual_tiki_p_forum_post_topic"]='n';
    }
    if($tiki_p_admin=='y' || $userlib->object_has_permission($user,$channels["data"][$i]["forumId"],'forum','tiki_p_admin_forum')) {
      $channels["data"][$i]["individual_tiki_p_forum_post_topic"]='y';
      $channels["data"][$i]["individual_tiki_p_forum_vote"]='y';
      $channels["data"][$i]["individual_tiki_p_admin_forum"]='y';
      $channels["data"][$i]["individual_tiki_p_forum_post"]='y';
      $channels["data"][$i]["individual_tiki_p_forum_read"]='y';
    } 
    
  } else {
    $channels["data"][$i]["individual"]='n';
  }
}


$cant_pages = ceil($channels["cant"] / $maxRecords);
$smarty->assign_by_ref('cant_pages',$cant_pages);
$smarty->assign('actual_page',1+($offset/$maxRecords));
if($channels["cant"] > ($offset+$maxRecords)) {
  $smarty->assign('next_offset',$offset + $maxRecords);
} else {
  $smarty->assign('next_offset',-1); 
}
// If offset is > 0 then prev_offset
if($offset>0) {
  $smarty->assign('prev_offset',$offset - $maxRecords);  
} else {
  $smarty->assign('prev_offset',-1); 
}

$smarty->assign_by_ref('channels',$channels["data"]);

$groups = $userlib->get_groups(0,-1,'groupName_asc','');
$smarty->assign_by_ref('groups',$groups['data']);

$cat_type='forum';
$cat_objid = $_REQUEST["forumId"];
include_once("categorize_list.php");

$sections = $tikilib->get_forum_sections();
$smarty->assign_by_ref('sections',$sections);

// Display the template
$smarty->assign('mid','tiki-admin_forums.tpl');
$smarty->display("styles/$style_base/tiki.tpl");
?>