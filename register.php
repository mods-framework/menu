<?php

return [
    'mod_menu' => [
        'name' => 'Menu',
        'type' => 'module',
        'providers' => [
            Mods\Menu\MenuServiceProvider::class
        ],
        'aliases' => [
            'Menu' => 'Mods\Menu\Facades\Menu',
        ],
        'depends' => [
        ],
        'autoload' => [
            'psr-4' => [
                'Mods\\Menu\\' => realpath(__DIR__.'/src/')
            ]
        ]
    ]
];
