<?php
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'mytheme';
$THEME->sheets = ['styles'];
$THEME->parents = ['boost'];
$THEME->layouts = [
    'base' => [
        'file' => 'layout/default.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
];
