<?php

defined('MOODLE_INTERNAL') || die();

use core_competency\api;

class report_competency_statistic_main_testcase extends advanced_testcase {

    /**
     * Setup testcase.
     */
    public function setUp() {
        $this->setAdminUser();
        $this->resetAfterTest();
    }

    public function test_for_course_no_competencies() {
        $course = $this->getDataGenerator()->create_course();
        $PAGE = new \moodle_page();

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $module = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));
        $page = new \report_competency_statistic\output\report_for_course($course->id);
        $output = $PAGE->get_renderer('report_competency_statistic');
        $result = $page->export_for_template($output);

        $this->assertEquals($course->id, $result->courseid);
        $this->assertEquals(1, count($result->students));
        $this->assertContains("data-percent='0'", $result->students[0]->progress);
        $this->assertContains("0%", $result->students[0]->progress);
        $this->assertContains($user->firstname, $result->students[0]->name);
        $this->assertContains($user->lastname, $result->students[0]->name);
    }

    public function test_for_course_one_uncompleted_competency() {
        $course = $this->getDataGenerator()->create_course();
        $PAGE = new \moodle_page();

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        $f1 = $lpg->create_framework();

        $c1 = $lpg->create_competency(array('competencyframeworkid' => $f1->get('id')));

        $tpl = $lpg->create_template();
        $lpg->create_template_competency(array('templateid' => $tpl->get('id'), 'competencyid' => $c1->get('id')));

        $uc = $lpg->create_user_competency(array('userid' => $user->id, 'competencyid' => $c1->get('id')));
        $uc = $lpg->create_user_competency_course(array('userid' => $user->id, 'competencyid' => $c1->get('id'), 'courseid' => $course->id));

        $cc = api::add_competency_to_course($course->id, $c1->get('id'));

        $evidence = \core_competency\external::grade_competency_in_course($course->id, $user->id, $c1->get('id'), 1, true);

        $page = new \report_competency_statistic\output\report_for_course($course->id);
        $output = $PAGE->get_renderer('report_competency_statistic');
        $result = $page->export_for_template($output);

        var_dump($result);

        $this->assertEquals($course->id, $result->courseid);
        $this->assertEquals(1, count($result->students));
        $this->assertContains("data-percent='0'", $result->students[0]->progress);
        $this->assertContains("0%", $result->students[0]->progress);
        $this->assertContains($user->firstname, $result->students[0]->name);
        $this->assertContains($user->lastname, $result->students[0]->name);
    }

    public function test_for_user_no_competencies() {
        $course = $this->getDataGenerator()->create_course();
        $PAGE = new \moodle_page();

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $module = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));
        $page = new \report_competency_statistic\output\report_for_user($course->id, $user->id, NULL, NULL);
        $output = $PAGE->get_renderer('report_competency_statistic');
        $result = $page->export_for_template($output);

        $this->assertEquals($course->id, $result->courseid);
        $this->assertEquals($user->id, $result->currentuser);
        $this->assertEquals(NULL, $result->date_start);
        $this->assertEquals(NULL, $result->date_end);
        $this->assertEquals('[]', $result->dates_labels);
        $this->assertEmpty($result->competencies_data);
        $this->assertEmpty($result->competencies_data_unfinished);
        $this->assertEmpty($result->competencies_dates);
    }


}
