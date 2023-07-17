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

    // Dashboard Routes
    Route::get('dashboard', 'DashboardController@index')->name('dashboard');


    //SAME AS ABOVE BUT WITH DIFFERENT WRITING
    Route::get('dashboard/course/continue/{num}', [DashboardController::class, 'courseContinue']);
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
