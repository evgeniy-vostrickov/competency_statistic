<?php
namespace report_competency_statistic\output;

use context_course;
use html_writer;
use moodle_url;
use renderable;
use templatable;
use renderer_base;
use stdClass;
use core_competency\api;
use grade_grade;
use grade_item;

class report_for_course implements renderable, templatable {

    /** @var context $context */
    protected $context;
    /** @var int $course_id */
    protected $course_id;
    /** @var array $competencies */
    protected $competencies;

    /**
     * Construct this renderable.
     *
     * @param int $courseid The course id
     */
    public function __construct($courseid) {
        $this->course_id = $courseid;
        $this->context = context_course::instance($courseid);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $PAGE, $OUTPUT;

        $data = new stdClass();
        $data->courseid = $this->course_id;
        $course_id = $this->course_id;

        $params = array('id' => $course_id);
        $course = $DB->get_record('course', $params);

        // Fetch showactive.
        $defaultgradeshowactiveenrol = !empty($CFG->grade_report_showonlyactiveenrol); // показывает активных пользователей
        $showonlyactiveenrol = get_user_preferences('grade_report_showonlyactiveenrol', $defaultgradeshowactiveenrol); // получение настроек пользователя
        $showonlyactiveenrol = $showonlyactiveenrol || !has_capability('moodle/course:viewsuspendedusers', $this->context); // проверка на то какие права у пользователя (нужно показывать только активных пользователй или заблокированных тоже)

        // Получить id активной группы курса.
        $currentgroup = groups_get_course_group($course, true);

        // Список пользователей курса.
        $users = get_enrolled_users($this->context, 'moodle/competency:coursecompetencygradable', $currentgroup, 'u.*', null, 0, 0, $showonlyactiveenrol);

        $params = array('id' => $course_id);
        $course = $DB->get_record('course', $params);

        // Получаем секции курса
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_sections();

        $cm_array = array();

        // Проходим по секциям и получаем модули
        foreach ($sections as $section) {
            foreach ($section as $cmid) {
                $cm = $modinfo->cms[$cmid];
                if ($cm->uservisible && $cm->modname !== 'label') {
                    $grade_items = grade_item::fetch_all(['iteminstance' => $cm->instance, 'itemmodule' => $cm->modname]);
                    if ($grade_items) {
                        $cm_array[$cmid] = $cm;
                    }
                }
            }
        }
        
        $students = [];

        foreach ($users as $user) {

            $student = new stdClass();

            // Создание аватара пользователя.
            $student->name = $OUTPUT->user_picture($user, array('courseid' => $course_id, 'link' => true)) . $user->firstname . " " . $user->lastname;

            // Список всех компетенций доступных пользователю в курсе.
            $usercompetencycourses = api::list_user_competencies_in_course($course_id, $user->id);

            $total = 0;
            $success = 0;

            // Формирование освоенных компетенций.
            foreach ($usercompetencycourses as $usercompetencycourse) {    
                $proficiency = $usercompetencycourse->get('proficiency');
                if ($proficiency == '0' || $proficiency == '1') {
                    $success += 1;
                }
                $total += 1;
            }
            if ($total == 0) {
                $percent = 0;
            } else {
                $percent = 100 * $success /  $total;
            }

            // Получение объекта вывода.
            $output = $PAGE->get_renderer('report_competency_statistic');

            // Прогресс бар освоенных компетенций.
            $student->progress = $output->render_progress_bar($percent);

            // Ссылка на полную статистику по данному курсу.
            $student->link = html_writer::link(new moodle_url('/report/competency_statistic/index.php', array('id' => $course_id, 'user_id' => $user->id)), "Статистика по курсу");
            
            // Ссылка на полную статистику по ВСЕМ курсам.
            $student->link_all_courses = html_writer::link(new moodle_url('/report/competency_statistic/index.php', array('id' => $course_id, 'user_id' => $user->id, 'statistic_all_courses' => 1)), "Статистика по курсам");
            
            // Ссылка на полную статистику по ВСЕМ курсам.
            $student->link_all_competencies = html_writer::link(new moodle_url('/report/competency_statistic/index.php', array('id' => $course_id, 'user_id' => $user->id, 'statistic_all_competencies' => 1)), "Освоенные компетенции");

            $total_modules = 0;
            $success_modules = 0;

            foreach ($cm_array as $cmid => $cm) {
                // Получение завершенного студентом модуля нашего курса.
                $completion = $DB->get_record("course_modules_completion", array("coursemoduleid" => $cmid, "userid" => $user->id));
                $total_modules += 1;

                // Если такой модуль есть в списке завершенных .
                if (!empty($completion) && $completion->completionstate == "1") {
                    $success_modules += 1;
                } else {
                    $grades = grade_get_grades($course_id, 'mod', $cm->modname, $cm->instance, $user->id); //Возвращает информацию об оценках.
                    $item_grades = $grades->items[0]->grades; // Получаем массив оценок (хотя там она всегда одна).
                    // Проверяем существует ли оценка за данный модуль.
                    if (!empty($grades) && count($grades->items) > 0 && count($grades->items[0]->grades) > 0 && $item_grades[array_keys($item_grades)[0]]->grade != null) {
                        $success_modules += 1;
                    }
                }
            }
            
            if ($total_modules == 0) {
                $percent_modules = 0;
            } else {
                $percent_modules = 100 * $success_modules /  $total_modules;
            }
            
            // Прогресс бар выполненных заданий (total_modules - всего компетенций по всем разделам курса; success_modules - успешно закрытые или выполненные компетенции).
            $student->progress_modules = $output->render_progress_bar($percent_modules);
                                                   
            $students[] = $student;
        }

        $helpicon_statistic = $OUTPUT->help_icon('statistic', 'report_competency_statistic', 'Подробнее');
        
        $data->helpicon_statistic = $helpicon_statistic;
        $data->students = $students;

        return $data;
    }
}
