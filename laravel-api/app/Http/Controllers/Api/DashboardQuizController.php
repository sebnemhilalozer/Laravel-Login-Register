<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Facades\Agent;

class DashboardQuiz extends Controller
{


    public function __construct()
    {
        if (!session()->get('personRole')) {
            echo 'Access denied.';
            exit;
        }
    }


    public function quizSessionStart($courseId, $contentId, $quizId, Request $request)
    {

        if (empty($courseId) || empty($quizId)) :
            die();
        endif;

        $authModel = new \App\Models\User();
        $loggedPersonId =  session()->get('loggedPerson');
        $personInfo =  $authModel->where('id', $loggedPersonId)->first();

        if (empty($personInfo)) :
            die();
        endif;

        $agent = Agent::parse($request->header('user-agent'));
        $browser_id = $agent->platform() . "-" . $agent->browser() . "-" . $agent->version();
        $ip_address = $request->ip();

        $quizSessionStartModel = new \App\Models\quizSessionStart();
        $quizSessionStartControl = $quizSessionStartModel->select("id, session_start_date")
            ->where(array("session_id" => session_id(), "ip" => $ip_address, "browser" => $browser_id,))->first();
        $session_start_date = "";
        if (empty($quizSessionStartControl)) {
            $values = [
                'person_id' => $personInfo["id"],
                'course_id' => $courseId,
                'quiz_id' => $quizId,
                'quiz_status' => "Tamamlanmadı",
                'session_start_date' => date("Y-m-d H:i:s"),
                'session_id' => session_id(),
                'ip' => $ip_address,
                'browser' => $browser_id
            ];
            $quizSessionStartModel->insert($values);
            $insert_id = $quizSessionStartModel->getInsertID();
            /** LOGS **/
            $this->saveLog("cc_quiz_session_start", $insert_id, "Create");
        } else {
            $session_start_date = $quizSessionStartControl["session_start_date"];
            $insert_id = $quizSessionStartControl["id"];
        }

        /*
         * Quiz Bilgileri
         */

        $courseContent = new \App\Models\courseContent();
        $content = $courseContent->select("course_id, title")
            ->where(array("status" => 1, "deleted" => 0, "id" => $contentId))->first();

        $courseContentQuiz = new \App\Models\courseContentQuiz();
        $contentDetail = $courseContentQuiz->select("duration, question_random")
            ->where(array("deleted" => 0, "id" => $quizId, "course_content_id" => $contentId))->first();


        /*
         * Question
         */
        $courseContentQuizQuestion = new \App\Models\courseContentQuizQuestion();
        $question = $courseContentQuizQuestion->select("id, title")
            ->where(array("deleted" => 0, "cc_quiz_id" => $quizId))
            ->findAll();

        $data = [
            'pageTitle' => "Quiz -> " . $content["title"],
            'personInfo' => $personInfo,
            'quiz' => $content,
            "quiz_id" => $quizId,
            'quizDetail' => $contentDetail,
            'session_id' => session_id(),
            'start_id' => $insert_id,
            'session_start_date' => $session_start_date,
            'question' => $question
        ];

        return view('frontend/dashboard/course_quiz', $data);
    }


    public function quizCompleted($status, Request $request)
    {

        $quiz_id = $request->input('quiz_id');
        $person_id = $request->input('person_id');
        $session_id = $request->input('session_id');
        $start_id = $request->input('start_id');
        if ($status == 1) {
            $status = "Otomatik Tamamlandı";
        } else if ($status == 2) {
            $status = "Tamamlandı";
        } else {
            $status = "Tamamlandı";
        }

        $quizSessionAnswerModel = new \App\Models\QuizSessionAnswer();


        $getTotalScore = $quizSessionAnswerModel->select("sum(score) as totalScore")->where(array('session_start_id' => $start_id))->first();

        $courseContentQuizModel = new \App\Models\courseContentQuiz();
        $courseContentQuiz = $courseContentQuizModel->where(array('id' => $quiz_id, 'deleted'  => 0))
            ->first();

        $completed = 0;
        if ($getTotalScore["totalScore"] >= $courseContentQuiz["passing_score"]) {
            $completed = 1;
        }

        $quizSessionStartModel = new \App\Models\quizSessionStart();

        $values = [
            'quiz_status' => $status,
            'score' => $getTotalScore["totalScore"],
            'completed' => $completed,
            'session_end_date' => date("Y-m-d H:i:s")
        ];
        $where = [
            'person_id' => $person_id,
            'quiz_id' => $quiz_id,
            'session_id' => $session_id
        ];


        $quizSessionStartModel->update($start_id, $values);
        /** LOGS **/
        $this->saveLog("cc_quiz_session_start", $start_id, "Update");
    }

