<?php
/**
 *
 * @package    report
 * @subpackage competency_statistic
 */

use core_competency\api;

require_once('../../config.php');


// echo 'Hello toc!! <p>';
// $id = optional_param('course_id', null, PARAM_INT);
// echo $id . "<---";

// die();


$PAGE->requires->css(new moodle_url('/report/competency_statistic/src/cssprogress.min.css'));
$PAGE->requires->css(new moodle_url('/report/competency_statistic/src/style.css'));

echo '<script src="'.new moodle_url('/report/competency_statistic/src/chart.min.js').'"></script>';

$course_id = optional_param('id', null, PARAM_INT);

$params = array('id' => $course_id);
$course = $DB->get_record('course', $params);

require_login($course);

$context = context_course::instance($course->id);

$currentuser = optional_param('user_id', null, PARAM_INT);

$statistic_all_courses = optional_param('statistic_all_courses', null, PARAM_INT);
$statistic_all_competencies = optional_param('statistic_all_competencies', null, PARAM_INT);

$currentmodule = optional_param('mod', null, PARAM_INT);

$date_start = optional_param('date_start', null, PARAM_TEXT);
$date_end = optional_param('date_end', null, PARAM_TEXT);

$baseurl = new moodle_url('/report/competency_statistic/index.php');


if (!$currentuser) {
    $PAGE->set_title(get_string('reportname', 'report_competency_statistic'));
    $PAGE->set_heading(get_string('reportname', 'report_competency_statistic'));
    echo $OUTPUT->header();

    $output = $PAGE->get_renderer('report_competency_statistic'); // получение объекта рендера (файл report/competency_statistic/classes/output/renderer.php)
    $page = new \report_competency_statistic\output\report_for_course($course->id);
    echo $output->render($page);

} else if ($statistic_all_courses) {
    $user = $DB->get_record('user', array('id'=>$currentuser, 'deleted'=>0), '*', MUST_EXIST);
    $title = get_string('reportname', 'report_competency_statistic');
    $PAGE->navbar->add($title, new moodle_url('/report/competency_statistic/index.php', array('id' => $course_id))); // В навигации ссылка на Competency statistic (общий отчет по компетенциям)
    $title .= ' для пользователя '.$user->firstname . " " . $user->lastname;
    $PAGE->set_heading($title);
    $PAGE->navbar->add($title);
    $PAGE->set_title($title);
    echo $OUTPUT->header();
    $output = $PAGE->get_renderer('report_competency_statistic');
    $page = new \report_competency_statistic\output\report_for_all_course($currentuser, $date_start, $date_end);
    // $page = new \report_competency_statistic\output\report_for_all_course($currentuser, $date_start, $date_end);
    echo $output->render($page);
} else if ($statistic_all_competencies) {
    $user = $DB->get_record('user', array('id'=>$currentuser, 'deleted'=>0), '*', MUST_EXIST);
    $title = get_string('reportname', 'report_competency_statistic');
    $PAGE->navbar->add($title, new moodle_url('/report/competency_statistic/index.php', array('id' => $course_id))); // В навигации ссылка на Competency statistic (общий отчет по компетенциям)
    $title .= ' для пользователя '.$user->firstname . " " . $user->lastname;
    $PAGE->set_heading($title);
    $PAGE->navbar->add($title);
    $PAGE->set_title($title);
    echo $OUTPUT->header();
    $output = $PAGE->get_renderer('report_competency_statistic');
    $page = new \report_competency_statistic\output\report_for_all_competencies($currentuser, $date_start, $date_end);
    echo $output->render($page);
} else {
    $user = $DB->get_record('user', array('id'=>$currentuser, 'deleted'=>0), '*', MUST_EXIST);
    $title = get_string('reportname', 'report_competency_statistic');
    $PAGE->navbar->add($title, new moodle_url('/report/competency_statistic/index.php', array('id' => $course_id))); // В навигации ссылка на Competency statistic (общий отчет по компетенциям)
    $title .= ' для пользователя '.$user->firstname . " " . $user->lastname;
    $PAGE->set_heading($title);
    $PAGE->navbar->add($title);
    $PAGE->set_title($title);
    echo $OUTPUT->header();
    $output = $PAGE->get_renderer('report_competency_statistic');
    $page = new \report_competency_statistic\output\report_for_user($course->id, $currentuser, $date_start, $date_end);
    echo $output->render($page);
}

echo $OUTPUT->footer();
