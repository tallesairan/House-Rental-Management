<?php
session_start();
ini_set('display_errors', 1);
Class Action {
	private $db;

	public function __construct() {
		ob_start();
   	include 'db_connect.php';
    
    $this->db = $conn;
	}
	function __destruct() {
	    $this->db->close();
	    ob_end_flush();
	}

	function login(){
		try {
			extract($_POST);
			$stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
			$stmt->execute([':username' => $username, ':password' => md5($password)]);
			if($stmt->rowCount() > 0){
				foreach ($stmt->fetch() as $key => $value) {
					if($key != 'passwors' && !is_numeric($key))
						$_SESSION['login_'.$key] = $value;
				}
				if($_SESSION['login_type'] != 1){
					foreach ($_SESSION as $key => $value) {
						unset($_SESSION[$key]);
					}
					return 2 ;
					exit;
				}
					return 1;
			}else{
				return 3;
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function login2(){
		try {
			extract($_POST);
			if(isset($email))
				$username = $email;
			$stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
			$stmt->execute([':username' => $username, ':password' => md5($password)]);
			if($stmt->rowCount() > 0){
				foreach ($stmt->fetch() as $key => $value) {
					if($key != 'passwors' && !is_numeric($key))
						$_SESSION['login_'.$key] = $value;
				}
				if($_SESSION['login_alumnus_id'] > 0){
					$bio = $this->db->prepare("SELECT * FROM alumnus_bio WHERE id = :id");
					$bio->execute([':id' => $_SESSION['login_alumnus_id']]);
					if($bio->rowCount() > 0){
						foreach ($bio->fetch() as $key => $value) {
							if($key != 'passwors' && !is_numeric($key))
								$_SESSION['bio'][$key] = $value;
						}
					}
				}
				if($_SESSION['bio']['status'] != 1){
					foreach ($_SESSION as $key => $value) {
						unset($_SESSION[$key]);
					}
					return 2 ;
					exit;
				}
				return 1;
			}else{
				return 3;
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}
	function logout2(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:../index.php");
	}

	function save_user(){
		try {
			extract($_POST);
			$data = " name = :name, username = :username, type = :type, establishment_id = :establishment_id ";
			$params = [':name' => $name, ':username' => $username, ':type' => $type, ':establishment_id' => $establishment_id];
			if(!empty($password)) {
				$data .= ", password = :password ";
				$params[':password'] = md5($password);
			}
			if($type == 1)
				$establishment_id = 0;
			$chk = $this->db->prepare("SELECT * FROM users WHERE username = :username AND id != :id");
			$chk->execute([':username' => $username, ':id' => $id]);
			if($chk->rowCount() > 0){
				return 2;
				exit;
			}
			if(empty($id)){
				$save = $this->db->prepare("INSERT INTO users SET $data");
			}else{
				$save = $this->db->prepare("UPDATE users SET $data WHERE id = :id");
				$params[':id'] = $id;
			}
			$save->execute($params);
			if($save){
				return 1;
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function delete_user(){
		try {
			extract($_POST);
			$delete = $this->db->prepare("DELETE FROM users WHERE id = :id");
			$delete->execute([':id' => $id]);
			if($delete)
				return 1;
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function signup(){
		try {
			extract($_POST);
			$data = " name = :name, username = :username, password = :password ";
			$params = [':name' => $firstname.' '.$lastname, ':username' => $email, ':password' => md5($password)];
			$chk = $this->db->prepare("SELECT * FROM users WHERE username = :username");
			$chk->execute([':username' => $email]);
			if($chk->rowCount() > 0){
				return 2;
				exit;
			}
			$save = $this->db->prepare("INSERT INTO users SET $data");
			$save->execute($params);
			if($save){
				$uid = $this->db->lastInsertId();
				$data = '';
				foreach($_POST as $k => $v){
					if($k =='password')
						continue;
					if(empty($data) && !is_numeric($k) )
						$data = " $k = :$k ";
					else
						$data .= ", $k = :$k ";
					$params[":$k"] = $v;
				}
				if($_FILES['img']['tmp_name'] != ''){
					$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
					$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
					$data .= ", avatar = :avatar ";
					$params[':avatar'] = $fname;
				}
				$save_alumni = $this->db->prepare("INSERT INTO alumnus_bio SET $data");
				$save_alumni->execute($params);
				if($save_alumni){
					$aid = $this->db->lastInsertId();
					$this->db->prepare("UPDATE users SET alumnus_id = :alumnus_id WHERE id = :id")->execute([':alumnus_id' => $aid, ':id' => $uid]);
					$login = $this->login2();
					if($login)
						return 1;
				}
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function update_account(){
		try {
			extract($_POST);
			$data = " name = :name, username = :username ";
			$params = [':name' => $firstname.' '.$lastname, ':username' => $email];
			if(!empty($password)) {
				$data .= ", password = :password ";
				$params[':password'] = md5($password);
			}
			$chk = $this->db->prepare("SELECT * FROM users WHERE username = :username AND id != :id");
			$chk->execute([':username' => $email, ':id' => $_SESSION['login_id']]);
			if($chk->rowCount() > 0){
				return 2;
				exit;
			}
			$save = $this->db->prepare("UPDATE users SET $data WHERE id = :id");
			$params[':id'] = $_SESSION['login_id'];
			$save->execute($params);
			if($save){
				$data = '';
				foreach($_POST as $k => $v){
					if($k =='password')
						continue;
					if(empty($data) && !is_numeric($k) )
						$data = " $k = :$k ";
					else
						$data .= ", $k = :$k ";
					$params[":$k"] = $v;
				}
				if($_FILES['img']['tmp_name'] != ''){
					$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
					$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
					$data .= ", avatar = :avatar ";
					$params[':avatar'] = $fname;
				}
				$save_alumni = $this->db->prepare("UPDATE alumnus_bio SET $data WHERE id = :id");
				$params[':id'] = $_SESSION['bio']['id'];
				$save_alumni->execute($params);
				if($save_alumni){
					foreach ($_SESSION as $key => $value) {
						unset($_SESSION[$key]);
					}
					$login = $this->login2();
					if($login)
						return 1;
				}
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}

	function save_settings(){
		try {
			extract($_POST);
			$data = " name = :name, email = :email, contact = :contact, about_content = :about_content ";
			$params = [':name' => str_replace("'","&#x2019;",$name), ':email' => $email, ':contact' => $contact, ':about_content' => htmlentities(str_replace("'","&#x2019;",$about))];
			if($_FILES['img']['tmp_name'] != ''){
				$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
				$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
				$data .= ", cover_img = :cover_img ";
				$params[':cover_img'] = $fname;
			}
			$chk = $this->db->query("SELECT * FROM system_settings");
			if($chk->rowCount() > 0){
				$save = $this->db->prepare("UPDATE system_settings SET $data");
			}else{
				$save = $this->db->prepare("INSERT INTO system_settings SET $data");
			}
			$save->execute($params);
			if($save){
				$query = $this->db->query("SELECT * FROM system_settings LIMIT 1")->fetch();
				foreach ($query as $key => $value) {
					if(!is_numeric($key))
						$_SESSION['system'][$key] = $value;
				}
				return 1;
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}

	function save_category(){
		try {
			extract($_POST);
			$data = " name = :name ";
			$params = [':name' => $name];
			if(empty($id)){
				$save = $this->db->prepare("INSERT INTO categories SET $data");
			}else{
				$save = $this->db->prepare("UPDATE categories SET $data WHERE id = :id");
				$params[':id'] = $id;
			}
			$save->execute($params);
			if($save)
				return 1;
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function delete_category(){
		try {
			extract($_POST);
			$delete = $this->db->prepare("DELETE FROM categories WHERE id = :id");
			$delete->execute([':id' => $id]);
			if($delete){
				return 1;
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function save_house(){
		try {
			extract($_POST);
			$data = " house_no = :house_no, description = :description, category_id = :category_id, price = :price ";
			$params = [':house_no' => $house_no, ':description' => $description, ':category_id' => $category_id, ':price' => $price];
			$chk = $this->db->prepare("SELECT * FROM houses WHERE house_no = :house_no");
			$chk->execute([':house_no' => $house_no]);
			if($chk->rowCount() > 0 ){
				return 2;
				exit;
			}
			if(empty($id)){
				$save = $this->db->prepare("INSERT INTO houses SET $data");
			}else{
				$save = $this->db->prepare("UPDATE houses SET $data WHERE id = :id");
				$params[':id'] = $id;
			}
			$save->execute($params);
			if($save)
				return 1;
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function delete_house(){
		try {
			extract($_POST);
			$delete = $this->db->prepare("DELETE FROM houses WHERE id = :id");
			$delete->execute([':id' => $id]);
			if($delete){
				return 1;
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function save_tenant(){
		try {
			extract($_POST);
			$data = " firstname = :firstname, lastname = :lastname, middlename = :middlename, email = :email, contact = :contact, house_id = :house_id, date_in = :date_in ";
			$params = [':firstname' => $firstname, ':lastname' => $lastname, ':middlename' => $middlename, ':email' => $email, ':contact' => $contact, ':house_id' => $house_id, ':date_in' => $date_in];
			if(empty($id)){
				$save = $this->db->prepare("INSERT INTO tenants SET $data");
			}else{
				$save = $this->db->prepare("UPDATE tenants SET $data WHERE id = :id");
				$params[':id'] = $id;
			}
			$save->execute($params);
			if($save)
				return 1;
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function delete_tenant(){
		try {
			extract($_POST);
			$delete = $this->db->prepare("UPDATE tenants SET status = 0 WHERE id = :id");
			$delete->execute([':id' => $id]);
			if($delete){
				return 1;
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function get_tdetails(){
		try {
			extract($_POST);
			$data =array();
			$tenants = $this->db->prepare("SELECT t.*,concat(t.lastname,', ',t.firstname,' ',t.middlename) as name,h.house_no,h.price FROM tenants t INNER JOIN houses h ON h.id = t.house_id WHERE t.id = :id");
			$tenants->execute([':id' => $id]);
			foreach($tenants->fetch() as $k => $v){
				if(!is_numeric($k)){
					$$k = $v;
				}
			}
			$months = abs(strtotime(date('Y-m-d')." 23:59:59") - strtotime($date_in." 23:59:59"));
			$months = floor(($months) / (30*60*60*24));
			$data['months'] = $months;
			$payable= abs($price * $months);
			$data['payable'] = number_format($payable,2);
			$paid = $this->db->prepare("SELECT SUM(amount) as paid FROM payments WHERE id != :pid AND tenant_id = :tenant_id");
			$paid->execute([':pid' => $pid, ':tenant_id' => $id]);
			$last_payment = $this->db->prepare("SELECT * FROM payments WHERE id != :pid AND tenant_id = :tenant_id ORDER BY unix_timestamp(date_created) DESC LIMIT 1");
			$last_payment->execute([':pid' => $pid, ':tenant_id' => $id]);
			$paid = $paid->rowCount() > 0 ? $paid->fetch()['paid'] : 0;
			$data['paid'] = number_format($paid,2);
			$data['last_payment'] = $last_payment->rowCount() > 0 ? date("M d, Y",strtotime($last_payment->fetch()['date_created'])) : 'N/A';
			$data['outstanding'] = number_format($payable - $paid,2);
			$data['price'] = number_format($price,2);
			$data['name'] = ucwords($name);
			$data['rent_started'] = date('M d, Y',strtotime($date_in));

			return json_encode($data);
		} catch (PDOException $e) {
			// Handle error
		}
	}
	
	function save_payment(){
		try {
			extract($_POST);
			$data = "";
			$params = [];
			foreach($_POST as $k => $v){
				if(!in_array($k, array('id','ref_code')) && !is_numeric($k)){
					if(empty($data)){
						$data .= " $k=:$k ";
					}else{
						$data .= ", $k=:$k ";
					}
					$params[":$k"] = $v;
				}
			}
			if(empty($id)){
				$save = $this->db->prepare("INSERT INTO payments SET $data");
			}else{
				$save = $this->db->prepare("UPDATE payments SET $data WHERE id = :id");
				$params[':id'] = $id;
			}
			$save->execute($params);
			if($save){
				return 1;
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}
	function delete_payment(){
		try {
			extract($_POST);
			$delete = $this->db->prepare("DELETE FROM payments WHERE id = :id");
			$delete->execute([':id' => $id]);
			if($delete){
				return 1;
			}
		} catch (PDOException $e) {
			// Handle error
		}
	}
}