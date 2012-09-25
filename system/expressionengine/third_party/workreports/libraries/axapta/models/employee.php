<?php
class employee extends Axapta {
	/*
	 *	Employee Details
	 *	
	 *	Defaults: EMAIL = current user email
	 *	Options: ANY COLUMN
	 *
	 */
	function employee($options = NULL) {
		/*
		 *  Employee information
		 */
		$query = 'SELECT
			[NAME]                        AS name_last_first,
			[ALIAS]                       AS alias,
			[TITLE]                       AS title,

			[EMPLID]                      AS id,
			[USERID]                      AS axapta_id,
			[DATAAREAID]                  AS data_area_id,
			[INTERNALEXTERNAL]            AS internal_external,
			[HRMACTIVEINACTIVE]           AS hrm_active_flag,
			[STATUS]                      AS status,

			[DATAAREAID]                  AS company_id,
			[DIMENSION]                   AS department_id,
			[DIMENSION2_]                 AS cost_center_id,
			[DIMENSION3_]                 AS technique_id,
			[DIMENSION4_]                 AS business_line_id,
			
			[EMAIL]                       AS email,
			[PHONE]                       AS phone,
			[CELLULARPHONE]               AS cell_phone,
			[RTDPRIVATEPHONE]             AS personal_phone,
			[TELEFAX]                     AS fax,
			
			[ADDRESS]                     AS full_address,
			[STREET]                      AS street,
			[CITY]                        AS city,
			[COUNTYID]                    AS county,
			[STATEID]                     AS state,
			[ZIPCODEID]                   AS zip_code,
			[COUNTRYID]                   AS country,
			
			[CURRENCY]                    AS currency,
			
			CONVERT(DATE,[BIRTHDATE])     AS birth_date,
			CONVERT(DATE,[MODIFIEDDATE])  AS modified_date,
			[MODIFIEDTIME]                AS modified_time,
			[MODIFIEDBY]                  AS modified_by,
			CONVERT(DATE,[CREATEDDATE])   AS created_date,
			[CREATEDTIME]                 AS created_time,
			[CREATEDBY]                   AS created_by
		FROM [TEST].[dbo].[EMPLTABLE]';

		//extend query with options
		$query .= ' WHERE ';

		//handy dubug defaults;
		//$options = array('DATAAREAID' => '107';
		//$options = array('email' => 'chet.yates@applusrtd.com');
		//$options = array('email' => 'bert.weber@applusrtd.com');

		if( is_array($options) && count($options) > 0 ){
			foreach ($options as $key => $value) {
				$query .= $key.' = :'.$key;

				if( count($options) > 1 || !isset($data['email']) ) { 
					$query .= ' AND '; 
				}
			}
		}

		//set defualt values
		if( !isset($data['email']) ) {
			$query .= 'EMAIL = :email';
		}

		$employee_info = $this->conn->prepare($query);

		//bind options
		if( is_array($options) && count($options) > 0 ){
			foreach ($options as $key => $value) {
				$employee_info->bindValue(':'.$key, $value);
			}
		}

		//bind defaults
		if ( !isset($options['email']) ) {
			$employee_info->bindValue(':email', $this->EE->session->userdata['email'], PDO::PARAM_STR);
		} else {
			$employee_info->bindValue(':email', $options['email'], PDO::PARAM_STR);
		}
		
		$employee_info->setFetchMode(PDO::FETCH_NAMED);
		$employee_info->execute();
		$employee = $employee_info->fetchAll();

		//Sanity Check if there are more than one employee's returned for 1 email given
		if( count($employee) == 1 ) {
			//no need for nested arrays
			$employee = $employee[0];

			//fix employee name so we have a nice "First Last" setup
			$employee_name = explode(',', $employee['name_last_first']);
			$employee['name'] = ltrim($employee_name[1]).' '.$employee_name[0];

			//fix modified and created dates into unix timestamps
			$employee['modified_datetime'] = strtotime($employee['modified_date']) + ($employee['modified_time']/1000);
			$employee['created_datetime']  = strtotime($employee['created_date']) + ($employee['created_time']/1000);

			
			/*
			 *	each company has it's own groups
			 *	each employee can belong to multiple groups in differnt companies
			 *	!an employee should only be granted a group's privlidges for the correct company
			 *
			 *	[groups] => Array(
		     *      [company_id] => Array(
		     *          [0] => 'GROUP ID'
		     *      ),
		     *      [107] => Array(
		     *          [0] => 'WA TECH',
		     *          [1] => 'WA DISP',
		     *			[2] => 'WA ADMIN'
		     *      )
			 *  )
			 *
			 */
			$employee['groups'] = array();
			$employeeGroups = $this->conn->prepare(
				"SELECT
					HRMVIRTUALNETWORKHISTORY.DATAAREAID        AS company_id,
					HRMVIRTUALNETWORKHISTORY.HRMPOSITIONID     AS position_id
				FROM HRMVIRTUALNETWORKTABLE
				JOIN HRMVIRTUALNETWORKHISTORY ON HRMVIRTUALNETWORKTABLE.HRMVIRTUALNETWORKID = HRMVIRTUALNETWORKHISTORY.HRMVIRTUALNETWORKID
				WHERE REFERENCE = :id"
			);
			$employeeGroups->bindParam(':id', $employee['id'], PDO::PARAM_STR, 12);
			$employeeGroups->setFetchMode(PDO::FETCH_NAMED);
			$employeeGroups->execute();

			foreach ($employeeGroups->fetchALL() as $group) {
				if( !array_key_exists($group['company_id'], $employee['groups'])){
					$employee['groups'][$group['company_id']] = array();
				}
				array_push($employee['groups'][$group['company_id']], $group['position_id'] );
			}

			return $this->fix_padding($employee);
		} else {
			return FALSE;
		}
	}
}