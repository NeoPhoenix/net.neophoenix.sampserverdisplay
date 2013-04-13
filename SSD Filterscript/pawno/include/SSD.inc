/*
 *	@version 1.0.1
 *	@author NeoPhoenix <support@neophoenix.net>
 *	@copyright 2012 <http://www.neophoenix.net>
 */
#include <INI>
#include <a_samp>
#include <a_mysql>
#include <a_http>
#define SSD:: SSD_
#define SSD_PATH "ssd"
#define SSD_INI #SSD_PATH"/ssd.ini"
#define SSD_TMP #SSD_PATH"/ssd_tmp.ini"
#define DATA_LEN		(1024)
#define SSD_TIME_BONUS	(60)
new data[DATA_LEN];
enum package_type (+=1)
{
	ADD = 1,
	UPDATE,
	REMOVE,
	DEACTIVATE,
}
enum ssdType (+=1)
{
	ssdTypeNone = 0,
	ssdTypeHTTP,
	ssdTypeMySQL,
}
enum ssd_info
{
	bool:ssdActive,
	ssdApiPath[MAX_VALUE_LEN],
	ssdAuth[MAX_VALUE_LEN],
	ssdWCFN,
	bool:ssdDebug,
	ssdCacheTime,
	ssdType:ssdConnection,
	bool:ssdCountNPCs,
	ssdIpAdress[15],
	ssdServerPort,
	ssdTIMER,
	ssdTIMERtime,
}
new SSD_Info[ssd_info];

forward bool:SSD::LoadINI(path[]);
stock bool:SSD::LoadINI(path[])
{
	new tmp[MAX_VALUE_LEN];
	tmp = INI::GetValue(path,"type","SSD");	
	SSD_Info[ssdDebug] = INI::GetValueAsBool(path,"debug","SSD");
	printf("SSD Debug mode is %s",SSD_Info[ssdDebug]?("on"):("off"));
	SSD_Info[ssdServerPort] = GetServerVarAsInt("port");
	SSD_Info[ssdCountNPCs]	= INI::GetValueAsBool(SSD_INI,"count_npcs","SAMP");
	if(!strcmp(tmp,"MySQL"))
	{
		if(SSD_Info[ssdDebug]) print("SSD Etablish MySQL connection");
		SSD_Info[ssdConnection] = ssdTypeMySQL;
		mysql_connect(INI::GetValue(path,"host","MySQL"),INI::GetValue(path,"user","MySQL"),INI::GetValue(path,"database","MySQL"),INI::GetValue(path,"password","MySQL"));
		if(mysql_ping() != 1)
		{
			if(SSD_Info[ssdDebug]) print("SSD Unable to etablish MySQL connection");
			return false;
		}
		if(SSD_Info[ssdDebug]) print("SSD MySQL connection etablished. Waiting for API...");
	}
	else if(!strcmp(tmp,"HTTP"))
	{
		if(SSD_Info[ssdDebug]) print("SSD Etablishing HTTP connection...");
		SSD_Info[ssdConnection] = ssdTypeHTTP;
	}
	else
	{
		printf("SSD Invalid connection type: %s",tmp);
		SSD_Info[ssdConnection] = ssdTypeNone;
		return false;
	}
	SSD_Info[ssdApiPath]	= INI::GetValue(path,"api_path","HTTP");
	SSD_Info[ssdAuth]		= INI::GetValue(path,"auth","HTTP");
	format(data,sizeof(data),"category=init&tool=basic&port=%d&auth=%s",SSD_Info[ssdServerPort],SSD_Info[ssdAuth]);
	HTTP(0,HTTP_POST,SSD_Info[ssdApiPath],data,"SSD_ReceiveBasicInformation");
	return true;
}

