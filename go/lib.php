<?php
defined('MOODLE_INTERNAL') || die();

function local_go_extend_navigation(global_navigation $navigation) {
    global $PAGE;
    
    if (has_capability('local/go:manage', context_system::instance())) {
        $node = $navigation->add(
            get_string('manage', 'local_go'),
            new moodle_url('/local/go/manage.php'),
            navigation_node::TYPE_SETTING,
            null,
            'local_go_manage',
            new pix_icon('t/nav_link', '')
        );
        $node->showinflatnavigation = true;
    }
}
