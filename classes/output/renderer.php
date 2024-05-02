<?php

/**
 * Renderer class for report_competency_statistic
 *
 * @package    report_competency_statistic
 */

namespace report_competency_statistic\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use stdClass;

/**
 * Renderer class for competency statistic report
 *
 * @package    report_competency_statistic
 */
class renderer extends plugin_renderer_base {

    /**
     * @param report_for_user $page
     * @return string html for the page
     */
    public function render_report_for_user(report_for_user $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('report_competency_statistic/report_for_user', $data);
    }

    /**
     * @param report_for_course $page
     * @return string html for the page
     */
    public function render_report_for_course(report_for_course $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('report_competency_statistic/report_for_course', $data);
    }

    /**
     * @param report_for_all_course $page
     * @return string html for the page
     */
    public function render_report_for_all_course(report_for_all_course $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('report_competency_statistic/report_for_all_course', $data);
    }

    /**
     * @param report_for_all_competencies $page
     * @return string html for the page
     */
    public function render_report_for_all_competencies(report_for_all_competencies $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('report_competency_statistic/report_for_all_competencies', $data);
    }

    /**
     * @param integer $percent
     * @return string
     */
    public function render_progress_bar(int $percent): string
    {
        $data = new stdClass();
        $data->percent = $percent;
        return parent::render_from_template('report_competency_statistic/progress_bar', $data);
    }

}
