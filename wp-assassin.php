<?php
/*
Plugin Name: WP Assassin
Plugin URI: http://azbuki.info/viewforum.php?f=30
Description: Protection from spam through your blog || Защита от рассылки спама через ваш блог
Version: 150719
Author: Evgen Yurchenko
Author URI: http://yur4enko.com/
*/

/*  Copyright 2015 Evgen Yurchenko  (email: evgen@yur4enko.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc.
*/

class wpa_main_class {

    private $haccess;
    private $folder_dir;
            
    function __construct() {
        $site = get_option('home');
        $files = get_option('siteurl');
        $pref = str_replace($site, '', $files);
        $path = empty($pref)?ABSPATH:substr(ABSPATH, 0, -strlen($pref));
        $this->haccess = $path.'.htaccess';
        $dirs['content'] = str_replace($site.'/', '', content_url().'/');
        $dirs['admin'] = str_replace($site.'/', '', admin_url());
        $dirs['includes'] = str_replace($site.'/', '', includes_url());
        $this->folder_dir = $dirs;
    }
    
    protected function genRules($n, $link) {
        $dirs = $this->folder_dir;
        $r['001'] = 'RewriteRule ^' . $dirs['content'].'(.*).php$ ' . $link . ' [R=301,L] #WP-Assassin_001';
        $r['002'] = 'RewriteRule ^' . $dirs['includes'].'(.*).php$ ' . $link . ' [R=301,L] #WP-Assassin_002';
        $r['003'] = 'RewriteRule ^' . $dirs['admin'].'(.*)/(.*).php$ ' . $link . ' [R=301,L] #WP-Assassin_003';
        $ret = '#WP-Assassin START
RewriteEngine On #WP-Assassin
';
        foreach ($n as $value) {
            if (!empty($r[$value])) {
                $ret .= $r[$value] . '
';
            }
        }
        $ret .= '#WP-Assassin END';
        return $ret;
    }

    protected function getcleancont() {
        $f = fopen($this->haccess, r);
        $p = '';
        if ($f) {
            while (($str = fgets($f)) !== FALSE) {
                if (stristr($str, '#WP-Assassin') === FALSE) {
                    $p .= $str;
                }
            }
        }
        fclose($f);
        return $p;
    }

    protected function updatesettings($r, $link) {
        file_put_contents($this->haccess, $this->getcleancont().$this->genRules($r, $link));
    }

    //рабочие формы
    function main_settings() {
        $setting = get_option('WPA_set');
        if (gettype($setting) != 'array') {
            $setting = array();
        }

        $tmp = filter_input(INPUT_POST, 'apply');
        if (!empty($tmp)) {
            $inp['001'] = filter_input(INPUT_POST, 'r1');
            $inp['002'] = filter_input(INPUT_POST, 'r2');
            $inp['003'] = filter_input(INPUT_POST, 'r3');
            $link = filter_input(INPUT_POST, 'link');
            $setting['link'] = $link;
            update_option('WPA_set', $setting);
            $this->updatesettings($inp, $link);
        }

        $f = fopen($this->haccess, r);
        if ($f) {
            while (($str = fgets($f)) !== FALSE) {
                $s = stristr($str, '#WP-Assassin_');
                if (!empty($s)) {
                    $id = trim(substr(stristr($s, '_'), 1));
                    $n[$id] = ' checked';
                }
            }
        }
        fclose($f);

        $link = $setting['link'];
        if (empty($link)) {
            $link = 'http://localhost';
        }
        echo '<h2>WP-Assassin защищает директории:</h2>
        <form method=POST>
        <fieldset class="options">
            <input type="checkbox" name="r1" value="001"' . $n['001'] . '>wp-content<br>
            <input type="checkbox" name="r2" value="002"' . $n['002'] . '>wp-includes<br>
            <input type="checkbox" name="r3" value="003"' . $n['003'] . '>wp-admin<br>
            Переадресовывать на: <input type="text" name="link" value="' . $link . '"><br>
            <p><input type="submit" value="Отправить" name="apply">
       	</fieldset>
	</form>';
    }

    //Активация деактивация
    function activations() {
        $n = array();
        file_put_contents($this->haccess, $this->genRules($n, ''), FILE_APPEND | LOCK_EX);
    }

    function deactivations() {
        file_put_contents($this->haccess, $this->getcleancont());
    }
}

function WPA_activations() {
    $class = New wpa_main_class();
    $class->activations();
}

function WPA_deactivations() {
    $class = New wpa_main_class();
    $class->deactivations();
}

function WPA_settings() {
    $class = New wpa_main_class();
    $class->main_settings();
}

function WPA_add_menu() {
    add_options_page('WP-Assassin', 'Assassin', 8, __FILE__, 'WPA_settings');
}

add_action('admin_menu', 'WPA_add_menu');
register_activation_hook( __FILE__, 'WPA_activations' );
register_deactivation_hook( __FILE__, 'WPA_deactivations');
