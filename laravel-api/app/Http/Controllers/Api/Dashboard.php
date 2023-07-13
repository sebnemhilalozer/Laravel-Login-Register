<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class Dashboard extends Controller
{


    function __construct()
    {
        helper(['url','form']);
        if ( ! session()->get('loggedPerson'))
        {
            redirect('login');
        }
    }

    public function index()
    {
        $authModel = new \App\Models\User();
        $coursePackagePersonModel = new \App\Models\CoursePackagePerson();
        $courseEnrollModel = new \App\Models\CourseEnroll();

        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        //$packageList = $coursePackagePersonModel->where(array( 'end_date', 'dropout_status' => 0))->groupBy('package_id')->join('course_package_detail')->findAll(); cpp table'ında dropout_status yok!
        //$buy_courses = $courseEnrollModel->where(array('end_date', 'dropout_status' => 0))->groupBy('course_id')->join('course')->findAll();

        if (!$personInfo):
            exit();
        endif;

        /*
         * Organisation
         */
        $organisationModel = new \App\Models\Organisation();
        $organisationInfo =  $organisationModel
            ->select("id, title, img_path")
            ->where('id', $personInfo["organisation_id"])->first();



        /*
         *      Organizasyona açık eğitimler
         *      x_organisation_courses
         */

        $organisationCoursesModel = new \App\Models\OrganisationCourses();
        $openAccess =  $organisationCoursesModel
            ->select("course_id")
            ->where(array("status" => 1, "organisation_id" => $organisationInfo["id"] ))->findAll();


        $courseEnrollModel = new \App\Models\CourseEnroll();
        foreach ($openAccess as $itemOpenAccess):

            $open_course_id = $itemOpenAccess["course_id"];
            $open_access_check =  $courseEnrollModel
                ->select("course_id")
                ->where(array( "course_id" => $open_course_id, "person_id" => $personInfo["id"] ))->first();

            if (empty($open_access_check ["course_id"])):
                $open_access_insert_data = [
                    "person_id" => $personInfo["id"],
                    "course_id" => $open_course_id,
                    "created_at" => date("Y-m-d H:i:s"),
                    "enroll_status" => 1,
                    "enroll_date" => date("Y-m-d H:i:s"),
                    "enroll_by" =>  $personInfo["first_name"]. " ".  $personInfo["last_name"]
                ];
                $courseEnrollModel->insert($open_access_insert_data);
            endif;

        endforeach;

        /*
         * Course Enroll Number
         */

        $course_list = $courseEnrollModel->select('id, course_id')->where( array('person_id' => $personInfo["id"], 'dropout_status' => 0, 'course_completed' => 0) )->findAll();
        $completedEnrollList = $courseEnrollModel->select('id')->where( array('person_id' => $personInfo["id"], 'course_completed' => 1) )->findAll();
        foreach($completedEnrollList as $completedEnroll){
            Certificate::create_certificate(1, $completedEnroll["id"]);
        }
        $courseContentModel = new \App\Models\CourseContent();
        $courseCompleted = new \App\Models\CourseCompleted();


        /*
         * Course tamamlama durumu
         */
        foreach ($course_list as $item_course_list):
            $content = $courseContentModel->select("id")
                ->where(array("status"=>1, "deleted"=>0, "course_id"=> $item_course_list["course_id"]))
                ->where('course_type_id !=','1')
                ->find();

            $courseCompletedStatus = 1;
            foreach ($content as $itemContent):
                $userCompleted =  $courseCompleted->Select("completed")
                    ->where(array('person_id'=>$personInfo["id"], 'course_id' => $item_course_list["course_id"], 'content_id'=> $itemContent["id"]))->first();
                if(!isset($userCompleted["completed"])):
                    $courseCompletedStatus = 0;
                elseif ($userCompleted["completed"] == 0):
                    $courseCompletedStatus = 0;
                endif;
            endforeach;

            if ($courseCompletedStatus==1):

                /*
                 * Son content tamamalama tarihini çekip -> aşağıdaki tarihle güncelle
                 */
                $userCompletedUpdate =  $courseCompleted->Select("created_at")
                    ->where(array('person_id' => $personInfo["id"], 'course_id' => $item_course_list["course_id"] ))
                    ->where(array("completed" => 1))
                    ->orderBy("id", "DESC")
                    ->first();

                $data_u = [
                    "course_completed" => 1,
                    "course_completed_date" => $userCompletedUpdate["created_at"]
                ];
                $db      = \Config\Database::connect();
                $builder = $db->table('course_enroll');
                $query = $builder->where(array('person_id'  => $personInfo["id"], 'course_id' => $item_course_list["course_id"]))->update($data_u);
            endif;
        endforeach;


        /*
         * Popular Course Week
         */
        $courseVisitsModel = new \App\Models\CourseVisits();
        $popular_course = $courseVisitsModel
            ->select("course_id, Count(id) as SumCount")
            ->orderBy("SumCount", "DESC")
            ->where( "created_at BETWEEN CURDATE()-INTERVAL 1 WEEK AND CURDATE()")
            ->groupBy("course_id")
            ->limit(3)
            ->find();
        $courses_id = [];


        foreach ($popular_course as $item_popular_course){
            $courses_id[] = $item_popular_course["course_id"];
        }

        $courseModel = new \App\Models\Course();

        if (!empty($courses_id)):
            $popular_courses = $courseModel
                ->select("course.id, course.title as courseTitle, course.slug, course.img_path")
                ->select("instructor.name, instructor.surname, instructor.img as instructor_img")
                ->select("course_category.title as course_category")
                ->join("instructor", "instructor.id = course.instructor_id","left")
                ->join("course_category", "course_category.id = course.category_id","left")
                ->orderBy("course.course_order", "DESC")
                ->where(array("course.status"=>1, "course.deleted"=>0))
                ->whereIn("course.id", $courses_id)
                ->find();
        else:
            $popular_courses = array();
        endif;

        /*
         * NEW COURSES LIMIT 3
         */

        $new_courses = $courseModel->select("course.id, course.title as courseTitle, course.slug, course.img_path")
            ->select("instructor.name, instructor.surname, instructor.img as instructor_img")
            ->select("course_category.title as course_category")
            ->join("instructor", "instructor.id = course.instructor_id","left")
            ->join("course_category", "course_category.id = course.category_id","left")
            ->orderBy("course.published_date", "DESC")
            ->where(array("course.status"=>1, "course.deleted"=>0))
            ->limit(3)
            ->find();

        /*
         * Visit Last Course
         */
        $last_courses = $courseModel->select("course.id, course.title as courseTitle, course.img_path, course_enroll.last_access_date")
            ->select("instructor.prefix, instructor.name, instructor.surname, instructor.img as instructor_img")
            ->join("instructor", "instructor.id = course.instructor_id","left")
            ->join("course_enroll", "course_enroll.course_id = course.id")
            ->where(array("course.status" => 1, "course.deleted" => 0))
            ->where(array("course_enroll.person_id" =>$personInfo["id"], 'course_enroll.dropout_status' => 0)) // TODO course_enroll.end_date
            ->orderBy("course_enroll.last_access_date", "DESC")
            ->groupBy("course.id")
            ->first();

        /*
         * Top Course 4
         */
        $top_courses = $courseModel->select("course.id, course.title as courseTitle, course.img_path")
            ->select("certificate_user.certificate_token")
            ->join("course_enroll", "course_enroll.course_id = course.id")
            ->join("certificate_user", "course.id = certificate_user.course_id AND certificate_user.person_id=".$personInfo["id"],"left") // WARNING -> enroll_id olmamalı!!
            ->where(array("course.status"=>1, "course.deleted"=>0))
            ->where(array("course_enroll.person_id"=>$personInfo["id"], 'course_enroll.dropout_status'=>0)) // TODO course_enroll.end_date
            ->where("course.id !=", $last_courses["id"])
            ->orderBy("course_enroll.last_access_date", "DESC")
            ->groupBy("course.id")
            ->limit(4)
            ->find();

        /*
         * ORGANISATION COURSES ALLOW - OPEN COURSE
         */
        $today_date = date("Y-m-d");
        /*
        $where = " (organisation_courses.organisation_id ='".$personInfo["organisation_id"]."' AND organisation_courses.status= 1) OR (organisation_courses.organisation_id ='".$personInfo["organisation_id"]."' AND organisation_courses.status= 2 AND organisation_courses.start_date <='". $today_date."' AND organisation_courses.end_date >='".$today_date."' )";
        $open_courses = $courseModel->select("course.id, course.title as courseTitle, course.slug, course.img_path")
            ->select("instructor.name, instructor.surname, instructor.img as instructor_img")
            ->select("course_category.title as course_category")
            ->join("instructor", "instructor.id = course.instructor_id","left")
            ->join("course_category", "course_category.id = course.category_id","left")
            ->join("organisation_courses", "organisation_courses.course_id = course.id","left")
            ->orderBy("course.published_date", "DESC")
            ->where(array("course.status"=>1, "course.deleted"=>0))
            ->where($where)
            ->limit(4)
            ->find();
        */




        if ($personInfo["organisation_id"] == 30):
            $this->fullAccessCertificateControl();
            $coursePackagePersonModel = new \App\Models\CoursePackagePerson();
            $packageList = $coursePackagePersonModel
                ->select("course_package_detail.id, course_package_detail.title, course_package_detail.slug, course_package_detail.tag, course_package_detail.cover_img, course_package_person.start_date, course_package_person.end_date")
                ->where(array("course_package_detail.status" => 1, "course_package_detail.deleted" => 0))
                ->where(array("course_package_person.purchase_type" => "Certificate", "course_package_person.person_id" => $personInfo["id"] ))
                ->join("course_package_detail", "course_package_detail.id = course_package_person.package_id","left")
                ->groupBy("course_package_person.package_id")
                ->findAll();

        else:
            $coursePackageOrganisationModel = new \App\Models\CoursePackageOrganisation();
            /*
            $packageList = $coursePackageOrganisationModel
                ->select("course_package_detail.id, course_package_detail.title,course_package_detail.slug, course_package_detail.tag, course_package_detail.cover_img, course_package_organisation.start_date,course_package_organisation.end_date")
                ->join("course_package_detail", "course_package_detail.id = course_package_organisation.package_id","left")
                ->where(array("course_package_organisation.organisation_id" => $personInfo["organisation_id"], "course_package_organisation.status" => 1))
                ->where(array("course_package_detail.status" => 1, "course_package_detail.deleted" => 0))
                ->orderBy("course_package_detail.package_order", "DESC")
                ->groupBy("course_package_organisation.package_id") 
                ->findAll();
            */
            $packageList = $coursePackageOrganisationModel->query("
            SELECT packageList.* FROM (
                (SELECT x_course_package_detail.id, x_course_package_detail.title, x_course_package_detail.slug, x_course_package_detail.tag, x_course_package_detail.cover_img, x_course_package_person.start_date, x_course_package_person.end_date
                FROM x_course_package_person
                LEFT JOIN x_course_package_detail ON x_course_package_detail.id = x_course_package_person.package_id
                WHERE x_course_package_detail.status = 1
                AND x_course_package_detail.deleted = 0
                AND x_course_package_person.purchase_type = 'Certificate'
                AND x_course_package_person.person_id = ".$personInfo["id"]."
                GROUP BY x_course_package_person.package_id)
            UNION
                (SELECT x_course_package_detail.id, x_course_package_detail.title, x_course_package_detail.slug, x_course_package_detail.tag, x_course_package_detail.cover_img, x_course_package_organisation.start_date, x_course_package_organisation.end_date
                FROM x_course_package_organisation
                LEFT JOIN x_course_package_detail ON x_course_package_detail.id = x_course_package_organisation.package_id
                WHERE x_course_package_organisation.organisation_id = ".$personInfo["organisation_id"]."
                AND x_course_package_organisation.status = 1
                AND x_course_package_detail.status = 1
                AND x_course_package_detail.deleted = 0
                GROUP BY x_course_package_organisation.package_id
                ORDER BY x_course_package_detail.package_order DESC)
            ) AS packageList GROUP BY packageList.id;
            ")->getResultArray();
        endif;


        $certificate_detail = array();
       
         foreach($packageList as &$packageListItem):
            
     
            $coursePackageModel = new \App\Models\CoursePackage();
            $coursePackageDetailModel = new \App\Models\CoursePackageDetail();
            $courseEnrollModel = new \App\Models\CourseEnroll();
            $courseModel = new \App\Models\Course();

            $package_id = $packageListItem["id"];
            $packageListDetail = $coursePackageDetailModel
                ->select("id, title, slug, total_course")
                ->where(array("id" => $package_id, "status" => 1, "deleted" => 0))->first();

                $packageListItem["certificate"] = array(
                    "count_course" => 0,
                    "completed_course" => 0,
                    "not_completed_course" => 0
                );
                
            if ($packageListDetail):
                $id = $packageListDetail["id"];

                $packageCourseList = $coursePackageModel
                    ->select("course_id")
                    ->where(array("package_id" => $id, "status" => 1, "deleted" => 0))->findAll();

                $count_course = 0;
                $completed_course = 0;
                $not_completed_course = 0;
               

                foreach ($packageCourseList as $itemPackageCourseList):
                    $count_course++;

                    $course_id = $itemPackageCourseList["course_id"];
                    $course = $courseModel->select("course.id, course.title as course_title, course.slug, course.img_path")
                        ->select("instructor.name, instructor.surname, instructor.img as instructor_img")
                        ->join("instructor", "instructor.id = course.instructor_id","left")
                        ->where(array("course.id"=> $course_id, "course.status"=> 1, "course.deleted"=> 0))
                        ->first();

                        $enroll = $courseEnrollModel->select("course_enroll.course_completed")
                            ->where(array("person_id"=> $personInfo["id"], "course_id"=> $course_id))
                            ->first();
                        if ($enroll):

                            if ($enroll["course_completed"] == 1):
                                $completed_course++;
                            else:
                                $not_completed_course++;
                            endif;

                            $enroll_status = array(
                                "course_completed" => $enroll["course_completed"]
                            );
                        else:
                            /*
                            $data_insert = [
                                'person_id' => $personInfo["id"],
                                'course_id' => $course_id,
                                'enroll_status'  => 2,
                                'enroll_date' => date("Y-m-d H:i:s"),
                                'enroll_by' => $personInfo["first_name"]." ".$personInfo["last_name"]
                            ];
                            $courseEnrollModel->insert($data_insert);
                            */

                            $not_completed_course++;
                            $enroll_status = array(
                                "course_completed" => 0
                            );
                        endif;
                        if($course):
                            $certificate_detail["course"][] = array_merge($course, $enroll_status);
                        endif;

                endforeach;
                
                  $packageListItem["certificate"] = array(
                    "count_course" => $count_course,
                    "completed_course" => $completed_course,
                    "not_completed_course" => $not_completed_course
                );

            endif;
           
        endforeach;
       


        /*
        $buy_courses = array();
        $buy_courses = $courseModel->select("course.id, course.title as courseTitle, course.slug, course.img_path")
            ->select("instructor.name, instructor.surname, instructor.img as instructor_img")
            ->select("course_category.title as course_category")
            ->join("course_package_person", "course_package_person.package_id = course.id", "left")
            ->join("instructor", "instructor.id = course.instructor_id","left")
            ->join("course_category", "course_category.id = course.category_id","left")
            ->where(array("course.status" => 1, "course.deleted" => 0))
            ->where(array("course_package_person.purchase_type" => "Course", "course_package_person.person_id" => $personInfo["id"] ))
            ->orderBy("course_package_person.created_at", "DESC")
            ->find();
        */
        $buy_courses = array();
        $buy_courses = $courseModel->select("course.id, course.title as courseTitle, course.img_path")
            ->select("certificate_user.certificate_token")
            ->join("course_enroll", "course_enroll.course_id = course.id")
            ->join("certificate_user", "course.id = certificate_user.course_id AND certificate_user.person_id=".$personInfo["id"],"left") // WARNING -> enroll_id olmamalı!!
            ->where(array("course.status"=>1, "course.deleted"=>0))
            ->where(array("course_enroll.person_id"=>$personInfo["id"], 'course_enroll.dropout_status'=>0)) // TODO course_enroll.end_date
            ->groupBy("course.id")
            ->find();


        $courseEnrollModel = new \App\Models\CourseEnroll();
        $course_completed = count($courseEnrollModel
            ->select('course_id')
            ->where( array('person_id' => $personInfo["id"], 'course_completed'  => 1, 'dropout_status' => 0) )
            ->groupBy("course_id")
            ->findAll()
        );
        $course_continue = count($courseEnrollModel
            ->select('course_id')
            ->where( array('person_id' => $personInfo["id"], 'course_completed'  => 0, 'dropout_status' => 0) )
            ->groupBy("course_id")
            ->findAll()
        );

        $suggestedCertificates = $this->suggestCertificates(array_column($buy_courses, 'id'), array_column($packageList, 'id'));
        if ($personInfo["organisation_id"] != 30):
            $suggestedCertificates = [];
        endif;

        $now_date = date("Y-m-d H:i:s");
        $fullAccessEnrollModel = new \App\Models\FullAccessEnroll();
        $fullAccessEnrollCheck = $fullAccessEnrollModel->select("id")
            ->where(array("person_id" => $personInfo["id"], "status" => 1, "deleted" => 0, "end_date >" => $now_date))->first();

        if (!empty($fullAccessEnrollCheck)):
            session()->set('personFullAccessActive', 1);
        else:
            session()->set('personFullAccessActive', 0);
        endif;
        
        $data = [
            'pageTitle' => "Genel Bakış",
            'pageLink' => "dashboard",
            'personInfo' => $personInfo,
            'organisationInfo' => $organisationInfo,
            'course_continue' => $course_continue,
            'course_completed' => $course_completed,
            'popular_courses' => $popular_courses,
            'new_courses' => $new_courses,
            'top_courses' => $top_courses,
            'last_courses' => $last_courses,
            //'open_courses' => $open_courses,
            'certificate_list' => $packageList,
            'buy_courses' => $buy_courses,
            'suggestedCertificates' => $suggestedCertificates

        ];
        return view('frontend/dashboard/index', $data);
    }


    /*
     * COURSE ORGANISATİON
     */
    public function courseOrganisation($category_slug = null)
    {

        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if (!$personInfo):
            exit();
        endif;
        if ($personInfo["organisation_id"] == 30):
            return redirect()->to(site_url("certificate-programs"));
            die();
        endif;


        $categoryModel = new \App\Models\CourseCategory();

        /*$categoryList = $categoryModel->select("id, title, slug")
            ->orderBy("category_order", "DESC")
            ->where(array("status"=>1, "deleted"=>0))->findAll();*/
        $courseModel = new \App\Models\Course();
        $today_date = date("Y-m-d");
        $where_a = " (organisation_courses.organisation_id ='".$personInfo["organisation_id"]."' AND organisation_courses.status= 1) OR (organisation_courses.organisation_id ='".$personInfo["organisation_id"]."' AND organisation_courses.status= 2 AND organisation_courses.start_date <='". $today_date."' AND organisation_courses.end_date >='".$today_date."' )";
        $where_b = array("course.status" =>1, "course.deleted" =>0);

        $courseListCategory = $courseModel->select("course.category_id")
            ->join("organisation_courses", "organisation_courses.course_id = course.id", "left")
            ->where($where_b)
            ->where($where_a)
            ->find();

        function unique_multi_array($array, $key) {
            $temp_array = array();
            $i = 0;
            $key_array = array();

            foreach($array as $val) {
                if (!in_array($val[$key], $key_array)) {
                    $key_array[$i] = $val[$key];
                    $temp_array[$i] = $val;
                }
                $i++;
            }
            return $temp_array;
        }

        $categoryList = array();
        $unique_category_id = unique_multi_array($courseListCategory,"category_id");
        foreach ($unique_category_id as $item_unique_category_id):
            $category_id = $item_unique_category_id["category_id"];
            $uniqueCategoryList = $categoryModel->select("id, title, slug")
            ->where(array("status"=>1, "deleted"=>0, "id" => $category_id))->first();
            if ($uniqueCategoryList):
                $categoryList[] = $uniqueCategoryList;
            endif;
        endforeach;



        if (!empty($category_slug)):
            $category = $categoryModel->select("id, title, slug")
                ->orderBy("category_order", "DESC")
                ->where(array("status"=>1, "deleted"=>0, "slug"=> $category_slug ))->first();
        else:
            $category = $categoryList[0];
        endif;


        /*
         * ORGANISATION COURSES ALLOW - OPEN COURSE
         */
        if (!empty($category_slug)):
            $where_2 = array("course.status"=>1, "course.deleted"=>0, "course.category_id" => $category["id"]);
        else:
            $where_2 = array("course.status"=>1, "course.deleted"=>0, "course.category_id" => $categoryList[0]["id"]);
        endif;


        $courseList = $courseModel->select("course.id, course.title as courseTitle, course.slug, course.img_path")
            ->join("organisation_courses", "organisation_courses.course_id = course.id", "left")
            ->orderBy("course.published_date", "DESC")
            ->where($where_2)
            ->where($where_a)
            ->find();



        $data = [
            'pageTitle' => "Açık Eğitimler",
            'personInfo' => $personInfo,
            'category' => $category,
            "categoryList" => $categoryList,
            'courseList' => $courseList,

        ];
        return view('frontend/dashboard/organisation_course', $data);
    }


    function courseEnrollCheck(){

        $loggedPersonId =  session()->get('loggedPerson');
        $authModel = new \App\Models\User();
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        if (!$personInfo):
            exit();
        endif;

        $course_id = $this->request->getPost('course_id');

        /*
         * Person Course Enroll
         */
        $courseEnrollModel = new \App\Models\CourseEnroll();
        $enroll =  $courseEnrollModel->Select("id")
            ->where(array('person_id'=>$personInfo["id"], 'course_id' => $course_id , 'dropout_status' => 0))->first();

        /*
         * Content -> Eğer devam ediyorsa son content i getir
         */
        $courseCompletedModel = new \App\Models\CourseCompleted();
        $continue_completed = $courseCompletedModel->select("content_id")
            ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id ))
            ->orderBy("last_change_date", "DESC")
            ->first();

        if (!$continue_completed){
            $courseContentModel = new \App\Models\CourseContent();
            $continue_completed = $courseContentModel->select("id as content_id")
                ->where(array("course_id" => $course_id, "deleted" => 0, "status" =>1))
                ->orderBy("content_order", "DESC")
                ->orderBy("id", "ASC")
                ->first();
        }


        if ($enroll):
            echo json_encode(['code' => 1, 'content_id' => $continue_completed["content_id"] ]);
        else:
            echo json_encode(['code' => 0 ]);
        endif;

    }

    function courseEnrollSave(){

        $loggedPersonId =  session()->get('loggedPerson');
        $authModel  = new \App\Models\User();
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        $result = array();

        if (!$personInfo):
            exit();
        endif;

        $course_id = $this->request->getPost('course_id');

        $organisationCoursesModel  = new \App\Models\OrganisationCourses();

        $today_date = date("Y-m-d");
        $where = " (organisation_id ='".$personInfo["organisation_id"]."' AND course_id = '". $course_id ."' AND status= 1) OR (organisation_id ='".$personInfo["organisation_id"]."' AND course_id='". $course_id ."' AND status= 2 AND start_date <='". $today_date."' AND end_date >='".$today_date."' )";
        $organisationInfo =  $organisationCoursesModel
                            ->select("id")
                            ->where($where)->first();

        if ($organisationInfo):
            $courseEnrollModel  = new \App\Models\CourseEnroll();
            $courseEnroll =  $courseEnrollModel
                            ->select("id")
                            ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id, 'dropout_status' => 0))->first();

            $courseContentModel = new \App\Models\CourseContent();
            $continue = $courseContentModel->select("id as content_id")
                ->where(array("course_id" => $course_id, "deleted" => 0, "status" =>1))
                ->orderBy("content_order", "DESC")
                ->orderBy("id", "ASC")
                ->first();


            if ($courseEnroll):
                    $result = array("code" => 2, "message" => "Bu derse zaten kayıtlısınız. Tekrar kayıt olmanıza gerek yoktur.", "continue" => $continue);
                else:

                    $courseEnrollStatus =  $courseEnrollModel
                        ->select("id")
                        ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id, 'dropout_status' => 1))->first();

                    if ($courseEnrollStatus):
                        //update

                        $update_data = array(
                            "dropout_status" => 0
                        );
                        $update = $courseEnrollModel->update($courseEnrollStatus["id"],$update_data);
                        if ($update):
                            /** LOGS **/
                            $insert_id = $courseEnrollModel->getInsertID();
                            $this->saveLog("course_enroll",$insert_id,"Create");
                            $result = array("code" => 1, "message" => "Kayıt işleminiz gerçekleştirilmiştir.", "continue" => $continue);
                        else:
                            $result = array("code" => 0, "message" => "Beklenmeyeen bir hata meydana geldi. Lütfen daha sonra tekrar deneyiniz.");
                        endif;
                    else:
                        $insert_data = array(
                            "person_id" => $personInfo["id"],
                            "course_id" => $course_id,
                            "created_at" => date("Y-m-d H:i:s"),
                        );
                        $insert = $courseEnrollModel->insert($insert_data);
                        if ($insert):
                            /** LOGS **/
                            $insert_id = $courseEnrollModel->getInsertID();
                            $this->saveLog("course_enroll",$insert_id,"Create");
                            $result = array("code" => 1, "message" => "Kayıt işleminiz gerçekleştirilmiştir.", "continue" => $continue);
                        else:
                            $result = array("code" => 0, "message" => "Beklenmeyeen bir hata meydana geldi. Lütfen daha sonra tekrar deneyiniz.");
                        endif;

                    endif;


                endif;
        else:
            $result = array("code" => 0, "message" => "Bu işlem için yetkiniz yoktur.");
        endif;

        echo json_encode($result);

    }

    function courseEnrollDropout(){

        $loggedPersonId =  session()->get('loggedPerson');
        $authModel  = new \App\Models\User();
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        $result = array();

        if (!$personInfo):
            exit();
        endif;

        $course_id = $this->request->getPost('course_id');

        $courseEnrollModel  = new \App\Models\CourseEnroll();
        $courseEnrollStatus =  $courseEnrollModel
            ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id))->first();

        if ($courseEnrollStatus):

            $courseEnroll =  $courseEnrollModel
                ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id))->first();

            if (!$courseEnroll):
                $result = array("code" => 2, "message" => "Bu derse kayıtlı değilsiniz. Tekrar bırakmanıza gerek yoktur.");
            else:
                if ($courseEnroll["dropout_status"] == 1):
                    $result = array("code" => 2, "message" => "Bu derse kayıtlı değilsiniz. Tekrar bırakmanıza gerek yoktur.");
                else:
                    $result = array("code" => 0, "message" => $courseEnroll);

                    $update_data = array(
                        "dropout_status" => 1,
                        "dropout_date" => date("Y-m-d H:i:s"),
                    );
                    $courseEnrollModel->update($courseEnroll["id"], $update_data);

                    $insert_data = array(
                        "person_id" => $courseEnroll["person_id"],
                        "course_id" =>  $courseEnroll["course_id"],
                        "last_access_date" => $courseEnroll["last_access_date"],
                        "enroll_date" => $courseEnroll["created_at"],
                        "course_completed" => $courseEnroll["course_completed"],
                        "course_completed_date" => $courseEnroll["course_completed_date"],
                        "dropout_date" =>date("Y-m-d H:i:s"),
                    );
                    $courseEnrollDropoutModel  = new \App\Models\CourseEnrollDropout();
                    $insert = $courseEnrollDropoutModel->insert($insert_data);

                    if ($insert):
                        $result = array("code" => 1, "message" => "İşleminiz gerçekleştirilmiştir.");
                    else:
                        $result = array("code" => 0, "message" => "Beklenmeyeen bir hata meydana geldi. Lütfen daha sonra tekrar deneyiniz.");
                    endif;
                endif;




                /*
                 * LOGS ADM LOS
                if ($insert):
                    $insert_id = $courseEnrollModel->getInsertID();
                    $this->saveLog("course_enroll",$insert_id,"Create");
                    $result = array("code" => 1, "message" => "Kayıt işleminiz gerçekleştirilmiştir.", "continue" => $continue);
                else:
                    $result = array("code" => 0, "message" => "Beklenmeyeen bir hata meydana geldi. Lütfen daha sonra tekrar deneyiniz.");
                endif;
                */
            endif;
        else:
            $result = array("code" => 0, "message" => "Bu işlem için yetkiniz yoktur.");
        endif;

        echo json_encode($result);

    }




    public function courseDetail($courseSlug, $courseContentId=null){ // WARNING -> enroll_id olmamalı!!



        helper('slug');
        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        if (!$personInfo):die();endif;


        /*
         * Active Course
         */
        $courseModel = new \App\Models\Course();
        $course = $courseModel->select("course.id as courseId, course.title as courseTitle, course.slug as courseSlug, course.audience, course.details, course.meta_description, course.img_path")
            ->select("instructor.id as instructorId, instructor.prefix, instructor.name, instructor.surname,instructor.slug as instructorSlug, instructor.job as instructorJob, instructor.img as instructorImg ")
            ->join("instructor", "instructor.id = course.instructor_id","left")
            ->where(array("course.status"=>1, "course.deleted"=>0, "course.slug"=> $courseSlug))->first();

        $courseId = $course["courseId"];
        if (!$courseId):die("Beklenmedik bir hata meydana geldi. Lütfen yöneticinize başvurun.");endif;


        /*
         * Person Enroll Check
         */
        $courseEnrollModel = new \App\Models\CourseEnroll();
        $person_enroll =  $courseEnrollModel->Select("id")
            ->where(array('person_id'=>$personInfo["id"], 'course_id' => $courseId, 'dropout_status' => 0)) // TODO course_enroll.end_date
            ->first();
        if (!$person_enroll):die("Erişim yetkiniz yoktur.");endif;




        $coursePackageModel = new \App\Models\CoursePackage();
        $package_courses = $coursePackageModel->select("course_package.id")
            ->join("course_package_organisation", "course_package_organisation.package_id = course_package.package_id","left")
            ->where(array("course_package.course_id" => $courseId,"course_package.status" => 1, "course_package.deleted" => 0))
            ->where(array("course_package_organisation.organisation_id" => $personInfo["organisation_id"], "course_package_organisation.status" => 1))
            ->first();
        if (!$package_courses):

            /*
             * ORGANISATION COURSES ALLOW - active/passive/time check
             */
            if ($personInfo["organisation_id"] !=30):
                $today_date = date("Y-m-d");
                $where = " (organisation_courses.organisation_id ='".$personInfo["organisation_id"]."' AND organisation_courses.course_id = '".$courseId."' AND organisation_courses.status= 1) OR (organisation_courses.organisation_id ='".$personInfo["organisation_id"]."' AND organisation_courses.course_id = '".$courseId."' AND organisation_courses.status= 2 AND organisation_courses.start_date <='". $today_date."' AND organisation_courses.end_date >='".$today_date."' )";
                $organisation_courses = $courseModel->select("course.id")
                    ->join("organisation_courses", "organisation_courses.course_id = course.id","right")
                    ->where(array("course.status"=>1, "course.deleted"=>0))
                    ->where($where)
                    ->first();
                if (!$organisation_courses):
                    helper('url');
                    return redirect()->to(site_url("dashboard/course-not-access"));
                    die("Eğitim içeriği erişimize açık değildir1.");
                endif;
            endif;

        endif;



        /*
         * Active Content
         */
        $courseContentModel = new \App\Models\CourseContent();
        $activeContent = $courseContentModel->select("id as contentId, course_type_id as contentTypeId, title as contentTitle")
            ->where(array("status"=>1, "deleted"=>0, "course_id"=> $courseId, "id"=> $courseContentId ))
            ->first();
        if (!$activeContent):die();endif;

        $activeContentDetail = [];
        $quizSessionStart = [];

        $quizQuestionTotal = [
            'SumCount' => ""
        ];
        $quizQuestionScoreTotal = [
            'sumScore' => ""
        ];

        if($activeContent["contentTypeId"] == 2):
            /*
             * Video
             */
            $courseContentVideo = new \App\Models\CourseContentVideo();
            $activeContentDetail =  $courseContentVideo->where(array('course_content_id'=> $courseContentId))->first();


        elseif ($activeContent["contentTypeId"] == 3):
            /*
             * Exam
             */
            $courseContentQuiz = new \App\Models\CourseContentQuiz();
            $activeContentDetail =  $courseContentQuiz->where(array('course_content_id'=> $courseContentId))->first();

            $quizQuestionModel = new \App\Models\CourseContentQuizQuestion();
            $quizQuestionScoreTotal =  $quizQuestionModel->select('SUM(score) as sumScore')->where(array('cc_quiz_id' => $activeContentDetail["id"], 'deleted' => 0))->first();

            $quizSessionStartModel = new \App\Models\AuizSessionStart();
            $quizSessionStart = $quizSessionStartModel
                ->where(array( "deleted"=>0, "quiz_id"=> $activeContentDetail["id"], "person_id"=> session()->get('loggedPerson') ))->findAll();

            $quizQuestionTotal =  $quizQuestionModel->select('Count(id) as SumCount')->where( array('cc_quiz_id' => $activeContentDetail["id"], 'deleted'  => 0) )
                ->first();

        endif;
        /*
         * Course Content
         */
        $content = $courseContentModel->select("id as contentId, course_type_id as contentTypeId, title as contentTitle")
            ->orderBy("content_order", "DESC")
            ->orderBy("id", "ASC")
            ->where(array("status"=>1, "deleted"=>0, "course_id"=> $courseId))
            ->where('course_type_id !=','1')
            ->findAll();

        /*
        * Enroll Control
        */
        $courseEnrollModel = new \App\Models\CourseEnroll();
        $enroll = $courseEnrollModel->select("id")
            ->where(array("person_id"=>$personInfo["id"], "course_id"=>$courseId, 'dropout_status' => 0))->first();

        if (empty($enroll)){
            $values = [
                "person_id" => $personInfo["id"],
                "course_id" => $courseId,
                "last_access_date" => date("Y-m-d H:i:s"),
                "created_at" => date("Y-m-d H:i:s")
            ];
            $courseEnrollModel->insert($values);
            /** LOGS **/
            $insert_id = $courseEnrollModel->getInsertID();
            $this->saveLog("course_enroll",$insert_id,"Create");
        }else{
            $values = [
                "last_access_date" => date("Y-m-d H:i:s"),
            ];
            $courseEnrollModel->update($enroll["id"], $values);
            /** LOGS **/
            $this->saveLog("course_enroll",$enroll["id"],"Update");
        }

        $courseCompletedModel = new \App\Models\CourseCompleted();
        //$completed =  $courseCompletedModel->where(array('person_id'=> $personInfo["id"], 'course_id' => $courseId))->findAll();

        $activeContentCompleted = $courseCompletedModel->select("video_resume_time, page_time_spent, completed")->where(array('person_id'=> $personInfo["id"], 'course_id' => $courseId, 'content_id'=> $courseContentId))->first();

        $data = [
            'pageTitle' => $course["courseTitle"],
            'personInfo' => $personInfo,
            'nav' => false,
            'course' => $course,
            'activeContent' => $activeContent,
            'activeContentDetail' => $activeContentDetail,
            'quizSessionStart' => $quizSessionStart,
            'activeContentCompleted' => $activeContentCompleted,
            'content' => $content,
            'courseContentId' => $courseContentId,
            'quizQuestionTotal' => $quizQuestionTotal["SumCount"],
            'quizQuestionScoreTotal' => $quizQuestionScoreTotal['sumScore'],
        ];
        return view('frontend/dashboard/course_detail', $data);
    }


    public function courseCompleted(){
        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        $courseId = $this->request->getPost('courseId');
        $contentId = $this->request->getPost('contentId');

        $getCurrentTime = $this->request->getPost('getCurrentTime');
        $content_type   = $this->request->getPost('courseType');
        $videoPercent   = $this->request->getPost('videoPercent');
        $pageTime       = $this->request->getPost('pageTime');
        $videoDuration  = $this->request->getPost('videoDuration');

        //if ($pageTime >= ($videoDuration-15)):
        /*
        if ($videoPercent > 0.97):
            $completed = 1;
        else:
            $completed = 0;
        endif;
        */
        $newVideoDuration = (int)$videoDuration - 15;

        if (  (int)$getCurrentTime > $newVideoDuration ):
            $completed = 1;
        else:
            $completed = 0;
        endif;


        $courseCompletedModel = new \App\Models\CourseCompleted();
        $status_check =  $courseCompletedModel->where(array('person_id'=> $personInfo["id"], 'course_id' => $courseId, 'content_id'=> $contentId))->first();
        if (empty($status_check)){
            $values = [
                "person_id" => $personInfo["id"],
                "course_id" => $courseId,
                "content_id" => $contentId,
                "content_type" => $content_type,
                "page_time_spent" => $pageTime,
                "video_duration" => $videoDuration,
                "video_resume_time" => $getCurrentTime,
                "video_percent" => $videoPercent,
                "completed" => $completed,
                "last_change_date" => date("Y-m-d H:i:s"),
                "created_at" => date("Y-m-d H:i:s")
            ];
            $courseCompletedModel->insert($values);
            /** LOGS **/
            $insert_id = $courseCompletedModel->getInsertID();
            $this->saveLog("course_completed",$insert_id,"Create");
        }else{
            if($status_check["completed"]){
                $completed = 1;
            }
            $values = [
                "video_duration" => $videoDuration,
                "page_time_spent" => $pageTime,
                "video_resume_time" => $getCurrentTime,
                "video_percent" => $videoPercent,
                "completed" => $completed,
                "last_change_date" => date("Y-m-d H:i:s")
            ];
            $courseCompletedModel->update($status_check["id"], $values);
            /** LOGS **/
            $this->saveLog("course_completed",$status_check["id"],"Update");
        }
        
        echo json_encode(['code' => 1, 'completed' => $completed]  );

    }

    public function courseDuration(){

        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if(!$personInfo):
            exit();
        endif;

        $contentId = $this->request->getPost('contentId');
        $courseId = $this->request->getPost('courseId');
        $contentType = $this->request->getPost('contentType');

        /*
        $courseContentModel = new \App\Models\courseContent();
        $courseContent =  $courseContent->where(array('id'=> $contentId))->first();
        */

        if($contentType==2){
            $courseContentVideo = new \App\Models\CourseContentVideo();
            $duration =  $courseContentVideo->Select("duration, cover_img")->where(array('course_content_id'=> $contentId))->first();

            $courseCompleted = new \App\Models\CourseCompleted();
            $completed =  $courseCompleted->Select("page_time_spent as pageTime, video_duration as videoDuration, video_percent as resume_time, completed as completed_status")
                ->where(array('person_id'=>$personInfo["id"], 'course_id' => $courseId, 'content_id'=> $contentId))->first();
            $results = $duration;
        }else if($contentType==3){
            //TODO Error
            //$courseContentQuizModel = new \App\Models\courseContentQuiz();
            //$results = $courseContentQuizModel->where(array('course_content_id'=> $contentId))->first();

            $courseContentQuiz = new \App\Models\CourseContentQuiz();
            $ccq = $courseContentQuiz->where(array('course_content_id'=> $contentId))->first();
    
            $quizSessionStartModel = new \App\Models\QuizSessionStart();
            $quizSessionStart = $quizSessionStartModel
                ->Select("completed as completed_status")
                ->orderBy("id", "desc")
                ->where(array( "deleted"=>0, "quiz_id"=> $ccq["id"], "person_id"=> session()->get('loggedPerson') ))->first();

            $results = $quizSessionStart; //TODO filter some parameters
            $completed = $quizSessionStart;
            $completed = [
                'pageTime' => $quizSessionStart["completed_status"],
                'videoDuration' => '1',
                'resume_time' => '1',
                'completed_status' => $quizSessionStart["completed_status"]
            ];
        }
        if ($results){
            echo json_encode(['code' => 1, 'msg' => '', 'result' => $results, 'completed' => $completed]  );
        }else{
            echo json_encode(['code' => 0, 'msg' => 'No results found', 'result' => null]);
        }

    }





    function courseContinue($course_id){

        $loggedPersonId =  session()->get('loggedPerson');
        $authModel = new \App\Models\User();
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        if (!$personInfo):
            exit();
        endif;

        /*
         * Course ID
         */
        $courseModel = new \App\Models\Course();
        $Continue_courses = $courseModel->select("course.id, course.title as course_title, course.slug as course_slug, course.details, course.img_path")
            ->select("instructor.name, instructor.surname, instructor.img as instructor_img")
            ->join("course_enroll", "course_enroll.course_id = course.id")
            ->join("instructor", "instructor.id = course.instructor_id","left")
            ->where(array("course.status" => 1, "course.deleted" => 0))
            ->where(array("course_enroll.person_id" => $personInfo["id"],"course_enroll.dropout_status" => 0, "course_enroll.course_id" => $course_id))
            ->first();

        /*
         * Content -> Eğer devam ediyorsa son contenti getir
         */
        $courseCompletedModel = new \App\Models\CourseCompleted();
        $Continue_completed = $courseCompletedModel->select("content_id")
            ->where(array("person_id" => $personInfo["id"], "course_id" => $Continue_courses["id"]))
            ->orderBy("last_change_date", "DESC")
            ->first();

        if (!$Continue_completed){
            $courseContentModel = new \App\Models\CourseContent();
            $Continue_completed = $courseContentModel->select("id as content_id")
                ->where(array("course_id" => $Continue_courses["id"], "deleted" => 0, "status" =>1))
                ->orderBy("content_order", "ASC")
                ->orderBy("id", "ASC")
                ->first();
        }

        /*
         * like
         */
        $like_status = Null;
        $courseRateModel = new \App\Models\CourseRate();
        $course_rate_check = $courseRateModel->select("like_status")
            ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id))
            ->first();

        if ($course_rate_check):
            $like_status = $course_rate_check["like_status"];
        endif;


        if ($Continue_courses){
            echo json_encode(['code' => 1, 'msg' => '', 'result' => $Continue_courses, 'content_id' => $Continue_completed["content_id"], "like_status" => $like_status ] );
        }else{
            echo json_encode(['code' => 0, 'msg' => 'No results found', 'result' => null]);
        }

    }


    function courseContinueLinkControl(){

        $loggedPersonId =  session()->get('loggedPerson');
        $authModel = new \App\Models\User();
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        if (!$personInfo):
            exit();
        endif;

        $course_id = $this->request->getPost('course');
        $content_id = $this->request->getPost('content');
        $indis = array();

        /*
         * Course Content
         */
        $courseContentModel = new \App\Models\CourseContent();
        $content = $courseContentModel->select("id")
            ->orderBy("content_order", "DESC")
            ->orderBy("id", "ASC")
            ->where(array("status"=>1, "deleted"=>0, "course_id"=> $course_id))
            ->where('course_type_id !=','1')
            ->find();

        foreach ($content as $itemContent){
            foreach ($itemContent as $item){
                $indis[]= $item;
            }
        }

        $indis_number = array_search($content_id, $indis);
        if ($indis_number > 0){
            $control_content_id = $indis[$indis_number-1];
        }else{
            $control_content_id = $indis[0];
        }

        $courseCompleted = new \App\Models\CourseCompleted();
        /*
         * Course tamamlama durumu
         */
        $courseCompletedStatus = 1;
        foreach ($content as $itemContent):
            $userCompleted =  $courseCompleted->Select("completed")
                ->where(array('person_id'=>$personInfo["id"], 'course_id' => $course_id, 'content_id'=> $itemContent["id"]))->first();
            if($userCompleted["completed"] == Null):
                $courseCompletedStatus = 0;
            elseif ($userCompleted["completed"] == 0):
                $courseCompletedStatus = 0;
            endif;
        endforeach;

        $completed =  $courseCompleted->Select("completed")
            ->where(array('person_id'=>$personInfo["id"], 'course_id' => $course_id, 'content_id'=> $control_content_id))->first();




        if ($completed):
            if ($completed["completed"] == 1):
                echo json_encode(['code' => 1, 'result' => $content_id]);
            else:

                if ($courseCompletedStatus==1):
                    $data_u = [
                        "course_completed" => 1,
                        "course_completed_date" => date("Y-m-d H:i:s")
                    ];
                    $db      = \Config\Database::connect();
                    $builder = $db->table('course_enroll');
                    $query = $builder->where(array('person_id'=>$personInfo["id"], 'course_id' => $course_id))->update($data_u);
                endif;

                echo json_encode(['code' => 0, 'result' => $content_id]);
            endif;
        else:
            echo json_encode(['code' => 0, 'result' => $content_id]);
        endif;



    }





    function courseContinueLinkNextEpisodes(){

        $loggedPersonId =  session()->get('loggedPerson');
        $authModel = new \App\Models\User();
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        if (!$personInfo):
            exit();
        endif;


        $course_id = $this->request->getPost('course');
        $content_id = $this->request->getPost('content');


        /*
        $course_id = 8;
        $content_id = 64;
        */

        $indis = array();
        /*
         * Course Content
         */
        $courseContentModel = new \App\Models\CourseContent();
        $content = $courseContentModel->select("id")
            ->orderBy("content_order", "DESC")
            ->orderBy("id", "ASC")
            ->where(array("status"=>1, "deleted"=>0, "course_id"=> $course_id))
            ->where('course_type_id !=','1')
            ->find();

        foreach ($content as $itemContent){
            foreach ($itemContent as $item){
                $indis[]= $item;
            }
        }

        $indis_number = array_search($content_id, $indis);

        if ($indis_number > 0):
            if ($content_id == end($indis)):
                $control_content_id = "End";
            else:
                $control_content_id = $indis[$indis_number+1];
            endif;


        elseif($indis_number == 0):
            $control_content_id = $indis[1];
        else:
            $control_content_id = $indis[0];
        endif;

        if (!empty($control_content_id)):
            if ($control_content_id == "End"):
                echo json_encode(['code' => 2, 'result' => "End"]);
            else:
                echo json_encode(['code' => 1, 'result' => $control_content_id]);
            endif;


        else:
            echo json_encode(['code' => 0, 'result' => $control_content_id]);
        endif;

    }

    function courseRate(){

        $loggedPersonId =  session()->get('loggedPerson');
        $authModel = new \App\Models\User();
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if (!$personInfo):
            exit();
        endif;


        $status = $this->request->getPost('status');

        if ($status == "like"):
            $course_id = $this->request->getPost('course');
            $rate = $this->request->getPost('rate');
            if (!is_numeric($course_id) || !is_numeric($rate)):
                echo json_encode(['code' => 0, 'msg' => 'No results found', 'result' => null]);
            endif;
            /*
             * Course ID
             */
            $courseEnrollModel = new \App\Models\CourseEnroll();
            $course_enroll_check = $courseEnrollModel->select("id")
                ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id, 'dropout_status' => 0))
                ->first();


            if ($course_enroll_check){
                $courseRateModel = new \App\Models\CourseRate();
                $course_rate_check = $courseRateModel->select("id")
                    ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id))
                    ->first();

                if ($course_rate_check){
                    $values = [
                        "like_status" => $rate,
                        "last_change_date" => date("Y-m-d H:i:s")
                    ];
                    $courseRateModel->update($course_rate_check["id"], $values);
                    /** LOGS **/
                    $this->saveLog("course_rate",$course_rate_check["id"],"Update");
                    echo json_encode(['code' => 1, 'msg' => "success_u"]);
                }else{
                    $values = [
                        "person_id" => $personInfo["id"],
                        "course_id" => $course_id,
                        "like_status" => $rate,
                        "create_date" => date("Y-m-d H:i:s")
                    ];
                    $courseRateModel->insert($values);
                    /** LOGS **/
                    $insert_id = $courseRateModel->getInsertID();
                    $this->saveLog("course_rate",$insert_id,"Create");
                    echo json_encode(['code' => 1, 'msg' => "success_i"]);
                }

            }else{
                echo json_encode(['code' => 0, 'msg' => 'No results found']);
            }

        elseif ($status == "feedback"):
           /*
            * FEEDBACK
            */

            $validation = \config\Services::validation();
            //Lets starts valition
            $this->validate([
                'feedback_course_id' => 'required|is_not_unique[course.id]',
                'feedback_instructor_id' => 'required|is_not_unique[instructor.id]',
                'instructor_comments' => 'max_length[512]',
                'course_comments' => 'max_length[512]',

            ]);

            if ($validation->run() == FALSE){
                $errors = $validation->getErrors();
                echo json_encode(['code' => 0, 'error' => $errors]);
            }else{

                $course_id = $this->request->getPost('feedback_course_id');
                $instructor_id = $this->request->getPost('feedback_instructor_id');

                $instructor_rate = $this->request->getPost('rate_instructor_number');
                $instructor_comments = $this->request->getPost('instructor_comments');
                $course_rate = $this->request->getPost('rate_course_number');
                $course_comments = $this->request->getPost('course_comments');

                /*
                 * Course ID
                 */
                $courseEnrollModel = new \App\Models\CourseEnroll();
                $course_enroll_check = $courseEnrollModel->select("id")
                    ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id, 'dropout_status' => 0))
                    ->first();

                if ($course_enroll_check){
                    $courseRateModel = new \App\Models\CourseRate();
                    $course_rate_check = $courseRateModel->select("id")
                        ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id))
                        ->first();

                    if ($course_rate_check){
                        $values = [
                            "instructor_id" => $instructor_id,
                            "course_rate" => $course_rate,
                            "course_comments" => $course_comments,
                            "instructor_rate" => $instructor_rate,
                            "instructor_comments" => $instructor_comments,
                            "last_change_date" => date("Y-m-d H:i:s")
                        ];
                        $courseRateModel->update($course_rate_check["id"], $values);
                        /** LOGS **/
                        $this->saveLog("course_rate",$course_rate_check["id"],"Update");
                        echo json_encode(['code' => 1, 'msg' => "Section successfully"]);
                    }else{
                        $values = [
                            "person_id" => $personInfo["id"],
                            "course_id" => $course_id,
                            "instructor_id" => $instructor_id,
                            "course_rate" => $course_rate,
                            "course_comments" => $course_comments,
                            "instructor_rate" => $instructor_rate,
                            "instructor_comments" => $instructor_comments,
                            "rate_scala" => 5,
                            "create_date" => date("Y-m-d H:i:s")
                        ];
                        $courseRateModel->insert($values);
                        /** LOGS **/
                        $insert_id = $courseRateModel->getInsertID();
                        $this->saveLog("course_rate",$insert_id,"Create");
                        echo json_encode(['code' => 1, 'msg' => "Section successfully"]);
                    }

                }else{
                    echo json_encode(['code' => 0, 'msg' => 'Something went wrong!']);
                }


            }

        endif;

    }


    public function courseRateList(){

        $loggedPersonId =  session()->get('loggedPerson');
        $authModel = new \App\Models\User();
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if (!$personInfo):
            exit();
        endif;

        /*
         * FEEDBACK
         */
        $status = $this->request->getPost('status');
        if ($status == "feedback"):
            $course_id = $this->request->getPost('course');
            $instructor_id = $this->request->getPost('instructor');

            if (!is_numeric($course_id) || !is_numeric($instructor_id)):
                echo json_encode(['code' => 0, 'msg' => 'No results found', 'result' => null]);
            endif;

            $courseRateModel = new \App\Models\CourseRate();
            $course_rate_check = $courseRateModel->select("course_rate, course_comments, instructor_rate, instructor_comments")
                ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id))
                ->first();

            if ($course_rate_check){
                echo json_encode(['code' => 1, 'msg' => '', 'result' => $course_rate_check] );
            }else{
                echo json_encode(['code' => 0, 'msg' => 'No results found', 'result' => null]);
            }
        endif;


    }



