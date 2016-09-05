<?php
require_once($CFG->dirroot . '/question/type/shortanswer/renderer.php');
require_once($CFG->dirroot . '/question/type/wq/renderer.php');

class qtype_shortanswerwiris_renderer extends qtype_wq_renderer{
    public function __construct(moodle_page $page, $target){
        parent::__construct(new qtype_shortanswer_renderer($page, $target), $page, $target);
    }
    
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {
        
        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('answer');
        
        $inputname = $qa->get_qt_field_name('answer');        

        $inputattributes = array(
            'type' => 'text',
            'name' => $inputname,
            'value' => $currentanswer,
            'id' => $inputname,
            'size' => 80,
            'style' => 'display:none;',
            'class' => 'wirisanswerfield',
        );
        if ($options->readonly) {
            $inputattributes['readonly'] = 'readonly';
        }
		
        $feedbackimg = '';
		
        if ($options->correctness) {
            $answer = $question->get_matching_answer(array('answer' => $currentanswer));
            $fraction = $answer ? $answer->fraction : 0;
            $inputattributes['class'] .= ' wirisembeddedfeedback ' . $this->feedback_class($fraction);
            // Feedback image delegate to wirisembeddedfeedback class.
            // $feedbackimg = $this->feedback_image($fraction);
        }

        $questiontext = $question->format_questiontext($qa);
        
        $input = html_writer::empty_tag('input', $inputattributes) . $feedbackimg;

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= $this->auxiliarCas();
        $result .= html_writer::tag('label', get_string('answer', 'qtype_shortanswer',
                   html_writer::tag('span', $input, array('class' => 'answer'))),
                   array('for' => $inputattributes['id']));
        $result .= html_writer::end_tag('div');
        

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }
        $result .= $this->addJavaScript();
        $result .= $this->lang();
        $result .= $this->question($qa);
        $result .= $this->questionInstance($qa);

        return $result;
    }
    
    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();
        $answer = $question->get_correct_response();
        if (!$answer) {
            return '';
        }
        $text = get_string('correctansweris', 'qtype_shortanswer', $answer['answer']);
        return $question->format_text($text, FORMAT_HTML, $qa, 'question', 'correctanswer', $question->id);
    }

	public function feedback_class($fraction) {
		return 'wiris' . parent::feedback_class($fraction);
	}
}
