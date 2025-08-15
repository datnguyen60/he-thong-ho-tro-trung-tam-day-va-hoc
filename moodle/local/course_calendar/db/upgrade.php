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

    if ($oldversion < 2025072702) {
        // Define table local_course_calendar_absence_request to be created.
        $table = new xmldb_table('local_course_calendar_absence_request');

        // Define fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_section_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('teacher_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('reason', XMLDB_TYPE_CHAR, '1024', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('created_by_manager', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('approver_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('createdtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('modifiedtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Define keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('section_fk', XMLDB_KEY_FOREIGN, ['course_section_id'], 'local_course_calendar_course_section', ['id']);
        $table->add_key('teacher_fk', XMLDB_KEY_FOREIGN, ['teacher_id'], 'user', ['id']);
        $table->add_key('approver_fk', XMLDB_KEY_FOREIGN, ['approver_id'], 'user', ['id']);

        // Create table if not exists.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Save upgrade point.
        upgrade_plugin_savepoint(true, 2025072701, 'local', 'course_calendar');
    }

    if ($oldversion < 20250730002) {
        
        // Define table local_course_calendar_absence_request to be created.
        $table = new xmldb_table('local_course_calendar_absence_request');

        // Adding fields to table local_course_calendar_absence_request.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('teacher_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_section_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('reason', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('approver_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('createdtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('approvedtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_course_calendar_absence_request.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('teacher_fk', XMLDB_KEY_FOREIGN, ['teacher_id'], 'user', ['id']);
        // Note: course_section table may not exist, so we'll skip this foreign key for now
        // $table->add_key('course_section_fk', XMLDB_KEY_FOREIGN, ['course_section_id'], 'local_course_calendar_course_section', ['id']);
        $table->add_key('approver_fk', XMLDB_KEY_FOREIGN, ['approver_id'], 'user', ['id']);

        // Adding indexes to table local_course_calendar_absence_request.
        // Note: No need for teacher_id_idx since teacher_fk foreign key already creates an index
        $table->add_index('status_idx', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('createdtime_idx', XMLDB_INDEX_NOTUNIQUE, ['createdtime']);

        // Conditionally launch create table for local_course_calendar_absence_request.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // course_calendar savepoint reached.
        upgrade_plugin_savepoint(true, 20250730002, 'local', 'course_calendar');
    }

    if ($oldversion < 20250802001) {
        // Add fields to local_course_calendar_absence_request for makeup session support
        $table = new xmldb_table('local_course_calendar_absence_request');
        
        // Add request_type field (0 = absence, 1 = makeup) with constraint
        $field = new xmldb_field('request_type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'status');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            
            // Add check constraint to ensure request_type is 0 or 1
            // Note: This will be enforced at application level in Moodle
        }
        
        // Add original_absence_id for makeup requests (only for request_type = 1)
        $field = new xmldb_field('original_absence_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'request_type');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add makeup_room_id for makeup sessions (only for request_type = 1)
        $field = new xmldb_field('makeup_room_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'original_absence_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add makeup_date for makeup sessions (only for request_type = 1)
        $field = new xmldb_field('makeup_date', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'makeup_room_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add makeup_start_time for makeup sessions (only for request_type = 1)
        $field = new xmldb_field('makeup_start_time', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'makeup_date');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add makeup_end_time for makeup sessions (only for request_type = 1)
        $field = new xmldb_field('makeup_end_time', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'makeup_start_time');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add foreign keys with proper error handling
        try {
            // Add foreign key for original_absence_id (self-referencing)
            $key = new xmldb_key('original_absence_fk', XMLDB_KEY_FOREIGN, ['original_absence_id'], 'local_course_calendar_absence_request', ['id']);
            if (!$dbman->find_key_name($table, $key)) {
                $dbman->add_key($table, $key);
            }
        } catch (Exception $e) {
            // Log error but continue - foreign key can be added later
            error_log('Could not add original_absence_fk: ' . $e->getMessage());
        }
        
        try {
            // Add foreign key for makeup_room_id  
            $key = new xmldb_key('makeup_room_fk', XMLDB_KEY_FOREIGN, ['makeup_room_id'], 'local_course_calendar_course_room', ['id']);
            if (!$dbman->find_key_name($table, $key)) {
                $dbman->add_key($table, $key);
            }
        } catch (Exception $e) {
            // Log error but continue - foreign key can be added later
            error_log('Could not add makeup_room_fk: ' . $e->getMessage());
        }

        // Add indexes for better performance
        $index = new xmldb_index('request_type_idx', XMLDB_INDEX_NOTUNIQUE, ['request_type']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        $index = new xmldb_index('original_absence_idx', XMLDB_INDEX_NOTUNIQUE, ['original_absence_id']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // course_calendar savepoint reached.
        upgrade_plugin_savepoint(true, 20250802001, 'local', 'course_calendar');
    }

    if ($oldversion < 20250606018) {

        // Define field class_begin_time to be added to local_course_calendar_course_schedule.
        $table = new xmldb_table('local_course_calendar_course_section');
        // ban đầu nếu không có ai dạy thì là người admin. Hiện tại admin có id = 2. Nhưng để đảm bảo thì lần đầu nếu không có dữ liệu thì nó là 0  để biết đây là lỗi dữ liệu
        $field = new xmldb_field(
            'editing_teacher_primary_teacher',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            0,
        );

        // Conditionally launch add field class_begin_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field(
            'non_editing_teacher_secondary_teacher',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            0
        );

        // Conditionally launch add field class_begin_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Course_calendar savepoint reached.
        upgrade_plugin_savepoint(true, 20250606018, 'local', 'course_calendar');

    }

    if ($oldversion < 20250606019) {

        // Define key created_user_fk (foreign) to be added to local_course_calendar_course_section.
        $table = new xmldb_table('local_course_calendar_course_section');
        $key = new xmldb_key('editing_teacher_primary_teacher', XMLDB_KEY_FOREIGN, ['editing_teacher_primary_teacher'], 'user', ['id']);

        // Launch add key created_user_fk.
        $dbman->add_key($table, $key);

        $key = new xmldb_key('non_editing_teacher_secondary_teacher', XMLDB_KEY_FOREIGN, ['non_editing_teacher_secondary_teacher'], 'user', ['id']);

        // Launch add key created_user_fk.
        $dbman->add_key($table, $key);

        $index = new xmldb_index('editing_teacher_primary_teacher_idx', XMLDB_INDEX_NOTUNIQUE, ['editing_teacher_primary_teacher']);

        // Conditionally launch add index created_user_id_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('non_editing_teacher_secondary_teacher_idx', XMLDB_INDEX_NOTUNIQUE, ['non_editing_teacher_secondary_teacher']);

        // Conditionally launch add index created_user_id_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        // Course_calendar savepoint reached.
        upgrade_plugin_savepoint(true, 20250606019, 'local', 'course_calendar');
    }

    // Everything has succeeded to here. Return true.
    return true;
}
