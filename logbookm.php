<?php
/**
 * Created by PhpStorm.
 * User: Tan4ik
 * Date: 23.03.15
 * Time: 14:21
 */
class logbookM extends CI_Model {

    var $table   = 'entry';

    public function getAll($cid){
        $this->db->where('e_companyId', $cid);
        $q = $this->db->get($this->table);
        return $q->result_array();
    }

   /* public function getLast($num, $cid){
        $q = $this->db->query('select * from '.$this->table.'
        left join `user` on user.id = entry.e_userId
        left join `drug` on drug.d_id = entry.e_drugId
        where e_companyId = ? order by e_id desc limit '.$num, array($cid));
        $res = $q->result_array();
        return $res;
    }*/
	  public function getLast($num, $cid){
		//echo $num;
		//echo $cid;
		$sdate1=time();

if(isset($_POST['sdate1']) && $_POST['sdate1']!="")
		{
			$sdate1=$_POST['sdate1'];
		}
		$sdate2=$sdate1;
if(isset($_POST['sdate2']) && $_POST['sdate2']!="")
		{
			$sdate2=$_POST['sdate2'];
		}

$prevWeek = time() - (7 * 24 * 60 * 60);

if(isset($_POST['sdate1'])) {
	$sdate1str=strtotime($sdate1);
	$sdate2str=strtotime($sdate2);
	if($sdate1str==$sdate2str) $sdate2str=$sdate2str+24*60*60;
		        $params = array($cid, $sdate1str, $sdate2str ,$num);
				}
else
		{
  $params = array($cid, $prevWeek, $sdate2,$num );
		}
		//echo "entried dates: sdate1:".$sdate1."    sdate2".$sdate2;

		$query='select * from '.$this->table.'
        left join `user` on user.id = entry.e_userId
        left join `drug` on drug.d_id = entry.e_drugId
        where e_companyId = ? AND e_date >=? AND e_date <=? group by e_original_entry_id order by e_id limit ?';
		//echo $query;
        $q = $this->db->query($query,$params);
		//echo $q;
		//break;
        $res = $q->result_array();
        return $res;
    }


    public function getNegative($cid){
        $q = $this->db->query('select * from `drug` where d_onHand < 0 and d_companyId = ?', array($cid));
        $res = $q->result_array();
        return $res;
    }

   /* public function getAudits($num, $cid){
        $q = $this->db->query('SELECT * FROM `entry`
        LEFT JOIN `drug` ON drug.d_id = entry.e_drugId
        LEFT JOIN `user` ON user.id = entry.e_userId
        WHERE e_type = "audit" AND e_companyId = ? ORDER BY e_id DESC LIMIT ?', array($cid, $num));
        $res = $q->result_array();
        return $res;
    }*/
	  public function getAudits($num, $cid){

$date1=time();

if(isset($_POST['date1']) && $_POST['date1']!="")
		{
			$date1=$_POST['date1'];
		}
		$date2=$date1;
if(isset($_POST['date2']) && $_POST['date2']!="")
		{
			$date2=$_POST['date2'];
		}

$prevWeek = time() - (7 * 24 * 60 * 60);
if(isset($_POST['date1'])) {
		        $params = array($cid, strtotime($date1), strtotime($date2) ,$num);}
else
		{
  $params = array($cid, $prevWeek, $date2,$num );
		}
//echo "audit dates: date1:".$date1."    date2".$date2;
        $q = $this->db->query('SELECT * FROM `entry`
        LEFT JOIN `drug` ON drug.d_id = entry.e_drugId
        LEFT JOIN `user` ON user.id = entry.e_userId
        WHERE e_type = "audit" AND e_companyId = ? AND e_date >=? AND e_date <=? ORDER BY e_id DESC LIMIT ?', $params);
		
	
	
        $res = $q->result_array();
        return $res;
    }

	// Get company alerts
	public function getAlerts($cid){
		$q = $this->db->query('SELECT * FROM alerts WHERE compId=?', array($cid));
		$res = array();

		foreach ($q->result_array() as $alert)
		{
			if ($alert["alertType"] == "Inventory") {
				if ($alert["drugList"] != "") {
					$q2 = $this->db->query('SELECT d_name, d_code, d_onHand FROM drug WHERE d_id in (' . $alert["drugList"] . ')');
					$alert["drugsData"] = $q2->result_array();
				}
				array_push($res, $alert);
			}
			if ($alert["alertType"] == "Audit") {
				if ($alert["drugList"] != "") {
					$q2 = $this->db->query('SELECT d_name, d_code, d_onHand FROM drug WHERE d_id in (' . $alert["drugList"] . ')');
					$alert["drugsData"] = $q2->result_array();
				}
				array_push($res, $alert);
			}
		}

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


 public function bulkuploadqtyin($file, $cid)
	{
        $updatedlotnos = array();
        $answer = '';
$newlotnos=array();
$newrx=array();
$oldrx=array();
$v_id="";
$oldqty=0;
$errentries=array();
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
                    $qtyins = explode("\r\n", $string);
                    array_shift($qtyins);//remove the header
            
$totalentries=sizeof($qtyins);
                    foreach ($qtyins as $one)
					{
                        
						//echo "errentries:".sizeof($errentries);
						//echo "<br/>";
						$qtyin = explode(",", $one);
                        //print_r($drug);

                        if (isset($qtyin[1]))
						{
                           
						    $alllotsforrx = explode("/",$qtyin[6]);
						$ls=sizeof($alllotsforrx);
						   $allexpdatesforrx =  explode("/",$qtyin[7]);
						 $ex=sizeof($allexpdatesforrx);
						   $alllotqtysforrx= explode("/",$qtyin[8]);
						 $qs=sizeof($alllotqtysforrx);
 $dcode= $this->drugM->get_drug_byndc($qtyin[3],$cid);
										foreach ($dcode as $dc)
												{
													$d_code=$dc->d_id;
													$lottracking=$dc->d_lotTracking;
													$size=$dc->d_size;
												}
												if($lottracking==1) {
						  if(!( $ls==$ex && $ex==$qs)) { $errentries[]= $qtyin[1];$errentries[]="Difference in number of lot nos , exp dates, qtys -  skipped<br/>";continue;}
						$sumqty=0;
						
						  for($f=0;$f<$qs;$f++)
							{
							
							  $sumqty=$sumqty+$alllotqtysforrx[$f];
							}
							
							
							if($sumqty != $qtyin[9]) { echo "sumqty:".$sumqty; echo "qty9:".$qtyin[9];$errentries[]=$qtyin[1];$errentries[]="Lot total do not match lot qty - skipped"; continue;}

							if(($size*$qtyin[4])!=$sumqty) { $errentries[]=$qtyin[1];$errentries[]="Total lot Qty not matched with DB size* no of packs -- skipped"; continue;}

								$vendorids=$this->getvendorid(strtoupper($qtyin[2]));

							foreach($vendorids as $vendorid)
												{
														$v_id=$vendorid['v_id'];
														//echo "vid:".$v_id;
												}

								if($v_id=="") {  $errentries[]=$qtyin[1];$errentries[]="Vendor not found --  skipped"; continue;}



												} // if lotttracking 1 end


					for($cn=0;$cn<sizeof($alllotsforrx);$cn++)
							{
                                 $post = array(
                                'in_date' => $qtyin[0],
                                'in_rx' => $qtyin[1],
                                'in_vendor' => $qtyin[2],
                                'in_ndc' => $qtyin[3],
                                'in_noofpacks' => $qtyin[4],
                                'in_acqcost' => $qtyin[5],
                                'in_lotname' =>  $alllotsforrx[$cn],//$qtyin[6],
                                'in_expdate' => $allexpdatesforrx[$cn],//$qtyin[7]
                                'in_lotqtyin' => $alllotqtysforrx[$cn]//$qtyin[8]
                            );
						
									 $uid 		= $this->session->userdata('id');

										 if($this->checkentryexists($post['in_rx'],$post['in_date'],$d_code,$alllotsforrx[$cn])==0)
											{
													
												
													

							$this->lotM->bulkuploadqtyin($post,$d_code);
							
							$newrx[]= $post['in_rx'];
													//	echo "vendor from csv:".$post['in_vendor'];
													
														$transaction_group_id = 'tgid-'.time();
														$this->db->query("INSERT INTO entry SET
														e_type=?,       e_companyId=?,  e_userId=?,
														e_drugId=?,     e_date=?,       e_invoice=?,
														e_vendorId=?,   e_numPacks=?,   e_costPack=?,
														e_lot=?,        e_expiration=?, e_old=?,
														e_new=?,        e_rx=?,         e_returned=?,
														e_out=?,        e_note=?,       e_transaction_group_id=?",
														array(
														'new',                              $cid,                               $uid,
														$d_code,                    strtotime($post['in_date']),        '',
														$v_id,                @$post["in_noofpacks"],                 @$post["in_acqcost"],
														$alllotsforrx[$cn],                 strtotime( $post['in_expdate']),    $oldqty,
														$alllotqtysforrx[$cn],                               @$post["in_rx"],                    0,
														0,                  "",$transaction_group_id    )
														);
											}
										else
													$oldrx[] = $post['in_rx'];
										if ($this->checkLotExpdate($alllotsforrx[$cn], $allexpdatesforrx[$cn],$d_code)==0)
												{
													$newlotnos[] = $alllotsforrx[$cn];
												}
													else
												{
													$updatedlotnos[] = $alllotsforrx[$cn];
													$results=$this->getlotcount($alllotsforrx[$cn],$d_code);
													foreach($results as $result)
												{
														$oldqty=$result['count'];
														//echo "vid:".$v_id;
												}
												}
												

                            $answer = 'ok';
                        }
                    }
                }
				}
			}else {
                $answer = 'internal server error';
            }
        }

