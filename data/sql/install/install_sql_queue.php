<?php return [
    /*'domains.sql',

    'tables/languages.sql',
    'tables/dict_ip.sql',

    'tables/groups.sql',
    'tables/groups_langs.sql',
    'tables/groups_langs.sql',
    'tables/groups_relationships.sql',

    'tables/members.sql',
    'tables/members_groups.sql',
    'tables/members_auth.sql',
    'tables/members_avatars.sql',
    'tables/members_change_email.sql',
    'tables/members_cookies.sql',
    'tables/members_keys.sql',
    'tables/members_log_auth.sql',
    'tables/members_log_email.sql',
    'tables/members_log_ip.sql',
    'tables/members_log_nick.sql',

    'tables/settings_systems.sql',
    'tables/settings.sql',

    'tables/members_settings.sql',

    'tables/sites.sql',
    'tables/sites_controllers.sql',
    'tables/sites_controllers_langs.sql',
    'tables/sites_controllers_to_site.sql',
    'tables/sites_domains.sql',
    'tables/sites_icons.sql',
    'tables/sites_meta.sql',

    'tables/sites_manifests_display.sql',
    'tables/sites_manifests_orientation.sql',
    'tables/sites_manifests_meta.sql',
    'tables/sites_manifests.sql',
    'tables/sites_routes.sql',


    'tables/apps.sql',
    'tables/apps_langs.sql',
    'tables/apps_access_groups.sql',
    'tables/apps_access_members.sql',
    'tables/apps_access_sites.sql',


    'tables/resources_types.sql',
    'tables/resources_packages.sql',
    'tables/resources_packages_to_apps.sql',
    'tables/resources_packages_to_sites.sql',
    'tables/resources.sql',
    'tables/resources_to_apps.sql',
    'tables/resources_to_sites.sql',
    'tables/resources_excludes.sql',


    'tables/rights.sql',
    'tables/rights_langs.sql',
    'tables/rights_to_apps.sql',
    'tables/rights_to_controllers.sql',
    'tables/rights_to_sites.sql',
    'tables/rights_relationships.sql',
    'tables/groups_rights.sql',
    'tables/members_rights.sql',



    'tables/cnt_images.sql',
    'tables/cnt_seo.sql',
    'tables/cnt_seo_langs.sql',

    //--------------------------------------------------------------------
    // ВЬЮШКИ
    //--------------------------------------------------------------------

    'views/groups_list.sql',

    'views/apps_access_groups_with_links.sql',
    'views/apps_access_sites_with_links.sql',
    'views/apps_access_members_with_links.sql',
    'views/apps_list.sql',

    'views/resources_apps_js.sql',
    'views/resources_explorer_js_blocks.sql',
    'views/resources_packages_sites_js.sql',
    'views/resources_packages_system.sql',
    'views/resources_require_js.sql',
    'views/resources_sites_js.sql',
    'views/resources_system.sql',

    'views/members_list_with_group_lang.sql',
    'views/members_groups_links.sql',

    'views/sites_list.sql',
    'views/sites_manifests_html_icons.sql',
    'views/sites_manifests_html_meta.sql',
    'views/sites_manifests_json.sql',
    'views/sites_manifests_json_icons.sql',
    'views/sites_manifests_ms_icons.sql',
    'views/sites_manifests_ogtc_icons.sql',
    'views/sites_manifests_ogtc_meta.sql',
    'views/sites_controllers_list.sql',
    'views/sites_controllers_linked_to_sites.sql',

    'views/resources_explorer_css_blocks.sql',
    'views/resources_apps_css.sql',
    'views/resources_sites_css.sql',

    'views/rights_to_noon_list.sql',
    'views/rights_to_apps_list.sql',
    'views/rights_to_controllers_list.sql',
    'views/rights_to_sites_list.sql',
    'views/rights_list_complete.sql',
    'views/rights_list_links_full.sql',
    'views/rights_granted_to_members.sql',
    'views/rights_granted_to_groups.sql',

    'views/cnt_seo_list_lang.sql',

    //--------------------------------------------------------------------
    // ФУНКЦИИ
    //--------------------------------------------------------------------

    'routines/dp_sites_add.sql',
    'routines/dp_sites_change.sql',
    'routines/dp_sites_counters_change.sql',
    'routines/dp_sites_domain_add.sql',
    'routines/dp_sites_domain_delete.sql',
    'routines/dp_sites_icon_add.sql',
    'routines/dp_sites_icon_delete.sql',
    'routines/dp_sites_manifest_android_edit.sql',
    'routines/dp_sites_manifest_ios_edit.sql',
    'routines/dp_sites_manifest_meta_edit.sql',
    'routines/dp_sites_manifest_ms_edit.sql',
    'routines/dp_sites_manifest_ogtc_edit.sql',
    'routines/dp_sites_manifest_ogtc_meta.sql',
    'routines/dp_sites_meta_edit.sql',
    'routines/dp_sites_controllers_add.sql',
    'routines/dp_sites_controllers_edit.sql',
    'routines/dp_sites_controllers_activated.sql',
    'routines/dp_sites_controllers_unplug.sql',
    'routines/dp_sites_controllers_plugin.sql',

    'routines/dp_rights_add.sql',
    'routines/dp_rights_edit.sql',
    'routines/dp_rights_change_parent.sql',
    'routines/dp_rights_delete.sql',
    'routines/dp_rights_add_to_app.sql',
    'routines/dp_rights_add_to_controller.sql',
    'routines/dp_rights_add_to_site.sql',
    'routines/dp_rights_link_member.sql',
    'routines/dp_rights_link_group.sql',
    'routines/dp_rights_link_group_inherit.sql',
    'routines/dp_rights_get_links_to_groups.sql',
    'routines/dp_rights_get_links_to_members.sql',

    'routines/dp_resources_package_add_file.sql',
    'routines/dp_resources_package_change_name.sql',
    'routines/dp_resources_package_add.sql',
    'routines/dp_resources_package_add_require.sql',
    'routines/dp_resources_app_add_file.sql',
    'routines/dp_resources_site_add_file.sql',
    'routines/dp_resources_package_add_site.sql',
    'routines/dp_resources_package_remove_site.sql',
    'routines/dp_resources_exclude_add_site.sql',
    'routines/dp_resources_exclude_remove_site.sql',
    'routines/dp_resources_get_list_js.sql',
    'routines/dp_resources_get_list_css.sql',
    'routines/dp_resources_get_list_js_groups.sql',
    'routines/dp_resources_get_list_css_groups.sql',
    'routines/dp_resources_package_delete.sql',
    'routines/dp_resources_get_list_css_compiled.sql',
    'routines/dp_resources_get_list_js_compiled.sql',
    'routines/dp_resources_get_list_js_controllers.sql',
    'routines/dp_sites_route_add.sql',
    'routines/dp_sites_route_save.sql',
    'routines/dp_sites_route_switch.sql',
    'routines/dp_sites_route_default_switch.sql',
    'routines/cnt_seo_save.sql',

    'routines/dp_apps_access_groups_link.sql',
    'routines/dp_apps_access_groups_unlink.sql',
    'routines/dp_apps_access_members_link.sql',
    'routines/dp_apps_access_members_unlink.sql',
    'routines/dp_apps_access_sites_link.sql',
    'routines/dp_apps_access_sites_unlink.sql',
    'routines/dp_apps_edit.sql',
    'routines/dp_apps_add.sql',
    'routines/dp_apps_member_search.sql',



    'routines/dp_members_activation.sql',
    'routines/dp_members_activation_mail_change.sql',
    'routines/dp_members_activation_mail_change_check.sql',
    'routines/dp_members_activation_mail_change_swap.sql',
    'routines/dp_members_delete.sql',
    'routines/dp_members_get_by_cookie.sql',

    'routines/dp_members_change_gender.sql',
    'routines/dp_members_change_nick.sql',
    'routines/dp_members_change_password.sql',
    'routines/dp_members_change_password_and_email.sql',
    'routines/dp_members_change_primary_group.sql',
    'routines/dp_members_change_right_link_toggle.sql',
    'routines/dp_members_change_group_link_toggle.sql',
    'routines/dp_members_rights_links_list.sql',




    'routines/dp_groups_add.sql',
    'routines/dp_groups_change_parent.sql',
    'routines/dp_groups_edit.sql',
    'routines/dp_groups_calculate_groups.sql',
    'routines/dp_groups_calculate_members.sql',
    'routines/dp_groups_calculate_rights.sql',


    //--------------------------------------------------------------------
    // ТРИГГЕРЫ ВМЕСТЕ С ФУНКЦИЯМИ
    //--------------------------------------------------------------------

    'triggers/members_triggers.sql',
    'triggers/cnt_seo_triggers.sql',
    'triggers/rights_triggers.sql',

    //--------------------------------------------------------------------
    // ЗАГРУЗКА ДАННЫХ
    //--------------------------------------------------------------------

    'data/data_languages.sql',
    'data/data_sites_manifests_display.sql',
    'data/data_sites_manifests_orientation.sql',

    'data/data_groups.sql',
    'data/data_groups_langs.sql',

    'data/data_resources_types.sql',
    'data/data_resources_packages.sql',
    'data/data_resources.sql',
    'data/data_sites_controllers.sql',*/
    //'data/data_sites_controllers_to_sites.sql',
];