@extends('layouts.layout_frontend', ['page_title' => 'Authentication', 'header' => 'header', 'body_classes' => 'backend-body', 'sidebar' => false, 'footer' => true])

@section('head_content')
    <style>
        .form-check-input:checked {

            background-color: #FF8427;

            border-color: #FF8427;

        }

        .form-check-label:before {

            display: none;

        }
           .fs-0{
            font-size: 36px;
        }
        .head-text{
            color: #9095A0;
          
            font-size: 16px;
            font-weight: 400;
        }
        .terms{
            color:#F07F2B !important;
        }
        .form-check-input:checked {
            background-color: #F07F2B;
            border-color: #F07F2B;
        }
        .form-check-input {
            position: relative;
            right: 10px;
        }

        .px-5 {
            padding-right: 2rem !important;
            padding-left: 2rem !important;
        }

        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7); /* Slightly transparent background */
            z-index: 9999;
            display: none; /* Initially hidden */
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(0.1px); /* Blurring the background */
        }

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #F07F2B;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Content styles */
        #content {
            margin: 20px;
        }

        @media (min-width: 300px) and (max-width: 576px) {
            .small-screen-margin {
                margin-top: 80px;
            }
        }

        
    </style>
@endsection

@section('main_content')
    <div id="preloader">
        <div class="spinner"></div>
    </div>

    <div class="container-fluid verify-page small-screen-margin">
        <div class="content1 mt-5" >
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-6 image-container align-content-center d-lg-block d-none">
                    <img src={{ asset('frontend_assets/image/making-money-easily-2.38f0003f5e856a22d5c4d15c2836d75b1.svg') }} alt="Image" style="height: 500px">
                </div>
                        
                <div class="col-sm-12 col-md-12 col-lg-6 align-content-center">
                    <div class="row mt-sm-5 pt-sm-2 pt-lg-4">
                        <div class="col-sm-12 col-md-12 col-lg-12 text-center mb-3">
                            <h3 class="fs-0 fw-bold mb-3">Welcome!</h3>
                            <p class="head-text">Lets get started</p>
                        </div>
                    </div>

                    <form action="" id="retail_sip_login_form">
                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-md-10 col-lg-10 ">
                                <div>
                                    <div class="input-block py-2 ">
                                        <input type="text" name="pran" id="pran" spellcheck="false" class="form-control" style="text-transform:none" maxlength="12" autocomplete="off" onkeyup="remove_error_msg(this.id, 'PRAN Number')" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        <span class="placeholder" id="pran_error">PRAN Number</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-md-10 col-lg-10 ">
                                <div>
                                    <div class="input-block py-2 ">
                                        <input type="text" name="mobile" id="mobile" spellcheck="false" class="form-control" maxlength="10" style="text-transform:none" fdprocessedid="44624c" autocomplete="off" onkeyup="remove_error_msg(this.id, 'Registered Mobile Number')" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        <span class="placeholder" id="mobile_error">Registered Mobile Number</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-md-12 col-lg-12 p-0 ">
                                <div class="row mt-1 ">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input focus-ring focus-ring-danger" type="checkbox" value="1" onclick="remove_err_terms_and_cond()" name="flexCheckDefault" id="flexCheckDefault" style="border: 1px solid #F07F2B !important">


                                        <label class="form-check-label ps-0" for="terms_and_conditions_check">
                                        I Agree to the
                                         <a data-bs-toggle="modal" data-bs-target="#staticBackdrop" role="button" aria-expanded="true" aria-controls="collapseExample" class="terms"  id="body_div_trigger">Terms &amp; Conditions</a>&nbspand&nbsp <a data-bs-toggle="modal" data-bs-target="#staticBackdrop2" role="button" aria-expanded="true" aria-controls="collapseExample" class="terms"  id="body_div_trigger">Privacy Policy</a> 
                                        
                                        
                                        </label>
                                       
                                    </div>
                                </div>

                                {{-- <div class="row">
                                    <div class="collapse" id="collapseExample" style="">
                                        <div class="card card-body custom-card p-0">
                                            <p class="card-text mb-0 text-justify">
                                                I hereby undertake the following: I have answered all the required fields after understanding its contents. I have fully understood the nature of the questions and the importance of disclosing all material information while answering such questions. I declare that the contribution made by me/ on my behalf has been derived from bona-fide/ legally declared and assessed sources of income. I also understand that the contribution and any withdrawal are subject to taxes/ charges in accordance with the applicable laws. I further understand that the Company has the right to peruse my financial profile or share the information with any government/ regulatory authorities. I agree and authorize the Company to verify/share relevant information provided herein on confidential basis with third-party entities for the purpose of processing and/or servicing my NPS account.
                                            </p>
                                        </div>
                                    </div>
                                </div> --}}
                            </div>
                        </div>

                        <div class="row" id="term_cond" style="display:none">
                            <div class="col-sm-12 col-md-12 col-lg-12 text-center mt-3">
                                <div>
                                    <span style="color:red" > Please agree to terms and conditions <span> 
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-12 col-lg-12 text-center mt-3">
                                <div>
                                    <span style="color:red" id="error_msg"><span> 
                                </div>
                            </div>
                        </div>  

                        <div class="row">
                            <div class="col-sm-12 col-md-12 col-lg-12 text-center">
                                <div>
                                    <input type="submit" class="btn btn-danger mt-3 px-5 rounded-pill btn-color" value="Send OTP">
                                </div>
                            </div>
                        </div>        
                    </form>

                    <form id="user_additional_details_form" method="POST" style="display: none;">
                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-md-10 col-lg-10">
                                <div>
                                    <div class="input-block py-2 ">
                                        <input type="text" name="user_pran" id="user_pran" spellcheck="false" class="form-control" style="text-transform:none" maxlength="12" autocomplete="off" onkeyup="remove_error_msg(this.id, 'PRAN Number')" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        <span class="placeholder" id="user_pran_error">PRAN Number</span>
                                    </div>
                                </div>
                            </div>
                        
                            <div class="col-sm-12 col-md-10 col-lg-10">
                                <div>
                                    <div class="input-block py-2 ">
                                        <input type="text" name="user_mobile" id="user_mobile" spellcheck="false" class="form-control" maxlength="10" style="text-transform:none" fdprocessedid="44624c" autocomplete="off" onkeyup="remove_error_msg(this.id, 'Registered Mobile Number')" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        <span class="placeholder" id="user_mobile_error">Registered Mobile Number</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-md-10 col-lg-10">
                                <div>
                                    <div class="input-block py-2 ">
                                        <input type="text" name="email" id="email" spellcheck="false" class="form-control" style="text-transform:none" autocomplete="off" onkeyup="remove_error_msg(this.id, 'Email')">
                                        <span class="placeholder" id="email_error">Email</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-md-5 col-lg-5">
                                <div>
                                    <div class="input-block py-2 ">
                                        <input type="text" name="state" id="state" spellcheck="false" class="form-control" style="text-transform:none" autocomplete="off" onkeyup="remove_error_msg(this.id, 'State')">
                                        <span class="placeholder" id="state_error">State</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-5 col-lg-5">
                                <div>
                                    <div class="input-block py-2 ">
                                        <input type="text" name="dob" id="dob" spellcheck="false" class="form-control" style="text-transform:none" autocomplete="off" onkeyup="remove_error_msg(this.id, 'Date Of Birth')">
                                        <span class="placeholder" id="dob_error">Date Of Birth</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-md-12 col-lg-12 p-0 ">
                                <div class="row mt-1 ">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input focus-ring focus-ring-danger" type="checkbox" value="1" onclick="remove_err_terms_and_cond(true)" name="terms_and_conditions_check" id="terms_and_conditions_check" style="border: 1px solid #F07F2B !important" checked>
                                        <a data-bs-toggle="collapse" href="#collapseExample2" role="button" aria-expanded="true" aria-controls="collapseExample2" class="terms"  id="body_div_trigger2">I Agree to the Terms &amp; Conditions</a>  
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="collapse" id="collapseExample2" style="">
                                        <div class="card card-body custom-card p-0">
                                            <p class="card-text mb-0 text-justify">
                                                I hereby undertake the following: I have answered all the required fields after understanding its contents. I have fully understood the nature of the questions and the importance of disclosing all material information while answering such questions. I declare that the contribution made by me/ on my behalf has been derived from bona-fide/ legally declared and assessed sources of income. I also understand that the contribution and any withdrawal are subject to taxes/ charges in accordance with the applicable laws. I further understand that the Company has the right to peruse my financial profile or share the information with any government/ regulatory authorities. I agree and authorize the Company to verify/share relevant information provided herein on confidential basis with third-party entities for the purpose of processing and/or servicing my NPS account.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="term_cond_error" style="display:none">
                            <div class="col-sm-12 col-md-12 col-lg-12 text-center mt-3">
                                <div>
                                    <span style="color:red" > Please agree to terms and conditions <span> 
                                </div>
                            </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-md-12 col-lg-12 text-center mt-3">
                                <div>
                                    <span style="color:red" id="additional_details_error"><span> 
                                </div>
                            </div>
                        </div>  
                    
                        <div class="row mt-4">
                            <div class="col-md-12 text-center">
                                <button class="btn btn-sm btn-danger px-4 rounded-pill btn-color" type="submit">Send OTP</button>
                            </div>
                        </div>
                    </form>
                </div> 
            </div>
        </div>                    
    </div>  

    <!-- Button trigger modal -->
{{-- <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
  Launch Modal
</button> --}}

