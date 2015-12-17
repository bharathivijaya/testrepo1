<?php
/**
 * Created by PhpStorm.
 * User: Tan4ik
 * Date: 23.03.15
 * Time: 14:21
 */
class drugM extends CI_Model {

    var $table   = 'drug';

    public function getOneBy($field, $value, $cid){
        //$w = array($field => $value);
        //$this->db->where($w);
		$tbl = $this->table;
        $q = $this->db->query("SELECT * FROM $tbl WHERE $field = ? AND d_companyId = ? order by d_modified desc", array($value, $cid));
        //$q = $this->db->query("SELECT *, (SELECT category.c_name FROM category WHERE category.c_id=${tbl}.d_catId) as c_name FROM $tbl WHERE $field = ? AND d_companyId = ?", array($value, $cid));

		if ($q->num_rows()) {
            $res = $q->result_array();
            return $res[0];
        } else
			return array();
    }

	public function getAllShortInfo($cid)
	{
		$q = $this->db->query("SELECT d_id, d_code FROM drug WHERE d_companyId=?", array($cid));
		$res = array();

		foreach ($q->result_array() as $drug)
		{
			$res[$drug["d_code"]] = $drug;
		}

		return $res;
	}

    public function getAll($cid, $status=''){
        if ($status == ''){
            $this->db->where('d_companyId', $cid);
        }
        else {
            $this->db->where(array('d_companyId' => $cid, 'd_status' => $status));
        }

        $q = $this->db->get($this->table);
        return $q->result_array();
    }


    public function addDrug($post)
    {
        if ($this->session->userdata('type') == 'user')
		{
            $user = $this->userM->getUserBy('id', $this->session->userdata('id'));
            $cid = $user['parent_id'];
        } else
			$cid = $this->session->userdata('id');

		$catlist = array();

		if (isset($post["d_catId"])) {
			$catlist = explode(",", $post["d_catId"]);
			unset($post["d_catId"]);
		}

        foreach ($post as $k =>$v)
			if ($k !== 'add_new')
				$data[$k] = strtoupper($v);

		// $data['d_companyId'] = $cid;

        $data['d_companyId'] = $cid;
        $data['d_created'] = time();
        $data['d_modified'] = time();
        $data['d_onHand'] = $post['d_start'];
        $data['d_userCreatedId'] = $this->session->userdata('id');

        $this->db->insert($this->table, $data);
		$drugId = $this->db->insert_id();

		// update cats
		$this->db->query("DELETE FROM drug_categories WHERE drugId=?", array($drugId));
		for ($i = 0; $i < count($catlist); $i++)
			if ($catlist[$i]) $this->db->query("INSERT INTO drug_categories SET drugId=?, catId=?", array($drugId, intval($catlist[$i])));

		return $drugId;
    }


    public function editDrug($id, $post)
	{
		$catlist = array();

		if (isset($post["d_catId"])) {
			$catlist = explode(",", $post["d_catId"]);
			unset($post["d_catId"]);
		}

        foreach ($post as $k =>$v)
			if (($k !== 'd_id')&& ($k !== 'add_new'))
				$data[$k] = strtoupper($v);

		// update cats
		if (@isset($post["d_id"])) $this->db->query("DELETE FROM drug_categories WHERE drugId=?", array($post["d_id"]));
		for ($i = 0; $i < count($catlist); $i++)
			if ($catlist[$i]) $this->db->query("INSERT INTO drug_categories SET drugId=?, catId=?", array($post["d_id"], intval($catlist[$i])));

		$data['d_modified'] = time();
        $this->db->where('d_id', $id);
        $this->db->update($this->table, $data);
    }

