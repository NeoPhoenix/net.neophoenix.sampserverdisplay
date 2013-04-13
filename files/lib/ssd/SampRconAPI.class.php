<?php
/**
 *	This API also connects directly to the server, but instead of using the
 *	query system, depends on the RCON system. 
 *	
 *	This system, unlike the query system (in my opinion) is not able to
 *	handle as many calls, so please use this wisely.
 *
 *	@package sampAPI
 *	@version 1.2
 *	@author David Weston <westie@typefish.co.uk>
 *	@copyright 2010; http://www.typefish.co.uk/licences/
 */

class SampRconAPI
{
	private $rSocket = false;
	private $aServer = array();
	
	public function __construct($sServer, $iPort, $sPassword)
	{
		$this->aServer[0] = $sServer;
		$this->aServer[1] = $iPort;
		$this->aServer[2] = $sPassword;
		$this->rSocket = fsockopen('udp://'.$this->aServer[0], $this->aServer[1], $iError, $sError, 2);
		if(!$this->rSocket)
		{
			$this->aServer[4] = false;
			return;
		}
		socket_set_timeout($this->rSocket, 2);
		$sPacket = 'SAMP';
		$sPacket .= chr(strtok($this->aServer[0], '.'));
		$sPacket .= chr(strtok('.'));
		$sPacket .= chr(strtok('.'));
		$sPacket .= chr(strtok('.'));
		$sPacket .= chr($this->aServer[1] & 0xFF);
		$sPacket .= chr($this->aServer[1] >> 8 & 0xFF);
		$sPacket .= 'p4150';
		fwrite($this->rSocket, $sPacket);
		if(fread($this->rSocket, 10))
		{
			if(fread($this->rSocket, 5) == 'p4150')
			{
				$this->aServer[4] = true;
				return;
			}
		}
		$this->aServer[4] = false;
		return;
	}
	public function __destruct()
	{
		@fclose($this->rSocket);
	}
	public function isOnline()
	{
		return isset($this->aServer[4])?$this->aServer[4]:false;
	}
	public function rconAvailable()
	{
		$response = $this->packetSend("echo ssd",2.0);
		return (!strcmp($response[0],'ssd'))?true:false;
	}
	public function packetSend($sCommand, $fDelay = 1.0)
	{
		fwrite($this->rSocket, $this->packetCreate($sCommand));
		if($fDelay === false)
		{
			return;
		}
		$aReturn = array();
		$iMicrotime = microtime(true) + $fDelay;
		while(microtime(true) < $iMicrotime)
		{
			$sTemp = substr(fread($this->rSocket, 128), 13);
			if(strlen($sTemp))
			{
				$aReturn[] = $sTemp;
			}
			else
			{
				break;
			}
		}
		return $aReturn;
	}
	private function packetCreate($sCommand)
	{
		$sPacket = 'SAMP';
		$sPacket .= chr(strtok($this->aServer[0], '.'));
		$sPacket .= chr(strtok('.'));
		$sPacket .= chr(strtok('.'));
		$sPacket .= chr(strtok('.'));
		$sPacket .= chr($this->aServer[1] & 0xFF);
		$sPacket .= chr($this->aServer[1] >> 8 & 0xFF);
		$sPacket .= 'x';
		$sPacket .= chr(strlen($this->aServer[2]) & 0xFF);
		$sPacket .= chr(strlen($this->aServer[2]) >> 8 & 0xFF);
		$sPacket .= $this->aServer[2];
		$sPacket .= chr(strlen($sCommand) & 0xFF);
		$sPacket .= chr(strlen($sCommand) >> 8 & 0xFF);
		$sPacket .= $sCommand;
		return $sPacket;
	}
}