stock SSD::CreateServerPackage(package_type:type)
{
	if(!SSD_Info[ssdActive] || SSD_Info[ssdConnection] == ssdTypeNone) return;
	if(SSD_Info[ssdConnection] == ssdTypeHTTP)
	{
		SSD::PrepareHttpQuery();
		SSD::AddHttpFieldString("category","server");
	}	
	switch(type)
	{
	    case ADD,UPDATE:
	    {
			new timestamp		= (gettime(_,_,_)+SSD_TIME_BONUS),
				hostname[64],
				gamemode[64],
				password		= 0,
				players			= GetServerVarAsInt("players"),
				maxplayers		= GetMaxPlayers(),
				gravity[10],
				mapname[64],
				version[15],
				weather[10],
				weburl[64],
				worldtime[5];
			GetServerVarAsString("gamemodetext",gamemode,sizeof(gamemode));
			GetServerVarAsString("gravity",gravity,sizeof(gravity));
			GetServerVarAsString("mapname",mapname,sizeof(mapname));
			GetServerVarAsString("weburl",weburl,sizeof(weburl));
			GetServerVarAsString("version",version,sizeof(version));
			GetServerVarAsString("worldtime",worldtime,sizeof(worldtime));
			GetServerVarAsString("weather",weather,sizeof(weather));
			GetServerVarAsString("password",hostname,sizeof(hostname));
			if(hostname[0])	password = 1;
			else			password = 0;
			GetServerVarAsString("hostname",hostname,sizeof(hostname));
			if(SSD_Info[ssdConnection] == ssdTypeHTTP)
			{
				SSD::AddHttpFieldString("tool",type==ADD?("add"):("update"));
				SSD::AddHttpFieldInt("pw",password);
				SSD::AddHttpFieldString("hn",hostname);
				SSD::AddHttpFieldString("gm",gamemode);
				SSD::AddHttpFieldInt("p",players);
				SSD::AddHttpFieldInt("mp",maxplayers);
				SSD::AddHttpFieldString("g",gravity);
				SSD::AddHttpFieldString("mn",mapname);
				SSD::AddHttpFieldString("v",version);
				SSD::AddHttpFieldString("w",weather);
				SSD::AddHttpFieldString("url",weburl);
				SSD::AddHttpFieldString("t",worldtime);
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
			}
			else if(SSD_Info[ssdConnection] == ssdTypeMySQL)
			{
				format(data,sizeof(data),"DELETE FROM wcf%d_ssd_server WHERE serverIP='%s' AND serverPort='%d'",SSD_Info[ssdWCFN],SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort]);
				mysql_SendQuery(data);
				mysql_real_escape_string(hostname,hostname);
				mysql_real_escape_string(gamemode,gamemode);
				mysql_real_escape_string(mapname,mapname);
				mysql_real_escape_string(weburl,weburl);
				format(data,sizeof(data),"INSERT INTO wcf%d_ssd_server (\
				serverIP,serverPort,online,timestamp,password,hostname,gamemode,players,maxplayers,gravity,mapname,version,weather,weburl,worldtime,use_fs) VALUES",SSD_Info[ssdWCFN]);
				format(data,sizeof(data),"%s ('%s','%d','1','%d','%d','%s','%s','%d','%d','%s','%s','%s','%s','%s','%s','1')",\
				data,SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort],timestamp,password,hostname,gamemode,players,maxplayers,gravity,mapname,version,weather,weburl,worldtime);
				mysql_SendQuery(data);
				format(data,sizeof(data),"SELECT COUNT(*) FROM wcf%d_ssd_server WHERE serverIP='%s' AND serverPort='%d'",SSD_Info[ssdWCFN],SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort]);
				new result = strval(mysql_GetResult(data));
				if(!result)	printf("SSD MySQL failed %s the server",type==ADD?("registering"):("updating"));
				else		printf("SSD MySQL %s the server",type==ADD?("registered"):("updated"));
			}
	    }
	    case REMOVE:
	    {
			if(SSD_Info[ssdConnection] == ssdTypeHTTP)
			{
				SSD::AddHttpFieldString("tool","remove");
			}
			else if(SSD_Info[ssdConnection] == ssdTypeMySQL)
			{
				format(data,sizeof(data),"DELETE FROM wcf%d_ssd_user_to_server WHERE serverIP='%s' AND serverPort='%d'",SSD_Info[ssdWCFN],SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort]);
				mysql_SendQuery(data);
				format(data,sizeof(data),"DELETE FROM wcf%d_ssd_server WHERE serverIP='%s' AND serverPort='%d'",SSD_Info[ssdWCFN],SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort]);
				mysql_SendQuery(data);
				format(data,sizeof(data),"SELECT COUNT(*) FROM wcf%d_ssd_server WHERE serverIP='%s' AND serverPort='%d'",SSD_Info[ssdWCFN],SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort]);
				new result = strval(mysql_GetResult(data));
				if(!result)	{ if(SSD_Info[ssdDebug]) print("SSD MySQL removed the server"); }
				else		{ if(SSD_Info[ssdDebug]) print("SSD MySQL failed removing the server"); }
			}
	    }
	}
	if(SSD_Info[ssdConnection] == ssdTypeHTTP) SSD::SendHttpQuery("SSD_OnServerChange");
	return;
}

