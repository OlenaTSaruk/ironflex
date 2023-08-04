{*
 * 2007-2020 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
<div class="panel"><h3><i class="icon-list-ul"></i> {l s='List of blocks' mod='hm_contentanywhere'}
<span class="panel-heading-action">
<a id="desc-product-new" class="list-toolbar-btn" href="{$link->getAdminLink('AdminModules')}&configure=hm_contentanywhere&blockForm&id_block=0">
	<span title="{l s='Add new' d='Admin.Actions'}" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s='Add new' d='Admin.Actions'}" data-html="true">
		<i class="process-icon-new "></i>
	</span>
</a>
</span>
</h3>
	{if $blocksByHook}
		<div id="block-list">
			{foreach from=$blocksByHook item=blockByHook}
				<div class="panel">
					<h3>{$blockByHook.name}</h3>
					<div class="block-list-hook">
						{foreach from=$blockByHook.blocks item=block}
							<div id="slides_{$block.id_block}" class="panel">
								<div class="row">
									<div class="col-md-1">
										<span><i class="icon-arrows "></i></span>
									</div>
									<div class="col-md-2">
										{if $block.image.$id_lang}
											<img src="{$image_baseurl}{$block.image.$id_lang}" alt="{$block.title.$id_lang}" class="img-thumbnail" />
										{/if}
									</div>
									<div class="col-md-9">
										<div class="btn-group-action pull-right">
											{$block.status}
											<a class="btn btn-default" href="{$link->getAdminLink('AdminModules')}&configure=hm_contentanywhere&blockForm&id_block={$block.id_block}"><i class="icon-edit"></i> {l s='Edit' d='Admin.Actions'}</a>
											<a class="btn btn-default" href="{$link->getAdminLink('AdminModules')}&configure=hm_contentanywhere&blockDelete&id_block={$block.id_block}"><i class="icon-trash"></i> {l s='Delete' d='Admin.Actions'}</a>
										</div>
										{if $block.title.$id_lang}<h4>{$block.title.$id_lang}</h4>{/if}
										<p>{$block.content.$id_lang|strip_tags|truncate:300}</p>
									</div>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			{/foreach}
		</div>
	{/if}
	{*<div id="slidesContent">
		<div id="slides">
			{foreach from=$slides item=slide}
				<div id="slides_{$slide.id_slide}" class="panel">
					<div class="row">
						<div class="col-lg-1">
							<span><i class="icon-arrows "></i></span>
						</div>
						<div class="col-md-3">
							<img src="{$image_baseurl}{$slide.image}" alt="{$slide.title}" class="img-thumbnail" />
						</div>
						<div class="col-md-8">

							<div class="btn-group-action pull-right">
								{$slide.status}

								<a class="btn btn-default"
									href="{$link->getAdminLink('AdminModules')}&configure=ps_imageslider&id_slide={$slide.id_slide}">
									<i class="icon-edit"></i>
									{l s='Edit' d='Admin.Actions'}
								</a>
								<a class="btn btn-default"
									href="{$link->getAdminLink('AdminModules')}&configure=ps_imageslider&delete_id_slide={$slide.id_slide}">
									<i class="icon-trash"></i>
									{l s='Delete' d='Admin.Actions'}
								</a>
							</div>
						</div>
					</div>
				</div>
			{/foreach}
		</div>
	</div>*}
</div>
