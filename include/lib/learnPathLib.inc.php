<?php

/*
Header
*/

/**
 * CLAROLINE 
 *
 * This functions library is used by most of the pages of the learning path tool
 *
 * @version version 1.7 $Revision$
 *
 * @copyright (c) 2001, 2005 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE 
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 *
 * @package CLLNP
 *
 */


/**
* content type
*/
define ( 'CTCLARODOC_', 'CLARODOC' );
/**
* content type
*/
define ( 'CTDOCUMENT_', 'DOCUMENT' );
/**
* content type
*/
define ( 'CTEXERCISE_', 'EXERCISE' );
/**
* content type
*/
define ( 'CTSCORM_', 'SCORM' );
/**
* content type
*/
define ( 'CTLABEL_', 'LABEL' );
/**
* content type
*/
define ( 'CTCOURSE_DESCRIPTION_', 'COURSE_DESCRIPTION' );


/**
* mode used by {@link commentBox($type, $mode)} and {@link nameBox($type, $mode)}
*/
define ( 'DISPLAY_', 1 );
/**
* mode used by {@link commentBox($type, $mode)} and {@link nameBox($type, $mode)}
*/
define ( 'UPDATE_', 2 );
define ( 'UPDATENOTSHOWN_', 4 );

/**
* mode used by {@link commentBox($type, $mode)} and {@link nameBox($type, $mode)}
*/
define ( 'DELETE_', 3 );

/**
* type used by {@link commentBox($type, $mode)} and {@link nameBox($type, $mode)}
*/
define ( 'ASSET_', 1 );
/**
* type used by {@link commentBox($type, $mode)} and {@link nameBox($type, $mode)}
*/
define ( 'MODULE_', 2 );
define ( 'LEARNINGPATH_', 3 );
define ( 'LEARNINGPATHMODULE_', 4 );

/**
 * This function is used to display comments of module or learning path with admin links if needed.
 * Admin links are 'edit' and 'delete' links.
 *
 * @param string $type MODULE_ , LEARNINGPATH_ , LEARNINGPATHMODULE_
 * @param string $mode DISPLAY_ , UPDATE_ , DELETE_
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 */
function commentBox($type, $mode)
{
    $tbl_lp_learnPath            = "lp_learnPath";
    $tbl_lp_rel_learnPath_module = "lp_rel_learnPath_module";
    $tbl_lp_module               = "lp_module";
    // globals
    global $is_adminOfCourse;
    global $langModify, $langOk, $langErrorNameAlreadyExists, $langAddComment, $langConfirmYourChoice;
    global $langDefaultLearningPathComment, $langDefaultModuleComment;
    global $langDefaultModuleAddedComment, $langDelete;
    // will be set 'true' if the comment has to be displayed
    $dsp = false;
    $output = "";

    // those vars will be used to build sql queries according to the comment type
    switch ( $type )
    {
        case MODULE_ :
            $defaultTxt = $langDefaultModuleComment;
            $col_name = 'comment';
            $tbl_name = $tbl_lp_module;
            if ( isset($_REQUEST['module_id'] ) )
            {
                $module_id = $_REQUEST['module_id'];
            }
            else
            {
                $module_id = $_SESSION['module_id'];
            }
            $where_cond = "`module_id` = " . (int) $module_id;  // use backticks ( ` ) for col names and simple quote ( ' ) for string
            break;
        case LEARNINGPATH_ :
            $defaultTxt = $langDefaultLearningPathComment;
            $col_name = 'comment';
            $tbl_name = $tbl_lp_learnPath;
            $where_cond = '`learnPath_id` = '. (int) $_SESSION['path_id'];  // use backticks ( ` ) for col names and simple quote ( ' ) for string
            break;
        case LEARNINGPATHMODULE_ :
            $defaultTxt = $langDefaultModuleAddedComment;
            $col_name = 'specificComment';
            $tbl_name = $tbl_lp_rel_learnPath_module;
            $where_cond = "`learnPath_id` = " . (int) $_SESSION['path_id'] . "
                                        AND `module_id` = " . (int) $_SESSION['module_id'];  // use backticks ( ` ) for col names and simple quote ( ' ) for string
            break;
    }

    // update mode
    // allow to chose between
    // - update and show the comment and the pencil and the delete cross (UPDATE_)
    // - update and nothing displayed after form sent (UPDATENOTSHOWN_)
    if ( ( $mode == UPDATE_ || $mode == UPDATENOTSHOWN_ )  && $is_adminOfCourse )
    {
        if ( isset($_POST['insertCommentBox']) )
        {
            $sql = "UPDATE `" . $tbl_name . "`
                           SET `" . $col_name . "` = \"". addslashes($_POST['insertCommentBox'])."\"
                         WHERE " . $where_cond;
            db_query($sql);
            
            if($mode == UPDATE_)
            	$dsp = true;
            elseif($mode == UPDATENOTSHOWN_)
            	$dsp = false;
        }
        else // display form
        {
            // get info to fill the form in
            $sql = "SELECT `".$col_name."`
                       FROM `" . $tbl_name . "`
                      WHERE " . $where_cond;
            $oldComment = db_query_get_single_value($sql);

            $output .= '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">' . "\n"
                .claro_disp_html_area('insertCommentBox', $oldComment, 15, 55).'<br />' . "\n"
                .'<input type="hidden" name="cmd" value="update' . $col_name . '" />'
                .'<input type="submit" value="' . $langOk . '" />' . "\n"
                .'<br />' . "\n"
                .'</form>' . "\n"
            ;
        }

    }

    // delete mode
    if ( $mode == DELETE_ && $is_adminOfCourse)
    {
        $sql =  "UPDATE `" . $tbl_name . "`
                 SET `" . $col_name . "` = ''
                 WHERE " . $where_cond;
        db_query($sql);
        $dsp = TRUE;
    }

    // display mode only or display was asked by delete mode or update mode
    if ( $mode == DISPLAY_ || $dsp == TRUE )
    {
        $sql = "SELECT `".$col_name."`
                FROM `" . $tbl_name . "`
                WHERE " . $where_cond;

        $result = db_query($sql);
        if($result)
        {
           list($value) = mysql_fetch_row($result);
           mysql_free_result($result);
           $currentComment = $value;
        }
        else
        {
           $currentComment = false;
        }

        // display nothing if this is default comment and not an admin
        if ( ($currentComment == $defaultTxt) && !$is_adminOfCourse ) return $output;

        if ( empty($currentComment) )
        {
            // if no comment and user is admin : display link to add a comment
            if ( $is_adminOfCourse )
            {
                $output .= '<p>' . "\n"
                .    '<a class="claroCmd" href="' . $_SERVER['PHP_SELF'] . '?cmd=update' . $col_name . '">' . "\n"
                .    $langAddComment . '</a>' . "\n"
                .    '</p>' . "\n"
                ;
            }
        }
        else
        {
            // display comment
            $output .= "<p>".$currentComment."</p>";
            // display edit and delete links if user as the right to see it
            if ( $is_adminOfCourse )
            {

                $output .= '<p>' . "\n"
                .    '<small>' . "\n"
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=update' . $col_name . '">' . "\n"
                .    '<img src="../../images/edit.gif" alt="' . $langModify . '" border="0" />'
                .    '</a>' . "\n"
                .    '<a href="' . $_SERVER['PHP_SELF'].'?cmd=del' . $col_name . '" '
                .    ' onclick="javascript:if(!confirm(\''.clean_str_for_javascript($langConfirmYourChoice).'\')) return false;">' . "\n"
                .    '<img src="../../images/delete.gif" alt="' . $langDelete . '" border="0" />' . "\n"
                .    '</a>' . "\n"
                .    '</small>' . "\n"
                .    '</p>' . "\n"
                ;
            }
        }
    }

    return $output;
}

