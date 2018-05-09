<?php
function finndy_get_version() {
    $reply = array(
        'protocol' => '1',
        'protocolVersion' => '1',
        'supportStdVersion' => array(
            'article' => '1.0.0',
            'question' => '1.0.0'
        ),
        'php' => PHP_VERSION,
        'supportVersion' => 'phpwind 9.X',
        'version' => '1.0',
        'pubVersion' => '1.0',
        'versionDetail' => array('wind_version' => WIND_VERSION), //set by this service
        'otherInfo' => array(),
        'versionDetail' => array('wekit_version' => WEKIT_VERSION, 'next_version' => NEXT_VERSION, 'next_release' => NEXT_RELEASE, 'next_fixbug' => NEXT_FIXBUG)
    );
    return $reply;
}