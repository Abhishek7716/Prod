<?php 
namespace App\Models\Cpan;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Download_report_model extends Model
{
    // public $t1 = 'subsequent_contribution_details';
    // public $t2 = 'users';
    // public $t3 = 'pg_transaction_details';
    // public $table2 = 'contribution_details_cycle';

  	public $subsequent_contribution_details = 'subsequent_contribution_details';
    public $users = 'users';
    public $pg_transaction_details = 'pg_transaction_details';
    public $contribution_details_cycle = 'contribution_details_cycle';
    public $user_address_details = 'user_address_details';
    public $table = 'master_contribution_details AS mc';
    public $master_contribution_details = 'master_contribution_details';



    function get_subsequent_contribution_csv($from_date = null, $to_date = null,$report_type = null){
      // return  DB::table('subsequent_contribution_details')->get()->toArray();
    
    
   
      if($report_type=='ALL')
      $report_type=null;
      
      $query = DB::table($this->subsequent_contribution_details)
      ->join($this->users, $this->subsequent_contribution_details . '.pran', '=', $this->users . '.pran')
      ->join($this->pg_transaction_details ,$this->subsequent_contribution_details . '.sub_contr_id', '=', $this->pg_transaction_details . '.contr_ref_id')
      ->leftJoin($this->user_address_details, $this->subsequent_contribution_details . '.pran', '=', $this->user_address_details . '.pran')
       ->select($this->subsequent_contribution_details . '.*', $this->users . '.*', $this->pg_transaction_details . '.created_on as cre', $this->pg_transaction_details . '.pran as p', $this->pg_transaction_details . '.payment_status as payment_status', $this->user_address_details . '.*')
   ->orderBy('cre', 'asc')
   ->groupBy($this->pg_transaction_details . '.payment_trans_id') ;
       
    
    
      if ($from_date && $to_date) {
        $query->whereBetween($this->pg_transaction_details . '.created_on', [$from_date, $to_date]);
      }
      if($report_type){
        $query->where($this->pg_transaction_details . '.payment_status', $report_type);
      }
       $query->where($this->pg_transaction_details . '.contribution_type', 'SUBSEQUENT');
      return $query->get()->toArray();
    }

// function get_sip_contribution_csv($from_date = null, $to_date = null,$report_type = null){
//  enable_query_log();
//         if($report_type=='ALL')   $report_type=null;
//         $query = DB::table($this->contribution_details_cycle)
//    			 ->leftjoin('pg_transaction_details AS td', 'mcy.contr_id', '=', 'td.contr_ref_id')
//    			 ->join('users AS us', 'mcy.pran', '=', 'us.pran')
//    			 ->join('user_address_details AS uadd', 'mcy.pran', '=', 'uadd.pran')
//    			 ->select('mcy.*', 'us.*', 'uadd.*', 'td.created_on as cre', 'td.payment_status','td.payment_trans_id as pg_payment_trans_id',);
// 			if ($from_date && $to_date) {
// 		  		  $query->whereBetween('mcy.created_on', [$from_date, $to_date]);
// 				}
// 			if ($report_type) {
// 		 	   $query->where('mcy.mandate_status', $report_type);
// 			}
           
//             $query->where('uadd.address_type', "P");
// 			$query->where('td.contribution_type', "SIP");
// 			$query->orderBy('cre', 'asc');
//   //enable_query_log(); 
//          //$query->get()->all();       
//          //print_last_query(true);
// 	   return $query->get()->toArray();
// }

function get_sip_list($from_date = null, $to_date = null,$report_type = null){
  if($report_type=='ALL')
   $report_type=null;
   $query = DB::table($this->table);
   $query->join('users AS us', 'mc.pran', '=', 'us.pran');
   $query->select('mc.*', 'us.*');        
   if ($from_date && $to_date) {
       $query->whereBetween('mc.start_date', [$from_date, $to_date]);
   }
   if ($report_type) {
       $query->where('mc.sip_status', $report_type);
   }
   $query->where('mc.sip_status', '!=' , 'P');
   $query->whereNotNull('mc.umrn');
   return $query->get()->toArray();


 }