/**
  * This function is used to display name of module or learning path with admin links if needed
  *
  * @param string $type MODULE_ , LEARNINGPATH_
  * @param string $mode display(DISPLAY_) or update(UPDATE_) mode, no delete for a name
  * @author Piraux S�bastien <pir@cerdecam.be>
  * @author Lederer Guillaume <led@cerdecam.be>
  */
function nameBox($type, $mode)
{
    $tbl_lp_learnPath            = "lp_learnPath";
    $tbl_lp_module               = "lp_module";

    // globals
    global $is_adminOfCourse;
    global $urlAppend;
    global $langModify, $langOk, $langErrorNameAlreadyExists;

    // $dsp will be set 'true' if the comment has to be displayed
    $dsp = FALSE;
    $output = "";

    // those vars will be used to build sql queries according to the name type
    switch ( $type )
    {
        case MODULE_ :
            $col_name = 'name';
            $tbl_name = $tbl_lp_module;
            $where_cond = '`module_id` = ' . (int) $_SESSION['module_id'];
            break;
        case LEARNINGPATH_ :
            $col_name = 'name';
            $tbl_name = $tbl_lp_learnPath;
            $where_cond = '`learnPath_id` = ' . (int) $_SESSION['path_id'];
            break;
    }

    // update mode
    if ( $mode == UPDATE_ && $is_adminOfCourse)
    {

        if ( isset($_POST['newName']) && !empty($_POST['newName']) )
        {

            $sql = "SELECT COUNT(`" . $col_name . "`)
                                 FROM `" . $tbl_name . "`
                                WHERE `" . $col_name . "` = '" . addslashes($_POST['newName']) . "'
                                  AND !(" . $where_cond . ")";
            $num = db_query_get_single_value($sql);

            if ($num == 0)  // name doesn't already exists
            {

                $sql = "UPDATE `" . $tbl_name . "`
                                      SET `" . $col_name . "` = '" . addslashes($_POST['newName']) ."'
                                    WHERE " . $where_cond;

                db_query($sql);
                $dsp = TRUE;
            }
            else
            {
                $output .= $langErrorNameAlreadyExists . '<br />';
                $dsp = TRUE;
            }
        }
        else // display form
        {
            $sql = "SELECT `name`
                    FROM `" . $tbl_name . "`
                    WHERE " . $where_cond;

            $oldName = db_query_get_single_value($sql);

            $output .= '<form method="POST" action="' . $_SERVER['PHP_SELF'].'">' . "\n"
            .    '<input type="text" name="newName" size="50" maxlength="255" value="'.htmlspecialchars($oldName).'" />'
            .    '<br />' . "\n"
            .    '<input type="hidden" name="cmd" value="updateName" />' ."\n"
            .    '<input type="submit" value="' . $langOk . '" />' . "\n"
            .    '<br />' . "\n"
            .    '</form>' . "\n"
            ;
        }

    }

    // display if display mode or asked by the update
    if ( $mode == DISPLAY_ || $dsp == true )
    {
        $sql = "SELECT `name`
                      FROM `" . $tbl_name . "`
                     WHERE " . $where_cond;

        $result = db_query($sql);
        if($result)
        {
           list($value) = mysql_fetch_row($result);
           mysql_free_result($result);
           $currentName = $value;
        }
        else
        {
           $currentName = false;
        }

        $output .= '<h4>' 
        .    $currentName;

        if ( $is_adminOfCourse )
            $output .= '<br /><a href="' . $_SERVER['PHP_SELF'] . '?cmd=updateName">'
            .    '<img src="../../images/edit.gif" alt="' . $langModify . '" border="0" />'
            .    '</a>' . "\n";
        $output .= '</h4>'."\n\n";
    }

    return $output;
}

/**
  * This function is used to display the correct image in the modules lists
  * It looks for the correct type in the array, and return the corresponding image name if found
  * else it returns a default image
  *
  * @param  string $contentType type of content in learning path
  * @return string name of the image with extension
  * @author Piraux S�bastien <pir@cerdecam.be>
  * @author Lederer Guillaume <led@cerdecam.be>
  */
 function selectImage($contentType)
 {

      $imgList[CTDOCUMENT_] = "document.gif";
      $imgList[CTCLARODOC_] = "clarodoc.gif";
      $imgList[CTEXERCISE_] = "quiz.gif";
      $imgList[CTSCORM_] = "scorm.gif";

      if (array_key_exists( $contentType , $imgList ))
      {
          return $imgList[$contentType];
      }

      return "default.gif";

 }
 /**
  * This function is used to display the correct alt texte for image in the modules lists.
  * Mainly used at the same time than selectImage() to add an alternate text on the image.
  *
  * @param  string $contentType type of content in learning path
  * @return string text for the alt
  * @author Piraux S�bastien <pir@cerdecam.be>
  * @author Lederer Guillaume <led@cerdecam.be>
  */
 function selectAlt($contentType)
 {
      global $langAltDocument, $langAltClarodoc, $langAltExercise, $langAltScorm;

      $altList[CTDOCUMENT_] = $langAltDocument;
      $altList[CTCLARODOC_] = $langAltClarodoc;
      $altList[CTEXERCISE_] = $langAltExercise;
      $altList[CTSCORM_] = $langAltScorm;

      if (array_key_exists( $contentType , $altList ))
      {
          return $altList[$contentType];
      }

      return "default.gif";
 }

/**
 * This function receives an array like $table['idOfThingToOrder'] = $requiredOrder and will return a sorted array
 * like $table[$i] = $idOfThingToOrder
 * the id list is sorted according to the $requiredOrder values
 *
 * @param  $formValuesTab array an array like these sent by the form on learingPathAdmin.php for an exemple
 *
 * @return array an array of the sorted list of ids
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 */
function setOrderTab ( $formValuesTab )
{
    global $langErrorInvalidParms, $langErrorValuesInDouble;
    global $dialogBox;

    $tabOrder = array(); // declaration to avoid bug in "elseif (in_array ... "
    $i = 0;
    foreach ( $formValuesTab as $key => $requiredOrder)
    {
        // error if input is not a number
        if( !is_num($requiredOrder) )
        {
            $dialogBox .= $langErrorInvalidParms;
            return 0;
        }
        elseif( in_array($requiredOrder, $tabOrder) )
        {
            $dialogBox .= $langErrorValuesInDouble;
            return 0;
        }
        // $tabInvert = required order => id module
        $tabInvert[$requiredOrder] = $key;
        // $tabOrder = required order : unsorted
        $tabOrder[$i] = $requiredOrder;
        $i++;
    }
    // $tabOrder = required order : sorted
    sort($tabOrder);
    $i = 0;
    foreach ($tabOrder as $key => $order)
    {
        // $tabSorted = new Order => id learning path
        $tabSorted[$i] = $tabInvert[$order];
        $i++;
    }
    return $tabSorted;
}


