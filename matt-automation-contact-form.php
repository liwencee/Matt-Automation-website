<?php
/*
Plugin Name: Matt Automation Contact Form
Description: Slatech.
Version: 1.1
Author: Slatech
*/

if (!defined('ABSPATH')) exit;

/* =====================================
   CREATE DATABASE TABLE
===================================== */
register_activation_hook(__FILE__, 'matt_create_table');
function matt_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'matt_contact_submissions';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        full_name varchar(100),
        email varchar(100),
        phone varchar(50),
        contacting_as varchar(50),
        service varchar(100),
        reason varchar(100),
        message text,
        urgency varchar(50),
        preferred_contact varchar(50),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/* =====================================
   ENQUEUE STYLES & SCRIPTS
===================================== */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('matt-form-style', plugin_dir_url(__FILE__) . 'style.css');

    wp_enqueue_script(
        'matt-form-script',
        plugin_dir_url(__FILE__) . 'script.js',
        [],
        false,
        true
    );

    // Google reCAPTCHA
    wp_enqueue_script(
        'google-recaptcha',
        'https://www.google.com/recaptcha/api.js',
        [],
        null,
        true
    );
});

/* =====================================
   SHORTCODE
===================================== */
add_shortcode('matt_ai_contact_form', function () {

    /* ===== HANDLE FORM SUBMISSION FIRST ===== */
    if (isset($_POST['matt_submit']) && isset($_POST['matt_nonce']) && wp_verify_nonce($_POST['matt_nonce'], 'matt_form_nonce')) {

        /* reCAPTCHA */
        if (empty($_POST['g-recaptcha-response'])) {
            echo "<p class='error-msg'>reCAPTCHA verification failed.</p>";
        } else {

            $verify = wp_remote_post(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'body' => [
                        'secret' => 'YOUT KEY HERE',
                        'response' => sanitize_text_field($_POST['g-recaptcha-response'])
                    ]
                ]
            );

            $captcha = json_decode(wp_remote_retrieve_body($verify), true);

            if ($captcha['success']) {

                $data = [
                    'full_name'         => sanitize_text_field($_POST['full_name'] ?? ''),
                    'email'             => sanitize_email($_POST['email'] ?? ''),
                    'phone'             => sanitize_text_field($_POST['phone'] ?? ''),
                    'contacting_as'     => sanitize_text_field($_POST['contacting_as'] ?? ''),
                    'service'           => sanitize_text_field($_POST['service'] ?? ''),
                    'reason'            => sanitize_text_field($_POST['reason'] ?? ''),
                    'message'           => sanitize_textarea_field($_POST['message'] ?? ''),
                    'urgency'           => sanitize_text_field($_POST['urgency'] ?? ''),
                    'preferred_contact' => sanitize_text_field($_POST['preferred_contact'] ?? ''),
                ];

                global $wpdb;
                $table = $wpdb->prefix . 'matt_contact_submissions';
                $wpdb->insert($table, $data);

                /* Email */
                wp_mail(
                    'info@mattautomation.com',
                    'New Contact Form Submission – Matt Automation',
                    "New Contact Form Submission\n\n" .
                    "Name: {$data['full_name']}\n" .
                    "Email: {$data['email']}\n" .
                    "Phone: {$data['phone']}\n" .
                    "Service: {$data['service']}\n" .
                    "Urgency: {$data['urgency']}\n\n" .
                    "Message:\n{$data['message']}",
                    [
                        'Content-Type: text/plain; charset=UTF-8',
                        'Reply-To: ' . $data['email']
                    ]
                );

                echo "<p class='success-msg'>Thank you! Your message has been sent.</p>";
            } else {
                echo "<p class='error-msg'>reCAPTCHA validation failed.</p>";
            }
        }
    }

    /* ===== FORM UI ===== */
    ob_start(); ?>

    <div class="bg-glow"></div>

    <form class="glass-form" method="post">
        <?php wp_nonce_field('matt_form_nonce', 'matt_nonce'); ?>

        <h3>GET A QUOTE</h3>

        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="tel" name="phone" placeholder="Phone Number (WhatsApp preferred)">

        <select name="contacting_as" required>
            <option value="">You are contacting us as</option>
            <option>Individual</option>
            <option>Business</option>
        </select>

        <select name="service" id="serviceSelect" required>
            <option value="">Select a Service</option>
            <option value="workflow">Workflow & Process Automation</option>
            <option value="agents">AI Agents & Intelligent Assistants</option>
            <option value="data">Data, Documents & Knowledge Automation</option>
            <option value="communication">Communication & Scheduling Automation</option>
            <option value="industry">Industry-Specific AI Solutions</option>
            <option value="custom">Custom AI & Bespoke Automations</option>
        </select>

        <div id="serviceDescription" class="service-box"></div>

        <select name="reason" required>
            <option value="">Reason for contacting us</option>
            <option>Book a demo</option>
            <option>Need an automation</option>
            <option>Support</option>
            <option>Partnership</option>
            <option>Others</option>
        </select>

        <textarea name="message" placeholder="Brief explanation of your request"></textarea>

        <select name="urgency" required>
            <option value="">How urgent is this?</option>
            <option>Low</option>
            <option>Medium</option>
            <option>High / ASAP</option>
        </select>

        <select name="preferred_contact" required>
            <option value="">Preferred contact method</option>
            <option>Email</option>
            <option>WhatsApp</option>
            <option>Phone Call</option>
        </select>

        <div class="g-recaptcha" data-sitekey="YOUR KEY HERE"></div>

        <label class="checkbox">
            <input type="checkbox" required>
            I agree that Matt Automation may contact me using the information provided.
        </label>

        <button type="submit" name="matt_submit">Submit</button>
    </form>

    <?php
    return ob_get_clean();
});
