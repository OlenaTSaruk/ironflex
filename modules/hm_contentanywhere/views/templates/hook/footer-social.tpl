{if $blocks}
	{foreach from=$blocks item="block"}
		{if $block.image}
			<p class="image">
				<img src="{$block.image}" alt="{$block.title}" class="img-fluid d-block" />
			</p>
		{/if}
		{$block.content nofilter}
	{/foreach}
{/if}