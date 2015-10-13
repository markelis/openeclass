<?php

/* ========================================================================
 * Open eClass 
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2015  Greek Universities Network - GUnet
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
 * ======================================================================== 
 */

require_once 'AbstractEventTest.php';
require_once 'modules/game/AssignmentEvent.php';

class AssignmentEventTest extends AbstractEventTest {
    
    public static function setUpBeforeClass() {
        self::$hasResource = true;
        self::$hasThreshold = true;
    }
    
    public function setUp() {
        $this->event = new AssignmentEvent();
        $data = new stdClass();
        $data->courseId = 1;
        $data->uid = 1000;
        $data->activityType = AssignmentEvent::ACTIVITY;
        $data->module = 5;
        $data->resource = 1;
        $this->currentdata = $data;
    }
    
    public function testAssignmentContext() {
        $this->event->emit(AssignmentEvent::NEWGRADE, [$this->currentdata]);
        $context = $this->event->getContext();
        
        $this->assertNotNull($context);
    }
}
