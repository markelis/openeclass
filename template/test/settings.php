<?php

load_js('jquery');
load_js('jquery-ui');


$icon_map = array(
    'arrow' => 'fa-caret-right',
    'announcements' => 'fa-bullhorn',
    'calendar' => 'fa-calendar-o',
    'dropbox' => 'fa-envelope-o',
    'docs' => 'fa-folder-open-o',
    'links' => 'fa-link',
    'description' => 'fa-info-circle',
    'forum' => 'fa-comments',
    'assignments' => 'fa-flask',
    'exercise' => 'fa-pencil-square-o',
    'questionnaire' => 'fa-question-circle',
    'ebook' => 'fa-book',
    'videos' => 'fa-film',
    'groups' => 'fa-users',
    'lp' => 'fa-ellipsis-h',
    'conference' => 'fa-exchange',
    'glossary' => 'fa-list',
    'wiki' => 'fa-globe',
    'course_info' => 'fa-cogs',
    'users' => 'fa-cogs',
    'tooladmin' => 'fa-cogs',
    'usage' => 'fa-cogs',
);

function template_callback($template, $menuTypeID)
{
    global $uid;

    if ($uid) {
	$template->set_var('BODY_SET_CLASS', ' class="sidebar-opened"');
	$template->set_block('mainBlock', 'LoggedOutBlock', 'delete');
    } else {
	$template->set_block('mainBlock', 'LoggedInBlock', 'delete');
    }
}
