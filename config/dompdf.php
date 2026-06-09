<?php

return [
    'show_warnings'   => false,
    'public_path'     => null,
    'convert_entities' => true,

    'options' => [
        'font_dir'                          => storage_path('fonts'),
        'font_cache'                        => storage_path('fonts'),
        'temp_dir'                          => sys_get_temp_dir(),
        'chroot'                            => realpath(base_path()),
        'allowed_protocols'                 => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'http://'  => ['rules' => []],
            'https://' => ['rules' => []],
        ],
        'log_output_file'                   => null,
        'is_font_subsetting_enabled'        => false,
        'default_media_type'                => 'screen',
        'default_paper_size'                => 'a4',
        'default_paper_orientation'         => 'portrait',
        'default_font'                      => 'dejavu sans',
        'dpi'                               => 96,
        'is_php_enabled'                    => false,
        'is_remote_enabled'                 => false,
        'is_javascript_enabled'             => false,
        'is_html5_parser_enabled'           => true,
        'font_height_ratio'                 => 1.1,
        'enable_css_float'                  => false,
        'enable_font_subsetting'            => false,
        'pdf_backend'                       => 'CPDF',
        'pdfa_autofetch'                    => false,
        'pdfa_version'                      => '1b',
    ],
];
