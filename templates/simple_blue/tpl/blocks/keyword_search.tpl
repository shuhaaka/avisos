<!-- keyword search block -->

<form method="post" action="{$rlBase}{if $config.mod_rewrite}{$pages.search}.html{else}?page={$pages.search}{/if}">
	<input type="hidden" name="form" value="keyword_search" />

	<table class="sTable">
	<tr>
		<td><div class="keyword_search_input"><input type="text" maxlength="255" name="f[keyword_search]" style="width: 100%;" {if $smarty.post.f.keyword_search}value="{$smarty.post.f.keyword_search}"{/if} /></div></td>
		<td style="width: 47px;" class="ralign"><input class="search" type="submit" name="search" value="&nbsp;" /></td>
	</tr>
	</table>
	<div class="keyword_search_opt">
		<ul>
			{assign var='tmp' value=3}
			{section name='keyword_opts' loop=$tmp max=3}
				<li><label><input {if $fVal.keyword_search_type || $keyword_mode}{if $smarty.section.keyword_opts.iteration == $fVal.keyword_search_type || $keyword_mode == $smarty.section.keyword_opts.iteration}checked="checked"{/if}{else}{if $smarty.section.keyword_opts.iteration == 2}checked="checked"{/if}{/if} value="{$smarty.section.keyword_opts.iteration}" type="radio" name="f[keyword_search_type]" /> {assign var='ph' value='keyword_search_opt'|cat:$smarty.section.keyword_opts.iteration}{$lang.$ph}</label></li>
			{/section}
		</ul>
	</div>
	<div class="keyword_search_link">
		<a id="refine_keyword_opt" class="dotted" href="javascript:void(0)">{$lang.advanced_options}</a>
	</div>
	<script type="text/javascript">
	{literal}
	
	$(document).ready(function(){
		$('#refine_keyword_opt').click(function(){
			$(this).parent().prev().slideToggle();
		});
	});
	
	{/literal}
	</script>
</form>

<!-- keyword search block -->