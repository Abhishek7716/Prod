<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use App\Libraries\Payment_gateway_lib;
use App\Libraries\Sms_gateway_lib;
use App\Libraries\Email_lib;
use Illuminate\Support\Facades\Validator;
use App\Models\Retail_sip_model;
use App\Models\Notification_model;
use Illuminate\Support\Carbon;
use App\Models\Insert_s2s_response_model;
use App\Models\Home_model;
use App\Libraries\Cams_lib;
use App\Libraries\Nsdl_lib;
use App\Libraries\Kfintech_lib;
use Illuminate\Support\Facades\Blade;
use App\Models\Cron\Sip_contributions_model;


class Retail_sip extends Controller
{
    private $retail_sip_model;
    public function __construct(Sip_contributions_model $sip_contributions_model,Retail_sip_model $retail_sip_model, Email_lib $email_lib, Sms_gateway_lib $sms_gateway_lib, Notification_model $notification_model, Insert_s2s_response_model $insert_s2s_response_model)
    {
    
    	$test = generate_gst_number();
    	// dd($test);
        $this->home_model = new Home_model();
        // $this->middleware('CheckLoggedIn');
        $this->retail_sip_model = $retail_sip_model;
        $this->notification_model = $notification_model;
        $this->insert_s2s_response_model = $insert_s2s_response_model;
        $this->sip_contributions_model = $sip_contributions_model;
        //$sms_data = $this->notification_model->get_sms_notification("sip","S");
        //$message = $sms_data->message;
        //$sms_response = $sms_gateway_lib->send_sms('918554809239', $message, $sms_data->template_id);
        //dd($sms_response);
        // $email_data = $this->notification_model->get_email_notification("sip","A");
        // $email_temp = view('frontend.email_templates.sip_initiation_successful', ['message' => $email_data->message])->render();
        // $param = array(
        // 'subject' => $email_data->subject,
        // 'message' => $email_temp,
        // "to"=> array(array
        // ('email' => "dilesh.r@mobilestyx.co.in"),
        // ),
        // "cc"=> array(array
        // ('email' => "dilesh.r@mobilestyx.co.in"),
        // ),
        // );
        // $test = $email_lib->send_email($param);

    }

    public function error_page(Request $request,Sms_gateway_lib $sms_gateway_lib, Email_lib $email_lib)
    {
        $failed_sms_template_details = $this->sip_contributions_model->get_sms_details(array(array('type', '=', 'failed_sip'), array('is_on', '=', 'Y')));
        $failed_email_template_details = $this->sip_contributions_model->get_email_details(array(array('type', '=', 'failed_sip'), array('is_on', '=', 'Y')));
    
            $failed_sms_template_attempt_details = $this->sip_contributions_model->get_sms_details(array(array('type', '=', 'failed_sip'), array('is_on', '=', 'Y')));
            $failed_email_template_attempt_details = $this->sip_contributions_model->get_email_details(array(array('type', '=', 'failed_sip'), array('is_on', '=', 'Y')));



            if($failed_email_template_details)
            $failed_email_msg = Blade::render($failed_email_template_details->message);
           if($failed_email_template_attempt_details)
            $failed_email_attemp_msg = Blade::render($failed_email_template_attempt_details->message);
      
            $mobile_no = '91'.(session('api_subscriber_mobile'));

            // $sent_sms = $sms_gateway_lib->send_sms($mobile_no,$failed_sms_template_attempt_details->message,  $failed_sms_template_details->template_id);




            $email = array(array('email' => (session('api_subscriber_email'))));
            $params = array(
                'to' => $email,
                'subject' => $failed_email_template_details->subject,
                'message' => $failed_email_attemp_msg  // Email message for Mandate Trigger 1
            );
            $sent_email = $email_lib->send_email($params);

            return $sent_email;

        return view('frontend.error_page');
    }

