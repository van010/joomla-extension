<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_articles_category
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
$moduleclass_sfx = $params->get('moduleclass_sfx','');
if(!$item_heading){
$item_heading = 5;
}
?>
<ul class="category-module<?php echo $moduleclass_sfx; ?>">
<?php if ($grouped) : ?>
	<?php foreach ($list as $group_name => $group) : ?>
	<li>
		<h<?php echo $item_heading; ?>><?php echo $group_name; ?></h<?php echo $item_heading; ?>>
		<ul>
			<?php 
			$i = 0;
			foreach ($group as $item) :
					
				$images = "";
				if (isset($item->images)) {
					$images = json_decode($item->images);
				}
				$jaliclass = '';
				if($i==0){
					$jaliclass=' li-first';
				}
				if($i == (count($group)-1)){
					$jaliclass=' li-last';
				}
				if(!(isset($images->image_intro) and !empty($images->image_intro))){
					$jaliclass .= ' no-images';
				}
			?>
				<li class="clearfix<?php echo $jaliclass;?>">
					<!-- Add images -->
					<!-- Intro images -->
					<?php  if (isset($images->image_intro) and !empty($images->image_intro)) : ?>
					<div class="img-intro">
						<img
							<?php if ($images->image_intro_caption):
								echo 'class="caption"'.' title="' .htmlspecialchars($images->image_intro_caption) .'"';
							endif; ?>
							src="<?php echo htmlspecialchars($images->image_intro); ?>" alt="<?php echo htmlspecialchars($images->image_intro_alt); ?>"/>
					</div>
					<?php endif; ?>

					<!-- Full images-->
					<?php  if (isset($images->image_fulltext) and !empty($images->image_fulltext)) : ?>
					<div class="img-details">
						<img
							<?php if ($images->image_fulltext_caption):
								echo 'class="caption"'.' title="' .htmlspecialchars($images->image_fulltext_caption) .'"';
							endif; ?>
							src="<?php echo htmlspecialchars($images->image_fulltext); ?>" alt="<?php echo htmlspecialchars($images->image_fulltext_alt); ?>"/>
					</div>
					<?php endif; ?>

					<!-- End add -->
					<div class="tabs-category clearfix">

						<h<?php echo $item_heading + 1; ?>>
							<?php if ($params->get('link_titles') == 1) : ?>
							<a class="mod-articles-category-title <?php echo $item->active; ?>" href="<?php echo $item->link; ?>">
								<?php echo $item->title; ?>
							</a>
							<?php else :?>
								<?php echo $item->title; ?>
							<?php endif; ?>
						</h<?php echo $item_heading + 1; ?>>


						<div class="article-aside clearfix">
							<?php if ($params->get('show_author')) :?>
								<span class="mod-articles-category-writtenby">
									<span><?php echo JText::_('JA_ARTICLE_CATEGORY_BY') ?>: </span>
									<?php echo $item->displayAuthorName; ?>
								</span>
							<?php endif;?>
							
							<?php if ($item->displayDate) : ?>
							<span class="mod-articles-category-date">
								<span> On: </span><?php echo $item->displayDate; ?></span>
							<?php endif; ?>

							<?php if ($item->displayCategoryTitle) :?>
								<span class="mod-articles-category-category">
									<span><?php echo JText::_('JA_ARTICLE_CATEGORY_IN') ?>: </span>
								<?php echo $item->displayCategoryTitle; ?>
								</span>
							<?php endif; ?>
						</div>
				
						<?php if ($params->get('show_introtext')) :?>
						<p class="mod-articles-category-introtext">
							<?php echo $item->displayIntrotext; ?>
						</p>
						<?php endif; ?>

					</div>

					<div class="footer-feature clearfix">
						<?php if ($item->displayHits) :?>
							<span class="mod-articles-category-hits">
								<span><?php  echo JText::_('JA_ARTICLE_CATEGORY_HITS'); ?>: </span>
								<?php echo $item->displayHits; ?>
							</span>
						<?php endif; ?>
						
						<?php if ($params->get('show_readmore')) :?>
						<p class="mod-articles-category-readmore">
							<a class="mod-articles-category-title btn-black <?php echo $item->active; ?>" href="<?php echo $item->link; ?>">
								<?php if ($item->params->get('access-view')== FALSE) :
									echo JText::_('JA_TOUR_MOD_ARTICLES_CATEGORY_REGISTER_TO_READ_MORE');
								elseif ($readmore = $item->alternative_readmore) :
									echo $readmore;
									echo JHtml::_('string.truncate', $item->title, $params->get('readmore_limit'));
									if ($params->get('show_readmore_title', 0) != 0) :
										echo JHtml::_('string.truncate', ($this->item->title), $params->get('readmore_limit'));
									endif;
								elseif ($params->get('show_readmore_title', 0) == 0) :
									echo JText::sprintf('MOD_ARTICLES_CATEGORY_READ_MORE_TITLE');
								else :

									echo JText::_('JA_TOUR_MOD_ARTICLES_CATEGORY_READ_MORE');
									echo JHtml::_('string.truncate', ($item->title), $params->get('readmore_limit'));
								endif; ?>
							</a>
						</p>
						<?php endif; ?>
					</div>
				</li>
			<?php 
			$i++;
			endforeach; ?>
		</ul>
	</li>
	<?php endforeach; ?>
