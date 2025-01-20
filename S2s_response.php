<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libraries\Payment_gateway_lib;
use Illuminate\Support\Facades\Crypt;
use App\Models\Pops_pg_transaction_s2s;
use App\Models\S2s_response_model;
use App\Models\Insert_s2s_response_model;
use App\Libraries\Sms_gateway_lib;
use App\Libraries\Email_lib;
use App\Libraries\Cams_lib;
use App\Libraries\Nsdl_lib;
use App\Libraries\Kfintech_lib;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;

class S2s_response extends Controller
{
    private $payment_gateway_lib;
	private $insert_s2s_response_model;

    public function __construct(Payment_gateway_lib $payment_gateway_lib, Insert_s2s_response_model $insert_s2s_response_model)
    {
        $this->payment_gateway_lib = $payment_gateway_lib;
    	$this->insert_s2s_response_model = $insert_s2s_response_model;
    }

    public function mandate_response(Request $request, S2s_response_model $s2s_response_model, Sms_gateway_lib $sms_gateway_lib, Email_lib $email_lib, Cams_lib $cams_lib, Nsdl_lib $nsdl_lib, Kfintech_lib $kfintech_lib)
    {
        // {
        //     "transactionType":"SIPTIER1",
        //     "paymentDetail":[
        //         {
        //             "instrumentReference":"20240710011610000017899757102191195",
        //             "totalAmount":"1751.00",
        //             "modeOfPayment":"UPI",
        //             "modeOfPaymentDesc":"UPI",
        //             "instrumentDate":"2024-07-17T14:09:51.706602345"
        //         }
        //     ],
        //     "mandateRespDetails":{"umrn":"1019837"},
        //     "transactionDateTime":"2024-07-09T01:25:12",
        //     "success":true,
        //     "transactionRefNumber":"TRN17205117125883",
        //     "channelId":"ICICIPRUJOP26042024",
        //     "totalAmount":"1751.00"
        // }

        if(!$request->hasHeader('x-jio-payload-auth') || empty($request->header('x-jio-payload-auth')))
            return response()->json(['Success' => false, 'errors' => array("code"=> "01", "reason"=> "Access Denied. Auth Token is Missing.")]);

        if(!$request->has('success') || !$request->has('paymentDetail') || !$request->has('mandateRespDetails') || !$request->has('transactionRefNumber'))
            return response()->json(['Success' => false, 'errors' => array("code"=> "01", "reason"=> "Not Getting Required Fields in POST.")]);
        
        $headers = $request->headers->all();
        $data = json_decode(file_get_contents('php://input'), true);
        $data['headers'] = $headers;

        $transaction_id = $data['transactionRefNumber'];

        $log_data = array(
            'payment_trans_id' => $transaction_id,
            'type' => "AUTO DEBIT MANDATE S2S",
            'data' => json_encode($data),
            'created_on' => time()
        );
        $all_s2s_log_id = DB::connection('icic_pop_txt')->table('all_pg_s2s_logs')->insertGetId($log_data);

        if($data['success'] === true)
            $success = "true";
        else
            $success = "false";

        $message = $success.'|'.$data['transactionRefNumber'].'|'.$data['totalAmount'];

        $x_jio_payload_auth = $headers['x-jio-payload-auth'];
        $x_jio_payload_auth = implode('', $x_jio_payload_auth);

        $auth_token = $this->payment_gateway_lib->generate_auth_token($message);

        if($x_jio_payload_auth != $auth_token)
            return response()->json(['Success' => false, 'errors' => array("code"=> "01", "reason"=> "Access Denied. Auth Token is Mismatching.")]);

        $contr_ref_id = NULL;

        $contr_details = $s2s_response_model->get_pg_trans_contr_details($transaction_id);

        if($contr_details)
        {
            $contr_ref_id = $contr_details->contr_id;
            $success_sms_template_details = $s2s_response_model->get_sms_details(array(array('type', '=', 'nps_successful_contribution'), array('is_on', '=', 'Y')));
            $success_email_template_details = $s2s_response_model->get_email_details(array(array('type', '=', 'nps_successful_contribution'), array('is_on', '=', 'Y')));

            if($success_email_template_details)
                $success_email_msg = Blade::render($success_email_template_details->message);

            $failed_sms_template_details = $s2s_response_model->get_sms_details(array(array('type', '=', 'failed_contribution'), array('is_on', '=', 'Y')));
            $failed_email_template_details = $s2s_response_model->get_email_details(array(array('type', '=', 'failed_contribution'), array('is_on', '=', 'Y')));

            if($failed_email_template_details)
                $failed_email_msg = Blade::render($failed_email_template_details->message);

            $failed_sms_template_attempt_details = $s2s_response_mode->get_sms_details(array(array('type', '=', 'sip_auto_debit_failure'), array('is_on', '=', 'Y')));
            $failed_email_template_attempt_details = $s2s_response_mode->get_email_details(array(array('type', '=', 'sip_auto_debit_failure'), array('is_on', '=', 'Y')));

        	if($failed_email_template_attempt_details)
                $failed_email_attemp_msg = Blade::render($failed_email_template_attempt_details->message);


            if($data['success'] === true)
            {
                $receipt_no = NULL;

                if(!empty($contr_details->contr_receipt_no))
                {
                    $receipt_no = $contr_details->contr_receipt_no;
                }
                else
                {
                    if($contr_details->cra_account == 'CAMS')
                        $receipt_no = $cams_lib->get_receipt_no();
                    elseif($contr_details->cra_account == 'NSDL')
                        $receipt_no = $nsdl_lib->get_receipt_no();
                    elseif($contr_details->cra_account == 'KFINTECH')
                        $receipt_no = $kfintech_lib->get_receipt_no();
                }

                if(!empty($contr_details->contr_receipt_no))
                    $gst_invoice_no = $contr_details->gst_invoice_no;
                else
                    $gst_invoice_no = generate_gst_number();

                $rows_affected = $s2s_response_model->update_pg_details(
                    array(array('payment_trans_id', '=', $transaction_id ), array('contr_ref_id', '=', $contr_details->contr_id), array('contribution_type', '=', 'SIP')), 
                    array('gst_invoice_no' => $gst_invoice_no, 'payment_status' => 'A', 'instrument_reference' => $data['paymentDetail'][0]['instrumentReference'], 'instrument_date' => $data['paymentDetail'][0]['instrumentDate'], 'payment_compl_on' => time(), 'updated_on' => time())
                );
                $rows_affected = $s2s_response_model->update_contr_details_cycle_data(
                    array(array('payment_trans_id', '=', $transaction_id), array('contr_id', '=', $contr_details->contr_id)), 
                    array('gst_invoice_no' => $gst_invoice_no, 'mandate_status' => 'S', 'contr_cra_sync_status' => 'P', 'contr_receipt_no' => $receipt_no, 'updated_on' => time())
                );
            
                if($success_sms_template_details && !empty($contr_details->mobile_no))
                {
                    $mobile_no = '91'.Crypt::decryptString($contr_details->mobile_no);
                    $sms_response = $sms_gateway_lib->send_sms($mobile_no, $success_sms_template_details->message, $success_sms_template_details->template_id);
    
                    $sms_notification_log_data = array(
                        'contr_ref_id' => $contr_details->contr_id, 
                        'contribution_type' => 'SIP', 
                        'notification_type' => "Contribution Successful", 
                        'notification_source' => 'SMS',
                        'created_on' => time()
                    );

                    if($sms_response['status'] != 200)
                    {
                        $sms_notification_log_data['ref_id'] = NULL;
                        $sms_notification_log_data['status'] = 'F';
                    }
                    else
                    {
                        $sms_notification_log_data['ref_id'] = $sms_response['req_id'];
                        $sms_notification_log_data['status'] = 'S';
                    }
                    $inserted = $s2s_response_model->insert_notification_details($sms_notification_log_data);
                }

                if($success_email_template_details && !empty($contr_details->email))
                {
                    $email = Crypt::decryptString($contr_details->email);
                    $email_params = array(
                        'to' => $email,
                        'subject' => $success_email_template_details->subject,
                        'message' => $success_email_msg
                    );

                    $email_response = $email_lib->send_email($email_params);

                    $email_notification_log_data = array(
                        'contr_ref_id' => $contr_details->contr_id, 
                        'contribution_type' => 'SIP', 
                        'notification_type' => 'Contribution Successful', 
                        'notification_source' => 'EMAIL', 
                        'created_on' => time()
                    );

                    if($email_response['status'] != 200)
                    {
                        $email_notification_log_data['ref_id'] = NULL;
                        $email_notification_log_data['status'] = 'F';
                    }
                    else
                    {
                        $email_notification_log_data['ref_id'] = $email_response['message_id'];
                        $email_notification_log_data['status'] = 'S';
                    }

                    $inserted = $s2s_response_model->insert_notification_details($email_notification_log_data);
                }
            }
            else
            {
                $rows_affected = $s2s_response_model->update_pg_details(
                    array(array('payment_trans_id', '=', $transaction_id), array('contr_ref_id', '=', $contr_details->contr_id), array('contribution_type', '=', 'SIP')), 
                    array('payment_status' => 'F', 'updated_on' => time() ,  'payment_compl_on'=>time())
                );
                $rows_affected = $s2s_response_model->update_contr_details_cycle_data(
                    array(array('payment_trans_id', '=', $transaction_id), array('contr_id', '=', $contr_details->contr_id)), 
                    array('mandate_status' => 'F', 'updated_on' => time())
                );
            
                if(!is_null($contr_details->mandate_trigger3_on) && $contr_details->mandate_trigger3_on != 0 && $success_sms_template_details && !empty($contr_details->mobile_no))
                {
                    if($failed_sms_template_details && !empty($contr_details->mobile_no))
                    {
                        $mobile_no = '91'.Crypt::decryptString($contr_details->mobile_no);
                        $sms_response = $sms_gateway_lib->send_sms($mobile_no, $failed_sms_template_details->message, $failed_sms_template_details->template_id);
        
                        $sms_notification_log_data = array(
                            'contr_ref_id' => $contr_details->contr_id, 
                            'contribution_type' => 'SIP', 
                            'notification_type' => 'Contribution Failed', 
                            'notification_source' => 'SMS',
                            'created_on' => time()
                        );
    
                        if($sms_response['status'] != 200)
                        {
                            $sms_notification_log_data['ref_id'] = NULL;
                            $sms_notification_log_data['status'] = 'F';
                        }
                        else
                        {
                            $sms_notification_log_data['ref_id'] = $sms_response['req_id'];
                            $sms_notification_log_data['status'] = 'S';
                        }
                        $inserted = $s2s_response_model->insert_notification_details($sms_notification_log_data);
                    }
    
                    if($failed_email_template_details && !empty($contr_details->email) && $success_email_template_details && !empty($contr_details->email))
                    {
                        $email = Crypt::decryptString($contr_details->email);
                        $email_params = array(
                            'to' => $email,
                            'subject' => $failed_email_template_details->subject,
                            'message' => $success_email_msg
                        );
    
                        $email_response = $email_lib->send_email($email_params);
    
                        $email_notification_log_data = array(
                            'contr_ref_id' => $contr_details->contr_id, 
                            'contribution_type' => 'SIP', 
                            'notification_type' => 'Contribution Failed', 
                            'notification_source' => 'EMAIL', 
                            'created_on' => time()
                        );
    
                        if($email_response['status'] != 200)
                        {
                            $email_notification_log_data['ref_id'] = NULL;
                            $email_notification_log_data['status'] = 'F';
                        }
                        else
                        {
                            $email_notification_log_data['ref_id'] = $email_response['message_id'];
                            $email_notification_log_data['status'] = 'S';
                        }
    
                        $inserted = $s2s_response_model->insert_notification_details($email_notification_log_data);
                    }
                }

                if(is_null($contr_details->mandate_trigger3_on) || $contr_details->mandate_trigger3_on == 0){
                        if($failed_sms_template_attempt_details && !empty($contr_details->mobile_no) && ((is_null($contr_details->mandate_trigger2_on) || $contr_details->mandate_trigger2_on == 0) || (is_null($contr_details->mandate_trigger1_on) || $contr_details->mandate_trigger1_on == 0)))
                        {
                            $mobile_no = '91'.Crypt::decryptString($contr_details->mobile_no);
                            $sms_response_attemp1 = $sms_gateway_lib->send_sms($mobile_no, $failed_sms_template_attempt_details->message, $failed_sms_template_details->template_id, $contr_details->contr_id, 'Contribution Failed (Mandate Attempt 1)');
                        
                        	$sms_notification_log_data = array(
                            	'contr_ref_no' => $contr_details->contr_id, 
                            	'contribution_type' => 'SIP', 
                            	'notification_type' => 'Contribution Failed Mandate Reminder', 
                            	'notification_source' => 'SMS',
                            	'created_on' => time()
                        	);
    
                        	if($sms_response_attemp1['status'] != 200)
                        	{
                            	$sms_notification_log_data['ref_id'] = NULL;
                            	$sms_notification_log_data['status'] = 'F';
                        	}
                        	else
                        	{
                            	$sms_notification_log_data['ref_id'] = $sms_response_attemp1['req_id'];
                            	$sms_notification_log_data['status'] = 'S';
                        	}
                        	$inserted = $s2s_response_model->insert_notification_details($sms_notification_log_data);
                        }
                        if($failed_email_template_attempt_details && !empty($contr_details->email) &&((is_null($contr_details->mandate_trigger2_on) || $contr_details->mandate_trigger2_on == 0) || (is_null($contr_details->mandate_trigger1_on) || $contr_details->mandate_trigger1_on == 0)))
                        {
                            $email = array(array('email' => Crypt::decryptString($contr_details->email)));
                            $email_params = array(
                                'to' => $email,
                                'subject' => $failed_email_template_details->subject,
                                'message' => $failed_email_attemp_msg  // Email message for Mandate Trigger 1
                            );
                        	$email_response = $email_lib->send_email($email_params);
    
                        	$email_notification_log_data = array(
                            	'contr_ref_no' => $contr_id, 
                            	'contribution_type' => 'SIP', 
                            	'notification_type' => 'Contribution Failed Mandate Reminder', 
                            	'notification_source' => 'EMAIL', 
                            	'created_on' => time()
                        	);
    
                        	if($email_response['status'] != 200)
                        	{
                            	$email_notification_log_data['ref_id'] = NULL;
                            	$email_notification_log_data['status'] = 'F';
                        	}
                        	else
                        	{
                            	$email_notification_log_data['ref_id'] = $email_response['message_id'];
                            	$email_notification_log_data['status'] = 'S';
                        	}
                        	$inserted = $s2s_response_model->insert_notification_details($email_notification_log_data);
                        }
                }
                if(is_null($contr_details->mandate_trigger3_on) || $contr_details->mandate_trigger3_on == 0){
                    if($failed_sms_template_attempt_details && !empty($contr_details->mobile_no) && ((is_null($contr_details->mandate_trigger2_on) || $contr_details->mandate_trigger2_on == 0) || (is_null($contr_details->mandate_trigger1_on) || $contr_details->mandate_trigger1_on == 0)))
                    {
                        $mobile_no = '91'.Crypt::decryptString($contr_details->mobile_no);
                        $sms_response_attemp1 = $sms_gateway_lib->send_sms($mobile_no, $failed_sms_template_attempt_details->message, $failed_sms_template_details->template_id, $contr_details->contr_id, 'Contribution Failed (Mandate Attempt 1)');
                    
                        $sms_notification_log_data = array(
                            'contr_ref_no' => $contr_details->contr_id, 
                            'contribution_type' => 'SIP', 
                            'notification_type' => 'Contribution Failed Mandate Reminder', 
                            'notification_source' => 'SMS',
                            'created_on' => time()
                        );
                        if($sms_response_attemp1['status'] != 200)
                        {
                            $sms_notification_log_data['ref_id'] = NULL;
                            $sms_notification_log_data['status'] = 'F';
                        }
                        else
                        {
                            $sms_notification_log_data['ref_id'] = $sms_response_attemp1['req_id'];
                            $sms_notification_log_data['status'] = 'S';
                        }
                        $inserted = $s2s_response_model->insert_notification_details($sms_notification_log_data);
                    }
                    if($failed_email_template_attempt_details && !empty($contr_details->email) &&((is_null($contr_details->mandate_trigger2_on) || $contr_details->mandate_trigger2_on == 0) || (is_null($contr_details->mandate_trigger1_on) || $contr_details->mandate_trigger1_on == 0)))
                    {
                        $email = array(array('email' => Crypt::decryptString($contr_details->email)));
                        $email_params = array(
                            'to' => $email,
                            'subject' => $failed_email_template_details->subject,
                            'message' => $failed_email_attemp_msg  // Email message for Mandate Trigger 1
                        );
                        $email_response = $email_lib->send_email($email_params);

                        $email_notification_log_data = array(
                            'contr_ref_no' => $contr_id, 
                            'contribution_type' => 'SIP', 
                            'notification_type' => 'Contribution Failed Mandate Reminder', 
                            'notification_source' => 'EMAIL', 
                            'created_on' => time()
                        );

                        if($email_response['status'] != 200)
                        {
                            $email_notification_log_data['ref_id'] = NULL;
                            $email_notification_log_data['status'] = 'F';
                        }
                        else
                        {
                            $email_notification_log_data['ref_id'] = $email_response['message_id'];
                            $email_notification_log_data['status'] = 'S';
                        }
                        $inserted = $s2s_response_model->insert_notification_details($email_notification_log_data);
                    }
               }
            }
        }

        //Save in PG Log
        $inserted_id = $s2s_response_model->insert_pg_log(
            array(
                'contr_ref_id' => $contr_ref_id,
                'contribution_type' => 'SIP',
                'payment_trans_id' => $transaction_id, 
                'type' => 'AUTO DEBIT MANDATE S2S',
                'data' => json_encode($data), 
                'created_on' => time()
            )
        );
        
        return response()->json(['Success' => true, 'errors' => array("code"=> "00", "reason"=> "Success")]);
    }

