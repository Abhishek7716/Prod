<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\cpan\Login;
use App\Http\Controllers\cpan\Nodal_officer_login;

use App\Http\Controllers\cpan\Change_Password;
use App\Http\Controllers\cpan\Uploadcsv_pran;
use App\Http\Controllers\cpan\Change_Password_new_user;


use App\Http\Controllers\cpan\Upload_pran_csv;
use App\Http\Controllers\cpan\Ckyc;

use App\Http\Controllers\cpan\Corporate_contribution;
use App\Http\Controllers\cpan\Testing_apg_api;
use App\Http\Controllers\cpan\Pran_shifting;
use App\Http\Controllers\cpan\Corporate_modification;
use App\Http\Controllers\cpan\Contribution_history;
use App\Http\Controllers\cpan\Operation_contribution_history;




use App\Http\Controllers\cpan\Corporate_mapping_list;
use App\Http\Controllers\Forget_Password;
use App\Http\Controllers\Corporate_mapping;
use App\Http\Middleware\EnsureAdminIsValid;


use App\Http\Middleware\EnsureUserIsValid;

use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Authentication;
use App\Http\Controllers\Retail_sip;
use App\Http\Controllers\Subsequent_contribution;
use App\Http\Controllers\cpan\User_management;
use App\Http\Controllers\cpan\Permissions_management;
use App\Http\Controllers\cpan\Subsequent_contribution_list;
use App\Http\Controllers\cpan\Sip_management;
use App\Http\Controllers\cpan\Cron_logs;
use App\Http\Controllers\cpan\Download_report;
use App\Http\Controllers\cpan\Charge_matrix;

use App\Http\Controllers\Home;
use App\Http\Controllers\Usernft;
use App\Http\Controllers\cpan\Nft_list;
use App\Http\Controllers\S2s_response;
use App\Http\Controllers\Testing;

Route::get('/clear-all-caches', function() {
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
	// Artisan::call('optimize');
    return "All caches cleared!";
});

Route::post('set_retry', [Subsequent_contribution::class, 'set_retry'])->name('set_retry');
Route::post('set_retry', [Usernft::class, 'set_retry'])->name('set_retry');
Route::get('testing', [Testing::class, 'index']);
Route::get('subscriber_details', [Testing::class, 'subscriber_details']);
Route::get('get_settings', [Testing::class, 'get_settings']);

Route::post('s2s_response/refund_response', [S2s_response::class, 'refund_response'])->name('refund_response');
Route::post('s2s_response/mandate_response', [S2s_response::class, 'mandate_response'])->name('mandate_response');
Route::post('s2s_response/payment_response', [S2s_response::class, 'payment_response'])->name('payment_response');

Route::post('retail_sip_check_polling_status', [Retail_sip::class, 'retail_sip_check_polling_status'])->name('retail_sip_check_polling_status');
Route::post('subsequent_check_polling_status', [Subsequent_contribution::class, 'subsequent_check_polling_status'])->name('subsequent_check_polling_status');
Route::post('usernft_check_polling_status', [Usernft::class, 'usernft_check_polling_status'])->name('usernft_check_polling_status');

Route::get('/', [Authentication::class, 'index']);

Route::get('/maintenance', function () {
return view('frontend.authentication_maintenance');
});


// Route::get('cron_logs', function () {
// return view('cpan.cron_logs');
// });

Route::get('/run_cron/{param?}', function(Request $request, $param = false) {
	if($param)
    {
    	// Get all available Artisan commands
        $commands = Artisan::all();
    
    	// Check if the command exists
        if(array_key_exists('cron:' . $param, $commands))
			Artisan::call('cron:'.$param);
   		else
        	abort(403, "Access Denied!");
    }
	else
    	abort(404, "Page Not Found!");
});

Route::get('/get_timestamp/{date?}', function(Request $request, $date = false) {
	if($date)
		echo strtotime($date);
});

// Route::get('/error_page', function () {
// 	// dd(session()->all());
// 	// if (!$request->session()->has('subsequent_set_retry')) {
//     				// session()->put('retail_sip_set_retry', 2);
// 				// }
//     return view('frontend.error_page');
// 	// return config('app.url');

// })->name('error_page');

Route::get('/error_page', [Retail_sip::class, 'error_page'])->name('error_page');



Route::get('test_res', [Retail_sip::class, 'test_res'])->name('test_res');

// jio_payment_response
Route::post('subsequent_contribution_jio_payment_response', [Subsequent_contribution::class, 'jio_payment_response'])->name('subsequent_contribution_jio_payment_response');
Route::post('retail_sip_jio_payment_response', [Retail_sip::class, 'jio_payment_response'])->name('retail_sip_jio_payment_response');


Route::get('subsequent_contribution_jio_payment', [Subsequent_contribution::class, 'jio_payment'])->name('subsequent_contribution_jio_payment');
Route::get('retail_sip_jio_payment', [Retail_sip::class, 'jio_payment'])->name('retail_sip_jio_payment');

