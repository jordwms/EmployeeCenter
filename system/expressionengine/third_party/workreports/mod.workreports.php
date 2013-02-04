<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Workreports {
    protected $return_data = '';

    function __construct() {
        $this->EE =& get_instance();
        $this->EE->load->library('axapta/axapta');
        $this->EE->load->library('mysql');
    }

    /*
     *  Template tags to return a simple dashboard message with current # of available work reports
     *  Used in the dashboard/index.html template
     */
    function dashboard() {
        $message = '';
        if( $this->EE->axapta->axapta_connection() ) {
            if( $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') )) ) {
            //if( $employee = $this->EE->axapta->employee->get_remote(array( 'email' => 'bert.weber@applusrtd.com' )) ) {

                $employee = $employee[0];

                // sync the 2 databases.
                //$this->sync($employee['id']);

                if( count($employee['groups']) > 0 ) {
                    $message = '';
                    if( array_key_exists('WA TECH', $employee['groups']) ){
                        $message .= 'You have '.$this->count($employee, 'WA TECH').' Work Reports assigned to you'.'<br>';
                        //$message = $this->EE->lang->line('');
                    }
                    if( array_key_exists('WA DISP', $employee['groups']) ){
                        $message .= 'You have '.$this->count($employee, 'WA DISP').' Work Reports awaiting DISPATCHER approval'.'<br>';
                    }
                    if( array_key_exists('WA ADMIN', $employee['groups']) ){
                        $message .= 'You have '.$this->count($employee, 'WA ADMIN').' Work Reports awaiting ADMIN approval'.'<br>';
                    }
                } else {
                    //$message = 'Please Contact HRM Department for Authorization';
                    $message = $this->EE->lang->line('unauthorized');
                }
            } else {
                //$message = 'Invalid Employee Information Returned';
                $message = $this->EE->lang->line('invalid_employee');
                $message = lang('invalid_employee');
            }
        } else {
            //$message = 'No connection to Axapta';
            $message = $this->EE->lang->line('no_connection');
        }

        return $message;
    }

    /*
     *  Returns a count of availiable workreports, given an employee id and an axapta group
     */
    function count($employee, $group_id) {
        $this->EE->db->from('wr_reports');

        switch ($group_id) {
            case 'WA TECH':
                $this->EE->db->join('wr_resources','wr_resources.report_id = wr_reports.id' );
                $this->EE->db->where('resource_id', $employee['id']);
                $this->EE->db->where('status <=', 2);
                break;

            case 'WA DISP':
                $this->EE->db->where('sales_responsible', $employee['id']);
                $this->EE->db->where('status', 3);
                break;

            case 'WA ADMIN':
                $this->EE->db->where_in('company_id', $employee['groups']['WA ADMIN']);
                $this->EE->db->where('status', 4);

                //$this->EE->db->where('department_id', $employee['department_id']);
                break;
        }

        return $this->EE->db->count_all_results();
    }

    /*
     *  Template tags to return all workreports marked as TEMPLATE
     *  Used primarily in workreports/index.html for the "new work report" button
     */
    function templates() {
        $tagdata = $this->EE->TMPL->tagdata;

        $this->EE->db->from('wr_reports');
        $this->EE->db->where('export_reason', 'TEMPLATE');
        //$this->EE->db->where('execution_datetime', strtotime('01-01-2012'));
        $this->EE->db->order_by('customer_name', 'asc');

        $template_list = $this->EE->db->get()->result_array();

        foreach ($template_list as &$wr) {
            $wr['project_link'] = str_replace('/', '-', $wr['project_id'] );
        }

        $this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $template_list);

        return $this->return_data;
    }


    /*
     *  Template Tags for the overview list
     *  Used primarily in the workreports index.html template
     */
    function wrList() {
        if ( $employee = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) ) ){
            $tagdata = $this->EE->TMPL->tagdata;

            $this->EE->db->select(
                'project_id,
                sales_id,
                crew_leader_id,
                sales_responsible,

                execution_datetime,
                wr_status.status,

                company_id,
                rtd_reference,
                object_description,
                order_description,

                work_location_id,
                work_location_name,
                work_location_address,

                customer_id,
                customer_name,

                customer_contact_name,
                customer_contact_email,
                customer_contact_phone,
                customer_contact_mobile,

                customer_reference'
            );

            $this->EE->db->from('wr_reports');
            $this->EE->db->join('wr_resources','wr_resources.report_id = wr_reports.id' );
            $this->EE->db->join('wr_status', 'wr_status.id = wr_reports.status');
            $this->EE->db->where('resource_id', $employee[0]['id']);
            $this->EE->db->where('wr_reports.status <', 4);
            $this->EE->db->order_by('execution_datetime', 'desc');
            // $this->EE->db->where('status', 0);

            $dispatch_list = $this->EE->db->get()->result_array();

            foreach ($dispatch_list as &$wr) {
                $wr['project_link'] = str_replace('/', '-', $wr['project_id'] );
                $wr['execution_datetime'] = $this->EE->localize->set_human_time( $wr['execution_datetime'] ); // Later this should be 'DD MONTH YYYY HH:MM'
            }

            $this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $dispatch_list);

            return $this->return_data;
        }
    }

    /*
     * Fetches data for a single work report and processes the template.
     */
    function wrDetails($project_id = NULL) {
        $employee = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) );
        $employee = $employee[0];

        // If this was called for details.html
        if($project_id == NULL){ 
            $project_id = str_replace('-', '/', $this->EE->TMPL->fetch_param('projid') );
            $tagdata = $this->EE->TMPL->tagdata;
        }
        // This was called for print.html
        else {
            $this->EE->load->library('Template', NULL, 'TMPL');
            $tagdata = $this->EE->TMPL->fetch_template('workreports', 'print', FALSE, $this->EE->config->item('site_id') );
        }

        $submit_uri = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Workreports', 'submit');

        //fetch base report data
        $this->EE->db->select(
            'id,
            project_id,
            contract_id,
            sales_id,
            crew_leader_id,
            sales_responsible,

            execution_datetime,

            cost_center_name,
            cost_center_address,
            cost_center_phone,
            cost_center_fax,
            cost_center_email,

            company_id,
            rtd_reference,
            object_description,
            order_description,

            work_location_id,
            work_location_name,
            work_location_address,

            team_contact_id,
            team_contact_name,
            team_contact_address,
            team_contact_phone,
            team_contact_fax,
            team_contact_email,

            customer_id,
            customer_name,
            customer_address,
            customer_phone,
            customer_fax,
            customer_email,
            customer_reference,

            customer_contact_id,
            customer_contact_name,
            customer_contact_email,
            customer_contact_phone,
            customer_contact_mobile,

            research_procedure_id,
            research_procedure_description,
            research_procedure_pdf,

            review_procedure_id,
            review_procedure_description,
            review_procedure_pdf,

            export_reason,
            remarks,
            customer_signature,
            status'
        );

        $this->EE->db->from('wr_reports');
        $this->EE->db->where('project_id', $project_id);
        // $this->EE->db->where('crew_leader_id', $employee);

        $data[0] = $this->EE->db->get()->row_array();
        
        $data[0]['materials'] = $this->EE->db->get_where('wr_materials', array('report_id' => $data[0]['id']) )->result_array();
        $data[0]['sales_items'] = $this->EE->db->get_where('wr_items', array('report_id' => $data[0]['id']) )->result_array();

        if ( $data[0]['export_reason'] == 'TEMPLATE' ){
            $employee = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) );
            $employee = $employee[0];

            $data[0]['resources'][0]['resource_id'] = $employee['id'];
            $data[0]['resources'][0]['name'] = $employee['name'];
        }else {
            $data[0]['resources'] = $this->EE->db->get_where('wr_resources', array('report_id' => $data[0]['id']) )->result_array();
        }

        $form_open = array(
            'action'        => $submit_uri,
            'name'          => 'workReport',
            'id'            => $this->EE->TMPL->form_id,
            'class'         => $this->EE->TMPL->form_class,
            'hidden_fields' => array(
                'project_id'            => $data[0]['project_id'],
                'id'                    => $data[0]['crew_leader_id'],
                'execution_datetime'    => $data[0]['execution_datetime'],
                'contract_id'           => $data[0]['contract_id'],
                'company_id'            => $data[0]['company_id'],
                'customer_id'           => $data[0]['customer_id'],
                'customer_contract_id'  => $data[0]['customer_id'],
                'rtd_reference'         => $data[0]['rtd_reference'],
                'work_location_name'    => $data[0]['work_location_name'],
                'work_location_address' => $data[0]['work_location_address'],
                'customer_signature'    => $data[0]['customer_signature']
            ),
            'secure'        => TRUE,
            'onsubmit'      => ''
        );
        $data[0]['form_open'] = $this->EE->functions->form_declaration($form_open);
        $data[0]['form_close'] = '</form>';

        /*
         *  Logic to set the appropriate actions based on user groups and current status of the work report.
         */
        $data[0]['actions'] = ''; //Initilize actions string
        if( (array_key_exists('WA DISP', $employee['groups']) && in_array($data[0]['company_id'], $employee['groups']['WA DISP']))
         || (array_key_exists('WA ADMIN', $employee['groups']) && in_array($data[0]['company_id'], $employee['groups']['WA ADMIN'])) ){
            // Current employee is DISP or ADMIN permissions for the company_id of the work report
            if( $data[0]['status'] < 5 ){
                // Work report has not been ADMIN approved, Allow Save
                // Save Button
                $data[0]['actions'] .= '<input type="submit" name="save" class="btn" value="Save" />';
            }

            if( $data[0]['status'] < 3 ){
                // Workreport has not been completed by a TECH yet, allow submit (renamed to Submit and Approve for UI)
                // Submit + Approve button
                $data[0]['actions'].= '<input type="submit" name="submit" class="btn" value="Submit and Approve" />';
            }

            if( $data[0]['status'] > 2 && $data[0]['status'] < 5 ){
                // Workreport has been completed by a TECH, but has not passed ADMIN and DISP Approval, Allow only Approval
                // Approve button
                $data[0]['actions'].= '<input type="submit" name="approve" class="btn" value="Approve" />';
            }

            if( $data[0]['status'] > 2 && $data[0]['status'] < 5){
                // Workreport is completed by a TECH, but has not recieved ADMIN approval, Allow Reject
                // Reject Button
                $data[0]['actions'].= '<input type="submit" name="reject" class="btn" value="Reject" />';
            }
        } else {
            // Current Employee does NOT have ADMIN or DISP permissions for the company_id of the work report
            // Assuming they have TECH, which isn't ideal...
            if($data[0]['status'] < 3){
                // Workreport has NOT been completed by a TECH
                // Submit button, Save button
                $data[0]['actions'].= '<input type="submit" name="submit" class="btn" value="Submit" />';
                $data[0]['actions'].= '<input type="submit" name="save" class="btn" value="Save" />';
            }
            if($data[0]['status'] > 2) {
                // Workreport has been completed by a TECH
                // No buttons for you!
            }
        }
        //Print button is always available
        $data[0]['actions'] .= '<input type="submit" name="print" class="btn" value="Print">';

        $this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $data );

        return $this->return_data;
    }

    /*
     *  Handles all work report form submission possibilities
     */
    function submit() {
        /*
         *  Get the employee object for the currently logged in user.  If invalid object returned, fail out of this process
         *  @TODO redirect to login screen upon failing to get valid user object
         */
        if ( $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') )) ) {
            $employee = $employee[0];

            $send_mail = FALSE; // Flag indicating whether the PDF should be emailed.
            //$success = array();
            $message = '';

            // Get the work report already in the cache (MySQL)
            $this->EE->db->where('project_id', $this->EE->input->post('project_id') );
            $existing_wr = $this->EE->db->get('wr_reports')->row_array();


            /*** Status Decision Tree **************************************************************
             *
             *  Technitions with Dispatcher and/or Admin are able to "short cut" a seperate approval step
             *  Also, status of approval will be maintained if work report is saved
             *  while always updating status to inprogress if not yet completed by tech
             */

            if ($existing_wr['status'] < 2 && $this->EE->input->post('save') ) {
                $status = 2;
            } else {
                $status = $existing_wr['status'];
            }

            if ( $this->EE->input->post('submit') || $this->EE->input->post('approve') ) {
                if(array_key_exists('WA TECH', $employee['groups']) && in_array($this->EE->input->post('company_id'), $employee['groups']['WA TECH']) && $existing_wr['status'] < 3) {
                    $status = 3;
                }
                if(array_key_exists('WA DISP', $employee['groups']) && in_array($this->EE->input->post('company_id'), $employee['groups']['WA DISP']) && $existing_wr['status'] < 4) {
                    $status = 4;
                    $send_mail = TRUE;
                }
                if(array_key_exists('WA ADMIN', $employee['groups']) && in_array($this->EE->input->post('company_id'), $employee['groups']['WA ADMIN']) && $existing_wr['status'] < 5) {
                    $status = 5;
                }
            } elseif ( $this->EE->input->post('reject') ) { // if "Reject" was clicked
                $status = 0;
            }

            /*** Work Report Form Data ************************************************************************
             *
             *  Begin by building the basic data array that will be used regarless if update or new from template
             *  These are the fields that are always updatable
             */
            $data = array(
                'submitter_id'          => $employee['id'],
                'execution_datetime'    => strtotime($this->EE->input->post('execution_date')),
                'submission_datetime'   => time(),
                'status'                => $status,

                'rtd_reference'         => $this->EE->input->post('rtd_reference'),

                'customer_reference'    => $this->EE->input->post('customer_reference'),

                'object_description'    => $this->EE->input->post('object_description'),
                'order_description'     => $this->EE->input->post('order_description'),
                'remarks'               => $this->EE->input->post('remarks'),
                'customer_signature'     => $this->EE->input->post('customer_signature')
            );

            /*
             *  These are the fields that could be updated, but if they are not, we need to preserve the existing data in MySQL
             */
            // CONTACT PERSON
            if( $this->EE->input->post('customer_contact_id') ){
                $data = array_merge($data, array(
                    'customer_contact_id'     => $this->EE->input->post('customer_contact_id'),
                    'customer_contact_name'   => $this->EE->input->post('customer_contact_name'),
                    'customer_contact_phone'  => $this->EE->input->post('customer_contact_phone'),
                    'customer_contact_mobile' => $this->EE->input->post('customer_contact_cell'),
                    'customer_contact_email'  => $this->EE->input->post('customer_contact_email')
                ));
            }
            // WORK LOCATION
            if( $this->EE->input->post('work_location_id') ){
                $data = array_merge($data, array(
                    'work_location_id'      => $this->EE->input->post('work_location_id'),
                    'work_location_name'      => $this->EE->input->post('work_location_name'),
                    'work_location_address' => $this->EE->input->post('work_location_address')
                ));
            }

            /*** Form Actions / Data Processing *****************************************************************
             *
             *  Now that we have the data from the form, let's see if we're gonna make a new report based on a template
             *  OR update the data in an existing report
             */
            if($existing_wr['export_reason'] == 'TEMPLATE'){
                /*
                 *  NEW FROM TEMPLATE
                 *  Begin by building a new project_id (>= 8 char long)
                 */

                //echo '<pre>TEMPLATE:<br>'; print_r($data); echo '</pre>';

                /*
                 *  project_id parts
                 *  [0] => order / [1] => work_order / [2] => work_report
                 */
                $project_id_parts = explode( '/', $existing_wr['project_id'] );

                /*
                 *  Old work_report_id algorithm
                 *  Builds Last 4 of employee_id + day # of year (DDD) + sequence #
                 *  ex: 02262911
                 */

                /*
                 *  get sequence # -> number of reports since 12 A.M. of current day submitting with same customer/order
                 */
                // $this->EE->db->from( 'wr_reports' );
                // $this->EE->db->where( 'submitter_id', $employee['id'] );
                // $this->EE->db->like( 'project_id', $project_id_parts[0].'/'.$project_id_parts[1], 'after' );
                // $sequence_id = $this->EE->db->count_all_results()+1;

                // $employee_id_parts = explode('.', $employee['id']);// $employee_id[2] will be section 1 of $project_id
                // $day_of_year = str_pad( date('z'), 3, '0', STR_PAD_LEFT );
                // $project_id[2] = $employee_id[2].$date['yday'].$sequence_id;

                /*
                 *  New/better algorithm for unique work_report_id
                 *  Take crc32 hash of time and employee id and
                 *  replace 3rd segment of project_id w/ new work_report_id
                 */
                $project_id_parts[2] = crc32( $employee['id'].time() );
                $project_id = implode('/', $project_id_parts);

                /*
                 *  Ammend $data with some additional stuff we need only if it's a template
                 *  First Merge $exsisting_wr with $data from form
                 *  Then ammend project_id and any other values necessary only for templates
                 */
                $data = array_merge($existing_wr, $data, array(
                    'project_id'    => $project_id, //new project id
                    'export_reason' => NULL         //make sure this isn't treated as a template in the future
                ));

                $data['id'] = NULL;

                $this->EE->db->insert('wr_reports', $data);
                $success['wr_reports'] = $this->EE->db->affected_rows();

                $report_id = $this->EE->db->insert_id();

                $message .= 'Sucessfully created new workreport from template'.NL;
            } else {
                /*
                 *  Update Existing Work Report
                 *  First set $report_id to the existing report
                 */

                //echo '<pre>UPDATE:<br>'; print_r($data); echo '</pre>';

                $report_id = $existing_wr['id'];

                $this->EE->db->where('id', $report_id );
                $this->EE->db->update('wr_reports', $data);
                $success['wr_reports'] = $this->EE->db->affected_rows();

                $message .= 'Sucessfully updated workreport'.NL;
            }

            /*** Resources ***************************************************************
             *
             *  Update existing or insert new wr_resources entries
             *  Because we test if the resource already exists for the $report_id
             *  there is no need to treat template differently than update
             */
            foreach($this->EE->input->post('resources') as $resource) {
                /*
                 *  First check if the quantity was actually updated to save some processing
                 *  Disabled this due to it preventing 0 or null from properly updating to the database
                 *  Example: setting a resource's total quantity to 0 or empty
                 */
                //if( !is_null($resource['qty']) ){

                    //echo '<pre>RESOURCE QTY CHANGED:<br>'; print_r($resource); echo '</pre>';

                    /*
                     *  Then check if resource already exists for $report_id
                     */
                    $this->EE->db->select('resource_id');
                    $this->EE->db->from('wr_resources');
                    $this->EE->db->where('report_id', $report_id);
                    $this->EE->db->where('resource_id', $resource['resource_id']);

                    $count = $this->EE->db->count_all_results();

                    if( $count == 1 ){
                        /*
                         *  The Resource DOES exist already, so just update the qty
                         */
                        $data = array(
                            'qty' => $resource['qty']
                        );
                        $this->EE->db->where('report_id', $report_id );
                        $this->EE->db->where('resource_id', $resource['resource_id']);
                        $this->EE->db->update('wr_resources', $data);
                    } elseif( $count == 0 ) {
                        /*
                         *  The Resource DOES NOT exist already, so insert new
                         *  This should always be triggered if from TEMPLATE
                         */
                        $data = array(
                            'report_id'     => $report_id,
                            'name'          => $resource['name'],
                            'resource_id'   => $resource['resource_id'],
                            'qty'           => $resource['qty']
                        );
                        $this->EE->db->insert('wr_resources', $data);
                    } else {
                        /*
                         *  There must be multiple resources existing with the same resource_id and thats' bad :(
                         *  Lets not do anything to the database just to be safe
                         @TODO Error trap this
                         */
                    }
                //}
            }

            /*** Sales Items ***************************************************************
             *
             *  Basically same process as resources
             */
            foreach($this->EE->input->post('sales_items') as $item) {
                //if( $item['qty'] ) {

                    //echo '<pre>SALES ITEM QTY CHANGED:<br>'; print_r($item); echo '</pre>';

                    $this->EE->db->select('item_id, dimension_id');
                    $this->EE->db->from('wr_items');
                    $this->EE->db->where('report_id', $report_id);
                    $this->EE->db->where('item_id', $item['item_id']);
                    $this->EE->db->where('dimension_id', $item['dimension_id']);

                    $count = $this->EE->db->count_all_results();

                    if($count == 1) {
                        //UPDATE
                        $data = array(
                            'qty' => $item['qty']
                        );
                        $this->EE->db->where('report_id', $report_id );
                        $this->EE->db->where('item_id', $item['item_id']);
                        $this->EE->db->where('dimension_id', $item['dimension_id']);
                        $this->EE->db->update('wr_items', $data);
                    } elseif( $count == 0 ) {
                        //INSERT
                        $data = array(
                            'report_id'     => $report_id,
                            'dimension_id'  => $item['dimension_id'],
                            'item_id'       => $item['item_id'],
                            'name'          => $item['name'],
                            'qty'           => $item['qty'],
                            'unit'          => $item['unit']
                        );
                        $this->EE->db->insert('wr_items', $data);
                    } else {
                        /*
                         *  There must be multiple resources existing with the same resource_id and thats' bad :(
                         *  Lets not do anything to the database just to be safe
                         @TODO Error trap this
                         */
                    }
                //}
            }

            /*** Materials/Consumables ********************************************************
             *
             *  Basically same process as resources except not every work report has materials
             */
            if( is_array($this->EE->input->post('materials')) ) {
                foreach($this->EE->input->post('materials') as $material) {
                    //if( $material['qty'] ){

                        //echo '<pre>MATERIAL QTY CHANGED:<br>'; print_r($material); echo '</pre>';
                        $this->EE->db->select('item_id, dimension_id');
                        $this->EE->db->from('wr_materials');
                        $this->EE->db->where('report_id', $report_id);
                        $this->EE->db->where('item_id', $material['item_id']);
                        $this->EE->db->where('dimension_id', $material['dimension_id'] );

                        $count = $this->EE->db->count_all_results();

                        if($count == 1) {
                            //UPDATE
                            $data = array(
                                'qty' => $material['qty']
                            );
                            $this->EE->db->where('report_id', $report_id);
                            $this->EE->db->where('item_id', $material['item_id']);
                            $this->EE->db->where('dimension_id', $material['dimension_id'] );
                            $this->EE->db->update('wr_materials', $data);
                        } elseif( $count == 0 ) {
                            //INSERT
                            $data = array(
                                'report_id'     => $report_id,
                                'dimension_id'  => $material['dimension_id'],
                                'item_id'       => $material['item_id'],
                                'name'          => $material['name'],
                                'qty'           => $material['qty'],
                                'unit'          => $material['unit']
                            );
                            $this->EE->db->insert('wr_items', $data);
                        } else {
                            /*
                             *  There must be multiple resources existing with the same resource_id and thats' bad :(
                             *  Lets not do anything to the database just to be safe
                             @TODO Error trap this
                             */
                        }
                    //}
                }
            }

            //Make sure we have the correct project_id
            $project_id = (isset($project_id)) ? $project_id : $existing_wr['project_id'];

            // If submit() was called by the "print" button redirect to print.html (changes are saved)
            if( $this->EE->input->post('print') ){
                echo $this->wrDetails($existing_wr['project_id']);
                return;
            }

            /*
             *  We're almost done.
             *  First, check if the work report has admin approval status
             *  If so, we need to export XML, set ax_status/approval == 1 and send a PDF if the customer contact has an email address.
             */
            if($status == 5) {
                $this->EE->mysql->create_xml($report_id);

                $this->EE->axapta->work_report->set_approval(array(
                    'status' => 1,
                    'project_id' => $project_id,
                    'employee_id' => $employee['id'],
                    'company_id' => $existing_wr['company_id']
                ));

                $message .= 'XML successfully generated.'.NL;
            }

            // Send Email (triggered by WA DISP approval)
            if($send_mail) { 
                // create a PDF from the print template
                $pdf = $this->wr_to_pdf( $project_id );

                // Send email with PDF attachment
                # TODO: Check if customer_contact_email is valid before sending
                $from = 'Robert.McCann@applusrtd.com';
                $to = '';
                if (NSM_ENV == 'production') {
                    if(! is_null($data[0]['customer_contact_email']) ) {
                        $to.= $data[0]['customer_contact_email'];
                    }
                    if(! is_null($data[0]['team_contact_email']) ) {
                        $to.= $data[0]['team_contact_email'];
                    }
                } else {
                    $to = 'Robert.McCann@applusrtd.com'; // , Bert.Weber@applusrtd.com';
                }

                if( $this->send_mail($from, $to, $pdf, $project_id) ) {
                    // Message successfully sent! Delete local file from /tmp
                    // $pdf->delete_file($file);
                    $message .= 'Email sucessfully sent.'.NL; 
                } else {
                    $message .= 'Error sending email.'.NL;
                }
            }

            $this->EE->output->show_message(array(
                'title'   => 'Information Accepted',
                'heading' => 'Thank you',
                'content' => nl2br($message),
                'link'    => array($this->EE->functions->form_backtrack('0'), 'Return to Work Reports')
            ));

            return TRUE;
        }
    }

    /*
     *  Pulls all new work reports from axapta and stores them in MySQL
     *  Done primarily for 
     */
    function sync($employee_id = NULL) {
        if( is_null($employee_id) ){
            $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') ));
            $employee = $employee[0];

            $employee_id = $employee['id'];
        }

        if( !is_null($employee_id) ) {
            // Get List of dispatched work reports
            $dispatch_list = $this->EE->axapta->dispatch_list->get_remote(array(
                'employee_id' => $employee_id,
                'invoiced_status' => 0
            ));

            $templates = $this->EE->axapta->work_report->get_remote( array(
                'export_reason' => 'TEMPLATE'
                ,'execution_date' => '2012-01-01'
            ) );

            $all_reports = array_merge($dispatch_list, $templates);

            //loop over dispatch list and sync the work report to mysql
            foreach ($all_reports as $dispatch_item) {
                $this->EE->db->select('project_id');
                $this->EE->db->from('wr_reports');
                $this->EE->db->where('project_id', $dispatch_item['project_id']);

                //$existing_wr = $this->EE->db->result_array();

                //if($this->EE->db->count_all_results() == 0 || $existing_wr['modified_datetime'] < $dispatch_item['modified_datetime']) {
                if($this->EE->db->count_all_results() == 0) {

                    //get workreport from axapta and add to mysql
                    $work_report = $this->EE->axapta->work_report->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );

                    // Insert each entry to the MySQL database
                    $data = array(
                        'project_id'                => $work_report[0]['project_id'],
                        'sales_id'                  => $work_report[0]['sales_id'],
                        //'submitter_id'            => $employee_id,

                        'customer_id'               => $work_report[0]['customer_id'],
                        'customer_reference'        => $work_report[0]['customer_reference'],

                        'customer_contact_id'       => $work_report[0]['customer_contact_person_id'],

                        'company_id'                => $work_report[0]['company_id'],
                        'department_id'             => $work_report[0]['department_id'],
                        'cost_center_id'            => $work_report[0]['cost_center_id'],
                        'technique_id'              => $work_report[0]['technique_id'],

                        'contract_id'               => $work_report[0]['contract_id'],

                        // 'deadline_datetime'      => $work_report[0]['deadline_datetime'],
                        'rtd_reference'             => $work_report[0]['rtd_reference'],
                        'sales_responsible'         => $work_report[0]['sales_responsible'],
                        'crew_leader_id'            => $work_report[0]['crew_leader_id'],

                        'team_contact_id'           => $work_report[0]['team_contact_person_id'],

                        'work_location_id'          => $work_report[0]['work_location_id'],
                        'work_location_name'        => $work_report[0]['work_location_name'],
                        'work_location_address'     => $work_report[0]['work_location_address'],

                        'object_description'        => $work_report[0]['object_description'],
                        'order_description'         => $work_report[0]['order_description'],

                        'research_procedure_id'     => $work_report[0]['research_procedure_id'],
                        'review_procedure_id'       => $work_report[0]['review_procedure_id'],

                        'status'                    => 1,
                        'export_reason'             => $work_report[0]['export_reason'],

                        'created_by'                => $work_report[0]['created_by'],
                        'modified_by'               => $work_report[0]['modified_by'],
                        'modified_datetime'         => $work_report[0]['modified_datetime'],
                        'created_datetime'          => $work_report[0]['created_datetime'],
                        'execution_datetime'        => $work_report[0]['execution_datetime']
                        );

                    if( $cost_center = $this->EE->axapta->cost_center->get_remote( array( 'id' => $work_report[0]['cost_center_id'], 'company_id' => $work_report[0]['company_id'] ) )){
                        $data = array_merge($data, array(
                            'cost_center_name'          => $cost_center[0]['name'],
                            'cost_center_address'       => $cost_center[0]['address'],
                            'cost_center_email'         => $cost_center[0]['email'],
                            'cost_center_phone'         => $cost_center[0]['phone'],
                            'cost_center_fax'           => $cost_center[0]['fax']
                        ));
                    }

                    if( $customer = $this->EE->axapta->customer->get_remote(array( 'id' => $work_report[0]['customer_id'] ) )){
                        $data = array_merge($data, array(
                            'customer_name'             => $customer[0]['name'],
                            'customer_address'          => $customer[0]['address'],
                            'customer_phone'            => $customer[0]['phone'],
                            //'customer_email'          => $customer[0]['email'],
                            'customer_fax'              => $customer[0]['fax']
                        ));
                    }

                    if( $customer_contact = $this->EE->axapta->contact_person->get_remote( array( 'id' => $work_report[0]['customer_contact_person_id'] ) )){
                        $data = array_merge($data, array(
                            'customer_contact_name'     => $customer_contact[0]['name'],
                            'customer_contact_email'    => $customer_contact[0]['email'],
                            'customer_contact_phone'    => $customer_contact[0]['phone'],
                            'customer_contact_mobile'   => $customer_contact[0]['cell_phone']
                        ));
                    }

                    if( $team_contact = $this->EE->axapta->employee->get_remote( array( 'id' => $work_report[0]['sales_responsible'] ) )){
                        $data = array_merge($data, array(
                            'team_contact_name'         => $team_contact[0]['name'],
                            //'team_contact_address'        => $team_contact[0]['address'],
                            'team_contact_email'        => $team_contact[0]['email'],
                            'team_contact_phone'        => $team_contact[0]['phone'],
                            'team_contact_fax'          => $team_contact[0]['fax'],
                            //'team_contact_mobile'         => $team_contact[0]['cell_phone']
                        ));
                    }

                    if( $research_procedure = $this->EE->axapta->research_procedure->get_remote(array( 'id' => $work_report[0]['research_procedure_id'] ) )){
                        $data = array_merge($data, array(
                            'research_procedure_description' => $research_procedure[0]['description'],
                            'research_procedure_pdf' => $research_procedure[0]['pdf_link']
                        ));
                    }

                    if( $review_procedure = $this->EE->axapta->review_procedure->get_remote(array( 'id' => $work_report[0]['review_procedure_id'] ) )){
                        $data = array_merge($data, array(
                            'review_procedure_description' => $review_procedure[0]['description'],
                            'review_procedure_pdf' => $review_procedure[0]['pdf_link']
                        ));
                    }

                    $this->EE->db->insert('wr_reports', $data);
                    $report_id = $this->EE->db->insert_id();

                    if( !$this->EE->db->affected_rows() == count($work_report) ){
                        // WE HAD A PROBLEM, DELETED EVERYTHING AND SHOW ERROR
                    }

                    //get resources from axapta and add to mysql
                    $resources = $this->EE->axapta->project_resources->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );

                    foreach($resources as $resource) {
                        $data = array(
                            'resource_id'   => $resource['id'],
                            'name'          => $resource['name'],
                            'report_id'     => $report_id
                            );
                        $this->EE->db->insert('wr_resources', $data);
                    }

                    if( !$this->EE->db->affected_rows() == count($resources) ){
                        // WE HAD A PROBLEM, DELETED EVERYTHING AND SHOW ERROR
                    }

                    //get sales items from axapta and add to mysql
                    $sales_items = $this->EE->axapta->sales_items->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );

                    foreach($sales_items as $item) {
                        $data = array(
                            'item_id'       => $item['id'],
                            'name'          => $item['name'],
                            'unit'          => $item['unit'],
                            'dimension_id'  => $item['dimension_id'],
                            'report_id'     => $report_id
                            );

                        $this->EE->db->insert('wr_items', $data);
                    }

                    if( !$this->EE->db->affected_rows() == count($sales_items) ){
                        // WE HAD A PROBLEM, DELETED EVERYTHING AND SHOW ERROR
                    }

                    //get materials from axapta and add to mysql
                    $materials = $this->EE->axapta->materials->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );

                    foreach($materials as $mat) {
                        $data = array(
                            'item_id'       => $mat['id'],
                            'name'          => $mat['name'],
                            'unit'          => $mat['unit'],
                            // 'qty'            => (int)$mat['amount'],
                            'dimension_id'  => $mat['dimension_id'],
                            'report_id'     => $report_id
                            );

                        $this->EE->db->insert('wr_materials', $data);
                    }

                    if( !$this->EE->db->affected_rows() == count($materials) ){
                        // WE HAD A PROBLEM, DELETED EVERYTHING AND SHOW ERROR
                    }
                }
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }


    /*
     *  Creates a PDF document from the workreports print template.
     *  Returns the full path to the PDF File
     *  Hardcoded to a tmp/ folder in the base directory of the site
     */
    function wr_to_pdf($project_id=NULL) {
        $this->EE->load->library('Template', NULL, 'TMPL');
        //$this->EE->load->library('WKPDF.php');
        include_once(__DIR__.'/libraries/WKPDF.php');
        
        $html = $this->wrDetails($project_id); // Get HTML

        // Create PDF
        $file_name = str_replace('/', '-', $project_id );

        $pdf = new WKPDF();
        $pdf->set_title('WorkReport');
        $pdf->set_html($html);
        $pdf->render();
        $pdf->output(WKPDF::$PDF_SAVEFILE, $file_name.'.pdf');

        $file = FCPATH.'tmp/'.$file_name.'.pdf';

        return $file;
    }

    /*
     *  Sends an email with attachment(s)
     *  Returns TRUE if sending was sucessful, FALSE if not
     *
     *  $from = senders email address
     *  $to   = recipients email address
     *  $file = path to attachment @TODO accept array for multiple attachments
     *  $project_id = project id @TODO replace with "body" message
     *  @TODO add $subject input
     */
    function send_mail($from=NULL, $to=NULL, $file=NULL, $project_id=NULL ) {
        // email fields: to, from, subject, and so on
        $subject = 'Applus RTD Work Report '.$project_id;
        $body = 'Attached is a work report from ApplusRTD. Please keep this for your records.';

        // multipart boundary
        $mime_boundary = 'Multipart_Boundary_x{'.md5(time()).'}x';

        // headers with multipart boundary definition
        $headers = "From: $from".NL; // root@localhost
        $headers .= 'MIME-Version: 1.0'.NL;
        $headers .= 'Content-Type: multipart/mixed; boundary="'.$mime_boundary.'"';

        // Main Body Boundary
        $message = "--$mime_boundary".NL;
        $message .= 'Content-Type: text/plain; charset="utf-8"'.NL;
        $message .= 'Content-Transfer-Encoding: 8bit'.NL.NL;
        // Actual Message Body
        $message .= $body;
        $message .= NL.NL;

        /*** Repeat this section for multiple attachments ***/
        // open and prepare attachments
        $fp =         @fopen($file,"rb");
        $attachment = @fread($fp,filesize($file));
                      @fclose($fp);
        // encode attachment
        $attachment = chunk_split(base64_encode($attachment));
        
        // Attachment Boundary
        $message .= "--$mime_boundary".NL;
        $message .= 'Content-Type: application/octet-stream; name='.basename($file).NL;
        $message .= 'Content-Description: '.basename($file).NL;
        $message .= 'Content-Disposition: attachment; filename='.basename($file).'; size='.filesize($file).';'.NL;
        $message .= 'Content-Transfer-Encoding: base64'.NL.NL;
        // encoded attachment
        $message .= $attachment;
        $message .= NL.NL;
        /*** End attachment section  ***/

        // End Boundary
        $message .= "--$mime_boundary--";

        $returnpath = "-f" . $from;

        return mail($to, $subject, $message, $headers, $returnpath);
    }

    /*
     *  This is a simple router designed as a REST like API
     *  Each method should return an named array, which is then passed to ouput,
     *  which could be json encoded, print_r'd for debugging, or (todo) xml encoded
     *
     *  You must be logged in to use this
     */
    function rest() {
        if( $this->EE->session->userdata('email') && $this->EE->session->userdata('is_banned') == 0 ) {
            $this->EE->config->set_item('compress_output', FALSE);

            $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') ));
            //$employee = $this->EE->axapta->employee->get_remote(array( 'email' => 'jordan.williams@applusrtd.com' ));
            $employee = $employee[0];

            $method  = $this->EE->input->get('method');
            $output  = $this->EE->input->get('output');

            if( !is_array($options = $this->EE->input->post('options'))) {
                $options = array();
            }


            switch ($method) {

                case 'employee':
                    $options = array_merge($options, array(
                        'email' => $this->EE->session->userdata('email')
                        //'email' => 'bert.weber@applusrtd.com'
                    ));
                    $return_data = $this->EE->axapta->employee->get_remote( $options );
                    break;

                case 'company':
                    $options = array_merge($options, array(
                        'id' => $employee['company_id']
                    ));
                    $return_data = $this->EE->axapta->company->get_remote( $options );
                    break;

                case 'cost_center':
                    $options = array_merge($options, array(
                        // 'id' => $employee['cost_center_id'],
                        //'company_id' => '002',
                        //'id' => '1312'
                    ));
                    $return_data = $this->EE->axapta->cost_center->get_remote( $options );
                    break;

                case 'customer':
                    $options = array_merge($options, array(
                        'company_id' => $employee['company_id'],
                        'department_id' => $employee['department_id'],
                        'cost_center_id' => $employee['cost_center_id'],
                        'blocked' => 0
                    ));
                    $return_data = $this->EE->axapta->customer->get_remote( $options );
                    break;

                case 'work_location':
                    $options = array_merge($options, array(
                        'company_id' => $employee['company_id']
                    ));
                    $return_data = $this->EE->axapta->work_location->get_remote( $options );
                    break;

                case 'contact_person':
                    $options = array_merge($options, array(
                        //'company_id' => $employee['company_id']
                        //'id' => '107..SYB2001383'
                    ));
                    $return_data = $this->EE->axapta->contact_person->get_remote( $options );
                    break;

                case 'work_report':
                    $options = array_merge($options, array(
                        //'project_id' => '07.005541/001/121013'
                        'export_reason' => 'TEMPLATE'
                        ,'execution_date' => '2012-01-01'
                    ));
                    $return_data = $this->EE->axapta->work_report->get_remote( $options );
                    break;

                case 'template':
                    $options = array_merge($options, array(
                        //'company_id' => $employee['company_id'],
                        'export_reason' => 'TEMPLATE',
                        'execution_date' => '2012-01-01'
                    ));
                    $return_data = $this->EE->axapta->work_report->get_remote( $options );
                    break;

                case 'project_resources':
                    $options = array_merge($options, array(
                        //'project_id' => '07.005532/001/120820'
                    ));
                    $return_data = $this->EE->axapta->resources->get_remote( $options );
                    break;

                case 'resources':
                    $options = array_merge($options, array(
                        //'company_id' => $employee['company_id'],
                        // 'department_id' => $employee['department_id'],
                        'status' => 1
                    ));
                    $return_data = $this->EE->axapta->resources->get_remote( $options );
                    break;

                case 'materials':
                    $options = array(
                        //'project_id' => '07.003464/142/120802'
                    );
                    $return_data = $this->EE->axapta->materials->get_remote( $options );
                    break;

                case 'sales_items':
                    $options = array_merge($options, array(
                        //'project_id' => '07.005532/001/120820'
                    ));
                    $return_data = $this->EE->axapta->sales_items->get_remote( $options );
                    break;

                case 'contract_items':
                    $options = array_merge($options, array(
                        // 'contract_id' => '900.001975',
                        // 'film_indicator' => 0
                    ));
                    $return_data = $this->EE->axapta->contract_items->get_remote( $options );
                    break;

                case 'dispatch_list':
                    $options = array_merge($options, array(
                        'employee_id' => $employee['id'],
                        'invoiced_status' => 0
                    ));
                    $return_data = $this->EE->axapta->dispatch_list->get_remote( $options );
                    break;

                case 'research_procedure':
                    $options = array_merge($options, array(
                        //'id' => 'RT-95105r14'
                    ));
                    $return_data = $this->EE->axapta->research_procedure->get_remote( $options );
                    break;

                case 'review_procedure':
                    $options = array_merge($options, array(
                        //'id' => 'RT-95105r14'
                    ));
                    $return_data = $this->EE->axapta->review_procedure->get_remote( $options );
                    break;

                case 'sync':
                    if( $this->sync($employee['id']) ){
                        $return_data = $this->dashboard();
                    }

                    break;

                case 'resource_time': // Expects resource ID, project ID, button's value
                    $table = 'wr_resource_time_log';
                    $time = FALSE; // Set for return_data
                    $success = FALSE;
                    $ax_resource_id = $this->EE->input->post('resource_id'); // AX value
                    $project_id = $this->EE->input->post('project_id'); // wr_reports.id
                    $report_id = $this->EE->mysql->get_field('id','wr_reports',array('project_id'=> $project_id ), 'id');
                    
                    // get resource_id
                    $params = array(
                        'resource_id' => $ax_resource_id,
                        'report_id'   => $report_id
                        );

                    $resource_id = $this->EE->mysql->get_field('id', 'wr_resources', $params, 'id');// wr_resources.id
                    
                    // If starting clock 
                    if($this->EE->input->post('value') == 'Begin Time') {
                        
                        // Insert new log entry
                        $params = array(
                            'start_datetime'    => time(),
                            'resource_id'       => $resource_id
                            );

                        $this->EE->db->insert($table, $params);
                        $success = $this->EE->db->affected_rows();
                    } else {
                        // Look up entry based on wr_resource.id and wr_report.id
                        $this->EE->db->where('resource_id', $resource_id)
                                    ->where('end_datetime IS NULL')
                                    ->update($table, array('end_datetime' => time() ) );                        

                        // Find and update cumulative hours of work to wr_resources.qty
                        $params = array( 'resource_id' => $resource_id );
                        $time = $this->EE->mysql->get_field('SUM(end_datetime - start_datetime) AS time', $table, $params, 'time');

                        $this->EE->db->where( 'report_id', $report_id )
                                    ->where( 'resource_id', $ax_resource_id )
                                    ->update( 'wr_resources', array('qty' => $time) );
                        
                        $success = $this->EE->db->affected_rows();
                    }

                   
                    // If update/insert was successful (non-zero rows affected)
                    if($success) {
                        $return_data = array(
                            'success'   => TRUE,
                            'qty'       => $time
                            );
                    } else {
                         $return_data = array('success' => FALSE);
                    }
                    break;

                default:
                    $return_data = array('error' => 'no method found');
                    break;
            }

            switch ($output) {
                default:
                case 'json':
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                    header('Content-type: application/json; charset=utf-8');
                    echo json_encode($return_data);
                break;

                /*
                case 'xml':
                    //should return an XML document;
                break;
                */
                
                case 'html':
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                    header('Content-type: text/html; charset=utf-8');
                    echo $return_data;
                break;

                case 'debug':
                    header('Content-Type: text/html; charset=utf-8');
                    echo '<pre>';
                    print_r($return_data);
                    echo '</pre>';
                break;
            }
        } else {
            echo lang('unauthorized');
        }
    }
}// END CLASS

/* End of file mod.workreports.php */
/* Location: ./system/expressionengine/third_party/modules/workreports/mod.workreports.php */