        $arr = array('answer' => $answer, 'newlotnos' => $newlotnos, 'updatedlotnos' =>$updatedlotnos, 'errentries'=>$errentries,'oldrx'=>$oldrx,'newrx'=>$newrx);
        return $arr;
    }
public function getvendorid($vendorname)
	{
		$q= "select * from vendor where v_name ='".$vendorname."'";
	//	echo "vendor query:".$q;

		$res=$this->db->query($q);
		return $res->result_array();
	}
	public function checkentryexists($rx, $indate,$d_code,$lotcode) {
       $q= "select * from entry where concat(e_rx,e_invoice) ='".$rx."'  and e_date='".strtotime($indate)."' and e_drugId='".$d_code."' and e_lot='".$lotcode."'";
	   $result = $this->db->query($q);

      return $result->num_rows();

    }
	public function getlotcount($lotname,$d_code){
		$q="select * from lot where lotName='".$lotname."' and drugId='".$d_code."'";
		$res =  $this->db->query($q);
		return $res->result_array();
	}
 public function checkLotExpDate($lotcode, $expdate,$d_code) {
        $noofrows = $this->lotM->getLotDetails( $lotcode, $expdate,$d_code);

      return $noofrows;
    }
	public function checkLotRXCode($code)
	{
		$q = $this->db->query("SELECT * FROM entry WHERE e_rx=?", array($code));
		$result = $q->result_array();
		return $result;
	}
