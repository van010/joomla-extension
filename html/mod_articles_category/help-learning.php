<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_articles_category
 * @copyright	Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;


$num_intro   = 1;//leading article
$item_index  = 0;
$intro_items = array();
$links_items = array();

if($grouped){
	foreach ($list as $group) {
		foreach ($group as $item) {
			
			if($item_index < $num_intro){
				$intro_items[] = $item;
			} else {
				$links_items[] = $item;
			}

			$item_index++;
		}
	}
} else {
	foreach ($list as $item) {
		if($item_index < $num_intro){
			$intro_items[] = $item;
		} else {
			$links_items[] = $item;
		}

		$item_index++;
	}
}

if (!isset($item_heading)) {
	$item_heading = 3;
}

$more = false;
if(!empty($intro_items)){
	$more = $intro_items[0];
} else if(!empty($links_items)){
	$more = $links_items[0];
}
?>

<?php if($more): ?>
	<div class="module-more">
		<a href="<?php echo JRoute::_(ContentHelperRoute::getCategoryRoute($more->category_alias ? ($more->catid.':'.$more->category_alias) : $more->catid)) ?>"><?php echo JText::_('TPL_MORE') ?></a>
	</div>
<?php endif ?>

<div class="category-module help-learning">
	<?php if(count($intro_items)) : ?>
	<div class="intro-items clearfix">
		<?php foreach ($intro_items as $i => $item) : ?>
			<div class="intro-item">
			<?php 

				$images = is_string($item->images) ? json_decode($item->images) : $item->images;
				
				if (!empty($images->image_intro)) {
					$intro_src   = $images->image_intro;
					$intro_title = !empty($images->image_intro_caption) ? $images->image_intro_caption : $item->title;
					$intro_alt   = !empty($images->image_intro_alt) ? $images->image_intro_alt : $item->title;
				} else {
					$intro_src   = '';
					$intro_title = $intro_alt = $item->title;
				}
				
				$iclass = '';
				if ($i == 0) {
					$iclass = ' li-first';
				}
				if ($i == (count($list) - 1)) {
					$iclass = ' li-last';
				}
				if (empty($intro_src)) {
					$iclass .= ' no-images';
				}
			?>
				
				<!-- Add images -->
				<!-- Intro images -->
					
				<?php if (!empty($intro_src)): ?>
				<div class="item-image view-image pull-left">
					<div class="img-intro">
						<?php if ($params->get('link_titles') == 1) : ?>
						<a class="article-link mod-articles-category-title <?php echo $item->active; ?>" href="<?php echo $item->link; ?>">
						<?php endif ?>
							<img title="<?php echo htmlspecialchars($intro_title); ?>"
								 src="<?php echo htmlspecialchars($intro_src); ?>"
								 alt="<?php echo htmlspecialchars($intro_alt); ?>"/>
						<?php if ($params->get('link_titles') == 1) : ?>
						</a>
						<?php endif ?>
					</div>
				</div>
				<?php endif; ?>

				<div class="">
					<p class="mod-articles-category-introtext">
						<?php echo $item->displayIntrotext; ?>
					</p>
				</div>

			</div>
		<?php endforeach ?>
	</div>
	<?php endif ?>

	<?php if(count($links_items)) : ?>
		<ul class="category-module<?php echo $moduleclass_sfx; ?> clearfix">
			<?php foreach ($links_items as $item) : ?>
				<li>
					<h<?php echo $item_heading; ?>>
					<?php if ($params->get('link_titles') == 1) : ?>
					<a class="mod-articles-category-title <?php echo $item->active; ?>" href="<?php echo $item->link; ?>">
						<?php echo $item->title; ?>
						<?php if ($item->displayHits) :?>
					<span class="mod-articles-category-hits">
								(<?php echo $item->displayHits; ?>)  </span>
						<?php endif; ?></a>
					<?php else :?>
						<?php echo $item->title; ?>
							<?php if ($item->displayHits) :?>
					<span class="mod-articles-category-hits">
								(<?php echo $item->displayHits; ?>)  </span>
						<?php endif; ?></a>
					<?php endif; ?>
					</h<?php echo $item_heading; ?>>

					<?php if ($params->get('show_author')) :?>
							<span class="mod-articles-category-writtenby">
					<?php echo $item->displayAuthorName; ?>
					</span>
					<?php endif;?>
				
					<?php if ($item->displayCategoryTitle) :?>
						<span class="mod-articles-category-category">
						(<?php echo $item->displayCategoryTitle; ?>)
						</span>
					<?php endif; ?>
					
					<?php if ($item->displayDate) : ?>
						<span class="mod-articles-category-date"><?php echo $item->displayDate; ?></span>
					<?php endif; ?>
					
					<?php if ($params->get('show_readmore')) :?>
						<p class="mod-articles-category-readmore">
							<a class="mod-articles-category-title <?php echo $item->active; ?>" href="<?php echo $item->link; ?>">
									<?php if ($item->params->get('access-view')== FALSE) :
									echo JText::_('MOD_ARTICLES_CATEGORY_REGISTER_TO_READ_MORE');
								elseif ($readmore = $item->alternative_readmore) :
									echo $readmore;
									echo JHtml::_('string.truncate', $item->title, $params->get('readmore_limit'));
								elseif ($params->get('show_readmore_title', 0) == 0) :
									echo JText::sprintf('MOD_ARTICLES_CATEGORY_READ_MORE_TITLE');
								else :
									echo JText::_('MOD_ARTICLES_CATEGORY_READ_MORE');
									echo JHtml::_('string.truncate', $item->title, $params->get('readmore_limit'));
								endif; ?>
								</a>
						</p>
					<?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ul>
	<?php endif ?>
</div>