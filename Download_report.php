<?php

namespace App\Http\Controllers\cpan;

use Crypt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Cpan\Download_report_model;


class Download_report extends Controller
{


    protected $download_report_model;

    public function __construct(Download_report_model $download_report_model){
        $this->download_report_model = $download_report_model;
    }

    function index()
    {
    $download_report = checkUserPermissions('download_report');
    if(!$download_report){
    abort(403, 'Unauthorized action.');
    }
    
    
    
        return view('cpan.download_report');
    }

    function download_csv(Request $request)
    {
    $filename=storage_path('custom_files/application.csv') ;


        $from_date = '';
        $to_date = '';
        $contribution_type = '';
        $report_type = '';

        if ($request->input('from_date')) {
            $from_date = Carbon::createFromFormat('d-m-Y', $request->input('from_date'))->startOfDay()->timestamp;
        }
        // dd($request->input('to_date'));
        if ($request->input('to_date')) {
            $to_date = Carbon::createFromFormat('d-m-Y', $request->input('to_date'))->endOfDay()->timestamp;
        }





        if ($request->input('contribution_type')) {
            $contribution_type = $request->input('contribution_type');
        }
        if ($request->input('report_type')) {
            $report_type = $request->input('report_type');
        }
    
    
    
    // return $report_type;die();

		$contribution_type_name = '';
        if($contribution_type == 'SIPC'){
        $contribution_type_name = 'SIP Contribution';
        // $data = $this->corporate_mapping_list_model->get_corp_mapping_data_between($from_date, $to_date);
        // $filename = "application.csv";

        $data = $this->download_report_model->get_sip_contribution_csv($from_date,$to_date,$report_type);  
        
        // echo $request->input('from_date')."_".$request->input('to_date');
        // die();

        
        
        // dd($data);
        // print_r_custom($data);die();
      
        $handle = fopen($filename, 'w+');
        fputcsv(
            $handle,
            array(
            "Merchant ID",
            "Request ID",
            "Date of Transaction",
            "Customer Name",
            "Amount",
            "Requested Action",
            "Status",
            "BankName",
            "Requested Amount", //
            "Discount",
            "Additional Charges",
            "Transaction Id",
            "CRA Transaction Id",
            
            "Payment Gateway",
            "Bank Reference No",
            // "Last Name",
            "Customer Email",
            "Settlement Date",
            "Settlement UTR",
            "Customer Phone",
            "City",
            "Country",
            "Zipcode",
            "State",
            "PRAN", 
            "Net Amount after deducting POP charges",   //
            "Tier type",
            "CRA",
            "Contribution receipt no",
            "Source",
            "Sip Frequency"
            )
        );
        foreach ($data as $row) {
            $full_name = "";
            if(decrypted($row->full_name) == "")
                $full_name = decrypted($row->fname).' '.decrypted($row->mname).' '.decrypted($row->lname);
            else   
                $full_name = decrypted($row->full_name);

		$status='';
        if($row->payment_status == 'A')
        $status='Success';
       	else if($row->payment_status == 'F')
        $status='Failed';
      	else if($row->payment_status == 'P')
        $status='Pending';
        else
		$status=$row->payment_status;
        
        
            $contr_receipt_no ='';
            if($row->contr_receipt_no)
                $contr_receipt_no = '="' . $row->contr_receipt_no . '"';
            
            $mobile_no_dec=decrypted($row->mobile_no);
        	$mobile_no='="' . $mobile_no_dec . '"';
        
        
            $pran = '="' . $row->p . '"';
        
        
        
            fputcsv(
                $handle,
                array(
                   		 '',
                        '',
                        (date('d/m/Y',$row->cre)),
                    	($full_name),
                        ($row->sip_amount),
                        ('SIP'),
                        ($status),
                        (''),   //bank
                        ($row->sip_amount),  
                
                        // ($row->total_amount_received),
                        ('N/A'),
                        ('N/A'),
                        ($row->payment_trans_id) , 
                        ($row->contr_cra_trans_id) , 
                
                        ('JIO'),
                        (''),   //Bank Reference No,
                        // (decrypted($row->lname)),  //Last Name,
                        (decrypted($row->email)),
                        (''),   //Settlement Date,
                        (''),   //Settlement UTR,
                        $mobile_no, //Customer Phone,
                        (''),   //City,
                        (''),   //Country
                        (''),   //Zipcode,
                        (''),   //State,
                        ($pran),
                        ($row->total_amount_received),
                        // ($row->sip_amount),  //
                        ($row->tier_type), 
                        ($row->cra_account), 
                       $contr_receipt_no,
                       ($row->vendor_uid == NULL) ? $row->vendor_uid : '',
                       ($row->sip_frequency), 

    
                )
            );
        }
        fclose($handle);
        $headers = array('Content-Type' => 'text/csv', 'X-Frame-Options' => 'SAMEORIGIN');
        $action = 'SIP Contribution Downloaded';
        $action_desc = 'Contribution Type : ' . $contribution_type_name . ' Date Range : ' . $from_date . ' to ' . $to_date . ' Reports Type : ' . $report_type;
        insert_admin_action_log($action, $action_desc);
        return response()->download($filename, time().'_SIP_Contribution.csv', $headers);


        } elseif($contribution_type == 'SL'){
        $contribution_type_name = 'SIP List';
        $data = $this->download_report_model->get_sip_list($from_date,$to_date,$report_type); 

           $handle = fopen($filename, 'w+');
        fputcsv(
            $handle,
            array(
            "SIP ID",
            "Tier Type",
            "Full Name",
            "Pran",
            "Pan No",
            "Email Id",
            "Mobil NUmber",
            "Sip Start Date",
            "SIP Auto Debit Date",
            "Sip End Date", //
            "Sip Amouunt",
            "Source",
            "Sip Status"
            
            )
        );
         foreach ($data as $row) {
            $full_name = "";
            if(decrypted($row->full_name) == "")
                $full_name = decrypted($row->fname).' '.decrypted($row->mname).' '.decrypted($row->lname);
            else   
                $full_name = decrypted($row->full_name);

           if($row->sip_status == "CL"){
                $status = "Inactive ";
            }else{
                $status = "Active";
            }
          if($row->tier_type == 'T1'){
                $tier_type = 'Tier I';
            }else{
                 $tier_type = 'Tier II';
            }
         
         
          $mobile_no_dec=decrypted($row->mobile_no);
          $mobile_no='="' . $mobile_no_dec . '"';
          $pran = '="' . $row->pran . '"';

         
         
         fputcsv(
                $handle,
                array(
                ($row->mast_sip_contr_id),
                ($tier_type),
                ($full_name),
				($pran),
     	        (decrypted($row->pan)),
                //(Crypt::decryptString($row->email)),
                (decrypted($row->email)),
                ($mobile_no), 
                ($row->start_date != 0) ? (date('d-M-Y', intval($row->start_date))) : '',
                $row->mandate_sip_date,
                ($row->end_date != 0) ? (date('d-M-Y', intval($row->end_date))) : '', 
                (number_format($row->sip_amount,2)),
                ($row->vendor_uid == NULL) ? $row->vendor_uid : '',
                $status,
    
                )
            );
        }
        fclose($handle);
        $headers = array('Content-Type' => 'text/csv', 'X-Frame-Options' => 'SAMEORIGIN');
        $action = 'SIP List Downloaded';
        $action_desc = 'SIP List : ' . $contribution_type_name . ' Date Range : ' . $from_date . ' to ' . $to_date . ' Reports Type : ' . $report_type;
        insert_admin_action_log($action, $action_desc);
        return response()->download($filename, time().'_SIP_List.csv', $headers);       
        } elseif($contribution_type == 'SC'){
        	$contribution_type_name = 'Subsequent Contribution';
            $data = $this->download_report_model->get_subsequent_contribution_csv($from_date,$to_date,$report_type); 
            // print_r_custom($data);die();   
           
            $handle = fopen($filename, 'w+');
            fputcsv(
                $handle,
                array(    
              "Merchant ID",
            "Request ID",
            "Date of Transaction",
            "Customer Name",
            "Amount",
            "Requested Action",
            "Status",
            "BankName",
            "Requested Amount", //
            "Discount",
            "Additional Charges",
            "Transaction ID",
            "CRA Transaction ID",
                
            "Payment Gateway",
            "Bank Reference No",
            // "Last Name",
            "Customer Email",
            "Settlement Date",
            "Settlement UTR",
            "Customer Phone",
            "City",
            "Country",
            "Zipcode",
            "State",
            "PRAN", 
            "Net Amount after deducting POP charges",   //
            "Tier type",
            "CRA",
            "Contribution receipt no",
            "Source"    
                )
            );
            foreach ($data as $row) {
            $full_name = "";
            if(decrypted($row->full_name) == "")
                $full_name = decrypted($row->fname).' '.decrypted($row->mname).' '.decrypted($row->lname);
            else   
                $full_name = decrypted($row->full_name);

            $status='';
        if($row->payment_status == 'A')
        $status='Success';
       	else if($row->payment_status == 'F')
        $status='Failed';
      	else if($row->payment_status == 'P')
        $status='Pending';
        else
		$status=$row->payment_status;
            
            
            $contr_receipt_no ='';
            if($row->contr_receipt_no)
            $contr_receipt_no = '="' . $row->contr_receipt_no . '"';
            
            $mobile_no_dec=decrypted($row->mobile_no);
        	$mobile_no='="' . $mobile_no_dec . '"';
        
            $pran = '="' . $row->p . '"';
        
                fputcsv(
                    $handle,
                    array(
                        '',
                        '',
                        (date('d/m/Y',$row->cre)),
                    	($full_name),
                        ($row->total_amount_received),
                        ('Subsequent'),
                        ($status),
                        (''),   //bank
                        // ($row->total_amount_received),
                        // ($row->contribution_amount), 
                        ($row->total_amount_received),
                    
                    
                        ('N/A'),
                        ('N/A'),
                        ($row->payment_trans_id) , 
                        ($row->contr_cra_trans_id) , 
                    
                        ('JIO'),
                        (''),   //Bank Reference No,
                        // (decrypted($row->lname)),  //Last Name,
                        (decrypted($row->email)),
                        (''),   //Settlement Date,
                        (''),   //Settlement UTR,
                        ($mobile_no), //Customer Phone,
                        (''),   //City,
                        (''),   //Country
                        (''),   //Zipcode,
                        (''),   //State,
                        ($pran),
                        // ($row->total_amount_received),
                        ($row->contribution_amount), 

                    
                    
                    
                        // ($row->contribution_amount), 
                        ($row->tier_type), 
                        ($row->cra_account), 
                        $contr_receipt_no,
                        ($row->vendor_uid == NULL) ? $row->vendor_uid : '',
                    
                    
                    
                    )
                );
            }
            fclose($handle);
            $headers = array('Content-Type' => 'text/csv', 'X-Frame-Options' => 'SAMEORIGIN');
        	$action = 'Subsequent Contribution Downloaded';
        	$action_desc = 'Contribution Type : ' . $contribution_type_name . ' Date Range : ' . $from_date . ' to ' . $to_date . ' Reports Type : ' . $report_type;
        	insert_admin_action_log($action, $action_desc);
            return response()->download($filename, time().'_Subsequent_contribution.csv', $headers);
        }elseif($contribution_type == 'PC'){
        	$contribution_type_name = 'POP Charges';
           $data = $this->download_report_model->get_POP_Charges_csv($from_date,$to_date);
        // print_r_custom($data);die();        
            $handle = fopen($filename, 'w+');
            fputcsv(
                $handle,
                array(    
            	"Date",
				"PRAN",
				"Total Contribution Amt",
                "Contribution Month",
                "Contribution Year",
                "Account Opening",
                "IGST",
                "CGST",
                "SGST",
				"POP",
				"IGST",
				"CGST",
				"SGST",
				"Payment Gateway Charges Amount(in ₹)",
				"Payment Gateway Charges Tax(in ₹)",
				"Net",
				"Type",
                "CHO Name/Retail",
                "Transaction ID",
				"CRA Transaction ID",
				"Receipts No.",
				"PRAN Generation date",
				"Tier",
                "Corp/Retail",
                "SIP/Subs",
                "Registration charges",
                "Contribution fees",
                "Minimum Fees",
                "Date of Transfer",
                "State"
                )
            );
            foreach ($data as $row) {
            $pran = '="' . $row->pran . '"';
            $tier=""; 
            if($row->tier_type =="T1")
            $tier="Tier I";
            elseif($row->tier_type =="T2")
            $tier="Tier II";
            else
            $tier="-";
           
        
            
            
            
            
            $total_contribution_amt="";  //full
            $net=""; //cut
            if($row->contribution_type == 'SIP'){
            $total_contribution_amt= $row->sip_amount;
            $net= $row->total_amount_received;
            }elseif($row->contribution_type == 'SUBSEQUENT'){
            $total_contribution_amt= $row->total_amount_received; 
            $net= $row->contribution_amount;
            }else{
            $total_contribution_amt="-";  
            $net="-";
            }
            
            
            
            
            
                fputcsv(
                    $handle,
                    array(
                               (date('d/m/Y',$row->created_on)),
                               ($pran),
                               (number_format($total_contribution_amt,2)),
                       		   (date('m', $row->created_on)),
                  			   (date('Y', $row->created_on)),
                    "",
                    "",
                    "",
                    "",
                    
                               (number_format($row->contribution_fees,2)),
                        	   (number_format($row->igst_amt,2)),
                               (number_format($row->cgst_amt,2)),
                               (number_format($row->sgst_amt,2)),
                    '',
                    '',
                               (number_format($net,2)),
                               ($row->cra_account),
                    '',
                                ($row->payment_trans_id),
                               ($row->contr_cra_trans_id),
                               ($row->contr_receipt_no),
                    '',
                               ($tier),
                    'Retail',
                               ($row->contribution_type),
                    '',
                    '0.50%',
                    '30',
                    '',
                    (!empty($row->address_state) ? decrypted($row->address_state) : (!empty($row->custom_state) ? decrypted($row->custom_state) : '-') ),                  
                    )
                );
            }
            fclose($handle);
            $headers = array('Content-Type' => 'text/csv', 'X-Frame-Options' => 'SAMEORIGIN');
        	$action = 'POP Charges Downloaded';
        	$action_desc = 'Contribution Type : ' . $contribution_type_name . ' Date Range : ' . $from_date . ' to ' . $to_date . ' Reports Type : ' . $report_type;
        	insert_admin_action_log($action, $action_desc);
            return response()->download($filename, time().'_POP_Charges.csv', $headers);
     

        }else{

        }
        // $data = $this->corporate_mapping_list_model->get_corp_mapping_data_between($from_date, $to_date);
        // echo "from_date : " . $from_date . "\n";
        // echo "to_date : " . $to_date . "\n";
        // echo "contribution_type : " . $contribution_type . "\n";
        // echo "report_type : " . $report_type . "\n";
    }
}
