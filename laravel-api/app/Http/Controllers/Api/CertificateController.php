<?php

namespace App\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CertificateController extends Controller
{

    private static function loginCheck()
    {
        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if (!$personInfo) :
            return null;
        else :
            return $personInfo;
        endif;
    }

    // SHOW CERTIFICATE
    public function verify($token = "", Request $request)
    {
        //helper("slug");
        // slug_link();

        $loginCheck = auth()->check();

        // Get the token from the query parameters or use a default value
        $token = $request->input('token', 'default_token_value');

        if (strlen($token) == 32) :
            $certificateUserModel = new \App\Models\CertificateUser();
            $certificateData = $certificateUserModel->where('certificate_token', $token)->first();
            $isMyCertificate = $certificateData["person_id"] == $loginCheck["id"];

            $courseModel = new \App\Models\Course();
            $course = $courseModel
                ->select("course.id, course.title as courseTitle, course.slug")
                ->select("instructor.prefix, instructor.name, instructor.surname")
                ->select("course_category.title as course_category")
                ->join("instructor", "instructor.id = course.instructor_id", "left")
                ->join("course_category", "course_category.id = course.category_id", "left")
                ->where(array("course.status" => 1, "course.deleted" => 0))
                ->where(array("course.id" => $certificateData["course_id"]))
                ->first();

            $data = [
                'pageTitle' => "Katılım Belgesi",
                'certificate_path' => $certificateData ? asset($certificateData["certificate_path"]) : "",
                'subtitle' => $certificateData ? "BAUGO tarafından onaylanmış Katılım Belgesi" : (!$token ? "" : "Geçersiz belge doğrulama anahtarı"),
                'verifyForm' => $certificateData ? "hidden" : "",
                'certificateData' => $certificateData,
                'token' => $token,
                'personInfo' => $loginCheck,
                'isMyCertificate' => $isMyCertificate,
                'course' => $course
            ];

            return view('frontend/certificate_verify', $data);
        else :
            return redirect()->to("404");
        endif;
    }

    public static function control_certificate()
    {
        $certificateUserModel = new \App\Models\CertificateUser();
        $courseEnrollList = $certificateUserModel
            ->select('course_enroll.id as course_enroll_id, certificate_user.id as certificate_user_id')
            ->join("course_enroll", "certificate_user.person_id = course_enroll.person_id AND certificate_user.course_id = course_enroll.course_id ", "left")
            ->where(array("certificate_user.id" => 1434))
            ->find();

        foreach ($courseEnrollList as $courseEnroll) {
            $certificateUserModel->update($courseEnroll['certificate_user_id'], array('course_enroll_id' => $courseEnroll['course_enroll_id']));
            CertificateController::create_certificate(1, $courseEnroll['course_enroll_id'], 1);
        }
    }

