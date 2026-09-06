<?php
defined('MOODLE_INTERNAL') || die();

$systemcontext = context_system::instance();
$canaccessmanage = $hassiteconfig
    || has_any_capability(['local/go:view', 'local/go:manage'], $systemcontext);

if ($canaccessmanage) {
    // Категория и страница списка доступны и без site:config.
    $ADMIN->add('localplugins', new admin_category('local_go', get_string('pluginname', 'local_go')));

    $ADMIN->add('local_go', new admin_externalpage(
        'local_go_manage',
        get_string('manage', 'local_go'),
        new moodle_url('/local/go/manage.php'),
        ['local/go:view', 'local/go:manage']
    ));
}

if ($hassiteconfig) {
    // Настройки плагина — только администраторам сайта.
    $settings = new admin_settingpage('local_go_settings', get_string('settings', 'local_go'));
    $ADMIN->add('local_go', $settings);

    // Главный переключатель.
    $setting = new admin_setting_configcheckbox(
        'local_go/enabled',
        get_string('globalenable', 'local_go'),
        get_string('globalenable_desc', 'local_go'),
        1
    );
    $setting->set_updatedcallback('local_go_refresh_snapshot_callback');
    $settings->add($setting);

    // Разрешить перенаправления для гостей.
    $setting = new admin_setting_configcheckbox(
        'local_go/allowguests',
        get_string('allowguests', 'local_go'),
        get_string('allowguests_desc', 'local_go'),
        0
    );
    $setting->set_updatedcallback('local_go_refresh_snapshot_callback');
    $settings->add($setting);

    // Быстрый путь без полного bootstrap Moodle.
    $setting = new admin_setting_configcheckbox(
        'local_go/fastredirects',
        get_string('fastredirects', 'local_go'),
        get_string('fastredirects_desc', 'local_go'),
        1
    );
    $setting->set_updatedcallback('local_go_refresh_snapshot_callback');
    $settings->add($setting);
}
