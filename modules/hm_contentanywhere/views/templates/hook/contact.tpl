{if $blocks}
<div class="content-anywhere-{$hook}">
	<div class="row">
	{foreach from=$blocks item="block"}
		<div class="col-6 col-md-3 col-xl-6 item">
			<div class="item-image">
				{if $block.image}<img src="{$block.image}" alt="{$block.title}" />{/if}
			</div>
			<div class="item-content">
				{$block.content nofilter}
			</div>
		</div>
	{/foreach}
	</div>
</div>
{/if}