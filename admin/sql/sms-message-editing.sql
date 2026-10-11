-- Existing installations only; fresh installations use sms-approval.sql.
ALTER TABLE sms_approval_requests
 ADD COLUMN current_fingerprint CHAR(64) DEFAULT NULL,
 ADD UNIQUE KEY unique_current_message (current_fingerprint);
UPDATE sms_approval_requests SET current_fingerprint=fingerprint;
