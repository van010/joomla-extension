<?php
/**
 * ------------------------------------------------------------------------
 * JA Biz Template
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2011 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites:  http://www.joomlart.com -  http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
 */

defined('_JEXEC') or die('Restricted access'); 
$rtl = 0;
$doc = JFactory::getDocument();
if($doc->direction=='rtl') $rtl = 1;
$padding_prop = $rtl?'padding-right':'padding-left';

$more = false;
if(!empty($list)){
	$more = $list[0];
}
?>

<?php if($more): ?>
	<div class="module-more">
		<a href="<?php echo JRoute::_(ContentHelperRoute::getCategoryRoute($more->category_alias ? ($more->catid.':'.$more->category_alias) : $more->catid)) ?>"><?php echo JText::_('TPL_MORE') ?></a>
	</div>
<?php endif ?>

<div id="ja-bulletin">
	<ul class="ja-bullettin<?php echo $params->get('moduleclass_sfx'); ?> clearfix">
	<?php if(!empty($list)): ?>
	<?php foreach ($list as $item) : ?>
		<li class="clearfix">
				<?php 
				$padding = ($params->get( 'show_image') && $item->image!='')?"style=\"$padding_prop:".($params->get('width')+10)."px\"":"";				
				if (isset($item->image)) : 
				?>
				<?php if( $item->image ) : ?>
					<a href="<?php echo $item->link; ?>" class="mostread<?php echo $params->get('moduleclass_sfx'); ?>-image">
						<?php echo $item->image; ?>
					</a>
				<?php endif; ?>
				<?php endif; ?>
				<div <?php echo $padding;?>>
				<?php if (isset($item->date)) : ?>
				 <span class="creatdate"><?php echo JHTML::_('date', $item->date, JText::_('DATE_FORMAT_LC3')); ?></span>
				<?php endif; ?>
				
				<a href="<?php echo $item->link; ?>" class="mostread<?php echo $params->get('moduleclass_sfx'); ?>"><?php echo $item->text; ?></a>
				<?php if ($showcreater) : ?>
						<br/><span class="createby"><?php echo $item->creater;?></span> 
				<?php endif; ?>
				
				<?php if (isset($item->hits)) : ?>
					<span class="item-hits">
					<?php if($useCustomText):
						 echo JText::_($customText);
					endif;
					?>
					<?php echo $item->hits; ?>
					</span>
				<?php endif; ?>
				<?php if ($showreadmore) : ?>
					<br/><a href="<?php echo $item->link; ?>" class="readon" title="<?php echo JText::sprintf('READ_MORE');?>"><?php echo JText::sprintf('READ_MORE');?></a>
				<?php endif; ?>
				</div>

		</li>
	<?php endforeach; ?>
	<?php endif; ?>
	</ul>
</div>