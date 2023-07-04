<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Mainbody 1 columns, content only
 */
?>

<div id="t3-mainbody" class="container t3-mainbody">
	<div class="main-container">
		<div class="row">

			<!-- MAIN CONTENT -->
			<div id="t3-content" class="t3-content col-xs-12">
				<?php if($this->hasMessage()) : ?>
				<jdoc:include type="message" />
				<?php endif ?>
				<?php if ($this->countModules('masshead')) : ?>
					<!-- MASHEAD 2-->
					<div class="<?php $this->_c('masshead') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('masshead') ?>" style="raw" />
					</div>
				<?php endif ?>
				<jdoc:include type="component" />
				
			</div>
			<!-- //MAIN CONTENT -->
			
		</div>

	</div>
</div> 