    public function search($post, $cid){
        $q = $this->db->query('
        SELECT * from `drug` WHERE '.$post['criterion'].' = ? AND d_companyId = ?
        ', array($post['value'], $cid));
        $res = $q->result_array();
        return $res;
    }

	public function selectAllInventoryIn($drugId)
	{
	//	$query="SELECT entry.*, (SELECT v_name FROM vendor WHERE vendor.v_id=entry.e_vendorId) as v_name FROM entry  AS a INNER
	//	JOIN (select GROUP_CONCAT(e_lot ORDER BY e_id SEPARATOR ',') as total_lots,GROUP_CONCAT(e_id ORDER BY e_id SEPARATOR '-') as total_entry_ids WHERE //entry.e_drugId=".$drugId." and (entry.e_type='new' or entry.e_type='return')";

	$query="select a.*,SUM(e_out)as e_out_total,SUM(e_old)as total_old_qty,SUM(e_new)as total_new_qty,vendor.v_name,
		user.username, drug.d_name,drug.d_code,drug.d_size,a.e_rx,total_lots,total_entry_ids,
		total_drug_ids from entry AS a INNER
		JOIN (select GROUP_CONCAT(e_lot ORDER BY e_id SEPARATOR ',') as total_lots,GROUP_CONCAT(e_id ORDER BY e_id SEPARATOR '-') as total_entry_ids,
		GROUP_CONCAT(e_drugId ORDER BY e_id SEPARATOR ',') as total_drug_ids,e_rx,e_date,e_transaction_group_id,e_drugId from entry 
		group by e_transaction_group_id) as b on a.e_transaction_group_id = b.e_transaction_group_id 
		LEFT JOIN drug ON drug.d_id = a.e_drugId LEFT JOIN user ON user.id = a.e_userId LEFT JOIN vendor ON 
		vendor.v_id = a.e_vendorId where (a.e_type = 'return' OR a.e_type = 'new' OR a.e_type='edit') and (a.e_out=0) and(a.e_drugId=".$drugId.")";

			if(isset($_POST['searchfield']) && $_POST['searchfield']!="")
		{
			$query=$query." and (e_invoice='".$_POST['searchfield']."' or e_rx='".$_POST['searchfield']."' or e_lot='".$_POST['searchfield']."')";
		}
		$query=$query." group by  a.e_transaction_group_id order by a.e_id desc,concat(a.e_invoice,a.e_rx)";
		//echo $query;
	$query = $this->db->query($query);  
		$search_result = $query->result();
		//echo "rows:".$query->num_rows();
		$new_search_array = array();
		foreach($search_result as $key => $value)
		{
		$q = $this->db->query("SELECT d_name,d_code FROM drug WHERE d_id IN (".$value->total_drug_ids.") 
		ORDER BY FIELD(d_id,". $value->total_drug_ids.")");
		$codes = '';
		$drug_names = '';
		$drug_code_result = $q->result();
		foreach($drug_code_result as $d_code_key => $d_code_value)
		{
		  if($d_code_key != count($drug_code_result)) 
		  {
		  $codes.= $d_code_value->d_code.',';
		  $drug_names.= $d_code_value->d_name.',';
		  }
		  else
		  {
		  $codes.= $d_code_value->d_code;
		  $drug_names.= $d_code_value->d_name;
		  }
		}
		
		$fieldName = 'total_drug_codes';
		$fieldDrugName = 'd_name';
		$total_lots = 'total_lots';
		
		$q = $this->db->query("SELECT e_type,e_lot,e_out,e_drugId,e_expiration,e_old,e_new FROM entry WHERE e_id IN (".str_replace('-',',',$value->total_entry_ids).") ORDER BY FIELD(e_id,". str_replace('-',',',$value->total_entry_ids).")");
		
		$lots = '';
		$lots_exp = '';
		$entry_result = $q->result();
		$previous_entry_drug_id = 0;
		foreach($entry_result as $entry_key => $entry_value)
		{		  
		  $out_qty_add_or_out = $entry_value->e_out != 0 ? $entry_value->e_out : $entry_value->e_new - $entry_value->e_old;
		  		  
		  if($entry_key == 0)
		  { 
		  $lots.= $entry_value->e_lot.'('.$out_qty_add_or_out.')&nbsp;';
		  $lots_exp.= $entry_value->e_expiration == 0 ? '' : date('m-d-Y', $entry_value->e_expiration).'&nbsp;';
		  }
		  else if($previous_entry_drug_id != $entry_value->e_drugId)
		  {
		  $lots.= ','.$entry_value->e_lot.'('.$out_qty_add_or_out.')';
		  $lots_exp.= $entry_value->e_expiration == 0 ? ',' : ','.date('m-d-Y', $entry_value->e_expiration).'&nbsp;';
		  }
		  else
		  {
		  $lots.= $entry_value->e_lot.'('.$out_qty_add_or_out.')&nbsp;';
		  $lots_exp.= $entry_value->e_expiration == 0 ? '' : date('m-d-Y', $entry_value->e_expiration).'&nbsp;';
		  }
		  
		  $previous_entry_drug_id = $entry_value->e_drugId;
		}
		
		$e_expiration = 'e_expiration';				
		$value->total_lots = $lots;
		$value->e_expiration = $lots_exp;
		$value->$fieldName = $codes;
		$value->$fieldDrugName = $drug_names;
		$new_search_array[] = $value;
		}
		return $new_search_array;		
		
		}


	public function selectAllInventoryOut($drugId)
	{
		$query="SELECT entry.*, (SELECT expirationDate FROM lot WHERE lotName=entry.e_lot and drugId=entry.e_drugId LIMIT 1) as expirationDate FROM entry WHERE entry.e_drugId=".$drugId." and entry.e_type='out'";
		if(isset($_POST['searchfield']) && $_POST['searchfield']!="")
		{
			$query=$query." and (e_rx='".$_POST['searchfield']."' or e_lot='".$_POST['searchfield']."')";
		}
		if(isset($_POST['dateview']) && $_POST['dateview']!="")
		{
			 $query=$query." ORDER by entry.e_date ".$_POST['dateview'];
		}
		else
$query=$query." ORDER by entry.e_date DESC";
//echo $query;
		$q = $this->db->query($query);
		$res = $q->result_array();
		return $res;
	}

	public function selectAllInventoryAudit($drugId)
	{
$query="SELECT entry.*,user.username FROM entry,user WHERE entry.e_drugId=".$drugId." and entry.e_type='audit' and entry.e_userId=user.id";
	if(isset($_POST['searchfield']) && $_POST['searchfield']!="")
		{
			$query=$query." and (e_rx='".$_POST['searchfield']."' or e_lot='".$_POST['searchfield']."')";
		}
		$query=$query."  ORDER by entry.e_date DESC";
		$q = $this->db->query($query);
		$res = $q->result_array();
		return $res;
	}

	public function setLotTracking($drugId, $status)
	{
		$st = 0;
		if ($status == "true") $st = 1;

		$this->db->query("UPDATE drug SET d_lotTracking=? WHERE d_id=?", array($st, $drugId));
	}

	public function searchAdvanced($cid, $q) {
		$q = $this->db->query('SELECT * FROM drug WHERE d_companyId = ? and (d_name like ? or d_barcode like ? or d_code like ?)',
			array(
				$cid,
				"%".$q."%",
				"%".$q."%",
				"%".$q."%"
			)
		);
		$res = $q->result_array();
		return $res;
	}

	public function checkDrugLotExists($drugId, $lot)
	{
		$q = $this->db->query('SELECT * FROM lot WHERE drugId=? and lotName=?', array($drugId, $lot));
		return $q->num_rows();
	}

	public function checkLotExists($lot)
	{
		$q = $this->db->query('SELECT * FROM lot WHERE lotName=?', array($lot));
		return $q->num_rows();
	}

	public function checkLotExistsOrActive($lot)
	{
		$q = $this->db->query('SELECT * FROM lot WHERE lotName=? and active=1', array($lot));
		return $q->num_rows();
	}

	public function addDrugLocalCategories($catIds, $drugId, $userId)
	{
		$this->db->query("DELETE FROM drug_categories WHERE drugId=?", array($drugId));

		$q = $this->db->query("SELECT * FROM category WHERE c_companyId=? and c_localId in (".implode(",", $catIds).")", array($userId));
		$realCats = array();
		foreach ($q->result_array() as $cat) $realCats[$cat["c_localId"]] = $cat["c_id"];

		for ($i = 0; $i < count($catIds); $i++)
			if (@isset($realCats[$catIds[$i]]))
				$this->db->query("INSERT INTO drug_categories SET drugId=?, catId=?", array($drugId, (int)$realCats[$catIds[$i]]));
	}

	public function getListByIds($cid, $ids)
	{
		$idlist = explode(",", $ids);
		$safelist = "";

		for ($i = 0; $i < count($idlist); $i++) $safelist .= intval($idlist[$i]).",";
		$safelist = substr($safelist, 0, -1);

		$q = $this->db->query('SELECT d_id, d_name, d_code FROM drug WHERE d_companyId = ? and d_id in ('.$safelist.')',
			array($cid)
		);

		$res = $q->result_array();
		return $res;
	}
public function getdrugsByIds($drugIds)
	{
		$query= $this->db->query("SELECT * FROM drug WHERE d_id IN (".$drugIds.") ORDER BY FIELD(d_id,". $drugIds.")");
		$res = $query->result_array();
		return $res;	
	}
    public function upload($file, $cid)
	{
        $codes = array();
        $answer = '';

        if ($file["file"]["size"] > 1024*3*1024)
		{
            $answer = 'file size is too big';
        } else {
            if (is_uploaded_file($file["file"]["tmp_name"]))
			{
                $ext = pathinfo($file["file"]["name"], PATHINFO_EXTENSION);

				if ($ext !== 'csv')
				{
                    $answer = 'file extension is not csv';
                } else {
                    $string = file_get_contents($file["file"]["tmp_name"]);
                    $drugs = explode("\r\n", $string);
                    array_shift($drugs);//remove the header
                    //print_r($drugs);

                    for ($i = 0; $i <= 2; $i++)
					{
                        //echo substr($drugs[0], 0, 8);
                        if (substr($drugs[0], 0, 8) == 'Example:')
						{
                            array_shift($drugs);
                        }
                    }

                    foreach ($drugs as $one)
					{
                        $drug = explode(",", $one);
                        //print_r($drug);

                        if (isset($drug[1]))
						{
                            $sts = ($drug[9] == 'Active' ?1 :0);
                            //$cat = $this->categoryM->getOneBy('c_name', strtoupper($drug[7]), $cid);
							$catIds = explode(".", $drug[7]);
							for ($i = 0; $i < count($catIds); $i++) $catIds[$i] = (int)$catIds[$i];

							/*if (empty($cat))
							{
                                $catId = '';
                            } else {
                                $catId = $cat['c_id'];
                            }*/

                            //print_r($drug);
                            $post = array(
                                'd_name' => $drug[0],
                                'd_code' => $drug[1],
                                'd_descr' => $drug[2],
                                'd_size' => (int)$drug[3],
                                'd_manufacturer' => $drug[4],
                                'd_start' => (int)$drug[5],
                                'd_schedule' => $drug[6],
                                //'d_catId' => $catId,
                                'd_barcode' => $drug[8],
                                'd_status' => $sts
                            );

                            if ($this->checkNdc($post['d_code'], $cid))
							{
                                $drugId = $this->addDrug($post);
								$this->addDrugLocalCategories($catIds, $drugId, $cid);
                            } else {
                                $codes[] = $post['d_code'];
                            }

                            $answer = 'ok';
                        }
                    }
                }
            } else {
                $answer = 'internal server error';
            }
        }

        $arr = array('answer' => $answer, 'codes' => $codes);
        return $arr;
    }


   
	public function removeMasterFileByID($id)
	{
		$this->db->query("DELETE FROM drug_list WHERE id=?", array($id));
	}

	public function updateMasterFile($id, $post)
	{
		$date = date ( "Y-m-d H:i:s", time () );
		$this->db->query("
			UPDATE drug_list SET
			drugName=?, ndc=?, description=?, packageSize=?,
			manufacture=?, schedule=?, barcode=?, status=?, last_modified=?
			WHERE id=?", array(
				@$post["drugName"],
				@$post["ndc"],
				@$post["description"],
				@$post["packageSize"],
				@$post["manufacture"],
				@$post["schedule"],
				@$post["barcode"],
				@$post["status"],
				$date,
				$id
		));
	}

	public function addMasterFile($post)
	{
		$date = date ( "Y-m-d H:i:s", time () );
		$this->db->query("
			INSERT INTO drug_list SET
			drugName=?, ndc=?, description=?, packageSize=?,
			manufacture=?, schedule=?, barcode=?, status=?, date_created=?, last_modified=?", array(
			@$post["drugName"],
			@$post["ndc"],
			@$post["description"],
			@$post["packageSize"],
			@$post["manufacture"],
			@$post["schedule"],
			@$post["barcode"],
			@$post["status"],
			$date,
			$date
					
		));
	}

