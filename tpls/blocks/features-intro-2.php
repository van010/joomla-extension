<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>


<div class="container">
  
	<div class="main-container">
	
	  <?php if ($this->countModules('masshead')) : ?>
		<!-- MASTHEAD 2-->
		<div class="<?php $this->_c('masshead') ?>">
			<jdoc:include type="modules" name="<?php $this->_p('masshead') ?>" style="raw" />
		</div>
	   <?php endif ?>

	  <!-- features-intro 2 -->
	  <div class="features-intro <?php $this->_c('features-intro-2') ?>">
		  <jdoc:include type="modules" name="<?php $this->_p('features-intro-2') ?>" style="FeatureRow" />
	  </div>
	  
	</div>
</div>
