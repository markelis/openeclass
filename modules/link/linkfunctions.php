<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
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

require_once 'modules/search/linkindexer.class.php';

function makedefaultviewcode($locatie) {
    global $aantalcategories;

    $view = str_repeat('0', $aantalcategories);
    $view[$locatie] = '1';
    return $view;
}

/**
 * Function getNumberOfLinks
 * @param unknown_type $catid
 * @return int number of links
 */
function getNumberOfLinks($catid) {
    global $course_id;

    list($count) = mysql_fetch_row(db_query("SELECT COUNT(*) FROM `link`
                                                        WHERE course_id = $course_id AND category = $catid
                                                        ORDER BY `order`"));
    return $count;
}

function showlinksofcategory($catid) {
    global $is_editor, $course_id, $urlview, $tool_content,
    $urlServer, $course_code, $course_code, $themeimg,
    $langLinkDelconfirm, $langDelete, $langUp, $langDown,
    $langModify, $langLinks, $langCategoryDelconfirm,
    $is_in_tinymce;

    $result = db_query("SELECT * FROM `link`
                                   WHERE course_id = $course_id AND category = $catid
                                   ORDER BY `order`");
    $numberoflinks = mysql_num_rows($result);

    $i = 1;
    while ($myrow = mysql_fetch_array($result)) {
        if ($i % 2 == 0) {
            $tool_content .= "<tr class='odd'>";
        } else {
            $tool_content .= "<tr class='even'>";
        }
        $title = empty($myrow['title']) ? $myrow['url'] : $myrow['title'];
        $tool_content .= "<td>&nbsp;</td><td width='1' valign='top'><img src='$themeimg/arrow.png' alt='' /></td>";
        if ($is_editor) {
            $num_merge_cols = 1;
        } else {
            $num_merge_cols = 1;
        }
        $aclass = ($is_in_tinymce) ? " class='fileURL' " : '';
        $tool_content .= "
                  <td valign='top' colspan='$num_merge_cols'><a href='" . $urlServer . "modules/link/go.php?c=$course_code&amp;id=$myrow[id]&amp;url=" .
                urlencode($myrow['url']) . "' $aclass target='_blank'>" . q($title) . "</a>";
        if (!empty($myrow['description'])) {
            $tool_content .= "<br />" . standard_text_escape($myrow['description']);
        }
        $tool_content .= "</td>";

        if ($is_editor && !$is_in_tinymce) {
            $tool_content .= "<td width='45' valign='top' align='right'>";
            $editlink = "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=editlink&amp;id=$myrow[0]&amp;urlview=$urlview";
            if (isset($category)) {
                $editlink .= "&amp;category=$category";
            }

            $tool_content .= icon('edit', $langModify, $editlink) .
                    "&nbsp;&nbsp;" .
                    icon('delete', $langDelete, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;action=deletelink&amp;id=$myrow[0]&amp;urlview=$urlview", "onclick=\"javascript:if(!confirm('" . $langLinkDelconfirm . "')) return false;\"") .
                    "</td><td width='35' valign='top' align='right'>";
            // Display move up command only if it is not the top link
            if ($i != 1) {
                $tool_content .= icon('up', $langUp, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=$urlview&amp;up=$myrow[id]");
            }
            // Display move down command only if it is not the bottom link
            if ($i < $numberoflinks) {
                $tool_content .= icon('down', $langDown, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=$urlview&amp;down=$myrow[id]");
            }
            $tool_content .= "</td>";
        }
        $tool_content .= "</tr>";
        $i++;
    }
}

function showcategoryadmintools($categoryid) {
    global $urlview, $aantalcategories, $catcounter, $langDelete,
    $langModify, $langUp, $langDown, $langCatDel, $tool_content,
    $course_code, $themeimg;

    $basecaturl = "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$categoryid&amp;urlview=$urlview&amp;";
    $tool_content .= "<th width='45' valign='top' class='right'>" .
            icon('edit', $langModify, $basecaturl . 'action=editcategory') .
            '&nbsp;&nbsp;' .
            icon('delete', $langDelete, $basecaturl . 'action=deletecategory', "onclick=\"javascript:if(!confirm('$langCatDel')) return false;\"") .
            "</th>
	                   <th width='35' valign='top' class='right'>";
    // Display move up command only if it is not the top link
    if ($catcounter != 1) {
        $tool_content .= icon('up', $langUp, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=$urlview&amp;cup=$categoryid");
    }
    // Display move down command only if it is not the bottom link
    if ($catcounter < $aantalcategories) {
        $tool_content .= icon('down', $langDown, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=$urlview&amp;cdown=$categoryid");
    }
    $tool_content .= "</th>";
    $catcounter++;
}

// Enter the modified info submitted from the link form into the database
function submit_link() {
    global $course_id, $catlinkstatus, $langLinkMod, $langLinkAdded,
    $urllink, $title, $description, $selectcategory;

    register_posted_variables(array('urllink' => true,
        'title' => true,
        'description' => true,
        'selectcategory' => true), 'all', 'trim');
    $urllink = canonicalize_url($urllink);
    $set_sql = "SET url = " . autoquote($urllink) . ",
                        title = " . autoquote($title) . ",
                        description = " . autoquote(purify($description)) . ",
                        category = " . intval($selectcategory);

    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);
        db_query("UPDATE `link` $set_sql WHERE course_id = $course_id AND id = $id");

        $catlinkstatus = $langLinkMod;
        $log_type = LOG_MODIFY;
    } else {
        $q = db_query("SELECT MAX(`order`) FROM `link`
                                      WHERE course_id = $course_id AND category = $selectcategory");
        list($order) = mysql_fetch_row($q);
        $order++;
        db_query("INSERT INTO `link` $set_sql, course_id = $course_id, `order` = $order");
        $id = mysql_insert_id();
        $catlinkstatus = $langLinkAdded;
        $log_type = LOG_INSERT;
    }
    $lidx = new LinkIndexer();
    $lidx->store($id);
    // find category name
    list($category) = mysql_fetch_array(db_query("SELECT link_category.name FROM link, link_category
                                                        WHERE link.category = link_category.id
                                                        AND link.course_id = $course_id
                                                        AND link.id = $id"));
    $txt_description = ellipsize_html(canonicalize_whitespace(strip_tags($description)), 50, '+');
    Log::record($course_id, MODULE_ID_LINKS, $log_type, @array('id' => $id,
        'url' => $urllink,
        'title' => $title,
        'description' => $txt_description,
        'category' => $category));
}

function category_form_defaults($id) {
    global $course_id, $form_name, $form_description;

    $result = db_query("SELECT * FROM link_category WHERE course_id = $course_id AND id = $id");
    if ($myrow = mysql_fetch_array($result)) {
        $form_name = ' value="' . q($myrow['name']) . '"';
        $form_description = q($myrow['description']);
    } else {
        $form_name = $form_description = '';
    }
}

function link_form_defaults($id) {
    global $course_id, $form_url, $form_title, $form_description, $category;

    $result = db_query("SELECT * FROM `link` WHERE course_id = $course_id AND id = $id");
    if ($myrow = mysql_fetch_array($result)) {
        $form_url = ' value="' . q($myrow['url']) . '"';
        $form_title = ' value="' . q($myrow['title']) . '"';
        $form_description = purify(trim($myrow['description']));
        $category = $myrow['category'];
    } else {
        $form_url = $form_title = $form_description = '';
    }
}

// Enter the modified info submitted from the category form into the database
function submit_category() {
    global $course_id, $langCategoryAdded, $langCategoryModded,
    $categoryname, $description, $catlinkstatus;

    register_posted_variables(array('categoryname' => true,
        'description' => true), 'all', 'trim');
    $set_sql = "SET name = " . autoquote($categoryname) . ",
                        description = " . autoquote(purify($description));

    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);
        db_query("UPDATE `link_category` $set_sql WHERE course_id = $course_id AND id = $id");
        $catlinkstatus = $langCategoryModded;
        $log_type = LOG_MODIFY;
    } else {
        $q = db_query("SELECT MAX(`order`) FROM `link_category`
                                      WHERE course_id = $course_id");
        list($order) = mysql_fetch_row($q);
        $order++;
        db_query("INSERT INTO `link_category` $set_sql, course_id = $course_id, `order` = $order");
        $id = mysql_insert_id();
        $catlinkstatus = $langCategoryAdded;
        $log_type = LOG_INSERT;
    }
    $txt_description = ellipsize(canonicalize_whitespace(strip_tags($description)), 50, '+');
    Log::record($course_id, MODULE_ID_LINKS, $log_type, array('id' => $id,
        'category' => $categoryname,
        'description' => $txt_description));
}

function delete_link($id) {
    global $course_id, $langLinkDeleted, $catlinkstatus;

    list($url, $title) = mysql_fetch_row(db_query("SELECT url, title FROM link WHERE course_id = $course_id AND id = $id"));
    db_query("DELETE FROM `link` WHERE course_id = $course_id AND id = $id");
    $lidx = new LinkIndexer();
    $lidx->remove($id);
    $catlinkstatus = $langLinkDeleted;
    Log::record($course_id, MODULE_ID_LINKS, LOG_DELETE, array('id' => $id,
        'url' => $url,
        'title' => $title));
}

function delete_category($id) {
    global $course_id, $langCategoryDeleted, $catlinkstatus;

    db_query("DELETE FROM `link` WHERE course_id = $course_id AND category = $id");
    list($category) = mysql_fetch_array(db_query("SELECT name FROM link_category WHERE course_id = $course_id AND id = $id"));
    db_query("DELETE FROM `link_category` WHERE course_id = $course_id AND id = $id");
    $catlinkstatus = $langCategoryDeleted;
    Log::record($course_id, MODULE_ID_LINKS, LOG_DELETE, array('cat_id' => $id,
        'category' => $category));
}
