<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_login();

define('NEW_PASSWORD', '123456');

// get user id from url or from global USER if not exist
$userid = required_param('userid', PARAM_INT); // User id; -1 if creating new user.
// get the user whose password we're going to change from the DB
$user = $DB->get_record('user', array('id' => $userid));
$authplugin = get_auth_plugin($user->auth);
$confirm = optional_param('confirm', false, PARAM_BOOL);
$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

// Check permissions.
$coursecontext = context_course::instance($course->id);
require_capability('report/passwordreset:view', $coursecontext);

$pageurl = '/report/passwordreset/resetpassword.php';
$returnurl = new moodle_url('/report/passwordreset/index.php', array('id' => $courseid));
$strdeletecheck = get_string('resetpasswordparticipant', 'report_passwordreset');
$backtocourse = new moodle_url('/course/view.php', array('id' => $courseid));

// Set up page - this part should always show, whether confirmed or not
$PAGE->set_context($coursecontext);
$PAGE->set_url(new moodle_url($pageurl));
$PAGE->navbar->add($course->shortname, $backtocourse);
$PAGE->navbar->add($strdeletecheck);
$PAGE->set_title($course->shortname . ': ' . get_string('passwordresettitle', 'report_passwordreset'));
$PAGE->set_heading($course->fullname);

if (!confirm_sesskey()) {
    redirect($returnurl);
}

if (!$confirm) {
    $reseturl = new moodle_url($pageurl, array('userid' => $userid, 'id' => $courseid, 'confirm' => true));
    $continueurl = $returnurl;
    $message = get_string('confirmmessage', 'report_passwordreset', $user->username);
    echo $OUTPUT->header();
    echo html_writer::tag('h2', get_string('resetpasswordparticipant', 'report_passwordreset'), array('class' => 'resetpasswordtitle'));
    echo $OUTPUT->confirm($message, $reseturl, $continueurl);
    echo $OUTPUT->footer();
    exit;
}

// give the user a new, fixed password
//if (!$authplugin->is_internal() and $authplugin->can_change_password()) {
if (!$authplugin->user_update_password($user, NEW_PASSWORD)) {
    print_object($authplugin);
    // Do not stop here, we need to finish user creation.
    debugging(get_string('cannotupdatepasswordonextauth', '', '', $user->auth), DEBUG_NONE);
}
//}

// require the user to change their password on next login
set_user_preference('auth_forcepasswordchange', 1, $user);


// show the username and new password
echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('resetpasswordparticipant', 'report_passwordreset'), array('class' => 'resetpasswordtitle'));
echo html_writer::start_tag('div', array('class'=>'passwordwasresetsuccess'));
echo html_writer::tag('p', get_string('passwordwasreset', 'report_passwordreset',
    array('username' => $user->username, 'password' => NEW_PASSWORD)));
echo html_writer::link($backtocourse, get_string('backtocourse', 'report_passwordreset'), array('class' => 'btn'));
echo html_writer::end_tag('div');

// Trigger a logs viewed event.
$params = array(
    'context' => context_course::instance($courseid),
    'other' => array(
        'participantid' => $user->id,
    )
);
$event = \report_passwordreset\event\report_updated::create($params);
$event->add_record_snapshot('user', $user);
$event->trigger();

echo $OUTPUT->footer();

