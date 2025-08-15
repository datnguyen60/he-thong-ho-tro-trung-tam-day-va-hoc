# Đề xuất cải tiến hệ thống Course Calendar

## 1. Cải tiến bảng course_section để hỗ trợ nghỉ từng tiết

### Thêm bảng course_section_periods (Chi tiết tiết học)
```sql
CREATE TABLE mdl_local_course_calendar_section_periods (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    course_section_id BIGINT NOT NULL,
    period_number INT NOT NULL, -- Tiết thứ mấy (1, 2, 3...)
    period_begin_time BIGINT NOT NULL,
    period_end_time BIGINT NOT NULL,
    is_cancelled TINYINT(1) DEFAULT 0,
    cancel_reason VARCHAR(1024) NULL,
    cancelled_by BIGINT NULL,
    cancelled_time BIGINT NULL,
    FOREIGN KEY (course_section_id) REFERENCES mdl_local_course_calendar_course_section(id),
    FOREIGN KEY (cancelled_by) REFERENCES mdl_user(id)
);
```

## 2. Cải tiến absence_request để hỗ trợ nghỉ từng tiết

### Sửa bảng absence_request
```sql
ALTER TABLE mdl_local_course_calendar_absence_request ADD COLUMN period_ids TEXT NULL; -- JSON array của period IDs
ALTER TABLE mdl_local_course_calendar_absence_request ADD COLUMN absence_type ENUM('full_session', 'partial_periods') DEFAULT 'full_session';
```

## 3. Thêm bảng makeup_sessions (Buổi bù)

```sql
CREATE TABLE mdl_local_course_calendar_makeup_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    original_section_id BIGINT NOT NULL, -- Buổi gốc bị nghỉ
    makeup_section_id BIGINT NOT NULL,   -- Buổi bù
    teacher_id BIGINT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_time BIGINT NOT NULL,
    approved_by BIGINT NULL,
    approved_time BIGINT NULL,
    notes TEXT NULL,
    FOREIGN KEY (original_section_id) REFERENCES mdl_local_course_calendar_course_section(id),
    FOREIGN KEY (makeup_section_id) REFERENCES mdl_local_course_calendar_course_section(id),
    FOREIGN KEY (teacher_id) REFERENCES mdl_user(id),
    FOREIGN KEY (approved_by) REFERENCES mdl_user(id)
);
```

## 4. Thêm bảng teacher_statistics (Thống kê giảng viên)

```sql
CREATE TABLE mdl_local_course_calendar_teacher_statistics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    teacher_id BIGINT NOT NULL,
    course_id BIGINT NOT NULL,
    month_year VARCHAR(7) NOT NULL, -- Format: 2025-08
    total_sessions INT DEFAULT 0,
    attended_sessions INT DEFAULT 0,
    absent_sessions INT DEFAULT 0,
    makeup_sessions INT DEFAULT 0,
    late_sessions INT DEFAULT 0,
    updated_time BIGINT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES mdl_user(id),
    FOREIGN KEY (course_id) REFERENCES mdl_course(id),
    UNIQUE KEY unique_teacher_course_month (teacher_id, course_id, month_year)
);
```

## 5. Thêm bảng course_teacher_history (Lịch sử giảng viên)

```sql
CREATE TABLE mdl_local_course_calendar_teacher_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    teacher_id BIGINT NOT NULL,
    course_id BIGINT NOT NULL,
    role_id BIGINT NOT NULL,
    start_date BIGINT NOT NULL,
    end_date BIGINT NULL, -- NULL = vẫn đang active
    status ENUM('active', 'inactive', 'transferred') DEFAULT 'active',
    created_time BIGINT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES mdl_user(id),
    FOREIGN KEY (course_id) REFERENCES mdl_course(id),
    FOREIGN KEY (role_id) REFERENCES mdl_role(id)
);
```

## 6. Logic cải tiến cho các trường hợp

### A. Nghỉ từng tiết:
1. Giảng viên chọn buổi học
2. Hiển thị các tiết trong buổi đó
3. Cho phép chọn tiết nào muốn nghỉ
4. Lưu vào absence_request với period_ids và absence_type = 'partial_periods'

### B. Buổi bù:
1. Khi tạo absence_request, có option "Đăng ký buổi bù"
2. Tạo record trong makeup_sessions
3. Manager approve cả absence và makeup cùng lúc

### C. Giảng viên rời course:
1. Cập nhật teacher_history với end_date
2. Course sections cũ vẫn giữ nguyên cho lịch sử
3. Thêm warning khi hiển thị lịch của giảng viên không còn active

### D. Thống kê:
1. Trigger/cron job cập nhật teacher_statistics hàng tháng
2. Dashboard hiển thị thống kê theo tháng/quarter
3. Export báo cáo Excel/PDF

## 7. API endpoints mới cần thêm

### Periods Management:
- GET /local/course_calendar/api/get_session_periods.php
- POST /local/course_calendar/api/cancel_periods.php

### Makeup Sessions:
- POST /local/course_calendar/api/request_makeup.php
- GET /local/course_calendar/api/get_makeup_requests.php
- POST /local/course_calendar/api/approve_makeup.php

### Statistics:
- GET /local/course_calendar/api/get_teacher_stats.php
- GET /local/course_calendar/api/export_stats.php

### Teacher Management:
- GET /local/course_calendar/api/get_active_teachers.php
- POST /local/course_calendar/api/transfer_teacher.php

## 8. UI/UX Improvements

### Absence Request Form:
- Radio button: "Nghỉ cả buổi" / "Nghỉ từng tiết"
- Nếu chọn "từng tiết": Hiển thị checkbox list các tiết
- Checkbox: "Đăng ký buổi bù"
- Nếu có buổi bù: Date/time picker cho buổi bù

### Manager Dashboard:
- Tab "Absence Requests"
- Tab "Makeup Requests" 
- Tab "Teacher Statistics"
- Tab "Inactive Teachers"

### Teacher Profile:
- Hiển thị status: Active/Inactive trong course
- Link "Transfer to another course"
- Monthly statistics chart