<?php else : ?>
	<?php 
		$i =0;
		foreach ($list as $item) : 
		//Get images 
		$images = "";
		if (isset($item->images)) {
			$images = json_decode($item->images);
		}
		$jaliclass = '';
		if($i==0){
			$jaliclass=' li-first';
		}
		if($i == (count($list)-1)){
			$jaliclass=' li-last';
		}
		if(!(isset($images->image_intro) and !empty($images->image_intro))){
			$jaliclass .= ' no-images';
		}
		
		?>
	    <li class="clearfix<?php echo $jaliclass;?>">
			<!-- Add images -->
			<!-- Intro images -->
			<?php  if (isset($images->image_intro) and !empty($images->image_intro)) : ?>
			<div class="img-intro">
				<img
					<?php if ($images->image_intro_caption):
						echo 'class="caption"'.' title="' .htmlspecialchars($images->image_intro_caption) .'"';
					endif; ?>
					src="<?php echo htmlspecialchars($images->image_intro); ?>" alt="<?php echo htmlspecialchars($images->image_intro_alt); ?>"/>
			</div>
			<?php endif; ?>
			<!-- Full images-->
			<?php  if (isset($images->image_fulltext) and !empty($images->image_fulltext)) : ?>
			<div class="img-details">
				<img
					<?php if ($images->image_fulltext_caption):
						echo 'class="caption"'.' title="' .htmlspecialchars($images->image_fulltext_caption) .'"';
					endif; ?>
					src="<?php echo htmlspecialchars($images->image_fulltext); ?>" alt="<?php echo htmlspecialchars($images->image_fulltext_alt); ?>"/>
			</div>
			<?php endif; ?>

			<!-- End add -->
			<div class="tabs-category clearfix">
				

		   		<h<?php echo $item_heading + 1; ?>>
					<?php if ($params->get('link_titles') == 1) : ?>
					<a class="mod-articles-category-title <?php echo $item->active; ?>" href="<?php echo $item->link; ?>">
						<?php echo $item->title; ?>
					</a>
					<?php else :?>
						<?php echo $item->title; ?>
					<?php endif; ?>
				</h<?php echo $item_heading + 1; ?>>

				
	       		<div class="article-aside clearfix">
					<?php if ($params->get('show_author')) :?>
						<span class="mod-articles-category-writtenby">
							<span><?php echo JText::_('JA_ARTICLE_CATEGORY_BY') ?>: </span>
							<?php echo $item->displayAuthorName; ?>
						</span>
					<?php endif;?>
					
					<?php if ($item->displayDate) : ?>
						<span class="mod-articles-category-date">
							<span> <?php echo JText::_('JA_ARTICLE_CATEGORY_ON') ?>: </span>
							<?php echo $item->displayDate; ?>
						</span>
					<?php endif; ?>

					<?php if ($item->displayCategoryTitle) :?>
						<span class="mod-articles-category-category">
							<span><?php echo JText::_('JA_ARTICLE_CATEGORY_IN') ?>: </span>
							<?php echo $item->displayCategoryTitle; ?>
						</span>
					<?php endif; ?>
				</div>
	        
				<?php if ($params->get('show_introtext')) :?>
				<p class="mod-articles-category-introtext">
					<?php echo $item->displayIntrotext; ?>
				</p>
				<?php endif; ?>

				<div class="footer-feature clearfix">
					
					<?php if ($item->displayHits) :?>
					<span class="mod-articles-category-hits">
						<span><?php echo JText::_('JA_ARTICLE_CATEGORY_HITS') ?>: </span>
						<?php echo $item->displayHits; ?>
					</span>
					<?php endif; ?>

					<?php if ($params->get('show_readmore')) :?>
					<p class="mod-articles-category-readmore">
						<a class="mod-articles-category-title btn-black <?php echo $item->active; ?>" href="<?php echo $item->link; ?>">
						<?php if ($item->params->get('access-view')== FALSE) :
								echo JText::_('JA_TOUR_MOD_ARTICLES_CATEGORY_REGISTER_TO_READ_MORE');
							elseif ($readmore = $item->alternative_readmore) :
								echo $readmore;
								echo JHtml::_('string.truncate', $item->title, $params->get('readmore_limit'));
								if ($params->get('show_readmore_title', 0) != 0) :
									echo JHtml::_('string.truncate', ($this->item->title), $params->get('readmore_limit'));
								endif;
							elseif ($params->get('show_readmore_title', 0) == 0) :
								echo JText::sprintf('JA_TOUR_MOD_ARTICLES_CATEGORY_READ_MORE_TITLE');
							else :

								echo JText::_('JA_TOUR_MOD_ARTICLES_CATEGORY_READ_MORE');
								echo JHtml::_('string.truncate', ($item->title), $params->get('readmore_limit'));
							endif; ?>
						</a>
					</p>
					<?php endif; ?>

				</div>

			</div>
		</li>
	<?php 
	$i++;
	endforeach; ?>
<?php endif; ?>
</ul>
