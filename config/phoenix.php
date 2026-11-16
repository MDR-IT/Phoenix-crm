<?php

return [
    'roles' => [
        'Super Administrator',
        'CEO',
        'Sales Manager',
        'Call Centre Supervisor',
        'Call Centre Agent',
        'Sales Agent',
        'Marketing Officer',
        'Compliance Officer',
        'Auditor',
    ],

    'permissions' => [
        'dashboard.view',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'roles.view',
        'roles.update',
        // Placeholders for later phases
        'leads.view',
        'leads.manage',
        'reports.view',
        'integrations.view',
    ],

    /*
    | Role => permission grants. Super Administrator receives all permissions.
    */
    'role_permissions' => [
        'Super Administrator' => ['*'],
        'CEO' => [
            'dashboard.view',
            'users.view',
            'roles.view',
            'leads.view',
            'reports.view',
        ],
        'Sales Manager' => [
            'dashboard.view',
            'users.view',
            'leads.view',
            'leads.manage',
            'reports.view',
        ],
        'Call Centre Supervisor' => [
            'dashboard.view',
            'users.view',
            'leads.view',
            'leads.manage',
            'reports.view',
        ],
        'Call Centre Agent' => [
            'dashboard.view',
            'leads.view',
            'leads.manage',
        ],
        'Sales Agent' => [
            'dashboard.view',
            'leads.view',
            'leads.manage',
        ],
        'Marketing Officer' => [
            'dashboard.view',
            'leads.view',
            'reports.view',
            'integrations.view',
        ],
        'Compliance Officer' => [
            'dashboard.view',
            'users.view',
            'roles.view',
            'leads.view',
            'reports.view',
        ],
        'Auditor' => [
            'dashboard.view',
            'users.view',
            'roles.view',
            'leads.view',
            'reports.view',
        ],
    ],
];
