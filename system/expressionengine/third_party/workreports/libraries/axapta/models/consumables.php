<?php

/*
 *  Consumables
 *
 *	Options: 
 *	Defaults: 
 *
 */

class consumables extends Axapta {
	function get($options = NULL) {
		$query = 
			'SELECT 
				INVENTTABLE.ITEMID                      AS id,
				INVENTTABLE.DATAAREAID                  AS company_id,
				LTRIM(PRICEDISCTABLE.INVENTDIMID)       AS dimension_id,
				INVENTTABLE.ITEMNAME                    AS name,
				PRICEDISCTABLE.UNITID                   AS unit,
				PRICEDISCTABLE.RTDPRICETYPE             AS price_type,
				PRICEDISCTABLE.AMOUNT                   AS price
			FROM
				INVENTTABLE
				JOIN PRICEDISCTABLE  ON PRICEDISCTABLE.ITEMRELATION = INVENTTABLE.ITEMID AND PRICEDISCTABLE.DATAAREAID = INVENTTABLE.DATAAREAID
			WHERE
				INVENTTABLE.RTDFILMIND = 1
				AND MODELGROUPID = \'CONSUMABLE\'
				AND RTDPROBEFUNCTION = \'WR\'
			ORDER BY PRICEDISCTABLE.RTDITEMNAME';
	}
}