<?php
require_once(WCF_DIR.'lib/page/AbstractPage.class.php');
require_once('lib/ssd/SampCache.class.php');
require_once('lib/ssd/SampQueryValidation.class.php');
require_once(WCF_DIR.'lib/data/user/usersOnline/UsersOnline.class.php');
if(SAMPDISPLAY_GENERAL_RCON_ACTIVATE == 1) require_once(WBB_DIR.'lib/ssd/SampRconAPI.class.php');

class SampserverdisplayPage extends AbstractPage 
{
   	public $templateName 			= 'Sampserverdisplay';
	public $page_optional_content 	= "";
	public $page_content 			= "";
	public $serverID				= 0;
	public $tabID					= 0;
	public $subtabID				= 0;
	public $isOnline				= false;
	public $messages 				= array();
	public $tpl_User				= array();
	public $modHistory				= array();
	public $rconActive				= false;
	public $countUsers				= 0;
	public $user_permission			= array();
	public $groupMarking			= array();
	
	public function readParameters() 
	{
		parent::readParameters();
		WCF::getCache()->addResource('groups', WCF_DIR.'cache/cache.groups.php', WCF_DIR.'lib/system/cache/CacheBuilderGroups.class.php');
		$groups = WCF::getCache()->get('groups','groups');
		foreach($groups as $group) $this->groupMarking[$group['groupID']] = $group['userOnlineMarking'];
		
		if(!SAMPDISPLAY_PAGE_ACTIVE) return;
		$this->user_permission = array(
			'can_view_page'			=> WCF::getUser()->getPermission("user.sampdisplay_page.canView")			==1?1:0,
			'can_join_server'		=> WCF::getUser()->getPermission("user.sampdisplay_server.canJoin")			==1?1:0,
			'can_view_users'		=> WCF::getUser()->getPermission("user.sampdisplay_page.canViewUsers")		==1?1:0,
			'can_kick'				=> WCF::getUser()->getPermission("user.sampdisplay_server.canKick")			==1?1:0,
			'can_ban'				=> WCF::getUser()->getPermission("user.sampdisplay_server.canBan")			==1?1:0,
			'can_see_moderation'	=> WCF::getUser()->getPermission("user.sampdisplay_moderation.canView")		==1?1:0,
			'can_delete_moderation'	=> WCF::getUser()->getPermission("user.sampdisplay_moderation.canDelete")	==1?1:0,
			'can_access_page'		=> 1);
		if(!$this->user_permission['can_view_page']) return;
		$basic = new SampQueryValidation(SAMPDISPLAY_SERVER_LIST);
		if(!isset($_GET['serverID'])) $this->serverID = 0;
		else $this->serverID = intval($_GET['serverID']);
		if($this->serverID < 0) $this->serverID = 0;
		if($this->serverID >= $basic->serverInfo['number']) $this->serverID = ($basic->serverInfo['number']-1);
		if(SAMPDISPLAY_GENERAL_RCON_ACTIVATE && strlen($basic->serverData[$this->serverID]['rcon']) >= 1) $this->rconActive = true;

		if(!isset($_GET['tab'])) $this->tabID = 0;
		else
		{
			if($_GET['tab'] == 1)
			{
				$this->tabID = 1;
				if($this->rconActive)
				{
					if(!isset($_GET['subtab']))		$this->subtabID = 0;
					else if($_GET['subtab'] == 1)	$this->subtabID = 1;
				}
			}
			else $this->tabID = 0;
		}
		
		if(!$this->user_permission['can_view_page']) $this->user_permission['can_access_page'] = 0;
		else
		{
			if($this->tabID == 1 && $this->subtabID == 0 && !$this->user_permission['can_view_users']) $this->user_permission['can_access_page'] = 0;
			else if($this->tabID == 1 && $this->subtabID == 1 && !$this->user_permission['can_see_moderation']) $this->user_permission['can_access_page'] = 0;
		}
		
		if($this->user_permission['can_access_page'])
		{
			if($basic->serverData[$this->serverID]['valid'] == 1)
			{
				$this->legende = UsersOnline::getUsersOnlineMarkings();
				@$samp_cache = new SampCache($basic->serverData[$this->serverID]['ip'],intval($basic->serverData[$this->serverID]['port']));
				if(@$samp_cache->server_data['cache_age'] >= 1 && @$samp_cache->server_data['use_fs'] < 1)
				{
					$msg = WCF::getLanguage()->get("net.neophoenix.sampdisplay.page_cache_age");
					$msg = str_replace("%ageS",@$samp_cache->server_data['cache_age'],$msg);				
					$this->ThrowMessage($msg,"warning");
				}
				$this->countUsers = $samp_cache->server_data['players'];
				if(@$samp_cache->server_data['online'])
				{
					$this->isOnline = true;
					$msg = WCF::getLanguage()->get("net.neophoenix.sampdisplay.page_online");
					$msg = str_replace("%server_ip",$basic->serverData[$this->serverID]['ip'],$msg);
					$msg = str_replace("%server_port",$basic->serverData[$this->serverID]['port'],$msg);				
					$this->ThrowMessage($msg,"success");
					
					if($this->tabID == 1)
					{		
						if($this->subtabID == 1)
						{//Moderationslog
							if($this->user_permission['can_delete_moderation'] && isset($_GET['tool']) && isset($_GET['value']))
							{
								$tool	= intval($_GET['tool']);
								$entry	= intval($_GET['value']);
								if($tool == 1 && $entry >= 1)
								{
									$sql = "SELECT COUNT(*) FROM wcf".WCF_N."_ssd_moderation_history WHERE serverIP='".$basic->serverData[$this->serverID]['ip']."' AND serverPort='".$basic->serverData[$this->serverID]['port']."' AND entryID='".$entry."'";
									$result = mysql_query($sql);
									$row = mysql_fetch_row($result);
									if($row[0] == 1)
									{
										$sql = "DELETE FROM wcf".WCF_N."_ssd_moderation_history WHERE serverIP='".$basic->serverData[$this->serverID]['ip']."' AND serverPort='".$basic->serverData[$this->serverID]['port']."' AND entryID='".$entry."'";
										$result = mysql_query($sql);
										$tpl = WCF::getLanguage()->get('net.neophoenix.sampdisplay.page_content_moderation_entry_deleted');
										$tpl = str_replace('%entry_id',$entry,$tpl);
										$this->ThrowMessage($tpl,'success');
									}
								}
							}
							$sql = "SELECT COUNT(*) FROM wcf".WCF_N."_ssd_moderation_history WHERE serverIP='".$basic->serverData[$this->serverID]['ip']."' AND serverPort='".$basic->serverData[$this->serverID]['port']."'";
							$result = mysql_query($sql);
							$row = mysql_fetch_row($result);
							if($row[0] == 0) $this->ThrowMessage(WCF::getLanguage()->get("net.neophoenix.sampdisplay.page_content_moderation_no_entries"),"error");
							else $this->ThrowMessage(str_replace("%countEntries",$row[0],WCF::getLanguage()->get("net.neophoenix.sampdisplay.page_content_moderation_count_entries")),"success");
							
							$sql = "SELECT * FROM wcf".WCF_N."_ssd_moderation_history WHERE serverIP='".$basic->serverData[$this->serverID]['ip']."' AND serverPort='".$basic->serverData[$this->serverID]['port']."' ORDER BY timestamp DESC";
							if(SAMPDISPLAY_MODERATION_LIMIT >= 1) $sql .= " LIMIT ".SAMPDISPLAY_MODERATION_LIMIT;
							$result = mysql_query($sql);
							
							$kick_tpl	= WCF::getLanguage()->get("net.neophoenix.sampdisplay.page_content_moderation_entry_kick");
							$ban_tpl	= WCF::getLanguage()->get("net.neophoenix.sampdisplay.page_content_moderation_entry_ban");
							while($row = mysql_fetch_array($result))
							{
								$raw_tpl = $row['rconTool']==1?$ban_tpl:$kick_tpl;							
								$raw_tpl = str_replace("%modName",$row['modName'],$raw_tpl);
								$raw_tpl = str_replace("%targetName",$row['targetName'],$raw_tpl);							
								$raw_tpl = str_replace("%entryDateDay",date("j",$row['timestamp']),$raw_tpl);
								$raw_tpl = str_replace("%entryDateMonth",date("m",$row['timestamp']),$raw_tpl);
								$raw_tpl = str_replace("%entryDateYear",date("Y",$row['timestamp']),$raw_tpl);
								$raw_tpl = str_replace("%entryDateHour",date("G",$row['timestamp']),$raw_tpl);
								$raw_tpl = str_replace("%entryDateMinutes",date("i",$row['timestamp']),$raw_tpl);
								$this->modHistory[] = array($row['entryID'],$raw_tpl);
							}
						}
						else
						{//Userliste
							if($this->rconActive == true)
							{
								if(isset($_GET['rconTool']) && isset($_GET['rconValue']) && isset($basic->serverData[$this->serverID]['rcon']) && ($this->user_permission['can_kick'] || $this->user_permission['can_ban']))
								{
									$rcon = new SampRconAPI($basic->serverData[$this->serverID]['ip'],$basic->serverData[$this->serverID]['port'],$basic->serverData[$this->serverID]['rcon']);
									if(!$rcon->isOnline() || !$rcon->rconAvailable())
									{
										$msg = WCF::getLanguage()->get("net.neophoenix.sampdisplay.page_rcon_not_available");
										$this->ThrowMessage($msg,"warning");
									}
									else
									{
										$query = new SampQueryAPI($basic->serverData[$this->serverID]['ip'],$basic->serverData[$this->serverID]['port']);
										$plist = $query->getBasicPlayers();
										$rconInfo = array(
											'Tool'		=> intval($_GET['rconTool']),
											'Value'		=> intval($_GET['rconValue']),
											'targetName'=> $_GET['targetName']);
										$offset = -1;
										for($i=0; $i<$samp_cache->server_data['players']; $i++)
										{
											if($rconInfo['Value'] == $samp_cache->user_data[$i]['playerid'])
											{
												if($samp_cache->user_data[$i]['nickname'] == $rconInfo['targetName']) $offset = $i;
												break;
											}
										}
										if($offset >= 0)
										{
											if($rconInfo['Tool'] == 0)
											{
												if($this->user_permission['can_kick'])
												{
													$rcon->packetSend("kick ".$rconInfo['Value'],2.0);
													$msg = WCF::getLanguage()->get('net.neophoenix.sampdisplay.page_kick_message');
													$msg = str_replace('%user_name',$rconInfo['targetName'],$msg);
													$msg = str_replace('%user_id',$rconInfo['Value'],$msg);
													$this->ThrowMessage($msg,"warning");												
													$samp_cache->RemoveUser($offset);
													$modName = WCF::getUser()->username;
													if(!strlen($modName)) $modName = WCF::getLanguage()->get('net.neophoenix.sampdisplay.page_rcon_guest');
													$sql= "INSERT INTO wcf".WCF_N."_ssd_moderation_history (serverIP,serverPort,modName,targetName,rconTool,timestamp) VALUES ('".$basic->serverData[$this->serverID]['ip']."','".$basic->serverData[$this->serverID]['port']."','".mysql_real_escape_string(htmlentities($modName))."','".mysql_real_escape_string(htmlentities($rconInfo['targetName']))."','0','".time()."')";
													mysql_query($sql);
												}
											}
											else if($rconInfo['Tool'] == 1)
											{
												if($this->user_permission['can_ban'])
												{
													$rcon->packetSend("ban ".$rconInfo['Value'],2.0);
													$msg = WCF::getLanguage()->get('net.neophoenix.sampdisplay.page_ban_message');
													$msg = str_replace('%user_name',$rconInfo['targetName'],$msg);
													$msg = str_replace('%user_id',$rconInfo['Value'],$msg);
													$samp_cache->RemoveUser($offset);
													$modName = WCF::getUser()->username;
													if(!strlen($modName)) $modName = WCF::getLanguage()->get('net.neophoenix.sampdisplay.page_rcon_guest');
													$sql= "INSERT INTO wcf".WCF_N."_ssd_moderation_history (serverIP,serverPort,modName,targetName,rconTool,timestamp) VALUES ('".$basic->serverData[$this->serverID]['ip']."','".$basic->serverData[$this->serverID]['port']."','".mysql_real_escape_string(htmlentities($modName))."','".mysql_real_escape_string(htmlentities($rconInfo['targetName']))."','0','".time()."')";
													mysql_query($sql);
												}
											}
										}
									}
								}
							}
							if($this->countUsers >= 1)
							{
								for($id=0; $id<$this->countUsers; $id++)
								{
									$userData = array('ID'=>0,'Avatar'=>'','FormatedName'=>@$samp_cache->user_data[$id]['nickname']);
									if(SAMPDISPLAY_GENERAL_CONNECT_USERS)
									{
										if(SAMPDISPLAY_GENERAL_CONNECT_USERS_TYPE == 0) $user = new User(null,null,@$samp_cache->user_data[$id]['nickname']);//Connect by Name
										else $user = new User(@$samp_cache->user_data[$id]['score']);
										if($user->userID != NULL)
										{//Fund!
											$userData['ID'] = $user->userID;//@$samp_cache->user_data[$id]['nickname']
											
											$sql = "SELECT userOnlineGroupID,username FROM wcf".WCF_N."_user WHERE userID='".$user->userID."'";
											$result = mysql_query($sql);
											$row = mysql_fetch_array($result);
											$userData['FormatedName'] = sprintf($this->groupMarking[$row['userOnlineGroupID']],StringUtil::encodeHTML($row['username']));
											$avatar = $user->avatarID;
											if($avatar != 0)
											{
												$avatar = new Avatar($avatar);
												$avatar->setMaxHeight(50);
												$userData['Avatar'] = $avatar->__toString();
											}
										}
									}
									$this->tpl_User[] = array(
										$id&1?2:1,
										@$samp_cache->user_data[$id]['playerid'],
										htmlentities(@$samp_cache->user_data[$id]['nickname']),
										$userData['Avatar'],
										gmdate("H:i:s",@$samp_cache->user_data[$id]['onlinezeit']),
										@$samp_cache->user_data[$id]['score'],
										@$samp_cache->user_data[$id]['ping'],
										$userData['ID'],
										$userData['FormatedName']);
								}
							}
							else $this->ThrowMessage(WCF::getLanguage()->get('net.neophoenix.sampdisplay.page_content_no_players'),'error');
						}
					}
					else
					{
						WCF::getTPL()->assign(array(
							'server_mapname'		=> @$samp_cache->server_data['mapname'],
							'server_hostname'		=> @$samp_cache->server_data['hostname'],
							'server_gamemode'		=> @$samp_cache->server_data['gamemode'],
							'server_weburl'			=> @$samp_cache->server_data['weburl'],
							'server_worldtime'		=> @$samp_cache->server_data['worldtime'],
							'server_weather'		=> @$samp_cache->server_data['weather'],
							'server_version'		=> @$samp_cache->server_data['version'],
							'server_players'		=> @$samp_cache->server_data['players'],
							'server_maxplayers'		=> @$samp_cache->server_data['maxplayers']
						));
					}
				}
				else
				{
					$this->isOnline = false;
					$msg = WCF::getLanguage()->get("net.neophoenix.sampdisplay.page_offline");
					$msg = str_replace("%server_ip",$basic->serverData[$this->serverID]['ip'],$msg);
					$msg = str_replace("%server_port",$basic->serverData[$this->serverID]['port'],$msg);				
					$this->ThrowMessage($msg,"error");
				}
			}
			else
			{
				$tpl = WCF::getLanguage()->get("net.neophoenix.sampdisplay.page_invalid");
				$tpl = str_replace('%server_id',$this->serverID,$tpl);
				$this->ThrowMessage($tpl,'warning');
			}
		}
	}
	
