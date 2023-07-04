<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<?php

/**
 * Mainbody 3 columns, content in center: sidebar1 - content - sidebar2
 */

// positions configuration
$sidebar1 = 'sidebar-1';
$sidebar2 = 'sidebar-2';

// detect layout

?>

<div id="t3-mainbody" class="container t3-mainbody" >
	<div class="main-container">
		<div class="row">

			<!-- MAIN CONTENT -->
			<div id="t3-content" class="t3-content col-xs-12 <?php echo $this->countModules($sidebar1) ? 'col-sm-8 col-sm-push-4 col-md-9 col-md-push-3' : '' ?>">
				<?php if($this->hasMessage()) : ?>
				<jdoc:include type="message" />
				<?php endif ?>
				<?php if ($this->checkSpotlight('spotlight-1', 'position-1, position-2, position-3')) : ?>
					<!-- SPOTLIGHT 1 -->
						<?php $this->spotlight('spotlight-1', 'position-1, position-2, position-3') ?>
					<!-- //SPOTLIGHT 1 -->
				<?php endif ?>
				
				<jdoc:include type="component" />
				
				<?php if ($this->checkSpotlight('spotlight-2', 'position-4, position-5, position-6')) : ?>
					<!-- SPOTLIGHT 2 -->
						<?php $this->spotlight('spotlight-2', 'position-4, position-5, position-6') ?>
					<!-- //SPOTLIGHT 2 -->
				<?php endif ?>
			</div>
			<!-- //MAIN CONTENT -->
			
			<?php if ($this->countModules($sidebar1)) : ?>
				<!-- SIDEBAR LEFT -->
				<div
					class="t3-sidebar t3-sidebar-left col-xs-12 col-sm-4 col-sm-pull-8 col-md-3 col-md-pull-9 <?php $this->_c($sidebar1) ?>">
					<jdoc:include type="modules" name="<?php $this->_p($sidebar1) ?>" style="T3Xhtml" />
				</div>
				<!-- SIDEBAR LEFT -->
			<?php endif ?>


	
		</div>

	</div>
</div> 