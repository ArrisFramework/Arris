<?php

function getServerTimeZone()
{
    $timezone = 'Europe/Moscow'; //'UTC';

    return $timezone;

    if (is_link('/etc/localtime')) {
        // Mac OS X (and older Linuxes)
        // /etc/localtime is a symlink to the
        // timezone in /usr/share/zoneinfo.
        $filename = readlink('/etc/localtime');
        $pos = strpos($filename, '/usr/share/zoneinfo/');
        if ($pos !== false) {
            $timezone = substr($filename, $pos + 20);
        }
    } elseif (is_file('/etc/timezone')) {
        // Ubuntu / Debian.
        $data = file_get_contents('/etc/timezone');
        if ($data) {
            $timezone = $data;
        }
    } elseif (is_file('/etc/sysconfig/clock')) {
        // RHEL / CentOS
        $data = parse_ini_file('/etc/sysconfig/clock');
        if (!empty($data['ZONE'])) {
            $timezone = $data['ZONE'];
        }
    }

    return $timezone;
}