/**
 * Check if an input string is a number
 *
 * @param string $var input to check
 * @return bool true if $var is a number, false otherwise
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function is_num($var)
{
    for ( $i = 0; $i < strlen($var); $i++ )
    {
        $ascii = ord($var[$i]);

        // 48 to 57 are decimal ascii values for 0 to 9
        if ( $ascii >= 48 && $ascii <= 57)
        	continue;
        else
        	return FALSE;
    }

    return TRUE;
}


/**
 *  This function allows to display the modules content of a learning path.
 *  The function must be called from inside a learning path where the session variable path_id is known.
 */
function display_path_content()
{
    $tbl_lp_learnPath            = "lp_learnPath";
    $tbl_lp_rel_learnPath_module = "lp_rel_learnPath_module";
    $tbl_lp_user_module_progress = "lp_user_module_progress";
    $tbl_lp_module               = "lp_module";
    $tbl_lp_asset                = "lp_asset";

    global $_cid;
    global $langModule;
    global $imgRepositoryWeb;
    $style = "";
    $output = "";

    $sql = "SELECT M.`name`, M.`contentType`, 
                   LPM.`learnPath_module_id`, LPM.`parent`, 
                   A.`path`
            FROM `" . $tbl_lp_learnPath . "` AS LP,
                 `" . $tbl_lp_rel_learnPath_module . "` AS LPM, 
                 `" . $tbl_lp_module . "` AS M
            LEFT JOIN `" . $tbl_lp_asset . "` AS A
              ON M.`startAsset_id` = A.`asset_id`
            WHERE LP.`learnPath_id` = " .  (int) $_SESSION['path_id'] . "
              AND LP.`learnPath_id` = LPM.`learnPath_id`
              AND LPM.`module_id` = M.`module_id`
            ORDER BY LPM.`rank`";
    $moduleList = db_query_fetch_all($sql);

    $extendedList = array();
    foreach( $moduleList as $module)
    {
        $extendedList[] = $module;
    }
    // build the array of modules
    // build_element_list return a multi-level array, where children is an array with all nested modules
    // build_display_element_list return an 1-level array where children is the deep of the module
    $flatElementList = build_display_element_list(build_element_list($extendedList, 'parent', 'learnPath_module_id'));

    // look for maxDeep
    $maxDeep = 1; // used to compute colspan of <td> cells
    for ($i = 0 ; $i < sizeof($flatElementList) ; $i++)
    {
        if ($flatElementList[$i]['children'] > $maxDeep) $maxDeep = $flatElementList[$i]['children'] ;
    }

    $output .= "\n".'<table class="claroTable" width="100%"  border="0" cellspacing="2">'."\n\n"
    .    '<tr class="headerX" align="center" valign="top" bgcolor="#e6e6e6">'."\n"
	.    '<th colspan="' . ($maxDeep+1).'">' . $langModule . '</th>'."\n"
    .    '</tr>'."\n\n"
	.	 '<tbody>'."\n"
    ;

    foreach ($flatElementList as $module)
    {
        $spacingString = '';
        for($i = 0; $i < $module['children']; $i++)
        	$spacingString .= '<td width="5">&nbsp;</td>'."\n";
        $colspan = $maxDeep - $module['children']+1;

        $output .= '<tr align="center" '.$style.'>' . "\n"
        .    $spacingString 
        .    '<td colspan="' . $colspan . '" align="left">'
        ;

        if ($module['contentType'] == CTLABEL_) // chapter head
        {
            $output .= '<b>' . $module['name'] . '</b>';
        }
        else // module
        {
            if($module['contentType'] == CTEXERCISE_ )
            	$moduleImg = 'quiz.gif';
            else
            	$moduleImg = choose_image(basename($module['path']));
            	
            $contentType_alt = selectAlt($module['contentType']);

            $output .= '<img src="' . $imgRepositoryWeb . $moduleImg . '" alt="' .$contentType_alt.'" border="0" />'
            .    $module['name']
            ;
        }
        $output .= '</td>'."\n"
		.	 '</tr>'."\n\n";
    }
    $output .= '</tbody>'."\n\n"
	.	 '</table>'."\n\n";
	
	return $output;
}

/**
 * Compute the progression into the $lpid learning path in pourcent
 * 
 * @param $lpid id of the learning path
 * @param $lpUid user id
 *
 * @return integer percentage of progression os user $mpUid in the learning path $lpid
 */
function get_learnPath_progress($lpid, $lpUid)
{
    
    $tbl_lp_learnPath            = "lp_learnPath";
    $tbl_lp_rel_learnPath_module = "lp_rel_learnPath_module";
    $tbl_lp_user_module_progress = "lp_user_module_progress";
    $tbl_lp_module               = "lp_module";

    // find progression for this user in each module of the path

    $sql = "SELECT UMP.`raw` AS R, UMP.`scoreMax` AS SMax, M.`contentType` AS CTYPE, UMP.`lesson_status` AS STATUS
             FROM `" . $tbl_lp_learnPath . "` AS LP,
                  `" . $tbl_lp_rel_learnPath_module . "` AS LPM,
                  `" . $tbl_lp_user_module_progress . "` AS UMP,
                  `" . $tbl_lp_module . "` AS M
            WHERE LP.`learnPath_id` = LPM.`learnPath_id`
              AND LPM.`learnPath_module_id` = UMP.`learnPath_module_id`
              AND UMP.`user_id` = " . (int) $lpUid . "
              AND LP.`learnPath_id` = " . (int) $lpid . "
              AND LPM.`visibility` = 'SHOW'
              AND M.`module_id` = LPM.`module_id`
              AND M.`contentType` != '" . CTLABEL_ . "'";

    $result = db_query($sql);
	$modules = array();

    while( $row = mysql_fetch_array($result) )
    {
        $modules [] = $row;
    }
    mysql_free_result($result);
    
    $progress = 0;
    if( !is_array($modules) || empty($modules) )
    {
        $progression = 0;
    }
    else
    {
        // progression is calculated in pourcents
        foreach( $modules as $module )
        {
            if( $module['SMax'] <= 0 )
            {
                $modProgress = 0 ;
            }
            else
            {
                $modProgress = @round($module['R']/$module['SMax']*100);
            }

            // in case of scorm module, progression depends on the lesson status value
            if (($module['CTYPE']=="SCORM") && ($module['SMax'] <= 0) && (( $module['STATUS'] == 'COMPLETED') || ($module['STATUS'] == 'PASSED')))
            {
                $modProgress = 100;
            }
            
            if ($modProgress >= 0)
            {
                $progress += $modProgress;
            }
        }
        // find number of visible modules in this path
        $sqlnum = "SELECT COUNT(M.`module_id`)
                    FROM `" . $tbl_lp_rel_learnPath_module . "` AS LPM,
                          `". $tbl_lp_module . "` AS M
                    WHERE LPM.`learnPath_id` = " . (int) $lpid . "
                    AND LPM.`visibility` = 'SHOW'
                    AND M.`contentType` != '" . CTLABEL_ . "'
                    AND M.`module_id` = LPM.`module_id`
                    ";
        $result = db_query($sqlnum);
        if($result) {
            list($value) = mysql_fetch_row($result);
            mysql_free_result($result);
            $nbrOfVisibleModules = $value;
        }
        else {
            $nbrOfVisibleModules = false;
        }
        

		if( is_numeric($nbrOfVisibleModules) )
          	$progression = @round($progress/$nbrOfVisibleModules);
		else
			$progression = 0;

    }
    return $progression;
}

