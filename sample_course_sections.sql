-- Sample data for course sections (1 week schedule)
-- Course IDs: 2, 3, 4
-- User IDs: 2, 3
-- Room IDs: 1-20
-- Duration: 2-3 hours per session
-- No overlapping sessions

INSERT INTO mdl_local_course_calendar_course_section 
(courseid, created_user_id, modified_user_id, course_room_id, createdtime, modifiedtime, class_begin_time, class_end_time, class_total_sessions, reason, is_cancel, is_makeup, is_accepted, visible)
VALUES

-- Monday (August 4, 2025)
-- Morning slots: 8:00-11:00, 11:30-14:30
-- Afternoon slots: 15:00-18:00, 18:30-21:30

-- Monday Morning
(2, 2, NULL, 1, 1754071016, 1754071016, 1754280000, 1754290800, 1, NULL, 0, 0, 1, 1),  -- 8:00-11:00 Course 2, Teacher 2, Room 1
(3, 3, NULL, 2, 1754071016, 1754071016, 1754280000, 1754290800, 1, NULL, 0, 0, 1, 1),  -- 8:00-11:00 Course 3, Teacher 3, Room 2
(4, 2, NULL, 3, 1754071016, 1754071016, 1754292600, 1754303400, 1, NULL, 0, 0, 1, 1),  -- 11:30-14:30 Course 4, Teacher 2, Room 3

-- Monday Afternoon
(2, 3, NULL, 4, 1754071016, 1754071016, 1754305200, 1754316000, 1, NULL, 0, 0, 1, 1),  -- 15:00-18:00 Course 2, Teacher 3, Room 4
(3, 2, NULL, 5, 1754071016, 1754071016, 1754317800, 1754328600, 1, NULL, 0, 0, 1, 1),  -- 18:30-21:30 Course 3, Teacher 2, Room 5

-- Tuesday (August 5, 2025)
(4, 3, NULL, 6, 1754071016, 1754071016, 1754366400, 1754377200, 1, NULL, 0, 0, 1, 1),  -- 8:00-11:00 Course 4, Teacher 3, Room 6
(2, 2, NULL, 7, 1754071016, 1754071016, 1754379000, 1754387200, 1, NULL, 0, 0, 1, 1),  -- 11:30-14:00 Course 2, Teacher 2, Room 7
(3, 3, NULL, 8, 1754071016, 1754071016, 1754391600, 1754402400, 1, NULL, 0, 0, 1, 1),  -- 15:00-18:00 Course 3, Teacher 3, Room 8
(4, 2, NULL, 9, 1754071016, 1754071016, 1754404200, 1754413200, 1, NULL, 0, 0, 1, 1),  -- 18:30-21:00 Course 4, Teacher 2, Room 9

-- Wednesday (August 6, 2025)
(2, 3, NULL, 10, 1754071016, 1754071016, 1754452800, 1754463600, 1, NULL, 0, 0, 1, 1), -- 8:00-11:00 Course 2, Teacher 3, Room 10
(3, 2, NULL, 11, 1754071016, 1754071016, 1754465400, 1754474400, 1, NULL, 0, 0, 1, 1), -- 11:30-14:00 Course 3, Teacher 2, Room 11
(4, 3, NULL, 12, 1754071016, 1754071016, 1754478000, 1754488800, 1, NULL, 0, 0, 1, 1), -- 15:00-18:00 Course 4, Teacher 3, Room 12
(2, 2, NULL, 13, 1754071016, 1754071016, 1754490600, 1754501400, 1, NULL, 0, 0, 1, 1), -- 18:30-21:30 Course 2, Teacher 2, Room 13

-- Thursday (August 7, 2025)
(3, 3, NULL, 14, 1754071016, 1754071016, 1754539200, 1754550000, 1, NULL, 0, 0, 1, 1), -- 8:00-11:00 Course 3, Teacher 3, Room 14
(4, 2, NULL, 15, 1754071016, 1754071016, 1754551800, 1754560800, 1, NULL, 0, 0, 1, 1), -- 11:30-14:00 Course 4, Teacher 2, Room 15
(2, 3, NULL, 16, 1754071016, 1754071016, 1754564400, 1754575200, 1, NULL, 0, 0, 1, 1), -- 15:00-18:00 Course 2, Teacher 3, Room 16
(3, 2, NULL, 17, 1754071016, 1754071016, 1754577000, 1754587800, 1, NULL, 0, 0, 1, 1), -- 18:30-21:30 Course 3, Teacher 2, Room 17

