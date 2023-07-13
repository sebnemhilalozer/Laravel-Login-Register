<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PersonCookie;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Jenssegers\Agent\Facades\Agent;

/**
 * @group Auth endpoints
 */
class AuthenticationController extends Controller
{
    /**
     * Shows authenticated user information
     *
     * @authenticated
     *
     * @response 200 {
     *     "id": 2,
     *     "name": "Demo",
     *     "email": "demo@demo.com",
     *     "email_verified_at": null,
     *     "created_at": "2020-05-25T06:21:47.000000Z",
     *     "updated_at": "2020-05-25T06:21:47.000000Z"
     * }
     * @response status=400 scenario="Unauthenticated" {
     *     "message": "Unauthenticated."
     * }
     */

    //  public function __construct(){
    //     helper(['url','form','cookie','text']);
    // }

    public function user()
    {
        return auth()->user();
    }


    public function index(Request $request)
    {

        $cookie = $request->cookie('bg_uid');
        $ip_address = $this->$request->cookie('bg_uid');


        $agent = Agent::parse($request->header('User-Agent'));
        $browser_id = $agent->platform() . "-" . $agent->browser() . "-" . $agent->version();

        $personCookieModel = new PersonCookie();
        $cookie_info = $personCookieModel->where(array('cookie_code' => $cookie, 'browser_id' => $browser_id, 'expiry_date >' => date("Y-m-d")))->first();


        if (!empty($cookie_info)) {

            $person_id = $cookie_info['person_id'];
            session()->set('loggedPerson', $person_id);

            $authModel = new User();
            $user_info = $authModel->where(array('id' => $person_id))->first();
            session()->set('personRole', $user_info['person_role_id']);


            return redirect()->to("dashboard");
        } else {
            $pageTitle = trans('lang_login');
            $data = [
                'pageTitle' => "$pageTitle"
            ];
            return view('auth/login', $data);
        }
    }

    // public function check()
    // {

    //     //Lets starts valition
    //     $validation = $this->validate([
    //         'email' => 'required|min_length[6]|max_length[64]|valid_email|is_not_unique[person.email]|trim',
    //         'password' => 'required|min_length[6]|max_length[32]|trim'
    //     ]);

    //     $data = [
    //         'pageTitle' => "Giriş Yap",
    //         'validation' => $this->validator
    //     ];

    //     $agent = $this->request->getUserAgent();
    //     $browser_id = $agent->getPlatform() . "-" . $agent->getBrowser() . "-" . $agent->getVersion();
    //     $ip_address = $this->request->getIPAddress();


    //     if (!$validation) {
    //         return view('auth/login', $data);
    //     } else {
    //         //Lets check user
    //         $email = $this->request->getPost('email');
    //         $password = $this->request->getPost('password');
    //         $remember_me = $this->request->getPost('remember_me');

    //         $authModel = new \App\Models\authModel();
    //         $user_info = $authModel->where(array('email' => $email, 'deleted' => 0))->first();

    //         if (!empty($user_info)) {
    //             if ($user_info["status"] == 1) {

    //                 $current_date    = date("Y-m-d");
    //                 $expiration_date = "account_open";

    //                 if ($user_info["email_confirm"] == 0 && $user_info["organisation_id"] == 30) :
    //                     $params = [
    //                         'person_id' => $user_info["id"],
    //                         'first_name' => $user_info["first_name"],
    //                         'email' => $user_info["email"]
    //                     ];
    //                     $this->confirmEmailSentRepeat($params);
    //                     session()->setFlashdata('fail', 'Giriş yapabilmek için E-Posta hesabınızı doğrulamanız gerekmektedir. Konuyla ilgili e-Posta adresinize  doğrulama maili gönderdik.');
    //                     return redirect()->to("auth")->withInput();
    //                     die();
    //                 endif;


    //                 if (!empty($user_info["expiration_date"])) {
    //                     if ($user_info["expiration_date"] != "0000-00-00") {
    //                         if ($current_date >= $user_info["expiration_date"]) {
    //                             $expiration_date = "account_closed";
    //                         } else {
    //                             $expiration_date = "account_open";
    //                         }
    //                     }
    //                 }

    //                 if ($expiration_date == "account_open") {

    //                     $user_id                = $user_info["id"];
    //                     $user_organisation_id   = $user_info["organisation_id"];
    //                     $user_person_role_id    = $user_info["person_role_id"];
    //                     $password_change        = $user_info["password_change"];

