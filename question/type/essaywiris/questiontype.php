<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/wq/questiontype.php');
require_once($CFG->dirroot . '/question/type/essay/questiontype.php');

class qtype_essaywiris extends qtype_wq {

    public function __construct() {
        parent::__construct(new qtype_essay());
    }    
    
    public function create_editing_form($submiturl, $question, $category, $contexts, $formeditable) {
        global $CFG;
        require_once($CFG->dirroot . '/question/type/essaywiris/edit_essaywiris_form.php');
        $wform = $this->base->create_editing_form($submiturl, $question, $category, $contexts, $formeditable);
        return new qtype_essaywiris_edit_form($wform, $submiturl, $question, $category, $contexts, $formeditable);
    }

    public function save_question_options($formdata) {
        global $DB;

        // For Moodle 2.6 updates: essay_options must be saved
        // moodle essay updates deletes essayiris records)
        // qtype_essaywiris_backups not exists in 2.6 and upwards.
        if ($DB->get_manager()->table_exists('qtype_essaywiris_backup')) {
            $context = $formdata->context;

            $options = $DB->get_record('qtype_essaywiris_backup', array('questionid' => $formdata->id));
            if (!$options) {
                $options = new stdClass();
                $options->questionid = $formdata->id;
                $options->id = $DB->insert_record('qtype_essaywiris_backup', $options);
            }

            $options->responseformat = $formdata->responseformat;
            $options->responsefieldlines = $formdata->responsefieldlines;
            $options->attachments = $formdata->attachments;
            $options->graderinfo = $this->import_or_save_files($formdata->graderinfo,
                    $context, 'qtype_essay', 'graderinfo', $formdata->id);
            $options->graderinfoformat = $formdata->graderinfo['format'];
            $DB->update_record('qtype_essaywiris_backup', $options);
        }
        parent::save_question_options($formdata);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;

        if ($DB->get_manager()->table_exists('qtype_essaywiris_backup')) {
            $DB->delete_records('qtype_essaywiris_backup', array('questionid' => $questionid));
        }
        parent::delete_question($questionid, $contextid);
    }


    public function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $question->responseformat = &$question->base->responseformat;
        $question->responsefieldlines = &$question->base->responsefieldlines;
        $question->attachments = &$question->base->attachments;
        $question->graderinfo = &$question->base->graderinfo;
        $question->graderinfoformat = &$question->base->graderinfoformat;
        $question->responsetemplate = &$question->base->responsetemplate;
        $question->responsetemplateformat = &$question->base->responsetemplateformat;
    }
    
    public function export_to_xml($question, qformat_xml $format, $extra=null) {
        $fs = get_file_storage();
        $contextid = $question->contextid;        
        $expout = "    <responseformat>" . $question->options->responseformat .
                "</responseformat>\n";
        $expout .= "    <responsefieldlines>" . $question->options->responsefieldlines .
                "</responsefieldlines>\n";
        $expout .= "    <attachments>" . $question->options->attachments .
                "</attachments>\n";
        $expout .= "    <graderinfo " .
                $format->format($question->options->graderinfoformat) . ">\n";
        $expout .= $format->writetext($question->options->graderinfo, 3);
        $expout .= $format->write_files($fs->get_area_files($contextid, 'qtype_essay',
                'graderinfo', $question->id));
        $expout .= "    </graderinfo>\n";
        $expout .= parent::export_to_xml($question, $format);
        return $expout;
    }    
    
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        if (isset($question) && $question == 0){
            return false;
        }
        if (isset($data['#']['wirisquestion']) && substr($data['#']['wirisquestion'][0]['#'], 0, 9) == '«session'){
            //Moodle 1.9
            $text = $data['#']['questiontext'][0]['#']['text'][0]['#'];
            $text = $this->wrsqz_adapttext($text);
            $data['#']['questiontext'][0]['#']['text'][0]['#'] = $text;            
            $qo = $format->import_essay($data);
            $qo->qtype = 'essaywiris';
            $wirisquestion = '<question><wirisCasSession>';
            $wirisquestion .= htmlspecialchars($this->wrsqz_mathmlDecode(trim($data['#']['wirisquestion'][0]['#'])), ENT_COMPAT, "UTF-8");
            $wirisquestion .= '</wirisCasSession>';
            
            if (isset($data['#']['wirisoptions']) && count($data['#']['wirisoptions'][0]['#']) > 0){
                $wirisquestion .= '<localData>';    
                $wirisquestion .= $this->wrsqz_getCASForComputations($data);
                $wirisquestion .= $this->wrsqz_hiddenInitialCASValue($data);
                $wirisquestion .= '</localData>';
            }
            
            $wirisquestion .= '</question>';
            $qo->wirisquestion = $wirisquestion;
            return $qo;
        }else{
            //Moodle 2.x
            $qo = $format->import_essay($data);
            $qo->qtype = 'essaywiris';
            $qo->wirisquestion = trim($this->decode_html_entities($data['#']['wirisquestion'][0]['#']));
            return $qo;
        }            
    }    
    
}
