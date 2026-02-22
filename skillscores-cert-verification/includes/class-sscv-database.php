<?php
/**
 * Database setup and migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Database {

    /**
     * Run on plugin activation.
     */
    public static function activate() {
        self::create_tables();
        self::seed_defaults();
        flush_rewrite_rules();
        update_option( 'sscv_db_version', SSCV_DB_VERSION );
    }

    /**
     * Create custom database tables.
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $tables = array();

        // Students table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sscv_students (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            student_id VARCHAR(50) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            passport_url VARCHAR(500) DEFAULT '',
            phone VARCHAR(50) DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY student_id (student_id),
            KEY email (email),
            KEY user_id (user_id)
        ) {$charset_collate};";

        // Courses table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sscv_courses (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            course_title VARCHAR(255) NOT NULL,
            course_code VARCHAR(50) DEFAULT '',
            description TEXT DEFAULT '',
            template_id BIGINT(20) UNSIGNED DEFAULT NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_id (template_id)
        ) {$charset_collate};";

        // Certificate templates table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sscv_certificate_templates (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            template_name VARCHAR(255) NOT NULL,
            html_content LONGTEXT NOT NULL,
            css_content LONGTEXT DEFAULT '',
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        // Certificates table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sscv_certificates (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            certificate_id VARCHAR(50) NOT NULL,
            student_id BIGINT(20) UNSIGNED NOT NULL,
            course_id BIGINT(20) UNSIGNED NOT NULL,
            template_id BIGINT(20) UNSIGNED DEFAULT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            grade VARCHAR(50) DEFAULT '',
            date_completed DATE DEFAULT NULL,
            date_issued DATE DEFAULT NULL,
            passport_url VARCHAR(500) DEFAULT '',
            certificate_url VARCHAR(500) DEFAULT '',
            pdf_url VARCHAR(500) DEFAULT '',
            qr_code_url VARCHAR(500) DEFAULT '',
            status ENUM('pending','approved','rejected','revoked') NOT NULL DEFAULT 'pending',
            admin_notes TEXT DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY certificate_id (certificate_id),
            KEY student_id (student_id),
            KEY course_id (course_id),
            KEY status (status)
        ) {$charset_collate};";

        // Projects table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sscv_projects (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            student_id BIGINT(20) UNSIGNED NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            student_id_number VARCHAR(50) NOT NULL,
            project_title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT '',
            project_image_url VARCHAR(500) DEFAULT '',
            website_url VARCHAR(500) DEFAULT '',
            github_url VARCHAR(500) DEFAULT '',
            social_url VARCHAR(500) DEFAULT '',
            cv_url VARCHAR(500) DEFAULT '',
            category VARCHAR(100) DEFAULT '',
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            admin_notes TEXT DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY status (status),
            KEY category (category)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }
    }

    /**
     * Seed default data.
     */
    public static function seed_defaults() {
        global $wpdb;

        $template_table = $wpdb->prefix . 'sscv_certificate_templates';

        // Check if default template exists
        $exists = $wpdb->get_var( "SELECT COUNT(*) FROM {$template_table}" );

        if ( ! $exists ) {
            $default_html = self::get_default_template_html();
            $default_css  = self::get_default_template_css();

            $wpdb->insert( $template_table, array(
                'template_name' => 'Default Certificate',
                'html_content'  => $default_html,
                'css_content'   => $default_css,
                'is_default'    => 1,
            ), array( '%s', '%s', '%s', '%d' ) );
        }

        // Set default options
        $defaults = array(
            'sscv_institution_name' => 'Your Institution Name',
            'sscv_theme_color'      => '#1a365d',
            'sscv_accent_color'     => '#c8a951',
            'sscv_font_family'      => 'Georgia, serif',
            'sscv_qr_size'          => 150,
            'sscv_email_subject'    => 'Your Certificate is Ready - {{course_title}}',
            'sscv_email_body'       => "Dear {{student_name}},\n\nCongratulations! Your certificate for {{course_title}} has been approved and is ready for download.\n\nCertificate ID: {{certificate_id}}\nCourse: {{course_title}}\nDate Issued: {{date_issued}}\n\nYou can verify your certificate at: {{verification_url}}\n\nPlease find your certificate attached to this email.\n\nBest Regards,\n{{institution_name}}",
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                update_option( $key, $value );
            }
        }
    }

    /**
     * Default certificate HTML template.
     */
    public static function get_default_template_html() {
        return '<div class="certificate-wrapper">
    <div class="certificate-border">
        <div class="certificate-inner">
            <div class="certificate-header">
                <div class="institution-logo">{{institution_logo}}</div>
                <h1 class="institution-name">{{institution_name}}</h1>
                <h2 class="certificate-title">Certificate of Completion</h2>
            </div>

            <div class="certificate-body">
                <div class="passport-section">{{passport}}</div>
                <p class="presented-to">This is to certify that</p>
                <h2 class="student-name">{{student_name}}</h2>
                <p class="student-info">Student ID: {{student_id}}</p>
                <p class="completion-text">has successfully completed the course</p>
                <h3 class="course-title">{{course_title}}</h3>
                <p class="completion-date">on {{completion_date}}</p>
                <p class="grade-text">with a grade of <strong>{{grade}}</strong></p>
            </div>

            <div class="certificate-footer">
                <div class="signature-section">
                    <div class="signature">{{signature}}</div>
                    <div class="signature-line"></div>
                    <p class="signature-label">Authorized Signature</p>
                </div>
                <div class="qr-section">
                    {{qr_code}}
                    <p class="cert-id">ID: {{certificate_id}}</p>
                </div>
                <div class="stamp-section">
                    <div class="institution-stamp">{{institution_stamp}}</div>
                    <p class="date-issued">Issued: {{date_issued}}</p>
                </div>
            </div>
        </div>
    </div>
</div>';
    }

    /**
     * Default certificate CSS.
     */
    public static function get_default_template_css() {
        return '.certificate-wrapper {
    width: 1056px;
    min-height: 816px;
    margin: 0 auto;
    background: #fff;
    font-family: Georgia, "Times New Roman", serif;
    color: #1a365d;
    position: relative;
}

