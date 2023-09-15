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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
/**
 * Manage Sync Profile
 * for migrating data from K2 component (version 2) to Content component
 */
class JFormFieldSync extends JFormField
{
	protected $type = 'Sync';
	protected $layoutBasePath = null;
	protected $pathMigrate = '';
	/**
	 * @var JADataMigrator
	 */
	protected $migrator = null;


	protected function init()
	{
		$this->layoutBasePath = JPATH_ROOT . '/plugins/system/jak2tocomcontentmigration/layouts';
		$this->pathMigrate 	= JPATH_ROOT . '/plugins/system/jak2tocomcontentmigration/helpers';

		require_once($this->pathMigrate.'/migrator.php');
		$this->migrator = new JADataMigrator();
	}

	protected function getInput()
	{
		$this->redirectButton();
		//check K2 component is installed
		if (!$this->checkComponent('com_k2')) {
			return '<p style="color:red;">'.JText::_('JA_K2TOCONTENT_K2_WARNING').'</p>';
		}
		$this->init();

		$document = JFactory::getDocument();
		
		$displayData['categories'] = $this->renderSyncCategories();
		if (file_exists(JPATH_ROOT . '/plugins/system/jacontenttype/jacontenttype.php'))
		{
		    $plugins = JPluginHelper::getPlugin('system');
		    foreach ($plugins AS $pl) {
		    	if ($pl->name == 'jacontenttype') {
		    		$document->addScript(JUri::root(true).'/plugins/system/jak2tocomcontentmigration/models/fields/assets/js/migrate_data.js');
		    		$displayData['exgroups'] = $this->getK2ExtraFieldGroups();
					$displayData['exfields'] = $this->renderSyncExtraFields();
					$displayData['rec'] = $this->recplugin();
		    	}
		    }
		}

		$html =  JLayoutHelper::render('sync.panel', $displayData, $this->layoutBasePath);
		return $html;
	}
	
	protected function redirectButton() {
		$html = '';
		$syncParams = JComponentHelper::getParams('com_content')->get('sync', false);
		$jarec = 1; // the default value;
		if (isset($syncParams->jaredirect)) $jarec = $syncParams->jaredirect;
		$html.='<p>&nbsp;</p><div class="control-group">
					<div class="control-label">
						<label id="jform_jaredirect-lbl" for="jform_jaredirect" class="hasTooltip" title="" data-original-title="">
							'.JText::_('JA_REDIRECT_SETTING').'
						</label>
					</div>
					<div class="controls">
						<fieldset id="jform_jaredirect" class="radio btn-group btn-group-yesno">
							<input type="radio" id="jform_jaredirect0" name="'.$this->name.'[jaredirect]" value="1" '.($jarec == 1 ? ' checked="" ':'').'>
							<label for="jform_jaredirect0" class="btn '.($jarec == 1 ? ' btn-success ':'').'">'.JText::_('JYES').'</label>
							<input type="radio" id="jform_jaredirect1" name="'.$this->name.'[jaredirect]" value="0" '.($jarec == 0 ? ' checked="" ':'').'>
							<label for="jform_jaredirect1" class="btn '.($jarec == 0 ? ' btn-danger ':'').'">'.JText::_('JNO').'</label>
						</fieldset>
						<p>&nbsp;</p>
						<p>'.JText::_('JA_REDIRECT_INFO').'</p>
					</div>

					<div class="control-label">
						<label id="jform_jabatchsize-lbl" for="jform_jabatchsize" class="hasTooltip" title="" data-original-title="">
							'.JText::_('Batch Size: ').'
						</label>
					</div>
					<div class="controls">
						<input type="text" id="jform_jabatchsize" name="'.$this->name.'[jabatchsize]" value="500">
					</div>
				</div>';
		echo $html;
	}

