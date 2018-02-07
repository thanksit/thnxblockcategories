{if $blockCategTree && $blockCategTree.children|@count}
<!-- Block categories module -->
<div class="thnxblockcategories block categories_block_list">
	<h4 class="title_block">
		{if isset($currentCategory)}{$currentCategory->name|escape}{else}{l s='Categories' mod='thnxblockcategories'}{/if}
		{* <i class="icon-th-list f_right f_size_15 m_top_15"></i> *}
	</h4>
	<div class="block_content">
		<ul class="tree {if $isDhtml}dhtml{/if}">
		{foreach from=$blockCategTree.children item=child name=blockCategTree}
			{if $smarty.foreach.blockCategTree.last}
				{include file="$branche_tpl_path" node=$child last='true'}
			{else}
				{include file="$branche_tpl_path" node=$child}
			{/if}
		{/foreach}

		</ul>
		{* Javascript moved here to fix bug #PSCFI-151 *}
	</div>
</div>
<!-- /Block categories module -->
{/if}
