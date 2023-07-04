<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<?php if( $this->countModules('slideshow') || $this->countModules('lastviews')) : ?>
<!-- SLIDESHOW -->
<div class="t3-slideshow">
	<div class="container">
		<div class="main-container slideshow">
			<?php if ($this->countModules('slideshow')) : ?>
				<!-- HEAD SOCIAL -->
				<div class="main <?php $this->_c('slideshow') ?>">
					<jdoc:include type="modules" name="<?php $this->_p('slideshow') ?>" style="raw" />
				</div>
				<!-- //HEAD SOCIAL -->
			<?php endif ?>
		</div>

		<div class="bottom-slide">
			<div class="row main-container">
				<div class="col-xs-12 col-sm-8 col-md-9 right-slide pull-right">
					<?php if ($this->countModules('lastviews')) : ?>
						
						<div class="<?php $this->_c('lastviews') ?>">
							<jdoc:include type="modules" name="<?php $this->_p('lastviews') ?>" style="raw" />
						</div>
						
					<?php endif ?>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- //SLIDESHOW -->
<?php endif; ?>