	protected function recplugin() {
		$html = '';
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('extension_id'))
			->from('#__extensions')
			->where($db->quoteName('element') .'='.$db->quote('redirect'))
			->where($db->quoteName('name') .'='.$db->quote('plg_system_redirect'))
			->where($db->quoteName('access') .'='.$db->quote('1'))
			->where($db->quoteName('enabled') .'='.$db->quote('1'));
		$db->setQuery($query);
		$rec = $db->loadResult();
		if (empty($rec))
			$html .= '<p style="color:red;">'.JText::_('JA_K2TOCONTENT_REDIRECT_WARNING').'</p>';

		return $html;
	}

	protected function renderSyncCategories() {
		$joomcats = $this->getContentCategories();
		$syncParams = JComponentHelper::getParams('com_content')->get('sync', false);
		$categories = $this->getK2Categories(0, '', array());
		$HTEXT = '<table class="table table-striped">
		<thead>
			<tr>
				<th width="450">' . JText::_("JA_K2TOCONTENT_NAME") . '</th>
				<th width="450"></th>
				<th width="450">' . JText::_("JA_K2TOCONTENT_CONVERT") . '</th>
			</tr>
		</thead>
		<tbody>
		';

		foreach ($categories as $item) {
			if(isset($item->id) && $item->id > 0) {
				$assoc = $this->migrator->checkAssociation($item->id, 'category');
				$value = $assoc ? $assoc->key : (isset($syncParams->catID->{$item->id}) ? $syncParams->catID->{$item->id} : 'auto');

				$class = ' class="catID fixj35" percat="'.$item->parent.'"';

				if ($item->parent != 0)
					$class = ' class="catID fixj35 subcat" percat="'.$item->parent.'" ';

				$HTEXT .= '<tr>
					<td> &nbsp;&nbsp;&nbsp; '.$item->treename.' &nbsp;&nbsp;&nbsp; </td>
					<td>

					</td>
					<td>

						<select '.($assoc ? ' disabled="disabled" ':'').' '.$class.' idc="' . $item->id . '" jid="catid'.$item->id.'">
							<option '.($value == 'auto' ? ' selected="selected" ' : '').' value="auto">' . JText::_("JA_K2TOCONTENT_AUTO") . '</option>
							<option '.($value == 'ignore' ? ' selected="selected" ' : '').' value="ignore">' . JText::_("JA_K2TOCONTENT_IGNORE") . '</option>';

				for ($p=0;$p<count($joomcats);$p++) {
					$selected = ($value == $joomcats[$p]->value) ? ' selected="" ' : '';
					$HTEXT .= '<option '.$selected.' value="'.$joomcats[$p]->value.'">'.$joomcats[$p]->text.'</option>';
				}

				$HTEXT .= '		
						</select>
						'.($assoc ? '<input type="hidden" name="'.$this->name.'[catID]['.$item->id.']" value="'.$value.'" />' : '
							<input type="hidden" id="catid'.$item->id.'" value="'.$value.'" name="'.$this->name.'[catID]['.$item->id.']" />
						').'
					</td>
				</tr>';
			}
		}
		$HTEXT .= '</tbody>
		</table>';
		
		return $HTEXT;
	}

	protected function renderSyncExtraFields() {
		$auto = '<option file="" value="auto">' . JText::_("JA_K2TOCONTENT_AUTO") . '</option>';
		$ignore = '<option file="" value="ignore">' . JText::_("JA_K2TOCONTENT_IGNORE") . '</option>';
		$fselect = 'var exgselect=[];
		var exfselect=[];';
		$document = JFactory::getDocument();
		$syncParams = JComponentHelper::getParams('com_content')->get('sync', NULL);
		$newExtraGroup = JComponentHelper::getParams('com_content')->get('exgroup', false);
		$exgipName = isset($syncParams->exgipName) ? $syncParams->exgipName : NULL;
		$exfipName = isset($syncParams->exfipName) ? $syncParams->exfipName : NULL;
		$merged_groups = isset($syncParams->extraGroup) ? $syncParams->extraGroup : NULL;
		$merged_fields = isset($syncParams->extraField) ? $syncParams->extraField : NULL;
		if (!empty($merged_groups))
			foreach ($merged_groups AS $kg => $mg) {
				$fselect.='exgselect['.$kg.']="'.$mg.'";';
				foreach ($merged_fields->{$kg} AS $kf => $mf) {
					$fselect.='exfselect['.$kf.']="'.$mf.'";';
				}
			}

		$listFile = $this->getContentTypes();
		$exgselect='';
		$selType='';
		$selType .= 'var selectList = [];';
		$hidden = '';
		
		for ($p=0;$p<count($listFile);$p++) {
			$xml = simplexml_load_file($listFile[$p]);
			$fields = $xml->fields->fieldset->field;
			$selType .= 'selectList["'.strtolower($xml->type).'"] = [\''.$ignore.'\',';
			foreach ($fields AS $x => $f) {
				$attributes = $f->attributes();
				if ($attributes['name'] != 'ctm_content_type') {
					$selType .= '\'<option value="'.$attributes['name'].'">' . JText::_($attributes['label']) . ' ('.$attributes['name'].') </option>\',';
				}
			}
			$selType = rtrim($selType, ',').'];';
			$exgselect .= '<option value="'.strtolower($xml->type).'">'.$xml->title.'</option>';
			$hidden .= '<input type="hidden" value="'.$listFile[$p].'" name="'.$this->name.'[extraType]['.strtolower($xml->type).']" />';
		}
		
		$hidden .= '<input type="hidden" value="auto" name="'.$this->name.'[extraType][auto]" />';
		$hidden .= '<input type="hidden" value="ignore" name="'.$this->name.'[extraType][ignore]" />';
		$selType .= '
		selectList["auto"] = [\''.$auto.'\',\''.$ignore.'\'];
		selectList["ignore"] = [\''.$ignore.'\'];
		'.$fselect;
		$document->addScriptDeclaration($selType);

		// Initialize some field attributes.
		$class = $this->element['class'] ? ' class="checkboxes ' . (string) $this->element['class'] . '"' : ' class="checkboxes"';

		// Get the field options.
		$options = $this->getK2ExtraFields();

		// Build the checkbox field output.
		$group = 0;
		$groups = array();
		foreach ($options as $i => $option)
		{
			if($group != $option->group)
			{
				$group = $option->group;
				$groups[$option->group]['name'] = $option->gname;
				$groups[$option->group]['id'] = $option->gid;
			}
		}

		$k = 0;

		$html = '<table class="table table-striped"><thead>
			<th width="100">'.JText::_('JA_K2TOCONTENT_GROUPS').'</th>
            <th width="100">'.JText::_('JA_K2TOCONTENT_FIELDS').'</th>
            <th width="250">'.JText::_('JA_K2TOCONTENT_MIGRATION_OPTION').'</th>
            <th width="100">'.JText::_('JA_K2TOCONTENT_RENAME_IF_UNICODE').'</th>
		</thead><tbody>';
		foreach($groups AS $key=>$g)
		{
			$assoc = $this->migrator->checkAssociation($g['id'], 'extraGroup');
			$value = $assoc ? $assoc->key : (isset($syncParams->extraGroup->{$g['id']}) ? $syncParams->extraGroup->{$g['id']} : 'auto');
			$html .= '
				<tr>
					<td>'.$g['name'].'</td>
					<td></td>
					<td><select '.($assoc ? ' disabled="disabled" ' : '').' class="extraGroup fixj35" jid="exg'.$g['id'].'" name="'.$this->name.'[extraGroup]['.$g['id'].']" idg="'.$g['id'].'">
							<option value="auto" '.($value == 'auto' ? ' selected="selected" ' : '').'>' . JText::_("JA_K2TOCONTENT_AUTO") . '</option>
							<option value="ignore" '.($value == 'ignore' ? ' selected="selected" ' : '').'>' . JText::_("JA_K2TOCONTENT_IGNORE") . '</option>';

			for ($p=0;$p<count($listFile);$p++) {
				$xml = simplexml_load_file($listFile[$p]);
				$html .= '<option value="'.$xml->type.'" '.($value == $xml->type ? ' selected="selected" ' : '').'>'.$xml->title.'</option>';
			}
			$html .='
						</select>

						'.($assoc ? '<input type="hidden" name="'.$this->name.'[extraGroup]['.$g['id'].']" value="'.$value.'" />' : '
							<input type="hidden" name="'.$this->name.'[extraGroup]['.$g['id'].']" id="exg'.$g['id'].'" value="'.$value.'" />
						').'
						</td>
					<td>

						<span class="extratext" idg="'.$g['id'].'" '.($value == 'ignore' ? ' style="visibility: hidden;" ' : '').'>
							<input '.($assoc ? ' disabled="disabled" ' : '').' type="text" value="'.($assoc ? $assoc->key : (isset($syncParams->exgipName->{$g['id']}) ? $syncParams->exgipName->{$g['id']} : JADataMigrator::generateSafeName($g['name']))).'" name="'.$this->name.'[exgipName]['.$g['id'].']" class="form-control" />
						</span>
					</td>
				<tr>
			';

			foreach($options as $i => $option)
			{
				if($option->group == $key)
				{
					$assoc = $this->migrator->checkAssociation($option->id, 'extraField');
					$value = $assoc ? $assoc->key : (isset($syncParams->extraField->{$g['id']}->{$option->id}) ? $syncParams->extraField->{$g['id']}->{$option->id} : 'auto');
					$html .= '
							<tr>
								<td></td>
								<td>' . JText::_($option->text) . ' </td>
								<td><select '.($assoc ? ' disabled="disabled" ' : '').' idf="'.$option->id.'" jid="exf'.$option->id.'" idg="'.$g['id'].'" class="exf fixj35 extraField-'.$g['id'].'" name="'.$this->name.'[extraField]['.$g['id'].']['.$option->id.']"  data-value="'.$value.'">
								 		<option value="auto" '.($value == 'auto' ? ' selected="selected" ' : '').'>' . JText::_("JA_K2TOCONTENT_AUTO") . '</option>
										<option value="ignore" '.($value == 'ignore' ? ' selected="selected" ' : '').'>' . JText::_("JA_K2TOCONTENT_IGNORE") . '</option>
									</select>
									'.($assoc ? '<input type="hidden" name="'.$this->name.'[extraField]['.$g['id'].']['.$option->id.']" value="'.$value.'" />' : '
										<input type="hidden" name="'.$this->name.'[extraField]['.$g['id'].']['.$option->id.']" id="exf'.$option->id.'" value="'.$value.'" />
									').'
									</td>
								<td>

									<span class="extratext" idf="'.$option->id.'" idg="'.$g['id'].'" '.($value == 'ignore' ? ' style="visibility: hidden;" ' : '').'>
										<input '.($assoc ? ' disabled="" ' : '').' type="text" value="'.($assoc ? $assoc->key : (isset($syncParams->exfipName->{$g['id']}->{$option->id}) ? $syncParams->exfipName->{$g['id']}->{$option->id} : JADataMigrator::generateSafeName($option->text))).'" name="'.$this->name.'[exfipName]['.$g['id'].']['.$option->id.']"  class="form-control" />
									</span>
								</td>
							</tr>
						';
				}
				$hidden .= '<input type="hidden" value="'.$option->text.'" name="'.$this->name.'[exfName]['.$option->id.']" />';
			}
			$hidden .= '<input type="hidden" value="'.$g['name'].'" name="'.$this->name.'[exgName]['.$g['id'].']" />';
			$k++;

			// End the checkbox field output.
			
		}
		$html .= '
				</tbody>
			</table>';
		return $html.$hidden;
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

	public function getContentTypes() {
		$files = JFolder::files(JPATH_ROOT.'/plugins/system/jacontenttype/models/types', '\.xml$', false, true);

		$templates = JFolder::folders(JPATH_ROOT.'/templates/', '.', false, true);
		foreach($templates as $template) {
			if(JFolder::exists($template.'/contenttype/types/')) {
				$files = array_merge($files, JFolder::files($template.'/contenttype/types/', '\.xml$', false, true));
			}
		}

		return $files;
	}

	protected function getAssociations ($type) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('k.id_k2').','.$db->quoteName('k.joomid').','.$db->quoteName('k.config').','.$db->quoteName('k.merged'))
			->from($db->quoteName('#__contenttype_k2', 'k'))
			->where($db->quoteName('k.type').'='.$db->quote($type));
		if ($type == 'category') {
			$query->select($db->quoteName('c.title'));
			$query->join('LEFT', $db->quoteName('#__categories', 'c').' ON (k.joomid = c.id)');
		}

		$db->setQuery($query);
		$data = $db->loadObjectList('id_k2');
		return $data;
	}

	protected function getContentCategories()
	{
		$options = JHtml::_('category.options', 'com_content');
		return $options;
	}
	/**
	 * Show element data on K2
	 * @param int $id
	 * @param strig $indent
	 * @param array $list
	 * @param int $maxlevel
	 * @param int $level
	 * @param int $type
	 * @return array list categories element
	 */
	protected function getK2Categories($id, $indent, $list, $maxlevel = 9999, $level = 0, $type = 1)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("c.*")
			->from('#__k2_categories c')
			//->leftJoin("#__contenttype_k2 ctt ON (ctt.id_k2 = c.id AND ctt.type=".$db->quote('category').")")
			->where($db->quoteName('c.parent') .'='.$db->quote($id))
			->order('c.id ASC');
		$db->setQuery($query);
		$children = $db->loadObjectList();

		if (@$children && $level <= $maxlevel) {
			foreach ($children as $v) {
				$id = $v->id;

				if ($type) {
					$pre = '<sup>|_</sup>&nbsp;';
					$spacer = '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				} else {
					$pre = '- ';
					$spacer = '&nbsp;&nbsp;';
				}

				if ($v->parent == 0) {
					$txt = $v->name;
				} else {
					$txt = $pre . $v->name;
				}
				$pt = $v->parent;
				$list[$id] = $v;
				$list[$id]->treename = "{$indent}{$txt}";
				$list[$id]->children = count(@$children);
				$list[$id]->haschild = true;
				$list = $this->getK2Categories($id, $indent . $spacer, $list, $maxlevel, $level + 1, $type);
			}
		} else {
			if(isset($list[$id])) {
				$list[$id]->haschild = false;
			}
		}
		return $list;
	}

	protected function getK2ExtraFieldGroups()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from('#__k2_extra_fields_groups');
		$db->setQuery($query);
		return $db->loadObjectList();
	}

	protected function getK2ExtraFields()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('f.id, f.name AS fname, f.group, f.type, f.published, g.name AS gname, g.id AS gid')->
		from('#__k2_extra_fields f')
		->join('INNER', '#__k2_extra_fields_groups g ON g.id = f.group')
		->where('f.published = 1')->where('f.type <> "csv"')
		->order('f.group, f.ordering');
		$db->setQuery($query);
		$list = $db->loadAssocList();
		// Initialize variables.
		$options = array();

		if(count($list)) {
			foreach ($list as $option)
			{
				// Create a new option object based on the <option /> element.
				$tmp = JHtml::_(
					'select.option', $option['id'], $option['fname'], 'value', 'text',
					($option['published'] == 0)
				);

				// Set some option attributes.
				$tmp->class = '';

				// Set some JavaScript option attributes.
				$tmp->onclick 	= '';
				$tmp->title		= $option['fname'];
				$tmp->type 		= $option['type'];
				$tmp->group 	= $option['group'];
				$tmp->gname 	= $option['gname'];
				$tmp->id	 	= $option['id'];
				$tmp->gid	 	= $option['gid'];

				// Add the option object to the result set.
				$options[] = $tmp;
			}
		}
		//reset($options);
		return $options;
	}

}