    //CERTIFICATE CREATION CODE
    public static function create_certificate($template_id = 1, $courseEnroll_id = -1, $control_certificate = 0)
    {
        // helper(['slug']);
        // if($courseEnroll_id==-1){
        //     exit();
        // }
        $loginCheck = CertificateController::loginCheck();
        if (!$loginCheck) :
            exit();
        endif;

        // USER DETAILS
        $person_name = $loginCheck['first_name'] . ' ' . $loginCheck['last_name'];

        // COURSE ENROLL DETAILS
        $courseEnrollModel = new \App\Models\CourseEnroll();
        $courseEnroll_details = $courseEnrollModel->select("id, person_id, course_id, created_at, course_completed, course_completed_date, enroll_date")
            ->where('id', $courseEnroll_id)->first();
        $enroll_id = $courseEnroll_details["id"];
        $person_id = $courseEnroll_details["person_id"];
        $course_id = $courseEnroll_details["course_id"];

        /* TODO
        if(!$courseEnroll_details["course_completed"]) return;
        */

        /* FOR CERTIFICATE PROGRAM // TODO
        if($template_id==2){
            $incompleteCourseEnroll = $courseEnrollModel->select("person_id, course_id, created_at, course_completed")->where('course_completed', 0)->findAll();
            if($incompleteCourseEnroll) return;
            $course_id = $course_package_id; // for certificateUserModel
        }
        */

        // CONTROL CERTIFICATE DATA
        $certificateUserModel = new \App\Models\CertificateUser();
        $certificate_details = $certificateUserModel->select("certificate_token")
            ->where(array('person_id' => $person_id, 'course_id' => $course_id))->first();
        if ($certificate_details) { // ALREADY EXIST
            $alreadyExist = 1;
            $certificate_token = $certificate_details["certificate_token"];
            //header('Location: verify/'.$certificate_token);
            if (!$control_certificate) {
                return;
                //exit();
            }
        } else {
            $alreadyExist = 0;
            $certificate_token = bin2hex(openssl_random_pseudo_bytes(16));
        }

        // COURSE DETAILS
        $courseModel = new \App\Models\Course();
        $course_details = $courseModel->select("instructor_id, title, class_length")
            ->where('id', $course_id)->first();
        $course_title = $course_details['title'];

        // USER DETAILS
        $personModel = new \App\Models\User();
        $person_details = $personModel->select("first_name, last_name")
            ->where('id', $person_id)->first();
        $person_name = $person_details['first_name'] . ' ' . $person_details['last_name'];

        // INSTRUCTOR DETAILS
        $instructorModel = new \App\Models\Instructor();
        $instructor_details = $instructorModel->select("prefix, name, surname")
            ->where('id', $course_details["instructor_id"])->first();
        //$instructor_name = $instructor_details['prefix'].' '.$instructor_details['name'].' '.$instructor_details['surname'];

        //CERTIFICATE DETAILS
        $certificateTemplateModel = new \App\Models\CertificateTemplate();
        $certificate_template = $certificateTemplateModel->where('id', $template_id)->first();
        $certificate_text = $certificate_template["text"];
        $certificate_template_path = $certificate_template["path"];

        if (strpos($certificate_text, '{complate_date}') != false) {
            setlocale(LC_ALL, 'tr_TR.UTF-8');
            $certificate_text = str_replace('{complate_date}', strftime("%e %B %Y", strtotime($courseEnroll_details["course_completed_date"])), $certificate_text);
        }

        if (strpos($certificate_text, '{class_length}') != false) {
            $certificate_text = str_replace('{class_length}', $course_details["class_length"], $certificate_text);
        }

        // MAKE A COPY OF CERTIFICATE TEMPLATE
        //$certificate_token = bin2hex(openssl_random_pseudo_bytes(16));
        $certificate_path = 'certificates/' . $certificate_token . '.png'; // Assuming 'certificates' is a directory within the 'public' directory
        $certificate_src = public_path($certificate_path);

        //check if 'certificates/' path is really on the public file
        if ($person_id == 542) {
            copy(public_path('certificates/template_ea.png'), $certificate_src);
        } else {
            copy(public_path('certificates/template_' . $template_id . '.png'), $certificate_src);
        }

        $splited_certificate_template = explode("\n", wordwrap($certificate_text, 75));
        $splited_certificate_template_part = count($splited_certificate_template);


        // SAVE CERTIFICATE DATA
        $certificateData = [
            'certificate_template_id' => $template_id,
            'certificate_token' => $certificate_token,
            'course_enroll_id' => $enroll_id,
            'course_id' => $course_id,
            'course_title' => $course_title,
            'person_id' => $person_id,
            'person_name' => $person_name,
            'certificate_start_date' => $courseEnroll_details["enroll_date"],
            'certificate_end_date' => $courseEnroll_details["course_completed_date"],
            'certificate_path' => $certificate_path,
            'created_at' => date("Y-m-d H:i:s"),
        ];
        if (!$alreadyExist) {
            $certificateUserModel->save($certificateData);
        }

        if ($course_title  == "Mindful Yaşamın İyileştirici Gücü: Günlük Hayatta Karşılaşılan Sorunlara Yaratıcı Çözümler") :
            $course_title = "MINDFUL YAŞAMIN İYİLEŞTİRİCİ GÜCÜ: GÜNLÜK HAYATTA KARŞILAŞILAN SORUNLARA YARATICI ÇÖZÜMLER";
        endif;

        if (strlen($course_title) > 60) :
            $splitat = strpos($course_title, " ", strlen($course_title) / 2);
            $course_title_1 = substr($course_title, 0, $splitat);
            $course_title_2 = substr($course_title, $splitat);
        endif;



        // CONFIG CERTIFICATE TEMPLATE
        $image = \Config\Services::image()
            ->withFile($certificate_src)
            ->text("Sayın", [
                'color'      => '#001361',
                'fontPath'   => FCPATH . 'certificates/fonts/Gotham-Medium.otf',
                'opacity'    => 0.1,
                'withShadow' => false,
                'hAlign'     => 'left',
                'vAlign'     => 'top',
                'fontSize'   => 60,
                'hOffset'   => 1500 - 10 * strlen($person_name),
                'vOffset'   => 1345,
            ])
            ->text(ucwords_tr($person_name), [
                'color'      => '#001361',
                'fontPath'   => FCPATH . 'certificates/fonts/Pacifico-Regular.ttf',
                'opacity'    => 0.1,
                'withShadow' => false,
                'hAlign'     => 'left',
                'vAlign'     => 'top',
                'fontSize'   => 80,
                'hOffset'   => 1750 - 10 * strlen($person_name),
                'vOffset'   => 1325,
            ])
            ->text("Doğrulama Anahtarı : " . $certificate_token, [
                'color'      => '#001361',
                'fontPath'   => FCPATH . 'certificates/fonts/Gotham-Light.otf',
                'opacity'    => 0.1,
                'withShadow' => false,
                'hAlign'     => 'left',
                'vAlign'     => 'top',
                'fontSize'   => 25,
                'hOffset'   => 370,
                'vOffset'   => 2410,
            ]);

        if (isset($course_title_1)) :

            $image->text(strtoupper_tr($course_title_1), [
                'color'      => '#001361',
                'fontPath'   => FCPATH . 'certificates/fonts/Gotham-Medium.otf',
                'opacity'    => 0.1,
                'withShadow' => false,
                'hAlign'     => 'center',
                'vAlign'     => 'top',
                'fontSize'   => 64,
                'hOffset'   => 0,
                'vOffset'   => 1000,
            ]);

            $image->text(strtoupper_tr($course_title_2), [
                'color'      => '#001361',
                'fontPath'   => FCPATH . 'certificates/fonts/Gotham-Medium.otf',
                'opacity'    => 0.1,
                'withShadow' => false,
                'hAlign'     => 'center',
                'vAlign'     => 'top',
                'fontSize'   => 64,
                'hOffset'   => 0,
                'vOffset'   => 1125,
            ]);

        else :
            $image->text(strtoupper_tr($course_title), [
                'color'      => '#001361',
                'fontPath'   => FCPATH . 'certificates/fonts/Gotham-Medium.otf',
                'opacity'    => 0.1,
                'withShadow' => false,
                'hAlign'     => 'center',
                'vAlign'     => 'top',
                'fontSize'   => 64,
                'hOffset'   => 0,
                'vOffset'   => 1062,
            ]);
        endif;

        for ($i = 0; $i < $splited_certificate_template_part; $i++) {
            $vrt_offset = 1550;
            $line_number = $i + 1;
            $image->text($splited_certificate_template[$i], [
                'color'      => '#001361',
                'fontPath'   => FCPATH . 'certificates/fonts/Gotham-Light.otf',
                'opacity'    => 0.1,
                'withShadow' => false,
                'hAlign'     => 'center',
                'vAlign'     => 'top',
                'fontSize'   => 40,
                'hOffset'   => 0,
                'vOffset'   => $vrt_offset + (70 * $i),
            ]);
        }

        $image->save($certificate_src);
        //header('Location: verify/'.$certificate_token);
    }
}
