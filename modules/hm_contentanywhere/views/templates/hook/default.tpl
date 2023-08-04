{if $blocks}
	{foreach from=$blocks item="block"}
		<div id="subcategory-block-{$block.id_block}" class="block subcategory-block">
			<div class="container">
				{if $block.title}
					<div class="row">
						<div class="col-12">
							<h3 class="wrapper-title">{$block.title}</h3>
						</div>
					</div>
				{/if}
				{$block.content nofilter}
			</div>
		</div>
	{/foreach}
{/if}