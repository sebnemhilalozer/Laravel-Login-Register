<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{

    public function profile()
    {

        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        $personInfoModel = new \App\Models\PersonInfo();
        $personInfoDetail =  $personInfoModel->where('person_id', $loggedPersonId)->first();

        $data = [
            'pageTitle' => "Profile",
            'personInfo' => $personInfo,
            'personInfoDetail' => $personInfoDetail
        ];

        return view('frontend/account/profile', $data);
    }


    public function changePassword()
    {

        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        $data = [
            'pageTitle' => "Change Password",
            'personInfo' => $personInfo,
        ];
        return view('frontend/account/change_password', $data);
    }

    public function changePasswordSave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:6|max:32',
            'password_new' => 'required|min:6|max:32',
            'password_repeat' => 'same:password_new',
            'person_id' => 'numeric'
        ]);

        if ($validator->fails()) {
            $loggedPersonId = session()->get('loggedPerson');
            $personInfo = \App\Models\User::where('id', $loggedPersonId)->first();
            $data = [
                'pageTitle' => "Change Password",
                'personInfo' => $personInfo,
                'validation' => $validator->errors()
            ];
            return view('frontend/account/change_password', $data);
        } else {
            // UPDATE NEW ORGANISATION
            $person_id = $request->input('person_id');
            $password = $request->input('password');
            $password_new = $request->input('password_new');
            $password_repeat = $request->input('password_repeat');

            $personInfo = \App\Models\User::where(['id' => $person_id, 'deleted' => 0, 'status' => 1])->first();
            $check_password = Hash::check($password, $personInfo->password);

            if (!$check_password) {
                return redirect()->route('changePassword')->with('fail', "Mevcut şifrenizi yanlış girdiniz.");
            } else {
                $personModel = \App\Models\User::find($person_id);
                $personModel->password = Hash::make($password_new);
                $personModel->save();
                /** LOGS **/
                $this->saveLog("person", $person_id, "Update");
                return redirect()->route('changePassword')->with('success', "Şifreniz güncellenmiştir.");
            }
        }
    }




    public function notification()
    {

        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        $notificationModel = new \App\Models\Notification();
        $notification =  $notificationModel->where(array("status" => 1, "deleted" => 0))->findAll();


        $notificationAllowModel = new \App\Models\NotificationAllow();
        $notificationPerson = $notificationAllowModel->where(array('person_id' => $personInfo["id"]))->findAll();

        $data = [
            'pageTitle' => "Notification Settings",
            'personInfo' => $personInfo,
            'notification' => $notification,
            'notificationPerson' => $notificationPerson
        ];
        return view('frontend/account/notification', $data);
    }


    public function notificationUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'person_id' => 'numeric'
        ]);

        if ($validator->fails()) {
            return redirect()->route('account.notification')->with('fail', "Beklenmeyen bir hata meydana geldi.");
        } else {
            $notification = $request->input('notification');
            if (!$notification) {
                $notification = [];
            }

            $person_id = $request->input('person_id');
            $notificationModel = new \App\Models\Notification();
            $notificationDb = $notificationModel->where(['status' => 1, 'deleted' => 0])->get();

            foreach ($notificationDb as $item) {
                $status_allow = in_array($item->id, $notification) ? 1 : 0;

                $notificationAllowModel = new \App\Models\NotificationAllow();
                $notificationAllowPerson = $notificationAllowModel->where(['notification_id' => $item->id, 'person_id' => $person_id])->first();

                $values = [
                    'notification_id' => $item->id,
                    'person_id' => $person_id,
                    'status_allow' => $status_allow,
                ];

                if ($notificationAllowPerson) {
                    // Güncelle
                    $query = $notificationAllowModel->where('id', $notificationAllowPerson->id)->update($values);
                    /** LOGS **/
                    $this->saveLog("notification_allow", $notificationAllowPerson->id, "Update");
                } else {
                    // Ekle
                    $query = $notificationAllowModel->create($values);
                    /** LOGS **/
                    $insert_id = $query->id;
                    $this->saveLog("notification_allow", $person_id, "Create");
                }
            }
            return redirect()->route('account.notification')->with('success', "Bildirim ayarlarınız güncellenmiştir.");
        }
    }






    public function profileFotoSave()
    {

        $accountModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $accountModel->where('id', $loggedPersonId)->first();


        // if ($file = $this->request->getFile('file_data')) {
        //     if ($file->isValid() && !$file->hasMoved()) {
        //         $newName = $file->getRandomName();
        //         $file->move(APPPATH . '../public/assets/img/account', $newName);
        //         $file_name = $newName;

        //         $image = \Config\Services::image()
        //             ->withFile(APPPATH . "../public/assets/img/account/" . $newName)
        //             ->resize(512, 512, true, 'height')
        //             ->save(APPPATH . "../public/assets/img/account/512/" . $newName);

        //         $image_2 = \Config\Services::image()
        //             ->withFile(APPPATH . "../public/assets/img/account/" . $newName)
        //             ->fit(256, 256, 'center')
        //             ->save(APPPATH . "../public/assets/img/account/256/" . $newName);

        //         $image_3 = \Config\Services::image()
        //             ->withFile(APPPATH . "../public/assets/img/account/" . $newName)
        //             ->fit(128, 128, 'center')
        //             ->save(APPPATH . "../public/assets/img/account/128/" . $newName);
        //     }
        // }

        // $data = ['img_path'  => $file_name];
        // $query = $accountModel->update($loggedPersonId, $data);

        /** LOGS **/
        $this->saveLog("person", $loggedPersonId, "Update");
    }

    public function profileFotoRemove()
    {

        $accountModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $accountModel->where('id', $loggedPersonId)->first();

        $data = ['img_path'  => NULL];
        $query = $accountModel->update($loggedPersonId, $data);

        /** LOGS **/
        $this->saveLog("person", $loggedPersonId, "Update");
    }


    public function profileUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|min:2|max:32',
            'last_name' => 'required|min:2|max:32',
            'information' => 'max:256',
            'person_id' => 'numeric'
        ]);

        if ($validator->fails()) {
            $loggedPersonId = session()->get('loggedPerson');
            $authModel = new \App\Models\User();
            $personInfo = $authModel->where('id', $loggedPersonId)->first();

            $personInfoModel = new \App\Models\PersonInfo();
            $personInfoDetail = $personInfoModel->where('person_id', $loggedPersonId)->first();

            $data = [
                'pageTitle' => "Profile",
                'personInfo' => $personInfo,
                'personInfoDetail' => $personInfoDetail,
                'validation' => $validator->errors()
            ];
            return view('frontend/account/profile', $data);
        } else {
            // UPDATE NEW ORGANISATION
            $person_id = $request->input('person_id');
            $first_name = $request->input('first_name');
            $last_name = $request->input('last_name');
            $information = $request->input('information');

            $values = [
                'first_name' => $first_name,
                'last_name' => $last_name,
            ];
            $personModel = new \App\Models\User();
            $personModel->where('id', $person_id)->update($values);
            /** LOGS **/
            $this->saveLog("person", $person_id, "Update");

            $personInfoModel = new \App\Models\PersonInfo();
            $personInfoDetail = $personInfoModel->where('person_id', $person_id)->first();

            $values_info = [
                'person_id' => $person_id,
                'information' => $information,
            ];
            if ($personInfoDetail) {
                $personInfoModel->where('id', $personInfoDetail["id"])->update($values_info);
                /** LOGS **/
                $this->saveLog("person_info", $personInfoDetail["id"], "Update");
            } else {
                $personInfoModel->create($values_info);
            }
            return redirect()->route('account.profile')->with('success', "Profil bilgileriniz güncellenmiştir.");
        }
    }
    function subscriptions()
    {
        $accountModel = new \App\Models\User();
        $purchaseMainModel = new \App\Models\PurchaseMain();

        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $accountModel->where('id', $loggedPersonId)->first();

        if (!$personInfo) :
            return redirect()->to('auth')->with('fail', "Lütfen giriş yapınız.");
        endif;

        $purchaseMainModel = new \App\Models\PurchaseMain();
        $purchaseList =  $purchaseMainModel
            ->select("conversation_id, paid_price, product_id, product_title, package_price, kdv_rate, kdv_price, total_price, purchase_type, purchase_end_date, created_at")
            ->where(array("status_check" => "Tamamlandı", "payment_status" => "Success", "person_id" => $personInfo["id"]))
            ->findAll();


        $arrPurchaseList = array();
        foreach ($purchaseList as $key => $item) {
            $arrPurchaseList[$item['conversation_id']][$key] = $item;
        }
        ksort($arrPurchaseList, SORT_NUMERIC);

        $data = [
            'pageTitle' => "Aboneliklerim",
            'pageDescription' => 'Ödeme geçmişini görüntüleyin.',
            'personInfo' => $personInfo,
            'purchaseList' => $arrPurchaseList
        ];

        return view('frontend/account/subscriptions', $data);
    }

    function subscriptionsDetails($conversation_id)
    {

        $accountModel = new \App\Models\User();
        $purchaseMainModel = new \App\Models\PurchaseMain();

        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $accountModel->where('id', $loggedPersonId)->first();


        if (!$personInfo) :
            return redirect()->to('auth')->with('fail', "Lütfen giriş yapınız.");
        endif;

        $purchaseMainModel = new \App\Models\PurchaseMain();
        $purchaseList =  $purchaseMainModel
            ->select("conversation_id, paid_price, product_id, product_title, package_price, kdv_rate, kdv_price, total_price, purchase_type, purchase_end_date, buyer_phone, billing_name, billing_city, billing_country, billing_address, created_at")
            ->where(array("status_check" => "Tamamlandı", "payment_status" => "Success", "person_id" => $personInfo["id"], "conversation_id" => $conversation_id))
            ->findAll();

        if (!$purchaseList) :
            return redirect()->to("404");
        endif;

        $arrPurchaseList = array();
        foreach ($purchaseList as $key => $item) {
            $arrPurchaseList[$item['conversation_id']][$key] = $item;
        }
        ksort($arrPurchaseList, SORT_NUMERIC);

        $data = [
            'pageTitle' => "Aboneliklerim",
            'pageDescription' => 'Ödeme geçmişini görüntüleyin.',
            'personInfo' => $personInfo,
            'purchaseList' => $arrPurchaseList
        ];
        return view('frontend/account/subscriptions_details', $data);
    }
}
