<?php
/*
 *	@version 1.0.0 Dev 1
 *	@author NeoPhoenix <support@neophoenix.net>
 *	@copyright 2012; http://www.neophoenix.net/
 */
require_once('SampQueryAPI.class.php');

class SampCache
{
	public $query_api		= NULL;
	public $use_cache		= true;
	public $server_data		= array();
	public $data_cached		= false;
	public $user_data		= array(array());
	public $mysql_tmp		= array();
	
	public function __construct($serverIP,$serverPort=7777)
	{
		$this->server_data['serverIP']		= $serverIP;
		$this->server_data['serverPort']	= $serverPort;
		$timestamp = time();
		if(SAMPDISPLAY_GENERAL_CACHE <= 0)
		{
			$this->RetrieveInformation();
			return;
		}
		$sql = "SELECT * FROM wcf".WCF_N."_ssd_server WHERE serverIP='".$this->server_data['serverIP']."' AND serverPort='".$this->server_data['serverPort']."'";
		$result = mysql_query($sql);
		if(!mysql_num_rows($result))
		{
			$this->CreateCache();
			return;
		}
		$row = mysql_fetch_array($result,MYSQL_ASSOC);
		$cache_time = SAMPDISPLAY_GENERAL_CACHE;
		if(!$row['online']) $cache_time += SAMPDISPLAY_GENERAL_OFFLINE_CACHE;
		if($row['timestamp'] < ($timestamp-$cache_time))
		{
			$this->CreateCache();
			return;
		}
		$this->data_cached					= true;
		$this->server_data['use_fs']		= $row['use_fs'];
		$this->server_data['cache_age']		= ($timestamp-$row['timestamp']);
		$this->server_data['online']		= $row['online'];
		$this->server_data['updated']		= $row['timestamp'];
		$this->server_data['maxplayers']	= $row['maxplayers'];
		$this->server_data['gravity']		= $row['gravity'];
		$this->server_data['mapname']		= $row['mapname'];
		$this->server_data['version']		= $row['version'];
		$this->server_data['weather']		= $row['weather'];
		$this->server_data['weburl']		= $row['weburl'];
		$this->server_data['worldtime']		= $row['worldtime'];
		$this->server_data['hostname']		= $row['hostname'];
		$this->server_data['gamemode']		= $row['gamemode'];
		$this->server_data['players']		= 0;
		if($this->server_data['online'])
		{
			$sql = "SELECT * FROM wcf".WCF_N."_ssd_user_to_server WHERE serverIP='".$this->server_data['serverIP']."' AND serverPort='".$this->server_data['serverPort']."' ORDER BY userID ASC";
			$result = mysql_query($sql);
			if(mysql_num_rows($result))
			{
				$id = 0;
				while($row = mysql_fetch_array($result,MYSQL_ASSOC))
				{
					if(strlen($row['userName']) < 3) continue;
					$this->server_data['players']++;
					$this->user_data[$id]['playerid']	= $row['userID'];
					$this->user_data[$id]['nickname']	= $row['userName'];
					$this->user_data[$id]['score']		= $row['userScore'];
					$this->user_data[$id]['ping']		= $row['userPing'];
					$this->user_data[$id]['onlinezeit']	= ($timestamp-$row['timestamp']);
					$id++;
				}
			}
		}
	}
	
	public function RetrieveInformation()
	{
		if($this->data_cached == true) return;
		$this->data_cached = true;
		if($this->query_api == NULL) $this->query_api = new SampQueryAPI($this->server_data['serverIP'],$this->server_data['serverPort']);
		$this->server_data['cache_age']		= 0;
		$this->server_data['use_fs']		= 0;
		$this->server_data['online']		= $this->query_api->isOnline();
		$this->server_data['hostname']		= "SA:MP Server";
		$this->server_data['gamemode']		= "Unknown";
		$this->server_data['players']		= 0;
		$this->server_data['maxplayers']	= 500;
		$this->server_data['gravity']		= 0.0008;
		$this->server_data['mapname']		= "San Andreas";
		$this->server_data['version']		= "0.3e";
		$this->server_data['weather']		= 0;
		$this->server_data['weburl']		= "http://www.sa-mp.com";
		$this->server_data['worldtime']		= "12:00";
		$this->server_data['password']		= 0;
		if($this->server_data['online'])
		{
			$aInformation = $this->query_api->getInfo();
			$aServerRules = $this->query_api->getRules();
			$this->server_data['hostname']		= @$aInformation['hostname'];
			$this->server_data['gamemode']		= @$aInformation['gamemode'];
			$this->server_data['password']		= intval(@$aInformation['password']);
			$this->server_data['maxplayers']	= intval(@$aInformation['maxplayers']);
			$this->server_data['gravity']		= @$aServerRules['gravity'];
			$this->server_data['mapname']		= @$aServerRules['mapname'];
			$this->server_data['version']		= @$aServerRules['version'];
			$this->server_data['weather']		= intval(@$aServerRules['weather']);
			$this->server_data['weburl']		= @$aServerRules['weburl'];
			$this->server_data['worldtime']		= @$aServerRules['worldtime'];
			$this->server_data['players']		= 0;
			$users = $this->query_api->getDetailedPlayers();
			for($id=0; $id<sizeof($users); $id++)
			{
				if(strlen(@$users[$id]['nickname']) < 3) continue;
				$this->user_data[$id]['playerid']	= intval(@$users[$id]['playerid']);
				$this->user_data[$id]['nickname']	= @$users[$id]['nickname'];
				$this->user_data[$id]['score']		= intval(@$users[$id]['score']);
				$this->user_data[$id]['ping']		= intval(@$users[$id]['ping']);
				$this->user_data[$id]['onlinezeit']	= 0;
				$this->server_data['players']++;
			}
		}
	}
	
