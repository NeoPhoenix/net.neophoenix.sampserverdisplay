CREATE TABLE IF NOT EXISTS `wcf1_ssd_server` (
	`serverIP` text NOT NULL,
	`serverPort` int(11) NOT NULL,
	`online` int(11) NOT NULL,
	`use_fs` int(11) NOT NULL,
	`timestamp` int(11) NOT NULL,
	`password` int(11) NOT NULL,
	`hostname` text NOT NULL,
	`gamemode` text NOT NULL,
	`players` int(11) NOT NULL,
	`maxplayers` int(11) NOT NULL,
	`gravity` float(6) NOT NULL,
	`mapname` text NOT NULL,
	`version` text NOT NULL,
	`weather` int(4) NOT NULL,
	`weburl` text NOT NULL,
	`worldtime` varchar(5) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `wcf1_ssd_user_to_server` (
	`serverIP` text NOT NULL,
	`serverPort` int(11) NOT NULL,
	`userID` int(3) NOT NULL,
	`userName` varchar(24) NOT NULL,
	`userScore` int(11) NOT NULL,
	`userPing` int(5) NOT NULL,
	`timestamp` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `wcf1_ssd_user_to_server_tmp` (
	`serverIP` text NOT NULL,
	`serverPort` int(11) NOT NULL,
	`userID` int(3) NOT NULL,
	`userName` varchar(24) NOT NULL,
	`timestamp` int(11) NOT NULL,
	`updated` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `wcf1_ssd_moderation_history` (
	`entryID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`serverIP` text NOT NULL,
	`serverPort` int(11) NOT NULL,
	`modName` text NOT NULL,
	`targetName` text NOT NULL,
	`rconTool` int(5) NOT NULL,
	`timestamp` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;