	public function ThrowMessage($text,$type)
	{
		$this->messages[] = "<p class='".$type."' style='margin-top:4px;margin-bottom:1px'>".$text."</p>";
		return;
	}
	
	public function assignVariables() 
	{
		parent::assignVariables();
		$this->page_optional_content = "";
		for($mID=0; $mID<sizeof($this->messages); $mID++) $this->page_optional_content .= $this->messages[$mID];
		
		WCF::getCache()->addResource('groups', WCF_DIR.'cache/cache.groups.php', WCF_DIR.'lib/system/cache/CacheBuilderGroups.class.php');
		$groups = WCF::getCache()->get('groups','groups');
		$legende_str = "";
		$first = true;
		foreach ($groups as $group)
		{
			if($first) $first = false;
			else $legende_str .= ", ";
			$legende_str .= sprintf($group['userOnlineMarking'],StringUtil::encodeHTML(WCF::getLanguage()->get($group['groupName'])));
		}
			
		WCF::getTPL()->assign(array(
			'legende' 				=> $legende_str,
			'page_optional_content'	=> $this->page_optional_content,
			'page_content'			=> $this->page_content,
			'serverID'				=> $this->serverID,
			'tabID'					=> $this->tabID,
			'subtabID'				=> $this->subtabID,
			'isOnline'				=> $this->isOnline,
			'countUsers'			=> $this->countUsers,
			'users'					=> $this->tpl_User,
			'canSeeModeration'		=> $this->user_permission['can_see_moderation'],
			'canDeleteModeration'	=> $this->user_permission['can_delete_moderation'],
			'canSeeUsers'			=> $this->user_permission['can_view_users'],
			'canKick'				=> $this->user_permission['can_kick'],
			'canBan'				=> $this->user_permission['can_ban'],
			'canAccessPage'			=> $this->user_permission['can_access_page'],
			'rcon_active'			=> $this->rconActive,
			'activate_modhistory'	=> $this->rconActive==true?(SAMPDISPLAY_MODERATION_ACTIVE==1?1:0):0,
			'historyEntries'		=> $this->modHistory,
			'countHistoryEntries'	=> sizeof($this->modHistory)
		));
	}
	
	public function show() 
	{
		parent::show();	
	}
}

?>