    public function answerSave(Request $request)
    {

        $question_id = $request->input('question_id');
        $answer_id = $request->input('answer_id');
        $start_id = $request->input('start_id');
        $course_id = $request->input('course_id');
        $quiz_id = $request->input('quiz_id');
        $person_id = $request->input('person_id'); // Security
        $session_id = $request->input('session_id');
    
       
        $agent = Agent::parse($request->header('user-agent'));
        $browser_id = $agent->platform() . "-" . $agent->browser() . "-" . $agent->version();
    
        $ip_address = $request->ip();

        $quizSessionAnswerModel = new \App\Models\quizSessionAnswer();

        $courseContentQuizQuestion = new \App\Models\courseContentQuizQuestion();
        $ccqq = $courseContentQuizQuestion->where(array('id' => $question_id, 'deleted'  => 0))
            ->first();


        $where = [
            "session_start_id" => $start_id,
            "person_id" => $person_id,
            "course_id" => $course_id,
            "quiz_id" => $quiz_id,
            "question_id" => $question_id
        ];

        $courseContentQuizQuestionAlternative = new \App\Models\courseContentQuizQuestionAlternative();
        $ccqq_alternative = $courseContentQuizQuestionAlternative->where(array('id' => $answer_id, 'cc_question_id' => $question_id, 'deleted'  => 0))
            ->first();

        $score = 0;
        if ($ccqq_alternative["correct"]) {
            $score = $ccqq["score"];
        }
        $answerControl = $quizSessionAnswerModel->where($where)->first();
        if (!empty($answerControl)) {
            //update
            $values = [
                'answer_id' => $answer_id,
                'is_correct' => $ccqq_alternative["correct"],
                'score' => $score,
                'last_change_date' => date("Y-m-d H:i:s")
            ];

            $quizSessionAnswerModel->update($answerControl["id"], $values);
            /** LOGS **/
            $this->saveLog("cc_quiz_session_answer", $answerControl["id"], "Update");
        } else {
            //insert
            $values = [
                "session_start_id" => $start_id,
                "person_id" => $person_id,
                "course_id" => $course_id,
                "quiz_id" => $quiz_id,
                "question_id" => $question_id,
                "answer_id" => $answer_id,
                'is_correct' => $ccqq_alternative["correct"],
                'score' => $score,
                'created_at' => date("Y-m-d H:i:s"),
                "session_id" => $session_id,
                "ip" => $ip_address,
                "browser" => $browser_id
            ];
            $quizSessionAnswerModel->insert($values);
            /** LOGS **/
            $insert_id = $quizSessionAnswerModel->getInsertID();
            $this->saveLog("cc_quiz_session_answer", $insert_id, "Create");
        }
    }




    public function quizAlternative(Request $request)
    {
        $question_id = $request->input('question_id');
        $courseContentQuizQuestionAlternative = new \App\Models\courseContentQuizQuestionAlternative();
        $alternative =  $courseContentQuizQuestionAlternative->Select("id, title")->where(array('cc_question_id' => $question_id, 'deleted' => 0))->findAll();

        if ($alternative) {
            echo json_encode(['code' => 1, 'msg' => '', 'result' => $alternative]);
        } else {
            echo json_encode(['code' => 0, 'msg' => 'No results found', 'result' => null]);
        }
    }
}
