<?php

/*
 *  Contact Persons
 *
 *	Option: id
 *	Option: name
 *	Option: customer_id
 *	Option: customer_name
 *
 */
 
class contact_person extends Axapta {
	function get($options = NULL) {
		if ( $employee = $this->employee->get() ) {
			$query = 
			    'SELECT 
				    CONTACTPERSON.CONTACTPERSONID    AS id,
				    CONTACTPERSON.NAME               AS name_last_first,
				    CONTACTPERSON.EMAIL              AS email,
				    CONTACTPERSON.PHONE              AS phone,
				    CONTACTPERSON.CELLULARPHONE      AS cell_phone,
				    CUSTTABLE.ACCOUNTNUM             AS customer_id,
				    CUSTTABLE.NAME                   AS customer_name
				FROM CONTACTPERSON
				LEFT JOIN CUSTTABLE ON CUSTTABLE.ACCOUNTNUM = CONTACTPERSON.CUSTACCOUNT AND CUSTTABLE.DATAAREAID = CONTACTPERSON.DATAAREAID
				WHERE
					1=1';
			
			if( isset($options['id']) ) {
				$query .= ' AND CONTACTPERSON.CONTACTPERSONID = :id';
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
				$query .= ' AND CONTACTPERSON.NAME LIKE :name';
			}
			if( isset($options['customer_id']) ){
				$query .= ' AND CUSTTABLE.ACCOUNTNUM = :customer_id';
			}
			if( isset($options['customer_name']) ){
				$query .= ' AND CUSTTABLE.NAME LIKE :customer_name';
			}
			//	'  
			//	ORDER BY CUSTTABLE.ACCOUNTNUM';
			

			$contact_person = $this->conn->prepare($query);

			if( isset($options['id']) ){
				$contact_person->bindValue(':id', $options['id'], PDO::PARAM_STR);
			}
			if( isset($options['name']) ){
				$contact_person->bindValue(':name', '%'.$options['name'].'%', PDO::PARAM_STR);
			}
			if( isset($options['customer_id']) ){
				$contact_person->bindValue(':customer_id', $options['customer_id'], PDO::PARAM_STR);
			}
			if( isset($options['customer_name']) ){
				$contact_person->bindValue(':customer_name', '%'.$options['customer_name'].'%', PDO::PARAM_STR);
			}

			$contact_person->setFetchMode(PDO::FETCH_NAMED);
			$contact_person->execute();

			$return_data = $contact_person->fetchAll();

			return $this->fix_padding($return_data);
		} else {
			return FALSE;
		}
	}
}