<?php
class company extends Axapta {
	/*
	 *  Company Info
	 *	Options: id (default: employee['company_id'])
	 *
	 *	DataAreaID = Company ID
	 */
	function get_remote($options = NULL) {
		if ( $employee = $this->employee->get() ) {
			$query = 'SELECT
					DATAAREAID                  AS id,
					NAME                        AS name,
					ADDRESS                     AS address,
					PHONE                       AS phone,
					STREET                      AS street,
					CITY                        AS city,
					STATE                       AS state,
					ZIPCODE                     AS zipcode,
					COUNTY                      AS county,
					COUNTRY                     AS country,
					ADDRFORMAT                  AS address_format,
					RTDCOMPANYPREFIX            AS company_prefix,

					CONVERT(DATE,MODIFIEDDATE)  AS modified_date,
					MODIFIEDTIME                AS modified_time,
					MODIFIEDBY                  AS modified_by
				FROM COMPANYINFO';
			
			if( !isset($options['id']) || $options['id'] != 'ALL' ) {
				$query .= ' WHERE DATAAREAID = :id';
			}

			$company = $this->conn->prepare($query);

			if( isset($options['id']) && $options['id'] != 'ALL' ){
				$company->bindParam(':id', $options['id'], PDO::PARAM_STR, 3);
			} else {
				if ( $employee = $this->employee->get() ) {
					$company->bindParam(':id', $employee['company_id'], PDO::PARAM_STR, 3);
				} else {
					return FALSE;
				}
			}

			$company->setFetchMode(PDO::FETCH_NAMED);
			$company->execute();

			$return_data = $company->fetchAll();

			foreach ($return_data as $row => &$values) {
				//fix modified dates into unix timestamps
				$values['modified_datetime'] = date('l jS \of F Y h:i:s A', strtotime($values['modified_date']) + ($values['modified_time']/1000));
			}

			return $this->fix_padding($return_data);
		} else {
			return FALSE;
		}
	}
}