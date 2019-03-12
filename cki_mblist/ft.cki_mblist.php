<?php

require_once(PATH_THIRD . 'cki_mblist/addon.setup.php');

class Cki_mblist_ft extends EE_Fieldtype
{
    // Needed in order to get the fieldtype to work as a single AND tag pair
    public $has_array_data = true;

    public $info = array(
        'name'      =>  CKI_MBLIST_NAME,
        'version'   =>  CKI_MBLIST_VER
    );

    public function __construct()
    {
        parent::__construct();
        ee()->lang->loadfile(CKI_MBLIST_KEY);
    }

    /**
     * Previously saved cell data
     * @param  [mixed] $data
     */
    public function display_field($data)
    {
        $text_direction = ($this->settings['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr';
        $member_list = array();
        $deleted_user_message = '';

        ee()->db->select('group_title, exp_members.member_id, screen_name');
        ee()->db->from('exp_members');
        ee()->db->join('exp_member_groups', 'exp_members.group_id = exp_member_groups.group_id');
        ee()->db->join('exp_member_data', 'exp_member_data.member_id = exp_members.member_id');
        ee()->db->order_by('exp_member_groups.group_id asc, exp_members.screen_name');

        if ($this->settings[CKI_MBLIST_KEY]['group_ids']) {
            ee()->db->where_in('exp_members.group_id', explode('|', $this->settings[CKI_MBLIST_KEY]['group_ids']));
        }
        $q = ee()->db->get();

        // Create a blank option
        $member_list[''] = "None";
        $member_id_array =  array();

        // Setup the member list array to send to the form_dropdown function
        foreach ($q->result_array() as $member) {
            $member_list[$member['group_title']][$member['member_id']] = $member['screen_name'];
            $member_id_array[$member['member_id']] = $member['screen_name'];
        }

        // Quickly check to see (if on the EDIT page) that the previously selected member still exists
        if (!array_key_exists($data, $member_id_array) && $data != '') {
            // If not, append a warning message
            $deleted_user_message = "&nbsp;<span class='notice'>Selected member no longer exists.</span>";
        }

        return form_dropdown($this->field_name, $member_list, $data, 'dir="'.$text_direction.'" id="'.$this->field_id.'"').$deleted_user_message;
    }

    /**
     * Get the data out of the database
     */
    public function pre_process($data)
    {
        ee()->db->select('*');
        ee()->db->from('exp_members');
        ee()->db->join('exp_member_groups', 'exp_members.group_id = exp_member_groups.group_id');
        ee()->db->join('exp_member_data', 'exp_member_data.member_id = exp_members.member_id');
        ee()->db->limit(1);
        ee()->db->where('exp_members.member_id', $data);
        $q = ee()->db->get();

        if ($q->num_rows()) {
            $qa = $q->result_array();
            return $qa[0];
        } else {
            return false;
        }
    }

    /**
     * Parse the front end data
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        // Promote the use of the "show" parameter to select member data,
        // but keep backward compatibility by allowing "get" to still be used
        if (isset($params['show'])) {
            $params['get'] = $params['show'];
        }

        // Check everything is in order and the requested array key exists
        if ($data !== false && isset($params['get']) && array_key_exists($params['get'], $data)) {
            return $data[$params['get']];
        } else {
            if (is_array($data) && array_key_exists('member_id', $data)) {
                return $data['screen_name'];
            } else {
                return false;
            }
        }
    }

    /**
     * Validate the drop down selection
     */
    public function validate($data)
    {
        // Check that that a selection has been made
        if ($data != '') {
            // Query the database to see if selected member exists
            $q = ee()->db->get_where('exp_members', array('member_id' => $data), 1);

            if ($q->num_rows() ===  1) {
                return true;
            } else {
                return "The Member you have selected does not exist";
            }
        }
    }

    public function display_settings($data)
    {
        ee()->db->select('group_id, group_title');
        ee()->db->from('exp_member_groups');
        ee()->db->order_by('group_id asc');
        $q = ee()->db->get();

        $field_options = array();

        // Setup the member list array to send to the form_dropdown function
        foreach ($q->result_array() as $group) {
            $field_options['group_ids'][$group['group_id']] = $group['group_title'];
        }

        $field_values = $this->_normalise_settings();

        // Is this a new field?
        if (array_key_exists(CKI_MBLIST_KEY, $data)) {
            $field_values['group_ids'] = explode('|', $data[CKI_MBLIST_KEY]['group_ids']);
        }

        $rows[] = array(
            'title'  => lang('group_ids_label'),
            'desc'   => lang('group_ids_label_notes'),
            'fields' => array(
                'cki_mblist[group_ids]' => array(
                    'type'    => 'checkbox',
                    'choices' => $field_options['group_ids'],
                    'value' => $field_values['group_ids']
                ),
            ),
        );

        return array(
            'field_options_cki_mblist' => array(
                'label'    => 'field_options',
                'group'    => 'cki_mblist',
                'settings' => $rows,
            ),
        );
    }

    public function save_settings($data)
    {
        return array(CKI_MBLIST_KEY => $this->_normalise_settings($_POST[CKI_MBLIST_KEY], true));
    }

    public function install()
    {
        return true;
    }

    public function update()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch from array
     *
     * This is a helper function to retrieve values from an array
     * It has been borrowed, verbatim, from EE->input
     *
     * @access  private
     * @param   array
     * @param   string
     * @param   bool
     * @return  string
     */
    public function _fetch_from_array(&$array = array(), $index = '', $xss_clean = false)
    {
        if (! isset($array[$index])) {
            return false;
        }

        if ($xss_clean === true) {
            return ee()->security->xss_clean($array[$index]);
        }

        return $array[$index];
    }

    /**
     * Normalise Settings
     * Ensures all setting values are acceptable formats/ranges before saving
     * If passed array is empty, it returns an array of default settings
     *
     * @param   array
     * @param   bool
     * @return  array settings
     */
    public function _normalise_settings(&$array = array(), $xss_clean = false)
    {
        return array(
            'group_ids' => ($this->_fetch_from_array($array, 'group_ids', $xss_clean))
                ? implode('|', $this->_fetch_from_array($array, 'group_ids', $xss_clean)) : ''
        );
    }
}
