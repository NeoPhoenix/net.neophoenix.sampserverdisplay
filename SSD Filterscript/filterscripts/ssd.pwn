#include <SSD>
main(){}

public OnFilterScriptInit()
{
    SSD::LoadINI(SSD_INI);
	return 1;
}

public OnFilterScriptExit()
{
    SSD::CreateServerPackage(REMOVE);
	return 1;
}

public OnPlayerConnect(playerid)
{
    SSD::CreateUserPackage(ADD,playerid);
	return 1;
}

public OnPlayerDisconnect(playerid, reason)
{
    SSD::CreateUserPackage(REMOVE,playerid);
	return 1;
}

#define is_equal(%0,%1) (strlen(%0) == strlen(%1) && !strcmp(%0,%1))
public OnRconCommand(cmd[])
{
	if(strfind(cmd,"ssd",true) == 0)
	{
	    new token[5][30];
	    tokenize(cmd,token,' ');
		if(is_equal(token[1],"start"))
		{
		    if(SSD_Info[ssdActive] == true) print("SSD Services are already running");
			else
			{
			    print("SSD Initializing remote start of SSD services");
				SSD::LoadINI(SSD_INI);
			}
		}
		else if(is_equal(token[1],"stop"))
		{
		    print("SSD Initializing remote stop of SSD services");
		    KillTimer(SSD_Info[ssdTIMER]);
		    SSD::CreateServerPackage(DEACTIVATE);
			SSD_Info[ssdActive] = false;
		}
		else if(is_equal(token[1],"help"))
		{
		    if(is_equal(token[2],"start"))
		    {
		        print("SSD Help \"start\"");
		        print("-> \"ssd start\" initializes the SSD services");
		    }
		    else if(is_equal(token[2],"stop"))
		    {
		        print("SSD Help \"stop\"");
		        print("-> \"ssd stop\" stops the SSD services");
		    }
		    else if(is_equal(token[2],"setmode"))
		    {
		        print("SSD Help \"setmode @mode\"");
		        print("-> \"ssd setmode @mode\" sets the type SSD sends queries to @mode.");
		        print("-> Available modes: \"http\", \"mysql\" (recommended)");
		    }
		    else
		    {
			    printf("SSD Invalid parameter \"%s\" for \"ssd help @cmd\"",token[1]);
			    print("-> Available cmds: \"start\", \"stop\", \"setmode\"");
		    }
		}
		else if(is_equal(token[1],"setmode"))
		{
		    if(is_equal(token[2],"http"))
		    {
		        print("SSD will now work with HTTP-requests. Restarting...");
		        SSD_Info[ssdActive] = false;
		        INI::SetValue(SSD_INI,"type","HTTP","SSD");
		        SSD::LoadINI(SSD_INI);
		    }
		    else if(is_equal(token[2],"mysql"))
		    {
		        print("SSD will now work with direct MySQL access. Restarting...");
		        SSD_Info[ssdActive] = false;
		        INI::SetValue(SSD_INI,"type","MySQL","SSD");
		        SSD::LoadINI(SSD_INI);
		    }
		    else
		    {
		    	printf("SSD Invalid mode \"%s\" for \"ssd setmode @mode\"",token[1]);
		    	print("Available modes: \"http\", \"mysql\" (recommended)");
		    }
		}
		else if(is_equal(token[1],"cmdlist"))
		{
		    print("SSD Cmdlist");
			print("-> Usage: \"ssd @cmd\"");
			print("-> Available cmds: \"cmdlist\" \"start\" \"stop\", \"help\", \"setmode\"");
		}
		else
		{
		    printf("SSD Invalid command \"%s\"",token[1]);
		    print("-> Hint: use \"ssd cmdlist\" to have a look at all commands");
		}
	}
	return 1;
}

stock tokenize(const source[],dest[][],delim,destsize=sizeof(dest),destlen=sizeof(dest[]))
{
    new start,tokenID;
	for(new i; i<=strlen(source); i++)
    {
        if(source[i] == delim || i == strlen(source))
        {
            strmid(dest[tokenID],source,start,i,destlen);
            dest[tokenID][i-start] = '\0';
            start = (i+1);
            if(++tokenID >= destsize) break;
        }
    }
    return 1;
}
