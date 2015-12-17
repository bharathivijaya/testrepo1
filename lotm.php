<?php
	class lotM extends CI_Model
	{
		// update or insert new lot data
		public function updateLog($lotName, $drugId, $count, $operator, $expDate)
		{
			$q = $this->db->query("SELECT * FROM lot WHERE lotName=? and drugId=?", array($lotName, $drugId));

			// Protect from injection
			if ($operator == "+") $operator = "+";
			else $operator = "-";

			// If a record exists
			if ($q->num_rows() > 0)
			{
				// Update exists
				$this->db->query("UPDATE lot SET count=count".$operator."? WHERE lotName=? and drugId=?", array($count, $lotName, $drugId));
			} else {
				// Create new
				if ($expDate) $this->db->query("INSERT INTO lot SET lotName=?, drugId=?, `count`=?, expirationDate=?, active=1", array($lotName, $drugId, $count, $expDate));
			}
		}
		public function bulkuploadqtyin($post,$d_code)
		{
			$expDate=strtotime($post['in_expdate']);
			//echo "expDate:".$expDate;
			
			$q = $this->db->query("SELECT * FROM lot WHERE lotName=? and expirationDate=? and drugId=?", array($post['in_lotname'], $expDate,$d_code));

			//echo "num rows:".$q->num_rows();
//die();
			// If a record exists
			if ($q->num_rows() > 0)
			{
				// Update exists
				$this->db->query("UPDATE lot SET count=count+? WHERE lotName=? and expirationDate=? and drugId=?", array($post['in_lotqtyin'], $post['in_lotname'], $expDate,$d_code));
			} else {
			//	echo "inserting new";
				// Create new
				$this->db->query("INSERT INTO lot SET lotName=?,  `count`=?, expirationDate=?, active=1,drugId=?", array($post['in_lotname'], $post['in_lotqtyin'], $expDate,$d_code));
			}
		}
		public function recountAllLots()
		{
			$this->db->query("UPDATE lot SET active=0 WHERE `count` <= 0 or expirationDate < ?", array(time()));
		}

		public function setLotData($lotName, $drugId, $count, $expDate)
		{
			//echo "lot name:".$lotName;
			$q = $this->db->query("SELECT * FROM lot WHERE lotName=? and drugId=? and expirationDate=?", array($lotName, $drugId, $expDate));
//echo "num rows:".$q->num_rows();
//echo "count:".$count;
			if ($q->num_rows() > 0)
			{
				$this->db->query("UPDATE lot SET `count`=?, expirationDate=? WHERE lotName=? and drugId=?", array($count, $expDate, $lotName, $drugId));
				//die();
			}
			else
				$this->db->query("INSERT INTO lot SET lotName=?, drugId=?, `count`=?, expirationDate=?, active=1", array($lotName, $drugId, $count, $expDate));
		}
		
		public function reverseLotData($lotName, $drugId, $count,$trasaction_type)
		{
			$q = $this->db->query("SELECT count FROM lot WHERE lotName=? and drugId=?", array($lotName, $drugId));
					
			if ($q->num_rows() > 0) 
			{
				$countQty = 0;
				if($trasaction_type == 'new')
				$countQty = $q->row('count') - $count;
				else
				$countQty = $q->row('count') + $count;
				$this->db->query("UPDATE lot SET `count`=? WHERE lotName=? and drugId=?", array($countQty,$lotName, $drugId));	
				
				//after update check lot status and update it if needed
				$updated_query = $this->db->query("SELECT count,expirationDate FROM lot WHERE lotName=? and drugId=?", array($lotName, $drugId));
				if ($updated_query->num_rows() > 0) 
				{
				if($updated_query->row('count') < 1 ||  $updated_query->row('expirationDate') < time())
				$this->db->query("UPDATE lot SET `active`=0 WHERE lotName=? and drugId=?", array($lotName, $drugId));	
				else
				$this->db->query("UPDATE lot SET `active`=1 WHERE lotName=? and drugId=?", array($lotName, $drugId));
				}
									
			}	
		}
		
		public function reverseEntryInLotData($lotName, $drugId, $count)
		{
			$q = $this->db->query("SELECT count FROM lot WHERE lotName=? and drugId=?", array($lotName, $drugId));
			if ($q->num_rows() > 0)
				$this->db->query("UPDATE lot SET `count`=?  WHERE lotName=? and drugId=?", array($q->row('count') - $count,$lotName, $drugId));			
		}
		

		public function getLotsByDrugId($drugId)
		{
			$q = $this->db->query("SELECT * FROM lot WHERE drugId=?", array($drugId));
			return $q->result_array();
		}
	public function getLotsByDrugIdbrt($drugId)
		{
		echo "in lotM:".$drugId;
			$q = $this->db->query("SELECT * FROM lot,drug WHERE drugId=? and lot.drugId=drug.d_id and count!=0", array($drugId));
			return $q->result_array();
		}

		public function getActiveLotsByDrugId($drugId)
		{
			$q = $this->db->query("SELECT * FROM lot WHERE id=? and active=1", array($drugId));
			return $q->result_array();
			
		}
		public function getLotDetails($lotcode,$expdate,$d_code)
		{
			$expdate=strtotime($expdate);
			$query="SELECT * FROM lot WHERE lotName ='".$lotcode."' AND expirationDate ='".$expdate."' and drugId=".$d_code;
			//echo $query;
			  $q = $this->db->query($query);
        //$q = $this->db->query("SELECT *, (SELECT category.c_name FROM category WHERE category.c_id=${tbl}.d_catId) as c_name FROM $tbl WHERE $field = ? AND d_companyId = ?", array($value, $cid));
		//echo $q->num_rows;
		//die();
return $q->num_rows();
		
		}
		public function getlotsbylotsIds($lot_ids , $drug_ids , $e_expiration_date)
		{
			$separator = ',';
			$vals = explode($separator, $lot_ids);
			
			
		  	//Trim whitespace
		  	foreach($vals as $key => $val) {
			$vals[$key] = trim($val);
		  	}
			$this->db->select('*' ); 
			$this->db->from ('lot');	
			$this->db->where_in('lotName', $vals);
			$this->db->where('drugId IN', '('.$drug_ids.')', FALSE);	
			$this->db->where('expirationDate  IN', '('.$e_expiration_date.')', FALSE);
			$query = $this->db->get();
			return $query->result_array();	
		}
		
		
		public function getlotnamesByIds($lot_ids)
		{
			$separator = ',';
			$vals = explode($separator, $lot_ids);
 
		  	//Trim whitespace
		  	foreach($vals as $key => $val) {
			$vals[$key] = trim($val);
		  	}
			$this->db->select('lotName' ); 
			$this->db->from ('lot');
			$this->db->where('active', 1);		
			$this->db->where_in('lotName', $vals);		
			$query = $this->db->get();	
			$lotnames = array();
			foreach ($query->result_array() as $row)
			{
			   $lotnames [] = $row['lotName'];
			}
			return $lotnames;
		}
		

		public function swapLotStatus($lotId)
		{
			$q = $this->db->query("SELECT * FROM lot WHERE id=?", array($lotId));
		$results = $q->result_array();
			
		foreach($results as $row)
				{
			$origstatus=$row['active'];
		if($origstatus==1) $status=0;
		else
			$status=1;
				$this->db->query("UPDATE lot SET active=? WHERE id=?", array($status, $lotId));

			}
			

		
		}
	}
?>