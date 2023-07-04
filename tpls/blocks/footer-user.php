<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<!-- FOOTER -->
<footer class="container footer-user">
	
	<div class="main-container">
		
	<div class="row">
	
		<div class="">
		<?php if ($this->countModules('footer-user')) : ?>

			<div class="footer-user">				
				<jdoc:include type="modules" name="<?php $this->_p('footer-user') ?>" style="T3xhtml" />				
			</div>
		<?php endif ?>

		</div>
		
	
		
		
	</div>
				
	</div>

</footer>
<!-- //FOOTER -->