<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class local_go_edit_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;
        $isupdate = !empty($customdata['id']);

        $mform->addElement('header', 'general', get_string('redirectsettings', 'local_go'));

        // Shortname (unique identifier).
        $mform->addElement('text', 'shortname', get_string('shortname', 'local_go'), ['size' => 40]);
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
        $mform->addRule('shortname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('shortname', 'shortname', 'local_go');

        // Target URL.
        $mform->addElement('text', 'url', get_string('targeturl', 'local_go'), ['size' => 60]);
        $mform->setType('url', PARAM_URL);
        $mform->addRule('url', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('url', 'targeturl', 'local_go');

        // Status.
        $mform->addElement('selectyesno', 'status', get_string('status', 'local_go'));
        $mform->setDefault('status', 1);
        $mform->addHelpButton('status', 'status', 'local_go');

        // Category.
        $mform->addElement('text', 'category', get_string('category', 'local_go'), ['size' => 40]);
        $mform->setType('category', PARAM_TEXT);
        $mform->addHelpButton('category', 'category', 'local_go');

        // Comment.
        $mform->addElement('textarea', 'comment', get_string('comment', 'local_go'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('comment', PARAM_TEXT);
        $mform->addHelpButton('comment', 'comment', 'local_go');

        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, $isupdate ? get_string('update') : get_string('add'));

        if ($isupdate) {
            $this->set_data($customdata);
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Validate shortname uniqueness.
        global $DB;
        if ($DB->record_exists_select('local_go', 'shortname = ? AND id <> ?', [$data['shortname'], $data['id'] ?: 0])) {
            $errors['shortname'] = get_string('shortnametaken', 'local_go');
        }

        // Validate URL format.
        if (!preg_match('#^https?://#i', $data['url'])) {
            $errors['url'] = get_string('invalidurl', 'local_go');
        }

        return $errors;
    }
}