/*
 * COURSE PROGRESS
 */

    public function courseProgress($course_id){

        $loggedPersonId =  session()->get('loggedPerson');
        $authModel = new \App\Models\User();
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if (!$personInfo):
            exit();
        endif;

        /*
         * PROGRESS
         */
        if (!is_numeric($course_id)):
            die();
        endif;

        $courseEnrollModel = new \App\Models\CourseEnroll();
        $course_enroll_check = $courseEnrollModel->select("id")
            ->where(array("person_id" => $personInfo["id"], "course_id" => $course_id, 'dropout_status' => 0))
            ->first();

        if ($course_enroll_check):
            $courseContent = new \App\Models\CourseContent();
            $course_content_total = $courseContent->select("Count(id) as count_content")
                ->where(array("course_type_id" => 2, "course_id" => $course_id, "deleted" => 0, "status" => 1))
                ->first();

            $courseCompletedModel = new \App\Models\CourseCompleted();
            $course_completed_total = $courseCompletedModel->select("Count(id) as count_completed")
                ->where(array("content_type" => 2, "person_id" => $personInfo["id"], "course_id" => $course_id, "completed" => 1))
                ->first();

            $percent = ( $course_completed_total["count_completed"]*100 )/$course_content_total["count_content"];

            echo json_encode(['code' => 1, 'msg' => 'Success', 'course_content_total' => $course_content_total["count_content"], "course_person_total" => $course_completed_total["count_completed"], "percent" => (int)$percent] );
        else:
            echo json_encode(['code' => 0, 'msg' => 'fail', 'result' => null]);
        endif;

    }

















    /*
     * COURSE NOT ACCESS
     */
    public function courseNotAccess()
    {

        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if (!$personInfo):
            exit();
        endif;

        /*
         * Organisation
         */
        $organisationModel = new \App\Models\Organisation();
        $organisationInfo =  $organisationModel
            ->select("title, img_path")
            ->where('id', $personInfo["organisation_id"])->first();
        /*
         * Course Enroll Number
         */
        $courseEnrollModel = new \App\Models\CourseEnroll();
        $course_list = $courseEnrollModel->select('id, course_id')->where( array('person_id' => $personInfo["id"], 'dropout_status' => 0) )->findAll();

        $courseContentModel = new \App\Models\CourseContent();
        $courseCompleted = new \App\Models\CourseCompleted();


        /*
         * Course tamamlama durumu
         */
        foreach ($course_list as $item_course_list):
            $content = $courseContentModel->select("id")
                ->where(array("status"=>1, "deleted"=>0, "course_id"=> $item_course_list["course_id"]))
                ->where('course_type_id !=','1')
                ->find();

            $courseCompletedStatus = 1;
            foreach ($content as $itemContent):
                $userCompleted =  $courseCompleted->Select("completed")
                    ->where(array('person_id'=>$personInfo["id"], 'course_id' => $item_course_list["course_id"], 'content_id'=> $itemContent["id"]))->first();
                if($userCompleted["completed"] == Null):
                    $courseCompletedStatus = 0;
                elseif ($userCompleted["completed"] == 0):
                    $courseCompletedStatus = 0;
                endif;
            endforeach;

            if ($courseCompletedStatus==1):
                $data_u = [
                    "course_completed" => 1,
                    "course_completed_date" => date("Y-m-d H:i:s")
                ];
                $db      = \Config\Database::connect();
                $builder = $db->table('course_enroll');
                $query = $builder->where(array('person_id'=>$personInfo["id"], 'course_id' => $item_course_list["course_id"]))->update($data_u);
            endif;
        endforeach;

        $course_completed = $courseEnrollModel->select('Count(id) as SumCount')->where( array('person_id' => $personInfo["id"], 'course_completed'  => 1, 'dropout_status' => 0) )->first();
        $course_continue = $courseEnrollModel->select('Count(id) as SumCount')->where( array('person_id' => $personInfo["id"], 'course_completed'  => 0, 'dropout_status' => 0) )->first();


        $data = [
            'pageTitle' => "Genel Bakış",
            'personInfo' => $personInfo,
            'organisationInfo' => $organisationInfo,
            'course_continue' => $course_continue["SumCount"],
            'course_completed' => $course_completed["SumCount"],
        ];

        return view('frontend/dashboard/course_not_access', $data);
    }





    private function exfullAccessCertificateControl(){

        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if (!$personInfo):
            exit();
        endif;

        $today_date = date("Y-m-d");
        $purchaseMainModel = new \App\Models\purchaseMain();
        $purchaseCheck = $purchaseMainModel->select("id, conversation_id, purchase_end_date")
            ->where( array("status_check" => "Tamamlandı", "payment_status"=>"Success", "purchase_type" => "FullAccess", "purchase_end_date >=" => $today_date, "person_id" => $personInfo["id"]) )
            ->first();
        $fullAccessEnrollModel = new \App\Models\fullAccessEnroll();
        $fullAccessEnrollCheck = $fullAccessEnrollModel->select("id, person_id, end_date, purchase_main_id, enrolled_by")
            ->where(array("deleted" => 0, "status" => 1, "person_id" => $personInfo["id"])) 
            ->where("(end_date >= CURRENT_TIMESTAMP OR end_date IS NULL)")
            ->first();

        if (!empty($fullAccessEnrollCheck)):
            $conversation_id = $purchaseCheck["conversation_id"];
            $purchase_end_date = $purchaseCheck["purchase_end_date"];

            $coursePackagePersonModel = new \App\Models\CoursePackagePerson();
            $restOfTheCPackageList = $coursePackagePersonModel->db()->query("SELECT id FROM x_course_package_detail WHERE status = 1 AND deleted = 0 AND buy_full_access = 1 AND id NOT IN (SELECT package_id as id FROM x_course_package_person WHERE person_id = ".$personInfo["id"].")")->getResult();

            foreach ($restOfTheCPackageList as $restOfTheCPackage):
                $data_insert = [
                    "purchase_type" => "Certificate",
                    "package_id" => $restOfTheCPackage->id,
                    "person_id" => $personInfo["id"],
                    "conversation_id" => $conversation_id,
                    "status" => 1,
                    "start_date" => date("Y-m-d"),
                    "end_date" => date("Y-m-d", strtotime($purchase_end_date)),
                    "created_by" => $personInfo["first_name"]." ".$personInfo["last_name"],
                    "created_at" => date("Y-m-d H:i:s"),
                    "fullAccess_enroll_id" => $fullAccessEnrollCheck["id"],
                    "purchase_main_id" => $purchaseCheck["id"]
                ];
                $coursePackagePersonModel->insert($data_insert);
            endforeach;

            $courseEnrollModel = new \App\Models\CourseEnroll();

            // Courses not included in the package
            //$restOfTheAllCourseList = $courseEnrollModel->db()->query("SELECT id FROM x_course WHERE status = 1 AND deleted = 0 AND course_price IS NOT NULL AND buy_full_access = 1 AND id IN (SELECT course_id as id FROM x_course_package)")->getResult();
            
            // Courses do not have any enroll for this person
            $restOfTheCourseList = $courseEnrollModel->db()->query("SELECT id FROM x_course WHERE status = 1 AND deleted = 0 AND course_price IS NOT NULL AND buy_full_access = 1 AND id NOT IN (SELECT course_id as id FROM x_course_enroll WHERE person_id = ".$personInfo["id"]." AND dropout_status = 0)")->getResult();
            
            foreach ($restOfTheCourseList as $restOfTheCourse):
                $data_insert = [
                    "person_id" => $personInfo["id"],
                    "course_id" => $restOfTheCourse->id,
                    "enroll_status"  => 4,
                    "created_at" => date("Y-m-d H:i:s"),
                    "enroll_date" => date("Y-m-d H:i:s"),
                    "enroll_by" => $personInfo["first_name"]." ".$personInfo["last_name"],
                    "fullAccess_enroll_id" => $fullAccessEnrollCheck["id"],
                    "purchase_main_id" => $purchaseCheck["id"]
                ];
                $courseEnrollModel->insert($data_insert);
            endforeach;
        endif;
    }

    private function fullAccessCertificateControl(){

        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();
        if (!$personInfo):
            exit();
        endif;

        $coursePackageDetailModel = new \App\Models\CoursePackageDetail();
        $coursePackagePersonModel = new \App\Models\coursePackagePerson();
        $courseEnrollModel = new \App\Models\CourseEnroll();
        $coursePackageModel = new \App\Models\CoursePackage();

        $today_date = date("Y-m-d");
        $purchaseMainModel = new \App\Models\PurchaseMain();
        $purchaseCheck = $purchaseMainModel->select("id, conversation_id, purchase_end_date")
            ->where( array("status_check" => "Tamamlandı", "payment_status"=>"Success", "purchase_type" => "FullAccess", "purchase_end_date >=" => $today_date, "person_id" => $personInfo["id"]) )
            ->first();
        $fullAccessEnrollModel = new \App\Models\FullAccessEnroll();
        $fullAccessEnrollCheck = $fullAccessEnrollModel->select("id, person_id, end_date, purchase_main_id, enrolled_by")
            ->where(array("deleted" => 0, "status" => 1, "person_id" => $personInfo["id"])) 
            ->where("(end_date >= CURRENT_TIMESTAMP OR end_date IS NULL)")
            ->first();


        if (!empty($fullAccessEnrollCheck)):

                $fullAccess_enroll_id = $fullAccessEnrollCheck["id"];
                $conversation_id = isset($purchaseCheck) ? $purchaseCheck["conversation_id"] : "";
                $purchase_end_date = isset($purchaseCheck) ? $purchaseCheck["purchase_end_date"] : "";

                $coursePackageList = $coursePackageDetailModel->select("id")
                    ->where(array("status" => 1, "deleted" => 0, "buy_full_access" => 1))
                    ->findAll();

                foreach ($coursePackageList as $itemCoursePackageList):
                    $package_id = $itemCoursePackageList["id"];
                    $personPackageCheck = $coursePackagePersonModel->select("id, package_id")
                        ->where(array("purchase_type" => "Certificate", "package_id" => $package_id, "person_id" => $personInfo["id"], "fullAccess_enroll_id" => $fullAccess_enroll_id))
                        ->first();

                    if (empty($personPackageCheck) ):

                        $insert_data = array(
                            "purchase_type" => "Certificate",
                            "package_id" => $package_id,
                            "person_id" => $personInfo["id"],
                            "conversation_id" => $conversation_id,
                            "status" => 1,
                            "start_date" => date("Y-m-d"),
                            "end_date" => date("Y-m-d", strtotime($fullAccessEnrollCheck["end_date"])),
                            "created_by" => $personInfo["first_name"]." ".$personInfo["last_name"],
                            "created_at" => date("Y-m-d H:i:s"),
                            "fullAccess_enroll_id" => $fullAccessEnrollCheck["id"],
                            "purchase_main_id" => $fullAccessEnrollCheck["purchase_main_id"]
                        );
                        $coursePackagePersonModel->insert($insert_data);
                        $package_id = $coursePackagePersonModel->getInsertID();
                    endif;

                    $packageCourseList = $coursePackageModel
                        ->select("course_id")
                        ->where(array("package_id" => $package_id, "status" => 1, "deleted" => 0))->findAll();

                    foreach ($packageCourseList as $itemPackageCourseList):

                        $course_id = $itemPackageCourseList["course_id"];
                        $enroll = $courseEnrollModel->select("course_completed")
                            ->where(array("person_id"=> $personInfo["id"], "course_id"=> $course_id))
                            ->first();

                        if (!$enroll):
                            $data_insert = [
                                'person_id' => $personInfo["id"],
                                'course_id' => $course_id,
                                'enroll_status'  => 4,
                                'enroll_date' => date("Y-m-d H:i:s"),
                                'enroll_by' => $personInfo["first_name"]." ".$personInfo["last_name"],
                                "fullAccess_enroll_id" => $fullAccessEnrollCheck["id"],
                                "cPackage_enroll_id" => $package_id,
                                "purchase_main_id" => $fullAccessEnrollCheck["purchase_main_id"]
                            ];
                            $courseEnrollModel->insert($data_insert);
                        endif;

                    endforeach;


                endforeach;

            //$this->restOfTheCoursesControl($personInfo["id"]);

            $courseEnrollModel = new \App\Models\CourseEnroll();
            $restOfTheCourseList = $courseEnrollModel->db()->query("SELECT id FROM x_course WHERE status = 1 AND deleted = 0 AND course_price IS NOT NULL AND buy_full_access = 1 AND id NOT IN (SELECT course_id as id FROM x_course_enroll WHERE person_id = ".$personInfo["id"]." AND dropout_status = 0)")->getResult();
            //print_r($restOfTheCourses);
        
            foreach ($restOfTheCourseList as $restOfTheCourse):
                $data_insert = [
                    'person_id' => $personInfo["id"],
                    'course_id' => $restOfTheCourse->id,
                    'enroll_status'  => 4,
                    'created_at' => date("Y-m-d H:i:s"),
                    'enroll_date' => date("Y-m-d H:i:s"),
                    'enroll_by' => "Auto Enrolled",
                    "fullAccess_enroll_id" => $fullAccessEnrollCheck["id"],
                    "purchase_main_id" => $fullAccessEnrollCheck["purchase_main_id"]
                ];
                $courseEnrollModel->insert($data_insert);
            endforeach;
        endif;

    }


}
