<?php

return [
    'users' => [
        'name' => 'Team',
        'link' => '#',
        'isActive' => false,
        'iconClass' => 'fa-users',
        'permissionsNeeded' => 'manage_users',
        'subMenu' => [
            'users_manager' => [
                'name' => 'Users Manager',
                'link' => 'admin.users.index',
                'isActive' => false,
                'permissionsNeeded' => 'manage_users',
            ],
            'permissions' => [
                'name' => 'Permissions',
                'link' => 'permissions.index',
                'isActive' => false,
                'iconClass' => 'fa-lock',
                'permissionsNeeded' => 'manage_users',
            ],
        ],
    ],
    'seo_pages' => [
        'name' => 'SEO Pages',
        'link' => 'seo_pages.index',
        'isActive' => false,
        'iconClass' => 'fa-tags',
        'permissionsNeeded' => 'manage_users',
    ],

];