/**
 * This function displays the list of available exercises in this course
 * With the form to add a selected exercise in the learning path
 *
 * @param string $dialogBox Error or confirmation text
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 * @author Lederer Guillaume <led@cerdecam.be>
 */
function display_my_exercises($dialogBox)
{
    //$tbl_cdb_names = claro_sql_get_course_tbl();
    //$tbl_quiz_test = $tbl_cdb_names['quiz_test'];
    $tbl_quiz_test = "quiz_test";

    global $langAddModule;
    global $langAddModulesButton;
    global $langExercise;
    global $langNoEx;
    global $langAddOneModuleButton;
    global $imgRepositoryWeb;

    echo '<!-- display_my_exercises output -->' . "\n";
    /*--------------------------------------
    DIALOG BOX SECTION
    --------------------------------------*/
    $colspan = 4;
    if( !empty($dialogBox) )
    {
        echo claro_disp_message_box($dialogBox).'<br />'."\n";
    }
    echo '<table class="claroTable" width="100%" border="0" cellspacing="">'."\n\n"
    .    '<tr class="headerX" align="center" valign="top">'."\n"
    .    '<th width="10%">'
    .    $langAddModule
    .    '</th>'."\n"
    .    '<th>'
    .    $langExercise
    .    '</th>'."\n"
    .    '</tr>'."\n\n"
    ;

    // Display available modules
    echo '<form method="POST" name="addmodule" action="' . $_SERVER['PHP_SELF'] . '?cmdglobal=add">'."\n";
    $atleastOne = FALSE;
    $sql = "SELECT `id`, `titre` AS `title`, `description`
            FROM `" . $tbl_quiz_test . "`
            ORDER BY  `titre`, `id`";
    $exercises = db_query_fetch_all($sql);
    
    if( is_array($exercises) && !empty($exercises) )
    {
		echo '<tbody>' . "\n\n";
		
	    foreach ( $exercises as $exercise )
	    {
	        echo '<tr>'."\n"
	        .    '<td align="center">'
	        .    '<input type="checkbox" name="check_' . $exercise['id'] . '" id="check_' . $exercise['id'] . '" value="' . $exercise['id'] . '" />'
	        .    '</td>'."\n"
	        .    '<td align="left">'
	        .    '<label for="check_'.$exercise['id'].'" >'
	        .    '<img src="' . $imgRepositoryWeb . 'quiz.gif" alt="' . $langExercise . '" />'
	        .    $exercise['title']
	        .    '</label>'
	        .    '</td>'."\n"
	        .    '</tr>'."\n\n"
	        ;

	        // COMMENT

	        if( !empty($exercise['description']) )
	        {
	            echo '<tr>'."\n"
	            .    '<td>&nbsp;</td>'."\n"
	            .    '<td>'
	            .    '<small>' . $exercise['description'] . '</small>'
	            .    '</td>'."\n"
	            .    '</tr>'."\n\n"
	            ;
	        }
	        $atleastOne = true;
	    }//end while another module to display
	    echo '</tbody>'."\n\n";
	}
    
    echo '<tfoot>'."\n\n";
    
    if( !$atleastOne )
    {
        echo '<tr>'."\n"
		.	 '<td colspan="2" align="center">'
        .    $langNoEx
        .    '</td>'."\n"
		.	 '</tr>'."\n\n"
        ;
    }

    // Display button to add selected modules

    echo '<tr>'."\n"
    .    '<td colspan="2">'
    .    '<hr noshade size="1">'
    .    '</td>'."\n"
	.	 '</tr>'."\n\n"
    ;
    if( $atleastOne )
    {
        echo '<tr>'."\n"
		.	 '<td colspan="2">'
        .    '<input type="submit" name="insertExercise" value="'.$langAddModulesButton.'" />'
        .    '</td>'."\n"
		.	 '</tr>'."\n\n"
        ;
    }
    echo '</form>'."\n\n"
    .    '</tfoot>'."\n\n"
    .    '</table>'."\n\n"
    .    '<!-- end of display_my_exercises output -->' . "\n"
    ;
}

/**
  * This function is used to display the list of document available in the course
  * It also displays the form used to add selected document in the learning path
  *
  * @param string $dialogBox Error or confirmation text
  * @return nothing
  * @author Piraux S�bastien <pir@cerdecam.be>
  * @author Lederer Guillaume <led@cerdecam.be>
  */

