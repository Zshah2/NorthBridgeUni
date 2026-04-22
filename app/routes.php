<?php

/**
 * Very small router table: [method, path, handler]
 * handler signature: function(array $params): void
 */
return [
    ['GET', '/', 'home'],
    ['GET', '/health', 'health'],

    ['GET', '/login', 'admin_login_form'],
    ['POST', '/login', 'admin_login_submit'],
    ['GET', '/signup', 'admin_signup_form'],
    ['POST', '/signup', 'admin_signup_submit'],
    ['POST', '/admin/logout', 'admin_logout'],

    ['GET', '/admin', 'admin_dashboard'],
    ['GET', '/admin/students/search', 'admin_student_search'],
    ['GET', '/admin/students/show', 'admin_student_show'], // expects ?student_id=

    ['GET', '/admin/schedule', 'admin_schedule'],
    ['GET', '/admin/holds', 'admin_holds_index'],
    ['GET', '/admin/holds/show', 'admin_holds_show'],
    ['POST', '/admin/holds/add', 'admin_holds_add'],
    ['POST', '/admin/holds/clear', 'admin_holds_clear'],
];

