<?php
/*
 *	@version 1.0.0 Beta 1
 *	@author NeoPhoenix <support@neophoenix.net>
 *	@copyright 2012 <http://www.neophoenix.net>
 */

class SampQueryValidation
{
	public $serverInfo		= array();
	public $serverData		= array(array());
	
	public function __construct($sInput)
	{
		$this->serverInfo['number'] = 0;
		$list = explode("\r\n",$sInput);
		for($loop=0; $loop<sizeof($list); $loop++)
		{
			if(strlen($list[$loop]) == 0) continue;
			$this->serverData[$this->serverInfo['number']]['valid'] = 1;
			$part = explode("|",$list[$loop]);
			$data = explode(":",$part[0]);
			$data[0] = gethostbyname($data[0]);
			if(!$this->validateIP($data[0])) $this->serverData[$this->serverInfo['number']]['valid'] = 0;//IP is not valid
			$port = 7777;//Standart SA:MP port
			if(sizeof($data) >= 2) $port = $data[1];//Port available
			if(!$this->validatePort($port)) $this->serverData[$this->serverInfo['number']]['valid'] = 0;//Port is not valid
			//Valid line
			$this->serverData[$this->serverInfo['number']]['ip'] 	= $data[0];
			$this->serverData[$this->serverInfo['number']]['port'] 	= $port;
			$this->serverData[$this->serverInfo['number']]['rcon'] 	= "";
			if(sizeof($part) >= 2) $this->serverData[$this->serverInfo['number']]['rcon'] = $part[1];//optional rcon login available			
			$this->serverInfo['number']++;
		}
	}
	
	public function validateIP($sServerIP)
	{
		if(empty($sServerIP)) return false;
		if(!filter_var($sServerIP,FILTER_VALIDATE_IP)) return false;
		return true;
	}
	
	public function validatePort(&$sServerPort)
	{
		if(empty($sServerPort))
		{
			$sServerPort = 7777;
			return true;
		}
		if(intval($sServerPort) >= 1 && intval($sServerPort) <= 65535) return true;
		$sServerPort = 7777;
		return false;
	}
}

?>