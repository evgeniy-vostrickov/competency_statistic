<?php
namespace report_competency_statistic\output;

use DateInterval;
use DatePeriod;
use DateTime;
use renderable;
use templatable;
use renderer_base;
use stdClass;
use core_competency\api;
use tool_lp\external\user_competency_summary_in_course_exporter;
use report_competency_statistic\output\report_general;

class report_for_all_course extends report_general implements renderable, templatable {

    /** @var int $course_id */
    protected $course_id;
    /** @var int $user_id */
    protected $user_id;

    /**
     * Construct this renderable.
     *
     * @param int $userid The user id
     */
    public function __construct($userid, $date_start, $date_end) {
        $this->user_id = $userid;
        $this->date_start = $date_start;
        $date_start_stmp = DateTime::createFromFormat('Y-m-d', $date_start);
        if ($date_start_stmp) $this->date_start_stmp = $date_start_stmp->getTimestamp();
        $date_end_stmp = DateTime::createFromFormat('Y-m-d', $date_end);
        if ($date_end_stmp) $this->date_end_stmp = $date_end_stmp->getTimestamp();
        $this->date_end = $date_end;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;

        $data = new stdClass();

        // Получаем список всех курсов, на которые записан студент
        $list_courses = enrol_get_users_courses($this->user_id, true);
        $list_coursesid = array_keys($list_courses);
        array_shift($list_coursesid);
        
        $data->currentuser = $this->user_id;
        $data->date_start = $this->date_start;
        $data->date_end = $this->date_end;
        $currentuser = $this->user_id;

        $labels = []; // Названия сформированных компетенций.
        $chart_data = []; // Данные для формирования графика ([1, 1, ...]).
        $competencies_data = []; // Задания, которые формируют наши компетенции.
        $competencies_dates = [];

        // Перебираем все текущие курсы пользователя.
        foreach($list_coursesid as $course_id) {
            $this->course_id = $course_id;

            $params = array('id' => $course_id);
            $course = $DB->get_record('course', $params);

            $usercompetencycourses = api::list_user_competencies_in_course($course_id, $currentuser);

            $modinfo = get_fast_modinfo($course, $currentuser);

            foreach ($usercompetencycourses as $usercompetencycourse) { // Для всех компетенций в курсе.
                $proficiency = $usercompetencycourse->get('proficiency');
                if ($proficiency == '0' || $proficiency == '1') {
                    $competency = $usercompetencycourse->get_competency();
                    $competency_description = mb_strimwidth(strip_tags($competency->get("description")), 0, 50, "...");
                    if (in_array($competency_description, $labels)) {
                        $key = array_search($competency_description, $labels);
                        $chart_data[$key] += 1;
                    }
                    else {
                        $labels[] = $competency_description;
                        $chart_data[] = 1;
                    }
                }
            }

            $this->get_completed_modules($usercompetencycourses, $currentuser, $modinfo, $competencies_data, $DB);

            $this->get_completed_modules_dates($competencies_dates, $usercompetencycourses, $currentuser, $modinfo, $course_id, $DB);
        }

        $unique_competencies = [];

        foreach ($competencies_dates as $competency) {
            $name = $competency->competency_name;

            if (!isset($unique_competencies[$name])) {
                $unique_competencies[$name] = (object) [
                    'competency_name' => $name,
                    'dates_values' => [],
                ];
            }

            $unique_competencies[$name]->dates_values = array_merge(
                $unique_competencies[$name]->dates_values,
                $competency->dates_values
            );
        }

        // Преобразуем ассоциативный массив обратно в индексированный массив
        $unique_competencies = array_values($unique_competencies);

        $buf = $this->prepare_competencies_by_dates($unique_competencies);
        $data->competencies_dates = $buf[0];
        $data->dates_labels = json_encode($buf[1]);

        // print_r($buf);
        $COLORS = $this->generateRandomColors(count($labels)); // Получает массив цветов столько же сколько есть пользовательских компетенций курса.
        $colors = $COLORS;

        $colors = array_slice($COLORS, 0, count($labels)); // Получает массив цветов столько же сколько есть пользовательских компетенций курса.

        if (count($labels) == 0) {
            $labels[] = $this->NOT_COMPETENCIES;
            $colors[] = $this->GRAY_COLOR;
            $chart_data[] = 1;
        }

        $colors = json_encode($colors);
        $labels = json_encode($labels);
        $chart_data = json_encode($chart_data);

        $data->colors = $colors;
        $data->labels = $labels;
        $data->chart_data = $chart_data;
        $data->competencies_data = $competencies_data;

        return $data;
    }