	public function payment_response(Request $request, Insert_s2s_response_model $s2s_respose_model, Cams_lib $cams_lib, Nsdl_lib $nsdl_lib, Kfintech_lib $kfintech_lib)
    {
        if(!$request->hasHeader('x-jio-payload-auth') || empty($request->header('x-jio-payload-auth')))
            return response()->json(['Success' => false, 'errors' => array("code"=> "01", "reason"=> "Access Denied. Auth Token is Missing.")]);

        $data = json_decode(file_get_contents('php://input'), true);
        $headers = $request->headers->all();
        $data['headers'] = $headers;

        $x_jio_payload_auth = $headers['x-jio-payload-auth'];
        $x_jio_payload_auth = implode('', $x_jio_payload_auth); // convert array to string

        if ($data['success'] === true)
            $success = "true";
        else
            $success = "false";

        $transactionRefNumber = $data['transactionRefNumber'];
        $totalAmount = $data['totalAmount'];

        $transactionType = $data['transactionType'];
        $transaction_types = get_settings('payment_gateway', array('transaction_type'));

        $contribution_type = false;
        $type = false;
        $contr_ref_id = NULL;

        if($transactionType == $transaction_types['payment_gateway']['transaction_type']['tier1'] || $transactionType == $transaction_types['payment_gateway']['transaction_type']['tier2'])
        {
            $contribution_type = 'SUBSEQUENT';
            $type = 'SUBSEQUENT S2S';
        }
        elseif($transactionType == $transaction_types['payment_gateway']['transaction_type']['sip_tier1'] || $transactionType == $transaction_types['payment_gateway']['transaction_type']['sip_tier2'])
        {
            $contribution_type = 'SIP';
            $type = 'SIP S2S';
        }

        $log_data = array(
            'payment_trans_id' => $transactionRefNumber,
            'type' => $type,
            'data' => json_encode($data),
            'created_on' => time()
        );
        $all_s2s_log_id = DB::connection('icic_pop_txt')->table('all_pg_s2s_logs')->insertGetId($log_data);

        $message = $success . '|' . $transactionRefNumber . '|' . $totalAmount;

        $auth_token = $this->payment_gateway_lib->generate_auth_token($message);

        // if($x_jio_payload_auth != $auth_token)
        //     return response()->json(['Success' => false, 'errors' => array("code"=> "01", "reason"=> "Access Denied. Auth Token is Mismatching.")]);

        if($contribution_type && $type)
        {
            // Subsequent Flow
            if($contribution_type == 'SUBSEQUENT') 
            {
                $subscriber_details = $this->insert_s2s_response_model->get_subsequent_subscriber_details(array(array('t1.payment_trans_id', '=', $transactionRefNumber)));

                if($subscriber_details)
                    $contr_ref_id = $subscriber_details['sub_contr_id'];

                if($data['success'] === true)
                    $pg_status = "A";
                else
                    $pg_status = "F";

                $conditions = [['payment_trans_id', '=', $transactionRefNumber]];
                $update = [
                    'payment_status' => $pg_status,
                    'payment_compl_on' => time(),
                    'updated_on' => time()
                ];
            
                $gst_number = NULL;
                $gst_no_updated = false;
            
                if($data['success'] === true)
                {
                    $gst_number = generate_gst_number();

                    $instrumentReference = $data['paymentDetail'][0]['instrumentReference'];
                    $instrumentDate = $data['paymentDetail'][0]['instrumentDate'];
                    $update['instrument_reference'] = $instrumentReference;
                    $update['instrument_date'] = $instrumentDate;
                    if(($subscriber_details && (is_null($subscriber_details['gst_invoice_no']) || empty($subscriber_details['gst_invoice_no']))) || !$subscriber_details)
                    {
                        $update['gst_invoice_no'] = $gst_number;
                        $gst_no_updated = true;
                    }
                }
                else
                {
                    $update['instrument_reference'] = NULL;
                    $update['instrument_date'] = NULL;
                    $update['gst_invoice_no'] = NULL;
                }
                
                $update_pgtd = $this->insert_s2s_response_model->update_pg_transaction_details($conditions, $update);
                
                $conditions = [
                    ['payment_trans_id', '=', $transactionRefNumber]
                ];

                $update = [
                    'updated_on' => time()
                ];

                if($data['success'] === true && $subscriber_details)
                {
                    if($gst_no_updated === true || is_null($subscriber_details['gst_invoice_no']) || empty($subscriber_details['gst_invoice_no']))
                    {
                        $update['gst_invoice_no'] = $gst_number;
                    }

                    if(is_null($subscriber_details['contr_receipt_no']) || empty($subscriber_details['contr_receipt_no']))
                    {
                        if ($subscriber_details['cra_account'] == 'CAMS')
                            $receipt_no = $cams_lib->get_receipt_no();
                        elseif ($subscriber_details['cra_account'] == 'NSDL')
                            $receipt_no = $nsdl_lib->get_receipt_no();
                        elseif ($subscriber_details['cra_account'] == 'KFINTECH')
                            $receipt_no = $kfintech_lib->get_receipt_no();

                        $update['contr_receipt_no'] = $receipt_no;
                    }
                }
                elseif($data['success'] === false && $subscriber_details)
                {
                    $update['gst_invoice_no'] = NULL;
                    $update['contr_receipt_no'] = NULL;
                }

                $mode_of_payment = isset($data['paymentDetail'][0]['modeOfPaymentDesc']) ? $data['paymentDetail'][0]['modeOfPaymentDesc'] : null;
                $update['mode_of_payment'] = $mode_of_payment;

                $update_mcd = $this->insert_s2s_response_model->update_subsequent_contribution_details($conditions, $update);
            } 
            else if($contribution_type == 'SIP')
            {
                // SIP Flow    
                $contribution_cycle_details = $this->insert_s2s_response_model->get_contribution_details_cycle(array(array('payment_trans_id', '=', $transactionRefNumber)));
                if($contribution_cycle_details)
                    $contr_ref_id = $contribution_cycle_details['contr_id'];
                    
                if($data['success'] === true) 
                {
                    $pg_status = "A";
                    $payment_status = 'A';
                    $mandate_status = 'S';
                    $sip_status = 'A';
                    $instrumentReference = $data['paymentDetail'][0]['instrumentReference'];
                    $instrumentDate = $data['paymentDetail'][0]['instrumentDate'];
                } 
                else 
                {
                    $pg_status = "E";
                    $payment_status = 'F';
                    $mandate_status = 'F';
                    $sip_status = 'F';
                    $instrumentReference = NULL;
                    $instrumentDate = NULL;
                }

                $conditions = [['payment_trans_id', '=', $transactionRefNumber]];
            
                $gst_number = NULL;

                $update = [
                    'payment_status' => $payment_status,
                    'instrument_reference' => $instrumentReference,
                    'instrument_date' => $instrumentDate,
                    'payment_compl_on'=>time(),
                    'updated_on' => time()
                ];
                
                $gst_no_updated = false;

                if($data['success'] === true && $contribution_cycle_details && (is_null($contribution_cycle_details['gst_invoice_no']) || empty($contribution_cycle_details['gst_invoice_no'])))
                {
                    $gst_number = generate_gst_number();
                    $update['gst_invoice_no'] = $gst_number;
                    $gst_no_updated = true;
                }

                if($data['success'] === false)
                    $update['gst_invoice_no'] = NULL;

                $update_pgtd = $this->insert_s2s_response_model->update_pg_transaction_details($conditions, $update);
                
                $update = [
                    'mandate_status' => $mandate_status,
                    'updated_on' => time(),
                ];
            
                $subscriber_details = $this->insert_s2s_response_model->get_sip_subscriber_details(array(array('t1.pg_transaction_id', '=', $transactionRefNumber)));

                if($mandate_status == 'S')
                {
                    if($subscriber_details && $contribution_cycle_details)
                    {
                        if($gst_no_updated === true || is_null($contribution_cycle_details['gst_invoice_no']) || empty($contribution_cycle_details['gst_invoice_no']))
                        {
                            $update['gst_invoice_no'] = $gst_number;
                        }

                        if(is_null($contribution_cycle_details['contr_receipt_no']) || empty($contribution_cycle_details['contr_receipt_no']))
                        {
                            if ($subscriber_details['cra_account'] == 'CAMS')
                                $receipt_no = $cams_lib->get_receipt_no();
                            elseif ($subscriber_details['cra_account'] == 'NSDL')
                                $receipt_no = $nsdl_lib->get_receipt_no();
                            elseif ($subscriber_details['cra_account'] == 'KFINTECH')
                                $receipt_no = $kfintech_lib->get_receipt_no();

                            $update['contr_receipt_no'] = $receipt_no;
                        }
                    }
                }
                elseif($mandate_status == 'F')
                {
                    $update['gst_invoice_no'] = NULL;
                    $update['contr_receipt_no'] = NULL;
                }
            
                $update_cdc = $this->insert_s2s_response_model->update_contribution_details_cycle($conditions, $update);
            
                if($data['success'] === true) 
                {
                    $cdc_data = $this->insert_s2s_response_model->get_contribution_details_cycle($conditions);
                
                    $mcd_conditions = [
                        ['mast_sip_contr_id', '=', $cdc_data['mast_sip_contr_id']]
                    ];
                    
                    $mcd_data = $this->insert_s2s_response_model->get_master_contribution_details($mcd_conditions);
                    
                    if ($mcd_data['sip_frequency'] == 'Monthly') {   
                        $start_day = date('d', $mcd_data['start_date']); // Get the day of the month from start_date
                        $sip_date = strtotime($mcd_data['mandate_sip_date'] . date('M-Y', $cdc_data['sip_date']) . " +1 month");
                        if ($start_day > 15) {
                           
                            // Calculate the difference in seconds between sip_date and start_date
                            $sip_new_date = $sip_date - $mcd_data['start_date'];  // Difference in seconds
                            //print_r_custom($sip_new_date);die();
                            $diff_in_days = round($sip_new_date / (60 * 60 * 24));
                    
                            if ($diff_in_days < 20) {
                                $sip_date = strtotime($mcd_data['mandate_sip_date'] . date('M-Y', $cdc_data['sip_date']) . " +2 month");
                            } 
                        }
                    } else {
                        // If SIP frequency is not monthly, add 1 year to the mandate SIP date
                        $sip_date = strtotime($mcd_data['mandate_sip_date'] . date('Y', $cdc_data['sip_date']) . " +1 year");
                    }
        
                    
                    $check_cdc_data = $this->insert_s2s_response_model->get_contribution_details_cycle(array(array('mast_sip_contr_id', '=', $cdc_data['mast_sip_contr_id']), array('payment_trans_id', '=', NULL)));
                    if(!$check_cdc_data)
                    {
                        $state = false;
                        $address_details = $this->insert_s2s_response_model->get_state_address_details($subscriber_details['pran']);
                        if($subscriber_details['cra_account'] == 'CAMS' && $address_details)
                            $state = check_maha(Crypt::decryptString($address_details->full_address));
                        elseif($address_details && strtolower(Crypt::decryptString($address_details->address_state)) == 'maharashtra')
                            $state = true;
                        elseif(!empty($subscriber_details['custom_state']) && !is_null($subscriber_details['custom_state']) && strtolower(Crypt::decryptString($subscriber_details['custom_state'])) == 'maharashtra')
                            $state = true;
                    
                        $sip_amount_breakup = formula_matrix($mcd_data['sip_amount'], $state);
                    
                        $cycle_data = [
                            'mast_sip_contr_id' => $cdc_data['mast_sip_contr_id'],
                            'pran' => $cdc_data['pran'],
                            'full_name' => $cdc_data['full_name'],
                            'fname' => $cdc_data['fname'],
                            'mname' => $cdc_data['mname'],
                            'lname' => $cdc_data['lname'],
                            'tier_type' => $cdc_data['tier_type'],
                            'sip_amount' => $sip_amount_breakup['final_amount'],
                            'contribution_fees' => $sip_amount_breakup['contribution_charges'],
                            'sgst_amt' => $sip_amount_breakup['sgst'],
                            'cgst_amt' => $sip_amount_breakup['cgst'],
                            'gst_amt' => $sip_amount_breakup['gst_amt'],
                            'igst_amt' => $sip_amount_breakup['igst'],
                            'total_amount_received' => $sip_amount_breakup['sip_amount'],
                            'sip_date' => $sip_date,
                            'mandate_status' => 'P',
                            'payment_trans_id' => null
                        ];
                        $contr_cycle_id = $this->insert_s2s_response_model->insert_contribution_details_cycle($cycle_data);
                    }
                }

                $umrn = NULL;

                if($data['success'] === true && isset($data['mandateRespDetails']['umrn']) && !empty($data['mandateRespDetails']['umrn']))
                    $umrn = $data['mandateRespDetails']['umrn'];

                $conditions = [
                    ['pg_transaction_id', '=', $transactionRefNumber]
                ];
                $update = [
                    'pg_status' => $pg_status,
                    'umrn' => $umrn,
                    'sip_status' => $sip_status,
                    'sip_count' => 1,
                    'updated_on' => time(),
                ];

                if($pg_status == 'A')
                    $update['sip_count'] = 2;

                $mode_of_payment = isset($data['paymentDetail'][0]['modeOfPaymentDesc']) ? $data['paymentDetail'][0]['modeOfPaymentDesc'] : null;
                $update['mode_of_payment'] = $mode_of_payment;
                    
                $update_mcd = $this->insert_s2s_response_model->update_master_contribution_details($conditions, $update);
            }

            $record = Pops_pg_transaction_s2s::create([
                'contr_ref_id' => $contr_ref_id,
                'contribution_type' => $contribution_type,
                'payment_trans_id' => $data['transactionRefNumber'],
                'type' => $type,
                'data' => json_encode($data),
                'created_on' => time()
            ]);
        }

        return response()->json(['Success' => true, 'errors' => array("code" => "00", "reason" => "Okay")]);
    }
    
