<?php   if (!defined('BASEPATH'))   exit('No direct script access allowed');

define('ADMIN', 1);
define('EDITR', 2);
define('SUBSR', 3);

/**
 * Users
 *
 * This model represents user authentication data. It operates the following tables:
 * - user account data,
 * - user profiles
 *
 * @package	Tank_auth
 * @author	Ilya Konyukhov (http://konyukhov.com/soft/)
 */
class Users extends CI_Model {

    private $table_name = 'users';   // user accounts
    private $profile_table_name = 'users_profiles'; // user profiles
    private $makersID = 3;
    private $systemsID = 4;

    function __construct() {
        parent::__construct();

        $ci = & get_instance();
        $this->table_name = $ci->config->item('db_table_prefix', 'tank_auth') . $this->table_name;
        $this->profile_table_name = $ci->config->item('db_table_prefix', 'tank_auth') . $this->profile_table_name;
    }

    function get4DMakers_id() {
        return $this->makersID;
    }

    function get4DSystems_id() {
        return $this->systemsID;
    }

    /**
     * Get user record by Id
     *
     * @param	int
     * @param	bool
     * @return	object
     */
    function get_all() {
        $this->db->from($this->table_name);
        $this->db->where($this->table_name . '.role_id !=', 1);
        $this->db->join($this->profile_table_name, $this->profile_table_name . '.user_id = ' . $this->table_name . '.id');
        $this->db->join('roles', 'roles.role_id = ' . $this->table_name . '.role_id');
        return $this->db->get();
    }

    function count_all_users() {
        $this->db->from($this->table_name);
        return $this->db->count_all_results();
    }

