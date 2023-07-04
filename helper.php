<?php

class ModJASlideshowHelper{

    public static function response($result = array()){
        die(json_encode($result));
    }

    public static function error($msg = ''){
        return self::response(array(
            'error' => $msg
        ));
    }

    public static function save_profileAjax()
    {
        // Initialize some variables

        $app = JFactory::getApplication();
        $input = $app->input;
        $profile = $input->get('profile');
        $encode_data = $input->get('source_data', '', 'RAW'); // = $_GET['source_data']
        $decode_data = urldecode($encode_data);
        $data = json_decode($decode_data, true);

        if (!$profile) {
            return self::error(JText::_('INVALID_DATA_TO_SAVE_PROFILE'));
        }
        if (empty($data)){
        	return self::error(JText::_('Invalid Data!'));
        }

        // 'profiles' . DIRECTORY_SEPARATOR . $profile . '.ini';
        $file = JPATH_ROOT . "/modules/mod_jaslideshow/profiles/$profile.ini";

        if (JFile::exists($file)) {
            @chmod($file, 0644);
        }

        if (!@JFile::write($file, $decode_data)) {
            return self::error(JText::_('OPERATION_FAILED'));
        }

        return self::response(array(
            'message' => sprintf(JText::_('SAVE_PROFILE_SUCCESSFULLY'), $profile),
            'profile' => $profile,
            'type' => 'new'
        ));
    }

    /**
     *
     * Clone Profile
     */
    function duplicate()
    {
        $app = JFactory::getApplication();
        $input = $app->input;
        $profile = $input->get('profile');
        $from = $input->get('from');

        if (!$profile || !$from) {
            return self::error(JText::_('INVALID_DATA_TO_SAVE_PROFILE'));
        }

        $path = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'profiles';
        $source = $path . DIRECTORY_SEPARATOR . $from . '.ini';
        $dest = $path . DIRECTORY_SEPARATOR . $profile . '.ini';
        if (JFile::exists($dest)) {
            return self::error(sprintf(JText::_('PROFILE_EXIST'), $profile));
        }

        $result = array();
        if (JFile::exists($source)) {
            if ($error = @JFile::copy($source, $dest) == true) {
                return self::response(array(
                    'successful' => JText::_('CLONE_PROFILE_SUCCESSFULLY'),
                    'profile' => $profile,
                    'type' => 'duplicate'
                ));
            } else {
                return self::error($error);
            }
        } else {
            return self::error(JText::_(sprintf('PROFILE_NOT_FOUND', $from)));
        }
    }

    /**
     *
     * Delete a profile
     */
    function delete()
    {
        // Initialize some variables
        $app = JFactory::getApplication();
        $input = $app->input;
        $profile = $input->get('profile');
        if (!$profile) {
            return self::error(JText::_('NO_PROFILE_SPECIFIED'));
        }

        $file = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . $profile . '.ini';
        if (JFile::exists($file) && !@JFile::delete($file)) {
            return self::error(sprintf(JText::_('DELETE_FAIL'), $file));
        }

        return self::response(array(
            'successful' => sprintf(JText::_('DELETE_PROFILE_SUCCESSFULLY'), $profile),
            'profile' => $profile,
            'type' => 'delete'
        ));
    }
}

?>