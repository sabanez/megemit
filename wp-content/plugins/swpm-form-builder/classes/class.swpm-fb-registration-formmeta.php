<?php

/**
 * Description of class
 *
 * @author nur
 */
class SwpmFbRegistrationFormmeta extends SwpmFbFormmeta {

    public function create() {
        $field = new SwpmFbFieldmeta();
        $field->key = 'fieldset';
        $field->type = 'fieldset';
        $field->name = 'Fieldset';
        $field->sequence = 0;
        $this->fields[$field->sequence] = $field;

        $field = new SwpmFbFieldmeta();
        $field->key = 'user_name';
        $field->type = 'text';
        $field->name = 'Username';
        $field->description = 'Username';
        $field->size = 'medium';
        $field->required = 'yes';
        $field->sequence = 1;
        $this->fields[$field->sequence] = $field;

        $field = new SwpmFbFieldmeta();
        $field->key = 'password';
        $field->type = 'password';
        $field->name = 'Password';
        $field->description = 'Password';
        $field->size = 'medium';
        $field->required = 'yes';
        $field->sequence = 2;
        $this->fields[$field->sequence] = $field;

        $field = new SwpmFbFieldmeta();
        $field->key = 'membership_level';
        $field->type = 'text';
        $field->name = 'Membership Level';
        $field->description = '';
        $field->size = 'medium';
        $field->required = 'yes';
        $field->readnly = 'yes';
        $field->sequence = 3;
        $this->fields[$field->sequence] = $field;

        $field = new SwpmFbFieldmeta();
        $field->key = 'primary_email';
        $field->type = 'email';
        $field->name = 'Email';
        $field->description = 'Email';
        $field->size = 'medium';
        $field->required = 'yes';
        $field->sequence = 4;
        $this->fields[$field->sequence] = $field;
        $field = new SwpmFbFieldmeta();
        $field->key = 'verification';
        $field->type = 'verification';
        $field->name = 'Verification';
        $field->description = '(This is for preventing spam)';
        $field->sequence = 5;
        $this->fields[$field->sequence] = $field;

        $field = new SwpmFbFieldmeta();
        $field->key = 'secret';
        $field->type = 'secret';
        $field->name = 'Please enter any two digits';
        $field->description = 'Example: 12';
        $field->size = 'medium';
        $field->required = 'yes';
        $field->sequence = 6;
        $this->fields[$field->sequence] = $field;
        $field = new SwpmFbFieldmeta();
        $field->key = 'submit';
        $field->type = 'submit';
        $field->name = 'Submit';
        $field->sequence = 7;
        $this->fields[$field->sequence] = $field;

        $this->type = SwpmFbFormCustom::REGISTRATION;
        $this->success_message = '<p id="form_success">' . SwpmUtils::_("Registration is complete. You can now log into the site.") . '</p>';
        return $this->save();
    }

}
