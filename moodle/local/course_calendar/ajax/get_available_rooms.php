<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

// Set JSON header
header('Content-Type: application/json');

global $DB;

try {
    // Validate parameters
    $start_time = required_param('start_time', PARAM_INT);
    $end_time = required_param('end_time', PARAM_INT);
    $mode = optional_param('mode', 'exact', PARAM_ALPHA); // 'exact' or 'range'
    $check_teacher = optional_param('check_teacher', 0, PARAM_INT); // Teacher ID to check conflicts
    
    if (!confirm_sesskey()) {
        throw new Exception('Invalid session key');
    }
    
    if ($end_time <= $start_time) {
        throw new Exception('End time must be after start time');
    }
    
    if ($start_time <= time()) {
        throw new Exception('Time must be in the future');
    }
    
    // Check teacher availability if teacher ID provided
    if ($check_teacher > 0) {
        $teacher_conflict = check_teacher_availability_range($check_teacher, $start_time, $end_time);
        if ($teacher_conflict) {
            // Return empty rooms if teacher has conflict
            echo json_encode([
                'mode' => $mode,
                'rooms' => [],
                'teacher_conflict' => $teacher_conflict
            ]);
            exit;
        }
    }
    
    // Get all rooms
    $all_rooms = $DB->get_records('local_course_calendar_course_room', null, 'room_building, room_floor, room_number');
    
    $available_rooms = [];
    
    if ($mode === 'range') {
        // Mode 1: Show available time slots within the range for each room
        foreach ($all_rooms as $room) {
            $room_data = [
                'id' => $room->id,
                'room_building' => $room->room_building,
                'room_floor' => $room->room_floor, 
                'room_number' => $room->room_number,
                'capacity' => $room->capacity ?? null,
                'available_slots' => get_available_time_slots($room->id, $start_time, $end_time)
            ];
            $available_rooms[] = $room_data;
        }
    } else {
        // Mode 2: Show only rooms completely available for the exact time slot
        foreach ($all_rooms as $room) {
            if (is_room_available($room->id, $start_time, $end_time)) {
                $available_rooms[] = [
                    'id' => $room->id,
                    'room_building' => $room->room_building,
                    'room_floor' => $room->room_floor, 
                    'room_number' => $room->room_number,
                    'capacity' => $room->capacity ?? null
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'rooms' => $available_rooms,
        'total' => count($available_rooms),
        'mode' => $mode
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'rooms' => [],
        'mode' => $mode ?? 'exact'
    ]);
}

/**
 * Get available time slots for a room within a time range
 */
function get_available_time_slots($room_id, $range_start, $range_end) {
    global $DB;
    
    // Get all conflicts in the range
    $conflicts = [];
    
    // Get course section conflicts
    $section_conflicts = $DB->get_records_sql("
        SELECT cs.class_begin_time, cs.class_end_time, c.fullname as course_name
        FROM {local_course_calendar_course_section} cs  
        JOIN {course} c ON c.id = cs.courseid
        WHERE cs.course_room_id = :room_id 
        AND cs.visible = 1 
        AND cs.is_cancel = 0
        AND cs.class_end_time > :range_start
        AND cs.class_begin_time < :range_end
        ORDER BY cs.class_begin_time",
        [
            'room_id' => $room_id,
            'range_start' => $range_start,
            'range_end' => $range_end
        ]);
    
    foreach ($section_conflicts as $conflict) {
        $conflicts[] = [
            'start' => max($conflict->class_begin_time, $range_start),
            'end' => min($conflict->class_end_time, $range_end),
            'type' => 'class',
            'name' => $conflict->course_name
        ];
    }
    
    // Get makeup session conflicts
    $makeup_conflicts = $DB->get_records_sql("
        SELECT ar.makeup_start_time, ar.makeup_end_time, c.fullname as course_name
        FROM {local_course_calendar_absence_request} ar
        JOIN {local_course_calendar_course_section} cs ON cs.id = ar.course_section_id
        JOIN {course} c ON c.id = cs.courseid
        WHERE ar.request_type = 1 
        AND ar.makeup_room_id = :room_id 
        AND ar.status = 1
        AND ar.makeup_end_time > :range_start
        AND ar.makeup_start_time < :range_end
        ORDER BY ar.makeup_start_time",
        [
            'room_id' => $room_id,
            'range_start' => $range_start,
            'range_end' => $range_end
        ]);
    
    foreach ($makeup_conflicts as $conflict) {
        $conflicts[] = [
            'start' => max($conflict->makeup_start_time, $range_start),
            'end' => min($conflict->makeup_end_time, $range_end),
            'type' => 'makeup',
            'name' => $conflict->course_name . ' (Makeup)'
        ];
    }
    
    // Sort conflicts by start time
    usort($conflicts, function($a, $b) {
        return $a['start'] - $b['start'];
    });
    
    // Calculate available slots
    $available_slots = [];
    $current_time = $range_start;
    
    foreach ($conflicts as $conflict) {
        // If there's a gap before this conflict, it's available
        if ($current_time < $conflict['start']) {
            $available_slots[] = [
                'start' => $current_time,
                'end' => $conflict['start'],
                'duration_hours' => round(($conflict['start'] - $current_time) / 3600, 1)
            ];
        }
        
        // Move current time to end of conflict (or keep it if conflict ends before current time)
        $current_time = max($current_time, $conflict['end']);
    }
    
    // Check if there's available time after all conflicts
    if ($current_time < $range_end) {
        $available_slots[] = [
            'start' => $current_time,
            'end' => $range_end,
            'duration_hours' => round(($range_end - $current_time) / 3600, 1)
        ];
    }
    
    return [
        'conflicts' => $conflicts,
        'available_slots' => $available_slots,
        'total_available_hours' => array_sum(array_column($available_slots, 'duration_hours'))
    ];
}

/**
 * Enhanced room availability check
 */
function is_room_available($room_id, $start_time, $end_time) {
    global $DB;
    
    // Check existing course sections using this room
    $section_conflicts = $DB->get_records_sql("
        SELECT cs.id
        FROM {local_course_calendar_course_section} cs  
        WHERE cs.course_room_id = :room_id 
        AND cs.visible = 1 
        AND cs.is_cancel = 0
        AND cs.class_begin_time < :end_time
        AND cs.class_end_time > :start_time",
        [
            'room_id' => $room_id,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);
    
    if (!empty($section_conflicts)) {
        return false;
    }
    
    // Check approved makeup sessions using this room
    $makeup_conflicts = $DB->get_records_sql("
        SELECT ar.id
        FROM {local_course_calendar_absence_request} ar
        WHERE ar.request_type = 1 
        AND ar.makeup_room_id = :room_id 
        AND ar.status = 1
        AND ar.makeup_start_time < :end_time
        AND ar.makeup_end_time > :start_time",
        [
            'room_id' => $room_id,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);
    
    if (!empty($makeup_conflicts)) {
        return false;
    }
    
    return true;
}

/**
 * Check if teacher is available at given time range
 */
function check_teacher_availability_range($teacher_id, $start_time, $end_time) {
    global $DB;
    
    // Check existing course sections where teacher is enrolled
    $conflicts = $DB->get_records_sql("
        SELECT cs.id, c.fullname, cs.class_begin_time, cs.class_end_time
        FROM {local_course_calendar_course_section} cs
        JOIN {course} c ON c.id = cs.courseid
        JOIN {enrol} e ON e.courseid = c.id
        JOIN {user_enrolments} ue ON ue.enrolid = e.id
        WHERE ue.userid = :teacher_id 
        AND cs.visible = 1 
        AND cs.is_cancel = 0
        AND cs.class_begin_time < :end_time
        AND cs.class_end_time > :start_time",
        [
            'teacher_id' => $teacher_id,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);
    
    if (!empty($conflicts)) {
        $conflict = reset($conflicts);
        return $conflict->fullname . ' (' . date('Y-m-d H:i', $conflict->class_begin_time) . 
               ' - ' . date('H:i', $conflict->class_end_time) . ')';
    }
    
    return false;
}
?>