.certificate-border {
    border: 3px solid #c8a951;
    margin: 15px;
    padding: 10px;
    min-height: 780px;
}

.certificate-inner {
    border: 1px solid #c8a951;
    padding: 40px 50px;
    text-align: center;
    min-height: 760px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.certificate-header {
    margin-bottom: 20px;
}

.institution-logo img {
    max-height: 80px;
    margin-bottom: 10px;
}

.institution-name {
    font-size: 28px;
    font-weight: bold;
    color: #1a365d;
    margin: 5px 0;
    text-transform: uppercase;
    letter-spacing: 3px;
}

.certificate-title {
    font-size: 36px;
    color: #c8a951;
    font-weight: normal;
    font-style: italic;
    margin: 10px 0;
    letter-spacing: 2px;
}

.certificate-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.passport-section img {
    width: 100px;
    height: 120px;
    object-fit: cover;
    border: 2px solid #c8a951;
    margin-bottom: 15px;
}

.presented-to {
    font-size: 16px;
    color: #666;
    margin: 10px 0 5px;
}

.student-name {
    font-size: 32px;
    color: #1a365d;
    border-bottom: 2px solid #c8a951;
    padding-bottom: 5px;
    margin: 5px 0 10px;
    display: inline-block;
}

.student-info {
    font-size: 14px;
    color: #888;
    margin: 5px 0;
}

.completion-text {
    font-size: 16px;
    color: #666;
    margin: 15px 0 5px;
}

.course-title {
    font-size: 26px;
    color: #c8a951;
    margin: 5px 0;
}

.completion-date {
    font-size: 15px;
    color: #666;
    margin: 5px 0;
}

.grade-text {
    font-size: 16px;
    color: #1a365d;
    margin: 10px 0;
}

.certificate-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 30px;
    padding-top: 20px;
}

.signature-section,
.qr-section,
.stamp-section {
    flex: 1;
    text-align: center;
}

.signature img {
    max-height: 60px;
    margin-bottom: 5px;
}

.signature-line {
    border-top: 1px solid #333;
    width: 150px;
    margin: 0 auto 5px;
}

.signature-label {
    font-size: 12px;
    color: #666;
}

.qr-section img {
    width: 100px;
    height: 100px;
}

.cert-id {
    font-size: 11px;
    color: #888;
    margin-top: 5px;
    font-family: monospace;
}

.institution-stamp img {
    max-height: 70px;
    opacity: 0.8;
}

.date-issued {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}';
    }
}
