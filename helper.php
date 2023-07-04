<?php
/**
 * ------------------------------------------------------------------------
 * JA Fixel Template
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2011 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites:  http://www.joomlart.com -  http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.image.image');

class JAMarchHelper {

	public static function relTime($timespan, $granularity = 2) {
		static $units = array(
			'YEAR' => 31536000,
			'MONTH' => 2592000,
			'WEEK' => 604800,
			'DAY' => 86400,
			'HOUR' => 3600,
			'MIN' => 60,
			'SEC' => 1,
		);

		$output = '';
		if(!ctype_digit($timespan)){
        	$timespan = strtotime($timespan);
		}

    	$interval = time() - $timespan;

    	$future = $interval < 0;
    	if($future){
    		$interval = abs($interval);
    	}

		foreach ($units as $key => $value) {
			if ($interval >= $value) {
				$output .= ($output ? ' ' : '') . JText::sprintf('TPL_RT_' . $key . (floor($interval / $value) != 1 ? 'S' : ''), floor($interval / $value));
				$interval %= $value;
				$granularity--;
			}

			if ($granularity == 0) {
				break;
			}
		}

		return $output ? JText::sprintf($future ? 'TPL_RT_FUTURE' : 'TPL_RT_PAST', $output) : JText::_('TPL_RT_NOW');
	}

	public static function getParam($item){

		if(! $item->params instanceof JRegistry) {
			$params = new JRegistry;
			$params->loadString($item->params);

			$item->params = $params;
		}

		return $item->params;
	}

	public static function getEx($item){
		
		if(!isset($item->iattribs) && is_string($item->attribs)){
			$attribs = new JRegistry;
			$attribs->loadString($item->attribs);
			$item->iattribs = $attribs;
		}

		if(isset($item->iattribs) && $item->iattribs instanceof JRegistry){
			return array(
				'type' => $item->iattribs->get('content_type', 'text')
			);
		}
		
		return array(
			'type' => 'text',
		);
	}
	
	public static function getNav($item, $params = null){
		$db		= JFactory::getDbo();
		$user	= JFactory::getUser();
		$app	= JFactory::getApplication();
		$lang	= JFactory::getLanguage();
		$nullDate = $db->getNullDate();

		$date	= JFactory::getDate();
		$config	= JFactory::getConfig();
		$now = $date->toSql();

		$uid	    = $item->id;
		$option	    = 'com_content';
		$canPublish = $user->authorise('core.edit.state', $option.'.article.'.$item->id);
		
		if(!$params){
			$params = $item->params;
		}

		$order_method = $params->get('orderby', 'rdate');
		// Additional check for invalid sort ordering.
		if ($order_method == 'front') {
			$order_method = '';
		}

		// Determine sort order.
		switch ($order_method) {
			case 'date' :
				$orderby = 'a.created';
				break;
			case 'rdate' :
				$orderby = 'a.created DESC';
				break;
			case 'alpha' :
				$orderby = 'a.title';
				break;
			case 'ralpha' :
				$orderby = 'a.title DESC';
				break;
			case 'hits' :
				$orderby = 'a.hits';
				break;
			case 'rhits' :
				$orderby = 'a.hits DESC';
				break;
			case 'order' :
				$orderby = 'a.ordering';
				break;
			case 'author' :
				$orderby = 'a.created_by_alias, u.name';
				break;
			case 'rauthor' :
				$orderby = 'a.created_by_alias DESC, u.name DESC';
				break;
			case 'front' :
				$orderby = 'f.ordering';
				break;
			default :
				$orderby = 'a.ordering';
				break;
		}

		$xwhere = ' AND (a.state = 1 OR a.state = -1)' .
		' AND (publish_up = '.$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).')' .
		' AND (publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now).')';

		// Array of articles in same category correctly ordered.
		$query	= $db->getQuery(true);
       //sqlsrv changes
        $case_when = ' CASE WHEN ';
        $case_when .= $query->charLength('a.alias');
        $case_when .= ' THEN ';
        $a_id = $query->castAsChar('a.id');
        $case_when .= $query->concatenate(array($a_id, 'a.alias'), ':');
        $case_when .= ' ELSE ';
        $case_when .= $a_id.' END as slug';

        $case_when1 = ' CASE WHEN ';
        $case_when1 .= $query->charLength('cc.alias');
        $case_when1 .= ' THEN ';
        $c_id = $query->castAsChar('cc.id');
        $case_when1 .= $query->concatenate(array($c_id, 'cc.alias'), ':');
        $case_when1 .= ' ELSE ';
        $case_when1 .= $c_id.' END as catslug';
  		$query->select('a.id,a.title,'.$case_when.','.$case_when1);
		$query->from('#__content AS a');
		$query->leftJoin('#__categories AS cc ON cc.id = a.catid');
		$query->where('a.catid = '. (int)$item->catid .' AND a.state = '. (int)$item->state
					. ($canPublish ? '' : ' AND a.access = ' .(int)$item->access) . $xwhere);
		$query->order($orderby);
		if ($app->isSite() && $app->getLanguageFilter()) {
			$query->where('a.language in ('.$db->quote($lang->getTag()).','.$db->quote('*').')');
		}

		$db->setQuery($query);
		$list = $db->loadObjectList('id');
		// This check needed if incorrect Itemid is given resulting in an incorrect result.
		if (!is_array($list)) {
			$list = array();
		}

		reset($list);

		// Location of current content item in array list.
		$location = array_search($uid, array_keys($list));

		$rows = array_values($list);

		$result = array();

		if ($location -1 >= 0)	{
			$result['prev'] = $rows[$location -1];
		}

		if (($location +1) < count($rows)) {
			$result['next'] = $rows[$location +1];
		}

		return $result;
	}

	public static function sanitize($item, $prop = 'introtext'){
		$exinfo = self::getEx($item);
		$result = $item->$prop;

		if($exinfo['type'] == 'video'){
			$result = preg_replace('@<iframe\s[^>]*src=[\"|\']([^\"\'\>]+)[^>].*?</iframe>@ms', '', $item->$prop);
		} else if($exinfo['type'] == 'gallery'){
			$result = preg_replace('@<img[^>]+>@ms', '', $item->$prop);
		}

		return $result;
	}
	
	public static function extractImage(&$text){
			//get images
		$image = '';

		if (preg_match ('/<img[^>]+>/i', $text, $matches)) {
			$image = $matches[0];
			$text = str_replace ($image, '', $text);
			$text = preg_replace('/<p>([\s]*?|(?R))<\/p>/imsU', '', $text); //remove empty tags
		}

		return $image;
	}

	public static function icon($type){
		return $type != 'text' ? ('<i class="icon-' . ($type == 'video' ? 'play-sign' : 'camera') . '"></i>') : '';
	}

	public static function image($item, $type = ''){

		$result = array();

		if($type == 'video'){
			$result = self::video($item);
		}

		if(empty($result)){

			if(preg_match('/<img[^>]+>/i', isset($item->text) ? $item->text : $item->introtext, $imgs)){
				return JUtility::parseAttributes($imgs[0]);
			}
		}

		return $result;
	}


	public static function video($item){
		$result = array();
		$prop   = 'text';
		if(!isset($item->$prop)){
			$prop = 'introtext';
		}

		if(preg_match_all('@<iframe\s[^>]*src=[\"|\']([^\"\'\>]+)[^>].*?</iframe>@ms', $item->$prop, $iframesrc) > 0){
			if(isset($iframesrc[1])){

				if(strpos($iframesrc[1][0], 'vimeo.com') !== false ) {
					$vid = str_replace(
						array(
							'http:',
							'https:', 
							'//player.vimeo.com/video/'
						), '', $iframesrc[1][0]);
				} else {
					$vid = str_replace(
						array(
							'http:',
							'https:',
							'//youtu.be/',
							'//www.youtube.com/embed/',
							'//youtube.googleapis.com/v/'
						), '', $iframesrc[1][0]);
				}

				//remove any parameter
				$vid = preg_replace('@(\/|\?).*@i', '', $vid);
				
				if(!(empty($vid))){ 
					if(strpos($iframesrc[1][0], 'vimeo.com') !== false ) {
						require_once (JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/helpers/download.php');
						$filepath = JPATH_SITE . '/cache/vimeo/' . $vid . '.json';

						if(!is_file($filepath)){							
							AdmintoolsHelperDownload::download("http://vimeo.com/api/v2/video/$vid.json", $filepath);
						}

						if(is_file($filepath)){
							$vimeojson = json_decode(@file_get_contents($filepath));
							$result['src'] = $vimeojson[0]->thumbnail_large;
						}
					} else {
						$result['src'] = 'http://img.youtube.com/vi/'.$vid.'/0.jpg';
					}
					
					$item->$prop = str_replace($iframesrc['0'], '', $item->$prop);
				}
			}
		}

		return $result;
	}

	public static function gallery($item){
		
		$html = '';
		$prop = 'text';
		if(!isset($item->$prop)){
			$prop = 'introtext';
		}

		if($item->$prop && preg_match_all('#<img[^>]+>#iU', $item->$prop, $imgs)) {

			//remove the $prop
			$item->$prop = preg_replace('#<img[^>]+>#iU', '', $item->$prop);

			//collect all images
			$img_data = array();

			// parse image attributes
			foreach( $imgs[0] as $img_tag){
				$img_data[$img_tag] = JUtility::parseAttributes($img_tag);
			}

			$total = count($img_data);

			if($total > 0){

				$params = isset($item->params) ? $item->params : (isset($item->core_params) ? $item->core_params : null);
				$link = false;

				//tag does not have params (core_params)
				if (!$params || ($params->get('link_titles') && $params->get('access-view'))) {
					if(!$params && $item->core_state != 0){
						$link = JRoute::_(TagsHelperRoute::getItemRoute($item->content_item_id, $item->core_alias, $item->core_catid, $item->core_language, $item->type_alias, $item->router));
					} else {
						$link = JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid));
					}
				}

				if(!isset($item->id) && isset($item->core_content_id)){
					$item->id = $item->core_content_id;
				}
				
				$html .= '<div id="fixel-carousel-' . $item->id . '" class="carousel slide">';
				$html .= '<ol class="carousel-indicators">';
				
				for($i = 0; $i < $total; $i++){
					$html .= '<li data-target="#fixel-carousel-' . $item->id . '" data-slide-to="' . $i . '"' . ($i == 0 ? ' class="active"' : '') . '></li>';
				}

				$html .= '</ol>';
				$html .= '<div class="carousel-inner">';
				
				$j = 0;

				foreach($img_data as $img => $attr){
					
					// gallery item
					$html .= '<div class="item' . ($j == 0 ? ' active' : '') . '">';

					if($link){
						$html .= '<a class="article-link" href="' . $link . '" title="">' . $img . '</a>'; 
					} else {
						$html .= $img;
					}

					// gallery description
					if((isset($attr['alt']) && $attr['alt']) || (isset($attr['title']) && $attr['title'])){

						$html .= '<div class="carousel-caption">';
						$html .= (isset($attr['title']) && $attr['title']) ? '<h4>' . htmlspecialchars_decode($attr['title']) . '</h4>' : '';
						$html .= (isset($attr['alt']) && $attr['alt']) ? '<p>' . htmlspecialchars_decode($attr['title']) . '</p>' : '';

						$html .= '</div>';
					}

					$html .= '</div>';
					$j++;
				}

				$html .= '</div>';
				$html .= '<a class="carousel-control left" href="#fixel-carousel-'.$item->id.'" data-slide="prev">&lsaquo;</a>';
				$html .= '<a class="carousel-control right" href="#fixel-carousel-'.$item->id.'" data-slide="next">&rsaquo;</a>';
				$html .= '</div>';

				$html .= '<script type="text/javascript">
					(function($){
						$(document).ready(function($){
							$(\'#fixel-carousel-'.$item->id.'\').carousel();
						})
					})(jQuery);
				</script>';
			}
		}

		return $html;
	}

	public static function related($item, $count = 3)
	{
		// validate input
		if(empty($item) || !$count) {
			return array();
		}

		// get data
		$db			= JFactory::getDbo();
		$app		= JFactory::getApplication();
		$user		= JFactory::getUser();
		$groups		= implode(',', $user->getAuthorisedViewLevels());
		$date		= JFactory::getDate();

		$showDate	= 0;
		$nullDate	= $db->getNullDate();
		$now		= $date->toSql();
		$related	= array();
		$query		= $db->getQuery(true);

		
		// explode the meta keys on a comma
		$keys = explode(',', $item->metakey);
		$likes = array ();

		// assemble any non-blank word(s)
		foreach ($keys as $key)
		{
			$key = trim($key);
			if ($key) {
				$likes[] = $db->escape($key);
			}
		}

		if (count($likes))
		{
			// select other items based on the metakey field 'like' the keys found
			$query->clear();
			$query->select('a.id');
			$query->select('a.title');
			$query->select('a.alias');
			$query->select('a.attribs');
			$query->select('DATE_FORMAT(a.created, "%Y-%m-%d") as created');
			$query->select('a.catid');
			$query->select('cc.access AS cat_access');
			$query->select('cc.published AS cat_state');

            //sqlsrv changes
	        $case_when = ' CASE WHEN ';
	        $case_when .= $query->charLength('a.alias');
	        $case_when .= ' THEN ';
	        $a_id = $query->castAsChar('a.id');
	        $case_when .= $query->concatenate(array($a_id, 'a.alias'), ':');
	        $case_when .= ' ELSE ';
	        $case_when .= $a_id.' END as slug';
			$query->select($case_when);

            $case_when = ' CASE WHEN ';
            $case_when .= $query->charLength('cc.alias');
            $case_when .= ' THEN ';
            $c_id = $query->castAsChar('cc.id');
            $case_when .= $query->concatenate(array($c_id, 'cc.alias'), ':');
            $case_when .= ' ELSE ';
            $case_when .= $c_id.' END as catslug';
            $query->select($case_when);
			$query->from('#__content AS a');
			$query->leftJoin('#__content_frontpage AS f ON f.content_id = a.id');
			$query->leftJoin('#__categories AS cc ON cc.id = a.catid');
			$query->where('a.id != ' . (int) $item->id);
			$query->where('a.state = 1');
			$query->where('a.access IN (' . $groups . ')');
  			$concat_string = $query->concatenate(array('","', ' REPLACE(a.metakey, ", ", ",")', ' ","'));
			$query->where('('.$concat_string.' LIKE "%'.implode('%" OR '.$concat_string.' LIKE "%', $likes).'%")'); //remove single space after commas in keywords)
			$query->where('(a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).')');
			$query->where('(a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).')');

			// Filter by language
			if ($app->getLanguageFilter()) {
				$query->where('a.language in (' . $db->Quote(JFactory::getLanguage()->getTag()) . ',' . $db->Quote('*') . ')');
			}

			$query->order('a.created DESC');

			$db->setQuery($query, 0, $count);
			$qstring = $db->getQuery();
			$temp = $db->loadObjectList();

			if (count($temp))
			{
				foreach ($temp as $row)
				{
					if ($row->cat_state == 1)
					{
						$row->route = JRoute::_(ContentHelperRoute::getArticleRoute($row->slug, $row->catslug));
						$related[] = $row;
					}
				}
			}
			unset ($temp);
		}

		return $related;
	}
}