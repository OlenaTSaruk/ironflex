{if $blocks}
	{foreach from=$blocks item="block"}
		<div id="contentanywhere-block-{$block.id_block}" class="col-12 col-md-auto order-xl-first content-anywhere">
			{if $block.image}
				<p class="image">
					<img src="{$block.image}" alt="{$block.title}" class="img-fluid d-block" />
				</p>
			{/if}
			{$block.content nofilter}
		</div>
	{/foreach}
{/if}