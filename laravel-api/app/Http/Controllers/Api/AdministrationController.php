<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\PersonRole;
use App\Models\Organisation;
use App\Models\faqQuestions;
use App\Models\Contact;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\CourseRate;


class AdministrationController extends Controller
{
    public function __construct()
    {
        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if (!$personInfo) : die();
        endif;

        if ($personInfo['person_role_id'] != 1) {
            echo 'Access denied.';
            exit;
        }
    }

    public function menu()
    {
        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->find($loggedPersonId);

        $data = [
            'pageTitle' => "Administration",
            'personInfo' => $personInfo
        ];
    }
    public function personManagement()
    {
        $loggedPersonId =  session()->get('loggedPerson');
        $authModel = new User();
        $personInfo =  $authModel->find($loggedPersonId);

        // $organisationModel = new \App\Models\organisationModel();
        // $organisationInfo = $organisationModel->where(array('deleted' => 0))->findAll();

        $personRoleModel = new personRole();
        $personRoleInfo = $personRoleModel->findAll();
        $data = [
            'pageTitle' => "Person Management",
            // 'organisationInfo' => $organisationInfo,
            'personRoleInfo' => $personRoleInfo,
            'personInfo' => $personInfo
        ];
        // if ($personInfo["person_role_id"] == 1) :
        //     return view('administration/person/person_management', $data);
        // elseif ($personInfo["person_role_id"] == 3) :
        //     return view('administration/person/person_management_organisation', $data);
        // endif;
    }

    public function organisationManagement()
    {
        $organisationModel = new Organisation();
        $organisationInfo = $organisationModel->where(array('deleted' => 0))->orderBy('id', 'DESC')->findAll();
        $organisationActiveCount = $organisationModel->where(array('deleted' => 0, 'status' => 1))->countAllResults();
        $organisationDeactiveCount = $organisationModel->where(array('deleted' => 0, 'status' => 0))->countAllResults();

        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        $data = [
            'pageTitle' => "Organisation Management",
            'organisationInfo' => $organisationInfo,
            'organisationActiveCount' => $organisationActiveCount,
            'organisationDeactiveCount' => $organisationDeactiveCount,
            'personInfo' => $personInfo
        ];

        return view('administration/organisation/management', $data);
    }

    public function instructorManagement()
    {

        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        $data = [
            'pageTitle' => "Instructor Management",
            'personInfo' => $personInfo
        ];

        return view('administration/instructor/management', $data);
    }
    public function organisationReport()
    {

        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        $data = [
            'pageTitle' => "Organisation Report",
            'personInfo' => $personInfo,
            'ajaxUrl' => "OrganisationReport/reportAllOrganisations"
        ];

        return view('administration/report/management', $data);
    }

    public function individualReport()
    {

        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        $data = [
            'pageTitle' => "Bireysel Sertifika Raporu",
            'personInfo' => $personInfo,
            'ajaxUrl' => "OrganisationReport/reportAllIndividuals"
        ];

        return view('administration/report/management', $data);
    }


    public function packageManagement()
    {
        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        if ($personInfo["person_role_id"] != 1) :
            exit("Yetkiniz yoktur!");
        endif;

        $data = [
            'pageTitle' => "Package Management",
            'personInfo' => $personInfo
        ];

        return view('administration/package/management', $data);
    }

    public function courseManagement()
    {
        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();


        $data = [
            'pageTitle' => "Course Management",
            'personInfo' => $personInfo
        ];

        return view('administration/course/management', $data);
    }

    public function faqManagement()
    {
        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        $faqQuestionsModel = new faqQuestions();
        $faqList = $faqQuestionsModel->select("faq_questions.id, faq_questions.title_tr, faq_questions.title_en, faq_questions.check_faq_list, faq_questions.faq_order, faq_questions.view_count, faq_questions.status")
            ->select("faq_category.title_tr as categoryTitleTr, faq_category.title_en as categoryTitleEn")
            ->join("faq_category", "faq_category.id = faq_questions.faq_category_id", "left")
            ->orderBy("faq_order", "DESC")
            ->where(array("faq_questions.deleted" => 0))->findAll();
        $data = [
            'pageTitle' => "Faq Management",
            'personInfo' => $personInfo,
            'faqList' => $faqList
        ];

        //print_r($faqList);

        return view('administration/faq/management', $data);
    }

    public function contactManagement()
    {
        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        $contactModel = new contact();
        $list = $contactModel
            ->orderBy("id", "DESC")
            ->findAll();

        $data = [
            'pageTitle' => "İletişim Formu",
            'personInfo' => $personInfo,
            'list' => $list
        ];
        return view('administration/contact/management', $data);
    }

    public function feedbackManagement()
    {
        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();


        $organisationModel = new Organisation();
        $courseModel = new Course();
        $instructorModel = new Instructor();

        $courseRateModel = new CourseRate();
        $listRate = $courseRateModel
            ->orderBy("id", "DESC")
            ->findAll();

        $list = [];
        foreach ($listRate as $listRateItem) :

            $person_id = $listRateItem["person_id"];
            $course_id = $listRateItem["course_id"];
            $instructor_id = $listRateItem["instructor_id"];

            $person_detail =  $authModel
                ->select("first_name, last_name, organisation_id")
                ->where('id', $person_id)->first();

            $organisation_detail =  $organisationModel
                ->select("title")
                ->where('id', $person_detail["organisation_id"])->first();

            $course_detail =  $courseModel
                ->select("title")
                ->where('id', $course_id)->first();

            $instructor_detail =  $instructorModel
                ->select("name, surname")
                ->where('id', $instructor_id)->first();

            $list[] = array(
                "person_detail" => $person_detail,
                "organisation_detail" => $organisation_detail,
                "course_detail" => $course_detail,
                "instructor_detail" => $instructor_detail,
                "course_like" => $listRateItem["like_status"],
                "course_rate" => $listRateItem["course_rate"],
                "course_comments" => $listRateItem["course_comments"],
                "instructor_rate" => $listRateItem["instructor_rate"],
                "instructor_comments" => $listRateItem["instructor_comments"],
                "create_date" => $listRateItem["create_date"],
            );

        endforeach;

        $data = [
            'pageTitle' => "Geri Bildirim Yönetimi",
            'personInfo' => $personInfo,
            'list' => $list
        ];
        return view('administration/course/feedback/list', $data);
    }


    public function priceManagement()
    {
        $authModel = new User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        if ($personInfo["id"] != "526" && $personInfo["id"] != "542" && $personInfo["id"] != "770"):
            die("Erişim yetkiniz yoktur");
        endif;

        $data = [
            'pageTitle' => "Fiyatlandırma",
            'personInfo' => $personInfo,
        ];
        return view('administration/price/priceCalculator', $data);

    }

}