    //                     /*
    //                      * Organizasyon aktif / ve expired date kontrolu
    //                      */
    //                     $authListOrganisation = $authModel->select("person.id")
    //                         ->select("organisation.expiration_date as organisation_expiration_date, organisation.deleted as organisation_deleted, organisation.status as organisation_status")
    //                         ->join("organisation", "organisation.id = person.organisation_id", "left")
    //                         ->where(array("organisation.deleted" => 0))
    //                         ->where(array("person.id" => $user_id))->first();

    //                     if (!empty($authListOrganisation)) {
    //                         if ($authListOrganisation["organisation_status"] == 0) {
    //                             //FAIL ->Hesabınız Devre Dışı Bırakılmış. Lütfen sistem yöneticinize başvurun.
    //                             session()->setFlashdata('fail', 'Hesabınız devre dışı bırakılmış. Lütfen sistem yöneticinize başvurun.');
    //                             return redirect()->to("auth")->withInput();
    //                         } else {
    //                             $expiration_organisation_date = "organisation_open";

    //                             if ($user_person_role_id == 5) {

    //                                 if ($current_date >= $authListOrganisation["organisation_expiration_date"]) {
    //                                     $expiration_organisation_date = "organisation_closed";
    //                                 } else {
    //                                     $expiration_organisation_date = "organisation_open";
    //                                 }
    //                             }

    //                             if ($expiration_organisation_date == "organisation_closed") {
    //                                 //FAIL -> Organizasyonun geçerlilik süresi doldu.
    //                                 session()->setFlashdata('fail', 'Hesabınızın geçerlilik süresi dolmuştur. Lütfen sistem yöneticinize başvurun.');
    //                                 return redirect()->to("auth")->withInput();
    //                             } else if ($expiration_organisation_date == "organisation_open") {
    //                                 //SUCCESS
    //                                 //PASSWORD KONTROLü
    //                                 $check_password = Hash::check($password, $user_info['password']);
    //                                 if (!$check_password) {
    //                                     /*
    //                                      * Login Logs
    //                                      */
    //                                     $personLoginLogsModel = new PersonLoginLogs();
    //                                     $values = [
    //                                         "person_id" => $user_info["id"],
    //                                         "organisation_id" => $user_info["organisation_id"],
    //                                         "successful_attempt" => "0",
    //                                         "create_date" => date("Y-m-d H:i:s"),
    //                                         "sessions_id" => session_id(),
    //                                         "ip" => $ip_address,
    //                                         "browser" => $browser_id
    //                                     ];
    //                                     $personLoginLogsModel->insert($values);
    //                                     //FAIL
    //                                     session()->setFlashdata('fail', 'Kullanıcı adı ya da şifre hatalıdır. Lütfen kontrol ederek tekrar deneyiniz.');
    //                                     return redirect()->to("auth")->withInput();
    //                                 } else {

    //                                     $person_id = $user_info['id'];

    //                                     session()->set('loggedPerson', $person_id);
    //                                     session()->set('personRole', $user_info['person_role_id']);


    //                                     // $now_date = date("Y-m-d H:i:s");
    //                                     // $fullAccessEnrollModel = new FullAccessEnroll();
    //                                     // $fullAccessEnrollCheck = $fullAccessEnrollModel->select("id")
    //                                     //     ->where(array("person_id" => $person_id, "status" => 1, "deleted" => 0, "end_date >" => $now_date))->first();

    //                                     // if (!empty($fullAccessEnrollCheck)) :
    //                                     //     session()->set('personFullAccessActive', 1);
    //                                     // else :
    //                                     //     session()->set('personFullAccessActive', 0);
    //                                     // endif;

    //                                     /*
    //                                      * Login Logs
    //                                      */
    //                                     $personLoginLogsModel = new PersonLoginLogs();
    //                                     $values = [
    //                                         "person_id" => $user_info["id"],
    //                                         "organisation_id" => $user_info["organisation_id"],
    //                                         "successful_attempt" => "1",
    //                                         "create_date" => date("Y-m-d H:i:s"),
    //                                         "sessions_id" => session_id(),
    //                                         "ip" => $ip_address,
    //                                         "browser" => $browser_id
    //                                     ];
    //                                     $personLoginLogsModel->insert($values);

    //                                     /*
    //                                      * SET COOKIES
    //                                      */
    //                                     if (!empty($remember_me) && $remember_me == "remember_me") {