    private function get_completed_modules($usercompetencycourses, $currentuser, $modinfo, &$competencies_data, $DB) {
        global $PAGE;

        foreach ($usercompetencycourses as $usercompetencycourse) { // Для всех компетенций.

            $competency = $usercompetencycourse->get_competency();

            if (!$this->is_in_period($competency)) continue;

            $competency_name = strip_tags($competency->get("description"));

            $competency_data = new stdClass();
            $competency_data->competency_name = $competency_name;
            $competency_data->modules = []; // Массив завершенных модулей.

            $relatedcompetencies = api::list_related_competencies($competency->get('id')); // Список всех связанные компетенции.
            $user = $DB->get_record('user', array('id' => $currentuser));
            $evidence = api::list_evidence_in_course($currentuser, $this->course_id, $competency->get('id')); // Список всех доказательств компетентности пользователя в курсе.
            $course = $DB->get_record('course', array('id' => $this->course_id));

            $params = array(
                'competency' => $competency,
                'usercompetencycourse' => $usercompetencycourse,
                'evidence' => $evidence,
                'user' => $user,
                'course' => $course,
                'scale' => $competency->get_scale(),
                'relatedcompetencies' => $relatedcompetencies
            );
            
            // Класс для экспорта данных о компетенции пользователя с дополнительными связанными данными в плане.
            $exporter = new user_competency_summary_in_course_exporter(null, $params);
            $output = $PAGE->get_renderer('report_competency_statistic');
            $extra_data = $exporter->export($output);

            $competency_data->extra_data = $extra_data;

            $competency_modulecomps = $DB->get_records('competency_modulecomp', array('competencyid' => $competency->get('id')));

            foreach ($competency_modulecomps as $competency_modulecomp) {
                $mod = $modinfo->cms[$competency_modulecomp->cmid];
                
                $proficiency = $usercompetencycourse->get('proficiency');
                if ($proficiency == '0' || $proficiency == '1') {
                    $mod_std = new stdClass();
                    $mod_std->name = $mod->name;
                    $mod_std->url = $mod->url;
                    $competency_data->modules[] = $mod_std;
                }
            }
            
            if (count($competency_data->modules) > 0) $competencies_data[] = $competency_data;
        }
    }

    private function get_completed_modules_dates(&$competencies_dates, $usercompetencycourses, $currentuser, $modinfo, $course_id, $DB) {
        foreach ($usercompetencycourses as $usercompetencycourse) { // Для всех компетенций.

            $competency = $usercompetencycourse->get_competency();

            if (!$this->is_in_period($competency)) continue;

            $competency_name = mb_strimwidth(strip_tags($competency->get("description")), 0, 30, "...");;

            $competency_data = new stdClass(); // Дата закрытия компетенций.
            $competency_data->competency_name = $competency_name;
            $competency_data->dates_values = [];

            $competency_modulecomps = $DB->get_records('competency_modulecomp',
                                                       array('competencyid' => $competency->get('id'))); //TODO вынести отсюда.

            foreach ($competency_modulecomps as $competency_modulecomp) {
                if(!array_key_exists($competency_modulecomp->cmid, $modinfo->cms)) continue;
                $completion = $DB->get_record("course_modules_completion",
                                              array("coursemoduleid" => $competency_modulecomp->cmid, "userid" => $currentuser));//TODO вынести отсюда.

                if (!empty($completion) && $completion->completionstate == "1") {
                    $competency_data->dates_values[] = $completion->timemodified;
                } else {
                
                    $cm = $modinfo->get_cm($competency_modulecomp->cmid);
                    $grades = grade_get_grades($course_id, 'mod', $cm->modname, $cm->instance, $currentuser);
                    $item_grades = $grades->items[0]->grades;
                    if (!empty($grades) && count($grades->items) > 0 && count($grades->items[0]->grades) > 0 && $item_grades[array_keys($item_grades)[0]]->dategraded != null) {
                        $competency_data->dates_values[] = $item_grades[array_keys($item_grades)[0]]->dategraded;
                    }
                }
                
            }
            asort($competency_data->dates_values);

            $competencies_dates[] = $competency_data;
        }
    }

    private function prepare_competencies_by_dates($competencies_dates) {
        if (count($competencies_dates) == 0) return [[], []];

        //Находим начальную дату для графика.
        $min_date = PHP_INT_MAX;
        foreach ($competencies_dates as $competencies_date) {
            if (count($competencies_date->dates_values) > 0 && $competencies_date->dates_values[0] < $min_date) {
                $min_date = $competencies_date->dates_values[0];
            }
        }

        if ($min_date > time()) return [[], []];

        //С начальной даты, до текущей, для каждого дня.
        $begin = new DateTime();
        $begin->setTimestamp($min_date);
        $begin->modify('-2 months');
        $end = new DateTime();
        $end->modify('+1 months');
        $interval = DateInterval::createFromDateString('first day of next month');
        $period = new DatePeriod($begin, $interval, $end);

        $dates_labels = [];

        foreach ($period as $dt) {
            $dates_labels[] = $dt->format("F, Y");
        }

        $end->modify('-1 months');
        $period = new DatePeriod($begin, $interval, $end);

        // Перебираем даты формирования подкомпетенций в модулях (на каждом цикле берется массив дат закрытия модулей).
        foreach ($competencies_dates as $i => $competencies_date) {
            $all_formatted_dates = [];

            // Перебор дат закрытия подкомпетенции модулей.
            foreach ($competencies_date->dates_values as $date) {
                $all_formatted_dates[] = date("Y-m", $date);
            }
            
            $completed_by_dates = array_count_values($all_formatted_dates); // Превращение в массив, где ключ - дата, значение - количество повторов.
            $current_completed = 0; // Число завершенных подкомпетенций модуля.
            $competencies_date->dates_values = []; // Обнуляем и будем хранить сколько подкомпетенций были сформированы за период.

            // Перебираем даты в периоде
            foreach ($period as $dt) {
                $date_formatted = $dt->format("Y-m"); // Берем дату
                
                if (array_key_exists($date_formatted, $completed_by_dates)) {
                    $current_completed += $completed_by_dates[$date_formatted]; // Если в эту дату было завершение, то увеличиваем счетчик.
                }
                $competencies_date->dates_values[] = $current_completed; // Сохраняем количество сформированных компетенций.
            }
            $competencies_date->color = $this->generateRandomColors(1)[0]; // Берем цвет для покарски компетенции.
            $competencies_date->dates_values = json_encode($competencies_date->dates_values);
        }

        return [$competencies_dates, $dates_labels];
    }

}