stock SSD::CreateUserPackage(package_type:type,playerid)
{
	if(!SSD_Info[ssdActive] || SSD_Info[ssdConnection] == ssdTypeNone) return;
	if(!IsPlayerConnected(playerid)) return;
	if(IsPlayerNPC(playerid) && !SSD_Info[ssdCountNPCs] && type != REMOVE) return;
	if(SSD_Info[ssdConnection] == ssdTypeHTTP)
	{
		SSD::PrepareHttpQuery();
		SSD::AddHttpFieldString("category","user");
		SSD::AddHttpFieldInt("playerid",playerid);
	}
	new tmp_name[MAX_PLAYER_NAME+20];
	GetPlayerName(playerid,tmp_name,sizeof(tmp_name));
	if(IsPlayerNPC(playerid)) format(tmp_name,sizeof(tmp_name),INI::GetValue(SSD_INI,"npc_format","SAMP"),tmp_name);
	SSD::AddHttpFieldString("name",tmp_name);
	switch(type)
	{
	    case ADD,UPDATE:
	    {
			if(SSD_Info[ssdConnection] == ssdTypeHTTP)
			{
				SSD::AddHttpFieldInt("score",GetPlayerScore(playerid));
				SSD::AddHttpFieldInt("ping",GetPlayerPing(playerid));
				SSD::AddHttpFieldString("tool",type==ADD?("add"):("update"));
			}
			if(SSD_Info[ssdConnection] == ssdTypeMySQL)
			{
				if(type == ADD)
				{
					format(data,sizeof(data),"SELECT COUNT(*) FROM wcf%d_ssd_user_to_server WHERE serverIP='%s' AND serverPort='%d' AND userID='%d'",SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort],playerid);
					new result = strval(mysql_GetResult(data));
					if(result == 0)
					{
						format(data,sizeof(data),"DELETE FROM wcf%d_ssd_user_to_server WHERE serverIP='%s' AND serverPort='%d' AND userID='%d'",SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort],playerid);
						mysql_SendQuery(data);
						format(data,sizeof(data),"INSERT INTO wcf%d_ssd_user_to_server (serverIP,serverPort,userID,userName,userScore,userPing,timestamp) VALUES \
						('%s','%d','%d','%s','%d','%d')\
						",SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort],playerid,tmp_name,GetPlayerScore(playerid),GetPlayerPing(playerid),gettime(_,_,_));
						mysql_SendQuery(data);
					}
					else if(SSD_Info[ssdDebug]) printf("SSD MySQL failed adding user %d (already exists)",playerid);
				}
				else if(type == UPDATE)
				{
					format(data,sizeof(data),"SELECT COUNT(*) FROM wcf%d_ssd_user_to_server WHERE serverIP='%s' AND serverPort='%d' AND userID='%d'",SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort],playerid);
					new result = strval(mysql_GetResult(data));
					if(result >= 1)
					{
						format(data,sizeof(data),"UPDATE wcf%d_ssd_user_to_server SET userName='%s',userScore='%d',userPing='%d' WHERE serverIP='%s' AND serverPort='%d' AND userID='%d'",
						tmp_name,GetPlayerScore(playerid),GetPlayerPing(playerid),SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort],playerid);
						mysql_SendQuery(data);
					}
					else
					{
						format(data,sizeof(data),"INSERT INTO wcf%d_ssd_user_to_server (serverIP,serverPort,userID,userName,userScore,userPing,timestamp) VALUES \
						('%s','%d','%d','%s','%d','%d','%d')\
						",SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort],playerid,tmp_name,GetPlayerScore(playerid),GetPlayerPing(playerid),gettime(_,_,_));
						mysql_SendQuery(data);
					}
				}
			}
	    }
	    case REMOVE:
	    {
			if(SSD_Info[ssdConnection] == ssdTypeHTTP) SSD::AddHttpFieldString("tool","remove");
			if(SSD_Info[ssdConnection] == ssdTypeMySQL)
			{
				format(data,sizeof(data),"DELETE FROM wcf%d_ssd_user_to_server WHERE serverIP='%s' AND serverPort='%d' AND userID='%d'",SSD_Info[ssdIpAdress],SSD_Info[ssdServerPort],playerid);
				mysql_SendQuery(data);
			}
	    }
			
	}
	if(SSD_Info[ssdConnection] == ssdTypeHTTP) SSD::SendHttpQuery("SSD_OnUserChange");
	return;
}

