<?php
/**
 * $JA#COPYRIGHT$
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.form.formfield');

require_once(dirname(__FILE__).'/../behavior.php');
class JFormFieldJapanel extends JFormField {
    protected $type = 'Japanel';
    protected $asset_path = 'modules/mod_jaslideshow/assets/elements/';
    
    protected function getInput() {
        $this->init();
    	return null;
    }
    
    protected function init() {
    	$doc = JFactory::getDocument();
        $path = JURI::root() . $this->asset_path;

        $doc->addScript($path.'japanel/depend.js');

        if (!version_compare(JVERSION, '4', '>=')){
			JHtml::_('behavior.framework', true);
		}
        if(version_compare(JVERSION, '3.0', 'lt')) {
        	JHTML::_('JABehavior.jquery');
        	//JHTML::_('JABehavior.jquerychosen', '.pane-slider select');
        	JHTML::_('JABehavior.jquerychosen', '.form-validate select');
        	
        	$doc->addStyleSheet($path.'japanel/style.css');
        	$doc->addScript($path.'japanel/script.js');
        } else {
        	$doc->addStyleSheet($path.'japanel/style30.css');
        	$doc->addScript($path.'japanel/script30.js');
        }
        return null;
    }
    
    protected function depend() {
		$group_name = 'jform';
    	preg_match_all('/jform\\[([^\]]*)\\]/', $this->name, $matches);
		
		if(!isset($matches[1]) || empty($matches[1])){
			preg_match_all('/jaform\\[([^\]]*)\\]/', $this->name, $matches);
			$group_name = 'jaform';
		}
		
		
		$script = '';
		if(isset($matches[1]) && !empty($matches[1])) {
			foreach ($this->element->children() as $option){
				$elms = preg_replace('/\s+/', '', (string)$option[0]);
				$script .= "
					JADepend.inst.add('".$option['for']."', {
						val: '".$option['value']."',
						elms: '".$elms."',
						group: '".$group_name . '[' . @$matches[1][0] . ']'."'
					});";
			}
		}
		if(!empty($script)) {
			$doc = JFactory::getDocument();
			$doc->addScriptDeclaration("
			$(window).addEvent('load', function(){
				".$script."
			});");
		}
    }
}