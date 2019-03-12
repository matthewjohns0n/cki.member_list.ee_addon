<?php

if (! defined('CKI_MBLIST_KEY')) {
    define('CKI_MBLIST_NAME', 'CKI Member List');
    define('CKI_MBLIST_VER', '2.0.0');
    define('CKI_MBLIST_KEY', 'cki_mblist');
    define('CKI_MBLIST_AUTHOR', 'Christopher Imrie');
    define('CKI_MBLIST_DOCS', 'https://github.com/ckimrie/cki.member_list.ee_addon');
    define('CKI_MBLIST_DESC', 'ExpressionEngine 3+ custom field of your current members, with flexible options for publishing that member\'s data in templates');
}

return array(
    'author'         => CKI_MBLIST_AUTHOR,
    'author_url'     => CKI_MBLIST_DOCS,
    'name'           => CKI_MBLIST_NAME,
    'description'    => CKI_MBLIST_DESC,
    'version'        => CKI_MBLIST_VER,
    'docs_url'       => CKI_MBLIST_DOCS,
    'namespace'      => 'EEHarbor\CKIMemberList',
    'settings_exist' => false,
    'fieldtypes' => array(
      CKI_MBLIST_KEY => array(
        'name' => CKI_MBLIST_NAME
      )
    )
);
