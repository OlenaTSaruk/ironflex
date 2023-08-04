{if $blocks}
{foreach from=$blocks item="block"}
<div class="wrapper content-anywhere-{$hook}"{if $block.image} style="background-image: url('{$block.image}')"{/if}>
    <div class="row">
      <div class="col-12 col-lg-12 col-xl-12" data-animation="fadeInLeft">{$block.content nofilter}</div>
    </div>
  {*<img src="{$urls.theme_assets}img/wave1.png" class="wave wave1" alt="{l s='Background' d='Shop.Theme.Global'}" />
  <img src="{$urls.theme_assets}img/wave2.png" class="wave wave2" alt="{l s='Background' d='Shop.Theme.Global'}" />*}
</div>
{/foreach}
{/if}