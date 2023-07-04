<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<!-- HEADER -->
<div id="top-header" class=" t3-top-header">
	<!-- OFF-CANVAS -->
			<?php if ($this->getParam('addon_offcanvas_enable')) : ?>
				<?php $this->loadBlock ('off-canvas') ?>
		<?php endif ?>
		<!-- //OFF-CANVAS -->
  <div class="container">
	<div class="main-container">
		
		<div class="row">
		
			
			
			<div class="col-xs-6 col-sm-6 pull-left clearfix">
				
				<?php if ($this->countModules('languageswitcherload')) : ?>
  					<!-- LANGUAGE SWITCHER -->
  					<div class="languageswitcher-block pull-left">
  						<jdoc:include type="modules" name="<?php $this->_p('languageswitcherload') ?>" style="raw" />
  					</div>
  					<!-- //LANGUAGE SWITCHER -->

  				<?php endif ?>
			
				<?php if ($this->countModules('head-social')) : ?>
					<!-- HEAD SOCIAL -->
					<div class="head-social <?php $this->_c('head-social') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('head-social') ?>" style="raw" />
					</div>
					<!-- //HEAD SOCIAL -->
				<?php endif ?>
				
			</div>
			
			<div class="col-xs-6 col-sm-6 pull-right">
				<?php if ($this->countModules('head-login')) : ?>
					<!-- HEAD LOGIN -->
					<div class="head-login <?php $this->_c('head-login') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('head-login') ?>" style="raw" />
					</div>
					<!-- //HEAD LOGIN -->
				<?php endif ?>
			</div>
		</div>
	</div>
  </div>
</div>