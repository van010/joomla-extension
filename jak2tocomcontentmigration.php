<?php
/**
 * ------------------------------------------------------------------------
 * JA K2 To Com Content Migration Plugin for J25 & J34
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2011 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * ------------------------------------------------------------------------
 */

use Akeeba\AdminTools\Admin\View\Scans\Raw;

defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

require_once(__DIR__ . '/helpers/migrator.php');
require_once(__DIR__ . '/helpers/convertAttach.php');

/**
 *
 * @package     Joomla.Plugin
 * @subpackage  System.Jak2tocomcontentmigration
 * @since       1.5
 */
class PlgSystemJak2tocomcontentmigration extends JPlugin
{
	protected $pathField = '';
	protected $pathForm = '';
	protected $pathMigrate = '';

	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		$this->pathField 	= dirname(__FILE__) . '/models/fields';
		$this->pathForm 	= dirname(__FILE__) . '/models/forms';
		$this->pathMigrate 	= dirname(__FILE__) . '/helpers';
		
		$lang = JFactory::getLanguage();
		$extension = 'plg_system_jacontenttype_ex';
		$base_dir = JPATH_ADMINISTRATOR;
		$language_tag = 'en-GB';
		$reload = true;
		$lang->load($extension, $base_dir, $language_tag, $reload);
	}

	public static function onAjaxjak2tocomcontentmigration(){
		$input = JFactory::getApplication()->input;
		$task = $input->get('jatask');
		$articleId = $input->get('articleId');
		$articleTitle = urldecode($input->get('articleTitle', '', 'RAW'));
		
		if ($task !== 'fetchJoomlaAttachment') return ['code' => 404, 'message' => 'No task to do.'];

		$data = convertK2Attch::fetchJoomlaAttachment($articleId, $articleTitle);
		return $data;
	}

	public function onAfterInitialise()
	{
		$this->redirectContent(); // redirect Content
		//only override Joomla core for some cases to ensure that other extensions still work properly with Joomla Content component
		$app = JFactory::getApplication();
		$input = $app->input;
		$task = $input->get('task');

		//list articles
		if ($app->isAdmin() && $input->get('option') == 'com_content' && $input->get('view') == 'articles') {
			$taskList = ['article.importk2', 'article.importk2extra', 'article.recontent', 'article.importk2Batch', 'article.importAllAjax', 'article.recontentItems'];
			if(in_array($task, $taskList)) {
				$syncParams = JComponentHelper::getParams('com_content')->get('sync', NULL);
				if ($syncParams == false) {
					//if user have not configured sync profile
					$url = JUri::base(true).'/index.php?option=com_config&amp;view=component&amp;component=com_content&amp;path=';
					echo '<br />';
					echo JText::_('JA_K2TOCONTENT_WARNING_SYNC');
					echo ' <a href="'.$url.'" target="_parent">'.JText::_('JA_K2TOCONTENT_WARNING_SYNC_HERE').'</a>';
					echo '<br />';
				} else {
					require_once($this->pathMigrate.'/migrator.php');
					$jamigrator = new JADataMigrator();
					switch ($task){
						case 'article.importk2':
							$jamigrator->extra_type = "k2";
							$jamigrator->migrate();
							break;
						case 'article.importk2extra':
							$jamigrator->extra_type = "extra";
							$jamigrator->migrate();
							break;
						case 'article.recontent';
							$jamigrator->recontent();
							break;
						case 'article.recontentItems';
							$jamigrator->recontent_k2_items();
							break;
						default:
							break;
					}
				}
				$app->close();
			}
		}
		$this->add_uncategorised_to_tbl_jcat();
	}
	
	public function onAfterRoute() {
		// to do
		$doc = JFactory::getDocument();
		$jsFile = JPATH_ROOT . '/plugins/system/jak2tocomcontentmigration/assets/js/jak2migrate.js';
		if (is_file($jsFile)){
			JHtml::_('jquery.framework');
			$doc->addScript(JUri::root() . 'plugins/system/jak2tocomcontentmigration/assets/js/jak2migrate.js');
		}
	}

	/**
	 * Adding extra fields into Content Component's forms
	 * @param $form
	 * @param $data
	 * @return bool
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JAERROR_NOT_A_FORM');
			return false;
		}

		switch($form->getName()) {
			case 'com_content.articles.filter':
			case 'com_content.featured.filter':
				//list articles
				$this->_onContentPrepareFormArticles($form, $data);
				break;
			case 'com_config.component':
				$this->_onContentPrepareFormConfig($form, $data);
				break;
		}

		return true;
	}

	protected function _onContentPrepareFormConfig($form, $data) {
		$app = JFactory::getApplication();
		if ($app->input->get('component') == 'com_content') {
			$this->addFormPath();
			$form->loadFile($this->pathForm.'/config.migration.xml', false);
		}

	}

	protected function _onContentPrepareFormArticles($form, $data) {

		//Adding new filter option
		$this->addFormPath();
		$form->loadFile('filter_articles_xtd', false);

		//Adding new toolbar buttons
		$user  = JFactory::getUser();
		// Get the toolbar object instance
		$bar = JToolBar::getInstance('toolbar');
		$canDo = JHelperContent::getActions('com_content', 'category', 0);

		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_content', 'core.create'))) > 0 ) {
			//check K2 component is installed
			if (!$this->checkComponent('com_k2')) {
				return ;
			}
			// Add a new button.
			$dhtml = JLayoutHelper::render('sync.button', array(), dirname(__FILE__).'/layouts/');
			$bar->appendButton('Custom', $dhtml, 'importk2');

			$doc = JFactory::getDocument();
			$doc->addScriptDeclaration('
				(function($){
					$(document).ready(function(){
						$("body").append(\'<div class="modal hide fade" id="ja-migrator-modal"><iframe frameborder="0" style="width: 800px; height: 600px;" src="about:blank" name="ja-migrator-form"></iframe></div>\');
					});
				})(jQuery);
			');
		}
	}

	public function addFormPath()
	{
		JFormHelper::addFieldPath($this->pathField);
		JFormHelper::addFormPath($this->pathForm);
	}
	/**
	 *
	 * Check component is existed
	 * @param string $component component name
	 * @return int return > 0 when component is installed
	 */
	protected function checkComponent($component)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('extension_id'))
			->from('#__extensions')
			->where($db->quoteName('element') .'='.$db->quote($component))
			->where($db->quoteName('enabled') .'='.$db->quote('1'));
		$db->setQuery($query);
		return $db->loadResult();
	}

	/*
	*  Redirect to joomla page after migration if k2 item been delete or unpublished.
	*/
	private function redirectContent() {
		$mainframe = JFactory::getApplication();
		$syncParams = JComponentHelper::getParams('com_content')->get('sync', false);
		if (empty($syncParams->jaredirect) || $syncParams->jaredirect==0) return; // if don't had jaredirect params and if it set to 0.
		if ($mainframe->isAdmin()) return; // return if admin
		$jinput	= $mainframe->input;
		$option	= $jinput->get('option');
		$view	= $jinput->get('view');
		$id		= $jinput->get('id');
		$task	= $jinput->get('task');
		$layout	= $jinput->get('layout');
		$tag	= $jinput->get('tag');
		$jaredirect	= $jinput->get('jaredirect');
		$db		= JFactory::getDBO();
		$table	= 'k2_items';
		$field	= 'alias';
		$value	= null;
		$context	= 'item';
		$wherefield	= 'id';
		$redirect	= false;
		$continue	= false;
		$uri	= (string) JUri::getInstance();

		jimport('joomla.language.helper');
		$languages = JLanguageHelper::getLanguages('lang_code');
		$lang_code = JFactory::getLanguage()->getTag();
		$langtag = $lang_code; // default lang tag.
		$sef = $languages[$lang_code]->sef; // default sef tag.

		preg_match('/index.php\/(.*?)\//', $uri, $urisef);
		if (!empty($urisef)) {
			foreach ($languages AS $lag) {
				if ($urisef[1] == $lag->sef) {
					$langtag = $lag->lang_code; // get current lang tag from url
					$sef = $lag->sef; // get current sef tag from url
				}
			}
		}

		$urltocheck = str_replace(array('index.php/'.$sef,'&lang='.$sef),array('index.php',''),$uri); // strip the language tag from url

		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__redirect_links'));
		$query->where($db->quoteName('new_url') . ' LIKE '. $db->quote($urltocheck));
		$db->setQuery($query);
		$results = $db->loadObject();
		// if we insert the link already worked then stop the process.
		if (!empty($results))
		{
			if (!preg_match('/jaredirect/', $urltocheck)) {
				if ($option == 'com_k2')
					$redurl.='&jaredirect';
				else $redurl.='?jaredirect';
				$urltocheck.=$redurl;
				header('Location: '.$urltocheck,TRUE,301);
			}
			return;
		}

		// in case FRIENDLY URL not active.
		if ($option == 'com_k2') {
			// make sure to pull out only the id.
			preg_match('/^(\d+)/', $id, $_match);
			if (!empty($_match[1]))
				$value = $_match[1];

			if (!empty($task)) {
				if ($task == 'category') {
					$table = 'k2_categories';
					$context = 'category';
				}
				else {
					$table = 'k2_tags';
					$field = 'name';
					$wherefield = 'name';
					$id = $value = $tag;
					$context = 'tag';
				}
			}
			$redirect = true;
			// we will get value = number here if not active seo url.
		} else {
			// if FRIENDLY URL enabled so we will get the information in the url.
			// strip the alias in url.
			$url	= explode('/', $uri);
			$id		= end($url); // get id content (cat, tag, item) from url

			// get languages
			$selectlang = 'AND (language = "*" OR language = "'.$langtag.'")';
			if (count($languages)>1) { // check if we had more than 1 language.
				$selectlang = 'AND language = "'.$langtag.'"';
			}

			// strip menu alias from url. 
			$malias = str_replace('index.php/'.$sef, 'index.php', $uri);
			$malias = preg_replace('/(.*?)index.php/', '', $malias);
			// check if alias is menu
			$query = $db->getQuery(true);
			$query->select('path, link')
				->from($db->quoteName('#__menu'))
				->where('path LIKE "'.ltrim($malias, '/').'" '.$selectlang);
			$db->setQuery($query);

			$_alias = $db->loadObjectList();
			// if the link is menu link. we will get error if the item menu link to get delete from db.
			if (!empty($_alias)) {
				// in case we get duplicate alias menu.
				$menu_alias = '';
				foreach ($_alias AS $_ali) {
					if (preg_match('/com_k2/', $_ali->link) 
						&& preg_match('/'.str_replace('/','\/',$_ali->path).'/', $uri)) {
						$menu_alias = $_ali->link;
					}
				}
				$_alias = $menu_alias; // reassign _alias to k2 menu alias.
				if (preg_match('/com_k2/', $_alias)) {
					if (preg_match('/task=tag/', $_alias)) {
						preg_match_all('/tag\=(.*?)&/', $_alias, $tag);
						$value = $tag[1][0];
						$table = 'k2_tags';
						$field = 'name';
						$wherefield = 'name';
						$context = 'tag';
					}
					if (preg_match('/task=category/', $_alias)) {
						preg_match_all('/id\=(\d+)/', $_alias, $catid);
						$value = $catid[1][0];
						$table = 'k2_categories';
						$context = 'category';
					}
					if (preg_match('/view=item/', $_alias) && preg_match('/layout=item/', $_alias)) {
						preg_match_all('/id\=(\d+)/', $_alias, $itemid);
						$value = $itemid[1][0];
					}
					$redirect = true;
				}
			} else {
				// if the link is FRIENDLY SEO URL link.
				if (in_array('itemlist', $url) && in_array('category', $url)) {
					$redirect = true;
					$table = 'k2_categories';
					$context = 'category';
					preg_match('/^(\d+)/', $id, $_match);
					if (!empty($_match[1]))
						$value = $_match[1];
				} else if (in_array('itemlist', $url) && in_array('tag', $url)) {
					$redirect = true;
					$table = 'k2_tags';
					$field = 'name';
					$wherefield = 'name';
					$context = 'tag';
					preg_match('/^(\d+)/', $id, $_match);
					if (!empty($_match[1]))
						$value = $_match[1];
					else $value=$id;
				} else if (in_array('item', $url)) {
					$redirect = true;
					preg_match('/^(\d+)/', $id, $_match);
					if (!empty($_match[1]))
						$value = $_match[1];
				}
			}
		}

		$contentid=NULL;
		if (!empty($value)) {
			if ($table != 'k2_tags') {
				$db = JFactory::getDbo();
				$query = "SELECT id, ".$db->quoteName('key')." FROM #__associations WHERE context LIKE 'ja_migration.".$context."' AND id = ".$value;
				$db->setQuery($query);
				$assoc = $db->loadObject();
				if (!empty($assoc->key))
					$contentid = $assoc->key;
			} else {
				$value = strtolower($value);
				$query = $db->getQuery(true);
				$query->select('id')
					->from($db->quoteName('#__tags'))
					->where('alias LIKE "%'.$value.'"');
				$db->setQuery($query);
				$_item = $db->loadObject();
				if (!empty($_item->id))
					$contentid = $_item->id;
			}
		}

		if (!empty($contentid)) {
			$pu = parse_url(Juri::root());
    		$domain = $pu["scheme"] . "://" . $pu["host"];
			if(!class_exists('ContentHelperRoute'))
				require_once (JPATH_SITE . '/components/com_content/helpers/route.php');
			if ($table == 'k2_categories') {
				$redurl = str_replace('/component/content/category', '', JRoute::_(ContentHelperRoute::getCategoryRoute($contentid)));
			} elseif ($table == 'k2_tags') {
				$redurl = JRoute::_('index.php?option=com_tags&view=tag&id='.$contentid.'-'.$value);
			} else {
				$article = JTable::getInstance("content");
				$article->load($contentid);
				$redurl = JRoute::_(ContentHelperRoute::getArticleRoute($contentid, $article->get('catid')));
			}
// 			if ($option == 'com_k2')
// 				$redurl.='&jaredirect';
// 			else $redurl.='?jaredirect';

			$query = $db->getQuery(true);
			$query->select($db->quoteName('id').', '.$db->quoteName('published'));
			$query->from($db->quoteName('#__redirect_links'));
			$query->where($db->quoteName('old_url') . ' LIKE '. $db->quote($uri));
			$db->setQuery($query);
			$results = $db->loadObject();
			// if we insert the link already worked then stop the process.
			if (!empty($results) && $results->published==1) return;

			if (!$this->checkComponent('com_k2')) {
// 				$mainframe->redirect(htmlspecialchars_decode(preg_replace('/Itemid=\d+/', 'Itemid=1', $redurl)));
				$lasturl = htmlspecialchars_decode(preg_replace('/[\?\&]Itemid=\d+/', '', $redurl));
				$query = $db->getQuery(true);
				$columns = array('old_url', 'new_url', 'hits', 'published', 'created_date', 'modified_date', 'header');
				$values = array($db->quote($uri), $db->quote($domain.$lasturl), 0, 1, $db->quote(date('Y-m-d H:i:s')), $db->quote(date('Y-m-d H:i:s')), 301);
				if (!empty($results)) {
					// update
					$query->update($db->quoteName('#__redirect_links'))->set(
						array(
							$db->quoteName('new_url') . ' = ' . $db->quote($domain.$lasturl),
							$db->quoteName('published') . ' = 1',
						    $db->quoteName('modified_date') . ' = '.$db->quote(date('Y-m-d H:i:s'))
						)
					)->where(
						array(
							$db->quoteName('id') . ' = '.$results->id
						)
					);
				} else {
					// add
					$query
						->insert($db->quoteName('#__redirect_links'))
						->columns($db->quoteName($columns))
						->values(implode(',', $values));
				}
				$db->setQuery($query);
				$db->execute();
			} else {
				// select to check if we can view the item.
				$query = $db->getQuery(true);
				$query->select($field)
					->from($db->quoteName('#__'.$table))
					->where($wherefield.' = '.$db->quote($value));
				if ($table != 'k2_tags') {
					$query->where('(trash=0 AND published=1)');
				} else {
					$query->where('published=1');
				}
				$db->setQuery($query);
				$check_redirect = $db->loadResult();
				if (empty($check_redirect)) {
// 					$mainframe->redirect(htmlspecialchars_decode(preg_replace('/Itemid=\d+/', 'Itemid=1', $redurl)));
					$lasturl = htmlspecialchars_decode(preg_replace('/[\?\&]Itemid=\d+/', '', $redurl));
					$query = $db->getQuery(true);
					$columns = array('old_url', 'new_url', 'hits', 'published', 'created_date', 'modified_date', 'header');
					$values = array($db->quote($uri), $db->quote($domain.$lasturl), 0, 1, $db->quote(date('Y-m-d H:i:s')), $db->quote(date('Y-m-d H:i:s')), 301);
					if (!empty($results)) {
						// update
						$query->update($db->quoteName('#__redirect_links'))->set(
							array(
								$db->quoteName('new_url') . ' = ' . $db->quote($domain.$lasturl),
								$db->quoteName('published') . ' = 1',
								$db->quoteName('modified_date') . ' = '.$db->quote(date('Y-m-d H:i:s'))
							)
						)->where(
							array(
								$db->quoteName('id') . ' = '.$results->id
							)
						);
					} else {
						// add
						$query
							->insert($db->quoteName('#__redirect_links'))
							->columns($db->quoteName($columns))
							->values(implode(',', $values));
					}
					$db->setQuery($query);
					$db->execute();
				}
			}
		}
	}

	function add_uncategorised_to_tbl_jcat(){
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		
		// check if the site already has uncategorised row in tbl `#__categories`
		$query->select('id')
			->from('`#__categories`')
			->where('`path` = "uncategorised"')
			->where('`extension` = "com_content"');
		$db->setQuery($query);
		$unCat = $db->loadResult();
		if (!empty($unCat)) return ;

		/* $query->select('user_id')
			->from($db->quoteName('#__user_usergroup_map'))
			->where($db->quoteName('group_id') .' IN(8, 16, 16)');
		$db->setQuery($query);
		$adminUser = $db->loadResult(); */
		
		// inserting an uncategorised row into #__categories
		$data = [
			'id' => $db->quote('NULL'), 
			'asset_id' => 27, 
			'path' => $db->quote('uncategorised'), 
			'extension' => $db->quote('com_content'), 
			'title' => $db->quote('Uncategorised'), 
			'alias' => $db->quote('uncategorised'), 
			'published' => 1, 
			'access' => 1, 
			'params' => $db->quote('{"category_layout":"","image":""}'), 
			'metadata' => $db->quote('{"author":"","robots":""}'), 
			// 'created_user_id' => $db->quote($adminUser), // default = 0
			'created_time' => $db->quote(date('Y-m-d H:i:s')), 
			// 'modified_user_id' => $db->quote($adminUser), // default = 0
			'modified_time' => $db->quote(date('Y-m-d H:i:s')),
			'language' => $db->quote('*')
		];
		$query->clear();
		$query->insert('`#__categories`')
			->columns(array_keys($data))
			->values(implode(',', array_values($data)));
		try{
			$db->setQuery($query);
			$db->execute();
		}catch(Exception $e){
			echo "An error occurred: " . $e->getMessage();
		}
	}
}