	public function CreateCache()
	{
		$this->RetrieveInformation();
		$real_timestamp = time();
		$this->server_data['cache_age']	= 0;
		$sql = "DELETE FROM wcf".WCF_N."_ssd_server WHERE serverIP='".@$this->server_data['serverIP']."' AND serverPort='".@$this->server_data['serverPort']."'";
		mysql_query($sql);
		$sql = "DELETE FROM wcf".WCF_N."_ssd_user_to_server WHERE serverIP='".@$this->server_data['serverIP']."' AND serverPort=".@$this->server_data['serverPort'];
		mysql_query($sql);
		$this->server_data['players'] = 0;
		for($id=0; $id<sizeof($this->user_data); $id++)
		{
			if(strlen(@$this->user_data[$id]['nickname']) < 3) continue;
			$this->server_data['players']++;
			$sql = "SELECT * FROM wcf".WCF_N."_ssd_user_to_server_tmp WHERE userID='".@$this->user_data[$id]['playerid']."' AND userName='".mysql_real_escape_string(@$this->user_data[$id]['nickname'])."' AND serverIP='".@$this->server_data['serverIP']."' AND serverPort='".@$this->server_data['serverPort']."'";
			$result = mysql_query($sql);
			$timestamp = $real_timestamp;
			if(mysql_num_rows($result) >= 1)
			{
				$row = mysql_fetch_array($result);
				$timestamp = $row['timestamp'];
				$this->user_data[$id]['onlinezeit'] = ($timestamp-$row['timestamp']);
			}
			$sql = "DELETE FROM wcf".WCF_N."_ssd_user_to_server_tmp WHERE userID='".@$this->user_data[$id]['playerid']."' AND userName='".mysql_real_escape_string(@$this->user_data[$id]['nickname'])."' AND serverIP='".@$this->server_data['serverIP']."' AND serverPort='".@$this->server_data['serverPort']."'";
			mysql_query($sql);
			$sql = "INSERT INTO wcf".WCF_N."_ssd_user_to_server (serverIP,serverPort,userID,userName,userScore,userPing,timestamp) VALUES ('".@$this->server_data['serverIP']."','".@$this->server_data['serverPort']."','".@$this->user_data[$id]['playerid']."','".mysql_real_escape_string(@$this->user_data[$id]['nickname'])."',".@$this->user_data[$id]['score'].",".@$this->user_data[$id]['ping'].",'".$timestamp."')";
			mysql_query($sql);
			$sql = "INSERT INTO wcf".WCF_N."_ssd_user_to_server_tmp (serverIP,serverPort,userID,userName,timestamp,updated) VALUES ('".@$this->server_data['serverIP']."','".@$this->server_data['serverPort']."','".@$this->user_data[$id]['playerid']."','".mysql_real_escape_string(@$this->user_data[$id]['nickname'])."','".$timestamp."','".$real_timestamp."')";
			mysql_query($sql);
		}
		$sql = "DELETE FROM wcf".WCF_N."_ssd_user_to_server_tmp WHERE serverIP='".@$this->server_data['serverIP']."' AND serverPort='".@$this->server_data['serverPort']."' AND updated<".($real_timestamp-10)."";
		mysql_query($sql);
		$sql = "INSERT INTO wcf".WCF_N."_ssd_server (serverIP,serverPort,timestamp,online,hostname,gamemode,password,players,maxplayers,gravity,mapname,version,weather,weburl,worldtime) VALUES ('".@$this->server_data['serverIP']."',".@$this->server_data['serverPort'].",".$real_timestamp.",".@$this->server_data['online'].",'".mysql_real_escape_string(@$this->server_data['hostname'])."','".mysql_real_escape_string(@$this->server_data['gamemode'])."',".@$this->server_data['password'].",".@$this->server_data['players'].",".@$this->server_data['maxplayers'].",".@$this->server_data['gravity'].",'".mysql_real_escape_string(@$this->server_data['mapname'])."','".mysql_real_escape_string(@$this->server_data['version'])."',".@$this->server_data['weather'].",'".mysql_real_escape_string(@$this->server_data['weburl'])."','".@$this->server_data['worldtime']."')";
		mysql_query($sql);
		$this->mysql_tmp[] = $sql;
	}
	
	public function RemoveUser($offset)
	{
		$playerID = $this->user_data[$offset]['playerid'];
		$sql = "DELETE FROM wcf".WCF_N."_ssd_user_to_server_tmp WHERE serverIP='".@$this->server_data['serverIP']."' AND serverPort='".@$this->server_data['serverPort']."' AND userID='".$playerID."'";
		mysql_query($sql);
		$sql = "DELETE FROM wcf".WCF_N."_ssd_user_to_server WHERE serverIP='".@$this->server_data['serverIP']."' AND serverPort='".@$this->server_data['serverPort']."' AND userID='".$playerID."'";
		mysql_query($sql);
		$sql = "UPDATE wcf".WCF_N."_ssd_server SET players=players-1 WHERE serverIP='".@$this->server_data['serverIP']."' AND serverPort='".@$this->server_data['serverPort']."'";
		mysql_query($sql);
		return true;
	}
}

?>