<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Contact\Site\Helper\RouteHelper;

jimport('joomla.html.html.bootstrap');

$cparams = JComponentHelper::getParams('com_media');
$tparams = $this->item->params;
$htag    = $tparams->get('show_page_heading') ? 'h2' : 'h1';

if(version_compare(JVERSION, '4', 'ge')) {
	$this->contact = $this->item;
	$canDo   = ContentHelper::getActions('com_contact', 'category', $this->item->catid);
	$canEdit = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by === Factory::getUser()->id);
}

$activehead = 'basic-details';
$activetitle = 'COM_CONTACT_DETAILS';
$showform = $showlinks = $showarticles = $showprofile = $showmisc = false;

if(($tparams->get('show_email_form') && ($this->contact->email_to || $this->contact->user_id))){
	$showform = true;
	$activehead = 'display-form';
	$activetitle = 'COM_CONTACT_EMAIL_FORM';
}

if ($tparams->get('show_links')){
	$showlinks = true;
	$activehead = 'display-links';
	$activetitle = 'COM_CONTACT_LINKS';	
}

if ($tparams->get('show_articles') && $this->contact->user_id && $this->contact->articles){
	$showarticles = true;
	$activehead = 'display-articles';
	$activetitle = 'JGLOBAL_ARTICLES';	
}

if ($tparams->get('show_profile') && $this->contact->user_id && JPluginHelper::isEnabled('user', 'profile')){
	$showprofile = true;
	$activehead = 'display-profile';
	$activetitle = 'COM_CONTACT_PROFILE';	
}

if ($this->contact->misc && $tparams->get('show_misc')){
	$showmisc = true;
	$activehead = 'display-misc';
	$activetitle = 'COM_CONTACT_OTHER_INFORMATION';	
}

?>