stock SSD::SendHttpQuery(callback[],index=0)
{
	if(!SSD_Info[ssdActive] || SSD_Info[ssdConnection] == ssdTypeNone) return;
	HTTP(index,HTTP_POST,SSD_Info[ssdApiPath],data,callback);
	return;
}

stock SSD::PrepareHttpQuery()
{
	if(!SSD_Info[ssdActive] || SSD_Info[ssdConnection] == ssdTypeNone) return;
	format(data,sizeof(data),"auth=%s&port=%d",SSD_Info[ssdAuth],SSD_Info[ssdServerPort]);
	return;
}

stock SSD::AddHttpFieldString(fieldname[],fieldvalue[])
{
	if(!SSD_Info[ssdActive] || SSD_Info[ssdConnection] == ssdTypeNone) return;
	for(new c; c<strlen(fieldvalue); c++)
	{
		if(fieldvalue[c] == '&') fieldvalue[c] = '_';
		if(fieldvalue[c] == '=') fieldvalue[c] = '_';
		if(fieldvalue[c] == ' ') fieldvalue[c] = '_';
	}
	format(data,sizeof(data),"%s&%s=%s",data,fieldname,fieldvalue);
	return;
}

stock SSD::AddHttpFieldInt(fieldname[],fieldvalue)
{
	if(!SSD_Info[ssdActive] || SSD_Info[ssdConnection] == ssdTypeNone) return;
	format(data,sizeof(data),"%s&%s=%d",data,fieldname,fieldvalue);
	return;
}

stock str_replace(sSearch[],sReplace[],sSubject[],sSubjectSize=sizeof(sSubject))
{
	new sSearchLen = strlen(sSearch),
	    sReplaceLen = strlen(sReplace),
	    sCount = 0,
		sPosition = 0,
		sSearchPosition = -1;
	while((sSearchPosition = strfind(sSubject,sSearch,false,sPosition)) != -1)
	{
	   	strdel(sSubject,sSearchPosition,sSearchPosition+sSearchLen);
	    strins(sSubject,sReplace,sSearchPosition,sSubjectSize);
	    sPosition += sReplaceLen;
	}
	return sCount;
}

stock mysql_GetResult(query[])
{
	new queryS[256];
	mysql_query(query);
	mysql_store_result();
	mysql_fetch_row(queryS);
	mysql_free_result();
	return queryS;
}

stock mysql_SendQuery(query[])
{
	mysql_query(query);
	mysql_free_result();
	return;
}

