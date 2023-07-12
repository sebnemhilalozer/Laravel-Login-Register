<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PersonCookie;
use Illuminate\Http\Request;

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


    public function index(Request $request) {

        $cookie = $request->cookie('bg_uid');
        $ip_address = $this->$request->cookie('bg_uid');

        /* ! $agent giving error

         $agent = $request->header('User-Agent');
         $browser_id = $agent->getPlatform(). "-".$agent->getBrowser()."-".$agent->getVersion(); 

        $personCookieModel = new PersonCookie();
        $cookie_info = $personCookieModel->where(array('cookie_code' => $cookie, 'browser_id' => $browser_id, 'expiry_date >' => date("Y-m-d") ))->first(); */


        if (!empty($cookie_info)){

            $person_id = $cookie_info['person_id'];
            session()->set('loggedPerson', $person_id);

            $authModel = new User();
            $user_info = $authModel->where(array('id' => $person_id))->first();
            session()->set('personRole', $user_info['person_role_id']);

            /*
             * Login Logs
              */
            // $personLoginLogsModel = new PersonLoginLogs();
            // $values = [
            //     "person_id" => $user_info["id"],
            //     "organisation_id" => $user_info["organisation_id"],
            //     "successful_attempt" => 1,
            //     "create_date" => date("Y-m-d H:i:s"),
            //     "sessions_id" => session_id(),
            //     "ip" => $ip_address,
            //     "browser" => $browser_id
            // ];
            // $personLoginLogsModel->insert($values);


            return redirect()->to("dashboard");

        }else{
            $pageTitle = trans('lang_login');
            $data = [
                'pageTitle' => "$pageTitle"
            ];
            return view('auth/login', $data);
        }



    }

}
