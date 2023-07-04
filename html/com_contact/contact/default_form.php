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

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');

if (isset($this->error)) : ?>
	<div class="contact-error">
		<?php echo $this->error; ?>
	</div>
<?php endif; ?>

<div class="contact-form">
	<form id="contact-form" action="<?php echo JRoute::_('index.php'); ?>" method="post" class="form-validate form-horizontal">
		<fieldset>
			<legend><?php echo JText::_('COM_CONTACT_FORM_LABEL'); ?></legend>
			<div class="control-group-header row clearfix">
				<div class="control-group col-md-12">
					<div class="row">
						 <div class="control-contact col-md-6 col-xs-12">
							<?php echo $this->form->getLabel('contact_name'); ?>
							<?php echo $this->form->getInput('contact_name'); ?>
						 </div>
						 
						 <div class="control-contact col-md-6 col-xs-12">
							<?php echo $this->form->getLabel('contact_email'); ?>
							<?php echo $this->form->getInput('contact_email'); ?>
						 </div>			
					</div>
					
				</div>
				<div class="control-group col-md-12 col-xs-12">
					<div class="control-contact">
						<?php echo $this->form->getLabel('contact_subject'); ?>
						<?php echo $this->form->getInput('contact_subject'); ?>
					 </div>
				    <div class="control-contact">
					<?php echo $this->form->getLabel('contact_message'); ?>
					<?php echo $this->form->getInput('contact_message'); ?>
				     </div>		
				</div>
				<div class="re-captcha col-md-12 col-xs-12">
					<?php //Dynamically load any additional fields from plugins. ?>
			<?php foreach ($this->form->getFieldsets() as $fieldset) : ?>
				<?php if ($fieldset->name != 'contact'):?>
					<?php if ($fieldset->name === 'captcha' && !$this->captchaEnabled) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php $fields = $this->form->getFieldset($fieldset->name); ?>
					<?php if (count($fields)) : ?>
						<fieldset>
							<?php if (isset($fieldset->label) && ($legend = trim(Text::_($fieldset->label))) !== '') : ?>
								<legend><?php echo $legend; ?></legend>
							<?php endif; ?>
							<?php foreach ($fields as $field) : ?>
								<?php echo $field->renderField(); ?>
							<?php endforeach; ?>
						</fieldset>
					<?php endif; ?>
				<?php endif ?>
			<?php endforeach;?>
				</div>
			</div>
			
			<div class="control-group-footer row clearfix">
				<?php if ($this->params->get('show_email_copy')) { ?>			
					<div class="control-group col-md-6 col-sm-6 col-xs-12">
						<?php echo $this->form->getInput('contact_email_copy'); ?>
						<?php echo $this->form->getLabel('contact_email_copy'); ?>
					</div>
				<?php } ?>
				<div class="control-group col-md-6 col-sm-6 col-xs-12">
					<button class="btn btn-primary validate" type="submit"><?php echo Text::_('COM_CONTACT_CONTACT_SEND'); ?></button>
					<input type="hidden" name="option" value="com_contact" />
					<input type="hidden" name="task" value="contact.submit" />
					<input type="hidden" name="return" value="<?php echo $this->return_page;?>" />
					<input type="hidden" name="id" value="<?php echo $this->contact->slug; ?>" />
					<?php echo JHtml::_('form.token'); ?>
				</div>
			</div>
		</fieldset>
	</form>
</div>
