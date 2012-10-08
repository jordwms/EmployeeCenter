<?php
class work_report extends axapta {
	protected $project_id                  = 'SALESTABLE.PROJID';
	protected $template_indicator          = 'SALESTABLE.EXPORTREASON';

	protected $sales_id                    = 'SALESTABLE.SALESID';

	protected $customer_id                 = 'SALESTABLE.CUSTACCOUNT';
	protected $customer_name               = 'SALESTABLE.SALESNAME';
	protected $customer_refrence           = 'SALESTABLE.CUSTOMERREF';
	protected $customer_contact_person_id  = 'SALESTABLE.CONTACTPERSONID';

	protected $invoice_account             = 'SALESTABLE.INVOICEACCOUNT';

	protected $company_id                  = 'SALESTABLE.DATAAREAID';
	protected $department_id               = 'SALESTABLE.DIMENSION';
	protected $cost_center_id              = 'SALESTABLE.DIMENSION2_';
	protected $technique_id                = 'SALESTABLE.DIMENSION3_';

	protected $contract_id                 = 'RTDPROJORDERTABLE.CONTRACTID';
	protected $contract_date               = 'RTDPROJORDERTABLE.CONTRACTDATE';

	protected $deadline_date               = 'CONVERT(DATE, SALESTABLE.DEADLINE)';
	protected $execution_date              = 'CONVERT(DATE, SALESTABLE.DELIVERYDATE)';

	protected $rtd_reference               = 'SALESTABLE.RTDPROJORDERREFERENCE';

	protected $sales_responsible           = 'SALESTABLE.SALESRESPONSIBLE';
	protected $crew_leader_id              = 'RTDEMPLPERWORKREPORT.EMPLID';
	protected $team_contact_person_id      = 'RTDPROJORDERTABLE.CONTACTPERSONID';

	protected $work_location               = 'SALESTABLE.DELIVERYNAME';
	protected $work_location_address       = 'SALESTABLE.DELIVERYADDRESS';

	protected $object_description          = 'SALESTABLE.RTDOBJECTDESCRIPTION';
	protected $order_description           = 'SALESTABLE.RTDORDERDESCRIPTION';

	protected $research_norm_id            = 'RTDSALESPROCEDURE.RESEARCHNORMID';
	protected $research_procedure_id       = 'RTDSALESPROCEDURE.RESEARCHPROCEDUREID';
	protected $research_spec_id            = 'RTDSALESPROCEDURE.RESEARCHSPECID';

	protected $review_norm_id              = 'RTDSALESPROCEDURE.REVIEWNORMID';
	protected $review_procedure_id         = 'RTDSALESPROCEDURE.REVIEWPROCEDUREID';
	protected $review_spec_id              = 'RTDSALESPROCEDURE.REVIEWSPECID';

	protected $status                      = 'SALESTABLE.RTDAPPROVED';
	protected $project_status              = 'RTDPROJORDERTABLE.PROJORDERSTATUS';
	protected $invoiced_status             = 'SALESTABLE.RTDINVOICED';

	protected $created_date                = 'CONVERT(DATE, SALESTABLE.CREATEDDATE)';
	protected $created_time                = 'SALESTABLE.CREATEDTIME';
	protected $created_by                  = 'SALESTABLE.CREATEDBY';

	protected $modified_date               = 'CONVERT(DATE, SALESTABLE.MODIFIEDDATE)';
	protected $modified_time               = 'SALESTABLE.MODIFIEDTIME';
	protected $modified_by                 = 'SALESTABLE.MODIFIEDBY';


	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}
	
	/*
	 *  Work Reports
	 *
	 *	Option: 
	 *
	 */
	function get_remote($options = NULL) {
		$query = $this->build_SELECT();

		$query .= 'FROM SALESTABLE'.NL;
		$query .= 'LEFT JOIN PROJTABLE AS WORKREPORT ON WORKREPORT.PROJID            = SALESTABLE.PROJID    AND WORKREPORT.DATAAREAID           = SALESTABLE.DATAAREAID'.NL;
		$query .= 'LEFT JOIN PROJTABLE AS WORKORDER  ON WORKORDER.PROJID             = WORKREPORT.PARENTID  AND WORKORDER.DATAAREAID            = SALESTABLE.DATAAREAID'.NL;
		$query .= 'LEFT JOIN RTDPROJORDERTABLE       ON RTDPROJORDERTABLE.PROJID     = WORKORDER.PARENTID   AND RTDPROJORDERTABLE.DATAAREAID    = SALESTABLE.DATAAREAID AND RTDPROJORDERTABLE.PROJID <> \'\''.NL;
		$query .= 'LEFT JOIN RTDEMPLPERWORKREPORT    ON RTDEMPLPERWORKREPORT.PROJID  = SALESTABLE.PROJID    AND RTDEMPLPERWORKREPORT.DATAAREAID = SALESTABLE.DATAAREAID AND RTDEMPLPERWORKREPORT.TASKID = \'Crew Leader\''.NL;
		$query .= 'LEFT JOIN RTDSALESPROCEDURE       ON RTDSALESPROCEDURE.SALESID    = SALESTABLE.SALESID   AND RTDSALESPROCEDURE.DATAAREAID    = SALESTABLE.DATAAREAID'.NL;
			
		$query .= $this->build_WHERE($options);

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}
			
		$work_report = $this->conn->prepare($query);

		$this->bind_option_values($work_report, $options);

		$work_report->setFetchMode(PDO::FETCH_NAMED);
		$work_report->execute();

		$return_data = $work_report->fetchAll();

		return $this->fix_padding($return_data);
	}

	/*
	 *  Approval
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function set_approval($projid=NULL, $dataAreaID=NULL, $emplID=NULL, $approved=TRUE) {
		if( $employee = $this->employee->get() ) {
			$query = 'UPDATE Salestable SET RtdApproved = :approved WHERE PROJID = :projid AND DATAAREAID = :dataAreaID';

			$approval = $this->conn->prepare($query);

			if($approved) {
				$approval->bindValue('approved', 1, PDO::PARAM_STR);
			} else {
				$approval->bindValue('approved', 0, PDO::PARAM_STR);
			}
			$approval->bindValue('projid', $projid, PDO::PARAM_STR);
			$approval->bindValue('emplID', $projid, PDO::PARAM_STR);
			$approval->bindValue('dataAreaID', $dataAreaID, PDO::PARAM_STR);
			
			$approval->execute();
			//$approval->bindValue('emplID', $dataAreaID, PDO::PARAM_STR, 3);
		}
	}
}