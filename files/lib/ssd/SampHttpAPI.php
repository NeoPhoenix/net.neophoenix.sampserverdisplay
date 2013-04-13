<?php
/*
 *	@version 1.0.0 Beta 1
 *	@author NeoPhoenix <support@neophoenix.net>
 *	@copyright 2012 <http://www.neophoenix.net>
 */

class INI
{
	public $sections = array();
	public function __construct()
	{
	}
	public function LoadFileLines($lines = array())
	{
		$this->sections		= array();
		$current_section	= "";
		foreach($lines as $line)
		{
			if($line[0] == ';') continue;
			if(strpos($line,'=') === false)
			{
				$section = substr($line,1,strlen($line)-2);
				$current_section = $section;
				if(!isset($this->sections[$section])) $this->sections[$section] = array();
			}
			else
			{
				$data = explode('=',$line,2);
				$this->SetValue($current_section,$data[0],$data[1]);
			}
		}
	}
	public function CreateSection($section)
	{
		if(!isset($this->sections[$section]))
		{
			$this->sections[$section] = array();
			return true;
		}
		return false;
	}
	public function GetValue($section,$key)
	{
		return $this->sections[$section][$key];
	}
	public function SetValue($section,$key,$value=NULL)
	{
		$this->sections[$section][$key] = $value;
		return true;
	}	
	public function GenerateFileLines()
	{
		$file = array();
		foreach($this->sections as $section => $data) 
		{
			$file[] = "[".$section."]";
			foreach($data as $key => $value)
			{
				$file[] = $key."=".$value;
			}
		}
		return $file;
	}
}

class Validation
{
	public $gIsValid = -1;
	public function isValid()
	{
		if($this->gIsValid != -1) return $this->gIsValid==1?true:false;
		$query_validation = new SampQueryValidation(SAMPDISPLAY_SERVER_LIST);
		foreach($query_validation->serverData as $server)
		{
			if($server['ip'] == $_SERVER['REMOTE_ADDR'] && (intval($server['port']) == intval($_POST['port'])))
			{
				$this->gIsValid = 1;
				return true;
			}
		}
		$this->gIsValid = 0;
		return false;
	}
}

