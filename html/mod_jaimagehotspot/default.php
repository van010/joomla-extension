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
?>
<div class="jai-map-wrap<?php echo $params->get( 'moduleclass_sfx' );?>" id="ja-imagesmap<?php echo $module->id;?>">
   
	
    <?php if(in_array($dropdownPosition, array('top-left', 'top-right', 'middle-left', 'middle-right'))): ?>
		<?php require $layoutSelect; ?>
    <?php endif; ?>
    <div class="jai-map-container <?php echo $displaytooltips ? 'always-popup' : 'hover-popup'; ?>">
        <?php
        foreach($description as $i => $des):
            $classpoint = isset($des->classpoint) ? $des->classpoint:'';
			if(isset($des->ptype) && !empty($des->ptype)) $classpoint .= ' ja-marker-'.$des->ptype;
			if($des->offsety > 100) $des->offsety = 95;
			if($des->offsetx > 100) $des->offsetx = 95;
		?>
			<?php if(!empty($des->title) || !empty($des->details)): ?>
			<a style="top:<?php echo $des->offsety; ?>%;left:<?php echo $des->offsetx; ?>%"
			   class="point <?php echo $classpoint.' point'.$i; ?>"
			   href="javascript:void(0)"
			   id="<?php echo 'ja-marker-'.$des->imgid; ?>"
			   data-toggle="popover"
			   data-placement="top"
			   title="<?php echo $des->title; ?>"
			   data-content="<?php echo htmlspecialchars($des->details, ENT_COMPAT, 'UTF-8'); ?>" data-html="true">
				<span class="hidden">Point</span>
			</a>
			<?php else: ?>
			<a style="top:<?php echo $des->offsety; ?>%;left:<?php echo $des->offsetx; ?>%"
			   class="point <?php echo $classpoint.' point'.$i; ?>"
			   href="javascript:void(0)"
			   id="<?php echo 'ja-marker-'.$des->imgid; ?>"
			   title="">
				<span class="hidden">Point</span>
			</a>
			<?php endif; ?>
        <?php endforeach; ?>
        <img src="<?php echo $imgpath;?>" alt=""/>
    </div>
	
	<?php if(in_array($dropdownPosition, array('bottom-left', 'bottom-right'))): ?>
		<?php require $layoutSelect; ?>
    <?php endif; ?>

	 <?php if($modules_des):?>
	<div class="jai-map-description">
	<?php echo $modules_des;?>
	</div>
	<?php endif;?>
	
	
	
</div>