Route::get('usernft_contribution_jio_payment', [Usernft::class, 'jio_payment'])->name('usernft_contribution_jio_payment');
Route::post('usernft_contribution_jio_payment_response', [Usernft::class, 'jio_payment_response'])->name('usernft_contribution_jio_payment_response');
// Route::get('jio_payment', function () { return dd(42324); });


Route::get('corporate_mapping', [Corporate_mapping::class, 'index'])->name(('corporate_mapping'));
Route::get('otp', function () {
    return view('frontend.otp');
})->name('otp');


Route::get('download_receipt_sip', function () {
    return view('frontend.download_receipt_sip');
})->name('download_receipt_sip');

 Route::get('email_templates', function () {
    return view('frontend.email_templates.sip_initiation_successful');
	})->name('email_templates');


Route::post('add_corporate_mapping', [Corporate_mapping::class, 'add_corporate_mapping'])->name(('add_corporate_mapping'));

Route::post('expired_otp', [Corporate_mapping::class, 'expired_otp'])->name(('expired_otp'));
Route::post('check_otp_valid_or_not_corporate_mapping', [Corporate_mapping::class, 'check_otp_valid_or_not_corporate_mapping'])->name(('check_otp_valid_or_not_corporate_mapping'));
Route::post('send_otp_to_mobile', [Corporate_mapping::class, 'send_otp_to_mobile'])->name(('send_otp_to_mobile'));


Route::get('thank_you_page', function () {
    return view('frontend.thank_you_page');
})->name('thank_you_page');


Route::get('loader', function () {
    return view('frontend.loader');
})->name('loader');


Route::get('loader_subsequent', function () {
    return view('frontend.loader_subsequent');
})->name('loader_subsequent');


//frontend flow ------(start)
Route::get('redirect_login', [Home::class, 'redirect_login'])->name(('redirect_login'))->middleware(EnsureUserIsValid::class);

Route::get('home', [Home::class, 'index'])->name(('home'))->middleware(EnsureUserIsValid::class);
Route::get('view_all_transactions', [Home::class, 'view_all_transactions'])->name(('view_all_transactions'))->middleware(EnsureUserIsValid::class);
Route::post('get_all_transactions_datatable', [Home::class, 'get_all_transactions_datatable'])->name(('get_all_transactions_datatable'));
Route::post('reset_date', [Home::class, 'reset_date'])->name('reset_date');
Route::post('set_sip_status', [Home::class, 'set_sip_status'])->name('set_sip_status');
Route::post('set_contribution_status', [Home::class, 'set_contribution_status'])->name('set_contribution_status');
Route::post('home/get_user_sip_details', [Home::class, 'get_user_sip_details']);
Route::post('home/cancel_user_sip', [Home::class, 'cancel_user_sip']);
Route::post('home/pause_resume_sip', [Home::class, 'pause_resume_sip']);
Route::post('home/get_sip_details', [Home::class, 'get_sip_details']);
Route::post('home/update_modified_sip_details', [Home::class, 'update_modified_sip_details']);

Route::any('update_user_nft', [Usernft::class, 'update_user_nft'])->name('updateusernft')->middleware(EnsureUserIsValid::class);
Route::any('usernft_preview_page_submit', [Usernft::class, 'usernft_preview_page_submit'])->name('usernft_preview_page_submit')->middleware(EnsureUserIsValid::class);
Route::get('usersdata', [Usernft::class, 'get_users_data'])->name('usersdata')->middleware(EnsureUserIsValid::class)->middleware(EnsureUserIsValid::class);
Route::get('change_cra', [Usernft::class, 'change_cra'])->name('changecrm')->middleware(EnsureUserIsValid::class);
Route::post('submit_usernft', [Usernft::class, 'submit_data_usernft'])->name('submit_usernft')->middleware(EnsureUserIsValid::class);
Route::any('updatenft_user', [Usernft::class, 'update_usersnft_data'])->name('updatenft_user')->middleware(EnsureUserIsValid::class);
Route::any('panupdate', [Usernft::class, 'pan_update'])->name('panupdate')->middleware(EnsureUserIsValid::class);




Route::any('usernft_update_from_submit', [Usernft::class, 'usernft_update_from_submit'])->name('usernft_update_from_submit')->middleware(EnsureUserIsValid::class);

Route::any('knif_api', [Usernft::class, 'knif_api'])->name('knif_api')->middleware(EnsureUserIsValid::class);
Route::any('nsdl_test', [Usernft::class, 'nsdl_test'])->name('nsdl_test')->middleware(EnsureUserIsValid::class);
Route::any('submit_cams_test', [Usernft::class, 'submit_cams_test'])->name('submit_cams_test')->middleware(EnsureUserIsValid::class);
Route::any('cams_test', [Usernft::class, 'cams_test'])->name('cams_test')->middleware(EnsureUserIsValid::class);
Route::any('reissue_pran', [Usernft::class, 'reissue_pran'])->name('reissue_pran')->middleware(EnsureUserIsValid::class);
Route::any('fatca', [Usernft::class, 'fatca_update'])->name('fatca')->middleware(EnsureUserIsValid::class);
Route::any('view_pan_riessue', [Usernft::class, 'view_pan_riessue'])->name('view_pan_riessue')->middleware(EnsureUserIsValid::class);
Route::post('submit_pan_data', [Usernft::class, 'update_pan_data'])->name('submit_pan_data')->middleware(EnsureUserIsValid::class);
Route::any('get_users_data', [Usernft::class, 'get_users_data'])->name('get_users_data')->middleware(EnsureUserIsValid::class);
Route::any('submit_pan_riessue_data', [Usernft::class, 'submit_pan_riessue_data'])->name('submit_pan_riessue_data')->middleware(EnsureUserIsValid::class);

