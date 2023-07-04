<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_search
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
$moduleclass_sfx = $params->get('moduleclass_sfx','');
?>
<form class="form-search" action="<?php echo JRoute::_('index.php');?>" method="post">
	<div class="search<?php echo $moduleclass_sfx ?>">
		<?php
			$output = '<label for="mod-search-searchword">'.$label.'</label><i class="fa fa-search"></i><input name="searchword" id="mod-search-searchword" maxlength="'.$maxlength.'"  class="form-control '.$moduleclass_sfx.'" type="text" size="'.$width.'" />';

			if ($button) :
				if ($imagebutton) :
					$button = ' <input type="image" value="' . $button_text . '" class="button' . $moduleclass_sfx.'" src="' . $img . '" onclick="this.form.searchword.focus();"/>';
				else :
					$button = ' <button class="button' . $moduleclass_sfx . ' btn btn-primary" onclick="this.form.searchword.focus();">' . $button_text . '</button>';
				endif;
			else :
				$button = '';
			endif;

			switch ($button_pos) :
				case 'top' :
					$button = $button.'<br />';
					$output = $button.$output;
					break;

				case 'bottom' :
					$button = '<br />'.$button;
					$output = $output.$button;
					break;

				case 'right' :
					$output = $output.$button;
					break;

				case 'left' :
				default :
					$output = $button.$output;
					break;
			endswitch;

			echo $output;
		?>
	<input type="hidden" name="task" value="search" />
	<input type="hidden" name="option" value="com_search" />
	<input type="hidden" name="Itemid" value="<?php echo $mitemid; ?>" />
	</div>
</form>
