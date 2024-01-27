<?php
/**
 * ------------------------------------------------------------------------
 * JA K2 To Com Content Migration Plugin for J25 & J34
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2011 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * ------------------------------------------------------------------------
 */
defined('_JEXEC') or die;
?>
<?php if(!empty($displayData['rec'])): ?>
<?php echo $displayData['rec']; ?>
<?php endif; ?>
<div class="tabbable tabs-left" id="permissions-sliders">
	<ul class="nav nav-tabs">
		<li class="active">
			<a data-toggle="tab" href="#sync-category">Category</a>
		</li>
    <?php if(!empty($displayData['exgroups'])): ?>
		<li class="">
			<a data-toggle="tab" href="#sync-exgroup">
				<?php echo JText::_('JA_K2TOCONTENT_EXTRA_FIELDS_TAB'); ?>
			</a>
		</li>
    <?php endif; ?>
	</ul>
	<div class="tab-content">
		<div id="sync-category" class="tab-pane active">
			<?php echo $displayData['categories']; ?>
		</div>

		<?php if(!empty($displayData['exgroups'])): ?>
			<div id="sync-exgroup" class="tab-pane">
			</p><?php echo JText::_('JA_K2TOCONTENT_MESSAGE_EX'); ?></p>
				<?php echo $displayData['exfields']; ?>
			</div>
		<?php endif; ?>
	</div>
</div>