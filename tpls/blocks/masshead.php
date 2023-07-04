<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<!-- MASHEAD -->
<div id="t3-mashead" class="container t3-mashead">
	<div class="main-container">
		<div class="row">
						
				<?php if ($this->countModules('mashead-1')) : ?>
					<div class="col-xs-12 col-sm-4 col-md-3 mashead-1">
					<!-- MASHEAD 1 -->
					<div class="<?php $this->_c('mashead-1') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('mashead-1') ?>" style="raw" />
					</div>
					<!-- //MASHEAD 1 -->
					</div>
				<?php endif ?>		
			
				<?php if ($this->countModules('mashead-2')) : ?>
					<div class="col-xs-12 col-sm-8 col-md-9 mashead-2">
					<!-- MASHEAD 2-->
					<div class="<?php $this->_c('mashead-2') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('mashead-2') ?>" style="raw" />
					</div>
					<!-- //MASHEAD 2  -->
					</div>
				<?php endif ?>
			
		</div>
	</div>
</div>
<!-- //MASHEAD -->