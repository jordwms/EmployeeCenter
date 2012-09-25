<?php
class sales_items extends Axapta {
	/*
	 *  Sales Items
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function get($options = NULL) {
		if ( $this->conn = $this->axapta_connection() ) {
			$salesItems = $this->conn->query(
				"SELECT
					LTRIM(SALESLINE.INVENTDIMID)            AS dimension_id,
					SALESLINE.ITEMID                        AS item_id,
					SALESLINE.RTDITEMNAME                   AS name,
					SALESLINE.SALESUNIT                     AS unit
				FROM SALESTABLE
				LEFT JOIN SALESLINE ON SALESTABLE.SALESID = SALESLINE.SALESID AND SALESTABLE.DATAAREAID = SALESLINE.DATAAREAID
				WHERE 
				SALESTABLE.PROJID = REPLACE('$projid', '-', '/')
				AND SALESLINE.RTDWrkCtrID <> '$this->emplID'
				ORDER BY SALESLINE.RTDITEMNAME, SALESLINE.SALESUNIT"
			);

			return $salesItems->fetchAll();
		} else {
			return FALSE;
		}
	}
}