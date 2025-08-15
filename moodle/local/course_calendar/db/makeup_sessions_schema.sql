-- SQL to add makeup sessions table to the existing schema

-- Create makeup sessions table
CREATE TABLE mdl_local_course_calendar_makeup_sessions (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    original_section_id BIGINT(10) NOT NULL COMMENT 'Original cancelled section',
    teacher_id BIGINT(10) NOT NULL COMMENT 'Teacher requesting makeup',
    course_id BIGINT(10) NOT NULL COMMENT 'Course ID',
    room_id BIGINT(10) NOT NULL COMMENT 'Requested room for makeup',
    requested_begin_time BIGINT(10) NOT NULL COMMENT 'Requested start time',
    requested_end_time BIGINT(10) NOT NULL COMMENT 'Requested end time',
    reason TEXT NOT NULL COMMENT 'Reason for makeup session',
    status TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=approved, 2=rejected',
    approved_by BIGINT(10) NULL COMMENT 'Manager who approved/rejected',
    created_time BIGINT(10) NOT NULL,
    modified_time BIGINT(10) NOT NULL,
    approved_time BIGINT(10) NULL,
    notes TEXT NULL COMMENT 'Additional notes from manager',
    PRIMARY KEY (id),
    FOREIGN KEY (original_section_id) REFERENCES mdl_local_course_calendar_course_section(id),
    FOREIGN KEY (teacher_id) REFERENCES mdl_user(id),
    FOREIGN KEY (course_id) REFERENCES mdl_course(id),
    FOREIGN KEY (room_id) REFERENCES mdl_local_course_calendar_course_room(id),
    FOREIGN KEY (approved_by) REFERENCES mdl_user(id),
    INDEX status_idx (status),
    INDEX teacher_idx (teacher_id),
    INDEX course_idx (course_id),
    INDEX created_time_idx (created_time)
);