function display_my_documents($dialogBox)
{
    global $is_adminOfCourse;

    global $curDirName;
    global $curDirPath;
    global $parentDir;

	global $langAddModule;
    global $langUp;
    global $langName;
    global $langSize;
    global $langDate;
    global $langOk;
    global $langAddModulesButton;

    global $fileList;
    global $imgRepositoryWeb;
    
    global $secureDocumentDownload;

	$output = "";
    /**
     * DISPLAY
     */
    $output .= '<!-- display_my_documents output -->' . "\n";

    $dspCurDirName = htmlspecialchars($curDirName);
    $cmdCurDirPath = rawurlencode($curDirPath);
    $cmdParentDir  = rawurlencode($parentDir);

    $output .= '<br />'
    .    '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';

    /*--------------------------------------
    DIALOG BOX SECTION
    --------------------------------------*/
    $colspan = 4;
    if( !empty($dialogBox) )
    {
        $output .= claro_disp_message_box($dialogBox);
    }
    /*--------------------------------------
    CURRENT DIRECTORY LINE
    --------------------------------------*/

    /* GO TO PARENT DIRECTORY */
    if ($curDirName) /* if the $curDirName is empty, we're in the root point
    and we can't go to a parent dir */
    {
        $output .= '<a href="' . $_SERVER['PHP_SELF'] . '?cmd=exChDir&amp;file=' . $cmdParentDir . '">' . "\n"
        .    '<img src="' . $imgRepositoryWeb . 'parent.gif" border="0" align="absbottom" hspace="5" alt="" />'."\n"
        .    '<small>' . $langUp . '</small>' . "\n"
        .    '</a>' . "\n"
        ;
    }
    /* CURRENT DIRECTORY */
    $output .= '<table class="claroTable" width="100%" border="0" cellspacing="2">';
    if ( $curDirName ) /* if the $curDirName is empty, we're in the root point
    and there is'nt a dir name to display */
    {
        $output .= '<!-- current dir name -->' . "\n"
        .    '<tr>' . "\n"
        .    '<th class="superHeader" colspan="' . $colspan . '" align="left">'. "\n"
        .    '<img src="' . $imgRepositoryWeb . 'opendir.gif" align="absbottom" vspace=2 hspace=5 alt="" />' . "\n"
        .    $dspCurDirName . "\n"
        .    '</td>' . "\n"
        .    '</tr>' . "\n"
        ;
    }

    $output .= '<tr class="headerX" align="center" valign="top" bgcolor="#e6e6e6">'
    .    '<th>' . $langAddModule . '</th>' . "\n"
    .    '<th>' . $langName . '</th>' . "\n"
    .    '<th>' . $langSize . '</th>' . "\n"
    .    '<th>' . $langDate . '</th>' . "\n"
    .    '</tr><tbody>' . "\n"
    ;


    /*--------------------------------------
    DISPLAY FILE LIST
    --------------------------------------*/

    if ( $fileList )
    {
        $iterator = 0;

        while ( list( $fileKey, $fileName ) = each ( $fileList['name'] ) )
        {

            $dspFileName = htmlspecialchars($fileName);
            $cmdFileName = str_replace("%2F","/",rawurlencode($curDirPath."/".$fileName));

            if ($fileList['visibility'][$fileKey] == "i")
            {
                if ($is_adminOfCourse)
                {
                    $style = ' class="invisible"';
                }
                else
                {
                    $style = "";
                    continue; // skip the display of this file
                }
            }
            else
            {
                $style="";
            }

            if ($fileList['type'][$fileKey] == A_FILE)
            {
                $image       = choose_image($fileName);
                $size        = format_file_size($fileList['size'][$fileKey]);
                $date        = format_date($fileList['date'][$fileKey]);
                
                if ( strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')
                    && (isset($secureDocumentDownload) && $secureDocumentDownload == true) )
                {
                    // slash argument method - only compatible with Apache
                    $doc_url = $cmdFileName;
                }
                else
                {
                    // question mark argument method, for IIS ...
                    $doc_url = '?url=' . $cmdFileName;
                }
                
                $urlFileName = '../document/goto/index.php'.$doc_url;
            }
            elseif ($fileList['type'][$fileKey] == A_DIRECTORY)
            {
                $image       = 'folder.gif';
                $size        = '&nbsp;';
                $date        = '&nbsp;';
                $urlFileName = $_SERVER['PHP_SELF'] . '?openDir=' . $cmdFileName;
            }

            $output .= '<tr align="center" ' . $style . '>'."\n";

            if ($fileList['type'][$fileKey] == A_FILE)
            {
                $iterator++;
                $output .= '<td>'
                .    '<input type="checkbox" name="insertDocument_' . $iterator . '" id="insertDocument_' . $iterator . '" value="' . $curDirPath . "/" . $fileName . '" />'
                .    '</td>' . "\n"
                ;

            }
            else
            {
                $output .= '<td>&nbsp;</td>';
            }
            $output .= '<td align="left">'
            .    '<a href="' . $urlFileName . '" ' . $style . '>'
            .    '<img src="' . $imgRepositoryWeb . $image . '" border="0" hspace="5" alt="" />' . $dspFileName . '</a>'
            .    '</td>'."\n"
            .    '<td><small>' . $size . '</small></td>' . "\n"
            .    '<td><small>' . $date . '</small></td>' . "\n"
            ;

            /* NB : Before tracking implementation the url above was simply
            * "<a href=\"",$urlFileName,"\"",$style,">"
            */


            $output .= '</tr>' . "\n";

            /* COMMENTS */

            if ($fileList['comment'][$fileKey] != "" )
            {
                $fileList['comment'][$fileKey] = htmlspecialchars($fileList['comment'][$fileKey]);
                $fileList['comment'][$fileKey] = claro_parse_user_text($fileList['comment'][$fileKey]);

                $output .= '<tr align="left">'."\n"
                	.'<td>&nbsp;</td>'."\n"
                	.'<td colspan="'.$colspan.'">'."\n"
                	.'<div class="comment">'
                	.$fileList['comment'][$fileKey]
	                .'</div>'."\n"
	                .'</td>'."\n"
	                .'</tr>'."\n";
            }
        }  // end each ($fileList)
        // form button
        $output .= '</tbody><tfoot>'
        	.'<tr><td colspan="4"><hr noshade size="1"></td></tr>'."\n";

        $output .= '<tr>'."\n"
			.'<td colspan="'.$colspan.'" align="left">'."\n"
			.'<input type="hidden" name="openDir" value="'.$curDirPath.'" />'."\n"
			.'<input type="hidden" name="maxDocForm" value ="'.$iterator.'" />'."\n"
			.'<input type="submit" name="submitInsertedDocument" value="'.$langAddModulesButton.'" />'."\n"
			.'</td>'."\n"
			.'</tr>'."\n";
    } // end if ( $fileList)
	else
	{
		$output .= '<tr><td colspan="4"><hr noshade size="1"></td></tr>'."\n";
    }

	$output .= '</tfoot></table>'."\n"
    	.'</form>'."\n"
    	.'<!-- end of display_my_documents output -->'."\n";
	
	return $output;
}

/**
 * Recursive Function used to find the deep of a module in a learning path
 * DEPRECATED : no more since the display has been reorganised
 *
 * @param integer $id id_of_module that we are looking for deep
 * @param array $searchInarray of parents of modules in a learning path $searchIn[id_of_module] = parent_of_this_module
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function find_deep($id, $searchIn)
{
    if ( $searchIn[$id] == 0 || !isset($searchIn[$id]) && $id == $searchIn[$id])
    return 0;
    else
    return find_deep($searchIn[$id],$searchIn) + 1;
}

/**
 * Build an tree of $list from $id using the 'parent' 
 * table. (recursive function)
 * Rows with a father id not existing in the array will be ignored
 *
 * @param $list modules of the learning path list
 * @param $paramField name of the field containing the parent id
 * @param $idField name of the field containing the current id
 * @param $id learnPath_module_id of the node to build
 * @return tree of the learning path 
 *
 * @author Piraux S�bastien <pir@cerdecam.be>     
 */
function build_element_list($list, $parentField, $idField, $id = 0)
{
    $tree = array();

    if(is_array($list))
    {
        foreach ($list as $element)
        {
            if( $element[$idField] == $id )
            {
                $tree = $element; // keep all $list informations in the returned array
                // explicitly add 'name' and 'value' for the claro_build_nested_select_menu function
                //$tree['name'] = $element['name']; // useless since 'name' is the same word in db and in the  claro_build_nested_select_menu function
                $tree['value'] = $element[$idField];
                break;
            }
        }

        foreach ($list as $element)
        {
            if($element[$parentField] == $id && ( $element[$parentField] != $element[$idField] ))
            {
                if($id == 0)
                {
                    $tree[] = build_element_list($list, $parentField, $idField, $element[$idField]);
                }
                else
                {
                    $tree['children'][] = build_element_list($list, $parentField, $idField, $element[$idField]);
                }
            }
        }
    }
    return $tree;
}

