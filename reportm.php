<?php
/**
 * Created by PhpStorm.
 * User: Tan4ik
 * Date: 23.03.15
 * Time: 14:21
 */
class reportM extends CI_Model {

    public function entryReport($post, $cid){
        if ($post['date2'] == ''){
            $post['date2'] = $post['date1'];
        }
		
        $params = array($cid, strtotime($post['date1']), strtotime($post['date2']) + 24*60*60);

        /*if ($post['query'] !== ''){
            $cond = " AND ".$post['field']." = ? ";
            $params = array($cid, strtotime($post['date1']), strtotime($post['date2']), $post['query']);
        }
        else {*/
        if ((in_array('a_status', $post['parameters'])) && (!in_array('in_status', $post['parameters']))) {
            $cond = " AND d_status = ?";
            $params[] = 1;
        }
        else if ((!in_array('a_status', $post['parameters'])) && (in_array('in_status', $post['parameters']))) {
            $cond = " AND d_status = ?";
            $params[] = 0;
        }
        else {
            $cond = '';
        }

        //}
	
        if ($post['type'] == 'in') {
            $str = "SELECT * from `entry`
            LEFT JOIN `drug` ON entry.e_drugId = drug.d_id

            LEFT JOIN `user` ON entry.e_userId = user.id
            LEFT JOIN `vendor` ON entry.e_vendorId = vendor.v_id
            WHERE e_companyId = ? AND (e_type = 'new' OR e_type = 'return') AND e_date >=? AND e_date <=? group by e_transaction_group_id order by e_id desc".$cond;
        } else if (($post['type'] == 'out') || ($post['type'] == 'audit')) {
            $str = "SELECT * from `entry`
            LEFT JOIN `drug` ON entry.e_drugId = drug.d_id

            LEFT JOIN `user` ON entry.e_userId = user.id
            LEFT JOIN `vendor` ON entry.e_vendorId = vendor.v_id
            WHERE e_companyId = ? AND e_type = '".$post['type']."' AND e_date >=? AND e_date <=? group by e_transaction_group_id order by e_id desc".$cond;
        } 
		/*else if (($post['type'] == 'deleted') ) {
            $str = "SELECT * from `entry`
            LEFT JOIN `drug` ON entry.e_drugId = drug.d_id

            LEFT JOIN `user` ON entry.e_userId = user.id
            LEFT JOIN `vendor` ON entry.e_vendorId = vendor.v_id
            WHERE e_companyId = ? AND e_status = 3 AND e_deleteddate >=? AND e_deleteddate < ?".$cond;
        } */
		else if ($post['type'] == 'all') {
            $str = "SELECT * from `entry`
            LEFT JOIN `drug` ON entry.e_drugId = drug.d_id

            LEFT JOIN `user` ON entry.e_userId = user.id
            LEFT JOIN `vendor` ON entry.e_vendorId = vendor.v_id
            WHERE e_companyId = ? AND e_date >=? AND e_date <=?".$cond;
        } else if ($post['type'] == 'cat') {

			$catList = explode(",", $post["catList"]);
			for ($i = 0; $i < count($catList); $i++) $catList[$i] = (int)$catList[$i];

			$str = "SELECT * from entry
            LEFT JOIN `drug` ON entry.e_drugId = drug.d_id
            LEFT JOIN `user` ON entry.e_userId = user.id
            LEFT JOIN `vendor` ON entry.e_vendorId = vendor.v_id
            LEFT JOIN `drug_categories` ON drug_categories.drugId = drug.d_id
            LEFT JOIN `category` ON category.c_id = drug_categories.catId
            WHERE e_companyId = ? AND e_date >=? AND e_date <=?".$cond." AND drug_categories.catId in (".implode(",", $catList).")";

			/*$str = "SELECT * from `entry`
            LEFT JOIN `drug` ON entry.e_drugId = drug.d_id
            LEFT JOIN `category` ON drug.d_catId = category.c_id
            LEFT JOIN `user` ON entry.e_userId = user.id
            LEFT JOIN `vendor` ON entry.e_vendorId = vendor.v_id
            WHERE e_companyId = ? AND e_date >=? AND e_date <=?".$cond." AND drug.d_catId=".intval($post["catList"]);*/

		} else if ($post['type'] == 'ndc') {
			array_push($params, $post["ndcInput"]);

			$str = "SELECT * from `entry`
            LEFT JOIN `drug` ON entry.e_drugId = drug.d_id

            LEFT JOIN `user` ON entry.e_userId = user.id
            LEFT JOIN `vendor` ON entry.e_vendorId = vendor.v_id
            WHERE e_companyId = ? AND e_date >=? AND e_date <=?".$cond." AND drug.d_code=?";
		} else if ($post['type'] == 'dname') {
			array_push($params, $post["drugName"]);

			$str = "SELECT * from `entry`
            LEFT JOIN `drug` ON entry.e_drugId = drug.d_id

            LEFT JOIN `user` ON entry.e_userId = user.id
            LEFT JOIN `vendor` ON entry.e_vendorId = vendor.v_id
            WHERE e_companyId = ? AND e_date >=? AND e_date <=?".$cond." AND drug.d_name=?";
		}
       $q = $this->db->query($str, $params);
        $res = $q->result_array();

        foreach ($res as $k => $row){
            $res[$k]['variance'] = $row['e_new'] - $row['e_old'];

            if ($row['d_size'] == 0) {
                $res[$k]['costUnit'] = 0;
            } else {
                $res[$k]['costUnit'] = $row['e_costPack']/$row['d_size'];
            }
        }

        return $res;
    }
 public function deleted_transaction_report($from_date = '',$to_date = '',$search_query = '', $company_id){
        
		if ($from_date == '')
		{			
			$query = "select a.*,SUM(e_out)as e_out_total,SUM(e_old)as total_old_qty,SUM(e_new)as total_new_qty, vendor.v_name,
			user.username, drug.d_name,drug.d_code,drug.d_size,a.e_rx,total_lots,
			total_entry_ids,total_drug_ids from entry AS a INNER JOIN 
			(select GROUP_CONCAT(e_lot ORDER BY e_id SEPARATOR ',') as total_lots,GROUP_CONCAT(e_id ORDER BY e_id SEPARATOR '-') as total_entry_ids,
			GROUP_CONCAT(e_drugId ORDER BY e_id SEPARATOR ',') as total_drug_ids,e_rx,e_date,e_transaction_group_id,e_drugId from entry 
			group by e_transaction_group_id)
			as b on a.e_transaction_group_id = b.e_transaction_group_id LEFT JOIN drug 
			ON drug.d_id = a.e_drugId LEFT JOIN user ON user.id = a.e_userId LEFT JOIN vendor ON vendor.v_id = a.e_vendorId where (a.e_type = 'out' 
			OR a.e_type = 'new' OR a.e_type = 'edit') and a.e_companyId =".$company_id." and ((drug.d_name LIKE '%".$search_query."%' 
			and a.e_drugId != b.e_drugId) or a.e_invoice = '".$search_query."' or a.e_rx = '".$search_query."' or 
			drug.d_code = '".$search_query."') and (a.e_status = 3 ) group by  a.e_transaction_group_id order by a.e_id desc"; 
		//echo $query;
		$query = $this->db->query($query);  
		$search_result = $query->result();
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
		
		$q = $this->db->query("SELECT e_type,e_lot,e_out,e_drugId,e_expiration,e_old,e_new FROM entry WHERE e_id IN (".str_replace('-',',',$value->total_entry_ids).")  ORDER BY FIELD(e_id,". str_replace('-',',',$value->total_entry_ids).")");
		
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
		else
		{
		
		$query = "select a.*,SUM(e_out)as e_out_total,SUM(e_old)as total_old_qty,SUM(e_new)as total_new_qty,vendor.v_name,
		user.username, drug.d_name,drug.d_code,drug.d_size,a.e_rx,total_lots,total_entry_ids,
		total_drug_ids from entry AS a INNER
		JOIN (select GROUP_CONCAT(e_lot ORDER BY e_id SEPARATOR ',') as total_lots,GROUP_CONCAT(e_id ORDER BY e_id SEPARATOR '-') as total_entry_ids,
		GROUP_CONCAT(e_drugId ORDER BY e_id SEPARATOR ',') as total_drug_ids,e_rx,e_date,e_transaction_group_id,e_drugId from entry 
		group by e_transaction_group_id) as b on a.e_transaction_group_id = b.e_transaction_group_id 
		LEFT JOIN drug ON drug.d_id = a.e_drugId LEFT JOIN user ON user.id = a.e_userId LEFT JOIN vendor ON 
		vendor.v_id = a.e_vendorId where (a.e_type = 'out' OR a.e_type = 'new' OR a.e_type = 'edit') and a.e_companyId =".$company_id." 
		and (a.e_status = 3 AND a.e_deleteddate >=".$from_date ." AND a.e_deleteddate <".$to_date." )  group by  a.e_transaction_group_id order by a.e_id desc"; 
		//echo "in query:".$query;
		$query = $this->db->query($query);  
		$search_result = $query->result();
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
	}
	
    public function drugReport($post, $cid){

        if ((in_array('a_status', $post['parameters'])) && (!in_array('in_status', $post['parameters']))) {//only Active status
            $w = "AND d_status = 1";
        }
        else if ((!in_array('a_status', $post['parameters'])) && (in_array('in_status', $post['parameters']))) {//only Inactive
            $w = " AND d_status = 0";
        }
        else {
            $w = "";
        }
//echo "w:".$w;
        if ((in_array('e_costPack', $post['parameters'])) || (in_array('costUnit', $post['parameters']))) {
            $str = "
            SELECT drug.*, entry.e_costPack, user.username, category.c_name FROM drug
            LEFT JOIN `category` ON drug.d_catId = category.c_id
            LEFT JOIN user on d_userCreatedId = id
            LEFT JOIN entry ON e_drugId = d_id
            WHERE e_type = 'new' AND d_companyId = ?".$w."
            GROUP by e_drugId ORDER by e_id DESC
            ";
        }
        else {
            $str = "
            SELECT * FROM drug
            LEFT JOIN `category` ON drug.d_companyId = category.c_id
            LEFT JOIN user on d_userCreatedId = id
            WHERE d_companyId = ?
            ".$w;
        }
		//echo "qry string:".$str;
		//break;
        $q = $this->db->query($str, array($cid));
        $res = $q->result_array();

        return $res;
    }

    public function vendorReport($cid){
        $w = array('v_companyId' => $cid);

        $this->db->where($w);
        $q = $this->db->get('vendor');
        $res = $q->result_array();

        return $res;
    }
}