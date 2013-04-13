{include file="documentHeader"}
<head>
	<title> - {lang}{PAGE_TITLE}{/lang}</title>
	{assign var='allowSpidersToIndexThisPage' value=true}

	{include file='headInclude' sandbox=false}
</head>
<body{if $templateName|isset} id="tpl{$templateName|ucfirst}"{/if}>
{include file='header' sandbox=false}

<div id="main">
	{if $canAccessPage}
    <div class="mainHeadline">
		<img src="{icon}sampicon48x48.png{/icon}" alt="" />
		<div class="headlineContainer">
			<h2>SA:MP Serverdisplay</h2>
			<p><strong>{lang}wcf.usersOnline.marking.legend{/lang}</strong> {@$legende}</p>
		</div>
	</div>
	{if $userMessages|isset}{@$userMessages}{/if}
	{if $additionalTopContents|isset}{@$additionalTopContents}{/if}
	<div id='profileEditContent' class='tabMenu'>
		<ul>
			<li {if $tabID==0}class='activeTabMenu'{/if}><a href='index.php?page=Sampserverdisplay&serverID={$serverID}&tab=0'><img src='wcf/icon/updateServerM.png' alt=''> <span>{lang}net.neophoenix.sampdisplay.page_tab_server{/lang}</span></a></li>
			{if $canSeeUsers}<li {if $tabID==1}class='activeTabMenu'{/if}><a href='index.php?page=Sampserverdisplay&serverID={$serverID}&tab=1'><img src='wcf/icon/usersM.png' alt=''> <span>{lang}net.neophoenix.sampdisplay.page_tab_user{/lang}</span></a></li>{/if}
		</ul>
	</div>
	<div class='subTabMenu'>
		<div class='containerHead'>
			<ul>
			{if $tabID==1}
				{if $activate_modhistory}
					{if $canSeeUsers}<li{if $subtabID!=1} class="activeSubTabMenu"{/if}><a href="index.php?page=Sampserverdisplay&serverID={$serverID}&tab=1&subtab=0"><span>{lang}net.neophoenix.sampdisplay.page_content_link_players{/lang}</span></a></li>{/if}
					{if $canSeeModeration}<li{if $subtabID==1} class="activeSubTabMenu"{/if}><a href="index.php?page=Sampserverdisplay&serverID={$serverID}&tab=1&subtab=1"><span>{lang}net.neophoenix.sampdisplay.page_content_link_history{/lang}</span></a></li>{/if}
				{/if}
			{/if}
			</ul>
		</div>
	</div>
	<div class='border tabMenuContent'>
		<div class='container-1'>
			{if !$page_optional_content|empty}
			<fieldset>
			<legend><label for='styleID'>{lang}net.neophoenix.sampdisplay.page_content_announcements{/lang}</label></legend>
				{@$page_optional_content}
			</fieldset>
			{/if}
			{if $isOnline==true}
				{if $tabID==1}
				{if $subtabID==1}
						{if $countHistoryEntries}
							<fieldset>
								<legend><label for='styleID'>{lang}net.neophoenix.sampdisplay.page_content_content{/lang}</label></legend>
								<p class="error" style="margin-top:4px;margin-bottom:1px">
									{foreach from=$historyEntries item=$historyEntry}
										{if $canDeleteModeration}<a href='index.php?page=Sampserverdisplay&serverID={$serverID}&tab=1&subtab=1&tool=1&value={$historyEntry.0}' title='{lang}net.neophoenix.sampdisplay.page_content_moderation_delete_entry{/lang}'><img src='{icon}deleteS.png{/icon}'></a>{/if} {$historyEntry.1}<br/>
									{/foreach}
								</p>
							</fieldset>
						{/if}
					{else}
						{if $countUsers>=1}
							<fieldset>
								<legend><label for='styleID'>{lang}net.neophoenix.sampdisplay.page_content_content{/lang}</label></legend>
								<table class='tableList membersList'>
									<thead>
										<tr class='tableHead'>
											<th>PlayerID</th>
											<th>Spielername</th>
											<th>Avatar</th>
											<th>Onlinezeit</th>
											<th>Score</th>
											<th>Ping</th>
										</tr>
									</thead>
									<tbody>
									{foreach from=$users item=$user}
									<tr class='container-{$user.0}'>
										<td>{$user.1}{if $rcon_active} {if $canKick==1}<a href='index.php?page=Sampserverdisplay&serverID={$serverID}&tab=1&rconTool=0&rconValue={$user.1}&targetName={$user.2}' title='{lang}net.neophoenix.sampdisplay.page_content_moderation_kick{/lang}'><img src='{icon}userBanDisabledS.png{/icon}'/></a>{/if}{if $canBan==1}<a href='index.php?page=Sampserverdisplay&serverID={$serverID}&tab=1&rconTool=1&rconValue={$user.1}&targetName={$user.2}' title='{lang}net.neophoenix.sampdisplay.page_content_moderation_ban{/lang}'><img src='{icon}userUnbanS.png{/icon}'/></a>{/if}{/if}</td>
										<td>{if $user.7!=0}<a href='index.php?page=User&userID={$user.7}'>{/if}{@$user.8}{if $user.7!=0}</a>{/if}</td>
										<td>{@$user.3}</td>
										<td>{$user.4}</td>
										<td>{$user.5}</td>
										<td>{$user.6}</td>
									</tr>
									{/foreach}
									</tbody>
								</table>
							</fieldset>
						{/if}
					{/if}
				{else}
					<fieldset>
						<legend><label for='styleID'>{lang}net.neophoenix.sampdisplay.page_content_content{/lang}</label></legend>
						<table class='tableList membersList'>
							<thead>
								<tr class='tableHead'>
									<th><strong>{lang}net.neophoenix.sampdisplay.page_content_server_key{/lang}<strong></th>
									<th><strong>{lang}net.neophoenix.sampdisplay.page_content_server_value{/lang}</strong></th>
								</tr>
							</thead>
							<tbody>
								<tr class='container-1'><td>{lang}net.neophoenix.sampdisplay.page_content_server_mapname{/lang}</td><td>{$server_mapname}</td></tr>
								<tr class='container-2'><td>{lang}net.neophoenix.sampdisplay.page_content_server_hostname{/lang}</td><td>{$server_hostname}</td></tr>
								<tr class='container-1'><td>{lang}net.neophoenix.sampdisplay.page_content_server_gamemode{/lang}</td><td>{$server_gamemode}</td></tr>
								<tr class='container-2'><td>{lang}net.neophoenix.sampdisplay.page_content_server_weburl{/lang}</td><td>{$server_weburl}</td></tr>
								<tr class='container-1'><td>{lang}net.neophoenix.sampdisplay.page_content_server_worldtime{/lang}</td><td>{$server_worldtime}</td></tr>
								<tr class='container-2'><td>{lang}net.neophoenix.sampdisplay.page_content_server_weather{/lang}</td><td>{$server_weather}</td></tr>
								<tr class='container-1'><td>{lang}net.neophoenix.sampdisplay.page_content_server_sampversion{/lang}</td><td>{$server_version}</td></tr>
								<tr class='container-2'><td>{lang}net.neophoenix.sampdisplay.page_content_server_players{/lang}</td><td>{$server_players} / {$server_maxplayers}</td></tr>
							</tbody>
						</table>
					</fieldset>
				{/if}
			{/if}
		</div>
	</div>
	{else}
	<p class="error">{lang}wcf.global.error.permissionDenied{/lang}</p>
	{/if}
	{include file='footer' sandbox=false}
</body>
</html>