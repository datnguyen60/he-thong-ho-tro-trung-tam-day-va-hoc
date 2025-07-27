<?php

function xmldb_local_course_calendar_upgrade($oldversion): bool
{
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 20250606010) {

        // Define table local_course_calendar_holiday to be created.
        $table = new xmldb_table('local_course_calendar_holiday');

        // Adding fields to table local_course_calendar_holiday.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('created_user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('modified_user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('createdtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('modifiedtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('holiday', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_course_calendar_holiday.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('created_user_fk', XMLDB_KEY_FOREIGN, ['created_user_id'], 'user', ['id']);
        $table->add_key('modified_user_fk', XMLDB_KEY_FOREIGN, ['modified_user_id'], 'user', ['id']);

        // Adding indexes to table local_course_calendar_holiday.
        $table->add_index('holiday_idx', XMLDB_INDEX_UNIQUE, ['holiday']);

        // Conditionally launch create table for local_course_calendar_holiday.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Course_calendar savepoint reached.
        upgrade_plugin_savepoint(true, 20250606010, 'local', 'course_calendar');
    }

    if ($oldversion < 20250606011) {

        // Define table local_course_calendar_course_config_for_calendar to be created.
        $table = new xmldb_table('local_course_calendar_course_config_for_calendar');

        // Adding fields to table local_course_calendar_course_config_for_calendar.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('class_duration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 2);
        $table->add_field('number_course_session_weekly', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 2);
        $table->add_field('number_student_on_course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 25);
        $table->add_field('created_user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('modified_user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('createdtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('modifiedtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_course_calendar_course_config_for_calendar.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('created_user_fk', XMLDB_KEY_FOREIGN, ['created_user_id'], 'user', ['id']);
        $table->add_key('modified_user_fk', XMLDB_KEY_FOREIGN, ['modified_user_id'], 'user', ['id']);

        // Conditionally launch create table for local_course_calendar_course_config_for_calendar.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Course_calendar savepoint reached.
        upgrade_plugin_savepoint(true, 20250606011, 'local', 'course_calendar');
    }

    if ($oldversion < 20250606015) {

        // Define field class_begin_time to be added to local_course_calendar_course_schedule.
        $table = new xmldb_table('local_course_calendar_course_section');
        $field = new xmldb_field('class_begin_time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'modifiedtime');

        // Conditionally launch add field class_begin_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('class_end_time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'class_begin_time');

        // Conditionally launch add field class_end_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('class_total_sessions', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'class_end_time');

        // Conditionally launch add field class_total_sessions.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('reason', XMLDB_TYPE_CHAR, '1024', null, null, null, null, 'class_total_sessions');

        // Conditionally launch add field reason.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('is_cancel', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'reason');

        // Conditionally launch add field is_cancel.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('is_makeup', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'is_cancel');

        // Conditionally launch add field is_makeup.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('is_accepted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'is_makeup');

        // Conditionally launch add field is_accepted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('local_course_calendar_course_schedule');
        // Conditionally launch drop table for local_course_calendar_course_schedule.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Course_calendar savepoint reached.
        upgrade_plugin_savepoint(true, 20250606015, 'local', 'course_calendar');

    }

    if ($oldversion < 20250606017) {

        // Define field course_schedule_id to be dropped from local_course_calendar_course_section.
        // Define index course_schedule_id (not unique) to be dropped form local_course_calendar_course_section.
        $table = new xmldb_table('local_course_calendar_course_section');
        $index = new xmldb_index('course_schedule_id', XMLDB_INDEX_NOTUNIQUE, ['course_schedule_id']);

        // Conditionally launch drop index course_schedule_id.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define key course_schedule_fk (foreign) to be dropped form local_course_calendar_course_section.
        $key = new xmldb_key('course_schedule_fk', XMLDB_KEY_FOREIGN, ['course_schedule_id'], 'local_course_calendar_course_schedule', ['id']);

        // Launch drop key course_schedule_fk.
        $dbman->drop_key($table, $key);
        $field = new xmldb_field('course_schedule_id');

        // Conditionally launch drop field course_schedule_id.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Course_calendar savepoint reached.
        upgrade_plugin_savepoint(true, 20250606017, 'local', 'course_calendar');
    }

    if ($oldversion < 2025072701) {

        // Define table.
        $table = new xmldb_table('local_course_calendar_course_section');

        // Define field to change.
        $field = new xmldb_field('createdtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, false, '0', 'sectionname');

        // Apply the change if the field exists.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        // Savepoint.
        upgrade_plugin_savepoint(true, 2025072701, 'local', 'course_calendar');
    }
    // Everything has succeeded to here. Return true.
    return true;
}