/**
 * return a flattened tree of the modules of a learnPath after having add
 * 'up' and 'down' fields to let know if the up and down arrows have to be 
 * displayed. (recursive function)
 * 
 * @param $elementList a tree array as one returned by build_element_list
 * @param $deepness
 * @return array containing infos of the learningpath, each module is an element 
    of this array and each one has 'up' and 'down' boolean and deepness added in
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function build_display_element_list($elementList, $deepness = 0)
{
    $count = 0;
    $first = true;
    $last = false;
    $displayElementList = array();

    foreach($elementList as $thisElement)
    {
        $count++;

        // temporary save the children before overwritten it
        if (isset($thisElement['children']))
        $temp = $thisElement['children'];
        else
        $temp = NULL; // re init temp value if there is nothing to put in it

        // we use 'children' to calculate the deepness of the module, it will be displayed
        // using a spacing multiply by deepness
        $thisElement['children'] = $deepness;

        //--- up and down arrows displayed ?
        if ($count == count($elementList) )
        $last = true;

        $thisElement['up'] = $first ? false : true;
        $thisElement['down'] = $last ? false : true;

        //---
        $first = false;

        $displayElementList[] = $thisElement;

        if ( isset( $temp ) && sizeof( $temp ) > 0 )
        {
            $displayElementList = array_merge( $displayElementList,
            build_display_element_list($temp, $deepness + 1 ) );
        }
    }
    return  $displayElementList;
}

/**
 * This function set visibility for all the nodes of the tree module_tree
 *
 * @param $module_tree tree of modules we want to change the visibility
 * @param $visibility ths visibility string as requested by the DB
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function set_module_tree_visibility($module_tree, $visibility)
{
    //$tbl_cdb_names = claro_sql_get_course_tbl();
    //$tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
    $tbl_lp_rel_learnPath_module = "lp_rel_learnPath_module";

    foreach($module_tree as $module)
    {
        if($module['visibility'] != $visibility)
        {
            $sql = "UPDATE `" . $tbl_lp_rel_learnPath_module . "`
                        SET `visibility` = '" . addslashes($visibility) . "'
                        WHERE `learnPath_module_id` = " . (int) $module['learnPath_module_id'] . "
                          AND `visibility` != '" . addslashes($visibility) . "'";
            db_query($sql);
        }
        if (isset($module['children']) && is_array($module['children']) ) set_module_tree_visibility($module['children'], $visibility);
    }
}

/**
 * This function deletes all the nodes of the tree module_tree
 *
 * @param $module_tree tree of modules we want to change the visibility
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function delete_module_tree($module_tree)
{
    $tbl_lp_rel_learnPath_module = "lp_rel_learnPath_module";
    $tbl_lp_user_module_progress = "lp_user_module_progress";
    $tbl_lp_module               = "lp_module";
    $tbl_lp_asset                = "lp_asset";

    foreach($module_tree as $module)
    {
        switch($module['contentType'])
        {
            case CTSCORM_ :
                // delete asset if scorm
                $delAssetSql = "DELETE
                                    FROM `".$tbl_lp_asset."`
                                    WHERE `module_id` =  ". (int)$module['module_id']."
                                    ";
                db_query($delAssetSql);
                // no break; because we need to delete modul
            case CTLABEL_ : // delete module if scorm && if label
                $delModSql = "DELETE FROM `" . $tbl_lp_module . "`
                                     WHERE `module_id` =  ". (int)$module['module_id'];
                db_query($delModSql);
                // no break; because we need to delete LMP and UMP
            default : // always delete LPM and UMP
                db_query("DELETE FROM `" . $tbl_lp_rel_learnPath_module . "`
                                        WHERE `learnPath_module_id` = " . (int)$module['learnPath_module_id']);
                db_query("DELETE FROM `" . $tbl_lp_user_module_progress . "`
                                        WHERE `learnPath_module_id` = " . (int)$module['learnPath_module_id']);
    
                break;
        }
    }
    if ( isset($module['children']) &&  is_array($module['children']) ) delete_module_tree($module['children']);
}
/**
 * This function return the node with $module_id (recursive)
 * 
 *
 * @param $lpModules array the tree of all modules in a learning path
 * @param $iid node we are looking for
 * @param $field type of node we are looking for (learnPath_module_id, module_id,...)
 *
 * @return array the requesting node (with all its children)
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 */
function get_module_tree( $lpModules , $id, $field = 'module_id')
{
    foreach( $lpModules as $module)
    {
        if( $module[$field] == $id)
        {
            return $module;
        }
        elseif ( isset($module['children']) && is_array($module['children']) )
        {
            $temp = get_module_tree($module['children'], $id);
            if( is_array($temp) )
            return $temp;
            // else check next node
        }

    }
}

/**
 * Convert the time recorded in seconds to a scorm type 
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 * @param $time time in seconds to convert to a scorm type time
 * @return string compatible scorm type (smaller format)
 */
function seconds_to_scorm_time($time)
{
    $hours     = floor( $time / 3600 );
    if( $hours < 10 )
    {
        $hours = "0".$hours;
    }
    $min     = floor( ( $time -($hours * 3600) ) / 60 );
    if( $min < 10)
    {
        $min = '0' . $min;
    }
    $sec    = $time - ($hours * 3600) - ($min * 60);
    if($sec < 10)
    {
        $sec = '0' . $sec;
    }

    return     $hours . ':' . $min . ':' . $sec;
} 
/**
  * This function allow to see if a time string is the SCORM requested format : hhhh:mm:ss.cc
  *
  * @param $time a suspected SCORM time value, returned by the javascript API
  *
  * @author Lederer Guillaume <led@cerdecam.be>
  */
function isScormTime($time)
{
    $mask = "/^[0-9]{2,4}:[0-9]{2}:[0-9]{2}.?[0-9]?[0-9]?$/";
    if (preg_match($mask,$time))
     {
       return TRUE;
     }

    return FALSE;
}

 /**
  * This function allow to add times saved in the SCORM requested format : hhhh:mm:ss.cc
  *
  * @param $time1 a suspected SCORM time value, total_time,  in the API
  * @param $time2 a suspected SCORM time value, session_time to add, in the API
  *
  * @author Lederer Guillaume <led@cerdecam.be>
  *
  */
function addScormTime($time1, $time2)
{
       if (isScormTime($time2))
    {
          //extract hours, minutes, secondes, ... from time1 and time2

          $mask = "/^([0-9]{2,4}):([0-9]{2}):([0-9]{2}).?([0-9]?[0-9]?)$/";
        
          preg_match($mask,$time1, $matches);
          $hours1 = $matches[1];
          $minutes1 = $matches[2];
          $secondes1 = $matches[3];
          $primes1 = $matches[4];

          preg_match($mask,$time2, $matches);
          $hours2 = $matches[1];
          $minutes2 = $matches[2];
          $secondes2 = $matches[3];
          $primes2 = $matches[4];

          // calculate the resulting added hours, secondes, ... for result

          $primesReport = FALSE;
          $secondesReport = FALSE;
          $minutesReport = FALSE;
          $hoursReport = FALSE;

        //calculate primes

          if ($primes1 < 10) {$primes1 = $primes1*10;}
          if ($primes2 < 10) {$primes2 = $primes2*10;}
          $total_primes = $primes1 + $primes2;
          if ($total_primes >= 100)
          {
            $total_primes -= 100;
            $primesReport = TRUE;
          }

        //calculate secondes

          $total_secondes = $secondes1 + $secondes2;
          if ($primesReport) {$total_secondes ++;}
          if ($total_secondes >= 60)
          {
            $total_secondes -= 60;
            $secondesReport = TRUE;
          }

        //calculate minutes

          $total_minutes = $minutes1 + $minutes2;
          if ($secondesReport) {$total_minutes ++;}
          if ($total_minutes >= 60)
          {
            $total_minutes -= 60;
            $minutesReport = TRUE;
          }

        //calculate hours

          $total_hours = $hours1 + $hours2;
          if ($minutesReport) {$total_hours ++;}
          if ($total_hours >= 10000)
          {
            $total_hours -= 10000;
            $hoursReport = TRUE;
          }

        // construct and return result string

          if ($total_hours < 10) {$total_hours = "0" . $total_hours;}
          if ($total_minutes < 10) {$total_minutes = "0" . $total_minutes;}
          if ($total_secondes < 10) {$total_secondes = "0" . $total_secondes;}
          
        $total_time = $total_hours . ":" . $total_minutes . ":" . $total_secondes;
        // add primes only if != 0
        if ($total_primes != 0) {$total_time .= "." . $total_primes;}
        return $total_time;
       }
       else
       {
        return $time1;
    }
}


/*********************** Functions from claroline 1.7.x ***********************/

/**
 * function that cleans php string for javascript
 *
 * This function is needed to clean strings used in javascript output
 * Newlines are prohibited in the script, specialchar  are prohibited
 * quotes must be addslashes
 *
 * @param $str string original string
 * @return string cleaned string
 *
 * @author Piraux S�bastien <pir@cerdecam.be>
 *
 */
