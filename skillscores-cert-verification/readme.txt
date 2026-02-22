=== Certificate & Student Verification System ===
Contributors: tijanibulama
Tags: certificate, verification, qr code, student, education, projects
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A robust WordPress plugin for certificate application, generation, QR code verification, and student project showcase.

== Description ==

Certificate & Student Verification System allows educational institutions to:

* Accept certificate applications online from students
* Generate beautiful HTML certificates with dynamic placeholders
* Create QR codes for instant certificate verification
* Provide a public verification portal for employers
* Showcase approved student projects

= Features =

* **Certificate Application System** - Frontend form for students to apply for certificates
* **HTML Certificate Template Engine** - Admin-editable templates with placeholder support
* **QR Code Verification** - Unique QR codes linked to verification portal
* **Public Verification Portal** - AJAX-powered search by Certificate ID, Student ID, or Name
* **Student Project Showcase** - Project submission with admin approval workflow
* **Admin Control Panel** - Manage institution settings, courses, templates, and more
* **Email Automation** - Auto-generate and email certificates upon approval
* **Elementor Compatible** - 5 Elementor widgets included
* **REST API** - Full REST API for external integrations
* **Security** - Nonce verification, reCAPTCHA v3, file validation, rate limiting

= Shortcodes =

* `[certificate_application_form]` - Certificate application form
* `[certificate_verification]` - Public verification portal
* `[student_project_submission]` - Project submission form
* `[public_project_directory]` - Public project directory with search and pagination
* `[student_dashboard]` - Student dashboard for viewing certificate status

= REST API Endpoints =

* `GET /wp-json/sscv/v1/verify/{cert_id}` - Verify a certificate
* `GET /wp-json/sscv/v1/certificates?search=&type=` - Search certificates
* `GET /wp-json/sscv/v1/projects` - List approved projects
* `GET /wp-json/sscv/v1/courses` - List active courses
* `GET /wp-json/sscv/v1/student/{student_id}` - Get student data (admin only)

== Installation ==

1. Upload the `skillscores-cert-verification` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Certificates > Settings** to configure your institution details
4. Add courses via **Certificates > Courses**
5. Use the provided shortcodes on any page or post

== Frequently Asked Questions ==

= How do I create a certificate template? =
Go to **Certificates > Templates** in the admin panel. You can create HTML templates using the provided placeholders.

= How does QR code verification work? =
Each approved certificate gets a unique QR code. Scanning it opens the verification page showing certificate authenticity.

= Can I use this with Elementor? =
Yes! The plugin includes 5 Elementor widgets under the "Certificate & Verification" category.

= Is reCAPTCHA required? =
No. reCAPTCHA v3 is optional. Configure it in **Certificates > Settings** if desired.

== Changelog ==

= 1.0.0 =
* Initial release
* Certificate application system
* HTML template engine with placeholders
* QR code generation
* Public verification portal
* Student project showcase
* Admin control panel
* Email automation
* Elementor widgets
* REST API
* Security features

== Upgrade Notice ==

= 1.0.0 =
Initial release.