Route::any('get_fatca_details', [Usernft::class, 'get_fatca_details'])->name('get_fatca_details')->middleware(EnsureUserIsValid::class)->middleware(EnsureUserIsValid::class);
Route::any('get_oneway_detail', [Usernft::class, 'get_oneway_detail'])->name('get_oneway_detail')->middleware(EnsureUserIsValid::class)->middleware(EnsureUserIsValid::class);
Route::any('submit_oneway_detail', [Usernft::class, 'submit_oneway_detail'])->name('submit_oneway_detail')->middleware(EnsureUserIsValid::class)->middleware(EnsureUserIsValid::class);
Route::any('get_pranshifting_details', [Usernft::class, 'get_pranshifting_details'])->name('get_pranshifting_details')->middleware(EnsureUserIsValid::class)->middleware(EnsureUserIsValid::class);
Route::any('submit_pranshifting_details', [Usernft::class, 'submit_pranshifting_details'])->name('submit_pranshifting_details')->middleware(EnsureUserIsValid::class)->middleware(EnsureUserIsValid::class);
Route::any('submit_fatca_data', [Usernft::class, 'submit_fatca_data'])->name('submit_fatca_data')->middleware(EnsureUserIsValid::class)->middleware(EnsureUserIsValid::class);
Route::any('get_sector_shifting_detail', [Usernft::class, 'get_sector_shifting_detail'])->name('get_sector_shifting_detail')->middleware(EnsureUserIsValid::class)->middleware(EnsureUserIsValid::class);
Route::any('submit_sector_shifting_detail', [Usernft::class, 'submit_sector_shifting_detail'])->name('submit_sector_shifting_detail')->middleware(EnsureUserIsValid::class)->middleware(EnsureUserIsValid::class);



Route::get('logout', [Home::class, 'logout']);


Route::get('authentication/{param?}', [Authentication::class, 'index'])->name('authentication');

// Route::post('authentication_submit', [Authentication::class, 'authentication_submit'])->name(('authentication_submit'));
Route::post('send_otp_to_mobile_auth', [Authentication::class, 'send_otp_to_mobile_auth'])->name(('send_otp_to_mobile_auth'));



Route::post('authentication_submit', [Authentication::class, 'authentication_submit'])->name(('authentication_submit'));
Route::post('authentication_submit_maintenance', [Authentication::class, 'authentication_submit_maintenance'])->name(('authentication_submit_maintenance'));



Route::get('user_otp', [Authentication::class, 'user_otp_page'])->name('user_otp');
Route::post('expired_otp', [Authentication::class, 'expired_otp'])->name(('expired_otp'));
Route::post('check_otp_valid_or_not', [Authentication::class, 'check_otp_valid_or_not'])->name(('check_otp_valid_or_not'));
Route::post('make_otp_last_attempt_0', [Authentication::class, 'make_otp_last_attempt_0'])->name(('make_otp_last_attempt_0'));

Route::get('retail_sip', [Retail_sip::class, 'index'])->name('retail_sip_personal_delails_page')->middleware(EnsureUserIsValid::class);
Route::get('retail_sip_preview', [Retail_sip::class, 'retail_sip_preview_page'])->name('personal_delails_preview_page')->middleware([EnsureUserIsValid::class, 'EnsureFlowIsValid:retail_sip_flow']);
Route::post('personal_details_submit', [Retail_sip::class, 'personal_details_submit'])->name(('personal_details_submit'))->middleware([EnsureUserIsValid::class, 'EnsureFlowIsValid:retail_sip_flow']);;
Route::post('personal_details_preview_page_submit', [Retail_sip::class, 'personal_details_preview_page_submit'])->name(('personal_details_preview_page_submit'))->middleware([EnsureUserIsValid::class, 'EnsureFlowIsValid:retail_sip_flow']);;
Route::get('sip_scheduled_successfully', [Retail_sip::class, 'sip_scheduled_successfully'])->name('sip_scheduled_successfully')->middleware(EnsureUserIsValid::class);

Route::get('subsequent_contribution', [Subsequent_contribution::class, 'index'])->name('subsequent_contribution_personal_details_page')->middleware(EnsureUserIsValid::class);
Route::get('subsequent_contribution_preview', [Subsequent_contribution::class, 'subsequent_contribution_preview_page'])->name('subsequent_contribution_preview')->middleware([EnsureUserIsValid::class, 'EnsureFlowIsValid:subsequent_contribution_flow']);

