<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$tplparams = JFactory::getApplication()->getTemplate(true)->params;
$sitename  = $tplparams->get('sitename');
$slogan    = $tplparams->get('slogan', '');
$logotype  = $tplparams->get('logotype', 'text');
$logoimage = $tplparams->get('logoimage', 'templates/' . T3_TEMPLATE . '/images/logo-light.png');

if (!$sitename) {
	$sitename = JFactory::getConfig()->get('sitename');
}
?>
<div class="registration-wrap">
	<div class="registration-complete<?php echo $this->pageclass_sfx;?>">
		<div class="page-header">
      <div class="logo-image">
        <a href="<?php echo JURI::base(true) ?>" title="<?php echo strip_tags($sitename) ?>">
          <img class="logo-img" src="<?php echo JURI::base(true) . '/' . $logoimage ?>" alt="<?php echo strip_tags($sitename) ?>" />
        </a>
      </div>
			<?php if ($this->params->get('show_page_heading')) : ?>
			<h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
			<?php endif; ?>
		</div>
		
		<div class="form-registration">
			<div class="form-actions row">
				<div class="form-input">
					<a class="btn back pull-left" href="<?php echo JURI::base(true) ?>" title="<?php echo JText::_('JBACK');?>"><?php echo JText::_('JBACK');?></a>
				</div>
			</div>
		</div>
	</div>
</div>