class SSD_Update
{
	public function __construct()
	{
	}	
	public function Server($type)
	{
		if($type == 'add')
		{
			/*password	pw
			hostname	hn
			gamemode	gm
			players		p
			maxplayers	mp
			gravity		g
			mapname		mn
			version		v
			weather		w
			weburl		url
			worldtime	t*/	
			$this->Server('remove');
			$sql = "INSERT INTO wcf".WCF_N."_ssd_server (serverIP,serverPort,online,timestamp,use_fs,password,hostname,gamemode,players,maxplayers,gravity,mapname,version,weather,weburl,worldtime) VALUES ";
			$sql .= "('".$_SERVER['REMOTE_ADDR']."','".intval($_POST['port'])."','1','".time()."','2','".(intval($_POST['pw'])=='1'?1:0)."','".mysql_real_escape_string($_POST['hn'])."','".mysql_real_escape_string($_POST['gm'])."','".intval($_POST['p'])."'";
			$sql .= ",'".intval($_POST['mp'])."','".floatval($_POST['g'])."','".mysql_real_escape_string($_POST['mn'])."','".mysql_real_escape_string($_POST['v'])."','".intval($_POST['w'])."','".mysql_real_escape_string($_POST['url'])."','".mysql_real_escape_string($_POST['t'])."')";
			mysql_query($sql);
			return $this->Server('check');
		}
		else if($type == 'update')
		{
			if(!$this->Server('check')) return $this->Server('add');
			$sql = "UPDATE wcf".WCF_N."_ssd_server SET online='1',timestamp='".time()."',use_fs='2',password='".(intval($_POST['pw'])=='1'?1:0)."',hostname='".mysql_real_escape_string($_POST['hn'])."',gamemode='".mysql_real_escape_string($_POST['gm'])."',players='".intval($_POST['p'])."',maxplayers='".intval($_POST['mp'])."',gravity='".floatval($_POST['g'])."',mapname='".mysql_real_escape_string($_POST['mn'])."',version='".mysql_real_escape_string($_POST['v'])."',weather='".intval($_POST['w'])."',weburl='".mysql_real_escape_string($_POST['url'])."',worldtime='".mysql_real_escape_string($_POST['t'])."' WHERE serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'";
			mysql_query($sql);
			$sql = "UPDATE wcf".WCF_N."_ssd_user_to_server_tmp SET updated='".time()."' WHERE serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'";
			mysql_query($sql);
			return $this->Server('check');
		}
		else if($type == 'remove')
		{
			mysql_query("DELETE FROM wcf".WCF_N."_ssd_server WHERE serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'");
			mysql_query("DELETE FROM wcf".WCF_N."_ssd_user_to_server WHERE serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'");
			mysql_query("DELETE FROM wcf".WCF_N."_ssd_user_to_server_tmp WHERE serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'");
			return true;
		}
		else if($type == 'check')
		{
			$sql = "SELECT COUNT(*) FROM wcf".WCF_N."_ssd_server WHERE serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'";
			$result = mysql_query($sql);
			$row = mysql_fetch_array($result);
			return $row[0]?true:false;
		}
		return false;
	}	
	public function User($type,$accurate=true)
	{
		if($type == 'add')
		{
			$this->User('remove',false);
			$sql = "INSERT INTO wcf".WCF_N."_ssd_user_to_server (userID,userName,userScore,userPing,serverIP,serverPort,timestamp) VALUES ('".intval($_POST['playerid'])."','".mysql_real_escape_string($_POST['name'])."','".intval($_POST['score'])."','".intval($_POST['ping'])."','".$_SERVER['REMOTE_ADDR']."','".intval($_POST['port'])."','".time()."')";
			mysql_query($sql);
			$sql = "INSERT INTO wcf".WCF_N."_ssd_user_to_server_tmp (userID,userName,serverIP,serverPort,timestamp,updated) VALUES ('".intval($_POST['playerid'])."','".mysql_real_escape_string($_POST['name'])."','".$_SERVER['REMOTE_ADDR']."','".intval($_POST['port'])."','".time()."','".time()."')";
			mysql_query($sql);
			return $this->User('check');
		}
		else if($type == 'update')
		{
			if($this->User('check') == false) return $this->User('add');
			$sql = "UPDATE wcf".WCF_N."_ssd_user_to_server SET userName='".mysql_real_escape_string($_POST['name'])."',userScore='".intval($_POST['score'])."',userPing='".intval($_POST['ping'])."' WHERE userID='".intval($_POST['playerid'])."' AND serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'";
			mysql_query($sql);
			$sql = "SELECT COUNT(*) FROM wcf".WCF_N."_ssd_user_to_server_tmp WHERE userID='".intval($_POST['playerid'])."' AND serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'";
			$result = mysql_query($sql);
			$row = mysql_fetch_row($result);
			if($row[0])
			{
				$sql = "UPDATE wcf".WCF_N."_ssd_user_to_server_tmp SET updated='".time()."' WHERE userID='".intval($_POST['playerid'])."' AND userName='".mysql_real_escape_string($_POST['name'])."' AND serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'";
				mysql_query($sql);
			}
			else
			{
				$sql = "INSERT INTO wcf".WCF_N."_ssd_user_to_server_tmp (userID,userName,serverIP,serverPort,timestamp,updated) VALUES ('".intval($_POST['playerid'])."','".mysql_real_escape_string($_POST['name'])."','".$_SERVER['REMOTE_ADDR']."','".intval($_POST['port'])."','".time()."','".time()."')";
				mysql_query($sql);
			}
			return $this->User('check');
		}
		else if($type == 'remove')
		{
			mysql_query("DELETE FROM wcf".WCF_N."_ssd_user_to_server WHERE userID='".intval($_POST['playerid'])."' AND serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'");
			mysql_query("DELETE FROM wcf".WCF_N."_ssd_user_to_server_tmp WHERE userID='".intval($_POST['playerid'])."' AND serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'");
			if($accurate == true) return $this->User('check')==true?false:true;
			return true;
		}
		else if($type == 'check')
		{
			$result = mysql_query("SELECT * FROM wcf".WCF_N."_ssd_user_to_server WHERE userID='".intval($_POST['playerid'])."' AND serverIP='".$_SERVER['REMOTE_ADDR']."' AND serverPort='".intval($_POST['port'])."'");
			if(mysql_num_rows($result)) return true;
			return false;
		}
		return false;
	}
}