function clean_str_for_javascript( $str )
{
    $output = $str;
    // 1. addslashes, prevent problems with quotes
    // must be before the str_replace to avoid double backslash for \n
    $output = addslashes($output);
    // 2. turn windows CR into *nix CR
    $output = str_replace("\r", '', $output);
    // 3. replace "\n" by uninterpreted '\n'
    $output = str_replace("\n",'\n', $output);
    // 4. convert special chars into html entities
    $output = htmlspecialchars($output);

    return $output;
}

/**
 * Parse the user text (e.g. stored in database)
 * before displaying it to the screen
 * For example it change new line charater to <br> tag etc.
 *
 * @param string $userText original user tex
 * @return string parsed user text
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 */

function claro_parse_user_text($userText)
{
   global $claro_texRendererUrl; // see 'inc/conf/claro_main.conf.php'

   if ( !empty($claro_texRendererUrl) )
   {
       $userText = str_replace('[tex]',
                          '<img src="'.$claro_texRendererUrl.'?',
                          $userText);

       $userText = str_replace('[/tex]',
                           '" border="0" align="absmiddle">',
                           $userText);
   }
   else
   {
       $userText = str_replace('[tex]',
                              '<embed TYPE="application/x-techexplorer" texdata="',
                              $userText);

       $userText = str_replace('[/tex]',
                               '" width="100%" pluginspace="http://www.integretechpub.com/">',
                               $userText);
   }

   $userText = make_clickable($userText);

   if ( strpos($userText, '<!-- content: html -->') === false )
   {
        // only if the content isn't HTML change new line to <br>
        // Note the '<!-- content: html -->' is introduced by HTML Area
        $userText = nl2br($userText);
   }

    return $userText;
}

/**
 * Displays the title of a tool. Optionally, there can be a subtitle below
 * the normal title, and / or a supra title above the normal title.
 *
 * e.g. supra title:
 * group
 * GROUP PROPERTIES
 *
 * e.g. subtitle:
 * AGENDA
 * calender & events tool
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param  mixed $titleElement - it could either be a string or an array
 *                               containing 'supraTitle', 'mainTitle',
 *                               'subTitle'
 * @return void
 */

function claro_disp_tool_title($titlePart, $helpUrl = false)
{
    // if titleElement is simply a string transform it into an array

    if ( is_array($titlePart) )
    {
        $titleElement = $titlePart;
    }
    else
    {
        $titleElement['mainTitle'] = $titlePart;
    }


    $string = "\n" . '<h3 class="claroToolTitle">' . "\n";

    if ($helpUrl)
    {
        global $clarolineRepositoryWeb, $imgRepositoryWeb,$langHelp;

    $string .= "<a href='#' onClick=\"MyWindow=window.open('". $clarolineRepositoryWeb . "help/" .$helpUrl
            ."','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=350,height=450,left=300,top=10'); return false;\">"

            .'<img src="'.$imgRepositoryWeb.'/help.gif" '
            .' alt ="'.$langHelp.'"'
            .' align="right"'
            .' hspace="30">'
            .'</a>' . "\n"
            ;
    }


    if ( isset($titleElement['supraTitle']) )
    {
        $string .= '<small>' . $titleElement['supraTitle'] . '</small><br />' . "\n";
    }

    if ( isset($titleElement['mainTitle']) )
    {
        $string .= $titleElement['mainTitle'] . "\n";
    }

    if ( isset($titleElement['subTitle']) )
    {
        $string .= '<br /><small>' . $titleElement['subTitle'] . '</small>' . "\n";
    }

    $string .= '</h3>'."\n\n";

    return $string;
}


/**
 * Prepare display of the message box appearing on the top of the window,
 * just    below the tool title. It is recommended to use this function
 * to display any confirmation or error messages, or to ask to the user
 * to enter simple parameters.
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param string $message - include your self any additionnal html
 *                          tag if you need them
 * @return $string - the
 */

function claro_disp_message_box($message)
{
    return "\n".'<table class="claroMessageBox" border="0" cellspacing="0" cellpadding="10">'
    .      '<tr>'
    .      '<td>'
    .      $message
    .      '</td>'
    .      '</tr>'
    .      '</table>' . "\n\n"
    ;
}

/**
 * Terminate the script and display message
 *
 * @param string message
 */

function claro_die($message)
{
    global $includePath, $clarolineRepositoryWeb, $claro_stylesheet, $rootWeb,
           $siteName, $text_dir, $uid, $_cid, $administrator_name, $administrator_email,
           $is_platformAdmin, $_course, $_user, $_courseToolList, $coursesRepositoryWeb,
           $is_courseAllowed, $imgRepositoryWeb, $lang_footer_p_CourseManager,
           $lang_p_platformManager, $langPoweredBy, $langModifyProfile,
           $langLogout, $langOtherCourses, $langModifyProfile, $langMyCourses,
           $langMyAgenda, $langLogin, $langCourseHome, $_tid;

    if ( ! headers_sent () )
    {
    // display header
        require $includePath . '/claro_init_header.inc.php';
    }

    echo '<table align="center">'
    .    '<tr><td>'
    .    claro_disp_message_box($message)
    .    '</td></tr>'
    .    '</table>'
    ;

    require $includePath . '/claro_init_footer.inc.php' ;

    die(); // necessary to prevent any continuation of the application
}


/**
 * Prepare the display of a clikcable button
 *
 * This function is needed because claroline buttons rely on javascript.
 * The function return an optionnal behavior fo browser where javascript
 * isn't  available.
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 *
 * @param string $url url inserted into the 'href' part of the tag
 * @param string $text text inserted between the two <a>...</a> tags (note : it
 *        could also be an image ...)
 * @param string $confirmMessage (optionnal) introduce a javascript confirmation popup
 * @return string the button
 */

function claro_disp_button($url, $text, $confirmMessage = '')
{

    if (   claro_is_javascript_enabled()
        && ! preg_match('~^Mozilla/4\.[1234567]~', $_SERVER['HTTP_USER_AGENT']))
    {
        if ($confirmMessage != '')
        {
            $onClickCommand = "if(confirm('" . clean_str_for_javascript($confirmMessage) . "')){document.location='" . $url . "';return false}";
        }
        else
        {
            $onClickCommand = "document.location='".$url."';return false";
        }

        return '<button class="claroButton" onclick="' . $onClickCommand . '">'
        .      $text
        .      '</button>&nbsp;' . "\n"
        ;
    }
    else
    {
        return '<nobr>[ <a href="' . $url . '">' . $text . '</a> ]</nobr>';
    }
}

/**
 * Function used to draw a progression bar
 *
 * @author Piraux S�astien <pir@cerdecam.be>
 *
 * @param integer $progress progression in pourcent
 * @param integer $factor will be multiply by 100 to have the full size of the bar
 * (i.e. 1 will give a 100 pixel wide bar)
 */

function claro_disp_progress_bar ($progress, $factor)
{
    $maxSize  = $factor * 100; //pixels
    $barwidth = $factor * $progress ;

    // display progress bar
    // origin of the bar
    $progressBar = '<img src="../../images/bar_1.gif" width="1" height="12" alt="">';

    if($progress != 0)
            $progressBar .= '<img src="../../images/bar_1u.gif" width="' . $barwidth . '" height="12" alt="">';
    // display 100% bar

    if($progress!= 100 && $progress != 0)
            $progressBar .= '<img src="../../images/bar_1m.gif" width="1" height="12" alt="">';

    if($progress != 100)
            $progressBar .= '<img src="../../images/bar_1r.gif" width="' . ($maxSize - $barwidth) . '" height="12" alt="">';
    // end of the bar
    $progressBar .=  '<img src="../../images/bar_1.gif" width="1" height="12" alt="">';

    return $progressBar;
}


