<?php
require_once('./common.php');

/**
 * @since      08-Aug-2016
 * @package    local_akindi
 * @author     David Wolever <david@wolever.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_login();

$id = required_param('id', PARAM_INT);
$course = get_course($id);
$context = context_course::instance($course->id);

$PAGE->set_context($context);
$PAGE->set_url('/local/akindi/launch.php');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('launching', 'local_akindi'));
$PAGE->navbar->add(get_string('pluginname', 'local_akindi'));
echo $OUTPUT->header();

if (!has_capability('moodle/grade:edit', $context)) {
  ?>

  <h2>Permission Denied</h2>
  <p>Only instructors (with the <tt>moodle/grade:edit</tt> capability) can launch Akindi.</p>

  <?php
  echo $OUTPUT->footer();
  die();
}

global $CFG;
global $USER;

$required_settings = array(
  'akindi_launch_url',
  'akindi_public_key',
  'akindi_secret_key',
  'akindi_instance_secret'
);

foreach ($required_settings as $s) {
  if ($CFG->{$s})
    continue;
  ?>
  <h2>Configuration Error</h2>
  <p>
    Akindi has not been properly configured. Please ask your administrator to
    set the <tt><?=$s?></tt> setting.
  </p>
  <?php
  echo $OUTPUT->footer();
  die();
}

$data_str = json_encode(array(
  'user'=>array(
    'id'=>$USER->id,
    'email'=>$USER->email,
    'first'=>$USER->firstname,
    'last'=>$USER->lastname,
    'key'=>ak_sign($CFG->akindi_instance_secret, $USER->id),
  ),
  'course'=>array(
    'id'=>$course->id,
    'label'=>$course->fullname,
    'name'=>$course->shortname,
  ),
));

$expires = time() + 60 * 60 * 4;
$to_sign = "$expires\n$data_str";

$signature = ak_sign($CFG->akindi_secret_key, $to_sign);

?>
<h2><?=get_string('launching', 'local_akindi')?>&hellip;</h2>

<form id="ak-launch-form" method="POST" action="<?=trim($CFG->akindi_launch_url)?>" target="_blank">
  <input type="hidden" name="public_key" value="<?=htmlspecialchars($CFG->akindi_public_key)?>" />
  <input type="hidden" name="signature" value="<?=htmlspecialchars($signature)?>" />
  <input type="hidden" name="expires" value="<?=htmlspecialchars($expires)?>" />
  <input type="hidden" name="data" value="<?=htmlspecialchars($data_str)?>" />
  <noscript>
    <input type="submit" value="<?=get_string('launching', 'local_akindi')?>" />
  </noscript>
</form>
<a href="<?=$CFG->wwwroot?>/course/view.php?id=<?=$id?>"><?=get_string('returntocourse', 'local_akindi')?></a>

<script>
    setTimeout(function(){
        document.getElementById("ak-launch-form").submit();
        setTimeout(function(){
            window.location = "<?=$CFG->wwwroot?>/course/view.php?id=<?=$id?>";
        }, 1);
    }, 1);
</script>

<?php

echo $OUTPUT->footer();

?>