    public function test_res(Payment_gateway_lib $Payment_gateway_lib)
    {
		// echo public_path('frontend_assets/image');
        // $message = 'TRN17201846098249|ICICIPRUJOP26042024|NPSCONTRITIER1|2024-07-05T18:33:30';
        // $des = $Payment_gateway_lib->generate_auth_token($message);
        // print_r_custom($des, true);

//         $transactionRefNumber = 'TRN17202686939232';
//         $transactionDateTime = '2024-07-06T17: 55: 38';

//         $polling_status = $Payment_gateway_lib->status_polling($transactionDateTime, $transactionRefNumber);
//         print_r_custom($polling_status, true);
    
    		// insert_user_action_log('resr', true , false);
    }

    public function index()
    {
        session(['user_auth' => 'retail_sip_flow']);
        session()->forget('sip_amount_breakup');
        session()->forget('html_form');
        session()->forget('subsequent_amount_breakup');
        session()->forget('subsequent_contribution_data');
        // session()->forget('personal_details_data');
        session()->forget('sip_amount_breakup');
    	session()->forget('retail_sip_set_retry');

        $pran = session('api_subscriber_pran');
        $data['tier_1'] = $this->home_model->check_T1_availability($pran);

        // print_r($data['tier_1']);die();
        if ($data['tier_1']) {
            // echo "if";die();
            session(['tier_1' => 'set']);
        } else {
            // echo "else";die();
            session()->forget('tier_1');
        }

        $data['tier_2'] = $this->home_model->check_T2_availability($pran);
        // print_r($data['check']);
        if ($data['tier_2']) {
            // echo "if";die();
            session(['tier_2' => 'set']);
        } else {
            // echo "else";die();
            session()->forget('tier_2');
        }

        if (!session('tier_1') || !session('tier_2')) {
            if (!session('tier_1') && session('api_subscriber_tier_1') == 'A' || !session('tier_2') && session('api_subscriber_tier_2') == 'A') {
              
                return view('frontend.sip_personal_details');
            }
            return redirect()->route('home');
        }

        return redirect()->route('home');

    }

    public function retail_sip_preview_page()
    {
    // dd(session()->all()); 
        $sip_amount = session('personal_details_data.sip_amount');
        //$sip_amount_breakup_final_amount = session('sip_amount_breakup.final_amount');

       //if (!isset($sip_amount_breakup_final_amount)) {
        $sip_amount_breakup = formula_matrix($sip_amount,session('state'));

        // Add the array to the session
        session(['sip_amount_breakup' => $sip_amount_breakup]);
       
       //}
		// dd(session()->all());
        return view('frontend.personal_delails_preview_page');
    }

    public function sip_scheduled_successfully()
    {
        return view('frontend.sip_scheduled_successfully');
    }

