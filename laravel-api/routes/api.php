<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AdministrationController;
use App\Http\Controllers\Api\PersonManagementController;
use App\Http\Controllers\Api\OrganisationManagementController;
use App\Http\Controllers\Api\InstructorManagementController;
use App\Http\Controllers\Api\CourseCategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseContentController;
use App\Http\Controllers\Api\CourseContentSectionController;
use App\Http\Controllers\Api\CourseContentVideoController;
use App\Http\Controllers\Api\CourseContentTextController;
use App\Http\Controllers\Api\CourseContentQuizController;
use App\Http\Controllers\Api\PackageManagementController;
use App\Http\Controllers\Api\OrganisationReportController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\MyCoursesController;
use App\Http\Controllers\Api\DashboardQuizController;
use App\Http\Controllers\Api\HomeCertificateProgramsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['namespace' => 'Api', 'as' => 'api.'], function () {

    Route::post('login', 'LoginController@login')->name('login');

    Route::post('register', 'RegisterController@register')->name('register');


    // Authentication Routes
    Route::group(['middleware' => ['auth:api']], function () {

        Route::get('email/verify/{hash}', 'VerificationController@verify')->name('verification.verify');
        Route::get('email/resend', 'VerificationController@resend')->name('verification.resend');
        Route::get('user', 'AuthenticationController@user')->name('user');
        Route::post('logout', 'LoginController@logout')->name('logout');
    });

    //Administration Routes
    Route::get('administration/menu', 'Administration\AdministrationController@menu')->name('administration.menu');
    Route::get('administration/personmanagement', 'Administration\AdministrationController@personManagement')->name('administration.personmanagement');
    Route::get('administration/organisationmanagement', 'Administration\AdministrationController@organisationManagement')->name('administration.organisationmanagement');
    Route::get('administration/instructormanagement', 'Administration\AdministrationController@instructormanagement')->name('administration.instructormanagement');
    Route::get('administration/packagemanagement', 'Administration\AdministrationController@packagemanagement')->name('administration.packagemanagement');
    Route::get('administration/classmanagement', 'Administration\AdministrationController@classmanagement')->name('administration.classmanagement');
    Route::get('administration/faqmanagement', 'Administration\AdministrationController@faqmanagement')->name('administration.faqmanagement');
    Route::get('administration/price/calculator', 'Administration\AdministrationController@priceManagement')->name('administration.priceManagement');


    Route::get('administration/person/add', 'Administration\AdministrationController@personAdd')->name('administration.personAdd');
    Route::post('administration/person/save', 'Administration\AdministrationController@personSave')->name('administration.personSave');
    Route::get('administration/person/update/(:num)', 'Administration\AdministrationController@personUpdate/$1')->name('administration.personUpdate/$1');
    Route::post('administration/person/updateSave/(:num)', 'Administration\AdministrationController@updateSave/$1')->name('administration.updateSave/$1');
    Route::get('administration/person/csv', 'Administration\AdministrationController@personImportCsv')->name('administration.personImportCsv');
    Route::post('administration/person/csvSave', 'Administration\AdministrationController@personCSVSave')->name('administration.personCSVSave');
    Route::get('administration/person/getAllPerson', 'Administration\AdministrationController@getAllPerson')->name('administration.getAllPerson');
    Route::post('administration/person/delete', 'Administration\AdministrationController@deletePerson')->name('administration.deletePerson');
    Route::post('administration/person/changePasswordPersonInfo', 'Administration\AdministrationController@changePasswordPersonInfo')->name('administration.changePasswordPersonInfo');
    Route::post('administration/person/lastLoginPersonInfo', 'Administration\AdministrationController@lastLoginPersonInfo')->name('administration.lastLoginPersonInfo');

    Route::post('administration/person/changePasswordPersonUpdate', 'Administration\AdministrationController@changePasswordPersonUpdate')->name('administration.changePasswordPersonUpdate');

    Route::get('administration/organisation/add', 'Administration\AdministrationController@add')->name('OrganisationManagement.add');
    Route::post('administration/organisation/add', 'Administration\AdministrationController@save')->name('OrganisationManagement.save');
    Route::get('administration/organisation/update/(:num)', 'Administration\AdministrationController@Update/$1')->name('OrganisationManagement.Update/$1');
    Route::post('administration/organisation/updateSave/(:num)', 'Administration\AdministrationController@updateSave/$1')->name('OrganisationManagement::updateSave/$1');
    Route::get('administration/organisation/delete/(:num)', 'Administration\AdministrationController@delete/$1')->name('OrganisationManagement.delete/$1');
    Route::get('administration/organisation/coursemanagement/(:num)', 'Administration\AdministrationController@organisationCourseManagement/$1')->name('OrganisationManagement.organisationCourseManagement/$1');
    Route::post('administration/organisation/coursestatuslist/(:num)', 'Administration\AdministrationController@organisationCourseList/$1')->name('OrganisationManagement.organisationCourseList/$1');
    Route::post('administration/organisation/coursestatuschange/(:num)', 'Administration\AdministrationController@organisationCourseStatusChange/$1')->name('OrganisationManagement.organisationCourseStatusChange/$1');

    Route::post('administration/organisation/updatePackage/(:num)', 'Administration\AdministrationController@organisationPackageUpdate/$1')->name('administration.organisationPackageUpdate/$1');

    Route::get('administration/instructor/getAll', 'Administration\AdministrationController@getAll')->name('InstructorManagement.getAll');
    Route::get('administration/instructor/add', 'Administration\AdministrationController@add')->name('InstructorManagement.add');
    Route::post('administration/instructor/save', 'Administration\AdministrationController@save')->name('InstructorManagement.save');
    Route::post('administration/instructor/delete', 'Administration\AdministrationController@delete')->name('InstructorManagement.delete');
    Route::get('administration/instructor/edit/(:num)', 'Administration\AdministrationController@edit/$1')->name('InstructorManagement.edit/$1');
    Route::post('administration/instructor/update/(:num)', 'Administration\AdministrationController@update/$1')->name('InstructorManagement.update/$1');
    Route::post('administration/instructor/info', 'Administration\AdministrationController@info')->name('InstructorManagement.info');
    Route::post('administration/instructor/infoUpdate', 'Administration\AdministrationController@infoUpdate')->name('InstructorManagement.infoUpdate');

    Route::get('administration/course/category', 'Administration\AdministrationController@index')->name('CourseCategory.index');
    Route::get('administration/course/category/add', 'Administration\AdministrationController@add')->name('CourseCategory.add');
    Route::post('administration/course/category/delete', 'Administration\AdministrationController@delete')->name('CourseCategory.delete');
    Route::post('administration/course/category/save', 'Administration\AdministrationController@save')->name('CourseCategory.save');
    Route::get('administration/course/category/edit/(:num)', 'Administration\AdministrationController@edit/$1')->name('CourseCategory.edit/$1');
    Route::post('administration/course/category/update/(:num)', 'Administration\AdministrationController@update/$1')->name('CourseCategory.update/$1');

    Route::get('administration/course/add', 'Administration\AdministrationController@add')->name('Course.add');
    Route::post('administration/course/save', 'Administration\AdministrationController@save')->name('Course.save');
    Route::get('administration/course/getAll', 'Administration\AdministrationController@getAll')->name('Course.getAll');
    Route::get('administration/course/edit/(:num)', 'Administration\AdministrationController@edit/$1')->name('Course.edit/$1');
    Route::post('administration/course/update/(:num)', 'Administration\AdministrationController@update/$1')->name('Course.update/$1');
    Route::post('administration/course/delete', 'Administration\AdministrationController@delete')->name('Course.delete');

    Route::get('administration/course/content/manage/(:num)', 'Administration\AdministrationController@manage/$1')->name('CourseContent.manage/$1');
    Route::post('administration/course/content/delete', 'Administration\AdministrationController@courseContentDelete')->name('CourseContent.courseContentDelete');

    Route::post('administration/course/content/addSectionSave', 'Administration\AdministrationController@addSectionSave')->name('CourseContentSection.addSectionSave');
    Route::post('administration/course/content/sectionInfo', 'Administration\AdministrationController@sectionInfo')->name('CourseContentSection.sectionInfo');

    Route::post('administration/course/content/addVideoSave', 'Administration\AdministrationController@addVideoSave')->name('CourseContentVideo.addVideoSave');
    Route::post('administration/course/content/videoInfo', 'Administration\AdministrationController@videoInfo')->name('CourseContentVideo.videoInfo');

    Route::post('administration/course/content/addTextSave', 'Administration\AdministrationController@addTextSave')->name('CourseContentText.addTextSave');
    Route::post('administration/course/content/textInfo', 'Administration\AdministrationController@textInfo')->name('CourseContentText.textInfo');

    Route::post('administration/course/content/addQuizSave', 'Administration\AdministrationController@addQuizSave')->name('CourseContentQuiz.addQuizSave');
    Route::post('administration/course/content/quizInfo', 'Administration\AdministrationController@quizInfo')->name('CourseContentQuiz.quizInfo');
    Route::post('administration/course/content/quiz/detail/(:num)', 'Administration\AdministrationController@quizDetail')->name('CourseContentQuiz.quizDetail');
    Route::get('administration/course/content/quiz/view/(:num)/(:num)', 'Administration\AdministrationController@quizView/$1/$2')->name('CourseContentQuiz.quizView/$1/$2');
    Route::get('administration/course/content/quiz/questions/(:num)/(:num)', 'Administration\AdministrationController@quizQuestions/$1/$2')->name('CourseContentQuiz.quizQuestions/$1/$2');
    Route::post('administration/course/content/quiz/addQuestion', 'Administration\AdministrationController@addQuestion')->name('CourseContentQuiz.addQuestion');
    Route::post('administration/course/content/quiz/updateQuestion', 'Administration\AdministrationController@updateQuestion')->name('CourseContentQuiz.updateQuestion');

    Route::post('administration/course/content/quiz/question/delete', 'Administration\AdministrationController@questionDelete')->name('CourseContentQuiz.questionDelete');
    Route::post('administration/course/content/quiz/question/info', 'Administration\AdministrationController@questionInfo')->name('CourseContentQuiz.questionInfo');
    Route::get('administration/course/content/folder/(:num)/(:num)', 'Administration\AdministrationController@folderContent/$1/$2')->name('CourseContentQuiz.folderContent/$1/$2');


    // Dashboard Routes -- (SAME AS ABOVE BUT WITH DIFFERENT WRITING)
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/profile', [DashboardController::class, 'profile']);
    //Route::get('dashboard', 'DashboardController@index')->name('dashboard');
    //Route::get('dashboard/profile', 'DashboardController@profile')->name('dashboard.profile');


    Route::get('dashboard/course/continue/{num}', 'DashboardController@courseContinue/$1')->name('dashboard.courseContinue/$1');
    Route::post('dashboard/course/completed', [DashboardController::class, 'courseCompleted']);
    Route::post('dashboard/course/duration', [DashboardController::class, 'courseDuration']);
    Route::post('dashboard/course_rate/rate', [DashboardController::class, 'courseRate']);
    Route::post('dashboard/course_rate_list/rate', [DashboardController::class, 'courseRateList']);
    Route::post('dashboard/course_progress/progress/{num}', [DashboardController::class, 'courseProgress']);


    Route::get('dashboard/person/course/quiz/{any1}/{any2}/{any3}', [DashboardQuizController::class, 'quizSessionStart']);
    Route::post('dashboard/person/quiz/question/alternative', [DashboardQuizController::class, 'quizAlternative']);
    Route::post('dashboard/person/quiz/completed/{num}', [DashboardQuizController::class, 'quizCompleted']);
    Route::post('dashboard/person/quiz/answer/save', [DashboardQuizController::class, 'answerSave']);

    Route::get('dashboard/certificate-overview/{any}', [HomeCertificateProgramsController::class, 'certificateOverview']);
});
