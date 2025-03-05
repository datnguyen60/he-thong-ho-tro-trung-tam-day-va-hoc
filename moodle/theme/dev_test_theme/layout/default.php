<?php
defined('MOODLE_INTERNAL') || die();
echo $OUTPUT->doctype();
?>
<html>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <?php echo $OUTPUT->standard_head_html(); ?>
</head>
<body>
    <div class="content">
        <?php echo $OUTPUT->main_content(); ?>
    </div>
    <?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>