<!-- Modal -->
{{-- <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Vertically Centered Scrollable Modal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <span class="modal-text text-justify">
                                        <div>
                                             <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Introduction</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">This Privacy Policy shall be applicable to all customers/subscribers of ICICI Prudential Pension Funds Management Company and users of <a target="_blank" rel="noopener noreferrer" href="https://www.iciciprulife.com/" class="custom-area "><span class="custom-area " style="color: rgb(0, 0, 238);">iciciprupensionfund.com</span></a><span class="custom-area " style="color: rgb(0, 0, 238);">.</span> Please read the terms carefully. By accessing the Website or using any of our services, You agree to be bound by all the terms of this Privacy Policy.</p></div></div>
                                               
 
                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Definitions</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">"ICICI Prudential" or "Us" or “us” or "We"- refers to ICICI Prudential Pension Funds Management Company Limited.</p><p class="custom-area ">"Personal Information" means any information that relates to a natural person, which, either directly or indirectly, in combination with other information available or likely to be available with a body corporate, is capable of identifying such person.</p><p class="custom-area ">"Sensitive Personal Data or Information" of a person means such personal information which consists of information relating to -</p><ul class="custom-area " style="list-style-type: disc;"><li class="custom-area ">Password</li><li class="custom-area ">Financial information such as bank account, credit card, debit card or other payment instrument details</li><li class="custom-area ">Sexual orientation</li><li class="custom-area ">Biometric information</li><li class="custom-area ">Any detail relating to the above clauses as provided to ICICI Prudential for providing service and</li><li class="custom-area ">Any of the information received under above clauses to ICICI Prudential for processing, stored or processed under lawful contract or otherwise:</li></ul><p class="custom-area ">Provided that, any information that is freely available or accessible in public domain or furnished under the Right to Information Act, 2005 or any other law for the time being in force shall not be regarded as sensitive personal data or information.</p><p class="custom-area ">"Website" means the website accessed through URLs hosted on <a target="_blank" rel="noopener noreferrer" href="https://www.iciciprulife.com/" class="custom-area "><span class="custom-area " style="color: rgb(0, 0, 238);">iciciprupensionfund.com</span></a> and <a target="_blank" rel="noopener noreferrer" href="https://www.iciciprulife.com/" class="custom-area "><span class="custom-area " style="color: rgb(0, 0, 238);">iciciprupension.com</span></a> or on any of its sub-domain</p><p class="custom-area ">"You” or “you” or "Your" or “your” or "User(s) " refers to the person accessing the Website in any capacity.</p></div></div>
 
                                               <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Collection of Information</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">We are committed to protecting your privacy and have taken reasonable steps to protect the confidentiality of the information you have provided us and its transmission through the World Wide Web.</p><p class="custom-area ">In the process of you using this Website or availing of the existing or future services or facility provided by the Website, you may be required to furnish information, including but not limited to Personal Information and/or Sensitive Personal Information. You are urged to keep the information current to ensure that the services and facility remain relevant and reach you.</p><p class="custom-area ">We may collect Personal Information such as Name, Phone Number, Address, Email ID, Age, etc. for provision of services.</p><p class="custom-area ">We may also collect Sensitive Personal Information such as KYC Details (for financial due diligence and policy servicing), Video/Voice recording (for verification/authentication process),), Aadhaar Number and Related Information (for provision of services). Please note that this is an indicative list.</p><p class="custom-area ">The information collected by us in one or more ways are mentioned below:</p><ul class="custom-area " style="list-style-type: disc;"><li class="custom-area ">Information you provide us - while using the Website and/or while availing the products and some of the services made available on the Website</li><li class="custom-area ">Information we get from your usage of Website</li><li class="custom-area ">Information we get through cookies - We deploy cookies when you visit our Website and access said cookies on your device to allow you to buy and interact at the Website. The primary purpose of these cookies is to analyze how users move within our Website. Our cookies let you view customized pages while transacting with us. For more details, please visit <a target="_blank" rel="noopener noreferrer" href="https://s3-icici.s3.ap-south-1.amazonaws.com/PFM_Cookie_Policy_Final_9427f7f643.pdf" class="custom-area "><span class="custom-area " style="color: rgb(0, 0, 238);">Cookie Policy</span></a><span class="custom-area " style="color: rgb(0, 0, 238);">.</span></li><li class="custom-area ">Information we may get from our affiliates or third parties - These could be from your employers, , commercially available sources such as public databases, data aggregators, social media, etc.</li></ul></div></div>
 
                                               <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Usage of Information</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">By using this Website or our services, you authorize us to use your information for the following purposes:</p><ul class="custom-area " style="list-style-type: disc;"><li class="custom-area ">To provide schemes under National Pension System that you may purchase from ICICI Prudential</li><li class="custom-area ">To provide any services that you may avail from ICICI Prudential</li><li class="custom-area ">To contact you over the telephone/ mobile number and/or e-mail address provided by you for sending information of our products in which you have shown your interest, other service details and other general information about schemes</li><li class="custom-area ">To enhance customer service</li><li class="custom-area ">To extend services or administer a contest, promotion, survey or other site or business feature</li><li class="custom-area ">For Aadhaar authentication and sharing, storing, using Aadhaar data</li><li class="custom-area ">For sales and marketing Activities</li><li class="custom-area ">Allow you to access specific account information</li><li class="custom-area ">To process transactions, where requested, under your User ID and Password</li><li class="custom-area ">We may use the information provided by you to customize your visit to the Website by displaying appropriate content at our judgment and discretion</li><li class="custom-area ">Send you information about us and / or our services, that is to contact you when required and/or</li><li class="custom-area ">ICICI Prudential's Affiliates may from time to time send by e-mail or otherwise, information relating to their products and services</li><li class="custom-area ">To improve our Website</li><li class="custom-area ">To conduct statistical analysis</li><li class="custom-area ">As otherwise described to you at the point of collection</li></ul><p class="custom-area ">By providing your contact information, you have consented to be contacted by us or on our behalf on the said contact information even if the said contact information is registered under the National Do Not Call(“NDNC”) Registry, on your own volition and free will.</p></div></div>
 
                                               <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Sharing of Information</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">We undertake not to disclose, except as otherwise provided, the Personal Information and/or Sensitive Personal Information provided by you to any person, unless such action is necessary in the following cases:</p><ul class="custom-area " style="list-style-type: disc;"><li class="custom-area ">To meet requirements of any applicable law, regulation, legal process, governmental request, or customary practice directly or through our affiliates/banks/financial institutions/credit bureaus/agencies. Protect and defend ICICI Prudential's or its Affiliates' rights, interests or property. The Company has defined responsibilities and processes to evaluate and respond to law enforcement or Government data requests. The submission is done in accordance with applicable rules and regulations</li><li class="custom-area ">Enforce the terms and conditions of the products or services or Terms and Conditions</li><li class="custom-area ">Act to protect the interests of ICICI Prudential, its Affiliates, or its members, constituents or of other persons</li><li class="custom-area ">For statistical analysis and verification or risk management</li><li class="custom-area ">To enforce applicable terms and conditions, including investigation of potential violations</li><li class="custom-area ">To detect, prevent, or otherwise address fraud, security or technical issues, analyze and manage other commercial risks</li><li class="custom-area ">To protect against harm to the rights, property or safety of ICICI Prudential, its customer or the public as required or permitted by law</li><li class="custom-area ">To Third party service providers such as data cloud storage providers, call centers, payment gateways, banks to perform functions on our behalf. Examples include hosting data, delivering e-mail, analyzing data, providing marketing assistance, providing search results and providing customer service. The third party service providers are required to use appropriate security measures to protect the confidentiality and security of the Personal Information</li><li class="custom-area ">To outsource activities in accordance with PFRDA regulations/guidelines/directions;</li><li class="custom-area ">To provide You information on products and services offered by our company on pension products or pension schemes administered under PFRDA</li></ul><p class="custom-area ">We may also use your Personal Information and/or Sensitive Personal Information to provide you with information on products and services offered by our affiliates that are interested in serving you or any of your subscribed services and any servicerelated activities such as collecting subscription fees from you for those services and notifying or contacting you regarding such services. In this regard, it may be necessary to disclose your Personal Information and/or Sensitive Personal Information to one or more Advisors, Affiliates and contractors of ICICI Prudential and their sub-contractors, but such Advisors, contractors, and sub-contractors will be required to agree to use the information obtained from us only for these purposes. You may inform us at any time not to share your Personal Information and/or Sensitive Personal Information with third parties by writing to us at - ICICI Pru Life Towers, 1089 Appasaheb Marathe Marg, Prabhadevi, Mumbai- 400025.</p><p class="custom-area ">You may use any information provided in the website for your personal use. You shall not disclose to any person, in any manner whatsoever, any information relating to ICICI Prudential or its Affiliates of a confidential nature obtained in the course of availing the Facility or use of Website. Failure to comply with this obligation shall be deemed a material breach of the terms herein and shall entitle ICICI Prudential or its Affiliates to terminate the services or Facility. We will be entitled to any direct or indirect damages that may arise out of the disclosure of such confidential information by you/ your /use of this Website.</p></div></div>
 
 
 
                                               <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Aadhaar-Consent</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">By sharing your Aadhaar number, you hereby consent to our collection, use and storage of your Aadhaar number, Virtual ID, e-Aadhaar, XML, Masked Aadhaar, Aadhaar details, demographic information, identity information, Aadhaar registered mobile number, Aadhaar registered address, date of birth, face authentication details and/or biometric information as per applicable laws/regulations (collectively, “Aadhaar Information”) for the following purposes:</p><ol class="custom-area " style="list-style-type: lower-latin;"><li class="custom-area ">KYC and periodic KYC process and for establishing your identity, carrying out your identification, other authentication/verification/identification as may be permitted as per applicable law and for the provision of National Pension Scheme (NPS) services;</li><li class="custom-area ">Collecting, sharing, storing, preserving Aadhaar Information, maintaining records and using the Aadhaar Information and authentication/verification/identification records for the informed purposes above as well as for regulatory and legal reporting and filings and/or where required under applicable law;</li><li class="custom-area ">Producing records and logs of the consent, Aadhaar Information or of authentication, identification, verification etc. for evidentiary purposes including before a court of law, any authority or in arbitration</li></ol><p class="custom-area ">Aadhaar numbers so collected shall be kept protected as required by applicable regulations;</p><p class="custom-area ">You have been informed that submission of Aadhaar is not mandatory and there are alternative options for KYC and establishing identity;</p><p class="custom-area ">Your Aadhaar Information shall only be disclosed as required for the informed purposes above and in accordance with applicable laws.</p></div></div>
 
                                               <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Storage and retention of Information</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">We may retain your Personal Information and/or Sensitive Personal Information for long as it is necessary to fulfil the purpose for which such information was collected or for such a time as required to comply with regulations and applicable law.</p></div></div>    
 
                                               <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Communication and changes to the Privacy Policy</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">ICICI Prudential retains the right to communicate with you (via e-mail, postal service / courier or mobile messaging services / display banners on the internet and/or in third party websites) either directly or through third party vendors, when operational or regulatory requirements require us to do so. You shall have the option to unsubscribe to receive such email communication.</p><p class="custom-area ">The Website may amend or modify, this Privacy Policy including replacing this Privacy Policy with a new policy, at any time at the sole discretion of ICICI Prudential. Revised Privacy Policy/amendments thereto shall be effective from the date indicated therein. Users are requested to periodically check the terms and conditions under this Privacy Policy from time to time. ICICI Prudential shall not in any circumstance be held liable for such lapses on the part of the Users.</p></div></div>
 
 
                                               <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Information Security</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">We take reasonable precautions to protect the Website and our User’s information from unauthorized access to or unauthorized alteration, disclosure or destruction of information, which include.</p><ul class="custom-area "><li class="custom-area ">Providing role-based access to Users who need to login to our Website information systems, data processing systems, secure data vaults, etc</li><li class="custom-area ">Permit access to personally identifiable information to our employees, contractors and agents who are subject to strict contractual confidentiality obligations</li><li class="custom-area ">Periodically review access to systems</li><li class="custom-area ">Encrypt our internet-based services using SSL certificates</li><li class="custom-area ">Conduct vulnerability assessments of our website and data processing systems periodically</li></ul></div></div>
 
 
                                               <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Warranty Disclaimer</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">Information, content and materials provided on this Website are on an “As Is” basis and ICICI Prudential does not make any express or implied representation or warranty regarding the accuracy, adequacy or completeness of the same.</p></div></div>
 
 
                                               <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Contact Information</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">In case you require any further clarifications on the content of this policy or terms of use, please email us at:</p><p class="custom-area "><span class="custom-area " style="color: rgb(255, 160, 0);">Email us at:</span> <span class="custom-area " style="color: rgb(255, 160, 0);">nps@iciciprupension.com</span></p></div></div>
 
 
                                               <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Refund and Cancellation Policy :</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">Users can cancel the onboarding process prior to final submission of the application or payment. Once an application is submitted and a PRAN (Permanent Retirement Account Number) is generated, no cancellation is allowed. Once a contribution or SIP payment is successfully processed, it cannot be cancelled or reversed. If the transaction fails but the amount is debited, a full refund will be processed within 7–10 working days. In case of duplicate payments, the excess amount will be refunded post-verification within 10–15 working days. If onboarding fails before PRAN generation due to system or verification issues, the contribution amount will be refunded after deducting applicable charges, if any. Contributions made after PRAN generation are non-refundable. No refunds will be processed for voluntary or scheduled contributions unless there is a system error or duplicate payment, and no refunds will be made for services already rendered.</p><p class="custom-area ">All refunds will be processed to the original mode of payment only. No cash refunds or transfers to alternate bank accounts will be permitted. Refunds are typically processed within 7–15 working days, depending on the case and bank timelines. To request a refund or cancellation, users must contact support with mobile number/email, transaction ID, date, amount, and issue details.</p><p class="custom-area ">ICICI Prudential Pension Funds reserves the right to update or modify the policy without prior notice. Users are advised to review the policy periodically on our platforms.</p><p class="custom-area ">&nbsp;</p><p class="custom-area ">&nbsp;</p></div></div>
                                    </span>
 
      </div>
      <div class="modal-footer">
      <button class="btn btn-sm btn-danger px-4 rounded-pill btn-color" type="Button">I &nbsp; Agree</button>
      </div>
    </div>
  </div>
</div> --}}