public function checkLotNumber($code)
	{
		$q = $this->db->query("SELECT * FROM lot WHERE lotname=?", array($code));
		$result = $q->result_array();
		return $result;
	}

	public function checkLotInvoice($code)
	{
		$q = $this->db->query("SELECT * FROM entry WHERE concat(e_invoice,e_rx)=? ", array($code));
		$result = $q->result_array();
		return $result;
	}

		public function getActiveLots()
	{
		$q = $this->db->query("SELECT * FROM lot WHERE active=1 and count>0");
		$result = $q->result_array();
		return $result;
	}
	public function getActiveLotsbrt()
	{
	$date1=time();
	//echo $date1;
		$q = $this->db->query("SELECT * FROM lot WHERE active=1 and expirationDate>=?",array($date1));
		$result = $q->result_array();
		return $result;
	}
	public function getActiveLotsByName($name)
	{
		$q = $this->db->query("SELECT * FROM lot WHERE active=1 and lotName LIKE ?", array("%".$name."%"));
		$result = $q->result_array();
		return $result;
	}
	


	public function getActiveLotsByDrugID($drugId)
	{
		$q = $this->db->query("SELECT * FROM lot WHERE active=1 and drugId=?", array($drugId));
		$result = $q->result_array();
		return $result;
	}
	
	public function getActiveLotsByDrugIDEditTransaction($drugId)
	{
		$date1=time();

$q = $this->db->query("SELECT * FROM lot WHERE expirationDate>= ? and drugId=?", array($date1,$drugId));
		$result = $q->result_array();
		return $result;
	}
	public function getActiveLotsByNameEditTransaction($name)
	{
		$date1=time();
		$q = $this->db->query("SELECT * FROM lot WHERE  expirationDate>=? and lotName LIKE ?", array($date1,"%".$name."%"));
		$result = $q->result_array();
		return $result;
	}
	public function getActiveLot($name, $active)
	{
		$q = $this->db->query("SELECT * FROM lot WHERE active=? and lotName=?", array($active, $name));
		$result = $q->result_array();
		return $result;
	}
	public function ifHasDontAllowDrug($cid, $drugId, $lot)
	{
		// get inventory
		$q = $this->db->query("SELECT * FROM alerts WHERE compId=? and alertType='Inventory'", array($cid));

		foreach ($q->result_array() as $alert)
		{
			$d_ids = explode(",", $alert["drugList"]);

			for ($i = 0; $i < count($d_ids); $i++)
			{
			    if ($d_ids[$i] == $drugId && $alert["dontAllowUseDays"] > 0)
				{
					$q2 = $this->db->query("SELECT * FROM lot WHERE drugId=? and lotName=? and expirationDate - ? <= ?", array($drugId, $lot, $alert["dontAllowUseDays"] * 86400, time()));

					foreach ($q2->result_array() as $lot)
					{
						return true;
					}
				}
			}

			return false;
		}
	}
	
	public function ifHasAuditForce($cid)
	{
		$alerts = $this->getRealAlerts($cid);
		foreach ($alerts as $alert)
			if ($alert["alertType"] == "Audit" and $alert["forceAudit"] == "On") return true;
		return false;
	}

	public function ifHadAuditForceByNDC($cid, $ndc)
	{
		$alerts = $this->getRealAlerts($cid);
		foreach ($alerts as $alert)
			if ($alert["alertType"] == "Audit" and $alert["forceAudit"] == "On" and $alert["productNDC"] == $ndc) return true;
		return false;
	}

	

	private function checkDrugDoneAudit($drugId, $date)
	{
		$q = $this->db->query("SELECT * FROM entry WHERE e_drugId=? and e_type='audit' and e_date >= ? ORDER by e_date DESC LIMIT 0, 1", array($drugId, $date));
		if ($q->num_rows() > 0) return $q->result_array();
		return false;
	}
	
	private function updateDrugAudit($alertId, $drugId, $date)
	{
		$q = $this->db->query("SELECT * FROM alert_audit_drugs WHERE drug_id=? and alert_id=?", array($alertId, $drugId));

		if ($q->num_rows() > 0)
		{
			$this->db->query("UPDATE alert_audit_drugs SET last_audit=? WHERE alert_id=? and drug_id=?", array($date, $alertId, $drugId));
		} else {
			$this->db->query("INSERT INTO alert_audit_drugs SET drug_id=?, alert_id=?, last_audit=?", array($drugId, $alertId, $date));
		}
	}

	private function getAuditAlertDrugStartDate($alertId, $drugId)
	{
		$q = $this->db->query("SELECT * FROM alert_audit_drugs WHERE alert_id=? and drug_id=?", array($alertId, $drugId));
		if ($q->num_rows() > 0)
		{
			$res = $q->result_array();
			return $res[0]["start_audit"];
		} else return 0;
	}

	private function updateAllAlertAuditParams($alert)
	{
		$q = $this->db->query("SELECT * FROM alert_audit_drugs WHERE alert_id=?", array($alert["id"]));

		foreach ($q->result_array() as $auditParam)
		{
			$entry = $this->checkDrugDoneAudit($auditParam["drug_id"], $auditParam["start_audit"]);

			if ($entry)
			{
				if ($alert["rescheduleAuditAfterLastAudit"] == "Off")
					$this->db->query("UPDATE alert_audit_drugs SET last_audit=?, start_audit=start_audit+? WHERE alert_id=? and drug_id=?", array(
						$entry[0]["e_date"],
						$alert["auditFrequency"] * 86400,
						$alert["id"],
						$auditParam["drug_id"]
					));
				else
					$this->db->query("UPDATE alert_audit_drugs SET last_audit=?, start_audit=? WHERE alert_id=? and drug_id=?", array(
						$entry[0]["e_date"],
						strtotime(date("Y/m/d", time())) + $alert["auditFrequency"] * 86400,
						$alert["id"],
						$auditParam["drug_id"]
					));
			}
		}
	}

	// Get real alerts
	public function getRealAlerts($cid)
	{
		$alertBack = array();
		$alertsIds = array();
		$lotsIds = array();
		//$alertLastAppearDate = array();

		// update audit alerts
		$q = $this->db->query("SELECT * FROM alerts WHERE alertType='Audit' and drugList != '' and alertStatus=1", array($cid));
		foreach ($q->result_array() as $r2) {
			$this->updateAllAlertAuditParams($r2);			
		}

		// get inventory
		$alrq = $this->db->query("SELECT * FROM alerts WHERE compId=? and alertStatus=1", array($cid));

		// Only drugs
		$q3 = $this->db->query('SELECT * FROM drug WHERE d_onHand < 0 and d_companyId = ?', array($cid));
		foreach ($q3->result_array() as $r2) {
			array_push($alertBack, array(
				"alertId" => 0,
				"alertType" => "Inventory",
				"forceAudit" => "",
				"drugId" => $r2["d_id"],
				"productName" => $r2["d_name"],
				"productNDC" => $r2["d_code"],
				//"qoh" => $r2["count"],
				"qoh" => "<b style='color: #ff0000;'>".$r2["d_onHand"]."</b>",
				"manufacturer" => $r2["d_manufacturer"],
				"lotNumber" => "", // $r2["lotName"],
				"expirationDate" => "", //date("m/d/Y", $r2["expirationDate"]),
				"dontAllowUseDays" => "",
				"reOrderQty" => ""
			));
		}

		foreach ($alrq->result_array() as $alert)
		{
			if ($alert["drugList"] == "") continue;

			// Select inventory alerts
			if ($alert["alertType"] == "Inventory") {
				// select low quantity
				

				$q2 = $this->db->query("SELECT * FROM drug WHERE d_id in (" . $alert["drugList"] . ") and d_onHand < ?", array($alert["reOrderQty"]));

				foreach ($q2->result_array() as $r2) {
					array_push($alertBack, array(
						"alertId" => $alert["id"],
						"alertType" => "Inventory",
						"forceAudit" => $alert["forceAudit"],
						"drugId" => $r2["d_id"],
						"productName" => $r2["d_name"],
						"productNDC" => $r2["d_code"],
						//"qoh" => $r2["count"],
						"qoh" => $r2["d_onHand"],
						"manufacturer" => $r2["d_manufacturer"],
						"lotNumber" => "", //$r2["lotName"],
						"expirationDate" => "", //date("m/d/Y", $r2["expirationDate"]),
						"dontAllowUseDays" => $alert["dontAllowUseDays"],
						"reOrderQty" => $alert["reOrderQty"]
					));

					array_push($alertsIds, $alert["id"]);

					if (empty($lotsIds[$alert["id"]])) $lotsIds[$alert["id"]] = array($r2["d_id"] * 1);
					else {
						array_push($lotsIds[$alert["id"]], $r2["d_id"]);
						$lotsIds[$alert["id"]] = array_unique($lotsIds[$alert["id"]]);
					}
				}

				if ($alert["productExpirationAlert"] == "On")
					$q2 = $this->db->query('SELECT drug.d_id, drug.d_manufacturer, drug.d_onHand, drug.d_name, drug.d_code, lot.* FROM lot, drug WHERE lot.active=1 and drug.d_id=lot.drugId and lot.drugId in (' . $alert["drugList"] . ') and (lot.expirationDate - ' . ($alert["daysBeforeProductExpires"] * 86400) . ' <= ' . time() . ')');

				foreach ($q2->result_array() as $r2) {
					array_push($alertBack, array(
						"alertId" => $alert["id"],
						"alertType" => "Inventory",
						"forceAudit" => $alert["forceAudit"],
						"drugId" => $r2["d_id"],
						"productName" => $r2["d_name"],
						"productNDC" => $r2["d_code"],
						"qoh" => $r2["d_onHand"],
						"manufacturer" => $r2["d_manufacturer"],
						"lotNumber" => $r2["lotName"],
						"expirationDate" => date("m/d/Y", $r2["expirationDate"]),
						"dontAllowUseDays" => $alert["dontAllowUseDays"],
						"reOrderQty" => $alert["reOrderQty"]
					));

					array_push($alertsIds, $alert["id"]);
					
					if (empty($lotsIds[$alert["id"]])) $lotsIds[$alert["id"]] = array($r2["d_id"] * 1);
					else {
						array_push($lotsIds[$alert["id"]], $r2["d_id"] * 1);
						$lotsIds[$alert["id"]] = array_unique($lotsIds[$alert["id"]]);
					}
				}
			}

			if ($alert["alertType"] == "Audit") {
				// Skip overdue audits alert
				if ($alert["auditEndDate"] > 0 && time() >= $alert["auditEndDate"]) continue;

				$q2 = $this->db->query('SELECT * FROM drug WHERE d_id in ('.$alert["drugList"].')');

				foreach ($q2->result_array() as $r2)
				{
					$drugAuditStartDate = intval($this->getAuditAlertDrugStartDate($alert["id"], $r2["d_id"]));
					$drugAuditStatus = $this->checkDrugDoneAudit($r2["d_id"], $drugAuditStartDate);

					if ((!$drugAuditStatus && $drugAuditStartDate < time()) ||
						($alert["negativeInventory"] == "On" && $r2["d_onHand"] < 0) ||
						($alert["quantityLimit"] == "On" && ($r2["d_onHand"] <= $alert["lessThan"] || $r2["d_onHand"] >= $alert["greaterThan"])))
					{
						array_push($alertBack, array(
							"alertId" => $alert["id"],
							"alertType" => "Audit",
							"forceAudit" => $alert["forceAudit"],
							"drugId" => $r2["d_id"],
							"productName" => $r2["d_name"],
							"productNDC" => $r2["d_code"],
							"manufacturer" => $r2["d_manufacturer"],
							"qoh" => $r2["d_onHand"],
							"lotNumber" => "-",
							"expirationDate" => "-",
							"auditDateDue" => date("m/d/Y", $drugAuditStartDate),
							"reOrderQty" => "-"
						));

						array_push($alertsIds, $alert["id"]);
						if (empty($lotsIds[$alert["id"]])) $lotsIds[$alert["id"]] = array($r2["d_id"] * 1);
						else {
							array_push($lotsIds[$alert["id"]], $r2["d_id"]);
							$lotsIds[$alert["id"]] = array_unique($lotsIds[$alert["id"]]);
						}
					}
				}

			}
		}

		//$alertsIds = array_unique($alertsIds);

		// load temp alerts now
		$alertsNow = array();
		$q111 = $this->db->query("SELECT * FROM alert_tmp WHERE comp_id=?", array($cid));
		foreach ($q111->result_array() as $alert_tmp) array_push($alertsNow, $alert_tmp["alert_id"]."_".$alert_tmp["drug_id"]);

		$this->db->query("DELETE FROM alert_tmp WHERE comp_id=?", array($cid));
		$this->db->query("ALTER TABLE alert_tmp AUTO_INCREMENT = 1");

		foreach ($lotsIds as $key => $value)
		{
			for ($i = 0; $i < count($value); $i++)
			{
				$qcheck = $this->db->query("SELECT * FROM alert_tmp WHERE drug_id=? and alert_id=?", array($value[$i], $key * 1));

				if ($qcheck->num_rows() <= 0)
					$this->db->query("INSERT INTO alert_tmp SET drug_id=?, alert_id=?, comp_id=?", array($value[$i], $key * 1, $cid));
			}
		}

		// load temp alerts after
		$alertsAfter = array();
		$q222 = $this->db->query("SELECT * FROM alert_tmp WHERE comp_id=?", array($cid));
		foreach ($q222->result_array() as $alert_tmp) array_push($alertsAfter, $alert_tmp["alert_id"]."_".$alert_tmp["drug_id"]);

		for ($i = 0; $i < count($alertsNow); $i++)
		{
			if (!in_array($alertsNow[$i], $alertsAfter))
			{
				$adata = explode("_", $alertsNow[$i]);
				$this->db->query("INSERT INTO alert_history SET alert_id=?, drug_id=?, alert_end=?", array($adata[0], $adata[1], time()));
			}
		}

		return $alertBack;
	}

    public function saveEntry($cid, $uid, $post)
    {	
		$transaction_group_id = 'tgid-'.time();
		$e_ndc_type           = 0; // 0 for single NDC and 1 for multiple NDC
		
		if ($post['e_type'] == 'multi_out')
		{
			$e_ndc_type           = 1; //for multiple NDC
			
			$drugs = array();
			// Each drugs
			foreach ($post["e_drugId"] as $key => $value)
			{
				$drugs[$value] = array('drugInfo' => array(
					'e_out' => $post["e_out"][$key],
					'e_old' => $post["e_old"][$key],
					'e_new' => $post["e_new"][$key],
					'e_note' => $post["e_note"][$key],
					'e_date' => strtotime($post["e_date"]),
					'e_rx' => $post["e_rx"]
				), 'drugLots' => array());

				// Update drug
				$arr = array('d_onHand' => $post["e_new"][$key]);
				$this->drugM->editDrug($value, $arr);
				
				// Each lots
				if (isset($post["out_lot_".$value]) && $post["out_lot_".$value])
				for ($i = 0; $i < count($post["out_lot_".$value]); $i++)
				{
					array_push($drugs[$value]['drugLots'], array(
						'e_lot' => $post["out_lot_".$value][$i],
						'e_expiration' => $post["out_expiration_".$value][$i],
						'e_old' => (int)$post["out_oldqoh_".$value][$i],
						'e_new' => (int)$post["out_oldqoh_".$value][$i] - (int)$post["out_qoh_".$value][$i],
						'e_out' => (int)$post["out_qoh_".$value][$i]
					));
				 
				}
			}
			
			foreach ($drugs as $key => $value)
			{	
				//if lot tracking is disabled for any drug
				if(count($value['drugLots']) == 0)
				{
				 	$array_values   = array();
					array_push($array_values,'out');
					array_push($array_values,$cid);
					array_push($array_values,$uid);
					array_push($array_values,$key);
					array_push($array_values,$value['drugInfo']['e_date']);
					array_push($array_values,''); //invoice
					array_push($array_values,''); // vendor id
					array_push($array_values,''); // no of packs
					array_push($array_values,''); // cost per pack
					array_push($array_values,''); // lot number
					array_push($array_values,''); // lot expiration date
					array_push($array_values,''); // drug old in lot
					array_push($array_values,''); // drug new in lot
					array_push($array_values,$value['drugInfo']['e_rx']);
					array_push($array_values,0); // returned
					array_push($array_values,$value['drugInfo']['e_out']);
					array_push($array_values,$value['drugInfo']["e_note"]);
					array_push($array_values,$transaction_group_id);
					array_push($array_values,$e_ndc_type);
				    //save to db
					$this->insertEntry($array_values);
				}
				
				else
				{
				for ($i = 0; $i < count($value['drugLots']); $i++)
				{
					$this->lotM->setLotData($value['drugLots'][$i]['e_lot'], $key, $value['drugLots'][$i]['e_new'], 
					strtotime($value['drugLots'][$i]['e_expiration']));
					$array_values   = array();
					array_push($array_values,'out');
					array_push($array_values,$cid);
					array_push($array_values,$uid);
					array_push($array_values,$key); // drug id
					array_push($array_values,$value['drugInfo']['e_date']);
					array_push($array_values,''); //invoice
					array_push($array_values,''); // vendor id
					array_push($array_values,''); // no of packs
					array_push($array_values,''); // cost per pack
					array_push($array_values,$value['drugLots'][$i]['e_lot']);
					array_push($array_values,strtotime($value['drugLots'][$i]['e_expiration']));
					array_push($array_values,$value['drugLots'][$i]['e_old']);
					array_push($array_values,$value['drugLots'][$i]['e_new']);
					array_push($array_values,$value['drugInfo']['e_rx']);
					array_push($array_values,0); // returned
					array_push($array_values,$value['drugLots'][$i]["e_out"]);
					array_push($array_values,$value['drugInfo']["e_note"]);
					array_push($array_values,$transaction_group_id);
					array_push($array_values,$e_ndc_type);
					//save to db
					$this->insertEntry($array_values);
					
				}
			  }
			}
		   	
		} 
		else if ($post['e_type'] == 'new_mul' || $post['e_type'] == 'return_mul' || $post['e_type'] == 'out_mul')
		{
			//echo "Process each lot";
		
			// Process each lot
			$multidata = array();
			if(@$post['audit_lot']) 
			foreach (@$post['audit_lot'] as $key => $value)
			{
				$cnt_ret = $post['audit_qoh'][$key];
				$cnt_out = $post['audit_qoh'][$key];

				if ($post['e_type'] == 'new_mul') $cnt_ret = $cnt_out = 0;
				if ($post['e_type'] == 'return_mul') $cnt_out = 0;
				if ($post['e_type'] == 'out_mul') $cnt_ret = 0;

				$multidata[] = array(
					"e_lot" => $post['audit_lot'][$key],
					"e_expiration" => $post['audit_expiration'][$key],
					"e_old" => $post['audit_oldqoh'][$key],
					"e_count" => $post['audit_qoh'][$key],
					"e_returned" => $cnt_ret,
					"e_out" => $cnt_out
				);

				if ($post['e_type'] == 'out_mul') {
$this->lotM->setLotData($post['audit_lot'][$key], $post['e_drugId'], (int)$post['audit_oldqoh'][$key] - (int)$post['audit_qoh'][$key], strtotime($post['audit_expiration'][$key]));}
				else {
					
					$this->lotM->setLotData($post['audit_lot'][$key], $post['e_drugId'], (int)$post['audit_oldqoh'][$key] + (int)$post['audit_qoh'][$key], strtotime($post['audit_expiration'][$key]));}
			}

			// Update drug
			$arr = array('d_onHand' => $post['e_new']);
			$this->drugM->editDrug($post['e_drugId'], $arr);

			$type = "new";

			if ($post['e_type'] == "return_mul") $type = "return";
			if ($post['e_type'] == "out_mul") $type = "out";

			// Additional checking
			$add_check_list = array("e_invoice", "e_vendorId", "e_invoice", "e_numPacks", "e_costPack", "e_rx", "e_returned", "e_out");
			for ($i = 0; $i < count($add_check_list); $i++)
				if (@!$post[$add_check_list[$i]])
					$post[$add_check_list[$i]] = "";

			// Insert new records
			foreach ($multidata as $data)
			{
				$new = $data["e_count"] + $data["e_old"];
				if ($post['e_type'] == "out_mul") $new = $data["e_old"] - $data["e_count"];

				$this->db->query("INSERT INTO entry SET
					e_type=?, 		e_companyId=?, 	e_userId=?,
					e_drugId=?, 	e_date=?, 		e_invoice=?,
					e_vendorId=?, 	e_numPacks=?, 	e_costPack=?,
					e_lot=?, 		e_expiration=?, e_old=?,
					e_new=?, 		e_rx=?, 		e_returned=?,
					e_out=?, 		e_note=?,		e_transaction_group_id=?,e_ndc_type=?",
					array(
						$type, 								$cid, 								$uid,
						$post["e_drugId"], 					strtotime($post['e_date']), 		@$post["e_invoice"],
						@$post["e_vendorId"], 				@$post["e_numPacks"], 				@$post["e_costPack"],
						$data["e_lot"], 					@strtotime($data["e_expiration"]), 	$data["e_old"],
						$new, 								@$post["e_rx"], 					$data["e_returned"],
						$data["e_out"], 					$post["e_note"],$transaction_group_id,$e_ndc_type
					)
				);
			}

			// If lots is null
			if (count($multidata) == 0)
			{
				$this->db->query("INSERT INTO entry SET
					e_type=?, 		e_companyId=?, 	e_userId=?,
					e_drugId=?, 	e_date=?, 		e_invoice=?,
					e_vendorId=?, 	e_numPacks=?, 	e_costPack=?,
					e_lot=?, 		e_expiration=?, e_old=?,
					e_new=?, 		e_rx=?, 		e_returned=?,
					e_out=?, 		e_note=?,		e_transaction_group_id=?,e_ndc_type=?",
					array(
						$type, 								$cid, 								$uid,
						$post["e_drugId"], 					strtotime($post['e_date']), 		@$post["e_invoice"],
						@$post["e_vendorId"], 				@$post["e_numPacks"], 				@$post["e_costPack"],
						"", 								0, 									$post["e_old"],
						$post["e_new"], 					@$post["e_rx"], 					$post["e_returned"],
						$post["e_out"], 					$post["e_note"],$transaction_group_id , $e_ndc_type
					)
				);
			}

			// update all lots
			$this->lotM->recountAllLots();

		} 
		else if ($post['e_type'] == 'audit_mul')
		{
			 
			// Process each lot
			$multidata = array();
			foreach ($post['audit_lot'] as $key => $value)
			{
				$multidata[] = array(
					"e_lot" => $post['audit_lot'][$key],
					"e_expiration" => $post['audit_expiration'][$key],
					"e_count" => $post['audit_qoh'][$key]
				);

				$this->lotM->setLotData($post['audit_lot'][$key], $post['e_drugId'], $post['audit_qoh'][$key], strtotime($post['audit_expiration'][$key]));
			}

			// Update drug
			$arr = array('d_onHand' => $post['e_new']);
			$this->drugM->editDrug($post['e_drugId'], $arr);

			// Insert new audit record
			$data = array();

			foreach ($post as $k =>$v)
			{
				if ($k == "e_total" || $k == "e_operator" || $k == "audit_lot" || $k == "audit_expiration" || $k == "audit_qoh") continue;
				$data[$k] = strtoupper($v);
			}

			$data['e_companyId'] = $cid;
			$data['e_userId'] = $uid;
			$data['e_date'] = strtotime($post['e_date']);
			$data['e_type'] = "audit";

			for ($i = 0; $i < count($multidata); $i++)
			{
				$data['e_lot'] = $multidata[$i]["e_lot"];
				$data['e_expiration'] = strtotime($multidata[$i]["e_expiration"]);
				$this->db->insert($this->table, $data);
			}

			// update all lots
			$this->lotM->recountAllLots();
		} else if ($post['e_type'] == 'multi') {
           
			$multidata = array();
            foreach ($post['e_drugId'] as $key => $one)
			{
                $multidata[] = array(
                    'e_drugId' => $one,
                    'e_type' => 'out',
                    'e_companyId' => $cid,
                    'e_userId' => $uid,
                    'e_date' => strtotime($post['e_date']),
                    'e_rx' => strtoupper($post['e_rx']),
                    'e_old' => $post['e_old'][$key],
                    'e_out' => $post['e_out'][$key],
                    'e_lot' => $post['e_lot'][$key],
                    'e_total' => intval($post['e_old'][$key]) - intval($post['e_new'][$key]),
                    'e_new' => $post['e_new'][$key],
                    'e_note' => strtoupper($post['e_note'][$key])
                );
            }

            foreach($multidata as $one)
			{
				$total = $one["e_total"];

				unset($one["e_total"]);

				$this->db->insert($this->table, $one);
                $arr = array(
                    'd_onHand' => $one['e_new'],
                    //'d_modified' => time()
                );

                $this->drugM->editDrug($one['e_drugId'], $arr);
				if (@$one['e_lot']) $this->lotM->updateLog($one['e_lot'], $one['e_drugId'], $total, "-", @$one['e_expiration']);
			}

			// update all lots
			$this->lotM->recountAllLots();

            return 1;
        } else {
            
			//$data = $post;
            foreach ($post as $k =>$v) {
				if ($k == "e_total") continue;
				if ($k == "e_operator") continue;
				if ($k == "confirm_new") continue;

				if ($k !== 'add_new')
					$data[$k] = strtoupper($v);
			}

            if ($post['e_type'] == 'new' || $post['e_type'] == 'return')
				@$data['e_expiration'] = @strtotime($data['e_expiration']);

			// if out operation
			if ($post["e_type"] == "out")
			{
				// lot not found
				if (@$post['e_lot']) if ($this->drugM->checkDrugLotExists($post['e_drugId'], $post['e_lot']) == 0) return -1;
			}

			if ($post["e_type"] == "return")
			{
				$post['e_total'] = $post["e_returned"];
			}

            //echo $post['e_date'];
            $data['e_companyId'] = $cid;
            $data['e_userId'] = $uid;
            $data['e_date'] = strtotime($post['e_date']);

            $this->db->insert($this->table, $data);

			$arr = array(
                'd_onHand' => $post['e_new'],
                //'d_modified' => time()
            );

            $this->drugM->editDrug($post['e_drugId'], $arr);

			if (@$post['e_lot']) $this->lotM->updateLog($post['e_lot'], $post['e_drugId'], $post['e_total'], $post['e_operator'], @$data['e_expiration']);

			// update all lots
			$this->lotM->recountAllLots();

            return $this->db->insert_id();
        }
    }
	
 	public function editEntry($cid, $uid, $post,$transaction_id)
    {	
		
		$now = new DateTime();
		$transaction_group_id = 'tgid-'.time();
		
		$oldlots_array = $this->lotM->getlotnamesByIds($post['e_total_lots']);
		$old_entries   = $this->Edit_transactionm->getentrysbyentryIds($transaction_id);
		 
		$this->load->model('Edit_transactionm');
		$e_ndc_type           = 0; // 0 for single NDC and 1 for multiple NDC
		//replace - with ,	
		$transaction_id 	= str_replace('-',',',$transaction_id);	
		$parent_id		 	= $post['e_parent_id'];	
		
		if ($post['e_type'] == 'multi_out')
		{
			$e_ndc_type           = 1; //for multiple NDC	
			$drugs = array();
			// Each drugs
			foreach ($post["e_drugId"] as $key => $value)
			{
				$drugs[$value] = array('drugInfo' => array(
					'e_out' => $post["e_out"][$key],
					'e_old' => $post["e_old"][$key],
					'e_new' => $post["e_new"][$key],
					'e_note' =>$post["e_note"][$key],
					'e_date' =>strtotime($post["e_date"]),
					'e_rx' => $post["e_rx"]
				), 'drugLots' => array());

				// Each lots
				if (@$post["out_lot_".$value])
				for ($i = 0; $i < count($post["out_lot_".$value]); $i++)
				{
					array_push($drugs[$value]['drugLots'], array(
						'e_lot' => $post["out_lot_".$value][$i],'e_original_lot' => $post["original_lot_".$value][$i],
						'e_expiration' => $post["out_expiration_".$value][$i],
						'e_old' => (int)$post["out_oldqoh_".$value][$i],
						'e_new' => (int)$post["out_qoh_".$value][$i],
						'e_out' => (int)$post["out_qoh_".$value][$i]
					));
				}
				
						
			$arr = array('d_onHand' => $post['e_new'][$key]);
			$this->drugM->editDrug($value, $arr);
			}
			foreach ($drugs as $key => $value)
			{
				
				//if lot tracking is disabled for any drug
				if(count($value['drugLots']) == 0)	
				{
			
				 	$this->db->query("INSERT INTO entry SET
					e_type=?, 		e_companyId=?, 	e_userId=?,
					e_drugId=?, 	e_date=?, 		e_invoice=?,
					e_vendorId=?, 	e_numPacks=?, 	e_costPack=?,
					e_lot=?, 		e_expiration=?, e_old=?,
					e_new=?, 		e_rx=?, 		e_returned=?,
					e_out=?, 		e_note=?,e_last_edit_date=?,e_original_entry_id=?,e_transaction_group_id=?,e_ndc_type=?",
						array(
							'edit', 							$cid, 											$uid,
							$key, 								$value['drugInfo']['e_date'], 						'',
							'', 								'', 												'',
							'', 								'', 												'',
							'', 								$value['drugInfo']['e_rx'], 						0,
							$value['drugInfo']['e_out'], 	$value['drugInfo']["e_note"],$now->getTimestamp(),$parent_id,$transaction_group_id,$e_ndc_type
						)
					);
				}
				else
				{	
					for ($i = 0; $i < count($value['drugLots']); $i++)
					{	
						//match the previous lots with new lots if a lot is replaced or removed then update previous lot data
						$lotCount = 0;
										
						if($value['drugLots'][$i]['e_original_lot'] == '' || $value['drugLots'][$i]['e_lot']== $value['drugLots'][$i]['e_original_lot'])
						{			 
							 //$post['original_lot'][$key] == '' means a new lot has been added
							 if($value['drugLots'][$i]['e_original_lot'] == '')
							 {
							  $lotCount = (int)$value['drugLots'][$i]['e_old'] - (int)$value['drugLots'][$i]['e_out'];
							 }
							 
							 foreach($old_entries as $entry_key => $entry)
							 {
								 //check old lot out qty and new lot out qty and update the lot qty accordingly
								 if($entry->e_lot == $value['drugLots'][$i]['e_lot'])
								 {
								   if($entry->e_out >= $value['drugLots'][$i]['e_out'])
								   $lotCount = (int)$value['drugLots'][$i]['e_old'] + ((int)$entry->e_out - (int)$value['drugLots'][$i]['e_out']);
								   else
								   $lotCount = (int)$value['drugLots'][$i]['e_old'] - ((int)$value['drugLots'][$i]['e_out'] - (int)$entry->e_out);
								   continue;
								 }						
							 }	
											 
						}
						else // if not in array that means either lot is changed 
						{ 
							foreach($old_entries as $entry_key => $entry)
							{
								//check old lot out qty and new lot out qty and update the lot qty accordingly
								if($entry->e_lot == $value['drugLots'][$i]['e_original_lot'])
								{
								$this->lotM->reverseLotData($entry->e_lot,$key,(int)$entry->e_out);
								$lotCount = (int)$value['drugLots'][$i]['e_old'] - (int)$value['drugLots'][$i]['e_out'];
								continue;
								}						
							}	
						
						}	
					
					$pos = array_search($value['drugLots'][$i]['e_lot'], $oldlots_array);
					unset($oldlots_array[$pos]);
					
						
					$this->lotM->setLotData($value['drugLots'][$i]['e_lot'], $key, $lotCount, strtotime($value['drugLots'][$i]['e_expiration']));
	
	
					$new = 0;
					$old = 0;
					if ($post['e_type'] == "multi_out")
					{
					 foreach($old_entries as $entrykey => $entry)
					 {
						 //check old lot out qty and new lot out qty
						 if($entry->e_lot == $value['drugLots'][$i]['e_lot'])
						 {
						   $old = $entry->e_new;
						   if($entry->e_out > $value['drugLots'][$i]["e_out"])
						   $new = $entry->e_new + ((int)$entry->e_out - (int)$value['drugLots'][$i]["e_out"]);
						   else
						   $new = $entry->e_new - ((int)$value['drugLots'][$i]["e_out"] - (int)$entry->e_out);
						 }
					 }	 	
					}
					
						$this->db->query("INSERT INTO entry SET
						e_type=?, 		e_companyId=?, 	e_userId=?,
						e_drugId=?, 	e_date=?, 		e_invoice=?,
						e_vendorId=?, 	e_numPacks=?, 	e_costPack=?,
						e_lot=?, 		e_expiration=?, e_old=?,
						e_new=?, 		e_rx=?, 		e_returned=?,
						e_out=?, 		e_note=?,e_last_edit_date=?,e_original_entry_id=?,e_transaction_group_id=?,e_ndc_type=?",
							array(
								"edit", 							$cid, 											$uid,
								$key, 								$value['drugInfo']['e_date'], 						"",
								"", 								"", 												"",
								$value['drugLots'][$i]['e_lot'], 	@strtotime($value['drugLots'][$i]['e_expiration']), $value['drugLots'][$i]['e_old'],
								$value['drugLots'][$i]['e_new'], 	$value['drugInfo']['e_rx'], 						0,
								$value['drugLots'][$i]["e_out"], 	$value['drugInfo']["e_note"],$now->getTimestamp(),$parent_id,$transaction_group_id,$e_ndc_type
							)
						);
					  }
				}
			}
			
			// if any lot left out in array $oldlots_array that means that item has been deleted so need to revert that lot data
			
			foreach($oldlots_array as $old_lots_key => $old_lot)
			{
				foreach($old_entries as $entrykey => $entry)
				{
				//check old lot out qty and new lot out qty
					if($entry->e_lot == $old_lot && $entry->e_lot != '')
					{
					$this->lotM->reverseLotData($entry->e_lot,$entry->e_drugId,(int)$entry->e_out,'out');
					}
				}
			}
			
			//update status of edited rows to edited
			$this->Edit_transactionm->update_entry($transaction_id);

		} 
		else if ($post['e_type'] == 'out_mul')
		{
			// Process each lot
			$multidata = array();
			if (@$post['audit_lot']) 
			foreach (@$post['audit_lot'] as $key => $value)
			{
				$cnt_ret = $post['audit_qoh'][$key];
				$cnt_out = $post['audit_qoh'][$key];

				if ($post['e_type'] == 'new_mul') $cnt_ret = $cnt_out = 0;
				if ($post['e_type'] == 'return_mul') $cnt_out = 0;
				if ($post['e_type'] == 'out_mul') $cnt_ret = 0;

				$multidata[] = array("e_lot" => $post['audit_lot'][$key],"e_expiration" => $post['audit_expiration'][$key],
				"e_old" => $post['audit_oldqoh'][$key],"e_count" => $post['audit_qoh'][$key],"e_returned" => $cnt_ret,"e_out" => $cnt_out);
				
				//match the previous lots with new lots if a lot is replaced or removed then update previous lot data
				$lotCount = 0;
								
				if($post['original_lot'][$key] == '' || $post['audit_lot'][$key]== $post['original_lot'][$key] )
				{			 
					 //$post['original_lot'][$key] == '' means a new lot has been added
					 if($post['original_lot'][$key] == '')
					 {
					  $lotCount = (int)$post['audit_oldqoh'][$key] - (int)$post['audit_qoh'][$key];
					 }
					 
					 foreach($old_entries as $entry_key => $entry)
					 {
						 //check old lot out qty and new lot out qty and update the lot qty accordingly
						 if($entry->e_lot == $post['audit_lot'][$key])
						 {
						   if($entry->e_out >= $post['audit_qoh'][$key])
						   $lotCount = (int)$post['audit_oldqoh'][$key] + ((int)$entry->e_out - (int)$post['audit_qoh'][$key]);
						   else
						   $lotCount = (int)$post['audit_oldqoh'][$key] - ((int)$post['audit_qoh'][$key] - (int)$entry->e_out);
						   continue;
						 }						
					 }	
					 				 
				}
				else // if not in array that means either lot is changed or deleted
				{ 
					foreach($old_entries as $entry_key => $entry)
					{
						//check old lot out qty and new lot out qty and update the lot qty accordingly
						if($entry->e_lot == $post['original_lot'][$key])
						{
						$this->lotM->reverseLotData($entry->e_lot,$post['e_drugId'],(int)$entry->e_out);
						$lotCount = (int)$post['audit_oldqoh'][$key] - (int)$post['audit_qoh'][$key];
						continue;
						}						
					}	
				
				}
				 $pos = array_search($post['audit_lot'][$key], $oldlots_array);
				 unset($oldlots_array[$pos]);
				 
				 $this->lotM->setLotData($post['audit_lot'][$key],$post['e_drugId'],$lotCount,strtotime($post['audit_expiration'][$key]));
			}

			// Update drug
			$arr = array('d_onHand' => $post['e_new']);
			$this->drugM->editDrug($post['e_drugId'], $arr);
			
			// Additional checking
			$add_check_list = array("e_invoice", "e_vendorId", "e_invoice", "e_numPacks", "e_costPack", "e_rx", "e_returned", "e_out");
			for ($i = 0; $i < count($add_check_list); $i++)
				if (@!$post[$add_check_list[$i]])
					$post[$add_check_list[$i]] = "";

			// Insert new records
			foreach ($multidata as $data)
			{
				$new = 0;
				$old = 0;
				if ($post['e_type'] == "out_mul")
				{
				 foreach($old_entries as $key => $entry)
				 {
					 //check old lot out qty and new lot out qty
					 if($entry->e_lot == $data["e_lot"])
					 {
					   $old = $entry->e_new;
					   if($entry->e_out > $data["e_out"])
					   $new = $entry->e_new + ((int)$entry->e_out - (int)$data["e_out"]);
					   else
					   $new = $entry->e_new - ((int)$data["e_out"] - (int)$entry->e_out);
					 }
				 }	 	
				 //$new = $data["e_old"] - $data["e_count"];
				}
				
				$this->db->query("INSERT INTO entry SET
					e_type=?, 		e_companyId=?, 	e_userId=?,
					e_drugId=?, 	e_date=?, 		e_invoice=?,
					e_vendorId=?, 	e_numPacks=?, 	e_costPack=?,
					e_lot=?, 		e_expiration=?, e_old=?,
					e_new=?, 		e_rx=?, 		e_returned=?,
					e_out=?, 		e_note=?,		e_last_edit_date=?,e_original_entry_id=?,e_transaction_group_id=?,e_ndc_type=?",
					array(
						"edit", 							$cid, 								$uid,
						$post["e_drugId"], 					strtotime(str_replace('-','/',$post['e_date'])), 		@$post["e_invoice"],
						@$post["e_vendorId"], 				@$post["e_numPacks"], 				@$post["e_costPack"],
						$data["e_lot"], 					@strtotime($data["e_expiration"]), 	$data["e_old"],
						$new, 								@$post["e_rx"], 					$data["e_returned"],
						$data["e_out"], 					$post["e_note"],$now->getTimestamp(),$parent_id,$transaction_group_id,$e_ndc_type
					)
				);
			}
			// If lots is null
			if (count($multidata) == 0)
			{
				$this->db->query("INSERT INTO entry SET
					e_type=?, 		e_companyId=?, 	e_userId=?,
					e_drugId=?, 	e_date=?, 		e_invoice=?,
					e_vendorId=?, 	e_numPacks=?, 	e_costPack=?,
					e_lot=?, 		e_expiration=?, e_old=?,
					e_new=?, 		e_rx=?, 		e_returned=?,
					e_out=?, 		e_note=?,		e_last_edit_date=?,e_original_entry_id=?,e_transaction_group_id=?,e_ndc_type=?",
					array(
						"edit", 								$cid, 								$uid,
						$post["e_drugId"], 					strtotime(str_replace('-','/',$post['e_date'])), 		@$post["e_invoice"],
						@$post["e_vendorId"], 				@$post["e_numPacks"], 				@$post["e_costPack"],
						"", 								0, 									$post["e_old"],
						$post["e_new"], 					@$post["e_rx"], 					$post["e_returned"],
						$post["e_out"], 					$post["e_note"],$now->getTimestamp(),$parent_id,$transaction_group_id,$e_ndc_type
					)
				);
			}
			
			// if any lot left out in array $oldlots_array that means that item has been deleted so need to revert that lot data
			
			foreach($oldlots_array as $old_lots_key => $old_lot)
			{
				foreach($old_entries as $entrykey => $entry)
				{
				//check old lot out qty and new lot out qty
					if($entry->e_lot == $old_lot)
					{
					$this->lotM->reverseLotData($entry->e_lot,$entry->e_drugId,(int)$entry->e_out);
					}
				}
			}
			
			//update status of edited rows to edited
			$this->Edit_transactionm->update_entry($transaction_id);
			// update all lots
			$this->lotM->recountAllLots();
		}
		  		
    }
	
	
	public function editEntryIn($cid, $uid, $post,$transaction_id)
    {	
		
		$now = new DateTime();
		$transaction_group_id = 'tgid-'.time();
		
		$oldlots_array = $this->lotM->getlotnamesByIds($post['e_total_lots']);
		$old_entries   = $this->Edit_transactionm->getentrysbyentryIds($transaction_id);
		
		$this->load->model('Edit_transactionm');
		
		//replace - with ,	
		$transaction_id 	= str_replace('-',',',$transaction_id);	
		$parent_id		 	= $post['e_parent_id'];	

	   if ($post['e_type'] == 'new_mul' || $post['e_type'] == 'return_mul' || $post['e_type'] == 'out_mul')
		{

//echo "e_type:".$post['e_type'];
			// Process each lot
			$multidata = array();
			if(@$post['audit_lot']) 
			foreach (@$post['audit_lot'] as $key => $value)
			{
				//echo "value:".$value;
				$cnt_ret = $post['audit_qoh'][$key];
				$cnt_out = $post['audit_qoh'][$key];

				if ($post['e_type'] == 'new_mul') $cnt_ret = $cnt_out = 0;
				if ($post['e_type'] == 'return_mul') $cnt_out = 0;
				if ($post['e_type'] == 'out_mul') $cnt_ret = 0;

				$multidata[] = array(
					"e_lot" => $post['audit_lot'][$key],
					"e_expiration" => $post['audit_expiration'][$key],
					"e_old" => $post['audit_oldqoh'][$key],
					"e_count" => $post['audit_qoh'][$key],
					"e_returned" => $cnt_ret,
					"e_out" => $cnt_out
				);


				//match the previous lots with new lots if a lot is replaced or removed then update previous lot data
				$lotCount = 0;
								
				if($post['original_lot'][$key] == '' || $post['audit_lot'][$key]== $post['original_lot'][$key] )
				{			 
					 //$post['original_lot'][$key] == '' means a new lot has been added
					 if($post['original_lot'][$key] == '')
					 {
					  $lotCount = (int)$post['audit_oldqoh'][$key] + (int)$post['audit_qoh'][$key];
					 }
					 
					 foreach($old_entries as $entry_key => $entry)
					 {
						 //check old lot out qty and new lot out qty and update the lot qty accordingly
						 if($entry->e_lot == $post['audit_lot'][$key])
						 {
						   if($post['original_lot'][$key] >= $post['audit_qoh'][$key])
						   $lotCount = (int)$post['audit_oldqoh'][$key] - ((int)$post['e_original_out'][$key] - (int)$post['audit_qoh'][$key]);
						   else
						   $lotCount = (int)$post['audit_oldqoh'][$key] + ((int)$post['audit_qoh'][$key] - (int)$post['e_original_out'][$key]);
						   continue;
						 }						
					 }	
					 				 
				}
				else // if not in array that means either lot is changed 
				{ 
					foreach($old_entries as $entry_key => $entry)
					{
						//check old lot out qty and new lot out qty and update the lot qty accordingly
						if($entry->e_lot == $post['original_lot'][$key])
						{
						$this->lotM->reverseEntryInLotData($entry->e_lot,$post['e_drugId'],(int)$post['e_original_out'][$key]);
						$pos = array_search($post['audit_lot'][$key], $oldlots_array);
				 		unset($oldlots_array[$pos]);
						continue;
						}						
					}	
				
				}
				 $pos = array_search($post['audit_lot'][$key], $oldlots_array);
				 unset($oldlots_array[$pos]);
				 $this->lotM->setLotData($post['audit_lot'][$key],$post['e_drugId'],$lotCount,strtotime($post['audit_expiration'][$key]));
			}

			// Update drug
			$arr = array('d_onHand' => $post['e_new']);
			$this->drugM->editDrug($post['e_drugId'], $arr);

			$type = "new";

			if ($post['e_type'] == "return_mul") $type = "return";
			if ($post['e_type'] == "out_mul") $type = "out";

			// Additional checking
			$add_check_list = array("e_invoice", "e_vendorId", "e_invoice", "e_numPacks", "e_costPack", "e_rx", "e_returned", "e_out");
			for ($i = 0; $i < count($add_check_list); $i++)
				if (@!$post[$add_check_list[$i]])
					$post[$add_check_list[$i]] = "";

			// Insert new records
			foreach ($multidata as $data)
			{
				$new = $data["e_count"] + $data["e_old"];
			
				$this->db->query("INSERT INTO entry SET
					e_type=?, 		e_companyId=?, 	e_userId=?,
					e_drugId=?, 	e_date=?, 		e_invoice=?,
					e_vendorId=?, 	e_numPacks=?, 	e_costPack=?,
					e_lot=?, 		e_expiration=?, e_old=?,
					e_new=?, 		e_rx=?, 		e_returned=?,
					e_out=?, 		e_note=?,		e_last_edit_date=?,e_original_entry_id=?,e_transaction_group_id=?,e_ndc_type=?",
					array(
						'edit', 								$cid, 								$uid,
						$post["e_drugId"], 					strtotime(str_replace('-','/',$post['e_date'])), 		@$post["e_invoice"],
						@$post["e_vendorId"], 				@$post["e_numPacks"], 				@$post["e_costPack"],
						$data["e_lot"], 					@strtotime($data["e_expiration"]), 	$data["e_old"],
						$new, 								@$post["e_rx"], 					$data["e_returned"],
						$data["e_out"], 					$post["e_note"],$now->getTimestamp(),$parent_id,$transaction_group_id,2
					)
				);
			}

			// If lots is null
			if (count($multidata) == 0)
			{
				$this->db->query("INSERT INTO entry SET
					e_type=?, 		e_companyId=?, 	e_userId=?,
					e_drugId=?, 	e_date=?, 		e_invoice=?,
					e_vendorId=?, 	e_numPacks=?, 	e_costPack=?,
					e_lot=?, 		e_expiration=?, e_old=?,
					e_new=?, 		e_rx=?, 		e_returned=?,
					e_out=?, 		e_note=?,		e_last_edit_date=?,e_original_entry_id=?,e_transaction_group_id=?,e_ndc_type=?",
					array(
						'edit', 								$cid, 								$uid,
						$post["e_drugId"], 					strtotime(str_replace('-','/',$post['e_date'])), 		@$post["e_invoice"],
						@$post["e_vendorId"], 				@$post["e_numPacks"], 				@$post["e_costPack"],
						$data["e_lot"], 					@strtotime($data["e_expiration"]), 	$data["e_old"],
						$new, 								@$post["e_rx"], 					$data["e_returned"],
						$data["e_out"], 					$post["e_note"],$now->getTimestamp(),$parent_id,$transaction_group_id,2
					)
				);
			}
			
			// if any lot left out in array $oldlots_array that means that item has been deleted so need to revert that lot data
			
			foreach($oldlots_array as $old_lots_key => $old_lot)
			{
				foreach($old_entries as $entrykey => $entry)
				{
				
				//check old lot out qty and new lot out qty
					if($entry->e_lot == $old_lot)
					{
					$this->lotM->reverseEntryInLotData($entry->e_lot,$entry->e_drugId,((int)$entry->e_new - (int)$entry->e_old));
					}
				}
			}
			
			//update status of edited rows to edited
			$this->Edit_transactionm->update_entry($transaction_id);
			// update all lots
			$this->lotM->recountAllLots();
          }
		} 
	
	public function insertEntry($arrayValues)
	{
		$this->db->query("INSERT INTO entry SET
		e_type=?, 		e_companyId=?, 	e_userId=?,
		e_drugId=?, 	e_date=?, 		e_invoice=?,
		e_vendorId=?, 	e_numPacks=?, 	e_costPack=?,
		e_lot=?, 		e_expiration=?, e_old=?,
		e_new=?, 		e_rx=?, 		e_returned=?,
		e_out=?, 		e_note=?,		e_transaction_group_id=?,e_ndc_type=?",
		array($arrayValues[0],$arrayValues[1],$arrayValues[2],$arrayValues[3],$arrayValues[4],$arrayValues[5],$arrayValues[6],
		$arrayValues[7],$arrayValues[8],$arrayValues[9],$arrayValues[10],$arrayValues[11],$arrayValues[12],$arrayValues[13],
		$arrayValues[14],$arrayValues[15],$arrayValues[16],$arrayValues[17],$arrayValues[18]));	
	}
		
		
}