/**
 * Insert a    sort of    HTML Wysiwyg textarea inside a FORM
 * the html area currently implemented is HTMLArea 3.0. To work correctly,
 * the area    needs a    specific stylesheet
 * previously loaded in the html header.
 * For that, use the claroline $htmlHeadXtra[] array at
 * the top of the script
 * just before including claro_init_header.inc.php
 *
 * @param string $name content for name attribute in textarea tag
 * @param string $content optional content previously inserted into    the    area
 * @param int     $rows optional    textarea rows
 * @param int     $cols optional    textarea columns
 * @param string $optAttrib    optional - additionnal tag attributes
 *                                       (wrap, class, ...)
 * @return void
 *
 * @global strin urlAppend from    claro_main.conf.php
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 */
// Example : $htmlHeadXtra[] = '<style type="text/css">
//                               @import url('.$urlAppend.'/claroline/inc/htmlarea'.'/htmlarea.css);
//                              </style>';

function claro_disp_html_area($name, $content = '',
                              $rows=20,    $cols=80, $optAttrib='')
{
    global $urlAppend, $iso639_1_code, $langTextEditorDisable, $langTextEditorEnable,$langSwitchEditorToTextConfirm;
    $incPath = $urlAppend.'/claroline/inc/htmlarea';

    ob_start();

    if( ! isset( $_SESSION['htmlArea'] ) )
    {
        // TODO use a config variable instead of hardcoded value
        $_SESSION['htmlArea'] = 'enabled';
    }

    if (isset($_REQUEST['areaContent'])) $content = stripslashes($_REQUEST['areaContent']);

    if (claro_is_javascript_enabled())
    {
        if ( isset($_SESSION['htmlArea']) && $_SESSION['htmlArea'] != 'disabled' )
        {
            $switchState = 'off';
            $message     = $langTextEditorDisable;
            $areaContent = 'editor.getHTML()';
            $confirmCommand = "if(!confirm('".clean_str_for_javascript($langSwitchEditorToTextConfirm)."'))return(false);";
        }
        else
        {
            $switchState = 'on';
            $message     = $langTextEditorEnable;
            $areaContent = 'document.getElementById(\''.$name.'\').value';
            $confirmCommand = '';
        }

        $location = '\''
        .           $incPath.'/editorswitcher.php?'
        .           'switch='.$switchState
        .           '&sourceUrl=' . urlencode($_SERVER['REQUEST_URI'])
        .           '&areaContent='
        .           '\''
        .           '+escape('.$areaContent.')'
        ;



        echo "\n".'<div align="right">'
        .    '<small>'
        .    '<b>'
        .    '<a href="/" onClick ="' . $confirmCommand . 'window.location='
        .    $location . ';return(false);">'
        .    $message
        .    '</a>'
        .    '</b>'
        .    '</small>'
        .    '</div>'."\n"
        ;

    } // end if claro_is_javascript_enabled()


echo '<textarea '
        .'id="'.$name.'" '
        .'name="'.$name.'" '
        .'style="width:100%" '
        .'rows="'.$rows.'" '
        .'cols="'.$cols.'" '
        .$optAttrib.' >'
        ."\n".$content."\n"
        .'</textarea>'."\n";

    if ( isset($_SESSION['htmlArea']) && $_SESSION['htmlArea'] != 'disabled' )
    {

?>

<script type="text/javascript">_editor_url = "<?php echo  $incPath?>";</script>
<script type="text/javascript" src="<?php echo $incPath; ?>/htmlarea.js"></script>
<script type="text/javascript" src="<?php echo $incPath; ?>/lang/<?php echo $iso639_1_code; ?>.js"></script>
<script type="text/javascript" src="<?php echo $incPath; ?>/dialog.js"></script>

<script type="text/javascript">
var    editor = null;
function initEditor() {
  editor = new HTMLArea("<?php echo $name ?>");

  // comment the following two lines to    see    how    customization works
  editor.generate();
  return false;
}
<?php
// there is no link or button to use these functions, so do not output them
/*
function insertHTML() {
 var html =    prompt("Enter some HTML    code here");
 if    (html) {editor.insertHTML(html);}
}
function highlight() {
  editor.surroundHTML('<span style="background-color: yellow">', '</span>');
}
*/
?>
</script>

<script type="text/javascript">
initEditor();
</script>
<?php
    } // end if  $_SESSION['htmlArea'] != 'disabled'
    else
    {
        // noop
    }

    $returnString = ob_get_contents();
    ob_end_clean();
    return $returnString;
}

/**
 * function claro_build_nested_select_menu($name, $elementList)
 * Build in a relevant way 'select' menu for an HTML form containing nested data
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 *
 * @param string $name, name of the select tag
 *
 * @param array nested data in a composite way
 *
 *  Exemple :
 *
 *  $elementList[1]['name'    ] = 'level1';
 *  $elementList[1]['value'   ] = 'level1';
 *
 *  $elementList[1]['children'][1]['name' ] = 'level2';
 *  $elementList[1]['children'][1]['value'] = 'level2';
 *
 *  $elementList[1]['children'][2]['name' ] = 'level2';
 *  $elementList[1]['children'][2]['value'] = 'level2';
 *
 *  $elementList[2]['name' ]  = 'level1';
 *  $elementList[2]['value']  = 'level1';
 *
 * @return string the HTML flow
 * @desc depends on prepare option tags
 *
 */

function claro_build_nested_select_menu($name, $elementList)
{
    return '<select name="' . $name . '">' . "\n"
    .      implode("\n", prepare_option_tags($elementList) )
    .      '</select>' .  "\n"
    ;
}

/**
 * prepare the 'option' html tag for the claro_disp_nested_select_menu()
 * fucntion
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param array $elementList
 * @param int  $deepness (optionnal, default is 0)
 * @return array of option tag list
 */


function prepare_option_tags($elementList, $deepness = 0)
{
    foreach($elementList as $thisElement)
    {
        $tab = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $deepness);

        $optionTagList[] = '<option value="'.$thisElement['value'].'">'
        .                  $tab.$thisElement['name']
        .                  '</option>'
        ;
        if (   isset( $thisElement['children'] )
            && sizeof($thisElement['children'] ) > 0)
        {
            $optionTagList = array_merge( $optionTagList,
                                          prepare_option_tags($thisElement['children'],
                                                              $deepness + 1 ) );
        }
    }

    return  $optionTagList;
}
//////////////////////////////////////////////////////////////////////////////
//                              INPUT HANDLING
//
//////////////////////////////////////////////////////////////////////////////

/**
 * checks if the javascript is enabled on the client browser
 * Actually a cookies is set on the header by a javascript code.
 * If this cookie isn't set, it means javascript isn't enabled.
 *
 * @return boolean enabling state of javascript
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 */

function claro_is_javascript_enabled()
{
    global $_COOKIE;

    if ( isset( $_COOKIE['javascriptEnabled'] ) && $_COOKIE['javascriptEnabled'] == true)
    {
        return true;
    }
    else
    {
        return false;
    }
}

?>