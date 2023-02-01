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

JHtml::_('behavior.core');

?>

<div class="btn-group">
  <button type="button" class="btn btn-small btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <?php echo JText::_('JAK2_MIGRATION_ACTION'); ?> <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">
    <li><a href="#" data-toggle="modal" onclick="jQuery( '#ja-migrator-modal' ).modal('show'); jQuery(document.adminForm).attr('target', 'ja-migrator-form'); Joomla.submitbutton('article.importk2'); return true;"><?php echo JText::_('JAK2_MIGRATION_IMPORT_CONTENT'); ?></a></li>
    <li><a href="#" data-toggle="modal" onclick="jQuery( '#ja-migrator-modal' ).modal('show'); jQuery(document.adminForm).attr('target', 'ja-migrator-form'); Joomla.submitbutton('article.importk2extra'); return true;"><?php echo JText::_('JAK2_MIGRATION_IMPORT_CONTENT_EXTRA'); ?></a></li>
    <li role="separator" class="divider"></li>
    <li><a href="#" data-toggle="modal" onclick="jQuery( '#ja-migrator-modal' ).modal('show'); jQuery(document.adminForm).attr('target', 'ja-migrator-form'); Joomla.submitbutton('article.recontent'); return true;"><?php echo JText::_('JAK2_MIGRATION_RESET_CONTENT'); ?></a></li>
  </ul>
</div>