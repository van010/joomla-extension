<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

JHtml::_('behavior.keepalive');
if(version_compare(JVERSION, '3.0', 'lt')){
	JHtml::_('behavior.tooltip');
}
JHtml::_('behavior.formvalidation');

$tplparams = JFactory::getApplication()->getTemplate(true)->params;

$sitename  = $tplparams->get('sitename');
$slogan    = $tplparams->get('slogan', '');
$logotype  = $tplparams->get('logotype', 'text');
$logoimage = $tplparams->get('logoimage', 'templates/' . T3_TEMPLATE . '/images/logo-light.png');

if (!$sitename) {
	$sitename = JFactory::getConfig()->get('sitename');
}

?>
<div class="registration<?php echo $this->pageclass_sfx; ?>">
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
		
	<form id="member-registration" action="<?php echo Route::_('index.php?option=com_users&task=registration.register'); ?>" method="post" class="form-validate form-horizontal well" enctype="multipart/form-data">
		<?php // Iterate through the form fieldsets and display each one. ?>
		<?php foreach ($this->form->getFieldsets() as $fieldset) : ?>
			<?php $fields = $this->form->getFieldset($fieldset->name); ?>
			<?php if (count($fields)) : ?>
				<fieldset>
					<?php // If the fieldset has a label set, display it as the legend. ?>
					<?php if (isset($fieldset->label)) : ?>
						<legend><?php echo Text::_($fieldset->label); ?></legend>
					<?php endif; ?>
					<?php echo $this->form->renderFieldset($fieldset->name); ?>
				</fieldset>
			<?php endif; ?>
		<?php endforeach; ?>
		<div class="control-group">
			<div class="btn-group">
				<button type="submit" class="btn btn-primary validate">
					<?php echo Text::_('JREGISTER'); ?>
				</button>
				<a class="btn cancel" href="<?php echo JRoute::_(''); ?>" title="<?php echo Text::_('JCANCEL'); ?>">
					<?php echo Text::_('JCANCEL'); ?>
				</a>
				<input type="hidden" name="option" value="com_users" />
				<input type="hidden" name="task" value="registration.register" />
			</div>
		</div>
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