	public function getMasterFilesDrugByIdList($idlist)
	{
		$q = $this->db->query("SELECT * FROM drug_list WHERE id IN (".implode(",", $idlist).")");
		return $q->result_array();
	}

	public function getMasterFileDrugByID($id)
	{
		$q = $this->db->query("SELECT * FROM drug_list WHERE id=?", array($id));
		$res = $q->result_array();
		return @$res[0];
	}

	public function getMasterFileDrugs($aswho, $offset, $count, $search = '')
	{
		
		// ndc search
		$ndc_s = $search;

		if(preg_match( '/^(\d{5})(\d{4})(\d{2})$/', $search, $matches))
			$ndc_s = $matches[1] . '-' .$matches[2] . '-' . $matches[3];

		$status_sql = '';
		$status_sql_with_and = '';
		$status_sql_with_and_end = '';
		if ($aswho == "user") {
			$status_sql_with_and = " status = '1' AND  (";
			$status_sql_with_and_end = " )";
			$status_sql = " WHERE status = '1' ";
		}
		
		// get results
		$q = $this->db->query("SELECT * FROM drug_list WHERE ".$status_sql_with_and."  drugName LIKE ? or ndc LIKE ?".$status_sql_with_and_end." LIMIT ? , ?", array('%'.$search.'%', '%'.$ndc_s.'%', $offset, $count));
		$data = array("data" => array());

		// get total
		$q2 = $this->db->query("SELECT COUNT(*) FROM drug_list ".$status_sql, array());
		$rj2 = $q2->result_array();

		// get filtered
		$q3 = $this->db->query("SELECT COUNT(*) FROM drug_list WHERE ".$status_sql_with_and." drugName LIKE ? or ndc LIKE ? ". $status_sql_with_and_end."", array('%'.$search.'%', '%'.$ndc_s.'%'));
		$rj3 = $q3->result_array();

		$data["recordsTotal"] = $rj2[0]["COUNT(*)"];
		$data["recordsFiltered"] = $rj3[0]["COUNT(*)"];

		foreach ($q->result_array() as $arr)
		{
			$tmp_arr = array(
				$arr["drugName"],
				$arr["ndc"],
				$arr["description"],
				$arr["packageSize"],
				$arr["manufacture"],
				$arr["schedule"],
				$arr["barcode"]				
			);

			if ($aswho == "admin") {				
				array_push($tmp_arr, $arr["date_created"]);
				array_push($tmp_arr, $arr["last_modified"]);
				array_push($tmp_arr, ($arr["status"] == 1? 'active' : 'inactive'));
			}
			if ($aswho == "admin") array_push($tmp_arr, "<a href='/drug/add_master_drug/".$arr["id"]."'>Edit</a> / <a href='javascript://void();' onclick='openModal(".$arr["id"].")'>Delete</a>");
			if ($aswho == "user") array_push($tmp_arr, "<input type='checkbox' data-info='drug' data-id='".$arr["id"]."' onchange='setCheckBox(".$arr["id"].");' id='drug_".$arr["id"]."'>");

			array_push($data["data"], $tmp_arr);
		}

		return $data;
	}

