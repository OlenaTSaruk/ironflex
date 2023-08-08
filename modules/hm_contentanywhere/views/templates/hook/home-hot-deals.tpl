{if $blocks}
{foreach from=$blocks item="block"}
<div class="wrapper content-anywhere-{$hook}"{if $block.image} style="background-image: url('{$block.image}')"{/if}>
	<div class="row">  
      <h2>{l s='Seasonal sale' d='Shop.Theme.Global'}</h2>
      <div class="hot-sale col-12 col-lg-5" data-animation="fadeInLeft">{hook h='displayCustomDiscount'}</div>
      <div class="three-products col-12 col-lg-7" data-animation="fadeInLeft">{$block.content nofilter}</div>
    </div>
</div>
{/foreach}
{/if}