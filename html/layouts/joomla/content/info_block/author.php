<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
$item = $displayData['item'];
$author = ($item->created_by_alias ? $item->created_by_alias : $item->author);
?>

<dd class="createdby hasTooltip"  itemprop="author">
	<i class="fa fa-user"></i>
  <span itemprop="name">
		<?php if (!empty($displayData['item']->contact_link ) && $displayData['params']->get('link_author') == true) : ?>
			<?php echo JHtml::_('link', $displayData['item']->contact_link, $author, array('itemprop' => 'url')); ?>
		<?php else :?>
			<?php echo $author; ?>
		<?php endif; ?>
	</span>
	<span itemtype="https://schema.org/Organization" itemscope="" itemprop="publisher" style="display: none;">
      <span itemtype="https://schema.org/ImageObject" itemscope="" itemprop="logo">
        <img itemprop="url" alt="logo" src="<?php echo JURI::base(); ?>/templates/<?php echo JFactory::getApplication()->getTemplate() ?>/images/logo.png">
        <meta content="auto" itemprop="width">
        <meta content="auto" itemprop="height">
      </span>
      <meta content="<?php echo $author; ?>" itemprop="name">
    </span>
</dd>
