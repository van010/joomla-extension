<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');

$tplparams = JFactory::getApplication()->getTemplate(true)->params;

$sitename  = $tplparams->get('sitename');
$slogan    = $tplparams->get('slogan', '');
$logotype  = $tplparams->get('logotype', 'text');
$logoimage = $tplparams->get('logoimage', 'templates/' . T3_TEMPLATE . '/images/logo-light.png');

if (!$sitename) {
	$sitename = JFactory::getConfig()->get('sitename');
}
?>

<div class="login-wrap">
  <div class="login <?php echo $this->pageclass_sfx?>">
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

  	<?php if (($this->params->get('logindescription_show') == 1 && str_replace(' ', '', $this->params->get('login_description')) != '') || $this->params->get('login_image') != '') : ?>
  	<div class="login-description">
  	<?php endif; ?>

  		<?php if($this->params->get('logindescription_show') == 1) : ?>
  			<?php echo $this->params->get('login_description'); ?>
  		<?php endif; ?>

  		<?php if ($this->params->get('login_image')!='') :?>
  			<img src="<?php echo $this->escape($this->params->get('login_image')); ?>" class="login-image" alt="<?php echo Text::_('COM_USER_LOGIN_IMAGE_ALT')?>"/>
  		<?php endif; ?>

  	<?php if (($this->params->get('logindescription_show') == 1 && str_replace(' ', '', $this->params->get('login_description')) != '') || $this->params->get('login_image') != '') : ?>
  	</div>
  	<?php endif; ?>

  	<form action="<?php echo JRoute::_('index.php?option=com_users&task=user.login'); ?>" method="post" class="form-horizontal">
			<div class="login-input-group">
				<?php foreach ($this->form->getFieldset('credentials') as $field): ?>
					<?php if (!$field->hidden): ?>
						<div class="login-input">
							<?php echo $field->label; ?>
							<?php echo $field->input; ?>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
				
				<?php $tfa = JPluginHelper::getPlugin('twofactorauth'); ?>
				<?php if (!is_null($tfa) && $tfa != array()): ?>
					<div class="login-input secretkey">
						<?php echo $this->form->getField('secretkey')->label; ?>
						<?php echo $this->form->getField('secretkey')->input; ?>
					</div>
				<?php endif; ?>
			
				<?php if (JPluginHelper::isEnabled('system', 'remember')) : ?>
					<div class="login-input remember">							
						<input id="remember" type="checkbox" name="remember" value="yes"/> 
						<label><?php echo JText::_(version_compare(JVERSION, '3.0', 'ge') ? 'COM_USERS_LOGIN_REMEMBER_ME' : 'JGLOBAL_REMEMBER_ME') ?></label>
					</div>
				<?php endif; ?>
			</div>
			
			<div class="login-submit">
				<button type="submit" class="btn btn-primary"><?php echo JText::_('JLOGIN'); ?></button>
			</div>
			<?php if ($this->params->get('login_redirect_url')) : ?>
					<input type="hidden" name="return" value="<?php echo base64_encode($this->params->get('login_redirect_url', $this->form->getValue('return'))); ?>" />
				<?php else : ?>
					<input type="hidden" name="return" value="<?php echo base64_encode($this->params->get('login_redirect_menuitem', $this->form->getValue('return'))); ?>" />
				<?php endif; ?>
			<?php echo JHtml::_('form.token'); ?>
  	</form>

  </div>

  <div class="other-links">
		<ul>
			<li>
				<a href="<?php echo JRoute::_('index.php?option=com_users&view=reset'); ?>">
					<?php echo Text::_('COM_USERS_LOGIN_RESET'); ?>
				</a>
			</li>
			<li>
				<a href="<?php echo JRoute::_('index.php?option=com_users&view=remind'); ?>">
					<?php echo Text::_('COM_USERS_LOGIN_REMIND'); ?>
				</a>
			</li>
			
			<?php $usersConfig = JComponentHelper::getParams('com_users');
			if ($usersConfig->get('allowUserRegistration')) : ?>
			<li>
				<a href="<?php echo JRoute::_('index.php?option=com_users&view=registration'); ?>">
					<?php echo Text::_('COM_USERS_LOGIN_REGISTER'); ?>
				</a>
			</li>
			<?php endif; ?>
		</ul>
  </div>

</div>