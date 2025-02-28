<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Authentication_model;
use App\Libraries\Cams_lib;
use App\Libraries\Nsdl_lib;
use App\Libraries\Kfintech_lib;
use App\Libraries\Sms_gateway_lib;
use App\Libraries\Sync_data_lib;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class Authentication extends Controller
{
    private $authentication_model;
    public function __construct(Authentication_model $authentication_model)
    {
        $this->authentication_model = $authentication_model;
    }
    
    public function index($param = NULL)
    {

        if(!session()->has('maintenance') && env('MAINTENANCE_MODE', false)) 
        {
            return view('frontend.authentication_maintenance');
        }
    
        if($param) 
        {
            if($param == 'retail_sip') 
            {
                session(['user_auth' => 'retail_sip_flow']);
            } 
            elseif($param == 'subsequent_contribution') 
            {
                session(['hide_home' => true]);
                session(['user_auth' => 'subsequent_contribution_flow']);
            } 
            else 
            {
                session(['user_auth' => 'home']);
            }
        }
        
        if(session('api_subscriber_pran'))
        {
        	if(isset($param) && $param != '' && in_array($param, array('retail_sip', 'subsequent_contribution')))
            {
     			session()->invalidate();
				return redirect()->route('authentication', ['param' => $param]);
            
            	if($param == 'retail_sip')
            		return redirect()->route('retail_sip_personal_delails_page');
            	elseif($param == 'subsequent_contribution')
                	return redirect()->route('subsequent_contribution_personal_details_page');
                else
                	return redirect()->route('home');
            }
        	else
        		return redirect()->route('home');
        }

        return view('frontend.authentication');
    }

	public function authentication_submit_maintenance(Request $request)
    {
        $username = trim($request->input('user_name'));
        $password = trim($request->input('password'));

        if($username != env('MAINTENANCE_USERNAME') || $password != env('MAINTENANCE_PASSWORD'))
        {
     		return response()->json([
                'success' => false,
                'message' => 'User name or password does not match'
            ]);
        }
        else
        {
            session(['maintenance'=> true]);
            return response()->json([
                'message' => 'success',
                'status'=>200
            ]);
        }
	}

    public function authentication_submit(Request $request, Cams_lib $cams_lib, Nsdl_lib $nsdl_lib, Kfintech_lib $kfintech_lib, Sms_gateway_lib $sms_gateway_lib, Sync_data_lib $sync_data_lib)
    {
        session_set_ip_address();
        session_set_user_agent();

        $validator = Validator::make($request->all(), array(
            'pran' => ['required', 'numeric', 'digits:12'],
            'mobile' => 'required|string|min:10|max:15|regex:/^[0-9\+\-\(\)\s]*$/',
            'flexCheckDefault' => 'accepted',
        ));

        if($validator->fails()) 
        {
            $errors = $validator->errors();
            $firstError = $errors->first();
            $failedField = $errors->keys()[0];

            return response()->json(array('status' => 400, 'msg' => $firstError, 'id' => $failedField));
        }

        $requestData = $request->all();

        // Remove flexCheckDefault from the Request Data
        unset($requestData['flexCheckDefault']);

        $input_pran = $request->input('pran');
        $pran[] = $request->input('pran');
        $mobile = $request->input('mobile');

        session()->forget('pran');
        session()->forget('mobile');
        session()->forget('cra');
        session()->forget('is_other_pop_user');
        session()->forget('is_new_other_pop_user');

        $users = $this->authentication_model->get_user_by_pran($input_pran);
        // dd($users);
        $email = "";
        $user_authenticated = false;
        $failed_auth_with_existing_cra = false;
        $user_exist_with_invalid_cra = false;

        if($users) 
        {
            $latest_attempt = $this->authentication_model->find_last_attempt($input_pran);
            if($latest_attempt)
                $latest_attempt = get_object_vars($latest_attempt);

            if($latest_attempt['otp_resent_attempts'] > config('constants.MAX_OTP_RESENT_ATTEMPTS') && $latest_attempt['otp_last_attempt_on'] > time() + config('constants.OTP_LAST_ATTEMPT_TIMEOUT')) 
                return response()->json(array('status' => 400, 'msg' => 'OTP limit exceeded. Try again later.'));

            $reset_resend_otp_attempts = $this->authentication_model->reset_otp_resend_attempts($input_pran);
            $reset_login_otp_attempts = $this->authentication_model->reset_otp_login_attempts($input_pran);

            if($users->cra_account == 'CAMS') 
            {
                $sub_details_cams = $cams_lib->get_subscriber_details($request->input('pran'));

                if($sub_details_cams['http_status'] != 200) 
                    return response()->json(array('status' => 400, 'msg' => 'Technical issue (Connectivity issue with CRA), Please try again later.', 'id' => 'custom_err_msg'));
                
                if($sub_details_cams['api_response']['api']['statusCode'] != 200)
                    return response()->json(array('status' => 400, 'msg' => 'Technical Error. Please Try After Sometime.', 'id' => 'pran'));

                if($request->input('mobile') != remove_mob_no_country_code($sub_details_cams['api_response']['response']['details'][0]['mobile']))
                    return response()->json(array('status' => 400, 'msg' => 'Mobile no. does not match with PRAN', 'id' => 'mobile'));
                
                $pran_status = $cams_lib->resolve_pran_status($sub_details_cams['api_response']['response']['details'][0]['pranstatus']);
                if($pran_status != 'A') 
                    return response()->json(array('status' => 400, 'msg' => 'Pran Status is not Active', 'id' => 'pran'));

                if($sub_details_cams['api_response']['response']['details'][0]['email'])
                    $email = $sub_details_cams['api_response']['response']['details'][0]['email'];

                //Check state is present or not in database and cra 
                $result = $this->authentication_model->check_state($input_pran);

                // if(!isset($sub_details_cams['api_response']['response']['address'][0]['state']) && !$result)
                //     return response()->json(array('status' => 400, 'msg' => 'insuficent user details please contact your admin for more details.', 'id' => 'custom_err_msg')); 

                if(!isset($sub_details_cams['api_response']['response']['address'][0]['address']) || empty($sub_details_cams['api_response']['response']['address'][0]['address'])) 
                {
                    if(!isset($sub_details_cams['api_response']['response']['address'][1]['address']) || empty($sub_details_cams['api_response']['response']['address'][1]['address'])) 
                        return response()->json(array('status' => 400, 'msg' => 'Insufficient user details please contact your admin for more details.', 'id' => 'custom_err_msg'));

                    $add = $sub_details_cams['api_response']['response']['address'][1]['address'];
                    $result_maha = check_maha($add);

                    if($result_maha)
                        session(['state' => true]);
                    else
                        session(['state' => false]);
                } 
                else 
                {
                    $add = $sub_details_cams['api_response']['response']['address'][0]['address'];
                    $result_maha = check_maha($add);

                    if($result_maha)
                        session(['state' => true]);
                    else
                        session(['state' => false]);
                }

                session(['cra' => 'CAMS']);
                $user_authenticated = true;
            }
            elseif($users->cra_account == 'NSDL') 
            {
                if($users->is_other_pop_user != 'Y')
                {
                    $sub_details_nsdl = $nsdl_lib->get_subscriber_details($request->input('pran'), 'T1', array('PD'));

                    if($sub_details_nsdl['http_status'] != 200) 
                        return response()->json(array('status' => 400, 'msg' => 'Technical issue (Connectivity issue with CRA), Please try again later.', 'id' => 'custom_err_msg'));

                    if(isset($sub_details_nsdl['api_response']['fieldErrors'][0]['errorMsg']) && !empty($sub_details_nsdl['api_response']['fieldErrors'][0]['errorMsg']))
                        return response()->json(array('status' => 400, 'msg' => "Error: ".$sub_details_nsdl['api_response']['fieldErrors'][0]['errorMsg'], 'id' => 'custom_err_msg'));

                    if($request->input('mobile') != remove_mob_no_country_code($sub_details_nsdl['api_response']['personalDtlsForm']['mobileNo']))
                        return response()->json(array('status' => 400, 'msg' => 'Mobile no. does not match with PRAN', 'id' => 'mobile'));

                    $pran_status_nsdl = $nsdl_lib->resolve_pran_status($sub_details_nsdl['api_response']['personalDtlsForm']['pranSts']);
                    
                    if($pran_status_nsdl != 'A') 
                        return response()->json(array('status' => 400, 'msg' => 'Pran Status is not Active', 'id' => 'pran'));

                    if($sub_details_nsdl['api_response']['personalDtlsForm']['emailId'])
                        $email = $sub_details_nsdl['api_response']['personalDtlsForm']['emailId'];
                    
                    $result = $this->authentication_model->check_state($input_pran);

                    if(!isset($sub_details_nsdl['api_response']['personalDtlsForm']['curAddressCity']) && !$result) 
                        return response()->json(array('status' => 400, 'msg' => 'Insufficient user details please contact your admin for more details.', 'id' => 'custom_err_msg'));
                    
                    session(['state' => false]);

                    if($sub_details_nsdl['api_response']['personalDtlsForm']['curAddressCity'] == 'Maharashtra')
                        session(['state' => true]);
                                
                    session(['cra' => 'NSDL']);
                    $user_authenticated = true;
                }
                else
                {
                    if($request->input('mobile') != Crypt::decryptString($users->mobile_no))
                        return response()->json(array('status' => 400, 'msg' => 'Mobile no. does not match with PRAN', 'id' => 'mobile'));
    
                    $result = $this->authentication_model->return_state($input_pran);
                    if(!$result)
                        return response()->json(array('status' => 400, 'msg' => 'Insufficient user details please contact your admin for more details.', 'id' => 'custom_err_msg'));
                    
                    session(['state' => false]);
    
                    if($result == 'Maharashtra')
                        session(['state' => true]);
                    else
                        session(['state' => false]);
    
                    session(['cra' => $users->cra_account]);
                    session(['is_other_pop_user' => true]);
                    $user_authenticated = true;
                }
            }
            elseif($users->cra_account == 'KFINTECH') 
            {
                $sub_details_kfintech = $kfintech_lib->act_status($request->input('pran'));
                
                if(!isset($sub_details_kfintech['api_response']['Table1'])) 
                    return response()->json(array('status' => 400, 'msg' => 'Technical issue (Connectivity issue with CRA), Please try again later.', 'id' => 'custom_err_msg'));

                if($request->input('mobile') != remove_mob_no_country_code($sub_details_kfintech['api_response']['Table1'][0]['MobileNumber']))
                    return response()->json(array('status' => 400, 'msg' => 'Mobile no. does not match with PRAN', 'id' => 'mobile'));

                $pran_status_kfintech = $kfintech_lib->resolve_tier_status($sub_details_kfintech['api_response']['Table1'][0]['tier1StsCode']);
                
                if($pran_status_kfintech != 'A') 
                    return response()->json(array('status' => 400, 'msg' => 'Pran Status is not Active', 'id' => 'pran'));

                if($sub_details_kfintech['api_response']['Table1'][0]['Email'])
                    $email = $sub_details_kfintech['api_response']['Table1'][0]['Email'];
                
                $result = $this->authentication_model->return_state($input_pran);
                if(!$result)
                    return response()->json(array('status' => 400, 'msg' => 'Insufficient user details please contact your admin for more details.', 'id' => 'custom_err_msg'));
                
                session(['state' => false]);

                if($result == 'Maharashtra')
                    session(['state' => true]);
                else
                    session(['state' => false]);

                session(['cra' => 'KFINTECH']);
                $user_authenticated = true;
            }
            else
                $user_exist_with_invalid_cra = true;
        }
    
        if($user_exist_with_invalid_cra)
            return response()->json(array('status' => 400, 'msg' => 'Technical Error. User Exist With Wrong CRA Account', 'id' => 'custom_err_msg'));

        if(!$users || !$user_authenticated)
        {
            // Checking User in CAMS CRA
            $sub_details_cams = $cams_lib->get_subscriber_details($request->input('pran'));
            if($sub_details_cams['http_status'] == 200) 
            {
                if($request->input('mobile') != remove_mob_no_country_code($sub_details_cams['api_response']['response']['details'][0]['mobile']))
                    return response()->json(array('status' => 400, 'msg' => 'Mobile no. does not match with PRAN', 'id' => 'mobile'));

                $pran_status = $cams_lib->resolve_pran_status($sub_details_cams['api_response']['response']['details'][0]['pranstatus']);
                if($pran_status != 'A') 
                    return response()->json(array('status' => 400, 'msg' => 'Pran Status is not Active', 'id' => 'pran'));

                if(!$users)
                {
                    $inserted_data = $this->authentication_model->insert_user($request->input('pran'), $sub_details_cams['api_response']['response']['details'][0]['mobile'], 'CAMS');
                    if(!$inserted_data){
                        return response()->json(array('status' => 400, 'msg' => 'There is some issue. Please try after some time.', 'id' => 'mobile'));
                    }
                }

                if($sub_details_cams['api_response']['response']['details'][0]['email'])
                    $email = $sub_details_cams['api_response']['response']['details'][0]['email'];

                $result = $this->authentication_model->check_state($input_pran);
                
                if(!isset($sub_details_cams['api_response']['response']['address'][0]['address']) || empty($sub_details_cams['api_response']['response']['address'][0]['address'])) 
                {
                    if(!isset($sub_details_cams['api_response']['response']['address'][1]['address']) || empty($sub_details_cams['api_response']['response']['address'][1]['address']))
                        return response()->json(array('status' => 400, 'msg' => 'Insufficient user details please contact your admin for more details.', 'id' => 'custom_err_msg'));

                    $add = $sub_details_cams['api_response']['response']['address'][1]['address'];
                    $result_maha = check_maha($add);

                    if($result_maha)
                        session(['state' => true]);
                    else
                        session(['state' => false]);
                } 
                else 
                {
                    $add = $sub_details_cams['api_response']['response']['address'][0]['address'];
                    $result_maha = check_maha($add);

                    if($result_maha)
                        session(['state' => true]);
                    else
                        session(['state' => false]);
                }
                
                session(['cra' => 'CAMS']);
                $user_authenticated = true;
            }
        
            if(!$user_authenticated) 
            {
                // Checking User in KFINTECH CRA
                $sub_details_kfintech = $kfintech_lib->act_status($request->input('pran'));

                if(isset($sub_details_kfintech['api_response']['Table1'])) 
                {
                    if($sub_details_kfintech['api_response']['Table1'][0]['Email'])
                        $email = $sub_details_kfintech['api_response']['Table1'][0]['Email'];
                
                    if($request->input('mobile') != remove_mob_no_country_code($sub_details_kfintech['api_response']['Table1'][0]['MobileNumber']))
                        return response()->json(array('status' => 400, 'msg' => 'Mobile no. does not match with PRAN', 'id' => 'mobile'));

                    $pran_status_kfintech = $kfintech_lib->resolve_tier_status($sub_details_kfintech['api_response']['Table1'][0]['tier1StsCode']);
                    if($pran_status_kfintech == 'A') 
                        return response()->json(array('status' => 400, 'msg' => 'Pran Status is not Active', 'id' => 'pran'));
                    
                    if(!$users)
                    {
                        return response()->json(array('status' => 400, 'msg' => 'Insufficient user details please contact your admin for more details.', 'id' => 'custom_err_msg'));
                        
                        $inserted_data = $this->authentication_model->insert_user($request->input('pran'), $sub_details_kfintech['api_response']['Table1'][0]['MobileNumber'], 'KFINTECH');
                        if(!$inserted_data)
                            return response()->json(array('status' => 400, 'msg' => 'There is some issue please try after some time.', 'id' => 'mobile'));
                    }

                    $result = $this->authentication_model->return_state($input_pran);
                    if(!$result)
                        return response()->json(array('status' => 400, 'msg' => 'Insufficient user details please contact your admin for more details.', 'id' => 'custom_err_msg'));
                    
                    session(['state' => false]);
                    if($result == 'Maharashtra')
                        session(['state' => true]);
                    else
                        session(['state' => false]);

                    session(['cra' => 'KFINTECH']);
                    $user_authenticated = true;
                }
            }
        
        	if(!$user_authenticated) 
            {
                // Checking if User Belogs to other POP
                $nsdl_pop_details = get_settings('nsdl_cra', 'pop_credential');
                $act_status_details = $nsdl_lib->act_status($request->input('pran'));

                if($act_status_details && $act_status_details['http_status'] == 200 && isset($act_status_details['api_response']['tier1PopRegNo']) && $act_status_details['api_response']['tier1PopRegNo'] != $nsdl_pop_details['nsdl_cra']['pop_credential']['pop_reg_no'])
                {
                    $tier1_status_nsdl = $nsdl_lib->resolve_tier_status($act_status_details['api_response']['tier1StsCode']);
                    $tier2_status_nsdl = $nsdl_lib->resolve_tier_status($act_status_details['api_response']['tier2StsCode']);
                    $pran_status_nsdl = (($tier1_status_nsdl == 'A' || $tier2_status_nsdl == 'A') ? 'A' : NULL);
                    if($pran_status_nsdl != 'A') 
                        return response()->json(array('status' => 400, 'msg' => 'Pran Status is not Active', 'id' => 'pran'));

                    session(['pran' => $request->input('pran'), 'mobile' => $request->input('mobile'), 'cra' => 'NSDL', 'is_other_pop_user' => true, 'is_new_other_pop_user' => true]);
                    
                    return response()->json(array('status' => 400, 'msg' => 'Your PRAN is associated with a different POP. To proceed, we will need some additional details from you.', 'is_other_pop_user' => true));
                }

                // Checking User in NSDL CRA
                $sub_details_nsdl = $nsdl_lib->get_subscriber_details($request->input('pran'), 'T1', array('PD'));
                if($sub_details_nsdl['http_status'] == 200) 
                {
                    if(isset($sub_details_nsdl['api_response']['fieldErrors'][0]['errorMsg']) && !empty($sub_details_nsdl['api_response']['fieldErrors'][0]['errorMsg']))
                        return response()->json(array('status' => 400, 'msg' => "Error: ".$sub_details_nsdl['api_response']['fieldErrors'][0]['errorMsg'], 'id' => 'custom_err_msg'));

        	        if(!isset($sub_details_nsdl['api_response']['personalDtlsForm']['mobileNo']) || $request->input('mobile') != remove_mob_no_country_code($sub_details_nsdl['api_response']['personalDtlsForm']['mobileNo']))
                        return response()->json(array('status' => 400, 'msg' => 'Mobile no. does not match with PRAN', 'id' => 'mobile'));
                    
                    $pran_status_nsdl = $nsdl_lib->resolve_pran_status($sub_details_nsdl['api_response']['personalDtlsForm']['pranSts']);
                    if($pran_status_nsdl != 'A') 
                        return response()->json(array('status' => 400, 'msg' => 'Pran Status is not Active', 'id' => 'pran'));
                    
                    if(!$users)
                    {
                        $inserted_data = $this->authentication_model->insert_user($request->input('pran'), $sub_details_nsdl['api_response']['personalDtlsForm']['mobileNo'], 'NSDL');
                        if(!$inserted_data)
                            return response()->json(array('status' => 400, 'msg' => 'There is some issue please try after some time.', 'id' => 'mobile'));
                    }

                    if($sub_details_nsdl['api_response']['personalDtlsForm']['emailId'])
                        $email = $sub_details_nsdl['api_response']['personalDtlsForm']['emailId'];

                    $result = $this->authentication_model->check_state($input_pran);

                    if(!isset($sub_details_nsdl['api_response']['personalDtlsForm']['curAddressCity']) && !$result) 
                        return response()->json(array('status' => 400, 'msg' => 'Insufficient user details please contact your admin for more details.', 'id' => 'custom_err_msg'));
                        
                    session(['state' => false]);
                    if($sub_details_nsdl['api_response']['personalDtlsForm']['curAddressCity'] == 'Maharashtra')
                        session(['state' => true]);

                    session(['cra' => 'NSDL']);
                    $user_authenticated = true; 
                }
            }
        }
    
        if(!$user_authenticated)
            return response()->json(array('status' => 400, 'msg' => 'PRAN not found or There is a technical error connecting to your CRA.', 'id' => 'custom_err_msg'));

        $users_list = array((object) ['pran' => $input_pran, 'cra_account' => session('cra'), 'is_other_pop_user' => ((session()->has('is_other_pop_user') && session('is_other_pop_user')) ? 'Y' : 'N')]);

        $sync_response = $sync_data_lib->sync_subscriber_data($users_list);

        if($sync_response !== true)
            return response()->json(array('status' => 400, 'msg' => 'Technical Error. Please try after sometime.', 'id' => 'custom_err_msg'));

        if(session('otp_time')) 
            return response()->json(array('status' => 200, 'msg' => 'Success'));

        $random_otp_number = rand(100000, 999999);
        session(['random_otp_number' => $random_otp_number]);
        $insert_otp = $this->authentication_model->insert_otp($input_pran, encrypted($mobile), encrypted($email), $random_otp_number);
        $this->authentication_model->expire_all_otp_except_current_id($input_pran, $mobile, $insert_otp);  
    
        if(!$insert_otp)
            return response()->json(array('status' => 400, 'msg' => 'Technical Error. Please try after sometime.', 'id' => 'custom_err_msg'));
    
        session(['pran' => $request->input('pran')]);
        session(['mobile' => $request->input('mobile')]);
        session(['email' => $email]);
        session(['otp_id' => $insert_otp]);
        $request->session()->put('validated_data', $requestData);
        session(['otp_page_session' => 'set']);

        session()->forget('otp_time');
        session()->forget('otp_duration');
        
        return response()->json(array('status' => 200, 'msg' => 'Success'));
    }

    public function verify_additional_details(Request $request, Nsdl_lib $nsdl_lib)
    {
        $validator = Validator::make($request->all(), [
            'user_pran' => ['required', 'numeric', 'digits:12'],
            'user_mobile' => ['required', 'string', 'min:10', 'max:15', 'regex:/^[0-9\+\-\(\)\s]*$/'],
            'email' => ['required', 'email', 'max:255'],
            'state' => ['required', 'string', 'max:100'],
            'dob' => ['required', 'date_format:d/m/Y', 'before:today'],
            'terms_and_conditions_check' => 'accepted'
        ],
        [
            // Custom messages for PRAN
            'user_pran.required' => 'PRAN is required.',
            'user_pran.numeric' => 'PRAN should be a number.',
            'user_pran.digits' => 'PRAN should be 12 digits.',

            // Custom messages for Mobile
            'user_mobile.required' => 'Mobile number is required.',
            'user_mobile.min' => 'Mobile number must be at least 10 digits.',
            'user_mobile.max' => 'Mobile number must not exceed 15 digits.',
            'user_mobile.regex' => 'Mobile number format is invalid.',

            // Custom messages for Email
            'email.required' => 'Email address is required.',
            'email.email' => 'Enter a valid email address.',
            'email.max' => 'Email address must not exceed 255 characters.',

            // Custom messages for State
            'state.required' => 'State is required.',
            'state.max' => 'State must not exceed 100 characters.',

            // Custom messages for DOB
            'dob.required' => 'Date of birth is required.',
            'dob.date_format' => 'Date of birth format must be DD/MM/YYYY.',
            'dob.before' => 'Date of birth must be before today.'
        ]);

        if($validator->fails()) 
        {
            $errors = $validator->errors();
            $first_error = $errors->first();
            $failed_field = $errors->keys()[0];

            return response()->json(array('status' => 400, 'msg' => $first_error, 'id' => $failed_field));
        }

        if(!session()->has('is_new_other_pop_user') || !session('is_new_other_pop_user') || !session()->has('is_other_pop_user') || !session('is_other_pop_user') || !session()->has('pran') || !session()->has('cra'))
            return response()->json(array('status' => 400, 'msg' => 'Technical Error. Please Try Again.'));

        $user_pran = $request->user_pran;
        $user_mobile = $request->user_mobile;
        $email = $request->email;
        $state = $request->state;
        $dob = $request->dob;

        if($user_pran != session('pran') || $user_mobile != session('mobile'))
            return response()->json(array('status' => 400, 'msg' => 'Technical Error. Pran or Mobile Number is not matching with your previous input.'));

        // Checking if User Belogs to other POP
        $nsdl_pop_details = get_settings('nsdl_cra', 'pop_credential');
        $act_status_details = $nsdl_lib->act_status($user_pran);

        if(!$act_status_details || $act_status_details['http_status'] != 200)
            return response()->json(array('status' => 400, 'msg' => 'Technical Error. Facing issue connecting to CRA.'));

        if($act_status_details['api_response']['tier1PopRegNo'] == $nsdl_pop_details['nsdl_cra']['pop_credential']['pop_reg_no'])
            return response()->json(array('status' => 400, 'msg' => 'Technical Error. The PRAN does not belongs to different POP.'));

        $tier1_status_nsdl = $nsdl_lib->resolve_tier_status($act_status_details['api_response']['tier1StsCode']);
        $tier2_status_nsdl = $nsdl_lib->resolve_tier_status($act_status_details['api_response']['tier2StsCode']);
        $pran_status_nsdl = (($tier1_status_nsdl == 'A' || $tier2_status_nsdl == 'A') ? 'A' : NULL);
        if($pran_status_nsdl != 'A') 
            return response()->json(array('status' => 400, 'msg' => 'Pran Status is not Active'));

        if($dob != $act_status_details['api_response']['dob'])
            return response()->json(array('status' => 400, 'msg' => 'The provided details do not match the records associated with your PRAN. Please verify and try again.'));

        session(
            array(
                'user_additional_details' => array(
                    'email' => $email,
                    'state' => $state,
                    'dob' => $dob
                )
            )
        );

        $random_otp_number = rand(100000, 999999);
        session(['random_otp_number' => $random_otp_number]);
        $insert_otp = $this->authentication_model->insert_otp(session('pran'), encrypted(session('mobile')), encrypted($email), $random_otp_number);
        $this->authentication_model->expire_all_otp_except_current_id(session('pran'), session('mobile'), $insert_otp);  

        session()->put('validated_data', array('pran' => session('pran'), 'mobile' => session('mobile')));
        session(['email' => $email]);
        session(['otp_id' => $insert_otp]);
        session(['otp_page_session' => 'set']);

        session()->forget('otp_time');
        session()->forget('otp_duration');

        return response()->json(array('status' => 200, 'msg' => 'Success'));
    }

    public function send_otp_to_mobile_auth(Sms_gateway_lib $sms_gateway_lib)
    {
        $sessionData = session()->all();

        if(array_key_exists('mobile', $sessionData['validated_data'])) 
            $sessionData['validated_data']['mob_num'] = $sessionData['validated_data']['mobile'];
    
        $result = $this->authentication_model->user_otp_cred();
		$to = '91' . $sessionData['validated_data']['mob_num'];
   		$msg = str_replace('[%OTP%]', session('random_otp_number'), $result[0]->message);

    	if(env('SEND_LOGIN_OTP', true))
        	$response = $sms_gateway_lib->send_sms($to,$msg,$result[0]->template_id);
    	else
        	$response = array('status' => 200, 'msg' => 'SMS Sent Successfully');
    
        return $response;
    }

    public function user_otp_page()
    {
        if(!session()->has('otp_page_session') || (session()->has('otp_page_session') && session('otp_page_session') != 'set')) 
            return redirect()->route('authentication');
        
        $data = array();
        $data['time'] = false;

        if(!session()->has('is_new_other_pop_user') || !session('is_new_other_pop_user'))
        {
            $result = $this->authentication_model->fetch_updated_on(session('validated_data.pran'));

            if($result[0]->otp_resent_attempt_on)
            {
                $otp_updated_time = $result[0]->otp_resent_attempt_on;
                $current_time = time();
                $time_difference = $current_time - $otp_updated_time;    
                if($time_difference < 59) 
                {
                    $time_left = 59 - $time_difference;
                    $data['time'] = $time_left;
                }
                else
                    $data['time'] = false;
            }
        }

        return view('frontend.user_otp', $data);
    }

    public function check_otp_valid_or_not(Request $request, Cams_lib $cams_lib, Nsdl_lib $nsdl_lib, Kfintech_lib $kfintech_lib)
    {
        $validator = Validator::make($request->all(), [
            'final_input' => 'required|string'
        ]);

        if($validator->fails())
            return response()->json(array('status' => 400, 'msg' => 'OTP cannot be blanked'));

        $sessionData = session()->all();
    
        if(!session()->has('is_new_other_pop_user') || !session('is_new_other_pop_user'))
        {
            $latest_attempt = $this->authentication_model->find_last_attempt($sessionData['validated_data']['pran']);

            if($latest_attempt)
                $latest_attempt = get_object_vars($latest_attempt);

            $attempt_left = abs($latest_attempt['otp_login_attempts'] - 2);
            $attempt_left_msg = 'Wrong OTP ' . $attempt_left . ' attempt left.';

            if($attempt_left == 0)
                $attempt_left_msg = 'Maximum login attempts exceeded';

            session()->forget('otp_login_attempts');

            if($latest_attempt['otp_login_attempts'] > 1) 
            {
                session(['otp_login_attempts' => 'set']);
                session(['otp_time' => time()]);
                session(['otp_duration' => 600]);
                return response()->json(array('status' => 400, 'msg' => 'Maximum login attempts exceeded.'));
            } 
            else 
            {
                session()->forget('otp_time');
                session()->forget('otp_duration');
                session()->forget('otp_login_attempts');
            }

            $attempt = intval($latest_attempt['otp_login_attempts']);
            $attempt = $attempt + 1;
            $update_attempt = $this->authentication_model->add_otp_login_attempts($sessionData['validated_data']['pran'], $attempt);
            if(!$update_attempt)
                return response()->json(array('status' => 400, 'msg' => 'Error!!'));
        }
        else
            $attempt_left_msg = 'Wrong OTP';

        $id = session('otp_id');
        if($request->input('final_input'))
            $finalInput = $request->input('final_input');
    
        // $userOTPDetails = $this->authentication_model->get_user_otp_details_by_id($id);
        // $userOTPDetails = $this->authentication_model->check_otp_by_pran_and_mobile(session('pran'),session('mobile'),$request->input('final_input'));
    
        $userOTPDetails = $this->authentication_model->check_otp_by_pran_and_mobile(session('pran'), session('mobile'));
        if($userOTPDetails)
            $this->authentication_model->expire_all_otp_except_current_id(session('pran'), session('mobile'),$id);  
 
        if(!session()->has('is_other_pop_user') || !session('is_other_pop_user'))
        {
            if(!$userOTPDetails)
                return response()->json(array('status' => 400, 'msg' => 'Wrong OTP ' . $attempt_left . ' attempt left.'));
        }
        else
        {
            if(!$userOTPDetails)
                return response()->json(array('status' => 400, 'msg' => 'Wrong OTP.'));
        }

        // Check if the OTP matches and status is 'N'
        if(env('USER_LOGIN_MASTER_OTP', $userOTPDetails->otp) != $finalInput) 
            return response()->json(array('status' => 400, 'msg' => $attempt_left_msg));

        $pran = session('pran');
    
        if($userOTPDetails) 
        {
            $pran_arr[] = $pran;
            if($pran)
                session(['api_subscriber_pran' => $pran]);

            if(session('cra') == 'CAMS') 
            {
                $sub_tier_details_cams = $cams_lib->get_subscriber_tier_status($pran_arr);

                $sub_details_cams = $cams_lib->get_subscriber_details($pran);
                if($sub_details_cams['api_response']['response']['details'][0]['name'])
                    session(['api_subscriber_name' => $sub_details_cams['api_response']['response']['details'][0]['name']]);

                if($sub_details_cams['api_response']['response']['details'][0]['mobile'])
                    session(['api_subscriber_mobile' => remove_mob_no_country_code($sub_details_cams['api_response']['response']['details'][0]['mobile'])]);


                if($sub_details_cams['api_response']['response']['details'][0]['email'])
                    session(['api_subscriber_email' => $sub_details_cams['api_response']['response']['details'][0]['email']]);

                if(isset($sub_tier_details_cams['api_response']['response']['pran_details']['success'][0]['details']['tier1_status'])) 
                {
                    $result_tier_1_status_cams = $cams_lib->resolve_tier_status($sub_tier_details_cams['api_response']['response']['pran_details']['success'][0]['details']['tier1_status']);

                    if($result_tier_1_status_cams == 'A')
                        session(['api_subscriber_tier_1' => $result_tier_1_status_cams]);
                    else
                        session()->forget('api_subscriber_tier_1');
                }

                if(isset($sub_tier_details_cams['api_response']['response']['pran_details']['success'][0]['details']['tier2_status'])) 
                {
                    $result_tier_2_status_cams = $cams_lib->resolve_tier_status($sub_tier_details_cams['api_response']['response']['pran_details']['success'][0]['details']['tier2_status']);

                    if($result_tier_2_status_cams == 'A')
                        session(['api_subscriber_tier_2' => $result_tier_2_status_cams]);
                    else
                        session()->forget('api_subscriber_tier_2');
                }
            } 
            elseif(session('cra') == 'NSDL') 
            {
                $sub_tier_details_nsdl = $nsdl_lib->act_status($pran);
                $fname = "";
                $mname = "";
                $lname = "";

                if(session()->has('is_other_pop_user'))
                {
                    session(['api_subscriber_name' => $sub_tier_details_nsdl['api_response']['username']]);
                    session(['api_subscriber_mobile' => remove_mob_no_country_code(session('mobile'))]);
                    session(['api_subscriber_email' => session('email')]);
                }
                else
                {
                    $sub_details_nsdl = $nsdl_lib->get_subscriber_details($pran, 'T1', array('PD'));

                    if($sub_details_nsdl['api_response']['personalDtlsForm']['subFName'])
                        $fname = $sub_details_nsdl['api_response']['personalDtlsForm']['subFName'];

                    if($sub_details_nsdl['api_response']['personalDtlsForm']['subMName'])
                        $mname = $sub_details_nsdl['api_response']['personalDtlsForm']['subMName'];

                    if($sub_details_nsdl['api_response']['personalDtlsForm']['subLName'])
                        $lname = $sub_details_nsdl['api_response']['personalDtlsForm']['subLName'];

                    $full_name = $fname . ' ' . $mname . ' ' . $lname;
                    session(['api_subscriber_name' => $full_name]);

                    if($sub_details_nsdl['api_response']['personalDtlsForm']['mobileNo'])
                        session(['api_subscriber_mobile' => remove_mob_no_country_code($sub_details_nsdl['api_response']['personalDtlsForm']['mobileNo'])]);

                    if($sub_details_nsdl['api_response']['personalDtlsForm']['emailId'])
                        session(['api_subscriber_email' => $sub_details_nsdl['api_response']['personalDtlsForm']['emailId']]);
                }

                $result_tier_1_status_nsdl = "";
                if(isset($sub_tier_details_nsdl['api_response']['tier1StsCode']))
                    $result_tier_1_status_nsdl = $nsdl_lib->resolve_tier_status($sub_tier_details_nsdl['api_response']['tier1StsCode']);

                if($result_tier_1_status_nsdl == 'A')
                    session(['api_subscriber_tier_1' => $result_tier_1_status_nsdl]);
                else
                    session()->forget('api_subscriber_tier_1');

                $result_tier_2_status_nsdl = "";
                if(isset($sub_tier_details_nsdl['api_response']['tier2StsCode']))
                    $result_tier_2_status_nsdl = $nsdl_lib->resolve_tier_status($sub_tier_details_nsdl['api_response']['tier2StsCode']);

                if($result_tier_2_status_nsdl == 'A')
                    session(['api_subscriber_tier_2' => $result_tier_2_status_nsdl]);
                else
                    session()->forget('api_subscriber_tier_2');
            } 
            elseif (session('cra') == 'KFINTECH') 
            {
                $sub_details_kfintech = $kfintech_lib->act_status($pran);

                if($sub_details_kfintech['api_response']['Table1'][0]['username'])
                    session(['api_subscriber_name' => $sub_details_kfintech['api_response']['Table1'][0]['username']]);

                if($sub_details_kfintech['api_response']['Table1'][0]['MobileNumber'])
                    session(['api_subscriber_mobile' => remove_mob_no_country_code($sub_details_kfintech['api_response']['Table1'][0]['MobileNumber'])]);

                if($sub_details_kfintech['api_response']['Table1'][0]['Email'])
                    session(['api_subscriber_email' => $sub_details_kfintech['api_response']['Table1'][0]['Email']]);

                if($sub_details_kfintech['api_response']['Table1'][0]['PAN'])
                    session(['api_subscriber_pan' => $sub_details_kfintech['api_response']['Table1'][0]['PAN']]);

                $result_tier_1_status_kfintech = "";
                if(isset($sub_details_kfintech['api_response']['Table1'][0]['tier1StsCode']))
                    $result_tier_1_status_kfintech = $kfintech_lib->resolve_tier_status($sub_details_kfintech['api_response']['Table1'][0]['tier1StsCode']);

                if($result_tier_1_status_kfintech) 
                {
                    if($result_tier_1_status_kfintech == 'A')
                        session(['api_subscriber_tier_1' => $result_tier_1_status_kfintech]);
                    else
                        session()->forget('api_subscriber_tier_1');
                }

                $result_tier_2_status_kfintech = "";
                if(isset($sub_details_kfintech['api_response']['Table1'][0]['tier2StsCode']))
                    $result_tier_2_status_kfintech = $kfintech_lib->resolve_tier_status($sub_details_kfintech['api_response']['Table1'][0]['tier2StsCode']);
                
                if($result_tier_2_status_kfintech) 
                {
                    if($result_tier_2_status_kfintech == 'A')
                        session(['api_subscriber_tier_2' => $result_tier_2_status_kfintech]);
                    else
                        session()->forget('api_subscriber_tier_2');
                }
            }
        }

        $updated = $this->authentication_model->update_otp_status_to_u($id);

        // if(!$updated) {
        //     return response()->json(array('status' => 400, 'msg' => 'otp not updated'));
        // }
        if(session()->has('is_new_other_pop_user') && session('is_new_other_pop_user'))
        {
            $act_status_details = $nsdl_lib->act_status(session('pran'));

            if(!$act_status_details || $act_status_details['http_status'] != 200)
                return response()->json(array('status' => 400, 'msg' => 'Technical Error. Facing issue connecting to CRA.'));

            $tier1_status_nsdl = $nsdl_lib->resolve_tier_status($act_status_details['api_response']['tier1StsCode']);
            $tier2_status_nsdl = $nsdl_lib->resolve_tier_status($act_status_details['api_response']['tier2StsCode']);
            $pran_status_nsdl = (($tier1_status_nsdl == 'A' || $tier2_status_nsdl == 'A') ? 'A' : NULL);

            $inserted_user_id = $this->authentication_model->insert_new_user(
                array(
                    'pran' => session('pran'), 
                    'full_name' => Crypt::encryptString($act_status_details['api_response']['username']), 
                    'fname' => Crypt::encryptString($act_status_details['api_response']['username']), 
                    'mname' => NULL, 
                    'lname' => NULL, 
                    'mobile_no' => Crypt::encryptString(session('mobile')), 
                    'email' => Crypt::encryptString(session('user_additional_details.email')), 
                    'cra_account' => session('cra'), 
                    'custom_state' => Crypt::encryptString(session('user_additional_details.state')), 
                    'pran_status' => $pran_status_nsdl, 
                    'tier1_status' => $tier1_status_nsdl, 
                    'tier2_status' => $tier2_status_nsdl, 
                    'pop_user_status' => 'A', 
                    'is_other_pop_user' => 'Y', 
                    'pop_reg_no' => $act_status_details['api_response']['tier1PopRegNo'],
                    'pop_sp_reg_no' => $act_status_details['api_response']['tier1PopSpRegNo'],
                    'cra_sync_status' => 'D', 
                    'cra_last_sync_on' => time(), 
                    'created_on' => time()
                )
            );

            if(!$inserted_user_id)
                return response()->json(array('status' => 400, 'msg' => 'Technical Error.'));

            $this->authentication_model->insert_user_additional_details(array('pran' => session('pran'), 'birthdate' => Crypt::encryptString($act_status_details['api_response']['dob']), 'created_on' => time()));
        
            $latest_attempt = $this->authentication_model->find_last_attempt($sessionData['validated_data']['pran']);

            if($latest_attempt)
                $latest_attempt = get_object_vars($latest_attempt);
        }

        session(['logged_in' => true]);

        session()->forget('otp_time');
        session()->forget('otp_duration');
        session()->forget('random_otp_number');
        session()->forget('otp_id');
        session()->forget('otp_page_session');
        session()->forget('user_additional_details');
        session()->forget('is_new_other_pop_user');

        insert_user_access_log($pran, $latest_attempt['otp_login_attempts'], $latest_attempt['otp_resent_attempts']);

        $users = $this->authentication_model->get_user_by_pran(session('pran'));
        if(!$users)
            $inserted_data = $this->authentication_model->insert_user(session('pran'), session('api_subscriber_mobile'), session('cra'));

        return response()->json(array('status' => 200, 'msg' => 'success', 'auth_param' => session('user_auth')));
    }

    function resent_otp(Sms_gateway_lib $sms_gateway_lib)
    { 
        $sessionData = session()->all();
    
        $flag=true;
        $result = $this->authentication_model->fetch_updated_on($sessionData['validated_data']['pran']);
        
        // this code will update the resend_attempt to zero 
        if($result[0]->otp_resent_attempt_on)
        {
            $otp_updated_time = $result[0]->otp_resent_attempt_on; // Your saved OTP timestamp
            $current_time = time(); // Get the current time as a Unix timestamp
            $time_difference = $current_time - $otp_updated_time; // Calculate the time difference in seconds

	        if($time_difference >= 300) // 600 seconds = 10 minutes
            { 
                $this->authentication_model->reset_otp_resend_attempts($sessionData['validated_data']['pran']);
                $flag=false;
		    }
            else
            {
                $time_left_after = 300 - $time_difference;
                // Convert seconds to minutes and seconds
                // Convert seconds to minutes
                $minutes_left = floor($time_left_after / 60);

                // Optionally, check if minutes_left is less than 0
                if($minutes_left <= 0)
                    $minutes_left = 1; // Ensure it doesn't go below 0
            }
        }
        // return $result[0]->updated_on;
    
    
        // Check if required session data is set
        if(!isset($sessionData['validated_data']['pran']) || !isset($sessionData['validated_data']['mobile']))
            return response()->json(array('status' => 400, 'msg' => 'Session data not set'));

        $latest_attempt = $this->authentication_model->find_last_attempt($sessionData['validated_data']['pran']);
        if($latest_attempt)
            $latest_attempt = get_object_vars($latest_attempt);

        if($latest_attempt['otp_resent_attempts'] > config('constants.MAX_OTP_RESENT_ATTEMPTS'))
            return response()->json(array('status' => 500, 'msg' => 'OTP limit exceeded. Try again after','time_left'=>$time_left_after));

        // resend otp 30 second--start
        if($flag)
        {
            // $result =   DB::table('users')->where('pran', $sessionData['validated_data']['pran'])->select('updated_on') ->get();
            $result = $this->authentication_model->fetch_updated_on($sessionData['validated_data']['pran']);
            
            if($result[0]->otp_resent_attempt_on)
            {
                $otp_updated_time = $result[0]->otp_resent_attempt_on;
                $current_time = time();
                $time_difference = $current_time - $otp_updated_time;    

                if($time_difference < 59) 
                {
                    // Less than 30 seconds, show how much time is left
                    $time_left = 59 - $time_difference;
                    return false ;
                    // return response()->json(array('status' => 400, 'msg' => "Please wait " . $time_left . "  seconds"));
                }
            }
        }
        // resend otp 30 second--end

        $random_otp_number = rand(100000, 999999);
        session(['random_otp_number' => $random_otp_number]);
    
        $pran = $sessionData['validated_data']['pran'];
        $mobile = $sessionData['validated_data']['mobile'];

        $insert_otp = $this->authentication_model->insert_otp($pran, encrypted($mobile),encrypted(session('email')), $random_otp_number);
        if(!$insert_otp)
            return response()->json(array('status' => 400, 'msg' => 'Error getting otp'));

        $this->expired_otp();

        session(['otp_id' => $insert_otp]);
        $attempt = intval($latest_attempt['otp_resent_attempts']);
        $attempt = $attempt + 1;

        $update_attempt = $this->authentication_model->add_otp_resent_attempts($pran, $attempt);
        if(!$update_attempt)
            return response()->json(array('status' => 400, 'msg' => 'Error!!'));

        return response()->json(array('status' => 200, 'msg' => 'Success'));
    }

    public function expired_otp()
    {
        $id = session('otp_id');
        $id = $id - 1;

        $updated = $this->authentication_model->update_otp_status_to_e($id);
        if($updated) 
        {
            session()->forget('otp_id');
            return response()->json(array('status' => 200, 'msg' => 'Success'));
        }
    }

    public function make_otp_last_attempt_0()
    {
        session()->forget('otp_time');
        session()->forget('otp_duration');
        $sessionData = session()->all();
        if($sessionData['validated_data']['pran'])
            $pran = $sessionData['validated_data']['pran'];
        
        $login_response = $this->authentication_model->reset_otp_login_attempts($pran);

        if($login_response)
            return response()->json(array('status' => 200, 'msg' => 'Success'));
    }
}