<div class="contact<?php echo $this->pageclass_sfx; ?>" itemscope itemtype="https://schema.org/Person">
	<?php if ($tparams->get('show_page_heading')) : ?>
		<h1>
			<?php echo $this->escape($tparams->get('page_heading')); ?>
		</h1>
	<?php endif; ?>
	<?php if ($this->contact->name && $tparams->get('show_name')) : ?>
		<div class="page-header">
			<<?php echo $htag; ?>>
				<?php if ($this->item->published == 0) : ?>
					<span class="label label-warning"><?php echo Text::_('JUNPUBLISHED'); ?></span>
				<?php endif; ?>
				<span class="contact-name" itemprop="name"><?php echo $this->contact->name; ?></span>
			</<?php echo $htag; ?>>
		</div>
	<?php endif;  ?>
	<?php if ($tparams->get('show_contact_category') === 'show_no_link') : ?>
		<h3>
			<span class="contact-category"><?php echo $this->contact->category_title; ?></span>
		</h3>
	<?php endif; ?>
	<?php if ($tparams->get('show_contact_category') === 'show_with_link') : ?>
		<?php $contactLink = ContactHelperRoute::getCategoryRoute($this->contact->catid); ?>
		<h3>
			<span class="contact-category"><a href="<?php echo $contactLink; ?>">
				<?php echo $this->escape($this->contact->category_title); ?></a>
			</span>
		</h3>
	<?php endif; ?>

	<?php echo $this->item->event->afterDisplayTitle; ?>

	<?php echo $this->item->event->beforeDisplayContent; ?>
	
	<?php if ($tparams->get('show_contact_list') && count($this->contacts) > 1) : ?>
		<form action="#" method="get" name="selectForm" id="selectForm">
			<?php echo Text::_('COM_CONTACT_SELECT_CONTACT'); ?>
			<?php echo JHtml::_('select.genericlist', $this->contacts, 'id', 'class="input" onchange="document.location.href = this.value"', 'link', 'name', $this->contact->link);?>
		</form>
	<?php endif; ?>

	<?php if ($tparams->get('show_tags', 1) && !empty($this->item->tags)) : ?>
		<?php $this->item->tagLayout = new JLayoutFile('joomla.content.tags'); ?>
		<?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
	<?php endif; ?>

	<?php if(version_compare(JVERSION, '4', 'ge')) {
		$presentation_style = 'plain';
		} else {
		$presentation_style = $tparams->get('presentation_style');
	}; ?>
	
	<?php if ($presentation_style == 'sliders') : ?>
		<div class="accordion" id="slide-contact">
	<?php endif; ?>
	<?php if ($presentation_style == 'tabs'):?>
		<ul class="nav nav-tabs" id="contact-tab-head">
				<?php if ($showform) : ?><li class="active"><a data-toggle="tab" href="#display-form"><?php echo Text::_('COM_CONTACT_EMAIL_FORM'); ?></a></li><?php endif; ?>
				<?php if ($showlinks) : ?><li<?php echo $activehead == 'display-links' ? ' class="active"' : '' ?>><a data-toggle="tab" href="#display-links"><?php echo Text::_('COM_CONTACT_LINKS'); ?></a></li><?php endif; ?>
				<?php if ($showarticles) : ?><li<?php echo $activehead == 'display-articles' ? ' class="active"' : '' ?>><a data-toggle="tab" href="#display-articles"><?php echo Text::_('JGLOBAL_ARTICLES'); ?></a></li><?php endif; ?>
				<?php if ($showprofile) : ?><li<?php echo $activehead == 'display-profile' ? ' class="active"' : '' ?>><a data-toggle="tab" href="#display-profile"><?php echo Text::_('COM_CONTACT_PROFILE'); ?></a></li><?php endif; ?>
				<?php if ($showmisc) : ?><li<?php echo $activehead == 'display-misc' ? ' class="active"' : '' ?>><a data-toggle="tab" href="#display-misc"><?php echo Text::_('COM_CONTACT_OTHER_INFORMATION'); ?></a></li><?php endif; ?>
				<li<?php echo $activehead == 'basic-details' ? ' class="active"' : '' ?>><a data-toggle="tab" href="#basic-details"><?php echo Text::_('COM_CONTACT_DETAILS'); ?></a></li>
		</ul>
		<div class="tab-content" id="contact-tabs">
	<?php endif; ?>
	
	<?php if ($showform) : ?>

		<?php if ($presentation_style=='sliders'):?>
			<div class="accordion-group">
				<div class="accordion-heading">
					<a class="accordion-toggle" data-toggle="collapse" data-parent="#slide-contact" href="#display-form">
					<?php echo Text::_('COM_CONTACT_EMAIL_FORM');?>
					</a>
				</div>
				<div id="display-form" class="accordion-body collapse in">
					<div class="accordion-inner">
		<?php endif; ?>
		<?php if ($presentation_style == 'tabs') : ?>
			<div id="display-form" class="tab-pane active">
		<?php endif; ?>
		<?php if ($presentation_style=='plain'):?>
			<?php  echo '<h3>'. Text::_('COM_CONTACT_EMAIL_FORM').'</h3>';  ?>
		<?php endif; ?>

		<?php  echo $this->loadTemplate('form');  ?>

		<?php if ($presentation_style=='sliders'):?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php if ($presentation_style == 'tabs') : ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
	
	<?php if ($showlinks) : ?>
		<?php echo $this->loadTemplate('links'); ?>
	<?php endif; ?>
		
	<?php if ($showarticles) : ?>
		<?php if ($presentation_style=='sliders'):?>
			<div class="accordion-group">
				<div class="accordion-heading">
					<a class="accordion-toggle" data-toggle="collapse" data-parent="#slide-contact" href="#display-articles">
					<?php echo Text::_('JGLOBAL_ARTICLES');?>
					</a>
				</div>
				<div id="display-articles" class="accordion-body collapse<?php echo $activehead == 'display-links' ? ' in' : '' ?>">
					<div class="accordion-inner">
		<?php endif; ?>
		<?php if ($presentation_style == 'tabs') : ?>
			<div id="display-articles" class="tab-pane<?php echo $activehead == 'display-links' ? ' active' : '' ?>">
		<?php endif; ?>
		<?php if  ($presentation_style=='plain'):?>
			<?php echo '<h3>'. Text::_('JGLOBAL_ARTICLES').'</h3>'; ?>
		<?php endif; ?>
			<?php echo $this->loadTemplate('articles'); ?>
		<?php if ($presentation_style=='sliders'):?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php if ($presentation_style == 'tabs') : ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ($tparams->get('show_user_custom_fields') && $this->contactUser) : ?>
		<?php echo $this->loadTemplate('user_custom_fields'); ?>
	<?php endif; ?>

	<?php if ($showprofile) : ?>
		<?php if ($presentation_style=='sliders'):?>
			<div class="accordion-group">
				<div class="accordion-heading">
					<a class="accordion-toggle" data-toggle="collapse" data-parent="#slide-contact" href="#display-profile">
					<?php echo Text::_('COM_CONTACT_PROFILE');?>
					</a>
				</div>
				<div id="display-profile" class="accordion-body collapse<?php echo $activehead == 'display-profile' ? ' in' : '' ?>">
					<div class="accordion-inner">
		<?php endif; ?>
		<?php if ($presentation_style == 'tabs') : ?>
			<div id="display-profile" class="tab-pane<?php echo $activehead == 'display-profile' ? ' active' : '' ?>">
		<?php endif; ?>
		<?php if ($presentation_style=='plain'):?>
			<?php echo '<h3>'. Text::_('COM_CONTACT_PROFILE').'</h3>'; ?>
		<?php endif; ?>
		<?php echo $this->loadTemplate('profile'); ?>
		<?php if ($presentation_style=='sliders'):?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php if ($presentation_style == 'tabs') : ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ($showmisc) : ?>
		<?php if ($presentation_style=='sliders'):?>
			<div class="accordion-group">
				<div class="accordion-heading">
					<a class="accordion-toggle" data-toggle="collapse" data-parent="#slide-contact" href="#display-misc">
					<?php echo Text::_('COM_CONTACT_OTHER_INFORMATION');?>
					</a>
				</div>
				<div id="display-misc" class="accordion-body collapse<?php echo $activehead == 'display-misc' ? ' in' : '' ?>">
					<div class="accordion-inner">
		<?php endif; ?>
		<?php if ($presentation_style == 'tabs') : ?>
			<div id="display-misc" class="tab-pane<?php echo $activehead == 'display-misc' ? ' active' : '' ?>">
		<?php endif; ?>
		<?php if ($presentation_style=='plain'):?>
			<?php echo '<h3>'. Text::_('COM_CONTACT_OTHER_INFORMATION').'</h3>'; ?>
		<?php endif; ?>
				<div class="contact-miscinfo">
					<dl class="dl-horizontal">
						<dt>
							<span class="<?php echo $tparams->get('marker_class'); ?>">
								<?php echo $tparams->get('marker_misc'); ?>
							</span>
						</dt>
						<dd>
							<span class="contact-misc">
								<?php echo $this->contact->misc; ?>
							</span>
						</dd>
					</dl>
				</div>
		<?php if ($presentation_style=='sliders'):?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php if ($presentation_style == 'tabs') : ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<!-- BASIC DETAIL -->
	<?php if ($presentation_style=='sliders'):?>
		<div class="accordion-group">
			<div class="accordion-heading">
				<a class="accordion-toggle" data-toggle="collapse" data-parent="#slide-contact" href="#basic-details">
				<?php echo Text::_('COM_CONTACT_DETAILS');?>
				</a>
			</div>
			<div id="basic-details" class="accordion-body collapse<?php echo $activehead == 'basic-details' ? ' in' : '' ?>">
				<div class="accordion-inner">
				<?php endif;?>

				<?php if ($presentation_style == 'tabs') : ?>
				<div id="basic-details" class="tab-pane <?php echo $activehead == 'basic-details' ? ' active' : '' ?>">
				<?php endif; ?>
				
				<?php if ($presentation_style == 'plain') : ?>
				<?php  echo '<h3>'. Text::_('COM_CONTACT_DETAILS').'</h3>';  ?>
				<?php endif; ?>

				<?php if ($this->contact->image && $tparams->get('show_image')) : ?>
				<div class="thumbnail pull-right">
					<?php echo JHtml::_('image', $this->contact->image, Text::_('COM_CONTACT_IMAGE_DETAILS'), array('align' => 'middle')); ?>
				</div>
				<?php endif; ?>

				<?php if ($this->contact->con_position && $tparams->get('show_position')) : ?>
				<dl class="contact-position dl-horizontal">
					<dd>
						<?php echo $this->contact->con_position; ?>
					</dd>
				</dl>
				<?php endif; ?>

				<?php echo $this->loadTemplate('address'); ?>

				<?php if ($tparams->get('allow_vcard')) :	?>
					<?php echo Text::_('COM_CONTACT_DOWNLOAD_INFORMATION_AS');?>
						<a href="<?php echo JRoute::_('index.php?option=com_contact&amp;view=contact&amp;id='.$this->contact->id . '&amp;format=vcf'); ?>">
						<?php echo Text::_('COM_CONTACT_VCARD');?></a>
				<?php endif; ?>

	<?php if ($presentation_style=='sliders'):?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($presentation_style == 'tabs') : ?>
		</div>
	<?php endif; ?>
	<!-- //BASIC DETAIL -->

	<?php if ($presentation_style=='sliders'):?>
			<script type="text/javascript">
				(function($){
					$('#slide-contact').collapse({ parent: false, toggle: true, active: 'basic-details'});
				})(jQuery);
			</script>
		</div>
	<?php endif; ?>
	<?php if ($presentation_style == 'tabs') : ?>
		</div>
	<?php endif; ?>
	<?php echo $this->item->event->afterDisplayContent; ?>
</div>
