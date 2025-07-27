<?php

namespace local_children_management\form;

// moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class add_child_form extends \moodleform {
    // Add elements to form.
    public function definition() {
        // A reference to the form is stored in $this->form.
        // A common convention is to store it in a variable, such as `$mform`.
        $mform = $this->_form; // Don't forget the underscore!
        // Add elements to your form.
        $mform->addElement('text', 'studentid', get_string('studentid', 'local_children_management'));

        // Set type of element.
        $mform->setType('studentid', PARAM_TEXT);
        $mform->addRule('studentid', 'Student ID is missing.','required', null, 'client');

        // Add elements to your form.
        $mform->addElement('text', 'username', get_string('username'));

        // Set type of element.
        $mform->setType('username', PARAM_TEXT);

        
        // Add elements to your form.
        $mform->addElement('text', 'firstname', get_string('firstname'));

        // Set type of element.
        $mform->setType('firstname', PARAM_TEXT);

        // Add elements to your form.
        $mform->addElement('text', 'lastname', get_string('lastname'));

        // Set type of element.
        $mform->setType('lastname', PARAM_TEXT);

        
         // Add elements to your form.
        $mform->addElement('text', 'email', get_string('email'));

        // Set type of element.
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', 'Email is missing.','required', null, 'client');

        
         // Add elements to your form.
        $mform->addElement('text', 'phone', get_string('phone'));

        // Set type of element.
        $mform->setType('phone', PARAM_TEXT);
        $mform->addRule('phone', 'Phone number is missing.','required', null, 'client');
        
        
        // Add elements to your form.
        $mform->addElement('text', 'OTP', get_string('OTP', 'local_children_management'));

        // Set type of element.
        $mform->setType('OTP', PARAM_INT);
        $mform->addRule('OTP', 'OTP code is required', 'required', null, 'client');
        
        $mform->addElement('html', 
        '<div class="fitem fitem_fsubmit mb-3 row">
            <div class="col-md-4 col-form-label d-flex pb-0 pe-md-0">
                <div class="form-label-addon d-flex align-items-center align-self-start">
                </div>
            </div>
            <div class="felement fsubmit col-md-7 d-flex flex-wrap align-items-start">
                <input type="button" class="btn btn-secondary" id="send_otp_button" value="' 
                . get_string('sendotp', 'local_children_management')
                . '"> <span id="otp_status_message" class="text-info ml-2"></span>
            </div>
        </div>');

    
        $this->add_action_buttons();
        
    }

    // Custom validation should be added here.
    function validation($data, $files) {
        return [];
    }
}