$ini = new INI();
$ini->CreateSection('General');
$ini->CreateSection('Sent');
$ini->CreateSection('Request');
$ini->SetValue('General','timestamp',time());
$ini->SetValue('General','remote_addr',$_SERVER['REMOTE_ADDR']);
$ini->SetValue('Sent','remote_port',$_POST['port']);

require_once('../../config.inc.php');
require_once(WBB_DIR.'options.inc.php');

if(strlen(SAMPDISPLAY_API_AUTH) >= 1 && strcmp(SAMPDISPLAY_API_AUTH,$_POST['auth']) != 0)
{
	$ini->CreateSection('Errors');
	$ini->SetValue('Errors','error_id',6);
	$ini->SetValue('Errors','error_description','invalid auth');
}
else if(!isset($_POST['tool']))
{
	$ini->CreateSection('Errors');
	$ini->SetValue('Errors','error_id',4);
	$ini->SetValue('Errors','error_description','no tool given');
}
else if(!isset($_POST['port']))
{
	$ini->CreateSection('Errors');
	$ini->SetValue('Errors','error_id',2);
	$ini->SetValue('Errors','error_description','no port given');
}
else if($_POST['port'] < 1 || $_POST['port'] > 65535)
{
	$ini->CreateSection('Errors');
	$ini->SetValue('Errors','error_id',3);
	$ini->SetValue('Errors','error_description','invalid port given');
}
else
{
	require_once(WBB_DIR.'/wcf/config.inc.php');
	require_once(WBB_DIR.'/lib/ssd/SampQueryValidation.class.php');

	$valid = new Validation();
	if($valid->isValid())
	{
		if(!strcmp($_POST['category'],'init'))
		{
			if(!strcmp($_POST['tool'],'basic'))
			{
				$ini->SetValue('Request','cache_time',SAMPDISPLAY_GENERAL_CACHE);
				$ini->SetValue('Request','host',$_SERVER['REMOTE_ADDR']);
				$ini->SetValue('Request','wcf_n',WCF_N);
			}
		}
		else
		{
			if(!SAMPDISPLAY_API_ACTIVE)
			{
				$ini->CreateSection('Errors');
				$ini->SetValue('Errors','error_id',5);
				$ini->SetValue('Errors','error_description','api not activated');
			}
			else
			{
				$ssd = new SSD_Update();
				mysql_connect($dbHost,$dbUser,$dbPassword);
				mysql_select_db($dbName);
				$ini->SetValue('Sent','category',$_POST['category']);
				$ini->SetValue('Sent','tool',$_POST['tool']);
				if(!strcmp($_POST['category'],'user'))
				{
					$result = $ssd->User($_POST['tool']);
					$ini->SetValue('Request','success',$result==true?true:false);
				}
				else if(!strcmp($_POST['category'],'server'))
				{
					$result = $ssd->Server($_POST['tool']);
					$ini->SetValue('Request','success',$result==true?true:false);
				}		
				else if(!strcmp($_POST['category'],'request'))
				{
					foreach($_POST as $fieldname => $fieldvalue)
					{
						$ini->SetValue('Request',$fieldname,$fieldvalue);
					}
				}
				else
				{
					$ini->CreateSection('Errors');
					$ini->SetValue('Errors','error_id',1);
					$ini->SetValue('Errors','error_description','invalid category given');
				}
				mysql_close();
			}
		}
	}
	else
	{
		$ini->CreateSection('Errors');
		$ini->SetValue('Errors','error_id',0);
		$ini->SetValue('Errors','error_description','server not whitelisted');
	}
}

foreach($ini->GenerateFileLines() as $line) echo $line."\r\n";
?>