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

/*==============================================================================
    @Description: Helper Functions for validations specific to the Hierarchy Tree

 	Used for validating the department manager admin role

==============================================================================*/
 

function validateNode($id)
{
	global $tool_content, $head_content, $is_admin, $tree, $user, $uid,
	       $langBack, $langNotAllowed;

	$notallowed = "<p class='caution'>$langNotAllowed</p><p align='right'><a href='$_SERVER[PHP_SELF]'>".$langBack."</a></p>";

	if ($id <= 0)
		exitWithError($notallowed);

	$result = db_query("SELECT * FROM ". $tree->getDbtable() ." WHERE id = ". intval($id) );

	if (mysql_num_rows($result) < 1)
		exitWithError($notallowed);

	if (!$is_admin)
	{
		$subtrees = $tree->buildSubtrees($user->getDepartmentIds($uid));

		if (!in_array($id, $subtrees))
			exitWithError($notallowed);
	}

}



function validateParentLft($nodelft)
{
	global $tool_content, $head_content, $is_admin, $tree, $user, $uid,
	       $langBack, $langNotAllowed;

	$notallowed = "<p class='caution'>$langNotAllowed</p><p align='right'><a href='$_SERVER[PHP_SELF]'>".$langBack."</a></p>";

	if ($nodelft <= 0)
		exitWithError($notallowed);

	$result = db_query("SELECT * FROM ". $tree->getDbtable() ." WHERE lft = ". intval($nodelft) );

	if (mysql_num_rows($result) < 1)
		exitWithError($notallowed);

	if (!$is_admin)
	{
		$row = mysql_fetch_assoc($result);
		$parentid = $row['id'];
		$subtrees = $tree->buildSubtrees($user->getDepartmentIds($uid));

		if (!in_array($parentid, $subtrees))
			exitWithError($notallowed);
	}
}



function exitWithError($message)
{
	global $tool_content, $head_content;

	$tool_content .= $message;
	draw($tool_content, 3, null, $head_content);
	exit();
}



