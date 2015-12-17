<?php
/**
 * Created by PhpStorm.
 * User: Tan4ik
 * Date: 23.03.15
 * Time: 14:21
 */
class Edit_transactionm extends CI_Model{ 

    public function search_transaction($from_date = '',$to_date = '',$search_query = '', $company_id){
        
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
			and a.e_drugId != b.e_drugId) or concat(a.e_invoice,a.e_rx) = '".$search_query."' or 
			drug.d_code = '".$search_query."') and (a.e_status = 1 ) group by  a.e_transaction_group_id order by a.e_id desc,concat(a.e_invoice,a.e_rx)"; 
		//echo "from date blank:".$query;
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
		and (a.e_date >=".$from_date." and a.e_date <=".$to_date." ) and (a.e_status = 1  ) group by  a.e_transaction_group_id order by a.e_id desc,concat(a.e_invoice,a.e_rx)"; 
		//echo "from:".$from_date;
		//echo "to:".$to_date;
		//echo $query;
		//die();
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
	}
	
	
	 public function get_transation_details_by_id($transaction_ids){
	 					
			$query = "select entry.*,SUM(e_out)as e_out_total,SUM(e_old)as total_old_qty,SUM(e_new)as total_new_qty,user.username 
			from entry LEFT JOIN user ON  user.id = entry.e_userId where e_id IN (".$transaction_ids.") group by e_transaction_group_id"; 
			//echo $query;
			$query = $this->db->query($query);  
			$transaction_details = $query->result_array(); 			
			$this->db->select('drug.d_id,drug.d_name,drug.d_code,drug.d_descr,drug.d_size,drug.d_manufacturer,drug.d_schedule,entry.e_out,
			entry.e_expiration,entry.e_type,entry.e_lot,entry.e_note,entry.e_date,entry.e_costPack,entry.e_new,entry.e_old'); 
			$this->db->from ('entry');
			$this->db->join ('drug','drug.d_id = entry.e_drugId' , 'left' );
			$this->db->join ('user','user.id = entry.e_userId' , 'left' );
			$this->db->where('e_id IN', '('.$transaction_ids.')', FALSE);	
			$query = $this->db->get();
            $drug_details = $query->result();
			
			return array('transaction_details'=>$transaction_details,'drug_details'=>$drug_details);
			//print_r(array('transaction_details'=>$transaction_details,'drug_details'=>$drug_details)); die();
				
	}

 	public function get_transation_details_by_id1($transaction_ids){	 					
			$this->db->select('drug.*,entry.e_type,entry.e_numPacks,entry.e_returned,entry.e_costPack,entry.e_rx,entry.e_lot,entry.e_invoice'); 
			$this->db->from ('entry');
			$this->db->join ('drug','drug.d_id = entry.e_drugId' , 'left' );
			$this->db->where('e_id IN', '('.$transaction_ids.')', FALSE);	
			$query = $this->db->get();
			if ($query->num_rows()) {
            $res = $query->result_array();
            return $res[0];
            } else
			return array();		
	}
	
	
	
	
	public function transaction_history($transaction_ids){
	
	$this->db->select('entry.*,vendor.v_name,user.username,drug.d_name,drug.d_code,drug.d_size,drug.d_descr,drug.d_manufacturer,drug.d_schedule' ); 
	$this->db->from ('entry');
	$this->db->join ('drug','drug.d_id = entry.e_drugId' , 'left' );
	$this->db->join ('user','user.id = entry.e_userId' , 'left' );
	$this->db->join ('vendor','vendor.v_id = entry.e_vendorId' , 'left' );			
	$this->db->where('e_original_entry_id IN', '('.$transaction_ids.')' .'order by e_last_edit_date desc', FALSE);	
	$query = $this->db->get();
	return $query->result ();	
	}
	
	
	public function getentrysbyentryIds($transaction_ids){
	$transaction_ids = str_replace('-',',',$transaction_ids);
	$this->db->select('*' ); 
	$this->db->from ('entry');		
	$this->db->where('e_id IN', '('.$transaction_ids.')', FALSE);	
	$query = $this->db->get();
	return $query->result ();	
	}
	
	public function edit_transaction($transaction_ids)
	{
	
	$query = "SELECT e_original_entry_id AS parent_id FROM entry WHERE e_id 
	IN (".$transaction_ids.") GROUP BY e_transaction_group_id"; 
	//echo $query;
	$parent_ids = $this->db->query($query)->row()->parent_id;
	$parentId = explode(',',$parent_ids);
	$original_transaction = array();
	$transaction_history  = array();
	$selected_transaction  = array();

	if($parentId[0] < 0 )
	{
	$query_original = $this->get_original_transaction_query($transaction_ids);
	$query_original = $this->db->query($query_original);  
	$original_transaction = $query_original->result();
	$selected_transaction = $original_transaction;
	}
	else
	{
	
	$query_original = $this->get_original_transaction_query($parent_ids);
	$query_original = $this->db->query($query_original);  
	$original_transaction = $query_original->result();
	
	$transaction_history = $this->transaction_history($parent_ids);
	
	$query_selected = $this->db->query($this->get_original_transaction_query($transaction_ids));  
	$selected_transaction = $query_selected->result();
	//print_r($selected_transaction); die();
	}
	
	return array('original_transaction'=>$original_transaction,'transaction_history'=>$transaction_history,'selected_transaction'=>$selected_transaction);
	 
	}


	public function edit_deletedtransaction($transaction_ids)
	{
	
	$query = "SELECT e_original_entry_id AS parent_id FROM entry WHERE  e_id 
	IN (".$transaction_ids.")GROUP BY e_transaction_group_id"; 
	//echo $query;
	$parent_ids = $this->db->query($query)->row()->parent_id;
	$parentId = explode(',',$parent_ids);
	//echo $parentId[0];
	$original_transaction = array();
	$transaction_history  = array();
	$selected_transaction  = array();

	if($parentId[0] < 0 )
	{
		//echo "parent id block";
	$query_original = $this->get_original_transaction_query($transaction_ids);
	$query_original = $this->db->query($query_original);  
	$original_transaction = $query_original->result();
	$selected_transaction = $original_transaction;
	}
	else
	{
//	echo " i am here";
	$query_original = $this->get_original_transaction_query($parent_ids);
	$query_original = $this->db->query($query_original);  
	$original_transaction = $query_original->result();
	
	$transaction_history = $this->transaction_history($parent_ids);
	
	$query_selected = $this->db->query($this->get_original_transaction_query($transaction_ids));  
	$selected_transaction = $query_selected->result();
	//print_r($selected_transaction); die();
	}
	
	return array('original_transaction'=>$original_transaction,'transaction_history'=>$transaction_history,'selected_transaction'=>$selected_transaction);
	 
	}
	
	public function update_entry($transaction_ids)
	{
		//echo "transaction ids:".$transaction_ids;

	$myArray = explode(',',$transaction_ids);
	$data = array('e_status'=>2); // 2 means edited
	$this->db->where_in('e_id',$myArray);
	$this->db->update('entry', $data);
	}
	
	public function reverse_entry($transaction_id)
	{
	$myArray = explode(',',$transaction_id);
		$now = new DateTime();
	$data = array('e_status'=>3,'e_deleteddate'=>$now->getTimestamp()); // 3 means reversed
	$this->db->where_in('e_id',$myArray);
	$this->db->update('entry', $data);
	}
	 
	public function get_original_transaction_query($transaction_ids)
	{
	
	$query = "select a.*,SUM(e_out)as e_out_total,SUM(e_old)as total_old_qty,SUM(e_new)as total_new_qty, vendor.v_name, user.username, drug.d_name, drug.d_code, drug.d_size, 
	drug.d_descr, drug.d_manufacturer,drug.d_schedule,total_lots,total_entry_ids,drug_ids,total_e_expiration from entry AS a INNER JOIN 
	(select GROUP_CONCAT(e_lot ORDER BY e_id SEPARATOR ',') as total_lots, GROUP_CONCAT(e_id ORDER BY e_id SEPARATOR '-') as total_entry_ids,
	GROUP_CONCAT(e_drugId ORDER BY e_id SEPARATOR ',') as drug_ids,GROUP_CONCAT(e_expiration ORDER BY e_id SEPARATOR ',') as total_e_expiration, e_rx,e_date,e_transaction_group_id from entry group by e_transaction_group_id order by e_id) as b
	on  a.e_transaction_group_id = b.e_transaction_group_id  LEFT JOIN drug ON drug.d_id = a.e_drugId LEFT JOIN user ON user.id = a.e_userId
	LEFT JOIN vendor ON vendor.v_id = a.e_vendorId WHERE a.e_id IN (".$transaction_ids.") 
	UNION
	SELECT entry.*,e_out as e_out_total,e_old as total_old_qty,e_new as total_new_qty, vendor.v_name, user.username, drug.d_name,
	drug.d_code, drug.d_size,drug.d_descr,drug.d_manufacturer,drug.d_schedule , entry.e_lot as total_lots ,entry.e_id as total_entry_ids,
	entry.e_drugId as drug_ids,entry.e_expiration as total_e_expiration
	from entry LEFT JOIN drug ON drug.d_id = entry.e_drugId LEFT JOIN user ON user.id = entry.e_userId LEFT JOIN vendor 
	ON vendor.v_id = entry.e_vendorId WHERE entry .e_id IN (".$transaction_ids.") order by e_id";
	return $query;
	}
	
	
	public function reverse_transaction($transaction_ids){
	
	$this->db->select('entry.*,vendor.v_name,user.username,drug.d_name,drug.d_code,drug.d_size,drug.d_descr,drug.d_manufacturer,drug.d_schedule' ); 
	$this->db->from ('entry');
	$this->db->join ('drug','drug.d_id = entry.e_drugId' , 'left' );
	$this->db->join ('user','user.id = entry.e_userId' , 'left' );
	$this->db->join ('vendor','vendor.v_id = entry.e_vendorId' , 'left' );			
	$this->db->where('e_id IN', '('.$transaction_ids.')', FALSE);	
	$query = $this->db->get();
	return $query->result ();	
	}
	
}