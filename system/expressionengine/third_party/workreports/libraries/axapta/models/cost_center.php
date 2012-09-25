<?php
class cost_center extends Axapta {
	/*
	 *  Cost Center
	 *	Option: id         (default: employee['cost_center_id'])
	 *	Option: company_id (default: employee['company_id'])
	 */
	function get($options = NULL) {
		if ( $employee = $this->employee->get() ) {
			$cost_center = $this->conn->prepare(
				'SELECT
					DIMENSIONS.NUM          AS id,
					DIMENSIONS.DATAAREAID   AS company_id,
					DIMENSIONS.COMPANYGROUP AS company_group,
					ADDRESS.NAME            AS name,
					ADDRESS.ADDRESS         AS address,
					ADDRESS.PHONE           AS phone,
					ADDRESS.TELEFAX         AS fax,
					ADDRESS.EMAIL           AS email,
					ADDRESS.STREET          AS street,
					ADDRESS.CITY            AS city,
					ADDRESS.STATE           AS state,
					ADDRESS.ZIPCODE         AS zipcode,
					ADDRESS.COUNTRY         AS country
				FROM DIMENSIONS
				LEFT JOIN ADDRESS ON DIMENSIONS.RTDADDRESS = ADDRESS.RECID
				WHERE DIMENSIONS.DATAAREAID = :company_id
				AND DIMENSIONS.NUM = :id
				AND DIMENSIONS.DIMENSIONCODE = 1'
			);


			if( isset($options['id']) ){
				$cost_center->bindValue(':id', $options['id'], PDO::PARAM_STR);
			} else {
				$cost_center->bindValue(':id', $employee['cost_center_id'], PDO::PARAM_STR);
			}

			if( isset($options['company_id']) ){
				$cost_center->bindValue(':company_id', $options['company_id'], PDO::PARAM_STR);
			} else {
				$cost_center->bindValue(':company_id', $employee['company_id'], PDO::PARAM_STR);
			}

			
			$cost_center->setFetchMode(PDO::FETCH_NAMED);
			$cost_center->execute();

			return $cost_center->fetchAll();
		} else {
			return FALSE;
		}
	}
}