<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\String\PunycodeHelper;

/**
 * Marker_class: Class based on the selection of text, none, or icons
 * jicon-text, jicon-none, jicon-icon
 */
?>
<dl class="row contact-address dl-horizontal">

<div class="col-lg-8 col-md-9 col-xs-12">

	<div class="row">

	<?php if ($this->contact->email_to && $this->params->get('show_email')) : ?>
		<div class="contact-address col-md-4 col-xs-12">
			<div class="<?php echo $this->params->get('marker_class'); ?>" >
				<?php echo nl2br($this->params->get('marker_email')); ?>
				<span class="jicons-desc"><?php echo Text::_('TPL_CONTACT_EMAIL_DESC') ?></span>
			</div>
			<div class="contact-emailto">
				<i class="icon-globe">&nbsp;</i>
				<?php echo $this->contact->email_to; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($this->contact->telephone && $this->params->get('show_telephone')) : ?>
		<div class="contact-address col-md-4 col-xs-12">
			<div class="<?php echo $this->params->get('marker_class'); ?>" >
				<?php echo $this->params->get('marker_telephone'); ?>
				<span class="jicons-desc"><?php echo Text::_('TPL_CONTACT_TEL_DESC') ?></span>
			</div>
			<div class="contact-telephone">
				<i class="icon-print">&nbsp;</i>
				<?php echo nl2br($this->contact->telephone); ?>
			</div>
		</div>
	<?php endif; ?>
	<?php if ($this->contact->fax && $this->params->get('show_fax')) : ?>
		<div class="contact-address col-md-4 col-xs-12">
			<div class="<?php echo $this->params->get('marker_class'); ?>" >
				<i class="icon-mobile-phone">&nbsp;</i>
				<?php echo $this->params->get('marker_fax'); ?>
				<span class="jicons-desc"><?php echo Text::_('TPL_CONTACT_FAX_DESC') ?></span>
			</div>
			<div class="contact-fax">
				<i class="icon-phone">&nbsp;</i>
				<?php echo nl2br($this->contact->fax); ?>
			</div>
		</div>
	<?php endif; ?>
	<?php if ($this->contact->mobile && $this->params->get('show_mobile')) :?>
		<div class="contact-address col-md-4 col-xs-12">
			<div class="<?php echo $this->params->get('marker_class'); ?>" >
				<?php echo $this->params->get('marker_mobile'); ?>
				<span class="jicons-desc"><?php echo Text::_('TPL_CONTACT_MOBILE_DESC') ?></span>
			</div>
			<div class="contact-mobile">
				<i class="icon-phone">&nbsp;</i>
				<?php echo nl2br($this->contact->mobile); ?>
			</div>
		</div>
	<?php endif; ?>
	<?php if ($this->contact->webpage && $this->params->get('show_webpage')) : ?>	
		<div class="contact-address">
			<div class="<?php echo $this->params->get('marker_class'); ?>" >
				<?php echo Text::_('TPL_LINKS') ?>:
				<span class="jicons-desc"><?php echo Text::_('TPL_CONTACT_LINK_DESC') ?></span>
			</div>
			<div class="contact-webpage">
				<i class="icon-anchor">&nbsp;</i>
				<a href="<?php echo $this->contact->webpage; ?>" target="_blank">
				<?php echo $this->contact->webpage; ?></a>
			</div>
		</div>
	<?php endif; ?>
	</div>
</div>

<div class="contact-address col-lg-4  col-md-3 col-xs-12">
  <div class="address-info">
	<?php if ($this->params->get('address_check') > 0) : ?>
		<span class="<?php echo $this->params->get('marker_class'); ?>" >
			<?php echo $this->params->get('marker_address'); ?>
		</span>
		<address>
	<?php endif; ?>
	<?php if ($this->contact->address && $this->params->get('show_street_address')) : ?>
		<span class="contact-street">
			<i class="icon-home">&nbsp;</i>
			<?php echo nl2br($this->contact->address); ?>
		</span>
	<?php endif; ?>
	<?php if ($this->contact->suburb && $this->params->get('show_suburb')) : ?>
		<span class="contact-suburb">
			<?php echo $this->contact->suburb; ?>
		</span>
	<?php endif; ?>
	<?php if ($this->contact->state && $this->params->get('show_state')) : ?>
		<span class="contact-state">
			<?php echo $this->contact->state; ?>
		</span>
	<?php endif; ?>
	<?php if ($this->contact->postcode && $this->params->get('show_postcode')) : ?>
		<span class="contact-postcode">
			<?php echo $this->contact->postcode; ?>
		</span>
	<?php endif; ?>
	<?php if ($this->contact->country && $this->params->get('show_country')) : ?>
		<span class="contact-country">
			<?php echo $this->contact->country; ?>
		</span>
	<?php endif; ?>

	<?php if ($this->params->get('address_check') > 0) : ?>
		</address>
	<?php endif; ?>
  </div>
</div>
</dl>
