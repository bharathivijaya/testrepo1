<?php
/**
 * Created by PhpStorm.
 * User: Tan4ik
 * Date: 23.03.15
 * Time: 14:21
 */
class adminM extends CI_Model {

    var $table   = 'admin';

   public function savePage($post){
       $data['pg_title'] = $post['pg_title'];
       $data['pg_content'] = $post['pg_content'];
       $w = array('pg_id' => $post['pg_id']);
       $this->db->where($w);
       $this->db->update('page', $data);
   }

    public function getContent(){
        $q = $this->db->get($this->table);
        return $q->result_array();
    }

    public function getPage($id){
        $this->db->where(array('pg_id' => $id));
        $q = $this->db->get('page');
        $res = $q->result_array();
		if(!is_array($res) && sizeof($res)>0)
		{
		if(isset($res[0]))
        	return $res[0];
			else
			return false;
		}
		else
		return false;
    } 

    public function getPages(){
        $q = $this->db->get('page');
        return $q->result_array();
    }
    public function usersReport($post) {
        if ($post['type'] == 'customers'){
            $period = ($post['customers'] == 'Monthly'?30*86400:30*86400*12);
            $q = $this->db->query('
            SELECT * FROM `payment`
            LEFT JOIN `user` ON user.id = payment.p_userId
            WHERE payment.p_type = ? AND user.payment_expiry > ? AND (payment.p_date + ?) >= user.payment_expiry
            GROUP BY user.id', array($post['customers'], time(), $period));
        }
        else if ($post['type'] == 'users') {
            if ($post['users'] == 'active') {
                $q = $this->db->query('SELECT * FROM `user` WHERE `type` = "company" AND payment_expiry > ?', array(0));

            }
            else {
                $q = $this->db->query('SELECT * FROM `user` WHERE `type` = "company" AND status != "new" AND payment_expiry < ?', array(1));
           }
        }
        return $q->result_array();

    }

    public function salesReport($post = array()) {
        $q = $this->db->query('
        SELECT * FROM `payment`
        LEFT JOIN `user` ON user.id = payment.p_userId
        ');
        return $q->result_array();
    }

    public function createAdmin($post) {
        $data = array(
            'first_name' => $post['first_name'],
            'last_name' => $post['last_name'],
            'username' => $post['username'],
            'password' => md5($post['password']),
            'email' => $post['email'],
            'type' => 'admin'
        );
        $this->db->insert('user', $data);
    }

    public function editAdmin($post, $aid) {
        $this->db->where('id', $aid);
        $this->db->update('user', $post);
    }

    public function getAdmins(){
        $this->db->where('type', 'admin');
        $q = $this->db->get('user');
        return $q->result_array();
    }

    public function getBanners($status=''){
        if ($status !== '') {
            $this->db->where('b_status', $status);
        }
        $q = $this->db->get('banner');
        return $q->result_array();
    }

    public function getBannerBy($field, $value){
        $w = array($field => $value);
        $this->db->where($w);
        $q = $this->db->get('banner');
        if ($q->num_rows()) {
            $res = $q->result_array();
            return $res[0];
        }
        else {
            return array();
        }
    }

    function createBanner($post, $file, $bid) {
        foreach ($post as $k => $v) {
            if ($k !== 'file') {
                $data[$k] = $v;
            }
        }

        $file_status = 'no';
        if ((isset($file['file']['name'])) && ($file['file']['name'] !== '')) {
            $data['b_image'] = $this->uploadPic($file);
            $file_status = $data['b_image'];
        }

        if ($bid == '') {
            $this->db->insert('banner', $data);
            $bid = $this->db->insert_id();
        }
        else {
            $this->db->where('b_id', $bid);
            $this->db->update('banner', $data);
        }

        if ($post['b_status'] == 'Active') {
            $this->activateBanner($bid);
        }
        return (array($file_status, $bid));
    }


    public function uploadPic($file){
        if($file["file"]["size"] > 1024*3*1024) {
            $answer = 'size';
        }
        else {
            if(is_uploaded_file($file["file"]["tmp_name"])) {
                $ext = pathinfo($file["file"]["name"], PATHINFO_EXTENSION);
                $new_name = md5(time().microtime()).".".$ext;
                if(move_uploaded_file($file["file"]["tmp_name"], "images/banners/".$new_name)){
                    $answer = $new_name;
                } else {
                    $answer = 'error1';
                }
            } else {
                $answer = 'error2';
            }
        }
        return $answer;
    }

    public function activateBanner($bid){

        $ban = $this->getBannerBy('b_id', $bid);
        $loc = $ban['b_location'];
        $this->db->query('UPDATE `banner` SET b_status = "Inactive" WHERE b_location = ? AND b_id !=?', array($loc, $bid));
    }

    public function deleteBanner($id) {
        $this->db->where('b_id', $id);
        $this->db->delete('banner');
    }
}