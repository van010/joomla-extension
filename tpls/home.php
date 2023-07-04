<?php
/** 
 *------------------------------------------------------------------------------
 * @package       T3 Framework for Joomla!
 *------------------------------------------------------------------------------
 * @copyright     Copyright (C) 2004-2013 JoomlArt.com. All Rights Reserved.
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 * @authors       JoomlArt, JoomlaBamboo, (contribute to this project at github 
 *                & Google group to become co-author)
 * @Google group: https://groups.google.com/forum/#!forum/t3fw
 * @Link:         http://t3-framework.org 
 *------------------------------------------------------------------------------
 */

defined('_JEXEC') or die;
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" class="<jdoc:include type="pageclass" />">

<head>
	<jdoc:include type="head" />
	<?php $this->loadBlock('head') ?>
	<?php $this->addCss('home') ?>
</head>

<body>

<div class="t3-wrapper home"> <!-- Need this wrapper for off-canvas menu. Remove if you don't use of-canvas -->

	<?php $this->loadBlock('top-header') ?>

	<?php $this->loadBlock('header') ?>
	
	<?php $this->loadBlock ('slideshow') ?>

	<?php $this->loadBlock('mainbody-home') ?>
	
	<?php $this->loadBlock ('masshead') ?>

	<?php $this->loadBlock('footer') ?>

</div>


</body>
</html>