    public function personal_details_submit(Request $request)
    {
        $request->merge([
            'name' => preg_replace('/\s+/', ' ', trim($request->input('name')))
        ]);

        $validator = Validator::make($request->all(), [
            // 'name' => [],
            'type_of_account' => ['required', 'in:T1,T2'],
            'sip_frequency' => ['required', 'in:Monthly,Yearly'],
            'start_date' => [
                'required',
                'date_format:d-m-Y',
                function ($attribute, $value, $fail) {
                    $date = Carbon::createFromFormat('d-m-Y', $value);
                    $today = Carbon::today();

                    if ($date->lte($today)) {
                        $fail('The start date must be a date after today.');
                    }

                    if ($date->day >= $date->daysInMonth - 4) {
                        $fail('The start date cannot be within the last 5 days of any month.');
                    }
                }
            ],
            // 'end_date' => 'required|min:4|max:4',
            'sip_amount' => ['required', 'numeric', 'min:500', 'max:500000']
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstError = $errors->first();
            $failedField = $errors->keys()[0];

            return response()->json([
                'status' => 400,
                'msg' => $firstError,
                'id' => $failedField,
            ]);
        } else {
            // $end_date = $request->input('end_date');
        
        if(session('api_subscriber_tier_2') != 'A'){
        if($request->input('type_of_account') == 'T2')
          return response()->json(array('status' => 400, 'msg' => 'TierII not active','id'=>'type_of_account' ));
        }
        
        if(session('api_subscriber_tier_1') != 'A'){
        if($request->input('type_of_account') == 'T1')
          return response()->json(array('status' => 400, 'msg' => 'TierI not active','id'=>'type_of_account' ));
        }
        
        if(session('tier_1') == 'set'){
        if($request->input('type_of_account') == 'T1')
          return response()->json(array('status' => 400, 'msg' => 'TierI already set','id'=>'type_of_account' ));
        }
        
        if(session('tier_2') == 'set'){
        if($request->input('type_of_account') == 'T2')
          return response()->json(array('status' => 400, 'msg' => 'TierII already set','id'=>'type_of_account' ));
        }
        
        
        
          // return response()->json(array('status' => 400, 'msg' => 'false','id'=>'type_of_account' ));
        

            $start_date = $request->input('start_date');
            $end_date = strtotime('+ 50 years', strtotime($start_date));
            $end_date = date('d-m-Y', $end_date);

            // Extract the month and day from the start_date
            $start_month_day = date('m-d', strtotime($start_date));

            // Combine the extracted year from end_date with month and day from start_date
            // $end_date = $end_date . '-' . $start_month_day;

            $sessionData = session()->all();
            $fields = [
                'name',
                'type_of_account',
                'sip_frequency',
                'start_date',
                'end_date',
                'sip_amount'
            ];

            foreach ($fields as $field) {
                $request->session()->put("personal_details_data.$field", $request->input($field));
            }

            $request->session()->put('personal_details_data.pran', $sessionData['validated_data']['pran']);
            $request->session()->put('personal_details_data.name', session('api_subscriber_name'));
        
        
            // $request->session()->put('personal_details_data.sip_amount', session('api_subscriber_name'));
//         
        
          // $request->session()->put('personal_details_data.sip_amount', session('sip_amount_breakup.final_amount'));
        
        // return  session('sip_amount_breakup.final_amount');
        
            $request->session()->put('personal_details_data.mobile', $sessionData['validated_data']['mobile']);
            $request->session()->put('personal_details_data.end_date', $end_date);
        
      
        
      
            return response()->json(array('status' => 200, 'msg' => 'Success'));
        }
    }

    public function personal_details_preview_page_submit(Request $request, Payment_gateway_lib $payment_gateway_lib)
    {
    //dd(session('transactionRefNumber'));
    $pran = session('api_subscriber_pran');
   		$data['tier_1'] = $this->home_model->check_T1_availability($pran);

        // print_r($data['tier_1']);die();
        if ($data['tier_1']) {
            // echo "if";die();
            session(['tier_1' => 'set']);
        } else {
            // echo "else";die();
            session()->forget('tier_1');
        }

        $data['tier_2'] = $this->home_model->check_T2_availability($pran);
        // print_r($data['check']);
        if ($data['tier_2']) {
            // echo "if";die();
            session(['tier_2' => 'set']);
        } else {
            // echo "else";die();
            session()->forget('tier_2');
        }
		// dd($data);
        //$request->session()->put('personal_details_data.sip_amount', session('sip_amount_breakup.final_amount'));
        $request->session()->put('personal_details_data.final_sip_amount', session('sip_amount_breakup.final_amount'));
    
    
 
    
    	$personal_details_data = session('personal_details_data');
     //print_r_custom(session('sip_amount_breakup.final_amount'));die();
    	
    	$tier_limits = [
    		'T1' => 'tier_1',
  			'T2' => 'tier_2'
		];

		foreach ($tier_limits as $type => $session_key) {
    		if (session()->has($session_key) && $personal_details_data['type_of_account'] == $type) {
        		return response()->json([
            		'status' => 400,
            		'msg' => "Tier $type limit exceeded!"
        		]);
    		}
		}

    
//         if (!session()->has('tier_1') && !session()->has('tier_2')) {
//             // return redirect()->route('home');
//         	return response()->json(array('status' => 400, 'msg' => 'Tier 1 and Tier 2 limit exceeded!'));
//         }
    	
//     	$personal_details_data = session('personal_details_data');
    
//     	$type = $personal_details_data['type_of_account'];
// 		$tier_sessions = [
//     		'T1' => 'tier_1',
//     		'T2' => 'tier_2'
// 		];

// 		if (!array_key_exists($type, $tier_sessions) || !session()->has($tier_sessions[$type])) {
// 		    $msg = ($type == 'T1') ? 'Maximum T1 exceeded!' : 'Maximum T2 exceeded!';
// 		    return response()->json(['status' => 400, 'msg' => $msg]);
// 		}

    	// dd(session('retail_sip_set_retry'));
        if (session()->has('retail_sip_set_retry') && session('retail_sip_set_retry') > 3)
            return response()->json(array('status' => 400, 'msg' => 'Maximum retry exceeded!',));

        
        $pop_uid = DB::table('users')->where('pran', $pran)->value('pop_uid');
    
    	$personal_details_data = session('personal_details_data');
    
    // dd($personal_details_data);

        $mandate_response = $payment_gateway_lib->create_mandate_payment($pop_uid,$personal_details_data);
    
    	// dd($mandate_response);
        $status_code = $mandate_response['response']['http_status'];
        if ($status_code != 200)
            return response()->json(array('status' => 400, 'msg' => 'Something went wrong!'));

        $htmlForm = $mandate_response['response']['api_response']['htmlForm'];

        $request->session()->put('html_form', $htmlForm);

        $transactionRefNumber = $mandate_response['transactionRefNumber'];

        // $inserted_data= $this->retail_sip_model->insert_master_contribution_details($request->input('pran'),$request->input('type_of_account_radio'),$request->input('sip_frequency_radio'), $request->input('sip_amount'),strtotime($request->input('start_date')),strtotime($request->input('end_date')));
        
        $sip_amount_breakup = session('sip_amount_breakup');

        if (session()->has('retail_sip_set_retry') && session('retail_sip_set_retry') < 3) {

        	$conditions = [
				['pg_transaction_id', '=', session('transactionRefNumber')]
			];
        
        
        
            $update = [
                'pran' => $personal_details_data['pran'],
                'tier_type' => $personal_details_data['type_of_account'],
                'sip_frequency' => $personal_details_data['sip_frequency'],
                'sip_amount' => $personal_details_data['sip_amount'],
                'start_date' => strtotime($personal_details_data['start_date']),
                'end_date' => strtotime($personal_details_data['end_date']),
                'sip_count' => '1', // sip_count
                'pg_transaction_id' => $transactionRefNumber
            ];
        // enable_query
        	$updated_mcd = $this->retail_sip_model->update_master_contribution_details($conditions,$update);
        // print_r_custom($conditions);
        // print_r_custom($update);
        // die();
        
            if (!$updated_mcd)
                return response()->json(array('status' => 400, 'msg' => 'Data not insterted 1'));

            // $start_date_timestamp = strtotime($personal_details_data['start_date']);
            // // Calculate the date exactly one month after the start date
            // $sip_date_timestamp = strtotime('+1 month', $start_date_timestamp);

			$conditions = [
				['payment_trans_id', '=', session('transactionRefNumber')]
			];
        
        	
        	$contr_ref_id = $this->retail_sip_model->get_contribution_details_cycle($conditions);
            $contr_cycle_id = $contr_ref_id->contr_id;
        
            $cycle_data = [
                // 'mast_sip_contr_id' => $master_id,
                'pran' => $personal_details_data['pran'],
                'full_name' => encrypted(session('api_subscriber_name')),
                'tier_type' => $personal_details_data['type_of_account'],
                'sip_amount' => $personal_details_data['sip_amount'],
                'contribution_fees' => $sip_amount_breakup['contribution_charges'],
                // 'sgst_amt' => $sip_amount_breakup['sgst'],
                // 'cgst_amt' => $sip_amount_breakup['cgst'],
            	'gst_amt' => $sip_amount_breakup['cgst'],
                'total_amount_received' => $sip_amount_breakup['final_amount'],
                'sip_date' => strtotime($personal_details_data['start_date']),
                'mandate_status' => 'P',
                'payment_trans_id' => $transactionRefNumber
            ];
        
        	if(session()->has('state') && session('state')){
        		$cycle_data['sgst_amt'] = $sip_amount_breakup['sgst'];
            	$cycle_data['cgst_amt'] = $sip_amount_breakup['cgst'];
            }else{
            	$cycle_data['igst_amt'] = $sip_amount_breakup['igst'];
            }
        
            $this->retail_sip_model->update_contribution_details_cycle($conditions,$cycle_data);

        } else {

            $master_id = $this->retail_sip_model->insert_master_contribution_details(
                $personal_details_data['pran'],
                $personal_details_data['type_of_account'],
                $personal_details_data['sip_frequency'],
                $personal_details_data['sip_amount'],
                strtotime($personal_details_data['start_date']),
                strtotime($personal_details_data['end_date']),
                '1', // sip_count
                $transactionRefNumber
            );
            if (!$master_id)
                return response()->json(array('status' => 400, 'msg' => 'Data not insterted '));

            $cycle_data = [
                'mast_sip_contr_id' => $master_id,
                'pran' => $personal_details_data['pran'],
                'full_name' => encrypted(session('api_subscriber_name')),
                'tier_type' => $personal_details_data['type_of_account'],
                'sip_amount' => session('sip_amount_breakup.final_amount'),
                'contribution_fees' => $sip_amount_breakup['contribution_charges'],
                // 'gst_amt' => $sip_amount_breakup['sgst'],
                // 'cgst_amt' => $sip_amount_breakup['cgst'],
            	'gst_amt' => $sip_amount_breakup['gst_amt'],
                'total_amount_received' => $sip_amount_breakup['sip_amount'],
                'sip_date' => strtotime($personal_details_data['start_date']),
                'mandate_status' => 'P',
                'payment_trans_id' => $transactionRefNumber
            ];
        
        	// if(session()->has('state') && session('state')){
        		$cycle_data['sgst_amt'] = $sip_amount_breakup['sgst'];
            	$cycle_data['cgst_amt'] = $sip_amount_breakup['cgst'];
            // }else{
            	$cycle_data['igst_amt'] = $sip_amount_breakup['igst'];
            // }
            $contr_cycle_id = $this->retail_sip_model->insert_contribution_details_cycle($cycle_data);
        
        if($contr_cycle_id)
        session(['contr_ref_id'=>$contr_cycle_id]);

        }

        $pg_data = [
            'contr_ref_id' => $contr_cycle_id,
            'contribution_type' => 'SIP',
            'pran' => $personal_details_data['pran'],
            'payment_type' => 'MD',
        	'payment_status' => 'F',
            'payment_trans_id' => $transactionRefNumber
        ];
    
        $pg_trans_id = $this->retail_sip_model->insert_pg_transaction_details($pg_data);
    
    // print_r_custom($pg_trans_id);die();

    $request->session()->put('transactionRefNumber', $transactionRefNumber);
        if ($contr_cycle_id && $pg_trans_id) {
            return response()->json(array('status' => 200, 'msg' => 'Success', 'data' => $htmlForm));
        }
    }

    public function jio_payment(Request $request)
    {
        if (!empty(session('html_form'))) {
            $htmlForm = session('html_form');
        	session()->forget('html_form');
            echo base64_decode($htmlForm);
        }
    }

 


    public function jio_payment_response(Request $request)
    {
        if ($request->isMethod('post')) {
            if ($request->has('responseData')) {

                if (!env('LOCALHOST', false))
                    $con_db = 'icic_pop_txt';
                else
                    $con_db = 'pop_uat_txt';

                // Encode the request data as JSON
                $data = $request->all();
                $time = time();

                $responseDataArray = json_decode($data['responseData'], true);
                $headers = $request->headers->all();
                if (!empty($headers))
                    $responseDataArray['headers'] = $headers;
                // Insert data into the database
                try {
                $contr_ref_id='';
            if (session()->has('contr_ref_id')) {
  			   $contr_ref_id = session('contr_ref_id');
               session()->forget('contr_ref_id');
            }
             
                
                    DB::connection($con_db)->table('pg_logs')->insert([
                        'payment_trans_id' => $responseDataArray['transactionRefNumber'],
                        'data' => json_encode($data),
                    'contr_ref_id' =>$contr_ref_id,
                     'type' => 'SIP B2B',
                    'contribution_type'=>'SIP',
                        'created_on' => $time,
                    ]);
                    // echo "Record inserted successfully.";
                } catch (\Exception $e) {
                    echo "Error inserting record: " . $e->getMessage();
                }

                $data['transactionRefNumber'] = $responseDataArray['transactionRefNumber'];
                $data['transactionDateTime'] = $responseDataArray['transactionDateTime'];

                return view('frontend.loader', $data);
            } else {
                echo "Invalid post request.";
            }
        }
    }

    public function check_S2S(Request $request, Sms_gateway_lib $sms_gateway_lib, Email_lib $email_lib)
    {
        if (!env('LOCALHOST', false))
            $con_db = 'icic_pop_txt';
        else
            $con_db = 'pop_uat_txt';

        $record = false;
        $record = DB::connection($con_db)
            ->table('pg_logs')
            ->where('payment_trans_id', $request->post('transactionRefNumber'))
            ->where('type', 'SIP S2S')
            ->first();

        // Redirection and status update based on success status
        if ($record) {
            $sms_data = $this->notification_model->get_sms_notification("nps_successful_sip", "S");
            $message = $sms_data->message;
            $sms_response = $sms_gateway_lib->send_sms(session('api_subscriber_mobile'), $message, $sms_data->template_id);

            $email_data = $this->notification_model->get_email_notification("nps_successful_sip", "A");
            $email_temp = view('frontend.email_templates.sip_initiation_successful', ['message' => $email_data->message])->render();
            $param = array(
                'subject' => $email_data->subject,
                'message' => $email_temp,
                "to" => array(
                    array
                    (
                        'email' => session('api_subscriber_email')
                    ),
                ),
                "cc" => array(
                    array
                    (
                        'email' => session('api_subscriber_email')
                    ),
                ),
            );
            // $email_send = $email_lib->send_email($param);


            return ['status' => 200, 'msg' => 'success'];
        } else {
        	// $request->session()->put('retail_sip_set_retry', 0);
        
        	if (!session()->has('retail_sip_set_retry')) {
    			session()->put('retail_sip_set_retry', 0);
			}
			
            return ['status' => 400, 'msg' => 'failed'];
        }
    }
    public function retail_sip_check_polling_status(Request $request, Payment_gateway_lib $payment_gateway_lib, Email_lib $email_lib, Sms_gateway_lib $sms_gateway_lib, Cams_lib $cams_lib, Nsdl_lib $nsdl_lib, Kfintech_lib $kfintech_lib,)
    {

        $validator = Validator::make($request->all(), [
            'transactionRefNumber' => 'required',
            'transactionDateTime' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstError = $errors->first();
            $failedField = $errors->keys()[0];

            return response()->json([
                'status' => 400,
                'msg' => $firstError,
                'id' => $failedField,
            ]);
        } else {
        	$transaction_types = get_settings('payment_gateway', array('transaction_type'));
        
            $transactionRefNumber = $request->post('transactionRefNumber');
            $transactionDateTime = $request->post('transactionDateTime');

        	$conditions = [
            	['payment_trans_id', '=', $transactionRefNumber]
            ];

            $sip_cycle_data = $this->insert_s2s_response_model->get_contribution_details_cycle($conditions);
        
        	if($sip_cycle_data['tier_type'] == 'T1')
            	$business_flow = $transaction_types['payment_gateway']['transaction_type']['sip_tier1'];
            elseif($sip_cycle_data['tier_type'] == 'T2')
            	$business_flow = $transaction_types['payment_gateway']['transaction_type']['sip_tier2'];
        
            $polling_status = $payment_gateway_lib->status_polling($transactionDateTime, $transactionRefNumber, $business_flow);
        	// print_r_custom($polling_status);die();
            if (isset($polling_status['api_response']['status']) && $polling_status['api_response']['status'] === 'SUCCESS') {
                //print_r_custom($polling_status,true);
                $sms_data = $this->notification_model->get_sms_notification("nps_successful_sip", "S");
                $message = $sms_data->message;
                $sms_response = $sms_gateway_lib->send_sms(session('api_subscriber_mobile'), $message, $sms_data->template_id);

                $email_data = $this->notification_model->get_email_notification("nps_successful_sip", "A");
                $email_temp = Blade::render($email_data->message); // view('frontend.email_templates.nps_contribution_successful', ['message' => $email_data->message])->render();
                $param = array(
                    'subject' => $email_data->subject,
                    'message' => $email_temp,
                    "to" => array(
                        array
                        (
                            'email' => session('api_subscriber_email')
                        ),
                    ),
                    "cc" => array(
                        array
                        (
                            'email' => session('api_subscriber_email')
                        ),
                    ),
                );
                $email_send = $email_lib->send_email($param);

                $conditions = [
                    ['payment_trans_id', '=', $transactionRefNumber]
                ];
                $pg_status = "A";
                // print_r_custom($polling_status,true);
                $instrumentReference = $polling_status['api_response']['paymentDetail'][0]['instrumentReference'];
                $instrumentDate = $polling_status['api_response']['paymentDetail'][0]['instrumentDate'];
            	$gst_number = generate_gst_number();
                $update = [
                	'gst_invoice_no' => $gst_number,
                    'payment_status' => $pg_status,
                    'instrument_reference' => $instrumentReference,
                    'instrument_date' => $instrumentDate,
                    'updated_on' => time(),
                ];
                $update_pgtd = $this->insert_s2s_response_model->update_pg_transaction_details($conditions, $update);
                if (!$update_pgtd)
                    return response()->json(array('status' => 400, 'msg' => 'Error updating SIP contribution', 'response' => $polling_status));


                $mandate_status = "S";
            	if (session('cra') == 'CAMS') {
            		$receipt_no = $cams_lib->get_receipt_no();
                } elseif (session('cra') == 'NSDL') {
            		$receipt_no = $nsdl_lib->get_receipt_no();
                } elseif (session('cra') == 'KFINTECH') {
            		$receipt_no = $nsdl_lib->get_receipt_no();
                }
                $update = [
                    'gst_invoice_no' => $gst_number,
                	'contr_receipt_no' => $receipt_no,
                    'mandate_status' => $mandate_status,
                    'updated_on' => time(),
                ];
                $update_cdc = $this->insert_s2s_response_model->update_contribution_details_cycle($conditions, $update);
                if (!$update_cdc)
                    return response()->json(array('status' => 400, 'msg' => 'Error SIP details cycle', 'response' => $polling_status));
            
            	$conditions = [
                    ['payment_trans_id', '=', $transactionRefNumber],
                	['mandate_status', '=', 'S']
                ];

                $cdc_data = $this->insert_s2s_response_model->get_contribution_details_cycle($conditions);
            
            	$mcd_conditions = [
                	['mast_sip_contr_id', '=', $cdc_data['mast_sip_contr_id']]
				];
                    	
				$mcd_data = $this->insert_s2s_response_model->get_master_contribution_details($mcd_conditions);
                    
				if($mcd_data['sip_frequency'] == 'Monthly'){
                	$sip_date = strtotime('+1 month', $cdc_data['sip_date']);
                }else $sip_date = strtotime('+1 year', $cdc_data['sip_date']);

                // $sip_date = strtotime('+1 month', $cdc_data['sip_date']);
				$sip_amount_breakup = session('sip_amount_breakup');
            
                // dd($sip_amount_breakup);
            	
                $cycle_data = [
                    'mast_sip_contr_id' => $cdc_data['mast_sip_contr_id'],
                    'pran' => $cdc_data['pran'],
                    'full_name' => $cdc_data['full_name'],
                    'tier_type' => $cdc_data['tier_type'],
                    'sip_amount' => $cdc_data['sip_amount'],
                    'contribution_fees' => $sip_amount_breakup['contribution_charges'],
                    'sgst_amt' => $sip_amount_breakup['sgst'],
                    'cgst_amt' => $sip_amount_breakup['cgst'],
                	'gst_amt' => $sip_amount_breakup['gst_amt'],
                	'igst_amt' => $sip_amount_breakup['igst'],
                    'total_amount_received' => $sip_amount_breakup['final_amount'],
                    'sip_date' => $sip_date,
                    'mandate_status' => 'P',
                    'payment_trans_id' => null
                ];

                $contr_cycle_id = $this->retail_sip_model->insert_contribution_details_cycle($cycle_data);

                $conditions = [
                    ['pg_transaction_id', '=', $transactionRefNumber]
                ];
                $sip_status = "A";
                $pg_status = 'A';
                $umrn = NULL;
                if (isset($polling_status['api_response']['paymentDetail'][0]['mandateRespDetails']['umrn']) && !empty($polling_status['api_response']['paymentDetail'][0]['mandateRespDetails']['umrn'])) {
                    $umrn = $polling_status['api_response']['paymentDetail'][0]['mandateRespDetails']['umrn'];
                }
                // else {
                // print_r_custom($polling_status['api_response']);
                // print_r_custom($polling_status['api_response']['paymentDetail'][0]['mandateRespDetails']['umrn']);
                // die();
                // }
                $update = [
                    'pg_status' => $pg_status,
                    'umrn' => $umrn,
                    'sip_status' => $sip_status,
                	'sip_count' => 2,
                    'updated_on' => time(),
                ];
                $update_mcd = $this->insert_s2s_response_model->update_master_contribution_details($conditions, $update);
                if (!$update_mcd)
                    return response()->json(array('status' => 400, 'msg' => 'Error master SIP details', 'response' => $polling_status));
            
            	$action = 'Transaction Success.';
            	$action_desc = "PG Reason". $polling_status['api_response']['status'] . " for transaction Id : ". $transactionRefNumber;
            	// $row_id = $update_mcd;
            
            	insert_user_action_log($transactionRefNumber, $action , $action_desc);
			   // session()->forget([
			   // 'personal_details_data.type_of_account',
			   // 'personal_details_data.start_date',
			   // 'personal_details_data.sip_frequency',
			   // 'personal_details_data.sip_amount'
			   // ]);


                return response()->json(array('status' => 200, 'msg' => 'Transaction Reference Found.', 'response' => $polling_status));
            } else {
            
            	// $request->session()->put('retail_sip_set_retry', 0);
            	
            	if (!session()->has('retail_sip_set_retry')) {
    				session()->put('retail_sip_set_retry', 0);
				}

            	
                $conditions = [
                    ['payment_trans_id', '=', $transactionRefNumber]
                ];
                $payment_status = "F";
				
                $update = [
					'payment_status' => $payment_status,
                   'payment_compl_on'=>time(),
                    'updated_on' => time(),
                ];
                // enable_query_log();
                $update_pgtd = $this->insert_s2s_response_model->update_pg_transaction_details($conditions, $update);

                if (!$update_pgtd)
                    response()->json(array('status' => 400, 'msg' => 'Error updating subsequent contribution', 'response' => $polling_status));

                $mandate_status = 'F';
                $pg_status = 'E';
                $update = [
                    'mandate_status' => $pg_status,
                    'updated_on' => time(),
                ];
                $update_cdc = $this->insert_s2s_response_model->update_contribution_details_cycle($conditions, $update);
                if (!$update_cdc)
                    return response()->json(array('status' => 400, 'msg' => 'Error contribution details cycle', 'response' => $polling_status));

                $conditions = [
                    ['pg_transaction_id', '=', $transactionRefNumber]
                ];

                $sip_status = "F";
                $pg_status = 'E';
                $update = [
                    'pg_status' => $pg_status,
                    'sip_status' => $sip_status,
                    'updated_on' => time()
                ];
                $update_mcd = $this->insert_s2s_response_model->update_master_contribution_details($conditions, $update);
            
            	$action = 'Transaction Failed.';
            	$action_desc = "PG Reason". $polling_status['api_response']['status'] . " for transaction Id : ". $transactionRefNumber;
            
            	insert_user_action_log($transactionRefNumber, $action , $action_desc);

                return response()->json(array('status' => 400, 'msg' => 'Transaction Reference Not Found.', 'response' => $polling_status));
            }
        }
    }
}