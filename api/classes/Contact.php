<?php
/**
 * Mason Construction Services Inc.
 * Contact Form Handler Class
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

class Contact {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Submit a new contact form entry
     */
    public function submit($data) {
        $name    = sanitize($data['name'] ?? '');
        $email   = sanitize($data['email'] ?? '');
        $phone   = sanitize($data['phone'] ?? '');
        $subject = sanitize($data['subject'] ?? 'General Inquiry');
        $message = sanitize($data['message'] ?? '');
        $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Validation
        if (strlen($name) < 2 || strlen($name) > 100) {
            return ['success' => false, 'message' => 'Name must be between 2 and 100 characters.'];
        }
        if (!validateEmail($email)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }
        if (strlen($message) < 10) {
            return ['success' => false, 'message' => 'Message must be at least 10 characters.'];
        }

        // Rate limiting: max 3 submissions per IP per hour
        $recent = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM contact_submissions
             WHERE ip_address = ? AND submitted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
            [$ip]
        );
        if ($recent && $recent['cnt'] >= 3) {
            return ['success' => false, 'message' => 'Too many submissions. Please try again later.'];
        }

        try {
            $this->db->query(
                'INSERT INTO contact_submissions (name, email, phone, subject, message, ip_address, status, submitted_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                [$name, $email, $phone, $subject, $message, $ip, 'new']
            );
            $insertId = $this->db->lastInsertId();

            // Send email notifications
            $this->sendConfirmationEmail($name, $email);
            $this->sendAdminNotification($name, $email, $phone, $subject, $message);

            return ['success' => true, 'message' => 'Thank you! Your message has been received. We\'ll be in touch soon.', 'id' => $insertId];
        } catch (PDOException $e) {
            error_log('Contact submit error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to save your message. Please try again.'];
        }
    }

    /**
     * Get all contact submissions (for admin)
     */
    public function getAll($filters = []) {
        $sql    = 'SELECT * FROM contact_submissions';
        $params = [];

        $conditions = [];
        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[]     = sanitize($filters['status']);
        }
        if (!empty($filters['search'])) {
            $conditions[] = '(name LIKE ? OR email LIKE ? OR message LIKE ?)';
            $term         = '%' . sanitize($filters['search']) . '%';
            $params[]     = $term;
            $params[]     = $term;
            $params[]     = $term;
        }
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY submitted_at DESC';

        $limit  = isset($filters['limit']) ? (int)$filters['limit'] : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
        $sql   .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (PDOException $e) {
            error_log('Contact getAll error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count of submissions (for pagination)
     */
    public function getCount($filters = []) {
        $sql    = 'SELECT COUNT(*) as total FROM contact_submissions';
        $params = [];

        $conditions = [];
        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[]     = sanitize($filters['status']);
        }
        if (!empty($filters['search'])) {
            $conditions[] = '(name LIKE ? OR email LIKE ? OR message LIKE ?)';
            $term         = '%' . sanitize($filters['search']) . '%';
            $params[]     = $term;
            $params[]     = $term;
            $params[]     = $term;
        }
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        try {
            $row = $this->db->fetchOne($sql, $params);
            return $row ? (int)$row['total'] : 0;
        } catch (PDOException $e) {
            error_log('Contact getCount error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update a contact submission status
     */
    public function updateStatus($id, $status) {
        $id     = (int)$id;
        $status = sanitize($status);
        $allowed = ['new', 'read', 'in_progress', 'resolved', 'spam'];
        if (!in_array($status, $allowed)) {
            return ['success' => false, 'message' => 'Invalid status value.'];
        }
        try {
            $this->db->query(
                'UPDATE contact_submissions SET status = ?, updated_at = NOW() WHERE id = ?',
                [$status, $id]
            );
            return ['success' => true, 'message' => 'Status updated successfully.'];
        } catch (PDOException $e) {
            error_log('Contact updateStatus error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update status.'];
        }
    }

    /**
     * Add admin notes to a contact submission
     */
    public function addNote($id, $note) {
        $id   = (int)$id;
        $note = sanitize($note);
        try {
            $this->db->query(
                'UPDATE contact_submissions SET admin_notes = ?, updated_at = NOW() WHERE id = ?',
                [$note, $id]
            );
            return ['success' => true, 'message' => 'Note saved successfully.'];
        } catch (PDOException $e) {
            error_log('Contact addNote error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to save note.'];
        }
    }

    /**
     * Get analytics data
     */
    public function getAnalytics() {
        try {
            $totals = $this->db->fetchOne(
                'SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "new" THEN 1 ELSE 0 END) as new_count,
                    SUM(CASE WHEN status = "read" THEN 1 ELSE 0 END) as read_count,
                    SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved_count,
                    SUM(CASE WHEN status = "spam" THEN 1 ELSE 0 END) as spam_count
                 FROM contact_submissions'
            );

            $monthly = $this->db->fetchAll(
                'SELECT DATE_FORMAT(submitted_at, "%Y-%m") as month, COUNT(*) as count
                 FROM contact_submissions
                 WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                 GROUP BY month
                 ORDER BY month ASC'
            );

            $daily = $this->db->fetchAll(
                'SELECT DATE(submitted_at) as day, COUNT(*) as count
                 FROM contact_submissions
                 WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY day
                 ORDER BY day ASC'
            );

            return [
                'totals'  => $totals,
                'monthly' => $monthly,
                'daily'   => $daily,
            ];
        } catch (PDOException $e) {
            error_log('Contact getAnalytics error: ' . $e->getMessage());
            return ['totals' => [], 'monthly' => [], 'daily' => []];
        }
    }

    /**
     * Send confirmation email to the person who submitted the form
     */
    private function sendConfirmationEmail($name, $email) {
        $subject = 'Thank You for Contacting Mason Construction Services Inc.';
        $message = "Dear {$name},\n\n"
            . "Thank you for reaching out to Mason Construction Services Inc.!\n\n"
            . "We have received your message and one of our team members will get back to you within 1-2 business days.\n\n"
            . "If your matter is urgent, please call us at +(347) 933-0867.\n\n"
            . "Best regards,\n"
            . "Mason Construction Services Inc.\n"
            . "150 Motor Pkwy, Suite #401, Hauppauge, NY 11788\n"
            . "Phone: +(347) 933-0867\n"
            . "Email: mason@themasonconstruction.com";

        $headers  = "From: " . APP_NAME . " <" . ADMIN_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        if (!mail($email, $subject, $message, $headers)) {
            error_log('Contact confirmation email failed to: ' . $email);
        }
    }

    /**
     * Send notification email to admin
     */
    private function sendAdminNotification($name, $email, $phone, $subject, $message) {
        $emailSubject = "New Contact Form Submission: {$subject}";
        $emailBody    = "A new contact form submission has been received:\n\n"
            . "Name:    {$name}\n"
            . "Email:   {$email}\n"
            . "Phone:   {$phone}\n"
            . "Subject: {$subject}\n\n"
            . "Message:\n{$message}\n\n"
            . "---\n"
            . "Log in to the admin dashboard to manage this submission:\n"
            . "https://themasonconstruction.com/admin/";

        $headers  = "From: " . APP_NAME . " <" . ADMIN_EMAIL . ">\r\n";
        $headers .= "Reply-To: {$email}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        if (!mail(ADMIN_EMAIL, $emailSubject, $emailBody, $headers)) {
            error_log('Admin notification email failed for contact from: ' . $email);
        }
    }
}
