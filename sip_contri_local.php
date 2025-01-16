<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Models\Cron\Sip_contributions_model;
use App\Models\Db_2\Cron\Contribution_details_model;
use Illuminate\Support\Facades\Crypt;
use App\Libraries\Payment_gateway_lib;
use App\Libraries\Cams_lib;
use App\Libraries\Nsdl_lib;
use App\Libraries\Kfintech_lib;
use App\Helpers\EncryptHelper;
use App\Libraries\Sms_gateway_lib;
use App\Libraries\Email_lib;
use Illuminate\Support\Facades\Blade;

class Sip_contributions extends Controller
{
    private $sip_contributions_model;
    private $time;
    private $sms_gateway_lib;
    private $email_lib;

    public function __construct(Sip_contributions_model $sip_contributions_model, Sms_gateway_lib $sms_gateway_lib, Email_lib $email_lib)
    {
        $this->sip_contributions_model = $sip_contributions_model;
        $this->time = time();
        $this->sms_gateway_lib = $sms_gateway_lib;
        $this->email_lib = $email_lib;
    }

    public function create_users_upcoming_sips()
    {
        $cron_log_data = array(
            'cron_name' => 'create_users_upcoming_sip_entries',
            'cron_label' => "Create Users Upcoming SIP Entrie's",
            'cron_time' => time(),
            'num_data_to_process' => 0,
            'num_data_processed' => 0,
            'num_data_failed_to_process' => 0,
            'reference_details' => array(),
            'comment_msg' => NULL
        );

        $users_upcoming_sip_list = $this->sip_contributions_model->get_users_upcoming_sip_details(5000);

        if(empty($users_upcoming_sip_list))
        {
            //Adding Cron Log
            $cron_log_data['comment_msg'] = 'No Data To Process.';

            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);
        }
        else
        {
            foreach($users_upcoming_sip_list as $row)
            {
                $sip_date;
                if($row->sip_frequency == 'Monthly')
                {
                    $last_sip_date = date('d-M-Y', ((is_null($row->last_sip_date)) ? $row->start_date : $row->last_sip_date ));
                    $last_sip_mandate_date = $row->mandate_sip_date."-".date('M-Y', $last_sip_date);
                    $sip_date = strtotime($row->mandate_sip_date."-".date('M-Y', strtotime($last_sip_mandate_date.' +1 month')));
                
                    // If the calculated SIP date is in the past, move to the next month
                    if($sip_date <= strtotime(date('d-M-Y')." +2 days")) 
                    {
                        $sip_date = strtotime($row->mandate_sip_date."-".date('M-Y', strtotime($last_sip_mandate_date.' +2 month')));
                        if($sip_date <= strtotime(date('d-M-Y')." +2 days"))
                        {
                            $sip_date = strtotime($row->mandate_sip_date."-".date('M-Y'));

                            if($sip_date <= strtotime(date('d-M-Y')." +2 days"))
                                $sip_date = strtotime($row->mandate_sip_date."-".date('M-Y', strtotime(date('d-M-Y').' +1 month')));
                        }
                    }
                }
                elseif($row->sip_frequency == 'Yearly')
                {
                    $last_sip_date = date('d-M-Y', ((is_null($row->last_sip_date)) ? $row->start_date : $row->last_sip_date ));
                    $last_sip_mandate_date = $row->mandate_sip_date."-".date('Y', $last_sip_date);
                    $sip_date = strtotime($row->mandate_sip_date."-".date('Y', strtotime($last_sip_mandate_date.' +1 year')));
                
                    // If the calculated SIP date is in the past, move to the next year
                    if($sip_date <= strtotime(date('d-M-Y')." +2 days")) 
                    {
                        $sip_date = strtotime($row->mandate_sip_date."-".date('Y', strtotime($last_sip_mandate_date.' +2 year')));
                        if($sip_date <= strtotime(date('d-M-Y')." +2 days"))
                        {
                            $sip_date = strtotime($row->mandate_sip_date."-".date('Y'));

                            if($sip_date <= strtotime(date('d-M-Y')." +2 days"))
                                $sip_date = strtotime($row->mandate_sip_date."-".date('Y', strtotime(date('d-M-Y').' +1 year')));
                        }
                    }
                }
                else
                {
                    //Cron Log
                    $cron_log_data['num_data_failed_to_process'] += 1;

                    if(!isset($cron_log_data['reference_details']['Failed']))
                        $cron_log_data['reference_details']['Failed'] = array();
                    
                    $cron_log_data['reference_details']['Failed'][] = array(
                        'pran' => $row->pran,
                        'reference_id' => $row->mast_sip_contr_id,
                        'error_msg' => 'Failed to create upcoming sip entry. Unknown SIP Frequency.'
                    );

                    continue;
                }
    
                $state = false;
                $address_details = $this->sip_contributions_model->get_state_address_details($row->pran);
                if($row->cra_account == 'CAMS' && $address_details && !empty($address_details->full_address))
                    $state = check_maha(Crypt::decryptString($address_details->full_address));
                elseif($address_details && !empty($address_details->address_state) && strtolower(Crypt::decryptString($address_details->address_state)) == 'maharashtra')
                    $state = true;
                elseif(!empty($row->custom_state) && !is_null($row->custom_state) && strtolower(Crypt::decryptString($row->custom_state)) == 'maharashtra')
                    $state = true;
    
                $calculated_matrix_data = formula_matrix(floatval($row->sip_amount), $state);
    
                $new_upcoming_sip_data = array(
                    'mast_sip_contr_id' => $row->mast_sip_contr_id,
                    'pran' => $row->pran,
                    'full_name' => $row->full_name,
                    'fname' => $row->fname,
                    'mname' => $row->mname,
                    'lname' => $row->lname,
                    'mobile_no' => $row->mobile_no,
                    'email' => $row->email,
                    'tier_type' => $row->tier_type,
                    'sip_amount' => $calculated_matrix_data['final_amount'], 
                    'registration_fees' => 0, 
                    'contribution_fees' => $calculated_matrix_data['contribution_charges'], 
                    'gst_amt' => $calculated_matrix_data['gst_amt'], 
                    'igst_amt' => $calculated_matrix_data['igst'], 
                    'sgst_amt' => $calculated_matrix_data['sgst'], 
                    'cgst_amt' => $calculated_matrix_data['cgst'], 
                    'bank_charges' => 0, 
                    'total_amount_received' => $calculated_matrix_data['sip_amount'], 
                    'mandate_trigger1_on' => NULL, 
                    'mandate_trigger2_on' => NULL, 
                    'mandate_trigger3_on' => NULL, 
                    'sip_date' => $sip_date,
                    'mandate_status' => 'P',
                    'contr_receipt_no' => NULL,
                    'contr_cra_trans_id' => NULL,
                    'contr_cra_sync_status' => NULL,
                    'contr_cra_sync_on' => NULL,
                    'payment_trans_id' => NULL,
                    'created_on' => $this->time
                );
    
                $contr_id = $this->sip_contributions_model->insert_user_upcoming_sip_details($new_upcoming_sip_data);
                if($contr_id)
                {
                    $updated = $this->sip_contributions_model->update_user_sip_count($row->mast_sip_contr_id, ($row->sip_count + 1));
                    $log_data = array('data' => json_encode(array('reference_id' => $contr_id, 'action_type' => 'Created Upcoming SIP Entry.', 'cron' => "Create Upcoming SIP's.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                    add_automated_processes_action_logs($log_data);

                    if(!isset($cron_log_data['reference_details']['Success']))
                        $cron_log_data['reference_details']['Success'] = array();
                    
                    $cron_log_data['reference_details']['Success'][] = array(
                        'pran' => $row->pran,
                        'reference_id' => $row->mast_sip_contr_id
                    );

                    $cron_log_data['num_data_processed'] += 1;
                }
                else
                {
                    if(!isset($cron_log_data['reference_details']['Failed']))
                        $cron_log_data['reference_details']['Failed'] = array();
                    
                    $cron_log_data['reference_details']['Failed'][] = array(
                        'pran' => $row->pran,
                        'reference_id' => $row->mast_sip_contr_id,
                        'error_msg' => 'Failed to create upcoming sip entry. Facing issue in inserting data to database.'
                    );

                    $cron_log_data['num_data_failed_to_process'] += 1;
                }
            }

            //Adding Cron Log
            $cron_log_data['num_data_to_process'] = count($users_upcoming_sip_list);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
                $cron_log_data['comment_msg'] = 'Out of '.$cron_log_data['num_data_to_process'].' only '.$cron_log_data['num_data_processed'].' were processed successfully. Failed to process '.$cron_log_data['num_data_failed_to_process'].'.';
            else
                $cron_log_data['comment_msg'] = 'All Data Processed Successfully.';
            
            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);
        }
    }

    public function trigger_pg_mandate_debit_sips(Payment_gateway_lib $payment_gateway_lib)
    {
        $cron_log_data = array(
            'cron_name' => 'trigger_upcoming_sip_mandate_autodebit',
            'cron_label' => "Trigger Mandate Autodebit for Upcoming SIP's (1st Attempt)",
            'cron_time' => time(),
            'num_data_to_process' => 0,
            'num_data_processed' => 0,
            'num_data_failed_to_process' => 0,
            'reference_details' => array(),
            'comment_msg' => NULL
        );

        $users_sip_list = $this->sip_contributions_model->get_users_tomorrows_sips_list(500);
        
        if(empty($users_sip_list))
        {
            //Adding Cron Log
            $cron_log_data['comment_msg'] = 'No Data To Process.';

            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);
        }
        else
        {
            $failed_sms_template_details = $this->sip_contributions_model->get_sms_details(array(array('type', '=', 'failed_contribution'), array('is_on', '=', 'Y')));
            $failed_email_template_details = $this->sip_contributions_model->get_email_details(array(array('type', '=', 'failed_contribution'), array('is_on', '=', 'Y')));
        	$failed_sms_template_attempt_details = $this->sip_contributions_model->get_sms_details(array(array('type', '=', 'sip_auto_debit_failure'), array('is_on', '=', 'Y')));
            $failed_email_template_attempt_details = $this->sip_contributions_model->get_email_details(array(array('type', '=', 'sip_auto_debit_failure'), array('is_on', '=', 'Y')));


            if($failed_email_template_details)
                $failed_email_msg = Blade::render($failed_email_template_details->message);
        	if($failed_email_template_attempt_details)
                $failed_email_attemp_msg = Blade::render($failed_email_template_attempt_details->message);

            foreach($users_sip_list as $row)
            {
                $transaction_ref_no = $payment_gateway_lib->generate_transaction_ref_no();

                $mobile_no = (!empty($row->mobile_no) ? Crypt::decryptString($row->mobile_no) : false);
                $email = (!empty($row->email) ? Crypt::decryptString($row->email) : false);

                $state = false;
                $address_details = $this->sip_contributions_model->get_state_address_details($row->pran);
                if($row->cra_account == 'CAMS' && $address_details && !empty($address_details->full_address))
                    $state = check_maha(Crypt::decryptString($address_details->full_address));
                elseif($address_details && !empty($address_details->address_state) && strtolower(Crypt::decryptString($address_details->address_state)) == 'maharashtra')
                    $state = true;
                elseif(!empty($row->custom_state) && !is_null($row->custom_state) && strtolower(Crypt::decryptString($row->custom_state)) == 'maharashtra')
                    $state = true;

                $calculated_matrix_data = formula_matrix(floatval($row->sip_amount), $state);

                $contr_details = array(
                    'contribution_fees' => $calculated_matrix_data['contribution_charges'], 
                    'gst_amt' => $calculated_matrix_data['gst_amt'], 
                    'igst_amt' => $calculated_matrix_data['igst'], 
                    'sgst_amt' => $calculated_matrix_data['sgst'], 
                    'cgst_amt' => $calculated_matrix_data['cgst'], 
                    'sip_amount' => $calculated_matrix_data['final_amount'], 
                    'total_amount_received' => $calculated_matrix_data['sip_amount'], 
                    'updated_on' => $this->time
                );

                if(is_null($row->mandate_trigger1_on) || $row->mandate_trigger1_on == 0)
                    $contr_details['mandate_trigger1_on'] = time();
                elseif(is_null($row->mandate_trigger2_on) || $row->mandate_trigger2_on == 0)
                    $contr_details['mandate_trigger2_on'] = time();
                elseif(is_null($row->mandate_trigger3_on) || $row->mandate_trigger3_on == 0)
                    $contr_details['mandate_trigger3_on'] = time();

                $pg_response = $payment_gateway_lib->mandate_auto_debit_payment($row->tier_type, $row->umrn, $transaction_ref_no, number_format(floatval($calculated_matrix_data['final_amount']), 2, '.', ''), $mobile_no, $email);

                if($pg_response['http_status'] == 200 && $pg_response['api_response']['code'] == 200 && $pg_response['api_response']['data']['status'] == 1)
                {
                    $contr_details['payment_trans_id']  = $transaction_ref_no;
                    $contr_details['mandate_status'] = 'IP';

                    $inserted_id = $this->sip_contributions_model->insert_pg_details(array('contr_ref_id' => $row->contr_id, 'contribution_type' => 'SIP', 'pran' => $row->pran, 'payment_type' => 'MD', 'payment_status' => 'I', 'payment_trans_id' => $transaction_ref_no, 'payment_init_on' => time(), 'created_on' => time()));
                    $rows_affected = $this->sip_contributions_model->update_contr_details_cycle_data(array(array('contr_id', '=', $row->contr_id)), $contr_details);
                    $pg_log_inserted = $this->sip_contributions_model->insert_pg_log(
                        array(
                            'contr_ref_id' => $row->contr_id, 
                            'contribution_type' => 'SIP',
                            'payment_trans_id' => $transaction_ref_no, 
                            'type' => 'AUTO DEBIT MANDATE B2B',
                            'data' => json_encode($pg_response['api_response']), 
                            'created_on' => time()
                        )
                    );

                    $log_data = array('data' => json_encode(array('reference_id' => $row->contr_id, 'action_type' => 'SIP Auto Debit Mandate Payment Initiated.', 'comment' => 'For Reference PG Trans ID:'.$inserted_id.'.', 'cron' => "Trigger Auto Debit Mandate for Tomorrow's SIP's.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                    add_automated_processes_action_logs($log_data);

                    if(!isset($cron_log_data['reference_details']['Success']))
                        $cron_log_data['reference_details']['Success'] = array();
                    
                    $cron_log_data['reference_details']['Success'][] = array(
                        'pran' => $row->pran,
                        'reference_id' => $row->contr_id
                    );

                    $cron_log_data['num_data_processed'] += 1;
                }
                elseif($pg_response['http_status'] == 200 && ($pg_response['api_response']['code'] != 200 || $pg_response['api_response']['data']['status'] != 1))
                {
                    $contr_details['payment_trans_id'] = NULL;
                    $contr_details['mandate_status'] = 'P';
                    if(isset($contr_details['mandate_trigger3_on']))
                        $contr_details['mandate_status'] = 'F';

                    $rows_affected = $this->sip_contributions_model->update_contr_details_cycle_data(array(array('contr_id', '=', $row->contr_id)), $contr_details);
                    $pg_log_inserted = $this->sip_contributions_model->insert_pg_log(
                        array(
                            'contr_ref_id' => $row->contr_id, 
                            'contribution_type' => 'SIP',
                            'payment_trans_id' => $transaction_ref_no, 
                            'type' => 'AUTO DEBIT MANDATE B2B',
                            'data' => json_encode($pg_response['api_response']), 
                            'created_on' => time()
                        )
                    );

                    if(isset($contr_details['mandate_trigger3_on']))
                    {
                        if($failed_sms_template_details && !empty($row->mobile_no))
                        {
                            $mobile_no = '91'.Crypt::decryptString($row->mobile_no);
                            $sent_sms = $this->handle_send_sms($mobile_no, $failed_sms_template_details->message, $failed_sms_template_details->template_id, $row->contr_id, 'Contribution Failed (Mandate Attempt)');
                        }

                        if($failed_email_template_details && !empty($row->email))
                        {
                            $email = array(array('email' => Crypt::decryptString($row->email)));
                            $params = array(
                                'to' => $email,
                                'subject' => $failed_email_template_details->subject,
                                'message' => $failed_email_msg
                            );

                            $sent_email = $this->handle_send_email($params, $row->contr_id, 'Contribution Failed (Mandate Attempt)');
                        }
                    }
                	
                	if(!isset($contr_details['mandate_trigger3_on'])){
                        if($failed_sms_template_attempt_details && !empty($row->mobile_no)  && ((is_null($row->mandate_trigger1_on) || $row->mandate_trigger1_on == 0) || (is_null($row->mandate_trigger2_on) || $row->mandate_trigger2_on == 0)))
                        {
                            $mobile_no = '91'.Crypt::decryptString($row->mobile_no);
                            $sent_sms = $this->handle_send_sms($mobile_no, $failed_sms_template_attempt_details->message, $failed_sms_template_details->template_id, $row->contr_id, 'Contribution Failed Mandate Reminder');
                        }
                        if($failed_email_template_attempt_details && !empty($row->email) && ((is_null($row->mandate_trigger1_on) || $row->mandate_trigger1_on == 0)|| (is_null($row->mandate_trigger2_on) || $row->mandate_trigger2_on == 0)))
                        {
                            $email = array(array('email' => Crypt::decryptString($row->email)));
                            $params = array(
                                'to' => $email,
                                'subject' => $failed_email_template_details->subject,
                                'message' => $failed_email_attemp_msg  // Email message for Mandate Trigger 1
                            );
                            $sent_email = $this->handle_send_email($params, $row->contr_id, 'Contribution Failed Mandate Reminder');
                        }
                    }

                    $log_data = array('data' => json_encode(array('reference_id' => $row->contr_id, 'action_type' => 'Failed to Initiate SIP Auto Debit Mandate Payment.', 'comment' => 'Error:'.$pg_response['api_response']['message'], 'cron' => "Trigger Auto Debit Mandate for Tomorrow's SIP's.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                    add_automated_processes_action_logs($log_data);

                    if(!isset($cron_log_data['reference_details']['Failed']))
                        $cron_log_data['reference_details']['Failed'] = array();
                    
                    $cron_log_data['reference_details']['Failed'][] = array(
                        'pran' => $row->pran,
                        'reference_id' => $row->contr_id,
                        'error_msg' => 'Failed to trigger autodebit mandate. Getting error from payment gateway: '.$pg_response['api_response']['message'].'.'
                    );

                    $cron_log_data['num_data_failed_to_process'] += 1;
                }
                else
                {
                    if(!isset($cron_log_data['reference_details']['Failed']))
                        $cron_log_data['reference_details']['Failed'] = array();
                    
                    $cron_log_data['reference_details']['Failed'][] = array(
                        'pran' => $row->pran,
                        'reference_id' => $row->contr_id,
                        'error_msg' => 'Failed to trigger autodebit mandate. Getting unexpected error.'
                    );

                    $cron_log_data['num_data_failed_to_process'] += 1;
                }
            }

            //Adding Cron Log
            $cron_log_data['num_data_to_process'] = count($users_sip_list);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
                $cron_log_data['comment_msg'] = 'Out of '.$cron_log_data['num_data_to_process'].' only '.$cron_log_data['num_data_processed'].' were processed successfully. Failed to process '.$cron_log_data['num_data_failed_to_process'].'.';
            else
                $cron_log_data['comment_msg'] = 'All Data Processed Successfully.';
            
            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
            {
                $email_details = $this->sip_contributions_model->get_email_details(array('type' => 'cron_error_alert_email'));
                $email_data = array(
                    'message' => $cron_log_data['comment_msg'],
                    'error_msg' => 'Failed Entires Details For Reference: </br>'
                );

                $cron_log_data['reference_details'] = json_decode($cron_log_data['reference_details'], true);
                foreach($cron_log_data['reference_details']['Failed'] as $key => $row)
                {
                    $email_data['error_msg'] .= ($key + 1).'. PRAN: '.$row['pran'].' Reference ID: '.$row['reference_id'].' Error: '.$row['error_msg'].'</br>';
                }

                $email_msg = Blade::render($email_details->message, $email_data);
                $params = array(
                    'to' => config('constants.CRON_ERROR_LOG_EMAIL_TO'),
                    'subject' => "Cron Failed to Trigger Mandate Autodebit for Upcoming SIP's (1st Attempt)",
                    'message' => $email_msg
                );

                $this->email_lib->send_email($params);
            }
        }
    }
    
    public function trigger_pg_mandate_debit_past_sips(Payment_gateway_lib $payment_gateway_lib)
    {
        $cron_log_data = array(
            'cron_name' => 'trigger_past_sip_mandate_autodebit',
            'cron_label' => "Trigger Mandate Autodebit for Upcoming SIP's (2nd, 3rd Attempt or Past SIP)",
            'cron_time' => time(),
            'num_data_to_process' => 0,
            'num_data_processed' => 0,
            'num_data_failed_to_process' => 0,
            'reference_details' => array(),
            'comment_msg' => NULL
        );

        $users_sip_list = $this->sip_contributions_model->get_users_past_pending_sips_list(500);
        
        if(empty($users_sip_list))
        {
            //Adding Cron Log
            $cron_log_data['comment_msg'] = 'No Data To Process.';

            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);
        }
        else
        {
            $failed_sms_template_details = $this->sip_contributions_model->get_sms_details(array(array('type', '=', 'failed_contribution'), array('is_on', '=', 'Y')));
            $failed_email_template_details = $this->sip_contributions_model->get_email_details(array(array('type', '=', 'failed_contribution'), array('is_on', '=', 'Y')));
        	$failed_sms_template_attempt_details = $this->sip_contributions_model->get_sms_details(array(array('type', '=', 'sip_auto_debit_failure'), array('is_on', '=', 'Y')));
            $failed_email_template_attempt_details = $this->sip_contributions_model->get_email_details(array(array('type', '=', 'sip_auto_debit_failure'), array('is_on', '=', 'Y')));


            if($failed_email_template_details)
                $failed_email_msg = Blade::render($failed_email_template_details->message);
        	if($failed_email_template_attempt_details)
                $failed_email_attemp_msg = Blade::render($failed_email_template_attempt_details->message);

            foreach($users_sip_list as $row)
            {
                $transaction_ref_no = $payment_gateway_lib->generate_transaction_ref_no();
                
                $mobile_no = (!empty($row->mobile_no) ? Crypt::decryptString($row->mobile_no) : false);
                $email = (!empty($row->email) ? Crypt::decryptString($row->email) : false);

                $state = false;
                $address_details = $this->sip_contributions_model->get_state_address_details($row->pran);
                if($row->cra_account == 'CAMS' && $address_details && !empty($address_details->full_address))
                    $state = check_maha(Crypt::decryptString($address_details->full_address));
                elseif($address_details && !empty($address_details->address_state) && strtolower(Crypt::decryptString($address_details->address_state)) == 'maharashtra')
                    $state = true;
                elseif(!empty($row->custom_state) && !is_null($row->custom_state) && strtolower(Crypt::decryptString($row->custom_state)) == 'maharashtra')
                    $state = true;

                $calculated_matrix_data = formula_matrix(floatval($row->sip_amount), $state);

                $contr_details = array(
                   'contribution_fees' => $calculated_matrix_data['contribution_charges'], 
                    'gst_amt' => $calculated_matrix_data['gst_amt'], 
                    'igst_amt' => $calculated_matrix_data['igst'], 
                    'sgst_amt' => $calculated_matrix_data['sgst'], 
                    'cgst_amt' => $calculated_matrix_data['cgst'], 
                    'sip_amount' => $calculated_matrix_data['final_amount'], 
                    'total_amount_received' => $calculated_matrix_data['sip_amount'], 
                    'updated_on' => $this->time
                );

                if(is_null($row->mandate_trigger1_on) || $row->mandate_trigger1_on == 0)
                    $contr_details['mandate_trigger1_on'] = time();
                elseif(is_null($row->mandate_trigger2_on) || $row->mandate_trigger2_on == 0)
                    $contr_details['mandate_trigger2_on'] = time();
                elseif(is_null($row->mandate_trigger3_on) || $row->mandate_trigger3_on == 0)
                    $contr_details['mandate_trigger3_on'] = time();

                $pg_response = $payment_gateway_lib->mandate_auto_debit_payment($row->tier_type, $row->umrn, $transaction_ref_no, number_format(floatval($calculated_matrix_data['final_amount']), 2, '.', ''), $mobile_no, $email);

                if($pg_response['http_status'] == 200 && $pg_response['api_response']['code'] == 200 && $pg_response['api_response']['data']['status'] == 1)
                {
                    $contr_details['payment_trans_id']  = $transaction_ref_no;
                    $contr_details['mandate_status'] = 'IP';

                    $inserted_id = $this->sip_contributions_model->insert_pg_details(array('contr_ref_id' => $row->contr_id, 'contribution_type' => 'SIP', 'pran' => $row->pran, 'payment_type' => 'MD', 'payment_status' => 'I', 'payment_trans_id' => $transaction_ref_no, 'payment_init_on' => time(), 'created_on' => time()));
                    $rows_affected = $this->sip_contributions_model->update_contr_details_cycle_data(array(array('contr_id', '=', $row->contr_id)), $contr_details);
                    $pg_log_inserted = $this->sip_contributions_model->insert_pg_log(
                        array(
                            'contr_ref_id' => $row->contr_id, 
                            'contribution_type' => 'SIP',
                            'payment_trans_id' => $transaction_ref_no, 
                            'type' => 'AUTO DEBIT MANDATE B2B',
                            'data' => json_encode($pg_response['api_response']), 
                            'created_on' => time()
                        )
                    );

                    $log_data = array('data' => json_encode(array('reference_id' => $row->contr_id, 'action_type' => 'SIP Auto Debit Mandate Payment Initiated.', 'comment' => 'For Reference PG Trans ID:'.$inserted_id.'.', 'cron' => "Trigger Auto Debit Mandate for Past Pending SIP's.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                    add_automated_processes_action_logs($log_data);

                    if(!isset($cron_log_data['reference_details']['Success']))
                        $cron_log_data['reference_details']['Success'] = array();
                    
                    $cron_log_data['reference_details']['Success'][] = array(
                        'pran' => $row->pran,
                        'reference_id' => $row->contr_id
                    );

                    $cron_log_data['num_data_processed'] += 1;
                }
                elseif($pg_response['http_status'] == 200 && ($pg_response['api_response']['code'] != 200 || $pg_response['api_response']['data']['status'] != 1))
                {
                    $contr_details['payment_trans_id'] = NULL;
                    $contr_details['mandate_status'] = 'P';
                    if(isset($contr_details['mandate_trigger3_on']))
                        $contr_details['mandate_status'] = 'F';
                    
                    $rows_affected = $this->sip_contributions_model->update_contr_details_cycle_data(array(array('contr_id', '=', $row->contr_id)), $contr_details);
                    $pg_log_inserted = $this->sip_contributions_model->insert_pg_log(
                        array(
                            'contr_ref_id' => $row->contr_id, 
                            'contribution_type' => 'SIP',
                            'payment_trans_id' => $transaction_ref_no, 
                            'type' => 'AUTO DEBIT MANDATE B2B',
                            'data' => json_encode($pg_response['api_response']), 
                            'created_on' => time()
                        )
                    );

                    if(isset($contr_details['mandate_trigger3_on']))
                    {
                        if($failed_sms_template_details && !empty($row->mobile_no))
                        {
                            $mobile_no = '91'.Crypt::decryptString($row->mobile_no);
                            $sent_sms = $this->handle_send_sms($mobile_no, $failed_sms_template_details->message, $failed_sms_template_details->template_id, $row->contr_id, 'Contribution Failed (Mandate Attempt)');
                        }

                        if($failed_email_template_details && !empty($row->email))
                        {
                            $email = array(array('email' => Crypt::decryptString($row->email)));
                            $params = array(
                                'to' => $email,
                                'subject' => $failed_email_template_details->subject,
                                'message' => $failed_email_msg
                            );

                            $sent_email = $this->handle_send_email($params, $row->contr_id, 'Contribution Failed (Mandate Attempt)');
                        }
                    }

                    if(!isset($contr_details['mandate_trigger3_on'])){
                        if($failed_sms_template_attempt_details && !empty($row->mobile_no)  && ((is_null($row->mandate_trigger1_on) || $row->mandate_trigger1_on == 0) || (is_null($row->mandate_trigger2_on) || $row->mandate_trigger2_on == 0)))
                        {
                            $mobile_no = '91'.Crypt::decryptString($row->mobile_no);
                            $sent_sms = $this->handle_send_sms($mobile_no, $failed_sms_template_attempt_details->message, $failed_sms_template_details->template_id, $row->contr_id, 'Contribution Failed Mandate Reminder');
                        }
                        if($failed_email_template_attempt_details && !empty($row->email) && ((is_null($row->mandate_trigger1_on) || $row->mandate_trigger1_on == 0)|| (is_null($row->mandate_trigger2_on) || $row->mandate_trigger2_on == 0)))
                        {
                            $email = array(array('email' => Crypt::decryptString($row->email)));
                            $params = array(
                                'to' => $email,
                                'subject' => $failed_email_template_details->subject,
                                'message' => $failed_email_attemp_msg  // Email message for Mandate Trigger 1
                            );
                            $sent_email = $this->handle_send_email($params, $row->contr_id, 'Contribution Failed Mandate Reminder');
                        }
                    }

                    $log_data = array('data' => json_encode(array('reference_id' => $row->contr_id, 'action_type' => 'Failed to Initiate SIP Auto Debit Mandate Payment.', 'comment' => 'Error:'.$pg_response['api_response']['message'], 'cron' => "Trigger Auto Debit Mandate for Past Pending SIP's.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                    add_automated_processes_action_logs($log_data);

                    if(!isset($cron_log_data['reference_details']['Failed']))
                        $cron_log_data['reference_details']['Failed'] = array();
                    
                    $cron_log_data['reference_details']['Failed'][] = array(
                        'pran' => $row->pran,
                        'reference_id' => $row->contr_id,
                        'error_msg' => 'Failed to trigger autodebit mandate. Getting error from payment gateway: '.$pg_response['api_response']['message'].'.'
                    );

                    $cron_log_data['num_data_failed_to_process'] += 1;
                }
                else
                {
                    if(!isset($cron_log_data['reference_details']['Failed']))
                        $cron_log_data['reference_details']['Failed'] = array();
                    
                    $cron_log_data['reference_details']['Failed'][] = array(
                        'pran' => $row->pran,
                        'reference_id' => $row->contr_id,
                        'error_msg' => 'Failed to trigger autodebit mandate. Getting unexpected error.'
                    );

                    $cron_log_data['num_data_failed_to_process'] += 1;
                }
            }

            //Adding Cron Log
            $cron_log_data['num_data_to_process'] = count($users_sip_list);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
                $cron_log_data['comment_msg'] = 'Out of '.$cron_log_data['num_data_to_process'].' only '.$cron_log_data['num_data_processed'].' were processed successfully. Failed to process '.$cron_log_data['num_data_failed_to_process'].'.';
            else
                $cron_log_data['comment_msg'] = 'All Data Processed Successfully.';
            
            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
            {
                $email_details = $this->sip_contributions_model->get_email_details(array('type' => 'cron_error_alert_email'));
                $email_data = array(
                    'message' => $cron_log_data['comment_msg'],
                    'error_msg' => 'Failed Entires Details For Reference: </br>'
                );

                $cron_log_data['reference_details'] = json_decode($cron_log_data['reference_details'], true);
                foreach($cron_log_data['reference_details']['Failed'] as $key => $row)
                {
                    $email_data['error_msg'] .= ($key + 1).'. PRAN: '.$row['pran'].' Reference ID: '.$row['reference_id'].' Error: '.$row['error_msg'].'</br>';
                }

                $email_msg = Blade::render($email_details->message, $email_data);
                $params = array(
                    'to' => config('constants.CRON_ERROR_LOG_EMAIL_TO'),
                    'subject' => "Cron Failed to Trigger Mandate Autodebit for Upcoming SIP's (2nd, 3rd Attempt or Past SIP)",
                    'message' => $email_msg
                );

                $this->email_lib->send_email($params);
            }
        }
    }

    public function sync_users_sip_contribution_details_in_cra(Cams_lib $cams_lib, Nsdl_lib $nsdl_lib, Kfintech_lib $kfintech_lib, Contribution_details_model $contribution_details_model)
    {
        $cron_log_data = array(
            'cron_name' => 'sync_sip_contributions_with_cra',
            'cron_label' => "Sync SIP Contributions With CRA",
            'cron_time' => time(),
            'num_data_to_process' => 0,
            'num_data_processed' => 0,
            'num_data_failed_to_process' => 0,
            'reference_details' => array(),
            'comment_msg' => NULL
        );

        $users_sip_list = $this->sip_contributions_model->get_pending_cra_sync_sips(500);

        if(empty($users_sip_list))
        {
            //Adding Cron Log
            $cron_log_data['comment_msg'] = 'No Data To Process.';

            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);
        }
        else
        {
            // $success_sms_template_details = $this->sip_contributions_model->get_sms_details(array(array('type', '=', 'nps_successful_contribution'), array('is_on', '=', 'Y')));
            // $success_email_template_details = $this->sip_contributions_model->get_email_details(array(array('type', '=', 'nps_successful_contribution'), array('is_on', '=', 'Y')));

            // if($success_email_template_details)
            //     $success_email_msg = Blade::render($success_email_template_details->message);

            // $failed_sms_template_details = $this->sip_contributions_model->get_sms_details(array(array('type', '=', 'failed_contribution'), array('is_on', '=', 'Y')));
            // $failed_email_template_details = $this->sip_contributions_model->get_email_details(array(array('type', '=', 'failed_contribution'), array('is_on', '=', 'Y')));

            // if($failed_email_template_details)
            //     $failed_email_msg = Blade::render($failed_email_template_details->message);
            
            $settings = get_settings(array('cams_cra', 'nsdl_cra', 'kfintech_cra'), array('pop_credential', 'api_user_id'));
           
            $cams_contributions = array();
            $cams_users_mapping = array();
            $kfintech_contributions = array();
            $kfintech_users_mapping = array();

            $cams_index = 0;
            $cams_contribution_sr_no = 1;
            $kfintech_index = 0;
            $kfintech_contribution_sr_no = 1;

            foreach($users_sip_list as $row)
            {
                switch($row->cra_account)
                {
                    case "CAMS":
                        $receipt_no = $row->contr_receipt_no;
                        $contribution_details = array(
                            'SN' => $cams_contribution_sr_no,
                            'POPRegistrationNo' => $settings['cams_cra']['pop_credential']['pop_reg_no'],
                            'POPSPRegistrationNo' => $settings['cams_cra']['pop_credential']['pop_sp_reg_no'],
                            'PRAN' => $row->pran,
                            'SubscribersTotalcontributionamount' => number_format(floatval($row->total_amount_received), 2, '.', ''),
                            'ReceiptNumber' => $receipt_no,
                            'trxn_sector' => 'UOS',
                            'trxn_type' => 'C'
                        );

                        if($row->tier_type == 'T1')
                            $contribution_details['SubscribersTier1contributionamount'] = number_format(floatval($row->total_amount_received), 2, '.', '');
                        if($row->tier_type == 'T2')
                            $contribution_details['SubscribersTier2contributionamount'] = number_format(floatval($row->total_amount_received), 2, '.', '');

                        $cams_contributions[$cams_index][] = $contribution_details;
                        $cams_users_mapping[$cams_index][$cams_contribution_sr_no] = array('pran' => $row->pran, 'contr_id' => $row->contr_id, 'contr_cra_sync_attempts' => $row->contr_cra_sync_attempts, 'contr_cra_sync_status' => $row->contr_cra_sync_status, 'mobile_no' => $row->mobile_no, 'email' => $row->email);
                        if($cams_contribution_sr_no == 50)
                        {
                            $cams_index++;
                            $cams_contribution_sr_no = 1;
                        }
                        else
                            $cams_contribution_sr_no++;

                        break;
                    case "NSDL":
                        $receipt_no = $row->contr_receipt_no;
                        $unique_sequence_no = substr($receipt_no, -8);

                        $instrument_number = '';
                        if(floatval($row->total_amount_received) >= 50000)
                        {
                            $instrument_number = substr($row->payment_trans_id, -6);
                        }

                        $contribution_details = array(
                            'batchId' => $settings['nsdl_cra']['pop_credential']['pop_reg_no'].date('my').str_pad($unique_sequence_no, 9, '0', STR_PAD_LEFT),
                            'userId' => $settings['nsdl_cra']['api_user_id']['user_id'],
                            'pran' => $row->pran,
                            'upldId' => $settings['nsdl_cra']['pop_credential']['pop_reg_no'],
                            'upldBy' => 'U',
                            'subscriberContribution' => number_format(floatval($row->total_amount_received), 2, '.', ''),
                            'employerContribution' => '',
                            'branchRegNo' => $settings['nsdl_cra']['pop_credential']['pop_sp_reg_no'],
                            'contributionType' => (($row->tier_type == 'T1') ? ($row->is_other_pop_user == 'Y' ? 'V' : 'U') : 'T'),
                            'month' => '',
                            'year' => '',
                            'tierType' => $row->tier_type,
                            'receiptNumber' => $receipt_no,
                            'pfmId' => '',
                            'receiptGenerateFlag' => 'Y',
                            'popRegNumber' => $settings['nsdl_cra']['pop_credential']['pop_reg_no'],
                            'rcptDate' => date('d/m/Y'),
                            'paymentMode' => 'E-TRANSFER',
                            'instrumentNumber' => $instrument_number,
                            'clearFunddate' => date('d/m/Y'),
                            'contrAmount' => number_format(floatval($row->sip_amount), 2, '.', ''),
                            'netAmount' => number_format(floatval($row->total_amount_received), 2, '.', ''),
                            'subscriberName' => '',
                            'arrearRemarks' => '',
                            'remarks' => ''
                        );

                        $response = $nsdl_lib->create_single_subscriber_contribution($contribution_details);
                        $encrypted_response = EncryptHelper::encryptFields($response);

                        $contr_cra_sync_attempts = $row->contr_cra_sync_attempts + 1;

                        if($response['http_status'] == 200 && is_null($response['api_response']['fieldErrors']) && !is_null($response['api_response']['pran']))
                        {
                            $updated = $this->sip_contributions_model->update_sip_cra_sync_status($row->contr_id, $response['api_response']['receiptNumber'], $response['api_response']['transactionId'], 'S', $contr_cra_sync_attempts);

                            $contr_log_inserted = $contribution_details_model->insert_data(array('contr_ref_ids' => $row->contr_id, 'contr_cra_trans_id' => $response['api_response']['transactionId'], 'contribution_type' => "SIP", 'data' => json_encode(EncryptHelper::encryptFields($response['api_response'])), 'created_on' => $this->time));

                            // if($success_sms_template_details && !empty($row->mobile_no))
                            // {
                            //     $mobile_no = '91'.Crypt::decryptString($row->mobile_no);
                            //     $sent_sms = $this->handle_send_sms($mobile_no, $success_sms_template_details->message, $success_sms_template_details->template_id, $row->contr_id, 'Contribution Successful');
                            // }

                            // if($success_email_template_details && !empty($row->email))
                            // {
                            //     $email = Crypt::decryptString($row->email);
                            //     $params = array(
                            //         'to' => $email,
                            //         'subject' => $success_email_template_details->subject,
                            //         'message' => $success_email_msg
                            //     );

                            //     $sent_email = $this->handle_send_email($params, $row->contr_id, 'Contribution Successful');
                            // }

                            $log_data = array('data' => json_encode(array('reference_id' => $row->contr_id, 'action_type' => 'SIP Contribution Updated Successfully in CRA.', 'comment' => 'For Reference Transaction ID:'.$response['api_response']['transactionId'].'.', 'cron' => "Sync SIP Contributions with CRA.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                            add_automated_processes_action_logs($log_data);

                            if(!isset($cron_log_data['reference_details']['Success']))
                                $cron_log_data['reference_details']['Success'] = array();
                            
                            $cron_log_data['reference_details']['Success'][] = array(
                                'pran' => $row->pran,
                                'reference_id' => $row->contr_id
                            );

                            $cron_log_data['num_data_processed'] += 1;
                        }
                        elseif($response['http_status'] == 200 && !is_null($response['api_response']['fieldErrors']))
                        {
                            $contr_cra_sync_status = 'P';
                            
                            if($contr_cra_sync_attempts == 3)
                                $contr_cra_sync_status = 'F';

                            $updated = $this->sip_contributions_model->update_contr_details_cycle_data(array(array('contr_id', '=', $row->contr_id)), array('contr_cra_sync_attempts' => $contr_cra_sync_attempts, 'contr_cra_sync_status' => $contr_cra_sync_status, 'contr_cra_sync_error_msg' => $response['api_response']['fieldErrors'][0]['errorMsg'], 'updated_on' => time()));
                        
                            $contr_log_inserted = $contribution_details_model->insert_data(array('contr_ref_ids' => $row->contr_id, 'contr_cra_trans_id' => NULL, 'contribution_type' => "SIP", 'data' => json_encode(EncryptHelper::encryptFields($response['api_response'])), 'created_on' => $this->time));

                            // if($contr_cra_sync_attempts == 3)
                            // {
                            //     if($failed_sms_template_details && !empty($row->mobile_no))
                            //     {
                            //         $mobile_no = '91'.Crypt::decryptString($row->mobile_no);
                            //         $sent_sms = $this->handle_send_sms($mobile_no, $failed_sms_template_details->message, $failed_sms_template_details->template_id, $row->contr_id, 'Contribution Failed (CRA Sync)');
                            //     }

                            //     if($failed_email_template_details && !empty($row->email))
                            //     {
                            //         $email = Crypt::decryptString($row->email);
                            //         $params = array(
                            //             'to' => $email,
                            //             'subject' => $failed_email_template_details->subject,
                            //             'message' => $failed_email_msg
                            //         );

                            //         $sent_email = $this->handle_send_email($params, $row->contr_id, 'Contribution Failed (CRA Sync)');
                            //     }
                            // }

                            $log_data = array('data' => json_encode(array('reference_id' => $row->contr_id, 'action_type' => 'Failed to Update SIP Contribution in CRA.', 'cron' => "Sync SIP Contributions with CRA.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                            add_automated_processes_action_logs($log_data);

                            if(!isset($cron_log_data['reference_details']['Failed']))
                                $cron_log_data['reference_details']['Failed'] = array();
                            
                            $cron_log_data['reference_details']['Failed'][] = array(
                                'pran' => $row->pran,
                                'reference_id' => $row->contr_id,
                                'error_msg' => 'Failed to Sync SIP Contribution. Getting error from CRA: '.$response['api_response']['fieldErrors'][0]['errorMsg'].'.'
                            );

                            $cron_log_data['num_data_failed_to_process'] += 1;
                        }
                        else
                        {
                            if(!isset($cron_log_data['reference_details']['Failed']))
                                $cron_log_data['reference_details']['Failed'] = array();
                            
                            $cron_log_data['reference_details']['Failed'][] = array(
                                'pran' => $row->pran,
                                'reference_id' => $row->contr_id,
                                'error_msg' => 'Failed to Sync SIP Contribution. Getting unexpected error.'
                            );

                            $cron_log_data['num_data_failed_to_process'] += 1;
                        }

                        break;
                    case "KFINTECH":
                        $receipt_no = $row->contr_receipt_no;
                        $unique_sequence_no = substr($receipt_no, -8);
                        $transaction_id = time().$unique_sequence_no;

                        $contribution_details = array(
                            'SNO' => $kfintech_contribution_sr_no,
                            'Pran' => $row->pran,
                            'ContributionType' => 'S',
                            'T1CNTamount' => (($row->tier_type == 'T1') ? number_format(floatval($row->total_amount_received), 2, '.', '') : '0.00'),
                            'T1SGST' => (($row->tier_type == 'T1') ? number_format(floatval($row->sgst_amt), 2, '.', '') : '0.00'),
                            'T1CGST' => (($row->tier_type == 'T1') ? number_format(floatval($row->cgst_amt), 2, '.', '') : '0.00'),
                            'T1UTGST' => '0.00',
                            'T1IGST' => (($row->tier_type == 'T1') ? number_format(floatval($row->igst_amt), 2, '.', '') : '0.00'),
                            'T1ServiceCharge' => '0.00',
                            'T2CNTamount' => (($row->tier_type == 'T2') ? number_format(floatval($row->total_amount_received), 2, '.', '') : '0.00'),
                            'T2SGST' => (($row->tier_type == 'T2') ? number_format(floatval($row->sgst_amt), 2, '.', '') : '0.00'),
                            'T2CGST' => (($row->tier_type == 'T2') ? number_format(floatval($row->cgst_amt), 2, '.', '') : '0.00'),
                            'T2UTGST' => '0.00',
                            'T2IGST' => (($row->tier_type == 'T2') ? number_format(floatval($row->igst_amt), 2, '.', '') : '0.00'),
                            'T2ServiceCharge' => '0.00',
                            'GatewayCharges' => '0.00',
                            'PoPCharges' => number_format(floatval($row->contribution_fees), 2, '.', ''),
                            'AccounType' => (($row->tier_type == 'T1') ? '1' : '2'),
                            'TotalAmount' => number_format(floatval($row->sip_amount), 2, '.', ''),
                            'Transactionid' => $transaction_id,
                            'ReceiptNumber' => $receipt_no
                        );

                        $kfintech_contributions[$kfintech_index][] = $contribution_details;
                        $kfintech_users_mapping[$kfintech_index][$receipt_no] = array('pran' => $row->pran, 'contr_id' => $row->contr_id, 'contr_cra_sync_attempts' => $row->contr_cra_sync_attempts, 'contr_cra_sync_status' => $row->contr_cra_sync_status, 'mobile_no' => $row->mobile_no, 'email' => $row->email, 'contr_cra_req_trans_id' => $transaction_id);
                        if($kfintech_contribution_sr_no == 50)
                        {
                            $kfintech_index++;
                            $kfintech_contribution_sr_no = 1;
                        }
                        else
                            $kfintech_contribution_sr_no++;

                        break;
                    default: 
                        if(!isset($cron_log_data['reference_details']['Failed']))
                            $cron_log_data['reference_details']['Failed'] = array();
                        
                        $cron_log_data['reference_details']['Failed'][] = array(
                            'pran' => $row->pran,
                            'reference_id' => $row->contr_id,
                            'error_msg' => 'Failed to Sync SIP Contribution. Unknown CRA.'
                        );

                        $cron_log_data['num_data_failed_to_process'] += 1;
                }
            }

            if(!empty($cams_contributions))
            {
                foreach($cams_contributions as $key => $batch_data)
                {
                    $response = $cams_lib->create_subscriber_contribution($batch_data);
                    
                    if(($response['http_status'] == 201 && $response['api_response']['api']['statusCode'] == 201) || ($response['http_status'] == 500 && $response['api_response']['api']['statusCode'] == 500))
                    {
                        $encrypted_response = EncryptHelper::encryptFields($response);

                        foreach($response['api_response']['response']['result'] as $contr_key => $row)
                        {
                            $sr_no = $contr_key + 1;
                            $contr_cra_sync_attempts = $cams_users_mapping[$key][$sr_no]['contr_cra_sync_attempts'] + 1;
                            if(!$row['isError'])
                            {
                                $updated = $this->sip_contributions_model->update_sip_cra_sync_status($cams_users_mapping[$key][$sr_no]['contr_id'], $row['contribution_details']['success']['receipt_no'], $response['api_response']['response']['tid']['tid_number'], 'S', $contr_cra_sync_attempts);
                                
                                // if($success_sms_template_details && !empty($cams_users_mapping[$key][$sr_no]['mobile_no']))
                                // {
                                //     $mobile_no = '91'.Crypt::decryptString($cams_users_mapping[$key][$sr_no]['mobile_no']);
                                //     $sent_sms = $this->handle_send_sms($mobile_no, $success_sms_template_details->message, $success_sms_template_details->template_id, $cams_users_mapping[$key][$sr_no]['contr_id'], 'Contribution Successful');
                                // }

                                // if($success_email_template_details && !empty($cams_users_mapping[$key][$sr_no]['email']))
                                // {
                                //     $email = Crypt::decryptString($cams_users_mapping[$key][$sr_no]['email']);
                                //     $params = array(
                                //         'to' => $email,
                                //         'subject' => $success_email_template_details->subject,
                                //         'message' => $success_email_msg
                                //     );

                                //     $sent_email = $this->handle_send_email($params, $cams_users_mapping[$key][$sr_no]['contr_id'], 'Contribution Successful');
                                // }

                                $log_data = array('data' => json_encode(array('reference_id' => $cams_users_mapping[$key][$sr_no]['contr_id'], 'action_type' => 'SIP Contribution Updated Successfully in CRA.', 'comment' => 'For Reference Transaction ID:'.$response['api_response']['response']['tid']['tid_number'].'.', 'cron' => "Sync SIP Contributions with CRA.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                                add_automated_processes_action_logs($log_data);

                                if(!isset($cron_log_data['reference_details']['Success']))
                                    $cron_log_data['reference_details']['Success'] = array();
                                
                                $cron_log_data['reference_details']['Success'][] = array(
                                    'pran' => $cams_users_mapping[$key][$sr_no]['pran'],
                                    'reference_id' => $cams_users_mapping[$key][$sr_no]['contr_id']
                                );

                                $cron_log_data['num_data_processed'] += 1;
                            }
                            else
                            {
                                $contr_cra_sync_status = 'P';

                                if($contr_cra_sync_attempts == 3)
                                    $contr_cra_sync_status = 'F';

                                $updated = $this->sip_contributions_model->update_contr_details_cycle_data(array(array('contr_id', '=', $cams_users_mapping[$key][$sr_no]['contr_id'])), array('contr_cra_sync_attempts' => $contr_cra_sync_attempts, 'contr_cra_sync_status' => $contr_cra_sync_status, 'contr_cra_sync_error_msg' => $row['general_errors'], 'updated_on' => $this->time));
                            
                                // if($contr_cra_sync_attempts == 3)
                                // {
                                //     if($failed_sms_template_details && !empty($cams_users_mapping[$key][$sr_no]['mobile_no']))
                                //     {
                                //         $mobile_no = '91'.Crypt::decryptString($cams_users_mapping[$key][$sr_no]['mobile_no']);
                                //         $sent_sms = $this->handle_send_sms($mobile_no, $failed_sms_template_details->message, $failed_sms_template_details->template_id, $cams_users_mapping[$key][$sr_no]['contr_id'], 'Contribution Failed (CRA Sync)');
                                //     }

                                //     if($failed_email_template_details && !empty($cams_users_mapping[$key][$sr_no]['email']))
                                //     {
                                //         $email = Crypt::decryptString($cams_users_mapping[$key][$sr_no]['email']);
                                //         $params = array(
                                //             'to' => $email,
                                //             'subject' => $failed_email_template_details->subject,
                                //             'message' => $failed_email_msg
                                //         );

                                //         $sent_email = $this->handle_send_email($params, $cams_users_mapping[$key][$sr_no]['contr_id'], 'Contribution Failed (CRA Sync)');
                                //     }
                                // }

                                $log_data = array('data' => json_encode(array('reference_id' => $cams_users_mapping[$key][$sr_no]['contr_id'], 'action_type' => 'Failed to Update SIP Contribution in CRA.', 'comment' => 'For Reference Transaction ID:'.$response['api_response']['response']['tid']['tid_number'].'.', 'cron' => "Sync SIP Contributions with CRA.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                                add_automated_processes_action_logs($log_data);

                                if(!isset($cron_log_data['reference_details']['Failed']))
                                    $cron_log_data['reference_details']['Failed'] = array();
                                
                                $cron_log_data['reference_details']['Failed'][] = array(
                                    'pran' => $cams_users_mapping[$key][$sr_no]['pran'],
                                    'reference_id' => $cams_users_mapping[$key][$sr_no]['contr_id'],
                                    'error_msg' => 'Failed to Sync SIP Contribution. Getting error from CRA: '.$row['general_errors'].'.'
                                );

                                $cron_log_data['num_data_failed_to_process'] += 1;
                            }
                        }

                        $contr_log_inserted = $contribution_details_model->insert_data(array('contr_ref_ids' => implode(',', array_column($cams_users_mapping[$key], 'contr_id')), 'contr_cra_trans_id' => $response['api_response']['response']['tid']['tid_number'], 'contribution_type' => "SIP", 'data' => json_encode(EncryptHelper::encryptFields($response['api_response'])), 'created_on' => $this->time));
                    }
                    else
                    {
                        foreach($batch_data as $contr_key => $row)
                        {
                            $sr_no = $contr_key + 1;

                            if(!isset($cron_log_data['reference_details']['Failed']))
                                $cron_log_data['reference_details']['Failed'] = array();
                        
                            $cron_log_data['reference_details']['Failed'][] = array(
                                'pran' => $cams_users_mapping[$key][$sr_no]['pran'],
                                'reference_id' => $cams_users_mapping[$key][$sr_no]['contr_id'],
                                'error_msg' => 'Failed to Sync SIP Contribution. Getting unexpected error.'
                            );

                            $cron_log_data['num_data_failed_to_process'] += 1;
                        }
                    }
                }
            }

            if(!empty($kfintech_contributions))
            {
                foreach($kfintech_contributions as $key => $batch_data)
                {
                    $response = $kfintech_lib->create_subscriber_contribution($batch_data, count($batch_data));

                    if($response['http_status'] == 200 && isset($response['api_response']['Status']))
                    {
                        $contr_cra_trans_id = NULL;

                        $encrypted_response = EncryptHelper::encryptFields($response);

                        if(isset($response['api_response']['Failed Details']))
                        {
                            foreach($response['api_response']['Failed Details'] as $row)
                            {
                                $contr_cra_sync_attempts = $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_cra_sync_attempts'] + 1;
                                $contr_cra_sync_status = 'P';

                                if($contr_cra_sync_attempts == 3)
                                    $contr_cra_sync_status = 'F';

                                $updated = $this->sip_contributions_model->update_contr_details_cycle_data(array(array('contr_id', '=', $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_id'])), array('contr_cra_sync_attempts' => $contr_cra_sync_attempts, 'contr_cra_sync_status' => $contr_cra_sync_status, 'contr_cra_sync_error_msg' => $row['ErrorCode(s)'], 'updated_on' => $this->time));
                                
                                // if($contr_cra_sync_attempts == 3)
                                // {
                                //     if($failed_sms_template_details && !empty($kfintech_users_mapping[$key][$row['Receipt Number']]['mobile_no']))
                                //     {
                                //         $mobile_no = '91'.Crypt::decryptString($kfintech_users_mapping[$key][$row['Receipt Number']]['mobile_no']);
                                //         $sent_sms = $this->handle_send_sms($mobile_no, $failed_sms_template_details->message, $failed_sms_template_details->template_id, $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_id'], 'Contribution Failed (CRA Sync)');
                                //     }

                                //     if($failed_email_template_details && !empty($kfintech_users_mapping[$key][$row['Receipt Number']]['email']))
                                //     {
                                //         $email = Crypt::decryptString($kfintech_users_mapping[$key][$row['Receipt Number']]['email']);
                                //         $params = array(
                                //             'to' => $email,
                                //             'subject' => $failed_email_template_details->subject,
                                //             'message' => $failed_email_msg
                                //         );

                                //         $sent_email = $this->handle_send_email($params, $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_id'], 'Contribution Failed (CRA Sync)');
                                //     }
                                // }

                                $log_data = array('data' => json_encode(array('reference_id' => $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_id'], 'action_type' => 'Failed to Update SIP Contribution in CRA.', 'cron' => "Sync SIP Contributions with CRA.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                                add_automated_processes_action_logs($log_data);

                                if(!isset($cron_log_data['reference_details']['Failed']))
                                    $cron_log_data['reference_details']['Failed'] = array();
                                
                                $cron_log_data['reference_details']['Failed'][] = array(
                                    'pran' => $kfintech_users_mapping[$key][$row['Receipt Number']]['pran'],
                                    'reference_id' => $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_id'],
                                    'error_msg' => 'Failed to Sync SIP Contribution. Getting error from CRA: '.$row['ErrorCode(s)'].'.'
                                );

                                $cron_log_data['num_data_failed_to_process'] += 1;
                            }
                        }

                        if(isset($response['api_response']['Success Details']))
                        {
                            $contr_cra_trans_id = $response['api_response']['Success Details'][0]['TransactionID'];
                            foreach($response['api_response']['Success Details'] as $row)
                            {
                                $contr_cra_sync_attempts = $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_cra_sync_attempts'] + 1;
                                $updated = $this->sip_contributions_model->update_sip_cra_sync_status($kfintech_users_mapping[$key][$row['Receipt Number']]['contr_id'], $row['Receipt Number'], $row['TransactionID'], 'S', $contr_cra_sync_attempts, $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_cra_req_trans_id']);
                                
                                // if($success_sms_template_details && !empty($kfintech_users_mapping[$key][$row['Receipt Number']]['mobile_no']))
                                // {
                                //     $mobile_no = '91'.Crypt::decryptString($kfintech_users_mapping[$key][$row['Receipt Number']]['mobile_no']);
                                //     $sent_sms = $this->handle_send_sms($mobile_no, $success_sms_template_details->message, $success_sms_template_details->template_id, $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_id'], 'Contribution Successful');
                                // }

                                // if($success_email_template_details && !empty($kfintech_users_mapping[$key][$row['Receipt Number']]['email']))
                                // {
                                //     $email = Crypt::decryptString($kfintech_users_mapping[$key][$row['Receipt Number']]['email']);
                                //     $params = array(
                                //         'to' => $email,
                                //         'subject' => $success_email_template_details->subject,
                                //         'message' => $success_email_msg
                                //     );

                                //     $sent_email = $this->handle_send_email($params, $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_id'], 'Contribution Successful');
                                // }

                                $log_data = array('data' => json_encode(array('reference_id' => $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_id'], 'action_type' => 'SIP Contribution Updated Successfully in CRA.', 'comment' => 'For Reference Transaction ID:'.$row['TransactionID'].'.', 'cron' => "Sync SIP Contributions with CRA.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                                add_automated_processes_action_logs($log_data);

                                if(!isset($cron_log_data['reference_details']['Success']))
                                    $cron_log_data['reference_details']['Success'] = array();
                                
                                $cron_log_data['reference_details']['Success'][] = array(
                                    'pran' => $kfintech_users_mapping[$key][$row['Receipt Number']]['pran'],
                                    'reference_id' => $kfintech_users_mapping[$key][$row['Receipt Number']]['contr_id']
                                );

                                $cron_log_data['num_data_processed'] += 1;
                            }
                        }

                        $contribution_details_model->insert_data(array('contr_ref_ids' => implode(',', array_column($kfintech_users_mapping[$key], 'contr_id')), 'contr_cra_trans_id' => '', 'contribution_type' => "SIP", 'data' => json_encode(EncryptHelper::encryptFields($response['api_response'])), 'created_on' => $this->time));
                    }
                    else
                    {
                        foreach($batch_data as $row)
                        {
                            if(!isset($cron_log_data['reference_details']['Failed']))
                                $cron_log_data['reference_details']['Failed'] = array();
                        
                            $cron_log_data['reference_details']['Failed'][] = array(
                                'pran' => $kfintech_users_mapping[$key][$row['ReceiptNumber']]['pran'],
                                'reference_id' => $kfintech_users_mapping[$key][$row['ReceiptNumber']]['contr_id'],
                                'error_msg' => 'Failed to Sync SIP Contribution. Getting unexpected error.'
                            );

                            $cron_log_data['num_data_failed_to_process'] += 1;
                        }
                    }
                }
            }

            //Adding Cron Log
            $cron_log_data['num_data_to_process'] = count($users_sip_list);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
                $cron_log_data['comment_msg'] = 'Out of '.$cron_log_data['num_data_to_process'].' only '.$cron_log_data['num_data_processed'].' were processed successfully. Failed to process '.$cron_log_data['num_data_failed_to_process'].'.';
            else
                $cron_log_data['comment_msg'] = 'All Data Processed Successfully.';
            
            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
            {
                $email_details = $this->sip_contributions_model->get_email_details(array('type' => 'cron_error_alert_email'));
                $email_data = array(
                    'message' => $cron_log_data['comment_msg'],
                    'error_msg' => 'Failed Entires Details For Reference: </br>'
                );

                $cron_log_data['reference_details'] = json_decode($cron_log_data['reference_details'], true);
                foreach($cron_log_data['reference_details']['Failed'] as $key => $row)
                {
                    $email_data['error_msg'] .= ($key + 1).'. PRAN: '.$row['pran'].' Reference ID: '.$row['reference_id'].' Error: '.$row['error_msg'].'</br>';
                }

                $email_msg = Blade::render($email_details->message, $email_data);
                $params = array(
                    'to' => config('constants.CRON_ERROR_LOG_EMAIL_TO'),
                    'subject' => "Cron Failed to Sync SIP Contributions With CRA",
                    'message' => $email_msg
                );

                $this->email_lib->send_email($params);
            }
        }
    }

    public function trigger_upcoming_sip_reminder_notification()
    {
        $cron_log_data = array(
            'cron_name' => 'trigger_upcoming_sip_reminder_notification',
            'cron_label' => "Send SIP Reminder Notification To User",
            'cron_time' => time(),
            'num_data_to_process' => 0,
            'num_data_processed' => 0,
            'num_data_failed_to_process' => 0,
            'reference_details' => array(),
            'comment_msg' => NULL
        );

        $users_sip_list = $this->sip_contributions_model->get_notification_trigger_pending_sips(5000);

        if(empty($users_sip_list))
        {
            //Adding Cron Log
            $cron_log_data['comment_msg'] = 'No Data To Process.';

            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);
        }
        else
        {
            $sms_details = $this->sip_contributions_model->get_spi_reminder_sms_details();

            if(!$sms_details)
                return false;

            foreach($users_sip_list as $row)
            {
                if($row->mobile_no != '' && !is_null($row->mobile_no))
                {
                    $mobile_no = '91'.Crypt::decryptString($row->mobile_no);
                    $sent_sms = $this->handle_send_sms($mobile_no, $sms_details->message, $sms_details->template_id, $row->contr_id, 'SIP Reminder');

                    if(!$sent_sms)
                    {
                        $log_data = array('data' => json_encode(array('reference_id' => $row->contr_id, 'action_type' => 'Failed to Send SIP Reminder SMS.', 'cron' => "Trigger Upcomming SIP's Reminder Notification.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                        add_automated_processes_action_logs($log_data);

                        if(!isset($cron_log_data['reference_details']['Failed']))
                            $cron_log_data['reference_details']['Failed'] = array();
                    
                        $cron_log_data['reference_details']['Failed'][] = array(
                            'pran' => $row->pran,
                            'reference_id' => $row->contr_id,
                            'error_msg' => 'Failed to send sip reminder notification. Getting error from sms gateway.'
                        );

                        $cron_log_data['num_data_failed_to_process'] += 1;
                    }
                    else
                    {
                        $log_data = array('data' => json_encode(array('reference_id' => $row->contr_id, 'action_type' => 'SIP Reminder SMS Sent.', 'cron' => "Trigger Upcomming SIP's Reminder Notification.", 'cron_time' => $this->time)), 'created_on' => $this->time);
                        add_automated_processes_action_logs($log_data);

                        if(!isset($cron_log_data['reference_details']['Success']))
                            $cron_log_data['reference_details']['Success'] = array();
                        
                        $cron_log_data['reference_details']['Success'][] = array(
                            'pran' => $row->pran,
                            'reference_id' => $row->contr_id
                        );

                        $cron_log_data['num_data_processed'] += 1;
                    }
                }
            }

            //Adding Cron Log
            $cron_log_data['num_data_to_process'] = count($users_sip_list);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
                $cron_log_data['comment_msg'] = 'Out of '.$cron_log_data['num_data_to_process'].' only '.$cron_log_data['num_data_processed'].' were processed successfully. Failed to process '.$cron_log_data['num_data_failed_to_process'].'.';
            else
                $cron_log_data['comment_msg'] = 'All Data Processed Successfully.';
            
            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);
        }
    }

    public function initiate_refund_for_failed_sips(Payment_gateway_lib $payment_gateway_lib)
    {
        $cron_log_data = array(
            'cron_name' => 'initiate_refund_for_failed_sips',
            'cron_label' => "Initiated Refund to Failed SIP's (Failed to Sync with CRA)",
            'cron_time' => time(),
            'num_data_to_process' => 0,
            'num_data_processed' => 0,
            'num_data_failed_to_process' => 0,
            'reference_details' => array(),
            'comment_msg' => NULL
        );

        $sip_list = $this->sip_contributions_model->get_refund_pending_sips(500);

        if(empty($sip_list))
        {
            //Adding Cron Log
            $cron_log_data['comment_msg'] = 'No Data To Process.';

            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);
        }
        else
        {
            $sms_template_details = $this->sip_contributions_model->get_sms_details(array(array('type', '=', 'refund_initiated_contribution'), array('is_on', '=', 'Y')));
            $email_template_details = $this->sip_contributions_model->get_email_details(array(array('type', '=', 'refund_initiated_contribution'), array('is_on', '=', 'Y')));

            if($email_template_details)
                $email_msg = Blade::render($email_template_details->message);

            foreach($sip_list as $row)
            {
                $response = $payment_gateway_lib->refund_payment($row->payment_trans_id, $row->instrument_reference, number_format(floatval($row->sip_amount), 2, '.', ''));

                if($response['http_status'] == 200)
                {
                    $refund_attempts = $row->refund_attempts + 1;
                    if($response['api_response']['code'] == 200)
                    {
                        $conditions = array(array('payment_trans_id', '=', $row->payment_trans_id), array('contr_ref_id', '=', $row->contr_id), array('contribution_type', '=', 'SIP'));
                        $data = array(
                            'refund_trans_id' => $response['api_response']['transactionRefNumber'],
                            'refund_id' => $response['api_response']['refundId'],
                            'refund_status' => 'RI',
                            'refund_init_on' => time(),
                            'refund_compl_on' => NULL,
                            'refund_attempts' => $refund_attempts,
                            'updated_on' => $this->time
                        );

                        $affected_rows = $this->sip_contributions_model->update_refund_details($conditions, $data);

                        if($sms_template_details && !empty($row->mobile_no))
                        {
                            $mobile_no = '91'.Crypt::decryptString($row->mobile_no);
                            $sent_sms = $this->handle_send_sms($mobile_no, $sms_template_details->message, $sms_template_details->template_id, $row->contr_id, 'Refund Initiated');
                        }

                        if($email_template_details && !empty($row->email))
                        {
                            $email = array(array('email' => Crypt::decryptString($row->email)));
                            $params = array(
                                'to' => $email,
                                'subject' => $email_template_details->subject,
                                'message' => $email_msg
                            );

                            $sent_email = $this->handle_send_email($params, $row->contr_id, 'Refund Initiated');
                        }

                        $log_data = array('data' => json_encode(array('reference_id' => $row->contr_id, 'action_type' => 'Initiated Refund For Failed SIP Contribution.', 'cron' => "SIP Initiate Refund", 'cron_time' => $this->time)), 'created_on' => $this->time);
                        add_automated_processes_action_logs($log_data);

                        if(!isset($cron_log_data['reference_details']['Success']))
                            $cron_log_data['reference_details']['Success'] = array();
                        
                        $cron_log_data['reference_details']['Success'][] = array(
                            'pran' => $row->pran,
                            'reference_id' => $row->contr_id
                        );

                        $cron_log_data['num_data_processed'] += 1;
                    }
                    else
                    {
                        $conditions = array(array('payment_trans_id', '=', $row->payment_trans_id), array('contr_ref_id', '=', $row->contr_id), array('contribution_type', '=', 'SIP'));
                        $data = array(
                            'refund_trans_id' => NULL,
                            'refund_id' => NULL,
                            'refund_status' => NULL,
                            'refund_init_on' => NULL,
                            'refund_compl_on' => NULL,
                            'refund_attempts' => $refund_attempts,
                            'updated_on' => $this->time
                        );

                        $affected_rows = $this->sip_contributions_model->update_refund_details($conditions, $data);

                        $log_data = array('data' => json_encode(array('reference_id' => $row->contr_id, 'action_type' => 'Failed to Initiate Refund For Failed SIP Contribution.', 'cron' => "SIP Initiate Refund", 'cron_time' => $this->time)), 'created_on' => $this->time);
                        add_automated_processes_action_logs($log_data);

                        if(!isset($cron_log_data['reference_details']['Failed']))
                            $cron_log_data['reference_details']['Failed'] = array();
                        
                        $cron_log_data['reference_details']['Failed'][] = array(
                            'pran' => $row->pran,
                            'reference_id' => $row->contr_id,
                            'error_msg' => 'Failed to intiated refund for failed sip. Getting unexpected error from payment gateway.'
                        );

                        $cron_log_data['num_data_failed_to_process'] += 1;
                    }

                    $pg_log_inserted = $this->sip_contributions_model->insert_pg_log(
                        array(
                            'contr_ref_id' => $row->contr_id, 
                            'contribution_type' => 'SIP',
                            'payment_trans_id' => (isset($response['api_response']['transactionRefNumber']) ? $response['api_response']['transactionRefNumber'] : NULL), 
                            'type' => 'REFUND B2B',
                            'data' => json_encode($response['api_response']), 
                            'created_on' => time()
                        )
                    );
                }
                else
                {
                    if(!isset($cron_log_data['reference_details']['Failed']))
                        $cron_log_data['reference_details']['Failed'] = array();
                    
                    $cron_log_data['reference_details']['Failed'][] = array(
                        'pran' => $row->pran,
                        'reference_id' => $row->contr_id,
                        'error_msg' => 'Failed to intiated refund for failed sip. Getting unexpected error.'
                    );

                    $cron_log_data['num_data_failed_to_process'] += 1;
                }
            }

            //Adding Cron Log
            $cron_log_data['num_data_to_process'] = count($sip_list);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
                $cron_log_data['comment_msg'] = 'Out of '.$cron_log_data['num_data_to_process'].' only '.$cron_log_data['num_data_processed'].' were processed successfully. Failed to process '.$cron_log_data['num_data_failed_to_process'].'.';
            else
                $cron_log_data['comment_msg'] = 'All Data Processed Successfully.';
            
            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
            {
                $email_details = $this->sip_contributions_model->get_email_details(array('type' => 'cron_error_alert_email'));
                $email_data = array(
                    'message' => $cron_log_data['comment_msg'],
                    'error_msg' => 'Failed Entires Details For Reference: </br>'
                );

                $cron_log_data['reference_details'] = json_decode($cron_log_data['reference_details'], true);
                foreach($cron_log_data['reference_details']['Failed'] as $key => $row)
                {
                    $email_data['error_msg'] .= ($key + 1).'. PRAN: '.$row['pran'].' Reference ID: '.$row['reference_id'].' Error: '.$row['error_msg'].'</br>';
                }

                $email_msg = Blade::render($email_details->message, $email_data);
                $params = array(
                    'to' => config('constants.CRON_ERROR_LOG_EMAIL_TO'),
                    'subject' => "Cron Failed to Initiated Refund to Failed SIP's (Failed to Sync with CRA)",
                    'message' => $email_msg
                );

                $this->email_lib->send_email($params);
            }
        }
    }

    public function move_pending_sip_to_failed()
    {
        $cron_log_data = array(
            'cron_name' => 'move_pending_sip_to_failed',
            'cron_label' => "Move Pending SIP's To Failed (Failed to initiate Payment Gateway)",
            'cron_time' => time(),
            'num_data_to_process' => 0,
            'num_data_processed' => 0,
            'num_data_failed_to_process' => 0,
            'reference_details' => array(),
            'comment_msg' => NULL
        );

        $pending_sip = $this->sip_contributions_model->get_pending_users_sip(2500);

        if(empty($pending_sip))
        {
            //Adding Cron Log
            $cron_log_data['comment_msg'] = 'No Data To Process.';

            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);
        }
        else
        {
            foreach($pending_sip as $row)
            {
                $conditions = array(array('mast_sip_contr_id', '=', $row->mast_sip_contr_id));
                $data = array('sip_status' => 'F', 'pg_status' => 'E', 'updated_on' => $this->time);
    
                $this->sip_contributions_model->update_sip_details($conditions, $data);
                $this->sip_contributions_model->update_contr_details_cycle_data($conditions, array('mandate_status' => 'F', 'updated_on' => $this->time));
                $this->sip_contributions_model->update_pg_transaction_details(array(array('contr_ref_id', '=', $row->contr_id), array('contribution_type', '=', 'SIP'), array('payment_trans_id', '=', $row->payment_trans_id)), array('payment_status' => 'F', 'updated_on' => $this->time));
            
                $log_data = array('data' => json_encode(array('reference_id' => $row->mast_sip_contr_id, 'action_type' => "Moved Pending SIP to Failed.", 'cron' => "Moved Pending SIP to Failed", 'cron_time' => $this->time)), 'created_on' => $this->time);
                add_automated_processes_action_logs($log_data);

                if(!isset($cron_log_data['reference_details']['Success']))
                    $cron_log_data['reference_details']['Success'] = array();
                
                $cron_log_data['reference_details']['Success'][] = array(
                    'pran' => $row->pran,
                    'reference_id' => $row->mast_sip_contr_id
                );

                $cron_log_data['num_data_processed'] += 1;
            }

            //Adding Cron Log
            $cron_log_data['num_data_to_process'] = count($pending_sip);

            if($cron_log_data['num_data_to_process'] != $cron_log_data['num_data_processed'])
                $cron_log_data['comment_msg'] = 'Out of '.$cron_log_data['num_data_to_process'].' only '.$cron_log_data['num_data_processed'].' were processed successfully. Failed to process '.$cron_log_data['num_data_failed_to_process'].'.';
            else
                $cron_log_data['comment_msg'] = 'All Data Processed Successfully.';
            
            if(empty($cron_log_data['reference_details'])) 
                $cron_log_data['reference_details'] = NULL;
            else   
                $cron_log_data['reference_details'] = json_encode($cron_log_data['reference_details']);

            add_cron_log($cron_log_data);
        }
    }

    private function handle_send_sms($mobile_no, $message, $template_id, $contr_id, $notification_type)
    {
        $sms_response = $this->sms_gateway_lib->send_sms($mobile_no, $message, $template_id);
    
        if($sms_response['status'] != 200)
        {
            $inserted = $this->sip_contributions_model->insert_notification_details(
                array(
                    'contr_ref_id' => $contr_id, 
                    'contribution_type' => 'SIP', 
                    'notification_type' => $notification_type, 
                    'notification_source' => 'SMS',
                    'ref_id' => NULL,
                    'status' => 'F',
                    'created_on' => time()
                )
            );

            return false;
        }
        
        $inserted = $this->sip_contributions_model->insert_notification_details(
            array(
                'contr_ref_id' => $contr_id, 
                'contribution_type' => 'SIP', 
                'notification_type' => $notification_type, 
                'notification_source' => 'SMS',
                'ref_id' => $sms_response['req_id'],
                'status' => 'S',
                'created_on' => time()
            )
        );

        return true;
    }

    private function handle_send_email($email_params, $contr_id, $notification_type)
    {
        $email_response = $this->email_lib->send_email($email_params);

        if($email_response['status'] != 200)
        {
            $inserted = $this->sip_contributions_model->insert_notification_details(
                array(
                    'contr_ref_id' => $contr_id, 
                    'contribution_type' => 'SIP', 
                    'notification_type' => $notification_type, 
                    'notification_source' => 'EMAIL', 
                    'ref_id' => NULL,
                    'status' => 'F',
                    'created_on' => time()
                )
            );

            return false;
        }

        $inserted = $this->sip_contributions_model->insert_notification_details(
            array(
                'contr_ref_id' => $contr_id, 
                'contribution_type' => 'SIP', 
                'notification_type' => $notification_type, 
                'notification_source' => 'EMAIL', 
                'ref_id' => $email_response['message_id'],
                'status' => 'S',
                'created_on' => time()
            )
        );

        return true;
    }
}
