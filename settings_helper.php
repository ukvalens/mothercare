<?php
// settings_helper.php

function getSystemSettings($connection)
{
    // Check if system_settings table exists
    $check_table = "SHOW TABLES LIKE 'system_settings'";
    $result = mysqli_query($connection, $check_table);

    if (mysqli_num_rows($result) > 0) {
        // Table exists, get settings
        $settings_query = "SELECT * FROM system_settings WHERE id = 1";
        $settings_result = mysqli_query($connection, $settings_query);

        if (mysqli_num_rows($settings_result) > 0) {
            return mysqli_fetch_assoc($settings_result);
        }
    }

    // Return default settings if table doesn't exist or no settings found
    return [
        'system_name' => 'MaternalCare AI',
        'language' => 'english',
        'theme' => 'light',
        'auto_backup' => 1,
        'backup_frequency' => 'daily',
        'session_timeout' => 30,
        'email_notifications' => 1,
        'sms_notifications' => 1,
        'max_login_attempts' => 5,
        'password_expiry' => 90
    ];
}

// Language translations
function getTranslations($language)
{
    $translations = [
        'english' => [
            'welcome' => 'Welcome',
            'dashboard' => 'Dashboard',
            'patients' => 'Patients',
            'appointments' => 'Appointments',
            'visits' => 'ANC Visits',
            'deliveries' => 'Delivery Records',
            'ai_risk' => 'AI Risk Prediction',
            'reports' => 'Reports & Analytics',
            'my_dashboard' => 'My Dashboard',
            'my_appointments' => 'My Appointments',
            'health_records' => 'Health Records',
            'messages' => 'Messages',
            'pregnancy_tracker' => 'Pregnancy Tracker',
            'user_management' => 'User Management',
            'system_settings' => 'System Settings',
            'reports_admin' => 'Reports',
            'active_patients' => 'Active Patients',
            'anc_visits' => 'ANC Visits',
            'high_risk_cases' => 'High-Risk Cases',
            'upcoming_appointments' => 'Upcoming Appointments',
            'registered_patients' => 'Registered patients',
            'total_visits_recorded' => 'Total visits recorded',
            'require_attention' => 'Require attention',
            'scheduled_appointments' => 'Scheduled appointments',
            'recent_activity' => 'Recent Activity',
            'date' => 'Date',
            'activity' => 'Activity',
            'details' => 'Details',
            'status' => 'Status',
            'completed' => 'Completed',
            'logged_in_as' => 'Logged in as',
            'logout' => 'Logout'
        ],
        'kinyarwanda' => [
            'welcome' => 'Murakaza neza',
            'dashboard' => 'Ikibaho',
            'patients' => 'Abarwayi',
            'appointments' => 'Igihe ntarengwa',
            'visits' => 'Ukuvurwa kwa ANC',
            'deliveries' => 'Ibyerekeye ivuka',
            'ai_risk' => 'Gupima ingoza ku buryo bwa AI',
            'reports' => 'Raporo n\'ibisubizo',
            'my_dashboard' => 'Ikibaho cyanjye',
            'my_appointments' => 'Igihe cyanjye',
            'health_records' => 'Ibyanditswe by\'ubuzima',
            'messages' => 'Ubutumwa',
            'pregnancy_tracker' => 'Gukurikirana imyiterere',
            'user_management' => 'Gucunga abakoresha',
            'system_settings' => 'Ibyerekeye sisitemu',
            'reports_admin' => 'Raporo',
            'active_patients' => 'Abarwayi bariho',
            'anc_visits' => 'Ukuvurwa kwa ANC',
            'high_risk_cases' => 'Ingoza zo hejuru',
            'upcoming_appointments' => 'Igihe gikurikira',
            'registered_patients' => 'Abarwayi banditswe',
            'total_visits_recorded' => 'Uko kuvurwa kwose kwanditswe',
            'require_attention' => 'Bakeneye itabaza',
            'scheduled_appointments' => 'Igihe ntarengwa ryateguwe',
            'recent_activity' => 'Ibikorwa byakozwe vuba aha',
            'date' => 'Itariki',
            'activity' => 'Igikorwa',
            'details' => 'Ibisobanuro',
            'status' => 'Imimerere',
            'completed' => 'Byarakozwe',
            'logged_in_as' => 'Winjiye nk\'',
            'logout' => 'Sohoka'
        ]
    ];

    return $translations[$language] ?? $translations['english'];
}