    public function checkNdc($code, $cid) {
        $drug = $this->drugM->getOneBy('d_code', $code, $cid);

        $isAvailable = true;

        if(!@empty($drug)){

            $isAvailable = false;

        }
        return $isAvailable;
    }
	
	public function insert_drug($data_array) {
		$this->db->insert ( 'drug_list', $data_array );
	}
	
	public function update_drug($data_array, $ndc)
	{
		$this->db->where ( 'ndc', $ndc );
		$this->db->update ( 'drug_list', $data_array );
	}
	
	public function count_drug_by_ndc($ndc) {
		$this->db->from ( 'drug_list' );		
		$this->db->where ( 'ndc', $ndc );
		return $this->db->count_all_results ();
	}
	
	public function get_drug_list_for_export() {
		$this->db->select ( '*' );
		$this->db->from ( 'drug_list' );
		$query = $this->db->get ();
		return $query->result ();
	}
	
	public function get_drug_alert_count($drug_id, $customer_id) {
		$this->db->from ( 'alerts' );		
		$this->db->where ( " FIND_IN_SET('" . $drug_id . "', `drugList`) ", '', FALSE );
		$this->db->where ( 'compId', $customer_id );
		return $this->db->count_all_results ();
	}
	
	public function get_drug_alert_data($drug_id, $customer_id) {
		$this->db->from ( 'alerts' );		
		$this->db->where ( " FIND_IN_SET('" . $drug_id . "', `drugList`) ", '', FALSE );
		$this->db->where ( 'compId', $customer_id );		
		$query = $this->db->get ();
		return $query->result ();
	}
	
