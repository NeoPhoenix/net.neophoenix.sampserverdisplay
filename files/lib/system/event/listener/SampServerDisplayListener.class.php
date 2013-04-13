<?php

require_once(WCF_DIR.'lib/system/event/EventListener.class.php');
require_once('lib/ssd/SampQueryValidation.class.php');
require_once('lib/ssd/SampQueryAPI.class.php');
require_once('lib/ssd/SampCache.class.php');

class SampServerDisplayListener implements EventListener 
{
	public function execute($eventObj, $className, $eventName) 
	{
		if(OFFLINE) return;
		if(!SAMPDISPLAY_FOOTER_ACTIVE) return;
		if(WCF::getUser()->banned) return;
		if(WCF::getUser()->getPermission("user.sampdisplay_footer.canView") != 1) return;		
		if(WCF::getRequest()->page != 'IndexPage' && SAMPDISPLAY_FOOTER_INDEX_EXCLUSIVE == 1) return;
	
		$wbb_full = 0;
		if(!strcmp(PACKAGE_NAME,'WoltLab Burning Board') && version_compare(PACKAGE_VERSION,'3.0.0') >= 0) $wbb_full = 1;
		
		$validation	= new SampQueryValidation(SAMPDISPLAY_SERVER_LIST);	
		if(!$validation->serverInfo['number']) return;

		$output = "";
		
		$user_permission = array(
			'can_view_page'		=> WCF::getUser()->getPermission("user.sampdisplay_page.canView")==1?1:0,
			'can_join_server'	=> WCF::getUser()->getPermission("user.sampdisplay_server.canJoin")==1?1:0,
			'can_view_users'	=> WCF::getUser()->getPermission("user.sampdisplay_page.canViewUsers")==1?1:0);
		
		$text_format = array();
		if(SAMPDISPLAY_WBBLITE_TEXT_ACTIVE)
		{
			$text_format['online'] 	= SAMPDISPLAY_WBBLITE_SERVER_TEXT;
			$text_format['offline']	= SAMPDISPLAY_WBBLITE_SERVER_TEXT_OFFLINE;
			$text_format['invalid'] = SAMPDISPLAY_WBBLITE_SERVER_TEXT_INVALID;
		}
		else
		{
			$text_format['online'] 	= WCF::getLanguage()->get("net.neophoenix.sampdisplay.footer_online");
			$text_format['offline']	= WCF::getLanguage()->get("net.neophoenix.sampdisplay.footer_offline");
			$text_format['invalid']	= WCF::getLanguage()->get("net.neophoenix.sampdisplay.footer_invalid");
		}
		
		
		for($loop=0; $loop<$validation->serverInfo['number']; $loop++)
		{
			if(SAMPDISPLAY_FOOTER_MULTIPLE_SERVER_NEW_LINE == 1 && $loop > 0) $tmp_output = "<br/>";
			else $tmp_output = "";
			
			if($validation->serverData[$loop]['valid'] != 1)
			{//invalid information given
				$tmp_output .= $text_format['invalid'];
				$tmp_output = str_replace("%server_id",$loop,$tmp_output);
				if($loop == 0) $output = $tmp_output;
				else $output .= $tmp_output;
				continue;
			}
			
			$server_ip   = $validation->serverData[$loop]['ip'];
			$server_port = $validation->serverData[$loop]['port'];
			
			@$samp_cache  = new SampCache($server_ip,$server_port);
			if(@$samp_cache->server_data['online'])
			{
				$tmp_output .= $text_format['online'];
				$tmp_output = str_replace("%server_ip",		$server_ip,											$tmp_output);
				$tmp_output = str_replace("%server_port",	intval($server_port),								$tmp_output);
				$tmp_output = str_replace("%weather",		intval(@$samp_cache->server_data['weather']),		$tmp_output);
				$tmp_output = str_replace("%version",		htmlentities(@$samp_cache->server_data['version']),	$tmp_output);
				$tmp_output = str_replace("%user_online",	@$samp_cache->server_data['players']==0?WCF::getLanguage()->get("net.neophoenix.sampdisplay.footer_no_players"):@$samp_cache->server_data['players'],$tmp_output);
				$tmp_output = str_replace("%user_max",		intval(@$samp_cache->server_data['maxplayers']),	$tmp_output);
				$tmp_output = str_replace("%gravity",		@$samp_cache->server_data['gravity'],				$tmp_output);
				$tmp_output = str_replace("%weburl",		@$samp_cache->server_data['weburl'],				$tmp_output);
				$tmp_output = str_replace("%worldtime"	,	@$samp_cache->server_data['worldtime'],				$tmp_output);
				$tmp_output = str_replace("%hostname",		htmlentities(@$samp_cache->server_data['hostname']),$tmp_output);
				$tmp_output = str_replace("%gamemode",		htmlentities(@$samp_cache->server_data['gamemode']),$tmp_output);
				$tmp_output = str_replace("%mapname",		htmlentities(@$samp_cache->server_data['mapname']),	$tmp_output);
				
				if(SAMPDISPLAY_FOOTER_USER_LIMIT >= 1 && SAMPDISPLAY_FOOTER_USERS == 1 && @$samp_cache->server_data['players'] >= 1 && $user_permission['can_view_users'])
				{
					$tmp_output = str_replace("(USER)","",$tmp_output);
					$tmp_output = str_replace("(/USER)","",$tmp_output);
				}
				else
				{
					$tmp_output = str_replace("(USER)","<!--",$tmp_output);
					$tmp_output = str_replace("(/USER)","-->",$tmp_output);
				}
				if(SAMPDISPLAY_PAGE_ACTIVE && $user_permission['can_view_page'])
				{
					$tmp_output = str_replace("(MORE)","<a href='index.php?page=Sampserverdisplay&serverID=".$loop."'>",$tmp_output);
					$tmp_output = str_replace("(/MORE)","</a>",$tmp_output);
				}
				else
				{
					$tmp_output = str_replace("(MORE)","",$tmp_output);
					$tmp_output = str_replace("(/MORE)","",$tmp_output);
				}				
				if($user_permission['can_join_server'])
				{
					$tmp_output = str_replace("(JOIN)","<a href='samp://".$server_ip.":".$server_port."'>",$tmp_output);
					$tmp_output = str_replace("(/JOIN)","</a>",$tmp_output);
				}
				else
				{
					$tmp_output = str_replace("(JOIN)","",$tmp_output);
					$tmp_output = str_replace("(/JOIN)","",$tmp_output);
				}
				
				if(SAMPDISPLAY_FOOTER_USERS == 1 && SAMPDISPLAY_FOOTER_USER_LIMIT >= 1 && $user_permission['can_view_users'])
				{
					$user_list = "";
					if(@$samp_cache->server_data['players'] == 0) $user_list = WCF::getLanguage()->get("net.neophoenix.sampdisplay.footer_no_players");
					else if(@$samp_cache->server_data['players'] >= 100 && @$samp_cache->server_data['players'] <= 0) $user_list = WCF::getLanguage()->get("net.neophoenix.sampdisplay.footer_too_much_players");
					else
					{
						for($id=0; $id<@$samp_cache->server_data['players']; $id++)
						{
							if($id > 0) $user_list.= ", ";
							if($id >= SAMPDISPLAY_FOOTER_USER_LIMIT)
							{
								$user_list .= WCF::getLanguage()->get("net.neophoenix.sampdisplay.footer_more_players");
								break;
							}
							$formated_user = @$samp_cache->user_data[$id]['nickname'];
							if(SAMPDISPLAY_GENERAL_CONNECT_USERS)
							{
								if(SAMPDISPLAY_GENERAL_CONNECT_USERS_TYPE == 0) $user = new User(null,null,$formated_user);//Connect by Name
								else $user = new User(@$samp_cache->user_data[$id]['score']);
								if($user->userID != NULL)
								{//Fund!
									if($wbb_full)	$uname = UsersOnline::getFormattedUsername(null,$user);
									else			$uname = $user->username;
									$formated_user = "<a href='index.php?page=User&userID=".$user->userID."'>".htmlentities($formated_user)."</a>";
								}
							}
							$user_list .= $formated_user;
						}
					}
					$tmp_output = str_replace("%user_list",$user_list,$tmp_output);
				}
			}
			else
			{
				$tmp_output .= $text_format['offline'];
				$tmp_output = str_replace("%server_ip",$server_ip,$tmp_output);
				$tmp_output = str_replace("%server_port",$server_port,$tmp_output);					
			}
			if($loop == 0)	$output = $tmp_output;
			else			$output .= $tmp_output;			
		}
		
		WCF::getTPL()->assign('SampServerContent', $output);
		WCF::getTPL()->append('additionalBoxes', WCF::getTPL()->fetch('sampserver'));
	}
}
?>