Route::post('subsequent_contribution_submit', [Subsequent_contribution::class, 'subsequent_contribution_submit'])->name(('subsequent_contribution_submit'))->middleware([EnsureUserIsValid::class, 'EnsureFlowIsValid:subsequent_contribution_flow']);

Route::post('subsequent_contribution_preview_page_submit', [Subsequent_contribution::class, 'subsequent_contribution_preview_page_submit'])->name(('subsequent_contribution_preview_page_submit'))->middleware([EnsureUserIsValid::class, 'EnsureFlowIsValid:subsequent_contribution_flow']);

Route::post('resent_otp', [Authentication::class, 'resent_otp'])->name(('resent_otp'));
Route::get('subsequent_contribution_submitted', [Subsequent_contribution::class, 'subsequent_contribution_submitted'])->name('subsequent_contribution_submitted')->middleware(EnsureUserIsValid::class);
Route::get('download_receipt_page', [Subsequent_contribution::class, 'download_receipt_page'])->name('download_receipt_page');
Route::get('download_receipt_pdf/{pran?}', [Subsequent_contribution::class, 'download_receipt_pdf'])->name('download_receipt_pdf');
Route::get('download_receipt_pdf_in_home/{payment_trans_id?}', [Home::class, 'download_receipt_pdf_in_home'])->name('download_receipt_pdf_in_home');
Route::get('download_sip_receipt_pdf_in_home/{payment_trans_id?}', [Home::class, 'download_sip_receipt_pdf_in_home'])->name('download_sip_receipt_pdf_in_home');

Route::get('download_sip_receipt_pdf/{pran?}', [Subsequent_contribution::class, 'download_sip_receipt_pdf'])->name('download_sip_receipt_pdf');

Route::post('check_S2S', [Retail_sip::class, 'check_S2S'])->name(('check_S2S'));
Route::post('check_S2S_subsequent', [Subsequent_contribution::class, 'check_S2S'])->name(('check_S2S_subsequent'));
Route::post('usernft_check_S2S_subsequent', [Usernft::class, 'check_S2S'])->name(('usernft_check_S2S_subsequent'));

//frontend flow ------(end)