<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded rounded-4">
                <div class="modal-header mt-3">
                    <h3 class="modal-title modal-heading" id="staticBackdropLabel">Terms and Conditions</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class=" row fixed-height overflow-y-scroll">
                        <div class=" col-12">
                            <div class=" w-100">
                                <span class="modal-text text-justify">I hereby undertake the following: I have answered
                                    all the required fields after understanding its contents. I have fully understood
                                    the nature of the questions and the importance of disclosing all material
                                    information while answering such questions. I declare that the contribution made by
                                    me/ on my behalf has been derived from bona-fide/ legally declared and assessed
                                    sources of income. I also understand that the contribution and any withdrawal are
                                    subject to taxes/ charges in accordance with the applicable laws. I further
                                    understand that the Company has the right to peruse my financial profile or share
                                    the information with any government/ regulatory authorities. I agree and authorize
                                    the Company to verify/share relevant information provided herein on confidential
                                    basis with third-party entities for the purpose of processing and/or servicing my
                                    NPS account.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 ">
                        {{-- <button type="button" class="btn btn-custom rounded-pill" onclick="agreeAndCheckCheckbox()"
                            data-bs-dismiss="modal">I&nbsp;Agree</button> --}}
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="staticBackdrop2" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content rounded rounded-4">
                    <div class="modal-header mt-3 sticky-top bg-white z-3">
                        <h3 class="modal-title modal-heading" id="staticBackdropLabel">PRivacy Policy</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class=" row fixed-height overflow-y-scroll">
                            <div class=" col-12">
                                <div class=" w-100">
                                    <span class="modal-text text-justify">
                                            <div>
                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Introduction</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">This Privacy Policy shall be applicable to all customers/subscribers of ICICI Prudential Pension Funds Management Company and users of <a target="_blank" rel="noopener noreferrer" href="https://www.iciciprulife.com/" class="custom-area "><span class="custom-area " style="color: rgb(0, 0, 238);">iciciprupensionfund.com</span></a><span class="custom-area " style="color: rgb(0, 0, 238);">.</span> Please read the terms carefully. By accessing the Website or using any of our services, You agree to be bound by all the terms of this Privacy Policy.</p></div></div>
                                                    

                                                    <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Definitions</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">"ICICI Prudential" or "Us" or “us” or "We"- refers to ICICI Prudential Pension Funds Management Company Limited.</p><p class="custom-area ">"Personal Information" means any information that relates to a natural person, which, either directly or indirectly, in combination with other information available or likely to be available with a body corporate, is capable of identifying such person.</p><p class="custom-area ">"Sensitive Personal Data or Information" of a person means such personal information which consists of information relating to -</p><ul class="custom-area " style="list-style-type: disc;"><li class="custom-area ">Password</li><li class="custom-area ">Financial information such as bank account, credit card, debit card or other payment instrument details</li><li class="custom-area ">Sexual orientation</li><li class="custom-area ">Biometric information</li><li class="custom-area ">Any detail relating to the above clauses as provided to ICICI Prudential for providing service and</li><li class="custom-area ">Any of the information received under above clauses to ICICI Prudential for processing, stored or processed under lawful contract or otherwise:</li></ul><p class="custom-area ">Provided that, any information that is freely available or accessible in public domain or furnished under the Right to Information Act, 2005 or any other law for the time being in force shall not be regarded as sensitive personal data or information.</p><p class="custom-area ">"Website" means the website accessed through URLs hosted on <a target="_blank" rel="noopener noreferrer" href="https://www.iciciprulife.com/" class="custom-area "><span class="custom-area " style="color: rgb(0, 0, 238);">iciciprupensionfund.com</span></a> and <a target="_blank" rel="noopener noreferrer" href="https://www.iciciprulife.com/" class="custom-area "><span class="custom-area " style="color: rgb(0, 0, 238);">iciciprupension.com</span></a> or on any of its sub-domain</p><p class="custom-area ">"You” or “you” or "Your" or “your” or "User(s) " refers to the person accessing the Website in any capacity.</p></div></div>

                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Collection of Information</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">We are committed to protecting your privacy and have taken reasonable steps to protect the confidentiality of the information you have provided us and its transmission through the World Wide Web.</p><p class="custom-area ">In the process of you using this Website or availing of the existing or future services or facility provided by the Website, you may be required to furnish information, including but not limited to Personal Information and/or Sensitive Personal Information. You are urged to keep the information current to ensure that the services and facility remain relevant and reach you.</p><p class="custom-area ">We may collect Personal Information such as Name, Phone Number, Address, Email ID, Age, etc. for provision of services.</p><p class="custom-area ">We may also collect Sensitive Personal Information such as KYC Details (for financial due diligence and policy servicing), Video/Voice recording (for verification/authentication process),), Aadhaar Number and Related Information (for provision of services). Please note that this is an indicative list.</p><p class="custom-area ">The information collected by us in one or more ways are mentioned below:</p><ul class="custom-area " style="list-style-type: disc;"><li class="custom-area ">Information you provide us - while using the Website and/or while availing the products and some of the services made available on the Website</li><li class="custom-area ">Information we get from your usage of Website</li><li class="custom-area ">Information we get through cookies - We deploy cookies when you visit our Website and access said cookies on your device to allow you to buy and interact at the Website. The primary purpose of these cookies is to analyze how users move within our Website. Our cookies let you view customized pages while transacting with us. For more details, please visit <a target="_blank" rel="noopener noreferrer" href="https://s3-icici.s3.ap-south-1.amazonaws.com/PFM_Cookie_Policy_Final_9427f7f643.pdf" class="custom-area "><span class="custom-area " style="color: rgb(0, 0, 238);">Cookie Policy</span></a><span class="custom-area " style="color: rgb(0, 0, 238);">.</span></li><li class="custom-area ">Information we may get from our affiliates or third parties - These could be from your employers, , commercially available sources such as public databases, data aggregators, social media, etc.</li></ul></div></div>

                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Usage of Information</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">By using this Website or our services, you authorize us to use your information for the following purposes:</p><ul class="custom-area " style="list-style-type: disc;"><li class="custom-area ">To provide schemes under National Pension System that you may purchase from ICICI Prudential</li><li class="custom-area ">To provide any services that you may avail from ICICI Prudential</li><li class="custom-area ">To contact you over the telephone/ mobile number and/or e-mail address provided by you for sending information of our products in which you have shown your interest, other service details and other general information about schemes</li><li class="custom-area ">To enhance customer service</li><li class="custom-area ">To extend services or administer a contest, promotion, survey or other site or business feature</li><li class="custom-area ">For Aadhaar authentication and sharing, storing, using Aadhaar data</li><li class="custom-area ">For sales and marketing Activities</li><li class="custom-area ">Allow you to access specific account information</li><li class="custom-area ">To process transactions, where requested, under your User ID and Password</li><li class="custom-area ">We may use the information provided by you to customize your visit to the Website by displaying appropriate content at our judgment and discretion</li><li class="custom-area ">Send you information about us and / or our services, that is to contact you when required and/or</li><li class="custom-area ">ICICI Prudential's Affiliates may from time to time send by e-mail or otherwise, information relating to their products and services</li><li class="custom-area ">To improve our Website</li><li class="custom-area ">To conduct statistical analysis</li><li class="custom-area ">As otherwise described to you at the point of collection</li></ul><p class="custom-area ">By providing your contact information, you have consented to be contacted by us or on our behalf on the said contact information even if the said contact information is registered under the National Do Not Call(“NDNC”) Registry, on your own volition and free will.</p></div></div>

                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Sharing of Information</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">We undertake not to disclose, except as otherwise provided, the Personal Information and/or Sensitive Personal Information provided by you to any person, unless such action is necessary in the following cases:</p><ul class="custom-area " style="list-style-type: disc;"><li class="custom-area ">To meet requirements of any applicable law, regulation, legal process, governmental request, or customary practice directly or through our affiliates/banks/financial institutions/credit bureaus/agencies. Protect and defend ICICI Prudential's or its Affiliates' rights, interests or property. The Company has defined responsibilities and processes to evaluate and respond to law enforcement or Government data requests. The submission is done in accordance with applicable rules and regulations</li><li class="custom-area ">Enforce the terms and conditions of the products or services or Terms and Conditions</li><li class="custom-area ">Act to protect the interests of ICICI Prudential, its Affiliates, or its members, constituents or of other persons</li><li class="custom-area ">For statistical analysis and verification or risk management</li><li class="custom-area ">To enforce applicable terms and conditions, including investigation of potential violations</li><li class="custom-area ">To detect, prevent, or otherwise address fraud, security or technical issues, analyze and manage other commercial risks</li><li class="custom-area ">To protect against harm to the rights, property or safety of ICICI Prudential, its customer or the public as required or permitted by law</li><li class="custom-area ">To Third party service providers such as data cloud storage providers, call centers, payment gateways, banks to perform functions on our behalf. Examples include hosting data, delivering e-mail, analyzing data, providing marketing assistance, providing search results and providing customer service. The third party service providers are required to use appropriate security measures to protect the confidentiality and security of the Personal Information</li><li class="custom-area ">To outsource activities in accordance with PFRDA regulations/guidelines/directions;</li><li class="custom-area ">To provide You information on products and services offered by our company on pension products or pension schemes administered under PFRDA</li></ul><p class="custom-area ">We may also use your Personal Information and/or Sensitive Personal Information to provide you with information on products and services offered by our affiliates that are interested in serving you or any of your subscribed services and any servicerelated activities such as collecting subscription fees from you for those services and notifying or contacting you regarding such services. In this regard, it may be necessary to disclose your Personal Information and/or Sensitive Personal Information to one or more Advisors, Affiliates and contractors of ICICI Prudential and their sub-contractors, but such Advisors, contractors, and sub-contractors will be required to agree to use the information obtained from us only for these purposes. You may inform us at any time not to share your Personal Information and/or Sensitive Personal Information with third parties by writing to us at - ICICI Pru Life Towers, 1089 Appasaheb Marathe Marg, Prabhadevi, Mumbai- 400025.</p><p class="custom-area ">You may use any information provided in the website for your personal use. You shall not disclose to any person, in any manner whatsoever, any information relating to ICICI Prudential or its Affiliates of a confidential nature obtained in the course of availing the Facility or use of Website. Failure to comply with this obligation shall be deemed a material breach of the terms herein and shall entitle ICICI Prudential or its Affiliates to terminate the services or Facility. We will be entitled to any direct or indirect damages that may arise out of the disclosure of such confidential information by you/ your /use of this Website.</p></div></div>



                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Aadhaar-Consent</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">By sharing your Aadhaar number, you hereby consent to our collection, use and storage of your Aadhaar number, Virtual ID, e-Aadhaar, XML, Masked Aadhaar, Aadhaar details, demographic information, identity information, Aadhaar registered mobile number, Aadhaar registered address, date of birth, face authentication details and/or biometric information as per applicable laws/regulations (collectively, “Aadhaar Information”) for the following purposes:</p><ol class="custom-area " style="list-style-type: lower-latin;"><li class="custom-area ">KYC and periodic KYC process and for establishing your identity, carrying out your identification, other authentication/verification/identification as may be permitted as per applicable law and for the provision of National Pension Scheme (NPS) services;</li><li class="custom-area ">Collecting, sharing, storing, preserving Aadhaar Information, maintaining records and using the Aadhaar Information and authentication/verification/identification records for the informed purposes above as well as for regulatory and legal reporting and filings and/or where required under applicable law;</li><li class="custom-area ">Producing records and logs of the consent, Aadhaar Information or of authentication, identification, verification etc. for evidentiary purposes including before a court of law, any authority or in arbitration</li></ol><p class="custom-area ">Aadhaar numbers so collected shall be kept protected as required by applicable regulations;</p><p class="custom-area ">You have been informed that submission of Aadhaar is not mandatory and there are alternative options for KYC and establishing identity;</p><p class="custom-area ">Your Aadhaar Information shall only be disclosed as required for the informed purposes above and in accordance with applicable laws.</p></div></div> 

                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Storage and retention of Information</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">We may retain your Personal Information and/or Sensitive Personal Information for long as it is necessary to fulfil the purpose for which such information was collected or for such a time as required to comply with regulations and applicable law.</p></div></div>    

                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Communication and changes to the Privacy Policy</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">ICICI Prudential retains the right to communicate with you (via e-mail, postal service / courier or mobile messaging services / display banners on the internet and/or in third party websites) either directly or through third party vendors, when operational or regulatory requirements require us to do so. You shall have the option to unsubscribe to receive such email communication.</p><p class="custom-area ">The Website may amend or modify, this Privacy Policy including replacing this Privacy Policy with a new policy, at any time at the sole discretion of ICICI Prudential. Revised Privacy Policy/amendments thereto shall be effective from the date indicated therein. Users are requested to periodically check the terms and conditions under this Privacy Policy from time to time. ICICI Prudential shall not in any circumstance be held liable for such lapses on the part of the Users.</p></div></div>


                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Information Security</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">We take reasonable precautions to protect the Website and our User’s information from unauthorized access to or unauthorized alteration, disclosure or destruction of information, which include.</p><ul class="custom-area "><li class="custom-area ">Providing role-based access to Users who need to login to our Website information systems, data processing systems, secure data vaults, etc</li><li class="custom-area ">Permit access to personally identifiable information to our employees, contractors and agents who are subject to strict contractual confidentiality obligations</li><li class="custom-area ">Periodically review access to systems</li><li class="custom-area ">Encrypt our internet-based services using SSL certificates</li><li class="custom-area ">Conduct vulnerability assessments of our website and data processing systems periodically</li></ul></div></div>


                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Warranty Disclaimer</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">Information, content and materials provided on this Website are on an “As Is” basis and ICICI Prudential does not make any express or implied representation or warranty regarding the accuracy, adequacy or completeness of the same.</p></div></div>


                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Contact Information</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">In case you require any further clarifications on the content of this policy or terms of use, please email us at:</p><p class="custom-area "><span class="custom-area " style="color: rgb(255, 160, 0);">Email us at:</span> <span class="custom-area " style="color: rgb(255, 160, 0);">nps@iciciprupension.com</span></p></div></div>


                                                <div class=" container px-5 pb-2 md:pb-2 md:px-10 "><div class="flex flex-row items-center mt-0 md:mt-5 text-start"><h4 class="text-lg md:text-xl lg:text-2xl font-bold"><p class="custom-area ">Refund and Cancellation Policy :</p></h4></div><div class="text-sm  md:text-base lg:text-lg py-4 font-normal"><p class="custom-area ">Users can cancel the onboarding process prior to final submission of the application or payment. Once an application is submitted and a PRAN (Permanent Retirement Account Number) is generated, no cancellation is allowed. Once a contribution or SIP payment is successfully processed, it cannot be cancelled or reversed. If the transaction fails but the amount is debited, a full refund will be processed within 7–10 working days. In case of duplicate payments, the excess amount will be refunded post-verification within 10–15 working days. If onboarding fails before PRAN generation due to system or verification issues, the contribution amount will be refunded after deducting applicable charges, if any. Contributions made after PRAN generation are non-refundable. No refunds will be processed for voluntary or scheduled contributions unless there is a system error or duplicate payment, and no refunds will be made for services already rendered.</p><p class="custom-area ">All refunds will be processed to the original mode of payment only. No cash refunds or transfers to alternate bank accounts will be permitted. Refunds are typically processed within 7–15 working days, depending on the case and bank timelines. To request a refund or cancellation, users must contact support with mobile number/email, transaction ID, date, amount, and issue details.</p><p class="custom-area ">ICICI Prudential Pension Funds reserves the right to update or modify the policy without prior notice. Users are advised to review the policy periodically on our platforms.</p><p class="custom-area ">&nbsp;</p><p class="custom-area ">&nbsp;</p></div></div>
                                        </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 ">
                        {{-- <button type="button" class="btn btn-custom rounded-pill" onclick="agreeAndCheckCheckbox()"
                            data-bs-dismiss="modal">I&nbsp;Agree</button> --}}
                    </div>
                </div>
            </div>
        </div>   



    
    

