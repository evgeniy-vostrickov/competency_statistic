<?php
namespace report_competency_statistic\output;

use DateTime;
use renderable;
use templatable;
use renderer_base;
use stdClass;
use core_competency\api;
use report_competency_statistic\output\report_general;

class report_for_all_competencies extends report_general implements renderable, templatable {

    /** @var int $course_id */
    protected $course_id;
    /** @var int $user_id */
    protected $user_id;
    /** @var string $date_start */
    protected $date_start;
    /** @var string $date_end */
    protected $date_end;
    /** @var string $date_start_stmp */
    protected $date_start_stmp;
    /** @var string $date_end_stmp */
    protected $date_end_stmp;
    /** @var array $competencies */
    protected $competencies;

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
        
        $data->currentuser = $this->user_id;
        $data->date_start = $this->date_start;
        $data->date_end = $this->date_end;
        $currentuser = $this->user_id;

        $idnumber = $DB->get_field('user', 'idnumber', array('id' => $currentuser));

        $enrol = enrol_get_plugin('poasdatabase');
        $my_list_competency = $enrol->get_user_competencies($idnumber);
        $usercompetencycourses = array();
        $colors = array();
        $chart_data = array();

        if (count($my_list_competency) != 0) {
          for($i = 0; $i < count($my_list_competency); $i++) {
            $usercompetencycourses[$i] = mb_strimwidth($my_list_competency[$i], 0, 100, "...");
          }
  
          $colors = $this->generateRandomColors(count($my_list_competency));
          $chart_data = array_fill(0, count($my_list_competency), 1);
        } else {
          $my_list_competency[] = 'Нет освоенных компетенций';
          $usercompetencycourses[] = 'Нет освоенных компетенций';
          $colors[] = '#95a5a6';
          $chart_data[] = 1;
        }

        $colors = json_encode($colors);
        $labels = json_encode($usercompetencycourses);
        $chart_data = json_encode($chart_data);

        $data->colors = $colors;
        $data->labels = $labels;
        $data->chart_data = $chart_data;
        $data->my_list_competency = $my_list_competency;

        return $data;
    }

    private function is_in_period($object): bool {
        if ($this->date_end_stmp == null || $this->date_start_stmp == null) return true;
        return $object->get("timecreated") < $this->date_end_stmp && $object->get("timecreated") > $this->date_start_stmp;
    }

}
