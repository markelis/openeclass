<?php
/*=============================================================================
       	GUnet eClass 2.0 
        E-learning and Course Management Program  
================================================================================
       	Copyright(c) 2003-2006  Greek Universities Network - GUnet
        A full copyright notice can be read in "/info/copyright.txt".
        
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

/*===========================================================================
	phpInfo.php
	@last update: 31-05-2006 by Pitsiougas Vagelis
	@authors list: Karatzidis Stratos <kstratos@uom.gr>
		       Pitsiougas Vagelis <vagpits@uom.gr>
==============================================================================        
        @Description: Show contents of phpinfo()

 	This script allows the administrator to view the results of phpinfo()

 	The user can : - View information generated by phpinfo()
                 - Return to course list

 	@Comments: The script is organised in two sections.

  1) Display results generated from phpinfo()
  2) Display all on an HTML page
  
==============================================================================*/

/*****************************************************************************
		DEAL WITH BASETHEME, OTHER INCLUDES AND NAMETOOLS
******************************************************************************/

// Check if user is administrator and if yes continue
// Othewise exit with appropriate message
$require_admin = TRUE;
// Include baseTheme
include '../../include/baseTheme.php';
// Define $nameTools
$nameTools = "����������� ��� ��� PHP";
$navigation[] = array("url" => "index.php", "name" => $langAdmin);
// Initialise $tool_content
$tool_content = "";

/*****************************************************************************
		MAIN BODY
******************************************************************************/
// Display phpinfo
if ($to=="phpinfo") {
	$tool_content .= '<div>';
	ob_start();
	phpinfo();
	$phpinfo = ob_get_contents();
	ob_end_clean();
	$tool_content .= $phpinfo;
	$tool_content .= '</div>';
}
// Display link to go back to index.php
$tool_content .= "<br><center><p><a href=\"index.php\">���������</a></p></center>";

/*****************************************************************************
		DISPLAY HTML
******************************************************************************/
// Call draw function to display the HTML
// $tool_content: the content to display
// 3: display administrator menu
// admin: use tool.css from admin folder
draw($tool_content,3,'admin');
?>
