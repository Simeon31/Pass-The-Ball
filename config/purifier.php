<?php

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings' => [
        'default' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'h1,h2,h3,h4,h5,h6,p,br,strong,em,b,i,u,a[href|title|target|rel],ul,ol,li,blockquote',
            'HTML.ForbiddenElements' => 'script,style,iframe,object,embed',
            'HTML.ForbiddenAttributes' => 'onclick,onload,onerror,onmouseover',
            'CSS.AllowedProperties' => '',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
            'Attr.AllowedFrameTargets' => ['_blank'],
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
            'URI.DisableExternalResources' => false,
        ],
        'post_content' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'h1,h2,h3,p,br,strong,em,b,i,a[href|title|target|rel],ul,ol,li,blockquote',
            'HTML.ForbiddenElements' => 'script,style,iframe,object,embed,form,input,button',
            'HTML.ForbiddenAttributes' => 'onclick,onload,onerror,onmouseover,onsubmit,onfocus,onblur',
            'CSS.AllowedProperties' => '',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
            'Attr.AllowedFrameTargets' => ['_blank'],
            'Attr.DefaultImageAlt' => '',
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
            'URI.DisableExternalResources' => false,
        ],
    ],
];
