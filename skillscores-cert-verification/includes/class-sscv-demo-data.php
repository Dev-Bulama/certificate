<?php
/**
 * Demo Data Manager - One-click seed and reset for testing.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Demo_Data {

    /**
     * Seed the database with realistic dummy data.
     */
    public static function seed() {
        global $wpdb;

        // Ensure tables exist
        SSCV_Database::activate();

        // ---- 1. COURSES ----
        $courses = array(
            array( 'Full Stack Web Development',     'WEB-101', 'Master modern web technologies including HTML, CSS, JavaScript, PHP, and databases.' ),
            array( 'Mobile App Development',          'MOB-201', 'Build native and cross-platform mobile applications using Flutter and React Native.' ),
            array( 'Data Science & Analytics',        'DAT-301', 'Learn data analysis, visualization, and machine learning with Python.' ),
            array( 'Cybersecurity Fundamentals',      'SEC-102', 'Understand network security, ethical hacking, and information protection.' ),
            array( 'UI/UX Design Masterclass',        'DES-150', 'Design beautiful, user-centered interfaces with Figma and Adobe XD.' ),
            array( 'Cloud Computing with AWS',        'CLD-220', 'Deploy and manage cloud infrastructure on Amazon Web Services.' ),
            array( 'Artificial Intelligence & ML',    'AIM-401', 'Deep dive into neural networks, NLP, and computer vision.' ),
            array( 'Digital Marketing',               'MKT-110', 'SEO, social media marketing, Google Ads, and email marketing strategies.' ),
            array( 'Database Administration',         'DBA-250', 'MySQL, PostgreSQL, MongoDB administration and optimization.' ),
            array( 'DevOps Engineering',              'DVP-310', 'CI/CD pipelines, Docker, Kubernetes, and infrastructure as code.' ),
        );

        // Get default template
        $default_template = $wpdb->get_var(
            "SELECT id FROM {$wpdb->prefix}sscv_certificate_templates WHERE is_default = 1 LIMIT 1"
        );

        $course_ids = array();
        foreach ( $courses as $c ) {
            // Skip if already exists
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sscv_courses WHERE course_code = %s",
                $c[1]
            ) );

            if ( $exists ) {
                $course_ids[] = $exists;
                continue;
            }

            $wpdb->insert( $wpdb->prefix . 'sscv_courses', array(
                'course_title' => $c[0],
                'course_code'  => $c[1],
                'description'  => $c[2],
                'template_id'  => $default_template,
                'status'       => 'active',
            ) );
            $course_ids[] = $wpdb->insert_id;
        }

        // ---- 2. STUDENTS ----
        $students_data = array(
            array( 'STU-2024-001', 'Aisha Mohammed',    'aisha.mohammed@example.com' ),
            array( 'STU-2024-002', 'Chinedu Okafor',    'chinedu.okafor@example.com' ),
            array( 'STU-2024-003', 'Fatima Bello',      'fatima.bello@example.com' ),
            array( 'STU-2024-004', 'Ibrahim Yusuf',     'ibrahim.yusuf@example.com' ),
            array( 'STU-2024-005', 'Ngozi Eze',         'ngozi.eze@example.com' ),
            array( 'STU-2024-006', 'Oluwaseun Adeyemi', 'oluwaseun.adeyemi@example.com' ),
            array( 'STU-2024-007', 'Halima Sani',       'halima.sani@example.com' ),
            array( 'STU-2024-008', 'Emeka Nwosu',       'emeka.nwosu@example.com' ),
            array( 'STU-2024-009', 'Zainab Abdullahi',  'zainab.abdullahi@example.com' ),
            array( 'STU-2024-010', 'Tunde Bakare',      'tunde.bakare@example.com' ),
            array( 'STU-2024-011', 'Amina Garba',       'amina.garba@example.com' ),
            array( 'STU-2024-012', 'Chioma Obi',        'chioma.obi@example.com' ),
            array( 'STU-2024-013', 'Musa Abubakar',     'musa.abubakar@example.com' ),
            array( 'STU-2024-014', 'Blessing Osagie',   'blessing.osagie@example.com' ),
            array( 'STU-2024-015', 'Yusuf Danjuma',     'yusuf.danjuma@example.com' ),
            array( 'STU-2024-016', 'Ada Nkem',          'ada.nkem@example.com' ),
            array( 'STU-2024-017', 'Suleiman Musa',     'suleiman.musa@example.com' ),
            array( 'STU-2024-018', 'Funke Adeola',      'funke.adeola@example.com' ),
            array( 'STU-2024-019', 'Hassan Umar',       'hassan.umar@example.com' ),
            array( 'STU-2024-020', 'Grace Onyeka',      'grace.onyeka@example.com' ),
        );

        $student_ids = array();
        foreach ( $students_data as $s ) {
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sscv_students WHERE student_id = %s",
                $s[0]
            ) );

            if ( $exists ) {
                $student_ids[] = $exists;
                continue;
            }

            $wpdb->insert( $wpdb->prefix . 'sscv_students', array(
                'student_id'   => $s[0],
                'full_name'    => $s[1],
                'email'        => $s[2],
                'passport_url' => '',
            ) );
            $student_ids[] = $wpdb->insert_id;
        }

        // ---- 3. CERTIFICATES ----
        $grades = array( 'A (Distinction)', 'A', 'B+ (Merit)', 'B', 'B', 'A-', 'A (Distinction)', 'B+', 'C+ (Credit)', 'A' );
        $statuses = array( 'approved', 'approved', 'approved', 'pending', 'approved', 'approved', 'pending', 'approved', 'rejected', 'approved',
                           'approved', 'pending', 'approved', 'approved', 'pending', 'approved', 'approved', 'rejected', 'approved', 'approved' );

        // Each student gets 1-2 certificates
        $cert_count = 0;
        for ( $i = 0; $i < count( $student_ids ); $i++ ) {
            $num_certs = ( $i % 3 === 0 ) ? 2 : 1; // Every 3rd student gets 2 certificates

            for ( $j = 0; $j < $num_certs; $j++ ) {
                $sid = $student_ids[ $i ];
                $cid = $course_ids[ ( $i + $j ) % count( $course_ids ) ];
                $sdata = $students_data[ $i ];
                $status = $statuses[ $cert_count % count( $statuses ) ];
                $grade = $grades[ $cert_count % count( $grades ) ];

                // Check duplicate
                $exists = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_certificates WHERE student_id = %d AND course_id = %d",
                    $sid, $cid
                ) );

                if ( $exists ) {
                    $cert_count++;
                    continue;
                }

                $cert_id = SSCV_Helpers::generate_certificate_id();
                $completed = date( 'Y-m-d', strtotime( '-' . rand( 30, 365 ) . ' days' ) );
                $issued = ( $status === 'approved' ) ? date( 'Y-m-d', strtotime( '-' . rand( 1, 29 ) . ' days' ) ) : null;

                $wpdb->insert( $wpdb->prefix . 'sscv_certificates', array(
                    'certificate_id' => $cert_id,
                    'student_id'     => $sid,
                    'course_id'      => $cid,
                    'template_id'    => $default_template,
                    'full_name'      => $sdata[1],
                    'email'          => $sdata[2],
                    'grade'          => $grade,
                    'date_completed' => $completed,
                    'date_issued'    => $issued,
                    'passport_url'   => '',
                    'certificate_url' => '',
                    'pdf_url'        => '',
                    'qr_code_url'    => '',
                    'status'         => $status,
                    'admin_notes'    => ( $status === 'rejected' ) ? 'Demo: Application did not meet requirements.' : '',
                ) );

                $cert_count++;
            }
        }

        // ---- 4. PROJECTS ----
        $projects_data = array(
            array( 0, 'E-Commerce Platform',         'A full-featured online marketplace with payment integration, inventory management, and admin dashboard.', 'Web Development',  'https://example.com/ecommerce',  'https://github.com/demo/ecommerce',  'https://linkedin.com/in/demo1' ),
            array( 1, 'Health Tracker App',           'Mobile application for tracking daily health metrics including steps, water intake, and sleep quality.', 'Mobile App',        'https://example.com/healthapp',  'https://github.com/demo/healthapp',  'https://twitter.com/demo2' ),
            array( 2, 'Student Portal System',        'Comprehensive student management system with attendance, grades, and communication features.',          'Web Development',  'https://example.com/portal',     'https://github.com/demo/portal',     'https://linkedin.com/in/demo3' ),
            array( 3, 'Sales Prediction Model',       'Machine learning model that predicts monthly sales using historical data and seasonal patterns.',       'Data Science',      '',                                'https://github.com/demo/salesml',    '' ),
            array( 4, 'Network Vulnerability Scanner', 'Automated tool for scanning and reporting network vulnerabilities in enterprise environments.',        'Cybersecurity',     '',                                'https://github.com/demo/vulnscan',   'https://linkedin.com/in/demo5' ),
            array( 5, 'Restaurant Booking App',       'Cross-platform mobile app for restaurant reservations with real-time availability and reviews.',        'Mobile App',        'https://example.com/bookapp',    'https://github.com/demo/bookapp',    '' ),
            array( 6, 'AI Chatbot Assistant',         'Natural language processing chatbot for customer service with sentiment analysis capabilities.',        'AI/ML',             'https://example.com/chatbot',    'https://github.com/demo/chatbot',    'https://linkedin.com/in/demo7' ),
            array( 7, 'Inventory Management System',  'Real-time inventory tracking with barcode scanning, reporting, and multi-warehouse support.',           'Web Development',  'https://example.com/inventory',  'https://github.com/demo/inventory',  '' ),
            array( 8, 'Weather Dashboard',            'Interactive weather dashboard with data visualization, forecasting, and location-based alerts.',       'Data Science',      'https://example.com/weather',    'https://github.com/demo/weather',    'https://twitter.com/demo9' ),
            array( 9, 'Fitness Social Network',       'Social platform for fitness enthusiasts to share workouts, progress, and connect with trainers.',       'Web Development',  'https://example.com/fitnet',     'https://github.com/demo/fitnet',     'https://linkedin.com/in/demo10' ),
            array( 10, 'Smart Home Controller',       'IoT dashboard for managing smart home devices with automation rules and energy monitoring.',            'Mobile App',        '',                                'https://github.com/demo/smarthome', '' ),
            array( 11, 'Financial Analytics Tool',    'Dashboard for personal finance management with budgeting, expense categorization, and trend analysis.', 'Data Science',      'https://example.com/finance',    'https://github.com/demo/finance',    'https://linkedin.com/in/demo12' ),
            array( 12, 'Online Learning Platform',    'E-learning system with video courses, quizzes, progress tracking, and certificate generation.',         'Web Development',  'https://example.com/elearn',     'https://github.com/demo/elearn',     '' ),
            array( 13, 'Task Management App',         'Project management tool with kanban boards, team collaboration, and deadline notifications.',           'Web Development',  'https://example.com/taskapp',    'https://github.com/demo/taskapp',    'https://linkedin.com/in/demo14' ),
            array( 14, 'Cyber Threat Monitor',        'Real-time monitoring dashboard for detecting and alerting on cybersecurity threats.',                    'Cybersecurity',     '',                                'https://github.com/demo/cybmon',     '' ),
        );

        $proj_statuses = array( 'approved', 'approved', 'pending', 'approved', 'approved', 'pending', 'approved', 'approved', 'rejected', 'approved', 'approved', 'pending', 'approved', 'approved', 'pending' );

        foreach ( $projects_data as $idx => $p ) {
            $stu_idx = $p[0] % count( $student_ids );
            $sid     = $student_ids[ $stu_idx ];
            $sdata   = $students_data[ $stu_idx ];

            // Check duplicate
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_projects WHERE student_id = %d AND project_title = %s",
                $sid, $p[1]
            ) );

            if ( $exists ) {
                continue;
            }

            $wpdb->insert( $wpdb->prefix . 'sscv_projects', array(
                'student_id'        => $sid,
                'student_name'      => $sdata[1],
                'student_id_number' => $sdata[0],
                'project_title'     => $p[1],
                'description'       => $p[2],
                'project_image_url' => '',
                'website_url'       => $p[4],
                'github_url'        => $p[5],
                'social_url'        => $p[6],
                'cv_url'            => '',
                'category'          => $p[3],
                'status'            => $proj_statuses[ $idx % count( $proj_statuses ) ],
                'admin_notes'       => '',
            ) );
        }

        // Mark the demo as active
        update_option( 'sscv_demo_data_active', true );

        return array(
            'students'     => count( $student_ids ),
            'courses'      => count( $course_ids ),
            'certificates' => $cert_count,
            'projects'     => count( $projects_data ),
        );
    }

    /**
     * Reset / wipe all plugin data (demo + real).
     */
    public static function reset() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'sscv_certificates',
            $wpdb->prefix . 'sscv_projects',
            $wpdb->prefix . 'sscv_students',
            $wpdb->prefix . 'sscv_courses',
        );

        foreach ( $tables as $table ) {
            $wpdb->query( "TRUNCATE TABLE {$table}" );
        }

        // Clean up generated files
        $upload_dir = wp_upload_dir();
        $dirs = array(
            $upload_dir['basedir'] . '/sscv-certificates',
            $upload_dir['basedir'] . '/sscv-qrcodes',
        );

        foreach ( $dirs as $dir ) {
            if ( is_dir( $dir ) ) {
                self::delete_directory_contents( $dir );
            }
        }

        // Clear rate-limit transients
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%sscv_rate_%'" );

        delete_option( 'sscv_demo_data_active' );

        return true;
    }

    /**
     * Recursively delete directory contents (but keep the directory).
     */
    private static function delete_directory_contents( $dir ) {
        $items = glob( $dir . '/{*,.[!.]*}', GLOB_BRACE );
        if ( ! $items ) return;

        foreach ( $items as $item ) {
            if ( is_dir( $item ) ) {
                self::delete_directory_contents( $item );
                @rmdir( $item );
            } elseif ( basename( $item ) !== 'index.php' ) {
                @unlink( $item );
            }
        }
    }

    /**
     * Check if demo data is currently active.
     */
    public static function is_active() {
        return (bool) get_option( 'sscv_demo_data_active', false );
    }

    /**
     * Get current data counts for display.
     */
    public static function get_counts() {
        global $wpdb;

        return array(
            'students'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_students" ),
            'courses'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_courses" ),
            'certificates' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_certificates" ),
            'projects'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_projects" ),
        );
    }
}
