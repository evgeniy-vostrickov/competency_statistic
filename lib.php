<?php

function report_competency_statistic_extend_navigation_course($reportnav, $course, $context) {
    $url = new moodle_url('/report/competency_statistic/index.php', array('id' => $course->id));
    $reportnav->add(get_string('reportname', 'report_competency_statistic'), $url);
}

function report_competency_statistic_extend_navigation($reportnav, $course, $context) {
    $url = new moodle_url('/report/competency_statistic/index.php', array('id' => $course->id));
    $reportnav->add(get_string('reportname', 'report_competency_statistic'), $url);
}

function report_competency_statistic_extend_navigation_user($navigation, $user, $course) {
    $url = new moodle_url('/report/competency_statistic/index.php', array('id'=>$course->id, 'user_id'=>$user->id));
    $navigation->add(get_string('reportname', 'report_competency_statistic'), $url);
    $url = new moodle_url('/report/competency_statistic/index.php', array('user_id'=>$user->id));
    $navigation->add(get_string('reportname', 'report_competency_statistic'), $url);
}

function report_competency_statistic_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if(empty($course)) return;
    if (empty($course)) {
        // We want to display these reports under the site context.
        $course = get_fast_modinfo(SITEID)->get_course();
    }
    
    $url = new moodle_url('/report/competency_statistic/index.php', array('id'=>$course->id, 'user_id'=>$user->id));
    $name = get_string('reportname', 'report_competency_statistic');
    $node = new core_user\output\myprofile\node('reports', 'competency_statistic', $name, null, $url);
    $tree->add_node($node);
}
