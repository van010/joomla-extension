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

defined('_JEXEC') or die;

$app = JFactory::getApplication();
$theme = JFactory::getApplication()->getTemplate(true)->params->get('theme', '');
$twofactormethods = false;

if(version_compare(JVERSION, '3.2', 'ge')){
	//load userhelper
	require_once JPATH_ADMINISTRATOR . '/components/com_users/helpers/users.php';

	//just for sure
	if(method_exists('UsersHelper', 'getTwoFactorMethods')){
		$twofactormethods = UsersHelper::getTwoFactorMethods();
	}

	// Add JavaScript Frameworks
	JHtml::_('bootstrap.framework');
}

//check if t3 plugin is existed
if(!defined('T3')){
	if (JError::$legacy) {
		JError::setErrorHandling(E_ERROR, 'die');
		JError::raiseError(500, JText::_('T3_MISSING_T3_PLUGIN'));
		exit;
	} else {
		throw new Exception(JText::_('T3_MISSING_T3_PLUGIN'), 500);
	}
}

$t3app = T3::getApp($this);
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/offline.css" type="text/css" />
	<?php if($theme && is_file(T3_TEMPLATE_PATH . '/css/themes/' . $theme . '/offline.css')):?>
	<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/themes/<?php echo $theme ?>/offline.css" type="text/css" />
	<?php endif; ?>
</head>
<body>

	<div id="frame" class="outline">
		<jdoc:include type="message" />
		
		<?php if ($app->getCfg('offline_image') && file_exists($app->getCfg('offline_image'))) : ?>
		<img src="<?php echo $app->getCfg('offline_image'); ?>" alt="<?php echo htmlspecialchars($app->getCfg('sitename')); ?>" />
		<?php endif; ?>

		<div class="offline-content">
			<h1>
				<?php echo htmlspecialchars($app->getCfg('sitename')); ?>
			</h1>

			<?php if ($app->getCfg('display_offline_message', 1) == 1 && str_replace(' ', '', $app->getCfg('offline_message')) != '') : ?>
				<p class="offline-message">
					<?php echo $app->getCfg('offline_message'); ?>
				</p>
				<?php elseif ($app->getCfg('display_offline_message', 1) == 2 && str_replace(' ', '', JText::_('JOFFLINE_MESSAGE')) != '') : ?>
				<p class="offline-message">
					<?php echo JText::_('JOFFLINE_MESSAGE'); ?>
				</p>
			<?php  endif; ?>

			<form action="<?php echo JRoute::_('index.php', true); ?>" method="post" id="form-login">
				<fieldset class="input">
					<p id="form-login-username">
						<label for="username"><?php echo JText::_('JGLOBAL_USERNAME') ?></label>
						<input name="username" id="username" type="text" class="inputbox" alt="<?php echo JText::_('JGLOBAL_USERNAME') ?>" size="18" />
					</p>
					<p id="form-login-password">
						<label for="passwd"><?php echo JText::_('JGLOBAL_PASSWORD') ?></label>
						<input type="password" name="password" class="inputbox" size="18" alt="<?php echo JText::_('JGLOBAL_PASSWORD') ?>" id="passwd" />
					</p>
					<?php if ($twofactormethods && count($twofactormethods) > 1) : ?>
					<p id="form-login-secretkey">
						<label for="secretkey"><?php echo JText::_('JGLOBAL_SECRETKEY') ?></label>
						<input type="text" name="secretkey" class="inputbox" size="18" alt="<?php echo JText::_('JGLOBAL_SECRETKEY') ?>" id="secretkey" />
					</p>
					<?php endif; ?>
					<?php if (JPluginHelper::isEnabled('system', 'remember')) : ?>
					<p id="form-login-remember">
						<input type="checkbox" name="remember" class="inputbox" value="yes" alt="<?php echo JText::_('JGLOBAL_REMEMBER_ME') ?>" id="remember" />
						<label for="remember"><?php echo JText::_('JGLOBAL_REMEMBER_ME') ?></label>
					</p>
					<?php endif; ?>
					<p id="submit-buton">
						<label>&nbsp;</label>
						<input type="submit" name="Submit" class="button login" value="<?php echo JText::_('JLOGIN') ?>" />
					</p>
					<input type="hidden" name="option" value="com_users" />
					<input type="hidden" name="task" value="user.login" />
					<input type="hidden" name="return" value="<?php echo base64_encode(JUri::base()) ?>" />
					<?php echo JHtml::_('form.token'); ?>
				</fieldset>
			</form>
		</div>
	</div>
</body>
</html>
