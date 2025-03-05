<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'theme_dev_test';
$plugin->version   = 2025030500;
$plugin->requires  = 2021051700;
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0';
$plugin->dependencies = ['theme_boost' => 2021051700]; // Kế thừa từ theme Boost