Route::prefix('cpan')->name('cpan.')->group(function () {
    Route::post('update_user', [User_management::class, 'update_user'])->name('update_user')->middleware(EnsureAdminIsValid::class);
    Route::get('edit_user/{uid}', [User_management::class, 'edit_user'])->name('edit_user')->middleware(EnsureAdminIsValid::class);
    Route::post('delete_user', [User_management::class, 'delete_user'])->name('delete_user')->middleware(EnsureAdminIsValid::class);

    Route::post('reset_user_password', [User_management::class, 'reset_user_password'])->name('reset_user_password')->middleware(EnsureAdminIsValid::class);


    Route::post('register', [User_management::class, 'register'])->name('register')->middleware(EnsureAdminIsValid::class);
    Route::get('add_user', [User_management::class, 'add_user'])->name('add_user')->middleware(EnsureAdminIsValid::class);
    Route::post('get_user_management_datatable', [User_management::class, 'get_user_management_datatable'])->name('get_user_management_datatable')->middleware(EnsureAdminIsValid::class);
    Route::get('user_management', [User_management::class, 'index'])->name('user_management')->middleware(EnsureAdminIsValid::class);
    Route::get('jio_payment', [Permissions_management::class, 'jio_payment'])->name('jio_payment')->middleware(EnsureAdminIsValid::class);
    Route::get('/permissions_management', [Permissions_management::class, 'index'])->name('permissions_management')->middleware(EnsureAdminIsValid::class);
    Route::post('/get_permissions', [Permissions_management::class, 'get_permissions'])->name('get_permissions')->middleware(EnsureAdminIsValid::class);
    Route::post('/update_permissions', [Permissions_management::class, 'update_permissions'])->name('update_permissions')->middleware(EnsureAdminIsValid::class);
    Route::get('/login', [Login::class, 'index'])->name('login');
    // Route::get('/login', [Login::class, 'index'])->name('login');

    Route::get('otp', [Nodal_officer_login::class, 'nodal_otp'])->name('nodal_otp');
    Route::post('validate_nodal_login', [Nodal_officer_login::class, 'validate_nodal_login'])->name('validate_nodal_login');
    Route::post('nodal_otp_submit', [Nodal_officer_login::class, 'nodal_otp_submit'])->name('nodal_otp_submit');
    Route::post('nodal_resent_otp', [Nodal_officer_login::class, 'nodal_resent_otp'])->name('nodal_resent_otp');





    // Route::get('select_login', [Login::class, 'select_login'])->name('select_login');


    Route::post('forget_password', [Forget_Password::class, 'forget_password'])->name(('forget_password'))->middleware(EnsureAdminIsValid::class);
    Route::get('forgot_password', function () {
        return view('forgot_password');
    })->name('forgot_password')->middleware(EnsureAdminIsValid::class);
    Route::get('reset_password', function () {
        return view('cpan.reset_password');
    })->name('reset_password')->middleware(EnsureAdminIsValid::class);
    Route::post('reset_password_value', [Forget_Password::class, 'reset_password'])->name('reset_password_value')->middleware(EnsureAdminIsValid::class);
    Route::get('/logout', [Login::class, 'logout'])->name('logout')->middleware(EnsureAdminIsValid::class);
    Route::get('/change_password', [Change_Password::class, 'index'])->name('change_password')->middleware(EnsureAdminIsValid::class);


    Route::get('/change_password_new_user', [Change_Password_new_user::class, 'index'])->name('change_password_new_user');
    Route::post('/change_password_value_new_user', [Change_Password_new_user::class, 'change_password'])->name('change_password_value_new_user');

    Route::post('/change_password_value', [Change_Password::class, 'change_password'])->name('change_password_value')->middleware(EnsureAdminIsValid::class);
    
	Route::post('validate_login', [Login::class, 'validate_login'])->name(('validate_login'));
    Route::get('corporate_mapping', [Corporate_mapping_list::class, 'index'])
        ->name('corporate_mapping')
        ->middleware([EnsureAdminIsValid::class, 'CheckUserPermissions:show_corporate_mapping_list_page']);






    Route::get('corporate_mapping', [Corporate_mapping_list::class, 'index'])->name('corporate_mapping')->middleware(EnsureAdminIsValid::class);

    Route::post('get_corporate_mapping_datatable', [Corporate_mapping_list::class, 'get_corporate_mapping_datatable'])->name('get_corporate_mapping_datatable')->middleware(EnsureAdminIsValid::class);

    Route::get('/download_csv', [Corporate_mapping_list::class, 'download_csv'])->name('download_csv')->middleware(EnsureAdminIsValid::class);

    Route::post('reset_date', [Corporate_mapping_list::class, 'reset_date'])->name('reset_date')->middleware(EnsureAdminIsValid::class);


   
    Route::get('view_sip_list', [Sip_management::class, 'index'])->name('view_sip_list')->middleware(EnsureAdminIsValid::class);

    Route::post('get_sip_management_datatable', [Sip_management::class, 'get_sip_management_datatable'])->name('get_sip_management_datatable')->middleware(EnsureAdminIsValid::class);

    Route::get('/download_csv', [Sip_management::class, 'download_csv'])->name('download_csv')->middleware(EnsureAdminIsValid::class);

    Route::post('reset_date', [Sip_management::class, 'reset_date'])->name('reset_date')->middleware(EnsureAdminIsValid::class);
    Route::post('reset_date_upcoming', [Sip_management::class, 'reset_date_upcoming'])->name('reset_date_upcoming')->middleware(EnsureAdminIsValid::class);
    Route::post('reset_date_sip', [Sip_management::class, 'reset_date_sip'])->name('reset_date_sip')->middleware(EnsureAdminIsValid::class);
   
    Route::get('sip_transaction_list', [Sip_management::class, 'sip_transaction_list'])->name('sip_transaction_list')->middleware(EnsureAdminIsValid::class);

    Route::post('get_upcoming_sip_datatable', [Sip_management::class, 'get_upcoming_sip_datatable'])->name('get_upcoming_sip_datatable')->middleware(EnsureAdminIsValid::class);
    
    Route::any('view_profile_details', [Sip_management::class, 'view_profile_details'])->name('view_profile_details')->middleware(EnsureAdminIsValid::class);
    
    Route::post('set_transaction_status', [Sip_management::class, 'set_transaction_status'])->name('set_transaction_status')->middleware(EnsureAdminIsValid::class);

    Route::post('set_sip_status', [Sip_management::class, 'set_sip_status'])->name('set_sip_status')->middleware(EnsureAdminIsValid::class);
    
    Route::any('refund_payment', [Sip_management::class, 'refund_payment'])->name('refund_payment')->middleware(EnsureAdminIsValid::class);
    Route::any('upcoming_sip_transaction_list', [Sip_management::class, 'upcoming_sip_transaction_list'])->name('upcoming_sip_transaction_list')->middleware(EnsureAdminIsValid::class);
    Route::any('get_upcoming_sip_transaction_list', [Sip_management::class, 'get_upcoming_sip_transaction_list'])->name('get_upcoming_sip_transaction_list')->middleware(EnsureAdminIsValid::class);
    Route::post('set_upcoming_transaction_status', [Sip_management::class, 'set_upcoming_transaction_status'])->name('set_upcoming_transaction_status')->middleware(EnsureAdminIsValid::class);

	Route::get('subsequent_contribution_list', [Subsequent_contribution_list::class, 'index'])->name('subsequent_contribution_list')->middleware(EnsureAdminIsValid::class);
    Route::post('get_subsequent_contribution_datatable', [Subsequent_contribution_list::class, 'get_subsequent_contribution_datatable'])->name('get_subsequent_contribution_datatable')->middleware(EnsureAdminIsValid::class);
	Route::post('set_subsequent_contribution_status', [Subsequent_contribution_list::class, 'set_subsequent_contribution_status'])->name('set_subsequent_contribution_status')->middleware(EnsureAdminIsValid::class);
    Route::post('reset_date_subsequent', [Subsequent_contribution_list::class, 'reset_date_subsequent'])->name('reset_date_subsequent')->middleware(EnsureAdminIsValid::class);


    Route::post('get_cron_logs_datatable', [Cron_logs::class, 'get_cron_logs_datatable'])->name('get_cron_logs_datatable')->middleware(EnsureAdminIsValid::class);
   

    Route::get('download_report', [Download_report::class, 'index'])->name('download_report')->middleware(EnsureAdminIsValid::class);
    Route::post('download_report_csv', [Download_report::class, 'download_csv'])->name('download_report_csv')->middleware(EnsureAdminIsValid::class);


    Route::get('upload_pran', [Upload_pran_csv::class, 'index'])->name('upload_pran')->middleware(EnsureAdminIsValid::class);

    // Route::post('download_report_csv', [Sip_management::class, 'download_csv'])->name('download_report_csv');

    // Route::get('upload_pran', function () {
    // return view('cpan.upload_pran');
    // })->name('upload_pran')->middleware(EnsureAdminIsValid::class);

	
	Route::post('/import', [Upload_pran_csv::class, 'import'])->name('import');

	Route::get('cron_logs', [Cron_logs::class, 'index'])->name('cron_logs')->middleware(EnsureAdminIsValid::class);
	Route::post('fetch_cron_details', [Cron_logs::class, 'fetch_cron_details'])->name('fetch_cron_details')->middleware(EnsureAdminIsValid::class);
   
    Route::get('ckyc_converter', [Ckyc::class, 'index'])->name('ckyc_converter')->middleware(EnsureAdminIsValid::class);
    Route::post('ckyc/generate_ckyc_file', [Ckyc::class, 'generate_ckyc_file'])->name('generate_ckyc_file')->middleware(EnsureAdminIsValid::class);
    Route::get('ckyc/download_ckyc_file/{download_token}', [Ckyc::class, 'download_ckyc_file'])->middleware(EnsureAdminIsValid::class);

	Route::get('corporate_contribution', [Corporate_contribution::class, 'index'])->name('corporate_contribution')->middleware(EnsureAdminIsValid::class);
    Route::get('testing_apg_api', [Testing_apg_api::class, 'index']);
    
    Route::any('corporate_download_contribution_csv', [Corporate_contribution::class, 'corporate_download_contribution_csv'])->name('corporate_download_contribution_csv')->middleware(EnsureAdminIsValid::class);
	Route::get('corporate_contribution_preview', [Corporate_contribution::class, 'corporate_contribution_preview'])->name('corporate_contribution_preview')->middleware(EnsureAdminIsValid::class);
    Route::post('corporate_contribution_upload', [Corporate_contribution::class, 'corporate_contribution_upload'])->name('corporate_contribution_upload')->middleware(EnsureAdminIsValid::class);
    Route::post('corporate_contribution_upload', [Corporate_contribution::class, 'corporate_contribution_upload'])->name('corporate_contribution_upload')->middleware(EnsureAdminIsValid::class);
    Route::get('corporate_contribution_preview_active', [Corporate_contribution::class, 'corporate_contribution_preview_active'])->name('corporate_contribution_preview_active')->middleware(EnsureAdminIsValid::class);
    Route::post('upload_utr_or_cheque', [Corporate_contribution::class, 'upload_utr_or_cheque'])->name('upload_utr_or_cheque')->middleware(EnsureAdminIsValid::class);
    Route::get('corporate_contribution_process', [Corporate_contribution::class, 'corporate_contribution_process'])->name('corporate_contribution_process')->middleware(EnsureAdminIsValid::class);
    Route::get('corporate_contribution_csv', [Corporate_contribution::class, 'corporate_contribution_csv'])->name('corporate_contribution_csv')->middleware(EnsureAdminIsValid::class);
    Route::get('corporate_contribution_csv', [Corporate_contribution::class, 'corporate_contribution_csv'])->name('corporate_contribution_csv')->middleware(EnsureAdminIsValid::class);
    Route::get('corporate_contribution_final_invoise', [Corporate_contribution::class, 'corporate_contribution_final_invoise'])->name('corporate_contribution_final_invoise')->middleware(EnsureAdminIsValid::class);

	Route::get('corporate_contribution_cancel', [Corporate_contribution::class, 'corporate_contribution_cancel'])->name('corporate_contribution_cancel')->middleware(EnsureAdminIsValid::class);



    Route::any('add_matrix', [Charge_matrix::class, 'add_matrix'])->name('add_matrix')->middleware(EnsureAdminIsValid::class);
    Route::any('edit_charge_matrix/{matrix_id}', [Charge_matrix::class, 'edit_charge_matrix'])->name('edit_charge_matrix')->middleware(EnsureAdminIsValid::class);
    Route::any('update_charge_matrix', [Charge_matrix::class, 'update_charge_matrix'])->name('update_charge_matrix')->middleware(EnsureAdminIsValid::class);
    Route::any('charge_matrix', [Charge_matrix::class, 'index'])->name('charge_matrix')->middleware(EnsureAdminIsValid::class);
    Route::post('get_charge_matrix_datatable', [charge_matrix::class, 'get_charge_matrix_datatable'])->name('get_charge_matrix_datatable')->middleware(EnsureAdminIsValid::class);
    Route::post('insert_matrix_data', [Charge_matrix::class, 'insert_matrix_data'])->name('insert_matrix_data')->middleware(EnsureAdminIsValid::class);

    Route::get('pran_shifting', [Pran_shifting::class, 'index'])->name('pran_shifting')->middleware(EnsureAdminIsValid::class);
    Route::post('pran_shifting_upload', [Pran_shifting::class, 'pran_shifting_upload'])->name('pran_shifting_upload')->middleware(EnsureAdminIsValid::class);
    Route::get('pran_shifting_preview', [Pran_shifting::class, 'pran_shifting_preview'])->name('pran_shifting_preview')->middleware(EnsureAdminIsValid::class);
    Route::get('pran_shifting_preview_active', [Pran_shifting::class, 'pran_shifting_preview_active'])->name('pran_shifting_preview_active')->middleware(EnsureAdminIsValid::class);
    // Route::post('confirm_active_pran', [Pran_shifting::class, 'confirm_active_pran'])->name('confirm_active_pran')->middleware(EnsureAdminIsValid::class);
	Route::get('pran_shifting_process', [Pran_shifting::class, 'pran_shifting_process'])->name('pran_shifting_process')->middleware(EnsureAdminIsValid::class);

	Route::get('corporate_modification', [Corporate_modification::class, 'index'])->name('corporate_modification')->middleware(EnsureAdminIsValid::class);


	Route::get('operation_contribution_history', [Operation_contribution_history::class, 'index'])->name('operation_contribution_history')->middleware(EnsureAdminIsValid::class);
	Route::post('get_operation_contribution_history_datatable', [Operation_contribution_history::class, 'get_operation_contribution_history_datatable'])->name('get_operation_contribution_history_datatable')->middleware(EnsureAdminIsValid::class);
	Route::post('set_operation_contribution_history_status', [Operation_contribution_history::class, 'set_operation_contribution_history_status'])->name('set_operation_contribution_history_status')->middleware(EnsureAdminIsValid::class);
	Route::get('operation_contribution_history_receipt_download/{ref_id?}', [Operation_contribution_history::class, 'operation_contribution_history_receipt_download'])->name('operation_contribution_history_receipt_download')->middleware(EnsureAdminIsValid::class);



 	Route::any('insert_corporate_changes', [Corporate_modification::class, 'insert_corporate_changes'])->name('insert_corporate_changes')->middleware(EnsureAdminIsValid::class);
 	Route::post('insert_corporate_office_changes', [Corporate_modification::class, 'insert_corporate_office_changes'])->name('insert_corporate_office_changes')->middleware(EnsureAdminIsValid::class);
    Route::any('insert_nodal_changes', [Corporate_modification::class, 'insert_nodal_changes'])->name('insert_nodal_changes')->middleware(EnsureAdminIsValid::class);
    Route::any('corporate_modification_list', [Corporate_modification::class, 'corporate_modification_list'])->name('corporate_modification_list')->middleware(EnsureAdminIsValid::class);
    Route::post('corporate_modification_view_list', [Corporate_modification::class, 'corporate_modification_view_list'])->name('corporate_modification_view_list')->middleware(EnsureAdminIsValid::class);
    Route::post('corporate_appr_status_view_list', [Corporate_modification::class, 'corporate_appr_status_view_list'])->name('corporate_appr_status_view_list')->middleware(EnsureAdminIsValid::class);
    Route::post('action_corporate_modification_list', [Corporate_modification::class, 'action_corporate_modification_list'])->name('action_corporate_modification_list')->middleware(EnsureAdminIsValid::class);
    Route::post('reject_corporate_modification_user', [Corporate_modification::class, 'reject_corporate_modification_user'])->name('reject_corporate_modification_user')->middleware(EnsureAdminIsValid::class);
    Route::get('corporate_appr_status_list', [Corporate_modification::class, 'corporate_appr_status_list'])->name('corporate_appr_status_list')->middleware(EnsureAdminIsValid::class);
    Route::any('view_list/{login_id}/{admin?}', [Corporate_modification::class, 'view_list'])->name('view_list')->middleware(EnsureAdminIsValid::class);
    Route::any('new_data_view_list', [Corporate_modification::class, 'new_data_view_list'])->name('new_data_view_list')->middleware(EnsureAdminIsValid::class);
    Route::any('old_data_view_list', [Corporate_modification::class, 'old_data_view_list'])->name('old_data_view_list')->middleware(EnsureAdminIsValid::class);
	Route::any('nft_list', [Nft_list::class, 'nft_list'])->name('nft_list')->middleware(EnsureAdminIsValid::class);
    Route::any('nft_view_list', [Nft_list::class, 'nft_view_list'])->name('nft_view_list')->middleware(EnsureAdminIsValid::class);
    Route::post('action_nft_list', [Nft_list::class, 'action_nft_list'])->name('action_nft_list')->middleware(EnsureAdminIsValid::class);
    Route::post('reject_nft_user', [Nft_list::class, 'reject_nft_user'])->name('reject_nft_user')->middleware(EnsureAdminIsValid::class);
    Route::any('getRecordDetails', [Nft_list::class, 'getRecordDetails'])->name('getRecordDetails');


    Route::get('contribution_history', [Contribution_history::class, 'index'])->name('contribution_history')->middleware(EnsureAdminIsValid::class);
    Route::get('contribution_history_csv/{ref_id}', [Contribution_history::class, 'contribution_history_csv'])->name('contribution_history_csv')->middleware(EnsureAdminIsValid::class);
    Route::post('get_nodal_contribution_history_datatable', [Contribution_history::class, 'get_nodal_contribution_history_datatable'])->name('get_nodal_contribution_history_datatable')->middleware(EnsureAdminIsValid::class);

   
    Route::any('corporate_contribution_list/{cho_reg_no}/{ref_id}/{approval_status}/', [Corporate_contribution::class, 'corporate_contribution_list'])->name('corporate_contribution_list')->middleware(EnsureAdminIsValid::class);
    Route::post('corporate_contribution_view_list', [Corporate_contribution::class, 'corporate_contribution_view_list'])->name('corporate_contribution_view_list')->middleware(EnsureAdminIsValid::class);
    Route::post('action_corporate_contribution_list', [Corporate_contribution::class, 'action_corporate_contribution_list'])->name('action_corporate_contribution_list')->middleware(EnsureAdminIsValid::class);
    Route::post('reject_corporate_contribution_user', [Corporate_contribution::class, 'reject_corporate_contribution_user'])->name('reject_corporate_contribution_user')->middleware(EnsureAdminIsValid::class);
	Route::any('corporate_list', [Corporate_contribution::class, 'corporate_list'])->name('corporate_list')->middleware(EnsureAdminIsValid::class);
    Route::post('corporate_view_list', [Corporate_contribution::class, 'corporate_view_list'])->name('corporate_view_list')->middleware(EnsureAdminIsValid::class);
    Route::post('set_corporate_contribution_status', [Corporate_contribution::class, 'set_corporate_contribution_status'])->name('set_corporate_contribution_status')->middleware(EnsureAdminIsValid::class);

    Route::any('pran_shifting_list/{corporate_cho_no}/{ref_id}/', [Pran_shifting::class, 'pran_shifting_list'])->name('pran_shifting_list')->middleware(EnsureAdminIsValid::class);
    Route::post('pran_shifting_view_list', [Pran_shifting::class, 'pran_shifting_view_list'])->name('pran_shifting_view_list')->middleware(EnsureAdminIsValid::class);
    Route::post('action_pran_shifting_user_list', [Pran_shifting::class, 'action_pran_shifting_user_list'])->name('action_pran_shifting_user_list')->middleware(EnsureAdminIsValid::class);
    Route::post('reject_pran_shifting_user', [Pran_shifting::class, 'reject_pran_shifting_user'])->name('reject_pran_shifting_user')->middleware(EnsureAdminIsValid::class);
    Route::post('set_pran_shifting_status', [Pran_shifting::class, 'set_pran_shifting_status'])->name('set_pran_shifting_status')->middleware(EnsureAdminIsValid::class);
    Route::any('pran_shifting_request_view_list', [Pran_shifting::class, 'pran_shifting_request_view_list'])->name('pran_shifting_request_view_list')->middleware(EnsureAdminIsValid::class);
    Route::any('pran_shifting_request_list', [Pran_shifting::class, 'pran_shifting_request_list'])->name('pran_shifting_request_list')->middleware(EnsureAdminIsValid::class);
    Route::any('pran_shifting_nodal_list', [Pran_shifting::class, 'pran_shifting_nodal_list'])->name('pran_shifting_nodal_list')->middleware(EnsureAdminIsValid::class);
    Route::any('get_pran_shifting_nodal_list_datatable', [Pran_shifting::class, 'get_pran_shifting_nodal_list_datatable'])->name('get_pran_shifting_nodal_list_datatable')->middleware(EnsureAdminIsValid::class);
    Route::any('pran_shifting_nodal_view_list/{ref_id}/{approval_status}/', [Pran_shifting::class, 'pran_shifting_nodal_view_list'])->name('pran_shifting_nodal_view_list')->middleware(EnsureAdminIsValid::class);
    Route::any('get_pran_shifting_nodal_view_list_datatable', [Pran_shifting::class, 'get_pran_shifting_nodal_view_list_datatable'])->name('get_pran_shifting_nodal_view_list_datatable')->middleware(EnsureAdminIsValid::class);
    Route::get('upload_csv', [Uploadcsv_pran::class, 'showUploadForm'])->name('upload_csv');
    Route::any('submit_csv', [Uploadcsv_pran::class, 'upload'])->name('submit_csv');





});