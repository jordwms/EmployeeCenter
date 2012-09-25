<?php
class customer extends Axapta {
	/*
	 *  Customers
	 *
	 *	Option: id
	 *	Option: name
	 *
	 *	Will only return customers for an employee's authorized companies if no customer id given
	 *
	 */
	function get($options = NULL) {
		if ( $employee = $this->employee->get() ) {

			//$employee['groups'][101] = array('WA TECH');  //debugging test

			$query = 
				'SELECT
					CustTable.AccountNum          AS id,
					CustTable.AccountNum          AS account_num,
					CustTable.Name                AS name,
					CustTable.Address             AS address,
					CustTable.Phone               AS phone,
					CustTable.TELEFAX             AS fax,
					SMMBUSRELTABLE.MAINCONTACT    AS main_contact,
					smmBusRelTable.BusRelAccount  AS business_relation_account
				FROM CustTable
				LEFT JOIN smmBusRelTable ON smmBusRelTable.CustAccount = CustTable.AccountNum
				WHERE 
					CUSTTABLE.BLOCKED = 0';

			if( isset($options['id']) ) {
				$query .= ' AND LTRIM(CustTable.AccountNum) = :id';
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
				$query .= ' AND CustTable.DATAAREAID IN ('.$authorized_companies.')';
			}

			if( isset($options['name']) ) {
				$query .= ' AND CustTable.NAME LIKE :name';
			}

			$customers = $this->conn->prepare($query);

			if( isset($options['id']) ) {
				$customers->bindValue(':id', $options['id'], PDO::PARAM_STR);
			}
			if( isset($options['name']) ) {
				$customers->bindValue(':name', '%'.$options['name'].'%', PDO::PARAM_STR);
			}

			$customers->setFetchMode(PDO::FETCH_NAMED);
			$customers->execute();

			return $this->fix_padding($customers->fetchAll());
		} else {
			return FALSE;
		}
	}