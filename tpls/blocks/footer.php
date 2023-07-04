<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<?php if ($this->countModules('back-to-top')) : ?>
	<jdoc:include type="modules" name="<?php $this->_p('back-to-top') ?>" style="none" />
<?php endif ?>

<!-- FOOTER -->
<footer id="t3-footer" class="container t3-footer">
	
	<div class="main-container">
		<!-- FOOT NAVIGATION -->
		<div class="row">
			
			<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
				<div class="footer-info">
					<?php if ($this->countModules('footer-info')) : ?>
						<jdoc:include type="modules" name="<?php $this->_p('footer-info') ?>" style="T3xhtml" />
					<?php endif ?>
					
					<div class="copyright <?php $this->_c('footer') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('footer') ?>" />
					</div>
				</div>
				
			</div>
			
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 footer-links">
				<?php if ($this->checkSpotlight('footnav', 'footer-1, footer-2, footer-3, footer-4')) : ?>
					<?php $this->spotlight('footnav', 'footer-1, footer-2, footer-3, footer-4') ?>
				<?php endif ?>
			</div>
			
			<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 footer-subcribe">
				<?php if ($this->countModules('footer-subcribe')) : ?>
					<jdoc:include type="modules" name="<?php $this->_p('footer-subcribe') ?>" style="T3xhtml" />
				<?php endif ?>
			</div>
	
		</div>
		<!-- //FOOT NAVIGATION -->

		<section class="t3-copyright ">
			<div class="row">
			
				<div class="col-xs-9 col-sm-6 pull-left">
				<?php if ($this->countModules('footer-menu')) : ?>

					<div class="footer-menu">				
						<jdoc:include type="modules" name="<?php $this->_p('footer-menu') ?>" style="T3xhtml" />				
					</div>
				<?php endif ?>

				<?php if ($this->getParam('t3-rmvlogo', 1)): ?>
					<div class=" poweredby">
						<a class="t3-logo-small t3-logo-light" href="http://t3-framework.org" title="Powered By T3 Framework" <?php echo method_exists('T3', 'isHome') && T3::isHome() ? '' : 'rel="nofollow"' ?>
							 target="_blank">Powered by <strong>T3 Framework</strong></a>
					</div>
				<?php endif; ?>
				</div>
				
				<div class="col-xs-3 col-sm-6 pull-right">
				
				<?php if ($this->countModules('head-social-footer')) : ?>
					<!-- HEAD SOCIAL -->
					<div class="head-social pull-right <?php $this->_c('head-social-footer') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('head-social-footer') ?>" style="raw" />
					</div>
					<!-- //HEAD SOCIAL -->
				<?php endif ?>
				
			
				<?php if ($this->countModules('languageswitcherload-footer')) : ?>
  					<!-- LANGUAGE SWITCHER -->
  					<div class="languageswitcher-block pull-right">
  						<jdoc:include type="modules" name="<?php $this->_p('languageswitcherload-footer') ?>" style="raw" />
  					</div>
  					<!-- //LANGUAGE SWITCHER -->

  				<?php endif ?>
				
				
			</div>
				
			</div>
		</section>
	</div>

</footer>
<!-- //FOOTER -->