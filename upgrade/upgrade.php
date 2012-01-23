<?php
/* ========================================================================
 * Open eClass 2.4
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2011  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

session_start();

//Flag for fixing relative path
//See init.php to undestand its logic
$path2add = 2;

include '../include/baseTheme.php';
include '../include/lib/fileUploadLib.inc.php';
include '../include/lib/forcedownload.php';

// set default storage engine
db_query("SET storage_engine=MYISAM");

include 'upgrade_functions.php';

set_time_limit(0);

load_global_messages();

// include_messages
include("${webDir}modules/lang/$language/common.inc.php");
$extra_messages = "${webDir}/config/$language.inc.php";
if (file_exists($extra_messages)) {
        include $extra_messages;
} else {
        $extra_messages = false;
}
include("${webDir}modules/lang/$language/messages.inc.php");
if ($extra_messages) {
        include $extra_messages;
}

$nameTools = $langUpgrade;
$tool_content = "";

$auth_methods = array("imap","pop3","ldap","db");
$OK = "[<font color='green'> $langSuccessOk </font>]";
$BAD = "[<font color='red'> $langSuccessBad </font>]";

// default quota values  (if needed)
$diskQuotaDocument = 40000000;
$diskQuotaGroup = 40000000;
$diskQuotaVideo = 20000000;
$diskQuotaDropbox = 40000000;

$fromadmin = true;

if (isset($_POST['submit_upgrade'])) {
	$fromadmin = false;
}

if (!defined('UTF8')) {
        $Institution = iconv('ISO-8859-7', 'UTF-8', $Institution);
        $postaddress = iconv('ISO-8859-7', 'UTF-8', $postaddress);
}

if (!isset($_POST['submit2'])) {
        if(isset($encryptedPasswd) and $encryptedPasswd) {
                $newpass = md5(@$_POST['password']);
        } else {
                // plain text password since the passwords are not hashed
                $newpass = @$_POST['password'];
        }

        if (!is_admin(@$_POST['login'], $newpass, $mysqlMainDb)) {
                $tool_content .= "<p class='alert1'>$langUpgAdminError</p>
                        <center><a href=\"index.php\">$langBack</a></center>";
                draw($tool_content, 0);
                exit;
        }
}
$_SESSION['user_perso_active'] = false;
// Make sure 'video' subdirectory exists and is writable
if (!file_exists('../video')) {
        if (!mkdir('../video')) {
                die("$langUpgNoVideoDir");
        }
} elseif (!is_dir('../video')) {
        die("$langUpgNoVideoDir2");
} elseif (!is_writable('../video')) {
        die("$langUpgNoVideoDir3");
}

mkdir_or_error('../courses/temp');
mkdir_or_error('../courses/userimg');

// ********************************************
// upgrade config.php
// *******************************************
if (!@chdir("../config/")) {
     die ("$langConfigError4");
}

if (!isset($_POST['submit2'])) {      
        if (ini_get('register_globals')) { // check if register globals is Off     
                $tool_content .= "<div class='caution'>$langWarningInstall1</div>";
        }
        if (ini_get('short_open_tag')) { // check if short_open_tag is Off
                $tool_content .= "<div class='caution'>$langWarningInstall2</div>";
        }                
        // get old contact values
        $tool_content .= "<form action='$_SERVER[PHP_SELF]' method='post'>" .
                "<div class='info'>" .
                "<p>$langConfigFound" .
                "<br>$langConfigMod</p></div>" .
                "<fieldset><legend>$langUpgContact</legend>" .
                "<table width='100%' class='tbl'><tr><th width='220'>$langInstituteShortName:</th>" .
                "<td><input class=auth_input_admin type='text' size='40' name='Institution' value='".@$Institution."'></td></tr>" .
                "<tr><th>$langUpgAddress</th>" .
                "<td><textarea rows='3' cols='40' class=auth_input_admin name='postaddress'>".@$postaddress."</textarea></td></tr>" .
                "<tr><th>$langUpgTel</th>" .
                "<td><input class=auth_input_admin type='text' name='telephone' value='".@$telephone."'></td></tr>" .
                "<tr><th>Fax:</th>" .
                "<td><input class=auth_input_admin type='text' name='fax' value='".@$fax."'></td></tr></table></fieldset>				                
                <div class='right'><input name='submit2' value='$langCont &raquo;' type='submit'></div>
                </form>";
} else {
        // Main part of upgrade starts here
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo $langUpgrade; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link href="../template/<?php echo $theme ?>/theme.css" rel="stylesheet" type="text/css" />
</head>
<body class='upgrade-main'>
  <div id="container" style="padding: 30px;">
  <div id="header"> 

<a href="<?php echo $urlAppend ?>" title="<?php echo q($siteName) ?>" class="logo"></a></div>
<?php

        echo "<p class='title1'>$langUpgradeStart</p>",
             "<p class='sub_title1'>$langUpgradeConfig</p>";
	flush();
        // backup of config file
        if (!copy("config.php","config_backup.php"))
                die ("$langConfigError1");

        $conf = file_get_contents("config.php");
        if (!$conf)
                die ("$langConfigError2");

        $lines_to_add = "";

        // Convert to UTF-8 if needed
        if (!defined('UTF8')) {
                $lines_to_add .= "define('UTF8', true);\n";
                $conf = iconv('ISO-8859-7', 'UTF-8', $conf);
        }

        // for upgrading 1.5 --> 1.7
        if (!strstr($conf, '$postaddress')) {
                $lines_to_add .= "\$postaddress = '$_POST[postaddress]';\n";
        }
        if (!strstr($conf, '$fax')) {
                $lines_to_add .= "\$fax = '$_POST[fax]';\n";
        }        
        
        if (!strstr($conf, '$durationAccount')) {
                $lines_to_add .= "\$durationAccount = \"126144000\";\n";
        }
        if (!strstr($conf, '$persoIsActive')) {
                $lines_to_add .= "\$persoIsActive = true;\n";
        }
        if (!strstr($conf, '$encryptedPasswd')) {
                $lines_to_add .= "\$encryptedPasswd = true;\n";
        }        
        $new_copyright = file_get_contents('../info/license/header.txt');

        $new_conf = preg_replace(
                        array(
				'#^.*(mainInterfaceWidth|bannerPath|userMailCanBeEmpty|colorLight|colorMedium|colorDark|table_border|color1|color2).*$#m',
                                '#\$postaddress\b[^;]*;#sm',
                                '#\$fax\b[^;]*;#',                                
                                '#(\?>)?\s*$#',
                                '#\$Institution\b[^;]*;#',
                                '#\$telephone\b[^;]*;#',
                                '#^/\*$.*^\*/$#sm',
                                '#\/\/ .*^\/\/ HTTP_COOKIE[^\n]+$#sm'),
                        array(
				'',
                                "\$postaddress = '$_POST[postaddress]';",
                                "\$fax = '$_POST[fax]';",                                
                            	'',
                                "\$Institution = '$_POST[Institution]';",
                                "\$telephone = '$_POST[telephone]';",
                                $new_copyright,
                                ''),
                        $conf) . "\n" . $lines_to_add;
        $fp = @fopen("config.php","w");
        if (!$fp)
                die ("$langConfigError3");
        fwrite($fp, $new_conf);
        fclose($fp);


        // ****************************************************
        // 		upgrade eclass main database
        // ****************************************************

	echo "<p class='sub_title1'>$langUpgradeBase <b>$mysqlMainDb</b></p>";
	flush();
        mysql_select_db($mysqlMainDb);

	// Create or upgrade config table
	if (!mysql_table_exists($mysqlMainDb, 'config')) {
                db_query("CREATE TABLE `config`
                                (`key` VARCHAR(32) NOT NULL,
                                 `value` VARCHAR(255) NOT NULL,
                                 PRIMARY KEY (`key`))");
                db_query("INSERT INTO `config` (`key`, `value`)
                                 VALUES ('version', '2.1.2')");
                $oldversion = '2.1.2';
	        db_query('SET NAMES greek');
        	// old queries
        	require "upgrade_main_db_old.php";
	} else {
                if (mysql_field_exists($mysqlMainDb, 'config', 'id')) {
                        db_query("RENAME TABLE config TO old_config");
                        db_query("CREATE TABLE `config`
                                        (`key` VARCHAR(32) NOT NULL,
                                         `value` VARCHAR(255) NOT NULL,
                                         PRIMARY KEY (`key`))");
                        db_query("INSERT INTO config
                                         SELECT `key`, `value` FROM old_config
                                         GROUP BY `key`");
                        db_query("DROP TABLE old_config");
                }
                $oldversion = get_config('version');
        }
        db_query("INSERT IGNORE INTO `config` (`key`, `value`) VALUES
  			('dont_display_login_form', '0'),
			('email_required', '0'),
                        ('email_from', '1'),
			('am_required', '0'),
			('dropbox_allow_student_to_student', '0'),
			('block_username_change', '0'),
			('betacms', '0'),
			('display_captcha', '0'),
			('insert_xml_metadata', '0'),
			('doc_quota', '200'),
			('dropbox_quota', '100'),
			('video_quota', '100'),
			('group_quota', '100')");

        if ($oldversion < '2.1.3') {
        	// delete useless field
        	if (mysql_field_exists($mysqlMainDb, 'cours', 'scoreShow')) {
	        	echo delete_field('cours', 'scoreShow');
                }
        	// delete old example test from table announcements
                $langAnnounceExample = 'Παράδειγμα ανακοίνωσης. Μόνο ο καθηγητής και τυχόν άλλοι διαχειριστές του μαθήματος μπορεί να ανεβάσουν ανακοινώσεις.';
                db_query('SET NAMES utf8');
	        db_query("DELETE from annonces WHERE contenu='$langAnnounceExample'");
        }

        if ($oldversion < '2.2') {
                // course units
		db_query("CREATE TABLE IF NOT EXISTS `course_units` (
			`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`title` VARCHAR(255) NOT NULL DEFAULT '',
			`comments` MEDIUMTEXT,
			`visibility` CHAR(1) NOT NULL DEFAULT 'v',
			`order` INT(11) NOT NULL DEFAULT 0,
			`course_id` INT(11) NOT NULL)");
                db_query("CREATE TABLE IF NOT EXISTS `unit_resources` (
			`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`unit_id` INT(11) NOT NULL ,
			`title` VARCHAR(255) NOT NULL DEFAULT '',
			`comments` MEDIUMTEXT,
			`res_id` INT(11) NOT NULL,
			`type` VARCHAR(255) NOT NULL DEFAULT '',
			`visibility` CHAR(1) NOT NULL DEFAULT 'v',
			`order` INT(11) NOT NULL DEFAULT 0,
			`date` DATETIME NOT NULL DEFAULT '0000-00-00')");
	}

        if ($oldversion < '2.2.1') {
                db_query("ALTER TABLE `cours` CHANGE `doc_quota` `doc_quota` FLOAT NOT NULL DEFAULT '104857600'"); 
                db_query("ALTER TABLE `cours` CHANGE `video_quota` `video_quota` FLOAT NOT NULL DEFAULT '104857600'");
                db_query("ALTER TABLE `cours` CHANGE `group_quota` `group_quota` FLOAT NOT NULL DEFAULT '104857600'"); 
                db_query("ALTER TABLE `cours` CHANGE `dropbox_quota` `dropbox_quota` FLOAT NOT NULL DEFAULT '104857600'");
                db_query("CREATE TABLE IF NOT EXISTS `forum_notify` (
                        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                        `user_id` INT NOT NULL DEFAULT '0',
                        `cat_id` INT NULL ,
                        `forum_id` INT NULL ,
                        `topic_id` INT NULL ,
                        `notify_sent` BOOL NOT NULL DEFAULT '0',
                        `course_id` INT NOT NULL DEFAULT '0')");
                
        	if (!mysql_field_exists($mysqlMainDb, 'cours_user', 'cours_id')) {
	        	db_query('ALTER TABLE cours_user ADD cours_id int(11) DEFAULT 0 NOT NULL FIRST');
                        db_query('UPDATE cours_user SET cours_id =
                                        (SELECT cours_id FROM cours WHERE code = cours_user.code_cours)
                                  WHERE cours_id = 0');
	        	db_query('ALTER TABLE cours_user DROP PRIMARY KEY, ADD PRIMARY KEY (cours_id, user_id)');
                        db_query('CREATE INDEX cours_user_id ON cours_user (user_id, cours_id)');
                        db_query('ALTER TABLE cours_user DROP code_cours');
                }

        	if (!mysql_field_exists($mysqlMainDb, 'annonces', 'cours_id')) {
	        	db_query('ALTER TABLE annonces ADD cours_id int(11) DEFAULT 0 NOT NULL AFTER code_cours');
                        db_query('UPDATE annonces SET cours_id =
                                        (SELECT cours_id FROM cours WHERE code = annonces.code_cours)
                                  WHERE cours_id = 0');
                        db_query('ALTER TABLE annonces DROP code_cours');
                }
        }
	if ($oldversion < '2.3.1') {
		if (!mysql_field_exists($mysqlMainDb, 'prof_request', 'am')) {
			db_query('ALTER TABLE `prof_request` ADD `am` VARCHAR(20) NULL AFTER profcomm');
		}
        }
	db_query("INSERT IGNORE INTO `auth` VALUES (7, 'cas', '', '', 0)");
        mysql_field_exists($mysqlMainDb, 'user', 'email_public') or
                db_query("ALTER TABLE `user`
                                ADD `email_public` TINYINT(1) NOT NULL DEFAULT 0,
                                ADD `phone_public` TINYINT(1) NOT NULL DEFAULT 0,
                                ADD `am_public` TINYINT(1) NOT NULL DEFAULT 0");
        if ($oldversion < '2.4') {
        	if (mysql_field_exists($mysqlMainDb, 'cours', 'faculte')) {
	        	echo delete_field('cours', 'faculte');
                }

		db_query("ALTER TABLE user CHANGE lang lang VARCHAR(16) NOT NULL DEFAULT 'el'");
                mysql_index_exists('user', 'user_username') or
                        db_query('CREATE INDEX user_username ON user (username)');
                mysql_index_exists('course_units', 'course_units_title') or
                        db_query('CREATE FULLTEXT INDEX course_units_title ON course_units (title)');
                mysql_index_exists('course_units', 'course_units_comments') or
                        db_query('CREATE FULLTEXT INDEX course_units_comments ON course_units (comments)');
                mysql_index_exists('unit_resources', 'unit_resources_title') or
                        db_query('CREATE FULLTEXT INDEX unit_resources_title ON unit_resources (title)');
                mysql_index_exists('unit_resources', 'unit_resources_title') or
                        db_query('CREATE FULLTEXT INDEX unit_resources_comments ON unit_resources (comments)');
                mysql_field_exists($mysqlMainDb, 'annonces', 'visibility') or
                        db_query("ALTER TABLE `annonces` ADD `visibility` CHAR(1) NOT NULL DEFAULT 'v'");
                mysql_field_exists($mysqlMainDb, 'user', 'description') or
                        db_query("ALTER TABLE `user` ADD description TEXT,
                                                     ADD has_icon BOOL NOT NULL DEFAULT 0");
                mysql_field_exists($mysqlMainDb, 'user', 'verified_mail') or
                        db_query("ALTER TABLE `user` ADD verified_mail BOOL NOT NULL DEFAULT ".EMAIL_UNVERIFIED.",
                                                     ADD receive_mail BOOL NOT NULL DEFAULT 1");
                mysql_field_exists($mysqlMainDb, 'cours_user', 'receive_mail') or
                        db_query("ALTER TABLE `cours_user` ADD receive_mail BOOL NOT NULL DEFAULT 1");
		db_query("ALTER TABLE `loginout` CHANGE `ip` `ip` CHAR(39) NOT NULL DEFAULT '0.0.0.0'");
                db_query("CREATE TABLE IF NOT EXISTS `document` (
                                `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `course_id` INT(11) NOT NULL,
				`subsystem` TINYINT(4) NOT NULL,	
				`subsystem_id` INT(11) DEFAULT NULL,
                                `path` VARCHAR(255) NOT NULL,
                                `filename` VARCHAR(255) NOT NULL,
                                `visibility` CHAR(1) NOT NULL DEFAULT 'v',
                                `comment` TEXT,
                                `category` TINYINT(4) NOT NULL DEFAULT 0,
                                `title` TEXT,
                                `creator` TEXT,
                                `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                                `date_modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                                `subject` TEXT,
                                `description` TEXT,
                                `author` VARCHAR(255) NOT NULL DEFAULT '',
                                `format` VARCHAR(32) NOT NULL DEFAULT '',
                                `language` VARCHAR(16) NOT NULL DEFAULT '',
                                `copyrighted` TINYINT(4) NOT NULL DEFAULT 0,
                                FULLTEXT KEY `document`
                                        (`filename`, `comment`, `title`, `creator`,
                                         `subject`, `description`, `author`, `language`))");
                db_query("CREATE TABLE IF NOT EXISTS `group_properties` (
                                `course_id` INT(11) NOT NULL PRIMARY KEY ,
                                `self_registration` TINYINT(4) NOT NULL DEFAULT 1,
                                `multiple_registration` TINYINT(4) NOT NULL DEFAULT 0,
                                `allow_unregister` TINYINT(4) NOT NULL DEFAULT 0,
                                `forum` TINYINT(4) NOT NULL DEFAULT 1,
                                `private_forum` TINYINT(4) NOT NULL DEFAULT 0,
                                `documents` TINYINT(4) NOT NULL DEFAULT 1,
                                `wiki` TINYINT(4) NOT NULL DEFAULT 0,
                                `agenda` TINYINT(4) NOT NULL DEFAULT 0)");
                db_query("CREATE TABLE IF NOT EXISTS `group` (
                                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `course_id` INT(11) NOT NULL DEFAULT 0,
                                `name` varchar(100) NOT NULL DEFAULT '',
                                `description` TEXT,
                                `forum_id` INT(11) NULL,
                                `max_members` INT(11) NOT NULL DEFAULT 0,
                                `secret_directory` varchar(30) NOT NULL DEFAULT '0')");
                db_query("CREATE TABLE IF NOT EXISTS `group_members` (
                                `group_id` INT(11) NOT NULL,
                                `user_id` INT(11) NOT NULL,
                                `is_tutor` INT(11) NOT NULL DEFAULT 0,
                                `description` TEXT,
                                PRIMARY KEY (`group_id`, `user_id`))");
                db_query("CREATE TABLE IF NOT EXISTS `glossary` (
			       `id` MEDIUMINT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			       `term` VARCHAR(255) NOT NULL,
			       `definition` text NOT NULL,
			       `url` text,
                               `order` INT(11) NOT NULL DEFAULT 0,
                               `datestamp` DATETIME NOT NULL,
                               `course_id` INT(11) NOT NULL)");
                db_query("CREATE TABLE IF NOT EXISTS `link` (
                                `id` INT(11) NOT NULL AUTO_INCREMENT,
                                `course_id` INT(11) NOT NULL,
                                `url` VARCHAR(255),
                                `title` VARCHAR(255),
                                `description` TEXT,
                                `category` INT(6) DEFAULT NULL,
                                `order` INT(6) DEFAULT 0 NOT NULL,
                                `hits` INT(6) DEFAULT 0 NOT NULL,
                                PRIMARY KEY (`id`, `course_id`))");
                db_query("CREATE TABLE IF NOT EXISTS `link_category` (
                                `id` INT(6) NOT NULL AUTO_INCREMENT,
                                `course_id` INT(11) NOT NULL,
                                `name` VARCHAR(255) NOT NULL,
                                `description` TEXT,
                                `order` INT(6) NOT NULL DEFAULT 0,
                                PRIMARY KEY (`id`, `course_id`))");
                db_query('CREATE TABLE IF NOT EXISTS ebook (
                                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `course_id` INT(11) NOT NULL,
                                `order` INT(11) NOT NULL,
                                `title` TEXT)');
                db_query('CREATE TABLE IF NOT EXISTS ebook_section (
                                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `ebook_id` INT(11) NOT NULL,
                                `public_id` VARCHAR(11) NOT NULL,
				`file` VARCHAR(128),
                                `title` TEXT)');
                db_query('CREATE TABLE IF NOT EXISTS ebook_subsection (
                                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `section_id` VARCHAR(11) NOT NULL,
                                `public_id` VARCHAR(11) NOT NULL,
                                `file_id` INT(11) NOT NULL,
                                `title` TEXT)');

                if (mysql_table_exists($mysqlMainDb, 'prof_request')) {
                        db_query("RENAME TABLE prof_request TO user_request");
                        db_query("ALTER TABLE user_request
                                        CHANGE rid id INT(11) NOT NULL auto_increment,
                                        CHANGE profname name VARCHAR(255) NOT NULL DEFAULT '',
                                        CHANGE profsurname surname VARCHAR(255) NOT NULL DEFAULT '',
                                        CHANGE profuname uname VARCHAR(255) NOT NULL DEFAULT '',
                                        CHANGE profpassword password VARCHAR(255) NOT NULL DEFAULT '',
                                        CHANGE profemail email varchar(255) NOT NULL DEFAULT '',
                                        CHANGE proftmima faculty_id INT(11) NOT NULL DEFAULT 0,
                                        CHANGE profcomm phone VARCHAR(20) NOT NULL DEFAULT '',
                                        CHANGE lang lang VARCHAR(16) NOT NULL DEFAULT 'el',
                                        ADD ip_address INT(11) UNSIGNED NOT NULL DEFAULT 0");
                }

                // Upgrade table admin_announcements if needed
                if (mysql_field_exists($mysqlMainDb, 'admin_announcements', 'gr_body')) {
                        db_query("RENAME TABLE `admin_announcements` TO `admin_announcements_old`");
                        db_query("CREATE TABLE IF NOT EXISTS `admin_announcements` (
                                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                                        `title` VARCHAR(255) DEFAULT NULL,
                                        `body` TEXT,
                                        `date` DATETIME NOT NULL,
                                        `begin` DATETIME DEFAULT NULL,
                                        `end` DATETIME DEFAULT NULL,
                                        `visible` ENUM('V','I') NOT NULL,
                                        `lang` VARCHAR(10) NOT NULL DEFAULT 'el',
                                        `ordre` MEDIUMINT(11) NOT NULL DEFAULT 0,
                                        PRIMARY KEY (`id`))");
                        
                        $aq = db_query("INSERT INTO admin_announcements (title, body, `date`, visible, lang)
                                        SELECT gr_title AS title, CONCAT_WS('  ', gr_body, gr_comment) AS body, `date`, visible, 'el'
                                        FROM admin_announcements_old WHERE gr_title <> '' OR gr_body <> ''");     
                        $adm = db_query("INSERT INTO admin_announcements (title, body, `date`, visible, lang)
                                         SELECT en_title AS title, CONCAT_WS('  ', en_body, en_comment) AS body, `date`, visible, 'en'
                                         FROM admin_announcements_old WHERE en_title <> '' OR en_body <> ''");     
                        db_query("DROP TABLE admin_announcements_old");
                }
                mysql_field_exists($mysqlMainDb, 'admin_announcements', 'ordre') or
                        db_query("ALTER TABLE `admin_announcements` ADD `ordre` MEDIUMINT(11) NOT NULL DEFAULT 0 AFTER `lang`");
		// not needed anymore
		if (mysql_table_exists($mysqlMainDb, 'cours_faculte')) {
			db_query("DROP TABLE cours_faculte");	
		}		
        }
		
        if ($oldversion < '2.5') {
                db_query("INSERT IGNORE INTO `config` (`key`, `value`) VALUES
                        ('disable_eclass_stud_reg', '0'),
                        ('disable_eclass_prof_reg', '0'),
                        ('email_verification_required', '1'),
                        ('dont_mail_unverified_mails', '1'),
                        ('close_user_registration', '0'),
                        ('max_glossary_terms', '250'),
                        ('code_key', '" . generate_secret_key(32) . "')");

                // old users have their email verified
                if (mysql_field_exists($mysqlMainDb, 'user', 'verified_mail')) {
                        db_query('ALTER TABLE `user` MODIFY `verified_mail` TINYINT(1) NOT NULL DEFAULT '.EMAIL_UNVERIFIED);
                        db_query('UPDATE `user` SET `verified_mail`= ' . EMAIL_VERIFIED);				
                }
                mysql_field_exists($mysqlMainDb, 'user_request', 'verified_mail') or
                        db_query("ALTER TABLE `user_request` ADD `verified_mail` TINYINT(1) NOT NULL DEFAULT ".EMAIL_UNVERIFIED." AFTER `email`");		

                db_query("UPDATE `user` SET `email`=LOWER(TRIM(`email`))");
                db_query("UPDATE `user` SET `username`=TRIM(`username`)");
        }

        if ($oldversion < '3.0') {
            db_query('CREATE TABLE IF NOT EXISTS video (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `path` VARCHAR(255),
                            `url` VARCHAR(200),
                            `title` VARCHAR(200),
                            `description` TEXT,
                            `creator` VARCHAR(200),
                            `publisher` VARCHAR(200),
                            `date` DATETIME,
                            FULLTEXT KEY `video`
                               (`url`, `title`, `description`))');
            db_query('CREATE TABLE IF NOT EXISTS videolinks (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `url` VARCHAR(200),
                            `title` VARCHAR(200),
                            `description` TEXT,
                            `creator` VARCHAR(200),
                            `publisher` VARCHAR(200),
                            `date` DATETIME,
                            FULLTEXT KEY `video`
                               (`url`, `title`, `description`))');
            
            db_query("CREATE TABLE IF NOT EXISTS dropbox_file (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `uploaderId` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
                            `filename` VARCHAR(250) NOT NULL DEFAULT '',
                            `filesize` INT(11) UNSIGNED NOT NULL DEFAULT '0',
                            `title` VARCHAR(250) DEFAULT '',
                            `description` VARCHAR(250) DEFAULT '',
                            `author` VARCHAR(250) DEFAULT '',
                            `uploadDate` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `lastUploadDate` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00')");
            db_query("CREATE TABLE IF NOT EXISTS dropbox_person (
                            `fileId` INT(11) UNSIGNED NOT NULL DEFAULT '0',
                            `personId` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
                            PRIMARY KEY (fileId, personId))");
            db_query("CREATE TABLE IF NOT EXISTS dropbox_post (
                            `fileId` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                            `recipientId` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            PRIMARY KEY (fileId, recipientId))");
            
            db_query("CREATE TABLE IF NOT EXISTS `lp_module` (
                            `module_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `name` VARCHAR(255) NOT NULL DEFAULT '',
                            `comment` TEXT NOT NULL,
                            `accessibility` enum('PRIVATE','PUBLIC') NOT NULL DEFAULT 'PRIVATE',
                            `startAsset_id` INT(11) NOT NULL DEFAULT 0,
                            `contentType` enum('CLARODOC','DOCUMENT','EXERCISE','HANDMADE','SCORM','SCORM_ASSET','LABEL','COURSE_DESCRIPTION','LINK','MEDIA','MEDIALINK') NOT NULL,
                            `launch_data` TEXT NOT NULL)");
                            //COMMENT='List of available modules used in learning paths';
            db_query("CREATE TABLE IF NOT EXISTS `lp_learnPath` (
                            `learnPath_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `name` VARCHAR(255) NOT NULL DEFAULT '',
                            `comment` TEXT NOT NULL,
                            `lock` enum('OPEN','CLOSE') NOT NULL DEFAULT 'OPEN',
                            `visibility` enum('HIDE','SHOW') NOT NULL DEFAULT 'SHOW',
                            `rank` INT(11) NOT NULL DEFAULT 0)");
                            //COMMENT='List of learning Paths';
            db_query("CREATE TABLE IF NOT EXISTS `lp_rel_learnPath_module` (
                            `learnPath_module_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `learnPath_id` INT(11) NOT NULL DEFAULT 0,
                            `module_id` INT(11) NOT NULL DEFAULT 0,
                            `lock` enum('OPEN','CLOSE') NOT NULL DEFAULT 'OPEN',
                            `visibility` enum('HIDE','SHOW') NOT NULL DEFAULT 'SHOW',
                            `specificComment` TEXT NOT NULL,
                            `rank` INT(11) NOT NULL DEFAULT '0',
                            `parent` INT(11) NOT NULL DEFAULT '0',
                            `raw_to_pass` TINYINT(4) NOT NULL DEFAULT '50')");
                            //COMMENT='This table links module to the learning path using them';
            db_query("CREATE TABLE IF NOT EXISTS `lp_asset` (
                            `asset_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `module_id` INT(11) NOT NULL DEFAULT '0',
                            `path` VARCHAR(255) NOT NULL DEFAULT '',
                            `comment` VARCHAR(255) default NULL)");
                            //COMMENT='List of resources of module of learning paths';
            db_query("CREATE TABLE IF NOT EXISTS `lp_user_module_progress` (
                            `user_module_progress_id` INT(22) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
                            `learnPath_module_id` INT(11) NOT NULL DEFAULT '0',
                            `learnPath_id` INT(11) NOT NULL DEFAULT '0',
                            `lesson_location` VARCHAR(255) NOT NULL DEFAULT '',
                            `lesson_status` enum('NOT ATTEMPTED','PASSED','FAILED','COMPLETED','BROWSED','INCOMPLETE','UNKNOWN') NOT NULL default 'NOT ATTEMPTED',
                            `entry` enum('AB-INITIO','RESUME','') NOT NULL DEFAULT 'AB-INITIO',
                            `raw` TINYINT(4) NOT NULL DEFAULT '-1',
                            `scoreMin` TINYINT(4) NOT NULL DEFAULT '-1',
                            `scoreMax` TINYINT(4) NOT NULL DEFAULT '-1',
                            `total_time` VARCHAR(13) NOT NULL DEFAULT '0000:00:00.00',
                            `session_time` VARCHAR(13) NOT NULL DEFAULT '0000:00:00.00',
                            `suspend_data` TEXT NOT NULL,
                            `credit` enum('CREDIT','NO-CREDIT') NOT NULL DEFAULT 'NO-CREDIT')");
                            //COMMENT='Record the last known status of the user in the course';
            mysql_index_exists('lp_user_module_progress', 'optimize') or
                        db_query('CREATE INDEX `optimize` ON lp_user_module_progress (user_id, learnPath_module_id)');
            
            db_query("CREATE TABLE IF NOT EXISTS `wiki_properties` (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `title` VARCHAR(255) NOT NULL DEFAULT '',
                            `description` TEXT NULL,
                            `group_id` INT(11) NOT NULL DEFAULT 0 )");
            db_query("CREATE TABLE IF NOT EXISTS `wiki_acls` (
                            `wiki_id` INT(11) UNSIGNED NOT NULL,
                            `flag` VARCHAR(255) NOT NULL,
                            `value` ENUM('false','true') NOT NULL DEFAULT 'false' )");
            db_query("CREATE TABLE IF NOT EXISTS `wiki_pages` (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `wiki_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                            `owner_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `title` VARCHAR(255) NOT NULL DEFAULT '',
                            `ctime` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `last_version` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                            `last_mtime` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' )");
            db_query("CREATE TABLE IF NOT EXISTS `wiki_pages_content` (
                            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `pid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                            `editor_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `mtime` DATETIME NOT NULL default '0000-00-00 00:00:00',
                            `content` TEXT NOT NULL )");
            
            db_query("CREATE TABLE IF NOT EXISTS `poll` (
                            `pid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `creator_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `name` VARCHAR(255) NOT NULL DEFAULT '',
                            `creation_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `start_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `end_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `active` INT(11) NOT NULL DEFAULT 0 )");
            db_query("CREATE TABLE IF NOT EXISTS `poll_answer_record` (
                            `arid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `pid` INT(11) NOT NULL DEFAULT 0,
                            `qid` INT(11) NOT NULL DEFAULT 0,
                            `aid` INT(11) NOT NULL DEFAULT 0,
                            `answer_text` TEXT NOT NULL,
                            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `submit_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' )");
            db_query("CREATE TABLE IF NOT EXISTS `poll_question` (
                            `pqid` BIGINT(12) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `pid` INT(11) NOT NULL DEFAULT 0,
                            `question_text` VARCHAR(250) NOT NULL DEFAULT '',
                            `qtype` ENUM('multiple', 'fill') NOT NULL )");
            db_query("CREATE TABLE IF NOT EXISTS `poll_question_answer` (
                            `pqaid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `pqid` INT(11) NOT NULL DEFAULT 0,
                            `answer_text` TEXT NOT NULL )");
            
            db_query("CREATE TABLE IF NOT EXISTS `assignments` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `title` VARCHAR(200) NOT NULL DEFAULT '',
                            `description` TEXT NOT NULL,
                            `comments` TEXT NOT NULL,
                            `deadline` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `submission_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `active` CHAR(1) NOT NULL DEFAULT '1',
                            `secret_directory` VARCHAR(30) NOT NULL,
                            `group_submissions` CHAR(1) DEFAULT '0' NOT NULL )");
            db_query("CREATE TABLE IF NOT EXISTS `assignment_submit` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `uid` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
                            `assignment_id` INT(11) NOT NULL DEFAULT '0',
                            `submission_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                            `submission_ip` VARCHAR(16) NOT NULL DEFAULT '',
                            `file_path` VARCHAR(200) NOT NULL DEFAULT '',
                            `file_name` VARCHAR(200) NOT NULL DEFAULT '',
                            `comments` TEXT NOT NULL,
                            `grade` VARCHAR(50) NOT NULL DEFAULT '',
                            `grade_comments` TEXT NOT NULL,
                            `grade_submission_date` DATE NOT NULL DEFAULT '0000-00-00',
                            `grade_submission_ip` VARCHAR(16) NOT NULL DEFAULT '',
                            `group_id` INT( 11 ) DEFAULT NULL )");
            
            db_query("DROP TABLE IF EXISTS agenda");
            db_query("CREATE TABLE IF NOT EXISTS `agenda` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `title` VARCHAR(200) NOT NULL,
                            `content` TEXT,
                            `day` DATE NOT NULL DEFAULT '0000-00-00',
                            `hour` TIME NOT NULL DEFAULT '00:00:00',
                            `lasting` VARCHAR(20),
                            `visibility` CHAR(1) NOT NULL DEFAULT 'v',
                            FULLTEXT KEY `agenda` (`title` ,`content`))");
            
            db_query("CREATE TABLE IF NOT EXISTS `exercise` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `title` VARCHAR(250) DEFAULT NULL,
                            `description` TEXT,
                            `type` TINYINT(4) UNSIGNED NOT NULL DEFAULT '1',
                            `start_date` DATETIME DEFAULT NULL,
                            `end_date` DATETIME DEFAULT NULL,
                            `time_constraint` INT(11) DEFAULT '0',
                            `attempts_allowed` INT(11) DEFAULT '0',
                            `random` SMALLINT(6) NOT NULL DEFAULT '0',
                            `active` TINYINT(4) DEFAULT NULL,
                            `results` TINYINT(1) NOT NULL DEFAULT '1',
                            `score` TINYINT(1) NOT NULL DEFAULT '1',
                            FULLTEXT KEY `exercise` (`title`, `description`))");
            db_query("CREATE TABLE IF NOT EXISTS `exercise_user_record` (
                            `eurid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `eid` INT(11) NOT NULL DEFAULT '0',
                            `uid` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
                            `record_start_date` DATETIME NOT NULL DEFAULT '0000-00-00',
                            `record_end_date` DATETIME NOT NULL DEFAULT '0000-00-00',
                            `total_score` INT(11) NOT NULL DEFAULT '0',
                            `total_weighting` INT(11) DEFAULT '0',
                            `attempt` INT(11) NOT NULL DEFAULT '0' )");
            db_query("CREATE TABLE IF NOT EXISTS `question` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `question` TEXT,
                            `description` TEXT,
                            `weight` FLOAT(11,2) DEFAULT NULL,
                            `q_position` INT(11) DEFAULT 1,
                            `type` INT(11) DEFAULT 1 )");
            db_query("CREATE TABLE IF NOT EXISTS `answer` (
                            `id` INT(11) NOT NULL DEFAULT '0',
                            `question_id` INT(11) NOT NULL DEFAULT '0',
                            `answer` TEXT,
                            `correct` INT(11) DEFAULT NULL,
                            `comment` TEXT,
                            `weight` FLOAT(5,2),
                            `r_position` INT(11) DEFAULT NULL,
                            PRIMARY KEY (id, question_id) )");
            db_query("CREATE TABLE IF NOT EXISTS `exercise_question` (
                            `question_id` INT(11) NOT NULL DEFAULT '0',
                            `exercise_id` INT(11) NOT NULL DEFAULT '0',
                            PRIMARY KEY (question_id, exercise_id) )");
        }

        mysql_field_exists($mysqlMainDb, 'cours', 'expand_glossary') or
                db_query("ALTER TABLE `cours` ADD `expand_glossary` BOOL NOT NULL DEFAULT 0");
        mysql_field_exists($mysqlMainDb, 'cours', 'glossary_index') or
                db_query("ALTER TABLE `cours` ADD `glossary_index` BOOL NOT NULL DEFAULT 1");
        mysql_field_exists($mysqlMainDb, 'ebook', 'visible') or
                db_query("ALTER TABLE `ebook` ADD `visible` BOOL NOT NULL DEFAULT 1");
        mysql_field_exists($mysqlMainDb, 'admin', 'privilege') or
            db_query("ALTER TABLE `admin` ADD `privilege` INT NOT NULL DEFAULT '0'");
        mysql_field_exists($mysqlMainDb, 'cours_user', 'editor') or
            db_query("ALTER TABLE `cours_user` ADD `editor` INT NOT NULL DEFAULT '0' AFTER `tutor`"); 
        if (!mysql_field_exists($mysqlMainDb, 'glossary', 'category_id')) {
                db_query("ALTER TABLE glossary ADD category_id INT(11) DEFAULT NULL,
                                               ADD notes TEXT NOT NULL");
                db_query("CREATE TABLE IF NOT EXISTS `glossary_category` (
                                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `course_id` INT(11) NOT NULL,
                                `name` VARCHAR(255) NOT NULL,
                                `description` TEXT NOT NULL,
                                `order` INT(11) NOT NULL DEFAULT 0)");
        }
	
	mysql_index_exists('document', 'doc_path_index') or
                db_query('CREATE INDEX `doc_path_index` ON document (course_id,subsystem,path)');			
	mysql_index_exists('course_units', 'course_units_index') or
                db_query('CREATE INDEX `course_units_index` ON course_units (course_id,`order`)');
	mysql_index_exists('unit_resources', 'unit_res_index') or
		db_query('CREATE INDEX `unit_res_index` ON unit_resources (unit_id,visibility,res_id)');			

        // **********************************************
        // upgrade courses databases
        // **********************************************
        $res = db_query("SELECT code, languageCourse, cours_id
                         FROM cours ORDER BY code");
        $total = mysql_num_rows($res);
        $i = 1;
        while ($code = mysql_fetch_row($res)) {
                // get course language
                $lang = $code[1];
                if ($oldversion < '2.1.3') {
                        db_query('SET NAMES greek');
        		upgrade_course_old($code[0], $lang, "($i / $total)");
                        db_query('SET NAMES utf8');
               	        upgrade_course_2_1_3($code[0], "($i / $total)");
                }
                if ($oldversion <= '2.2') {
               	        upgrade_course_2_2($code[0], $lang, "($i / $total)");
		}
                if ($oldversion < '2.3') {
			upgrade_course_2_3($code[0], "($i / $total)");
		}
                if ($oldversion < '2.4') {
                        convert_description_to_units($code[0], $code[2]);
                        upgrade_course_index_php($code[0]);
			upgrade_course_2_4($code[0], $lang, "($i / $total)");
                }
                if ($oldversion < '2.5') {                        
			upgrade_course_2_5($code[0], $lang, "($i / $total)");
                }
                if ($oldversion < '3.0') {
                    upgrade_course_3_0($code[0], $lang, "($i / $total)");
                }
                echo "</p>\n";
                $i++;
        }
	echo "<hr>";
	
        if ($oldversion < '2.1.3') {
	        echo "<p>$langChangeDBCharset <b>$mysqlMainDb</b> $langToUTF</p><br>";
                convert_db_utf8($mysqlMainDb);
        }

        db_query("UPDATE config SET `value` = '" . ECLASS_VERSION ."' WHERE `key`='version'", $mysqlMainDb);

        echo "<hr><p class='success'>$langUpgradeSuccess
                <br><b>$langUpgReady</b></p>
                <p class='info'>$langUpgSucNotice</p>
		<p class='right'><a href='$urlServer?logout=yes'>$langBack</a></p>";

        echo '</div></body></html>';
        exit;
} // end of if not submit

draw($tool_content, 0);
