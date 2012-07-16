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

/**
 * Eclass Course Coordinating Object.
 *
 * This class does not represent a course entity, but a core logic coordinating object
 * responsible for handling course and hierarchy-to-course related tasks.
 */
class course {

    private $ctable;
    private $departmenttable;

    /**
     * Constructor - do not use any arguments for default eclass behaviour (standard db tables).
     *
     * @param string $ctable    - Name of courses table
     * @param string $deptable  - Name of course <-> department lookup table
     */
    public function course($ctable = 'course', $deptable = 'course_department')
    {
        $this->ctable = $ctable;
        $this->departmenttable = $deptable;
    }

    /**
     * Refresh the hierarchy nodes (departments) that a course belongs to. All previous belonging
     * nodes get deleted and then refreshed with the ones given as array arguments.
     *
     * @param int   $id          - Id for a given course
     * @param array $departments - Array containing the node ids that the given course should belong to
     */
    public function refresh($id, $departments)
    {
        if ($departments != null)
        {
            db_query("DELETE FROM $this->departmenttable WHERE course = '$id'");
            foreach (array_unique($departments) as $key => $department)
            {
                db_query("INSERT INTO $this->departmenttable (course, department) VALUES ($id, $department)");
            }
        }
    }

    /**
     * Delete course and all its hierarchy nodes dependencies.
     *
     * @param int $id - The id of the course to delete
     */
    public function delete($id)
    {
        db_query("DELETE FROM $this->departmenttable WHERE course = '$id'");
        db_query("DELETE FROM $this->ctable WHERE id = '$id'");
    }

    /**
     * Get an array with a given course's hierarchy nodes that it belongs to.
     *
     * @param  int   $id  - Id for a given course
     * @return array $ret - Array containing the given course's nodes
     */
    public function getDepartmentIds($id)
    {
        $ret = array();
        $result = db_query("SELECT cd.department AS id
                              FROM $this->ctable c, $this->departmenttable cd
                             WHERE c.id = ". intval($id) ."
                               AND c.id = cd.course");

        while($row = mysql_fetch_assoc($result))
            $ret[] = $row['id'];

        return $ret;
    }
}