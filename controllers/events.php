<?php

/**
 * Events controller.
 *
 * @category   apps
 * @package    events
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2015 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/events/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\events\SSP as SSP;

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Events controller.
 *
 * @category   apps
 * @package    events
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2015 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/events/
 */

class Events extends ClearOS_Controller
{

    /**
     * Events default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->library('events/Events');
        $this->lang->load('events');

        // Load form data
        //---------------
        $data = array();

        $data['flags'] = 7;  // Default all severity levels (1, 2 and 4 bits)
        if ($this->session->userdata('events_flags') !== FALSE)
            $data['flags'] = $this->session->userdata('events_flags');
        $options['breadcrumb_links'] = array(
            'settings' => array('url' => '/app/events/settings', 'tag' => lang('base_settings')),
            'delete' => array('url' => '#', 'tag' => lang('base_delete'), 'class' => 'events-delete')
        );
        $data['events_delete_key'] = rand(0, 10000);
        $this->session->set_userdata(array('events_delete_key' => $data['events_delete_key']));

        $this->page->view_form('events/summary', $data, lang('events_app_name'), $options);
    }

    /**
     * Ajax set flags filter
     *
     * @return JSON
     */

    function flags($filter = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        try {

            $flags = 7;  // Default all severity levels (1, 2 and 4 bits)
            if ($filter != NULL)
                $flags = $filter;
            $this->session->set_userdata('events_flags', $flags);

            echo json_encode(array('code' => 0));
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }

    /**
     * Acknowledge all events
     *
     * @return void
     */

    function acknowledge()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load dependencies
        //------------------

        $this->load->library('events/Events');

        $this->events->acknowledge();

        redirect('events');
    }

    /**
     * Delete all records
     *
     * @return void
     */

    function delete($confirm_key = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load dependencies
        //------------------

        $this->load->library('events/Events');

        if ($confirm_key != NULL && $confirm_key == $this->session->userdata('events_delete_key')) {
            $this->events->delete('all');
            redirect('events');
            return;
        }
        redirect('events');
    }

    /**
     * Ajax events info
     *
     * @return JSON
     */

    function get_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        try {
            $this->load->library('events/Events');
            $this->load->library('events/SSP');
            $this->load->library('date/Time');

            date_default_timezone_set($this->time->get_time_zone());

            $sql_details = array(
                'path' => '/var/lib/csplugin-sysmon/sysmon.db'
            );
            $table = 'alerts';
            $primaryKey = '';
            $columns = array(
                array( 'db' => 'flags', 'dt' => 0,
                    'formatter' => function( $d, $row ) {
                        if ((int)$d & 4)
                            return icon('critical', array('class' => 'theme-text-alert'));
                        else if ((int)$d & 2)
                            return icon('warning', array('class' => 'theme-text-warning'));
                        else if ((int)$d & 1)
                            return icon('info', array('class' => 'theme-text-ok'));
                        else
                            return icon('unknown');
                    }
                ),
                array( 'db' => 'desc', 'dt' => 1 ),
                array( 'db' => 'stamp',  'dt' => 2,
                    'formatter' => function( $d, $row ) {
                        return "<span style='white-space: nowrap;'>" . date('Y-m-d H:i:s', strftime($d)) . "</span>";
                    }
                )
            );

            parse_str($_SERVER['QUERY_STRING'], $get_params);

            if ($this->session->userdata('events_flags') !== FALSE)
                $get_params['flags'] = $this->session->userdata('events_flags');
            else
                $get_params['flags'] = 7;
                
            echo json_encode(
                SSP::simple($get_params, $sql_details, $table, $primaryKey, $columns)
            );

        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }
}