    public function refund_response(Request $request)
    {
        if(!$request->hasHeader('x-jio-payload-auth') || empty($request->header('x-jio-payload-auth')))
            return response()->json(['Success' => false, 'errors' => array("code"=> "01", "reason"=> "Access Denied. Auth Token is Missing.")]);

        if(!$request->has('success') || !$request->has('paymentDetail') || !$request->has('transactionRefNumber'))
            return response()->json(['Success' => false, 'errors' => array("code"=> "01", "reason"=> "Not Getting Required Fields in POST.")]);

        $data = json_decode(file_get_contents('php://input'), true);
        $headers = $request->headers->all();
        $data['headers'] = $headers;

        $transactionRefNumber = $data['transactionRefNumber'];

        $log_data = array(
            'payment_trans_id' => $transactionRefNumber,
            'type' => 'REFUND S2S',
            'data' => json_encode($data),
            'created_on' => time()
        );
        $all_s2s_log_id = DB::connection('icic_pop_txt')->table('all_pg_s2s_logs')->insertGetId($log_data);

        $totalAmount = $data['paymentDetail']['totalAmount'];
        $instrumentReference = $data['paymentDetail']['instrumentReference'];
        $instrumentDate = $data['paymentDetail']['instrumentDate'];

        $x_jio_payload_auth = $headers['x-jio-payload-auth'];
        $x_jio_payload_auth = implode('', $x_jio_payload_auth); // convert array to string

        $message = $transactionRefNumber . '|' . $totalAmount . '|' . $instrumentReference . '|' . $instrumentDate;
        $auth_token = $this->payment_gateway_lib->generate_auth_token($message);

        if($x_jio_payload_auth != $auth_token)
            return response()->json(['Success' => false, 'errors' => array("code"=> "01", "reason"=> "Access Denied. Auth Token is Mismatching.")]);

        if($data['success'] === true) 
            $pg_refund_status = "RS";
        else 
            $pg_refund_status = "RF";

        $conditions = array(array('refund_trans_id', '=', $transactionRefNumber));
        $update = array(
            'refund_status' => $pg_refund_status,
            'refund_compl_on' => time()
        );

        $update_pgtd = $this->insert_s2s_response_model->update_pg_transaction_details($conditions, $update);

        $payment_details = $this->insert_s2s_response_model->get_payment_details(array(array('refund_trans_id', '=', $transactionRefNumber)));
        
        $record = Pops_pg_transaction_s2s::create([
            'contr_ref_id' => $payment_details->contr_ref_id,
            'contribution_type' => $payment_details->contribution_type,
            'payment_trans_id' => $data['transactionRefNumber'],
            'type' => 'REFUND S2S',
            'data' => json_encode($data),
            'created_on' => time()
        ]);   
        
        return response()->json(['Success' => true, 'errors' => array("code" => "00", "reason" => "okay")]);
    }
}
?>