<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2013  Greek Universities Network - GUnet
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

$require_login = TRUE;
if(isset($_GET['course'])) {//course messages
    $require_current_course = TRUE;
} else {//personal messages
    $require_current_course = FALSE;
}
$guest_allowed = FALSE;

include '../../include/baseTheme.php';

if (!isset($course_id)) {
    $course_id = 0;
}

require_once("class.thread.php");

if (isset($_GET['tid'])) {
    require_once("class.msg.php");
    
    $tid = intval($_GET['tid']);
    $thread = new Thread($tid, $uid);
    if (!$thread->error) {
        $msgs = $thread->getMsgs();
       
        $out = "<div class=\"loading\" align=\"center\"><img src=\"".$themeimg."/ajax_loader.gif"."\" align=\"absmiddle\"/>".$langLoading."</div>";
        $urlstr = '';
        if ($course_id != 0) {
            $urlstr = "?course=".$course_code;
        }
        $out .= "<div style=\"float:right;\"><a href=\"inbox.php".$urlstr."\">$langBack</a></div>";
        $out .= "<h2>$langSubject: ".q($thread->subject)."</h2><br/>";
        if ($thread->course_id != 0 && $course_id == 0) {
            $out .= "<p class=\"tags\"><span class=\"st_tag\"><a class=\"outtabs\" href=\"index.php?course=".course_id_to_code($thread->course_id)."\">".course_id_to_title($thread->course_id)."</a></span></p><br/>";
        }
        $out .= "<table id=\"thread_table\">
                  <thead>
                    <tr>
                      <th>$langDate</th>
                      <th>$langSender</th>
                      <th>$langMessage</th>
                      <th>$langAttachedFile</th>
                      <th>$langDelete</th>
                    </tr>
                  </thead>
                  <tbody>";
        
        foreach ($msgs as $m) {
            $out .= "<tr id='$m->id'>
                       <td>".nice_format(date('Y-m-d H:i:s',$m->timestamp), true)."</td>
                       <td>".uid_to_name($m->author_id)."</td>
                       <td>".standard_text_escape($m->body)."</td>";
            if ($m->filename != '') {
                $out .= "<td><a href=\"dropbox_download.php?course=".course_id_to_code($m->course_id)."&amp;id=$m->id\" class=\"outtabs\" target=\"_blank\">$m->real_filename</a></td>";
            } else {
                $out .= "<td></td>";
            }
            $out .= "  <td><img src=\"".$themeimg.'/delete.png'."\" class=\"delete\"/></td>        
                     </tr>";
        }
        
        $out .= "  </tbody>
                 </table><br/><br/>";
        
        /*****Reply Form****/
        if ($course_id == 0) {
            $out .= "<form method='post' action='dropbox_submit.php' enctype='multipart/form-data' onsubmit='return checkForm(this)'>";
        } else {
            $out .= "<form method='post' action='dropbox_submit.php?course=$course_code' enctype='multipart/form-data' onsubmit='return checkForm(this)'>";
        }
        //hidden variables needed in case of a reply
        $out .= "<input type='hidden' name='message_title' value='$thread->subject' />";
        $out .= "<input type='hidden' name='thread_id' value='$thread->id' />";
        foreach ($thread->recipients as $rec) {
            if ($rec != $uid) {
                $out .= "<input type='hidden' name='recipients[]' value='$rec' />";
            }
        }
        $out .= "<fieldset>
                   <table width='100%' class='tbl'>
                     <caption><b>$langReply</b></caption>
                     <tr>
                       <th>$langSender:</th>
                       <td>" . q(uid_to_name($uid)) . "</td>
	                 </tr>";
        $out .= "<tr>
                  <th>" . $langMessage . ":</th>
                  <td>".rich_text_editor('body', 4, 20, '')."
                    <small><br/>$langMaxMessageSize</small></td>
                 </tr>";
        if ($course_id != 0) {
            $out .= "<tr>
                   <th width='120'>$langFileName:</th>
                   <td><input type='file' name='file' size='35' />
                   </td>
                 </tr>";
        }
        
        $out .= "<tr>
	               <th>&nbsp;</th>
                   <td class='left'><input type='submit' name='submit' value='" . q($langSend) . "' />&nbsp;
                      $langMailToUsers<input type='checkbox' name='mailing' value='1' checked /></td>
                 </tr>
               </table>
             </fieldset>
           </form>
           <p class='right smaller'>$langMaxFileSize " . ini_get('upload_max_filesize') . "</p>";

         $out .= "<script type='text/javascript' src='{$urlAppend}js/jquery.multiselect.min.js'></script>\n";
         $out .= "<script type='text/javascript'>$(document).ready(function () {
                              $('#select-recipients').multiselect({
                                selectedText: '$langJQSelectNum',
                                noneSelectedText: '$langJQNoneSelected',
                                checkAllText: '$langJQCheckAll',
                                uncheckAllText: '$langJQUncheckAll'
                              });
                            });</script>
        <link href='../../js/jquery.multiselect.css' rel='stylesheet' type='text/css'>";
        /******End of Reply Form ********/

        $out .= "<script>
                   $(document).ready(function() {
                     $('div.loading').hide();
                     $('#thread_table').dataTable();
                   });
                 </script>";
        
        $out .= '<script>
                  $(function() {
                    $(".delete").click(function() {
                      $(\'div.loading\').fadeIn();
                      var rowContainer = $(this).parent().parent();
                      var id = rowContainer.attr("id");
                      var string = \'mid=\'+ id ;
        
                      $.ajax({
                        type: "POST",
                        url: "delete.php",
                        data: string,
                        cache: false,
                        success: function(){
                          rowContainer.slideUp(\'slow\', function() {$(this).remove();});
                          $(\'div.loading\').fadeOut();
                        }
                     });
                     return false;
                   });
                 });
                 </script>';
    }
} else {
    require_once("class.mailbox.php");
    
    $mbox = new Mailbox($uid, $course_id);
    
    $threads = $mbox->getInboxThreads();
    
    if (empty($threads)) {
        $out = "<p class='alert1'>$langTableEmpty</p>";
    } else {
        $out = "<div class=\"loading\" align=\"center\"><img src=\"".$themeimg."/ajax_loader.gif"."\" align=\"absmiddle\"/>".$langLoading."</div>";
        $out .= "<table id=\"inbox_table\">
                  <thead>
                    <tr>";
        if ($course_id != 0) {
            $out .= "<th>$langCourse</th>";
        }
        $out .= "     <th>$langSubject</th>
                      <th>$langParticipants</th>
                      <th>$langDelete</th>
                    </tr>
                  </thead>
                  <tbody>";
        
        foreach ($threads as $thread) {
            $participants = '';
            foreach ($thread->recipients as $r) {
                $participants .= uid_to_name($r).'<br/>';
            }
            $participants = substr($participants, 0, strlen($participants)-5);
            $urlstr = '';
            if ($course_id != 0) {
                $urlstr = "&amp;course=".$course_code;
            }
            $out .= "<tr id='$thread->id'>";
            if ($course_id != 0) {
                $out .= "<td><a class=\"outtabs\" href=\"index.php?course=".course_id_to_code($thread->course_id)."\">".course_id_to_title($thread->course_id)."</a></td>";
            }
            $out .= " <td><a href='inbox.php?tid=$thread->id".$urlstr."'>".q($thread->subject)."</a></td>
                      <td>$participants</td>
                      <td><img src=\"".$themeimg.'/delete.png'."\" class=\"delete\"/></td>
                    </tr>";
        }
        
        $out .= "  </tbody>
                 </table>";
        $out .= "<script>
                   $(document).ready(function() {
                     $('div.loading').hide();
                     $('#inbox_table').dataTable();
                   });
                 </script>";
        
        $out .= '<script>
                  $(function() {
                    $(".delete").click(function() {
                      $(\'div.loading\').fadeIn();
                      var rowContainer = $(this).parent().parent();
                      var id = rowContainer.attr("id");
                      var string = \'tid=\'+ id ;

                      $.ajax({
                        type: "POST",
                        url: "delete.php",
                        data: string,
                        cache: false,
                        success: function(){
                          rowContainer.slideUp(\'slow\', function() {$(this).remove();});
                          $(\'div.loading\').fadeOut();
                        }
                     });
                     return false;
                   });
                 });
                 </script>';
    }
}
echo $out;
    