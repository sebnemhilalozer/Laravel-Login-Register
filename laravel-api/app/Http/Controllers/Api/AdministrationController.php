<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Auth;

class AdministrationController extends Controller
{
    public function __construct()
    {
        $authModel = new Auth();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if (!$personInfo):die();endif;

        if ($personInfo['person_role_id'] != 1) {
            echo 'Access denied.';
            exit;
        }
    }
}
