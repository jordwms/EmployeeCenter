<?php
class work_location extends Axapta {
	/*
	 *  Work Locations
	 *
	 *	Option: id
	 *	Option: name
	 *	Option: customer_name
	 *	Option: customer_id
	 *
	 *	Work Locations = address type 99
	 @TODO: Default Contact Person for Work Location
	 */
	function get($options = NULL) {
		if ( $employee = $this->employee->get() ) {
			$query = 
				'SELECT
					[ADDRESS].RTDLOCATIONID     AS id,
					[ADDRESS].NAME              AS name,
					CUSTTABLE.ACCOUNTNUM        AS customer_id,
					CUSTTABLE.NAME              AS customer_name,
					[ADDRESS].ADDRESS           AS full_address,
					[ADDRESS].STREET            AS street,
					[ADDRESS].CITY              AS county,
					[ADDRESS].STATE             AS state,
					[ADDRESS].ZIPCODE           AS zip_code,
					[ADDRESS].COUNTRY           AS country
				FROM CUSTTABLE
				RIGHT JOIN [ADDRESS] ON [ADDRESS].ADDRRECID = CUSTTABLE.RECID AND [ADDRESS].DATAAREAID = CUSTTABLE.DATAAREAID
				WHERE [ADDRESS].TYPE = 99';

			if( isset($options['id']) ) {
				$query .= ' AND [ADDRESS].RTDLOCATIONID = :id';
			} else {
				//These are the companies the employee has "WA TECH", we will only list customers of these companies.
				$authorized_companies = '';
				$last_key = end( array_keys($employee['groups']) );
				foreach ($employee['groups'] as $company => $group) {
					if ( in_array('WA TECH', $employee['groups'][$company]) ) {
						$authorized_companies .= "'$company'";
						if( count($employee['groups']) > 1 && $company != $last_key) {
							$authorized_companies .= ', ';
						}
					}
				}
				$query .= ' AND CUSTTABLE.DATAAREAID IN ('.$authorized_companies.')';
			}
			if( isset($options['name']) ){
				$query .= ' AND [ADDRESS].NAME LIKE :name';
			}
			if( isset($options['customer_id']) ){
				$query .= ' AND LTRIM(CUSTTABLE.ACCOUNTNUM) = :customer_id';
			}
			if( isset($options['customer_name']) ){
				$query .= ' AND CUSTTABLE.NAME LIKE :customer_name';
			}

			$query .= ' ORDER BY CUSTTABLE.NAME, [ADDRESS].NAME';

			$work_location = $this->conn->prepare($query);

			if( isset($options['id']) ) {
				$work_location->bindValue(':id', $options['id'], PDO::PARAM_STR);
			}
			if( isset($options['name']) ){
				$work_location->bindValue(':name', '%'.$options['name'].'%', PDO::PARAM_STR);
			}
			if( isset($options['customer_id']) ){
				$work_location->bindValue(':customer_id', $options['customer_id'], PDO::PARAM_STR);
			}
			if( isset($options['customer_name']) ){
				$work_location->bindValue(':customer_name', '%'.$options['customer_name'].'%', PDO::PARAM_STR);
			}
			
			$work_location->setFetchMode(PDO::FETCH_NAMED);
			$work_location->execute();
			
			$return_data = $work_location->fetchAll();

			return $this->fix_padding($return_data);
		} else {
			return FALSE;
		}
	}
}