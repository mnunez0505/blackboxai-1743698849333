-- Create Users Table
CREATE TABLE USERS (
    id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username VARCHAR2(50) NOT NULL UNIQUE,
    password VARCHAR2(255) NOT NULL,
    email VARCHAR2(100) NOT NULL UNIQUE,
    phone VARCHAR2(20),
    fullname VARCHAR2(100) NOT NULL,
    role VARCHAR2(20) DEFAULT 'employee' CHECK (role IN ('admin', 'supervisor', 'employee')),
    supervisor_id NUMBER,
    date_hire DATE NOT NULL,
    vacation_days NUMBER DEFAULT 0,
    reset_token VARCHAR2(64),
    reset_token_expiry TIMESTAMP,
    last_login TIMESTAMP,
    last_logout TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_supervisor FOREIGN KEY (supervisor_id) REFERENCES USERS(id)
);

-- Create Vacation Requests Table
CREATE TABLE VACATION_REQUESTS (
    id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    employee_id NUMBER NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_requested NUMBER NOT NULL,
    reason VARCHAR2(500) NOT NULL,
    status VARCHAR2(20) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
    supervisor_comment VARCHAR2(500),
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_employee FOREIGN KEY (employee_id) REFERENCES USERS(id)
);

-- Create System Logs Table
CREATE TABLE SYSTEM_LOGS (
    id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    log_type VARCHAR2(50) NOT NULL,
    message VARCHAR2(4000) NOT NULL,
    user_id NUMBER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_log FOREIGN KEY (user_id) REFERENCES USERS(id)
);

-- Create indexes for better performance
CREATE INDEX idx_users_username ON USERS(username);
CREATE INDEX idx_users_email ON USERS(email);
CREATE INDEX idx_users_supervisor ON USERS(supervisor_id);
CREATE INDEX idx_vacation_employee ON VACATION_REQUESTS(employee_id);
CREATE INDEX idx_vacation_status ON VACATION_REQUESTS(status);
CREATE INDEX idx_vacation_dates ON VACATION_REQUESTS(start_date, end_date);

-- Create trigger to update timestamp on Users table
CREATE OR REPLACE TRIGGER users_update_trigger
BEFORE UPDATE ON USERS
FOR EACH ROW
BEGIN
    :NEW.updated_at := CURRENT_TIMESTAMP;
END;
/

-- Create trigger to update timestamp on Vacation Requests table
CREATE OR REPLACE TRIGGER vacation_requests_update_trigger
BEFORE UPDATE ON VACATION_REQUESTS
FOR EACH ROW
BEGIN
    :NEW.updated_at := CURRENT_TIMESTAMP;
END;
/

-- Create procedure to log system events
CREATE OR REPLACE PROCEDURE log_system_event(
    p_log_type IN VARCHAR2,
    p_message IN VARCHAR2,
    p_user_id IN NUMBER DEFAULT NULL
)
AS
BEGIN
    INSERT INTO SYSTEM_LOGS (log_type, message, user_id)
    VALUES (p_log_type, p_message, p_user_id);
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END;
/

-- Create function to calculate vacation days between dates
CREATE OR REPLACE FUNCTION calculate_vacation_days(
    p_start_date IN DATE,
    p_end_date IN DATE
) RETURN NUMBER
AS
    v_days NUMBER;
BEGIN
    v_days := p_end_date - p_start_date + 1;
    RETURN v_days;
END;
/

-- Insert default admin user (password: Admin123!)
INSERT INTO USERS (
    username,
    password,
    email,
    phone,
    fullname,
    role,
    date_hire,
    vacation_days
) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- hashed password: Admin123!
    'admin@yourdomain.com',
    '1234567890',
    'System Administrator',
    'admin',
    CURRENT_DATE,
    0
);

-- Create view for employee vacation summary
CREATE OR REPLACE VIEW employee_vacation_summary AS
SELECT 
    u.id,
    u.fullname,
    u.email,
    u.vacation_days as available_days,
    COUNT(CASE WHEN vr.status = 'pending' THEN 1 END) as pending_requests,
    COUNT(CASE WHEN vr.status = 'approved' THEN 1 END) as approved_requests,
    SUM(CASE WHEN vr.status = 'approved' THEN vr.days_requested ELSE 0 END) as used_days
FROM USERS u
LEFT JOIN VACATION_REQUESTS vr ON u.id = vr.employee_id
WHERE u.role != 'admin'
GROUP BY u.id, u.fullname, u.email, u.vacation_days;

-- Create view for supervisor dashboard
CREATE OR REPLACE VIEW supervisor_dashboard AS
SELECT 
    s.id as supervisor_id,
    s.fullname as supervisor_name,
    COUNT(DISTINCT e.id) as total_employees,
    COUNT(CASE WHEN vr.status = 'pending' THEN 1 END) as pending_requests,
    COUNT(CASE WHEN vr.status = 'approved' THEN 1 END) as approved_requests,
    COUNT(CASE WHEN vr.status = 'rejected' THEN 1 END) as rejected_requests
FROM USERS s
LEFT JOIN USERS e ON s.id = e.supervisor_id
LEFT JOIN VACATION_REQUESTS vr ON e.id = vr.employee_id
WHERE s.role = 'supervisor'
GROUP BY s.id, s.fullname;

-- Grant necessary permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON USERS TO your_app_user;
GRANT SELECT, INSERT, UPDATE, DELETE ON VACATION_REQUESTS TO your_app_user;
GRANT SELECT, INSERT ON SYSTEM_LOGS TO your_app_user;
GRANT EXECUTE ON calculate_vacation_days TO your_app_user;
GRANT EXECUTE ON log_system_event TO your_app_user;

COMMIT;