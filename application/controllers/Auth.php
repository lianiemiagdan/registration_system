<?php

class Auth {
    
    function doFbLogin() {
        $user = $this->input->post('userdata');

        /**
         * check user email if existing
         * true get entity then set session
         * false register it
         */
        if (!$this->tank_auth->is_email_available($user['email'])) {

            if (!is_null($user = $this->users->get_user_by_email($user['email']))) { // login ok
                $this->session->set_userdata(array(
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'status' => 1,
                ));

                echo json_encode(array('success' => true, 'message' => 'set userdata'));
            } else {               // fail - wrong login
                echo json_encode(array('success' => false, 'message' => 'email not matched'));
            }
        } else {

            $email_activation = $this->config->item('email_activation', 'tank_auth');

            $user_data = array(
                'username' => $user['name'],
                'email' => $user['email'],
                'password' => $this->get_random_password(),
            );

            $fb_data = array(
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            );

            // validation ok
            if (!is_null($data = $this->tank_auth->create_user(
                            $user_data['username'], $user_data['email'], $user_data['password'], $email_activation, true, $fb_data))) {         // success
                $data['site_name'] = $this->config->item('website_name', 'tank_auth');

                $this->_send_email('welcome', $data['email'], $data);

                $data['login_by_username'] = ($this->config->item('login_by_username', 'tank_auth') AND
                        $this->config->item('use_username', 'tank_auth'));
                $data['login_by_email'] = $this->config->item('login_by_email', 'tank_auth');

                $this->tank_auth->login(
                        $user_data['username'], $user_data['password'], 0, $data['login_by_username'], $data['login_by_email']
                );

                unset($data['password']); // Clear password (just for any case)

                echo json_encode(array('success' => true, 'message' => 'created new record; set userdata'));
            } else {

                echo json_encode(array('success' => false, 'message' => 'di macreate :('));
            }
        }
    }
    
    function get_random_password($chars_min = 6, $chars_max = 8, $use_upper_case = false, $include_numbers = false, $include_special_chars = false) {
        $length = rand($chars_min, $chars_max);
        $selection = 'aeuoyibcdfghjklmnpqrstvwxz';
        if ($include_numbers) {
            $selection .= "1234567890";
        }
        if ($include_special_chars) {
            $selection .= "!@\"#$%&[]{}?|";
        }

        $password = "";
        for ($i = 0; $i < $length; $i++) {
            $current_letter = $use_upper_case ? (rand(0, 1) ? strtoupper($selection[(rand() % strlen($selection))]) : $selection[(rand() % strlen($selection))]) : $selection[(rand() % strlen($selection))];
            $password .= $current_letter;
        }

        return $password;
    }
    
    function logout() {
        $this->tank_auth->logout();

        redirect('/auth/index/logout');
    }
    
    function register() {

        $this->form_validation->set_rules('username', 'Username', 'trim|required|xss_clean|min_length[' . $this->config->item('username_min_length', 'tank_auth') . ']|max_length[' . $this->config->item('username_max_length', 'tank_auth') . ']|alpha_dash');
        $this->form_validation->set_rules('reg_email', 'Email', 'trim|required|xss_clean|valid_email');
        $this->form_validation->set_rules('reg_password', 'Password', 'trim|required|xss_clean|min_length[' . $this->config->item('password_min_length', 'tank_auth') . ']|max_length[' . $this->config->item('password_max_length', 'tank_auth') . ']|alpha_dash');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'trim|required|xss_clean|matches[reg_password]');

        $data['errors'] = array();

        $agreement = $this->input->post('agreement');

        if (isset($agreement) == false) {
            $data['errors'] = array('agreement' => 'The Privacy Policy check is required.');
        }

        $email_activation = $this->config->item('email_activation', 'tank_auth');

        if ($this->form_validation->run() && empty($data['errors'])) {      // validation ok
            if (!is_null($data = $this->tank_auth->create_user(
                            $this->form_validation->set_value('username'), $this->form_validation->set_value('reg_email'), $this->form_validation->set_value('reg_password'), $email_activation))) {         // success
                $data['site_name'] = $this->config->item('website_name', 'tank_auth');

                //  Start: login user
                $login_by_username = ($this->config->item('login_by_username', 'tank_auth') AND $this->config->item('use_username', 'tank_auth'));
                $login_by_email = $this->config->item('login_by_email', 'tank_auth');

                $this->tank_auth->login($this->form_validation->set_value('reg_email'), $this->form_validation->set_value('reg_password'), null, $login_by_username, $login_by_email);
                //  End: login user

                if ($email_activation) {         // send "activate" email
                    $data['activation_period'] = $this->config->item('email_activation_expire', 'tank_auth') / 3600;

                    $this->_send_email('activate', $data['email'], $data);

                    unset($data['password']); // Clear password (just for any case)

                    echo json_encode(array('success' => true, 'message' => $this->lang->line('auth_message_registration_completed_1'), 'profile_link' => site_url() . '/settings/my-profile/' . $this->encrypt->encode($data['user_id'], $this->config->item('encryption_key'), true)));
                } else {
                    if ($this->config->item('email_account_details', 'tank_auth')) { // send "welcome" email
                        $this->_send_email('welcome', $data['email'], $data);
                    }
                    unset($data['password']); // Clear password (just for any case)

                    echo json_encode(array('success' => true, 'message' => $this->lang->line('auth_message_registration_completed_2'), 'profile_link' => site_url() . '/settings/my-profile/' . $this->encrypt->encode($data['user_id'], $this->config->item('encryption_key'), true)));
                }
            } else {
                $errors = $this->tank_auth->get_error_message();
                foreach ($errors as $k => $v)
                    $data['errors'][$k] = $this->lang->line($v);
                echo json_encode(array('success' => false, 'message' => $data['errors']));
            }
        } else {
            $data['errors'] = array_merge($data['errors'], validation_errors_array());
            echo json_encode(array('success' => false, 'message' => $data['errors']));
        }
    }

}
