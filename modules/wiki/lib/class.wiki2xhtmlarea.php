<?php

/**=============================================================================
       	GUnet eClass 2.0 
        E-learning and Course Management Program  
================================================================================
       	Copyright(c) 2003-2007  Greek Universities Network - GUnet
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

/**===========================================================================
	class.wiki2xhtmlarea.php
	@last update: 15-05-2007 by Thanos Kyritsis
	@authors list: Thanos Kyritsis <atkyritsis@upnet.gr>
	               
	based on Claroline version 1.7.9 licensed under GPL
	      copyright (c) 2001, 2007 Universite catholique de Louvain (UCL)
	      
	      original file: class.wiki2xhtmlarea Revision: 1.10.2.2
	      
	Claroline authors: Frederic Minne <zefredz@gmail.com>
==============================================================================        
    @Description: 

    @Comments:
 
    @todo: 
==============================================================================
*/
     

    require_once dirname(__FILE__) . "/lib.javascript.php";
    
    /**
     * Wiki2xhtml editor textarea
     */
    class Wiki2xhtmlArea
    {
        var $content;
        var $attributeList;
        
        /**
         * Constructor
         * @param string content of the area
         * @param string name name of the area
         * @param int cols number of cols
         * @param int rows number of rows
         * @param array extraAttributes extra html attributes for the area
         */
        function Wiki2xhtmlArea(
            $content = ''
            , $name = 'wiki_content'
            , $cols = 80
            , $rows = 30
            , $extraAttributes = null )
        {
            $this->setContent( $content );
            
            $attributeList = array();
            $attributeList['name'] = $name;
            $attributeList['id'] = $name;
            $attributeList['cols'] = $cols;
            $attributeList['rows'] = $rows;
            
            $this->attributeList = ( is_array( $extraAttributes ) )
                ? array_merge( $attributeList, $extraAttributes )
                : $attributeList
                ;
        }
        
        /**
         * Set area content
         * @param string content
         */
        function setContent( $content )
        {
            $this->content = $content;
        }
        
        /**
         * Get area content
         * @return string area content
         */
        function getContent()
        {
            return $this->content;
        }
        
        /**
         * Get area wiki syntax toolbar
         * @return string toolbar javascript code
         */
        function getToolbar()
        {
            $toolbar = '';
            

            $toolbar .= '<script type="text/javascript" src="'
                .document_web_path().'/lib/javascript/toolbar.js"></script>'
                . "\n"
                ;
            $toolbar .= "<script type=\"text/javascript\">if (document.getElementById) {
		var tb = new dcToolBar(document.getElementById('".$this->attributeList['id']."'),
		'wiki','".document_web_path()."/toolbar/');

        tb.btStrong('Strong emphasis');
		tb.btEm('Emphasis');
		tb.btIns('Inserted');
		tb.btDel('Deleted');
		tb.btQ('Inline quote');
		tb.btCode('Code');
		tb.addSpace(10);
		tb.btBr('Line break');
		tb.addSpace(10);
		tb.btBquote('Blockquote');
		tb.btPre('Preformated text');
		tb.btList('Unordered list','ul');
		tb.btList('Ordered list','ol');
		tb.addSpace(10);
        tb.btLink('Link','URL?','Language?','fr');
        tb.btImgLink('External image','URL?');
		tb.draw('');
	}
	</script>\n";
            
            return $toolbar;
        }
        
        /**
         * paint (ie echo) area
         */
        function paint()
        {
            echo $this->toHTML();
        }
        
        /**
         * get area html code for string inclusion
         * @return string area html code
         */
        function toHTML()
        {
            $wikiarea = '';

            $attr = '';

            foreach( $this->attributeList as $attribute => $value )
            {
                $attr .= ' ' . $attribute . '="' . $value . '"';
            }

            $wikiarea .= '<textarea'.$attr.'>'.$this->getContent().'</textarea>' . "\n";

            $wikiarea .= $this->getToolbar();

            return $wikiarea;
        }
    }
?>
