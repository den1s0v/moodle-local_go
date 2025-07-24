<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/go:manage' => [
        'riskbitmask'  => RISK_CONFIG | RISK_XSS,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW
        ]
    ],
    
    'local/go:config' => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW
        ]
    ]
];
