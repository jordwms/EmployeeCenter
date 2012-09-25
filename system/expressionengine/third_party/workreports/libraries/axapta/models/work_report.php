<?php
class work_report extends Axapta {
	/*
	 *  Work Reports
	 *
	 *	Option: 
	 *
	 */
	function get($options = NULL) {
		if( $employee = $this->employee->get() ) {
			$query = 
				'SELECT TOP(10)
					SALESTABLE.PROJID                                  AS project_id,
					SALESTABLE.EXPORTREASON                            AS export_reason,
					SALESTABLE.SALESID                                 AS sales_id,
					SALESTABLE.DATAAREAID                              AS company_id,
					SALESTABLE.DIMENSION                               AS department_id,
					SALESTABLE.DIMENSION2_                             AS cost_center_id,
					SALESTABLE.DIMENSION3_                             AS technique_id,
					RTDPROJORDERTABLE.CONTRACTID                       AS contract_id,
					RTDPROJORDERTABLE.CONTRACTDATE                     AS contract_date,
					SALESTABLE.DEADLINE                                AS deadline_date,
					CONVERT(DATE, SALESTABLE.DELIVERYDATE)             AS execution_date,
					SALESTABLE.RTDPROJORDERREFERENCE                   AS rtd_reference,
					SALESTABLE.SALESRESPONSIBLE                        AS sales_responsible,
					RTDEMPLPERWORKREPORT.EMPLID                        AS crew_leader,
					RTDPROJORDERTABLE.CONTACTPERSONID                  AS contact_person_id,
					SALESTABLE.DELIVERYNAME                            AS delivery_name,
					SALESTABLE.DELIVERYADDRESS                         AS delivery_address,
					SALESTABLE.CUSTACCOUNT                             AS customer_id,
					SALESTABLE.SALESNAME                               AS customer_name,
					SALESTABLE.CUSTOMERREF                             AS customer_refrence,
					SALESTABLE.CONTACTPERSONID                         AS customer_contact_person_id,
					SALESTABLE.RTDOBJECTDESCRIPTION                    AS object_description,
					SALESTABLE.RTDORDERDESCRIPTION                     AS order_description,
					RTDSALESPROCEDURE.RESEARCHNORMID                   AS research_norm_id,
					RTDSALESPROCEDURE.RESEARCHPROCEDUREID              AS research_procedure_id,
					RTDSALESPROCEDURE.RESEARCHSPECID                   AS research_spec_id,
					RTDSALESPROCEDURE.REVIEWNORMID                     AS review_norm_id,
					RTDSALESPROCEDURE.REVIEWPROCEDUREID                AS review_procedure_id,
					RTDSALESPROCEDURE.REVIEWSPECID                     AS review_spec_id,
					SALESTABLE.RTDAPPROVED                             AS status,
					RTDPROJORDERTABLE.PROJORDERSTATUS                  AS project_status,
					SALESTABLE.INVOICEACCOUNT                          AS invoice_account,
					SALESTABLE.RTDINVOICED                             AS invoiced_status,
					CONVERT(DATE, SALESTABLE.CREATEDDATE)              AS created_date,
					SALESTABLE.CREATEDTIME                             AS created_time,
					SALESTABLE.CREATEDBY                               AS created_by,
					CONVERT(DATE, SALESTABLE.MODIFIEDDATE)             AS modified_date,
					SALESTABLE.MODIFIEDTIME                            AS modified_time,
					SALESTABLE.MODIFIEDBY                              AS modified_by
				FROM SALESTABLE
				LEFT JOIN PROJTABLE AS WORKREPORT ON WORKREPORT.PROJID            = SALESTABLE.PROJID    AND WORKREPORT.DATAAREAID           = SALESTABLE.DATAAREAID
				LEFT JOIN PROJTABLE AS WORKORDER  ON WORKORDER.PROJID             = WORKREPORT.PARENTID  AND WORKORDER.DATAAREAID            = SALESTABLE.DATAAREAID
				LEFT JOIN RTDPROJORDERTABLE       ON RTDPROJORDERTABLE.PROJID     = WORKORDER.PARENTID   AND RTDPROJORDERTABLE.DATAAREAID    = SALESTABLE.DATAAREAID AND RTDPROJORDERTABLE.PROJID <> \'\'
				LEFT JOIN RTDEMPLPERWORKREPORT    ON RTDEMPLPERWORKREPORT.PROJID  = SALESTABLE.PROJID    AND RTDEMPLPERWORKREPORT.DATAAREAID = SALESTABLE.DATAAREAID AND RTDEMPLPERWORKREPORT.TASKID = \'Crew Leader\'
				LEFT JOIN RTDSALESPROCEDURE       ON RTDSALESPROCEDURE.SALESID    = SALESTABLE.SALESID   AND RTDSALESPROCEDURE.DATAAREAID    = SALESTABLE.DATAAREAID
				WHERE
					WORKREPORT.RTDPROJORDERLEVEL = 3';
			
				
			$work_report = $ax_conn->prepare($query);

			$work_report->setFetchMode(PDO::FETCH_NAMED);
			$work_report->execute();

			$return_data = $work_report->fetchAll();

			return $this->fix_padding($return_data);
		} else {
			return FALSE;
		}
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