    /**
     * Get user record by Id
     *
     * @param	int
     * @param	bool
     * @return	object
     */
    function get_user_by_id($user_id, $activated) {
        $this->db->where('id', $user_id);
        $this->db->where('activated', $activated ? 1 : 0);

        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1)
            return $query->row();
        return NULL;
    }

    /**
     * Get user record by login (username or email)
     *
     * @param	string
     * @return	object
     */
    function get_user_by_login($login) {
        $this->db->where('LOWER(username)=', strtolower($login));
        $this->db->or_where('LOWER(email)=', strtolower($login));

        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1)
            return $query->row();
        return NULL;
    }

    /**
     * Get user record by username
     *
     * @param	string
     * @return	object
     */
    function get_user_by_username($username) {
        $this->db->where('LOWER(username)=', strtolower($username));

        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1)
            return $query->row();
        return NULL;
    }

    /**
     * Get user record by email
     *
     * @param	string
     * @return	object
     */
    function get_user_by_email($email) {
        $this->db->where('LOWER(email)=', strtolower($email));

        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1)
            return $query->row();
        return NULL;
    }

    function get_info($id) {
        $this->db->from($this->table_name);
        $this->db->join($this->profile_table_name, $this->profile_table_name . '.user_id = ' . $this->table_name . '.id');
        $this->db->join('roles', 'roles.role_id = ' . $this->table_name . '.role_id');
        $this->db->where($this->table_name . '.id', $id);
        $query = $this->db->get();

        if ($query->num_rows() == 1) {
            return $query->row();
        } else {
            //Get empty base parent object, as $customer_id is NOT an customer
            $obj = new stdClass();
            //Get all the fields from customer table
            $fields = $this->db->list_fields($this->table_name);

            //append those fields to base parent object, we we have a complete empty object
            foreach ($fields as $field) {
                $obj->$field = '';
            }

            return $obj;
        }
    }

    /**
     * Check if account deactivated
     *
     * @param	string
     * @return	bool
     */
    function is_account_deactivated($email) {
        $this->db->select('1', FALSE);
        $this->db->where('email', strtolower($email));
        $this->db->where('deactivated', 1);

        $query = $this->db->get($this->table_name);
        return $query->num_rows() == 0;
    }

    /**
     * Check if username available for registering
     *
     * @param	string
     * @return	bool
     */
    function is_username_available($username) {
        $this->db->select('1', FALSE);
        $this->db->where('LOWER(username)=', strtolower($username));

        $query = $this->db->get($this->table_name);
        return $query->num_rows() == 0;
    }

    /**
     * Check if email available for registering
     *
     * @param	string
     * @return	bool
     */
    function is_email_available($email) {
        $this->db->select('1', FALSE);
        $this->db->where('LOWER(email)=', strtolower($email));
        $this->db->or_where('LOWER(new_email)=', strtolower($email));

        $query = $this->db->get($this->table_name);
        return $query->num_rows() == 0;
    }

    /**
     * Create new user record
     *
     * @param	array
     * @param	bool
     * @return	array
     */
    function create_user($data, $activated = TRUE, $fb_data = array()) {
        $data['created'] = date('Y-m-d H:i:s');
        $data['activated'] = $activated ? 1 : 0;

        if ($this->db->insert($this->table_name, $data)) {
            $user_id = $this->db->insert_id();
            if ($activated)
                $this->create_profile($user_id, $fb_data);
            return array('user_id' => $user_id);
        }
        return NULL;
    }

    /**
     * Create new user record
     *
     * @param	array
     * @param	bool
     * @return	array
     */
    function update_user($data, $id) {
        $this->db->where('user_id', $id);
        return $this->db->update($this->profile_table_name, $data);
    }

    /**
     * Activate user if activation key is valid.
     * Can be called for not activated users only.
     *
     * @param	int
     * @param	string
     * @param	bool
     * @return	bool
     */
    function activate_user($user_id, $activation_key, $activate_by_email) {
        $this->db->select('1', FALSE);
        $this->db->where('id', $user_id);
        if ($activate_by_email) {
            $this->db->where('new_email_key', $activation_key);
        } else {
            $this->db->where('new_password_key', $activation_key);
        }
        $this->db->where('activated', 0);
        $query = $this->db->get($this->table_name);

        if ($query->num_rows() == 1) {

            $this->db->set('activated', 1);
            $this->db->set('new_email_key', NULL);
            $this->db->where('id', $user_id);
            $this->db->update($this->table_name);

            $this->create_profile($user_id);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Purge table of non-activated users
     *
     * @param	int
     * @return	void
     */
    function purge_na($expire_period = 172800) {
        $this->db->where('activated', 0);
        $this->db->where('UNIX_TIMESTAMP(created) <', time() - $expire_period);
        $this->db->delete($this->table_name);
    }

    /**
     * Delete user record
     *
     * @param	int
     * @return	bool
     */
    function delete_user($user_id) {
        $this->db->where('id', $user_id);
        $this->db->delete($this->table_name);
        if ($this->db->affected_rows() > 0) {
            $this->delete_profile($user_id);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Set reactivate.
     * This key can be used for authentication when resetting user's password.
     *
     * @param	int
     * @param	string
     * @return	bool
     */
    function set_deactivated($user_id) {
        $this->db->set('deactivated', 0);
        $this->db->where('id', $user_id);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Set new password key for user.
     * This key can be used for authentication when resetting user's password.
     *
     * @param	int
     * @param	string
     * @return	bool
     */
    function set_password_key($user_id, $new_pass_key) {
        $this->db->set('new_password_key', $new_pass_key);
        $this->db->set('new_password_requested', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Check if given password key is valid and user is authenticated.
     *
     * @param	int
     * @param	string
     * @param	int
     * @return	void
     */
    function can_reset_password($user_id, $new_pass_key, $expire_period = 900) {
        $this->db->select('1', FALSE);
        $this->db->where('id', $user_id);
        $this->db->where('new_password_key', $new_pass_key);
        $this->db->where("UNIX_TIMESTAMP(CONVERT_TZ(new_password_requested, '+10:00', @@global.time_zone)) + (2*60*60) > ", time() - $expire_period);
//  Australia UTC+10:00
//  Added 2hours (2*60*60) 
        $query = $this->db->get($this->table_name);

        return $query->num_rows() == 1;
    }

    /**
     * Change user password if password key is valid and user is authenticated.
     *
     * @param	int
     * @param	string
     * @param	string
     * @param	int
     * @return	bool
     */
    function reset_password($user_id, $new_pass, $new_pass_key, $expire_period = 900) {
        $this->db->set('password', $new_pass);
        $this->db->set('new_password_key', NULL);
        $this->db->set('new_password_requested', NULL);
        $this->db->where('id', $user_id);
        $this->db->where('new_password_key', $new_pass_key);
//		$this->db->where('UNIX_TIMESTAMP(new_password_requested) >=', time() - $expire_period);
        $this->db->where("UNIX_TIMESTAMP(CONVERT_TZ(new_password_requested, '+10:00', @@global.time_zone)) + (2*60*60) >= ", time() - $expire_period);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Change user password
     *
     * @param	int
     * @param	string
     * @return	bool
     */
    function change_password($user_id, $new_pass) {
        $this->db->set('password', $new_pass);
        $this->db->where('id', $user_id);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Set new email for user (may be activated or not).
     * The new email cannot be used for login or notification before it is activated.
     *
     * @param	int
     * @param	string
     * @param	string
     * @param	bool
     * @return	bool
     */
    function set_new_email($user_id, $new_email, $new_email_key, $activated) {
        $this->db->set($activated ? 'new_email' : 'email', $new_email);
        $this->db->set('new_email_key', $new_email_key);
        $this->db->where('id', $user_id);
        $this->db->where('activated', $activated ? 1 : 0);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Activate new email (replace old email with new one) if activation key is valid.
     *
     * @param	int
     * @param	string
     * @return	bool
     */
    function activate_new_email($user_id, $new_email_key) {
        $this->db->set('email', 'new_email', FALSE);
        $this->db->set('new_email', NULL);
        $this->db->set('new_email_key', NULL);
        $this->db->where('id', $user_id);
        $this->db->where('new_email_key', $new_email_key);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Update user login info, such as IP-address or login time, and
     * clear previously generated (but not activated) passwords.
     *
     * @param	int
     * @param	bool
     * @param	bool
     * @return	void
     */
    function update_login_info($user_id, $record_ip, $record_time) {
        $this->db->set('new_password_key', NULL);
        $this->db->set('new_password_requested', NULL);

        if ($record_ip)
            $this->db->set('last_ip', $this->input->ip_address());
        if ($record_time)
            $this->db->set('last_login', date('Y-m-d H:i:s'));

        $this->db->where('id', $user_id);
        $this->db->update($this->table_name);
    }

    /**
     * Ban user
     *
     * @param	int
     * @param	string
     * @return	void
     */
    function ban_user($user_id, $reason = NULL) {
        $this->db->where('id', $user_id);
        $this->db->update($this->table_name, array(
            'banned' => 1,
            'ban_reason' => $reason,
        ));
    }

    /**
     * Unban user
     *
     * @param	int
     * @return	void
     */
    function unban_user($user_id) {
        $this->db->where('id', $user_id);
        $this->db->update($this->table_name, array(
            'banned' => 0,
            'ban_reason' => NULL,
        ));
    }

    /**
     * Create an empty profile for a new user
     *
     * @param	int
     * @return	bool
     */
    private function create_profile($user_id, $fb_data = array()) {
        $this->db->set('user_id', $user_id);
        if (!empty($fb_data)) {
            $this->db->set('first_name', $fb_data['first_name']);
            $this->db->set('last_name', $fb_data['last_name']);
        }
        return $this->db->insert($this->profile_table_name);
    }

    /**
     * Delete user profile
     *
     * @param	int
     * @return	void
     */
    private function delete_profile($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->delete($this->profile_table_name);
    }

    function getChartData_users() {
        $select = "CONCAT(DATE_FORMAT(created,'%b'), \" '\", DATE_FORMAT(created, '%y')) as date_created, "
                . "sum(case when fb_login=1 then 1 else 0 end) as fb_login_count, "
                . "sum(case when fb_login=0 then 1 else 0 end) as wb_login_count ";

        $this->db->select($select);
        $this->db->group_by("DATE_FORMAT(created,'%M %Y')");
        $this->db->order_by("id");
        return $this->db->get($this->table_name)->result();
    }

    function update_user_role($data, $id) {
        $this->db->where('id', $id);
        return $this->db->update($this->table_name, $data);
    }

    function get_all_users() {
        $this->db->from($this->table_name);
        $this->db->join($this->profile_table_name, $this->profile_table_name . '.user_id = ' . $this->table_name . '.id');
        $this->db->join('roles', 'roles.role_id = ' . $this->table_name . '.role_id');
        $this->db->order_by('last_login', 'desc');
        return $this->db->get();
    }

}

/* End of file users.php */
/* Location: ./application/models/auth/users.php */