	public function get_drug_data($drug_id, $customer_id) {
		$this->db->from ( 'drug' );
		$this->db->where ( 'd_id', $drug_id );
		$this->db->where ( 'd_companyId', $customer_id );		
		$query = $this->db->get ();
		return $query->result ();
	}
	
	public function get_drug_byndc($ndc,$cid) {
		$this->db->from ( 'drug' );
		$this->db->where ( 'd_code', $ndc );
		$this->db->where ( 'd_companyId', $cid );		
		$query = $this->db->get ();
		return $query->result ();
	}
	
	public function add_to_drug_list_temp_table($file_path) {		
		$this->db->truncate('drug_list_temp');
		
		$csv_sql = "LOAD DATA LOCAL INFILE '".$file_path."' INTO TABLE `drug_list_temp` FIELDS ESCAPED BY '\\\' TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\r\n'  IGNORE 1 LINES (
					  `drugName`,
					  `ndc`,
					  `description`,
					  `packageSize`,
					  `manufacture`,
					  `schedule`,
					  `barcode`,
					  `date_created`,
					  `last_modified`,
					  `status`
					 
					) ;
							";
		
		$this->db->query($csv_sql, FALSE);
	}
	
	public function get_all_drug_list_temp_data($limit = NULL, $offset = NULL) {
		$this->db->select ( '*' );
		$this->db->from ( 'drug_list_temp' );
		$this->db->limit ( $limit, $offset );
		$query = $this->db->get ();
		return $query->result ();
	}
	
	public function count_temp_drug_list() {
		$this->db->from ( 'drug_list_temp' );		
		return $this->db->count_all_results ();
	}
	
	public function reverseDrugData($drugId, $count)
		{
			$q = $this->db->query("SELECT d_onHand FROM drug WHERE d_id=?", array($drugId));
			if ($q->num_rows() > 0)
				$this->db->query("UPDATE drug SET d_onHand=? WHERE d_id=?", array($q->row()->d_onHand + $count,$drugId));			
		}
	
}