    //                                         $random_token = Str::random(64);
    //                                         $expires =   (4 * 7 * 24 * 60 * 60);

    //                                         if (session()->get('sessions_cookie')) {
    //                                             if (session()->get('sessions_cookie') == "true") {
    //                                                 $response = new Response();
    //                                                 $response->withCookie(Cookie::make('bg_uid', $random_token, $expires, '/', '', true, true));
    //                                                 return $response;
    //                                             }
    //                                         }
    //                                         $expiry_date = date("Y-m-d H:i:s", (time() + $expires));

    //                                         $personCookieModel = new PersonCookie();
    //                                         $cookie_info = $personCookieModel->where(array('person_id' => $person_id, 'ip' => $ip_address, 'browser_id' => $browser_id, 'expiry_date >' => date("Y-m-d")))->first();

    //                                         if (empty($cookie_info)) {
    //                                             $values = [
    //                                                 "person_id" => $person_id,
    //                                                 "cookie_code" => $random_token,
    //                                                 "ip" => $ip_address,
    //                                                 "browser_id" => $browser_id,
    //                                                 "expiry_date" => $expiry_date,
    //                                                 "last_access_data" => date("Y-m-d H:i:s"),
    //                                                 "created_at" => date("Y-m-d H:i:s")
    //                                             ];
    //                                             $personCookieModel->insert($values);
    //                                         } else {
    //                                             /*
    //                                              * update
    //                                              */
    //                                             $values = [
    //                                                 "cookie_code" => $random_token,
    //                                                 "ip" => $ip_address,
    //                                                 "browser_id" => $browser_id,
    //                                                 "expiry_date" => $expiry_date,
    //                                                 "last_access_data" => date("Y-m-d H:i:s"),
    //                                             ];
    //                                             $personCookieModel->update($cookie_info["id"], $values);
    //                                         }
    //                                         //delete_cookie("__Secure-remember_token");

    //                                     }



    //                                     if ($password_change == 1) :
    //                                         $data = [
    //                                             'pageTitle' => "Change Password (Necessary)",
    //                                             'person' => $user_info
    //                                         ];
    //                                         return view('auth/new_password_necessary', $data);
    //                                     elseif ($password_change == 0) :
    //                                         return redirect()->to('dashboard')->withCookies();
    //                                     endif;
    //                                 }
    //                             } else {
    //                                 //fail -> beklenmedik bir hata meydana geldi
    //                                 session()->setFlashdata('fail', 'Beklenmedik bir hata meydana geldi. Lütfen sistem yöneticinize başvurun.');
    //                                 return redirect()->to("auth")->withInput();
    //                             }
    //                         }
    //                     } else {
    //                         //FAIL -> Bağlı bulunduğunuz kurum bulunamadı, Lütfen sistem yöneticinize başvurunuz
    //                         session()->setFlashdata('fail', 'Bağlı bulunduğunuz Kurum bilgisine ulaşılamamaktadır. Lütfen sistem yöneticinize başvurun.');
    //                         return redirect()->to("auth")->withInput();
    //                     }
    //                 } else if ($expiration_date == "account_closed") {
    //                     //fail -> Hesabınızın geçerlilik süresi dolmuştur
    //                     session()->setFlashdata('fail', 'Hesabınızın geçerlilik süresi dolmuştur. Lütfen sistem yöneticinize başvurun.');
    //                     return redirect()->to("auth")->withInput();
    //                 } else {
    //                     //fail -> beklenmedik bir hata meydana geldi
    //                     session()->setFlashdata('fail', 'Beklenmedik bir hata meydana geldi. Lütfen sistem yöneticinize başvurun.');
    //                     return redirect()->to("auth")->withInput();
    //                 }
    //             } else {
    //                 //FAIL ->Hesabınız Devre Dışı Bırakılmış. Lütfen sistem yöneticinize başvurun.
    //                 session()->setFlashdata('fail', 'Hesabınız devre dışı bırakılmış. Lütfen sistem yöneticinize başvurun.');
    //                 return redirect()->to("auth")->withInput();
    //             }
    //         } else {
    //             //fail -> Kayıtlı kullanıcı bulunamadı.
    //             session()->setFlashdata('fail', 'Kayıtlı kullanıcı bulunamadı. E-posta adresinizi kontrol ediniz.');
    //             return redirect()->to("auth")->withInput();
    //         }
    //     }
    // }
}
