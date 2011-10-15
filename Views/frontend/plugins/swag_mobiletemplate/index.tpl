{block name="frontend_index_no_script_message" prepend}
{if $shopwareMobile.active}
	<script type="text/javascript">
		var useSubShop = {$shopwareMobile.useSubShop},
			subShopID  = {$shopwareMobile.subShopId};


	if(navigator.userAgent.match(/{$shopwareMobile.userAgents}/i)) {
		var text = unescape("M%F6chten Sie die f%FCr mobile Endger%E4te optimierte Version dieser Seite aufrufen%3F");
		var quest = confirm(text);

		if(quest == true && useSubShop == 0) {
			document.body.innerHTML += '<form id="dynForm" action="" method="post"><input type="hidden" name="sMobile" value="1"></form>';
			document.getElementById("dynForm").submit();
		}

		if(quest == true && useSubShop == 1) {
			document.body.innerHTML += '<form id="dynForm" action="" method="post"><input type="hidden" name="sLanguage" value="'+ subShopID +'"></form>';
			document.getElementById("dynForm").submit();
		}
	}
	</script>
{/if}

{literal}
<script type="text/javascript">
addEventListener("load", function() { setTimeout(function() { window.scrollTo(0, 1) }, 0) }, false);
</script>
{/literal}
{if $shopwareMobile.pluginOpts->showNotice}
	<style type="text/css">
	html { padding: 0 }
	.visit-mobile {
		background: -webkit-gradient(linear, left top, left bottom, from({$shopwareMobile.pluginOpts->startColor}), to({$shopwareMobile.pluginOpts->endColor}));
		padding: 20px;
		width: 100%;
	}
	.visit-mobile .text,
	.visit-mobile a {
		color: {$shopwareMobile.pluginOpts->textColor};
		text-align: center;
		display: block;
		font-size: 40px;
		line-height: 46px;
	}
	.visit-mobile a {
		font-weight: 700;
	}
	</style>
	<div class="visit-mobile">
		<p class="text">
			{s name="MobileNoticeUseATouchPhone"}Sie nutzen ein Touch-basiertes Smartphone?{/s}
		</p>
		<p>
			<form id="dynForm" action="" method="post">
				<input type="hidden" name="sMobile" value="1" />
				{if $shopwareMobile.useSubShop}
					<input type="hidden" name="sLanguage" value="{$shopwareMobile.subShopId}" />
				{/if}
				<a href="#mobile" onclick="document.getElementById('dynForm').submit();return false;">{s name="MobileNoticeUseMobileVersion"}Nutzen Sie jetzt die mobile Version von {$sShopname}{/s}</a>
			</form>
		</p>

		</div>
	</div>
{/if}
{/block}