forward SSD::ReceiveBasicInformation(index,response_code,sData[]);
public SSD::ReceiveBasicInformation(index,response_code,sData[])
{
	if(SSD_Info[ssdActive]) return;
	print(sData);
	if(response_code == 200)
	{
		new File:tmp = fopen(SSD_TMP,io_write);
		fwrite(tmp,sData);
		fclose(tmp);
		
		new error = INI::GetValueAsInt(SSD_TMP,"error_id","Errors");
		if(error != 0)
		{
			printf("SSD API Error (error_id %d) - for more information read the documentation about \"SampHttpAPI\"",error);
			return;
		}
		else
		{
			SSD_Info[ssdCacheTime]	= INI::GetValueAsInt(SSD_TMP,"cache_time","Request");
			if(SSD_Info[ssdCacheTime] >= 10)
			{
				SSD_Info[ssdWCFN]		= INI::GetValueAsInt(SSD_TMP,"wcf_n","Request");
				if(SSD_Info[ssdDebug]) printf("SSD API information received (c.%d)",SSD_Info[ssdCacheTime]);
				format(SSD_Info[ssdIpAdress],sizeof(SSD_Info[ssdIpAdress]),"%s",INI::GetValue(SSD_TMP,"remote_addr","General"));
				SSD_Info[ssdActive]	= true;
				SSD::CreateServerPackage(ADD);
				new Float:ms = ((float(SSD_Info[ssdCacheTime])/4)*3000);
				if(SSD_Info[ssdTIMER]) KillTimer(SSD_Info[ssdTIMER]);
				SSD_Info[ssdTIMERtime]	= floatround(ms);
				SSD_Info[ssdTIMER]		= SetTimer("SSD_Cronjob",SSD_Info[ssdTIMERtime],1);
				if(SSD_Info[ssdDebug]) printf("SSD cronjob working with %dms",SSD_Info[ssdTIMERtime]);
			}
			else print("SSD is unable to start (minimum of 10s cacheage required)");
		}
	}
	else printf("SSD ReceiveBasicInformation returns invalid response code (%d)",response_code);
	return;
}

forward SSD::Cronjob();
public SSD::Cronjob()
{
	if(!SSD_Info[ssdActive]) return;
	SSD::CreateServerPackage(UPDATE);
	return;
}

forward SSD::OnServerChange(index,response_code,sData[]);
public SSD::OnServerChange(index,response_code,sData[])
{
	if(response_code == 200)
	{
		new File:tmp = fopen(SSD_TMP,io_write);
		fwrite(tmp,sData);
		fclose(tmp);
		new error = INI::GetValueAsInt(SSD_TMP,"error_id","Errors");
		if(error != 0)
		{
			printf("SSD HTTP API Error (error_id %d) - for more information read the documentation about \"SampHttpAPI\"",error);
			return;
		}
		else
		{
			if(INI::GetValueAsBool(SSD_TMP,"success","Request") == true)
			{
				switch(package_type:INI::GetValueAsInt(SSD_TMP,"tool","Sent"))
				{
					case ADD:
					{
						if(SSD_Info[ssdDebug]) print("SSD HTTP Server added");
						for(new i; i<GetMaxPlayers(); i++)
						{
							if(!IsPlayerConnected(i)) continue;
							SSD::CreateUserPackage(ADD,i);
						}
					}
					case UPDATE:	if(SSD_Info[ssdDebug]) print("SSD HTTP Server updated");
					case REMOVE:	if(SSD_Info[ssdDebug]) print("SSD HTTP Server removed");
				}
				SSD_Info[ssdActive] = true;
			}
			else print("SSD HTTP Server change failed");
		}
	}
	else printf("SSD OnServerChange returns invalid response code (%d)",response_code);
	return;
}

forward SSD::OnUserChange(index,response_code,sData[]);
public SSD::OnUserChange(index,response_code,sData[])
{
	if(response_code == 200)
	{
		new File:tmp = fopen(SSD_TMP,io_write);
		fwrite(tmp,sData);
		fclose(tmp);
		new error = INI::GetValueAsInt(SSD_TMP,"error_id","Errors");
		if(error != 0)
		{
			printf("SSD HTTP API Error (error_id %d) - for more information read the documentation about \"SampHttpAPI\"",error);
			return;
		}
		else
		{
			if(INI::GetValueAsBool(SSD_TMP,"success","Request") == true)
			{
				switch(package_type:INI::GetValueAsInt(SSD_TMP,"tool","Sent"))
				{
					case ADD:		if(SSD_Info[ssdDebug]) print("SSD HTTP User added");
					case UPDATE:	if(SSD_Info[ssdDebug]) print("SSD HTTP User updated");
					case REMOVE:	if(SSD_Info[ssdDebug]) print("SSD HTTP User removed");
				}
			}
			else if(SSD_Info[ssdDebug]) print("SSD HTTP User change failed");
		}
	}
	else if(SSD_Info[ssdDebug]) printf("SSD OnUserChange returns invalid response code (%d)",response_code);
	return;
}

public OnQueryError(errorid, error[], resultid, extraid, callback[], _query[], connectionHandle)
{
	if(SSD_Info[ssdDebug]) printf("MYSQL RETURNED AN ERROR. %s - %s",_query,error);
	return 1;
}