@endsection

@section('modals_and_scripts_content')
    <script>                                                        
        function remove_error_msg(id, msg) 
        {
            $('#error_msg').html('');  
            $('#additional_details_error').html('');  
            $('#' + id + '_error').text(msg).css('color', 'black');
            $('#' + id).css('border', '1px solid #dee2e6');
        }

        function remove_err_terms_and_cond(additional_details_check = false) 
        {
            $('#error_msg').html('');
            $('#additional_details_error').html('');  
            if(!additional_details_check)
            {
                if($("#flexCheckDefault").prop("checked")) 
                {
                    $('#term_cond').hide();
                    $('#body_div_trigger').attr('style', 'color: #F07F2B !important ;');
                    $("#flexCheckDefault").css("border", "1px solid #F07F2B !important");
                } 
                else 
                {
                    $('#body_div_trigger').attr('style', 'color: red !important;');
                    $("#flexCheckDefault").css("border", "1px solid red");
                }
            }
            else
            {
                if($("#terms_and_conditions_check").prop("checked")) 
                {
                    $('#term_cond').hide();
                    $('#body_div_trigger2').attr('style', 'color: #F07F2B !important ;');
                    $("#terms_and_conditions_check").css("border", "1px solid #F07F2B !important");
                } 
                else 
                {
                    $('#body_div_trigger2').attr('style', 'color: red !important;');
                    $("#terms_and_conditions_check").css("border", "1px solid red");
                }
            }
        }

        function js_error(id, error_msg) 
        {
            $('#' + id + '_error').text(error_msg).css('color', 'red');
            $('#' + id).css('border', '3px solid #ff9a9a');
        }

        $(document).ready(function() {
            $("#user_pran, #user_mobile").on("keydown", function (event) {
                event.preventDefault(); // Prevent all keypresses
            });
            $("#user_pran, #user_mobile").on("keyup", function (event) {
                event.preventDefault(); // Prevent all keypresses
            });

            $('#user_pran, #user_mobile').on('focus', function () {
                $(this).blur(); // Immediately remove focus
            });

            $('#retail_sip_login_form').submit(function(e) {
                e.preventDefault(); // prevent the form from submitting normally
                // Serialize form data
                var formData = $(this).serialize();
                var formDataObj = {};
                $(this).serializeArray().forEach(function(item) {
                    formDataObj[item.name] = item.value;
                });

                if(!formDataObj['pran']) 
                {
                    js_error('pran', 'PRAN number is required')
                    return false;
                } 
                else if(formDataObj['pran'].length < 12) 
                {
                    js_error('pran', 'PRAN number must be 12 digit')
                    return false;
                } 
                else 
                {
                    remove_error_msg('pran', 'PRAN Number')
                }

                if(!formDataObj['mobile']) 
                {
                    js_error('mobile', 'Mobile number is required')
                    return false;
                } 
                else if(formDataObj['mobile'].length < 10) 
                {
                    js_error('mobile', 'Mobile number must be 10 digit')
                    return false;
                } 
                else 
                {
                    remove_error_msg('mobile', 'Registered Mobile Number')
                }

                if($("#flexCheckDefault").prop("checked")) 
                {
                    $('#term_cond').hide();
                    $('#body_div_trigger').attr('style', 'color: #F07F2B !important ;');
                    $("#flexCheckDefault").css("border", "1px solid #F07F2B !important");
                } 
                else 
                {
                    $('#term_cond').show();
                    $('#body_div_trigger').attr('style', 'color: red !important;');
                    $("#flexCheckDefault").css("border", "1px solid red");
                    return false;
                }

                $('#preloader').css('display', 'flex');

                $.ajax({
                    ...ajax_defaults_serialize,
                    url: "{{ route('authentication_submit') }}",
                    data: formData,
                    beforeSend: function() {
                        $('#error_msg').html('');
                    },
                    success: function(response) {
                        $('#preloader').css('display', 'none');

                        // Handle successful response
                        if(response.status != 200) 
                        {
                            $('#preloader').css('display', 'none');
                            if(response.id == 'custom_err_msg')
                                $('#error_msg').html(response.msg);
                            else
                                js_error(response.id, response.msg);

                            if(typeof response.is_other_pop_user !== 'undefined')
                            {
                                $('#user_pran').val(formDataObj['pran']);
                                $('#user_mobile').val(formDataObj['mobile']);
                                $('#retail_sip_login_form').hide();
                                $('#additional_details_error').html(response.msg);
                                $('#user_additional_details_form').show();
                            }

                            return false;
                        }

                        $.ajax({
                            ...ajax_defaults_serialize,
                            url: "{{ route('send_otp_to_mobile_auth') }}",
                            success: function(response) {
                                if(response.status == 200) 
                                {
                                    $('#preloader').css('display', 'none');
                                    window.location.href = "{{ route('user_otp') }}";
                                }
                            }
                        });

                        window.location.href =  "{{ route('user_otp') }}";
                    }
                });
            });

            let state_options = [
                "Andhra Pradesh",
                "Arunachal Pradesh", 
                "Assam", 
                "Bihar", 
                "Chhattisgarh", 
                "Goa", 
                "Gujarat", 
                "Haryana", 
                "Himachal Pradesh", 
                "Jammu and Kashmir", 
                "Jharkhand", 
                "Karnataka", 
                "Kerala", 
                "Ladakh", 
                "Madhya Pradesh", 
                "Maharashtra", 
                "Manipur", 
                "Meghalaya", 
                "Mizoram", 
                "Nagaland", 
                "Odisha", 
                "Punjab", 
                "Rajasthan", 
                "Sikkim", 
                "Tamil Nadu", 
                "Telangana", 
                "Tripura", 
                "Uttar Pradesh", 
                "Uttarakhand", 
                "West Bengal"
            ];

            $('#state').autocomplete({
                source: state_options,
                // appendTo: '#user_additional_details_form',
                position: { my: "left bottom", at: "left top" },
                minLength: 0,
                focus: function(event, ui) {
                    return false;
                }
            })
            .on('blur', function () {
                let inputValue = $(this).val();
                if(!state_options.includes($(this).val()) && state_options.includes(inputValue.toLowerCase())) 
                    $(this).val('');
                else
                    $(this).val(inputValue.replace(/\b\w/g, char => char.toUpperCase()));
            })
            .click(function() {
                $(this).autocomplete('search', '');
            });

            let currentDate = new Date();
            $('#dob').datepicker({
                changeMonth: true,
                changeYear: true,
                yearRange: (currentDate.getFullYear() - 110) + ":" + currentDate.getFullYear(),
                maxDate: 0,
                dateFormat: "dd/mm/yy"
            });

            $('#user_additional_details_form').on('submit', function(e) {
                e.preventDefault();

                const user_pran = $('#user_pran').val();
                const user_mobile = $('#user_mobile').val();
                const email = $('#email').val();
                const state = $('#state').val();
                const dob = $('#dob').val();

                if(!user_pran || user_pran == '') 
                {
                    js_error('user_pran', 'PRAN number is required');
                    return false;
                } 
                else if(user_pran.length != 12) 
                {
                    js_error('user_pran', 'PRAN number must be 12 digit');
                    return false;
                } 
                else if(user_pran != $('#pran').val())
                {
                    js_error('user_pran', 'Technical Error');
                    return false;
                }
                else 
                {
                    remove_error_msg('user_pran', 'PRAN Number');
                }

                if(!user_mobile || user_mobile == '') 
                {
                    js_error('user_mobile', 'Mobile number is required');
                    return false;
                } 
                else if(user_mobile.length != 10) 
                {
                    js_error('user_mobile', 'Mobile number must be 10 digit');
                    return false;
                } 
                else if(user_mobile != $('#mobile').val())
                {
                    js_error('user_mobile', 'Technical Error');
                    return false;
                }
                else 
                {
                    remove_error_msg('user_mobile', 'Registered Mobile Number');
                }

                const regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                if(!email || email == '')
                {
                    js_error('email', 'Email is required');
                    return false;
                }
                else if(!regex.test(email))
                {
                    js_error('email', 'Invalid Email');
                    return false;
                }
                else
                {
                    remove_error_msg('email', 'Email');
                }

                if(!state || state == '')
                {
                    js_error('state', 'State is required');
                    return false;
                }
                else
                {
                    remove_error_msg('state', 'State');
                }

                if(!dob || dob == '')
                {
                    js_error('dob', 'Date Of Birth is required');
                    return false;
                }
                else
                {
                    remove_error_msg('dob', 'Date Of Birth')
                }

                if($("#terms_and_conditions_check").prop("checked")) 
                {
                    $('#term_cond_error').hide();
                    $('#body_div_trigger2').attr('style', 'color: #F07F2B !important ;');
                    $("#terms_and_conditions_check").css("border", "1px solid #F07F2B !important");
                } 
                else 
                {
                    $('#term_cond_error').show();
                    $('#body_div_trigger2').attr('style', 'color: red !important;');
                    $("#terms_and_conditions_check").css("border", "1px solid red");
                    return false;
                }

                $.ajax({
                    ...ajax_defaults_serialize,
                    url: "{{ url('autentication/verify_additional_details') }}",
                    data: $(this).serialize(),
                    beforeSend: function() {
                        $('#additional_details_error').html('');
                    },
                    success: function(response) {
                        if(response.status != 200)
                        {
                            if(typeof response.id !== 'undefined')
                            {
                                if(response.id != 'terms_and_conditions_check')
                                    $('#additional_details_error').html(response.msg);
                                else
                                {
                                    $('#term_cond_error').show();
                                    $('#body_div_trigger2').attr('style', 'color: red !important;');
                                    $("#terms_and_conditions_check").css("border", "1px solid red");
                                }
                            }
                            else
                                $('#additional_details_error').html(response.msg);

                            return false;
                        }

                        $.ajax({
                            ...ajax_defaults_serialize,
                            url: "{{ route('send_otp_to_mobile_auth') }}",
                            success: function(response) {
                                if(response.status == 200) 
                                    window.location.href = "{{ route('user_otp') }}";
                            }
                        });
                    }
                });
            });
        });

        window.onpopstate = function(event) {
            location.reload();
        };
    </script>
@endsection