-- Friday (August 8, 2025)
(4, 3, NULL, 18, 1754071016, 1754071016, 1754625600, 1754636400, 1, NULL, 0, 0, 1, 1), -- 8:00-11:00 Course 4, Teacher 3, Room 18
(2, 2, NULL, 19, 1754071016, 1754071016, 1754638200, 1754647200, 1, NULL, 0, 0, 1, 1), -- 11:30-14:00 Course 2, Teacher 2, Room 19
(3, 3, NULL, 20, 1754071016, 1754071016, 1754650800, 1754661600, 1, NULL, 0, 0, 1, 1), -- 15:00-18:00 Course 3, Teacher 3, Room 20
(4, 2, NULL, 1, 1754071016, 1754071016, 1754663400, 1754674200, 1, NULL, 0, 0, 1, 1),  -- 18:30-21:30 Course 4, Teacher 2, Room 1

-- Saturday (August 9, 2025) - Weekend sessions
(2, 3, NULL, 2, 1754071016, 1754071016, 1754712000, 1754722800, 1, NULL, 0, 0, 1, 1),  -- 8:00-11:00 Course 2, Teacher 3, Room 2
(3, 2, NULL, 3, 1754071016, 1754071016, 1754724600, 1754733600, 1, NULL, 0, 0, 1, 1),  -- 11:30-14:00 Course 3, Teacher 2, Room 3
(4, 3, NULL, 4, 1754071016, 1754071016, 1754737200, 1754748000, 1, NULL, 0, 0, 1, 1),  -- 15:00-18:00 Course 4, Teacher 3, Room 4
(2, 2, NULL, 5, 1754071016, 1754071016, 1754749800, 1754760600, 1, NULL, 0, 0, 1, 1),  -- 18:30-21:30 Course 2, Teacher 2, Room 5

-- Sunday (August 10, 2025) - Weekend sessions
(3, 3, NULL, 6, 1754071016, 1754071016, 1754798400, 1754809200, 1, NULL, 0, 0, 1, 1),  -- 8:00-11:00 Course 3, Teacher 3, Room 6
(4, 2, NULL, 7, 1754071016, 1754071016, 1754811000, 1754820000, 1, NULL, 0, 0, 1, 1),  -- 11:30-14:00 Course 4, Teacher 2, Room 7
(2, 3, NULL, 8, 1754071016, 1754071016, 1754823600, 1754834400, 1, NULL, 0, 0, 1, 1),  -- 15:00-18:00 Course 2, Teacher 3, Room 8
(3, 2, NULL, 9, 1754071016, 1754071016, 1754836200, 1754845200, 1, NULL, 0, 0, 1, 1);  -- 18:30-21:00 Course 3, Teacher 2, Room 9

-- Additional sessions for more variety (Week 2 preview)
INSERT INTO mdl_local_course_calendar_course_section 
(courseid, created_user_id, modified_user_id, course_room_id, createdtime, modifiedtime, class_begin_time, class_end_time, class_total_sessions, reason, is_cancel, is_makeup, is_accepted, visible)
VALUES

-- Monday Week 2 (August 11, 2025)
(4, 3, NULL, 10, 1754071016, 1754071016, 1754884800, 1754895600, 1, NULL, 0, 0, 1, 1), -- 8:00-11:00 Course 4, Teacher 3, Room 10
(2, 2, NULL, 11, 1754071016, 1754071016, 1754897400, 1754906400, 1, NULL, 0, 0, 1, 1), -- 11:30-14:00 Course 2, Teacher 2, Room 11
(3, 3, NULL, 12, 1754071016, 1754071016, 1754910000, 1754920800, 1, NULL, 0, 0, 1, 1), -- 15:00-18:00 Course 3, Teacher 3, Room 12
(4, 2, NULL, 13, 1754071016, 1754071016, 1754922600, 1754933400, 1, NULL, 0, 0, 1, 1), -- 18:30-21:30 Course 4, Teacher 2, Room 13

-- Tuesday Week 2 (August 12, 2025)
(2, 3, NULL, 14, 1754071016, 1754071016, 1754971200, 1754982000, 1, NULL, 0, 0, 1, 1), -- 8:00-11:00 Course 2, Teacher 3, Room 14
(3, 2, NULL, 15, 1754071016, 1754071016, 1754983800, 1754994600, 1, NULL, 0, 0, 1, 1), -- 11:30-14:30 Course 3, Teacher 2, Room 15
(4, 3, NULL, 16, 1754071016, 1754071016, 1754996400, 1755005400, 1, NULL, 0, 0, 1, 1), -- 15:00-17:30 Course 4, Teacher 3, Room 16
(2, 2, NULL, 17, 1754071016, 1754071016, 1755007200, 1755018000, 1, NULL, 0, 0, 1, 1); -- 18:00-21:00 Course 2, Teacher 2, Room 17
