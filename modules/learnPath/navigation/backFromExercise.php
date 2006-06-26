<?php 

/**=============================================================================
       	GUnet e-Class 2.0 
        E-learning and Course Management Program  
================================================================================
       	Copyright(c) 2003-2006  Greek Universities Network - GUnet
        � full copyright notice can be read in "/info/copyright.txt".
        
       	Authors:    Costas Tsibanis <k.tsibanis@noc.uoa.gr>
                     Yannis Exidaridis <jexi@noc.uoa.gr> 
                     Alexandros Diamantidis <adia@noc.uoa.gr> 

        For a full list of contributors, see "credits.txt".  
     
        This program is a free software under the terms of the GNU 
        (General Public License) as published by the Free Software 
        Foundation. See the GNU License for more details. 
        The full license can be read in "license.txt".
     
       	Contact address: GUnet Asynchronous Teleteaching Group, 
        Network Operations Center, University of Athens, 
        Panepistimiopolis Ilissia, 15784, Athens, Greece
        eMail: eclassadmin@gunet.gr
==============================================================================*/

/**===========================================================================
	backFromExercise.php
	@last update: 30-06-2006 by Thanos Kyritsis
	@authors list: Thanos Kyritsis <atkyritsis@upnet.gr>
	               
	based on Claroline version 1.7 licensed under GPL
	      copyright (c) 2001, 2006 Universite catholique de Louvain (UCL)
	      
	      original file: backFromExercise.php Revision: 1.6
	      
	Claroline authors: Piraux S�bastien <pir@cerdecam.be>
                      Lederer Guillaume <led@cerdecam.be>
==============================================================================        
    @Description: This script refreshes the upper frame for the user to see 
                  his updated learning path progress and prompts him
                  to click next after finishing an exercise.

    @Comments:
 
    @todo: 
==============================================================================
*/

$require_current_course = TRUE;
$langFiles='learnPath';

require_once("../../../config/config.php");
require_once ('../../../include/init.php');
?>
<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset?>">
 <link href="../../../template/classic/theme.css" rel="stylesheet" type="text/css" />
 <link href="../../../template/classic/tool_content.css" rel="stylesheet" type="text/css" />
 <link href="../tool.css" rel="stylesheet" type="text/css" />
 <script>
  <!-- //
   parent.tocFrame.location.href="../viewer_toc.php";
  //-->
 </script>
</head>
<body>
 <center>
  <br /><br /><br />
  <p>
<?php
if($_GET['op'] == 'cancel')
{
    echo $langExerciseCancelled;
}
elseif($_GET['op'] == 'finish') // exercise done
{
    echo $langExerciseDone;
}
?>
   </p>
  </center>
 </body>
</html>