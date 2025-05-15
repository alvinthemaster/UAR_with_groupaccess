-- Table for main group access request information
CREATE TABLE IF NOT EXISTS `group_access_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestor_name` varchar(255) NOT NULL,
  `business_unit` varchar(255) DEFAULT NULL,
  `access_request_number` varchar(50) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `request_date` varchar(100) DEFAULT NULL,
  `submission_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected','canceled') DEFAULT 'pending',
  `approver_comments` text DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access_request_number` (`access_request_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for detailed user access information in a group request
CREATE TABLE IF NOT EXISTS `group_access_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_request_id` int(11) NOT NULL,
  `application_system` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `access_type` varchar(50) DEFAULT NULL,
  `access_duration` enum('Permanent','Temporary') DEFAULT 'Permanent',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `date_needed` date DEFAULT NULL,
  `justification` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `group_request_id` (`group_request_id`),
  CONSTRAINT `group_access_details_ibfk_1` FOREIGN KEY (`group_request_id`) REFERENCES `group_access_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `employee_email` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 