 function get_sip_contribution_csv($from_date = null, $to_date = null,$report_type = null){
      // return  DB::table('subsequent_contribution_details')->get()->toArray();
      if($report_type=='ALL')
      $report_type=null;
 
 // enable_query_log();
      
      $query = DB::table($this->contribution_details_cycle)
      ->join($this->users, $this->contribution_details_cycle . '.pran', '=', $this->users . '.pran')
      ->join($this->pg_transaction_details ,$this->contribution_details_cycle . '.contr_id', '=', $this->pg_transaction_details . '.contr_ref_id')
      ->leftJoin($this->user_address_details, $this->contribution_details_cycle . '.pran', '=', $this->user_address_details . '.pran')
      ->join($this->master_contribution_details, $this->contribution_details_cycle . '.mast_sip_contr_id', '=', $this->master_contribution_details . '.mast_sip_contr_id')
       ->select($this->contribution_details_cycle . '.*', $this->users . '.*', $this->pg_transaction_details . '.created_on as cre', $this->pg_transaction_details . '.pran as p', $this->pg_transaction_details . '.payment_status as payment_status', $this->user_address_details . '.*',$this->master_contribution_details . '.sip_frequency')
   ->orderBy('cre', 'asc')
   ->groupBy($this->pg_transaction_details . '.payment_trans_id') ;
 
 
    
     
    
      if ($from_date && $to_date) {
        $query->whereBetween($this->pg_transaction_details . '.created_on', [$from_date, $to_date]);
      }
      if($report_type){
        $query->where($this->pg_transaction_details . '.payment_status', $report_type);
      }
       $query->where($this->pg_transaction_details . '.contribution_type', 'SIP');
// $query->get();
 //print_last_query();
      return $query->get()->toArray();
    }

 


	 function get_POP_Charges_csv($from_date = null, $to_date = null){
      // return  DB::table('subsequent_contribution_details')->get()->toArray();
     
     //for subsequent  -- (start)
      $query_subsequent = DB::table($this->subsequent_contribution_details)
    	->join('pg_transaction_details', 'pg_transaction_details.payment_trans_id', '=', "{$this->subsequent_contribution_details}.payment_trans_id")
    	->join('users', 'users.pran', '=', "{$this->subsequent_contribution_details}.pran")
    	->join('user_address_details', 'user_address_details.pran', '=', "{$this->subsequent_contribution_details}.pran")
    	->select(
        'pg_transaction_details.created_on', 
      	'subsequent_contribution_details.pran',
        'users.cra_account',
      	'subsequent_contribution_details.tier_type',
      	'pg_transaction_details.contribution_type',
      	'subsequent_contribution_details.total_amount_received',
      	'subsequent_contribution_details.contribution_amount',
      	'subsequent_contribution_details.contribution_fees',
      	'subsequent_contribution_details.igst_amt',
      	'subsequent_contribution_details.cgst_amt',
      	'subsequent_contribution_details.sgst_amt',
      	'subsequent_contribution_details.contr_cra_trans_id',
      	'subsequent_contribution_details.contr_receipt_no',
        'pg_transaction_details.payment_trans_id',
        'users.custom_state',
        'user_address_details.address_state'
      
   	 	)
   		 ->where('pg_transaction_details.payment_status', 'A')
        ->orderBy('created_on', 'asc')
   ->groupBy($this->pg_transaction_details . '.payment_trans_id') ;
       
    	
      if ($from_date && $to_date) {
        $query_subsequent->whereBetween($this->subsequent_contribution_details . '.created_on', [$from_date, $to_date]);
      }
      $result_subsequent= $query_subsequent->get()->toArray();
//      //for subsequent  -- (end)
     
     
     // return $result_subsequent;
     
     
     
      $query_sip = DB::table($this->contribution_details_cycle)
    	->join('pg_transaction_details', 'pg_transaction_details.payment_trans_id', '=', "{$this->contribution_details_cycle}.payment_trans_id")
    	->join('users', 'users.pran', '=', "{$this->contribution_details_cycle}.pran")
    	->join('user_address_details', 'user_address_details.pran', '=', "{$this->contribution_details_cycle}.pran")

    	->select(
        'pg_transaction_details.created_on', 
      	'contribution_details_cycle.pran',
        'users.cra_account',
      	'contribution_details_cycle.tier_type',
      	'pg_transaction_details.contribution_type',
      	'contribution_details_cycle.total_amount_received',
      	'contribution_details_cycle.sip_amount',
      	'contribution_details_cycle.contr_cra_trans_id',
      
      	'contribution_details_cycle.contribution_fees',
      	'contribution_details_cycle.igst_amt',
      	'contribution_details_cycle.cgst_amt',
      	'contribution_details_cycle.sgst_amt',
      	'contribution_details_cycle.contr_receipt_no',
        'pg_transaction_details.payment_trans_id',
        'users.custom_state',
        'user_address_details.address_state'
      
   	 	)
   		 ->where('pg_transaction_details.payment_status', 'A')
        ->orderBy('created_on', 'asc')
   ->groupBy($this->pg_transaction_details . '.payment_trans_id');
       
    	
      if ($from_date && $to_date) {
        $query_sip->whereBetween($this->pg_transaction_details . '.created_on', [$from_date, $to_date]);
      }
        $result_sip= $query_sip->get()->toArray();
     // return $result_sip;
     
     
     $final_result = array_merge($result_sip, $result_subsequent);
     
     
     return $final_result;
    
    }
}
?>