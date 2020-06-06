<?php declare(strict_types=1);


namespace Demo;


class RestClient extends \CRest
{
	public static $auth;

	/**
	 * Can overridden this method to change the data storage location.
	 *
	 * @return array setting for getAppSettings()
	 */
	protected static function getSettingData(): array
	{
		$return = static::$auth;

		if(defined("C_REST_CLIENT_ID") && !empty(\C_REST_CLIENT_ID))
		{
			$return['C_REST_CLIENT_ID'] = \C_REST_CLIENT_ID;
		}
		if(defined("C_REST_CLIENT_SECRET") && !empty(\C_REST_CLIENT_SECRET))
		{
			$return['C_REST_CLIENT_SECRET'] = \C_REST_CLIENT_SECRET;
		}

		return $return;
	}

	/**
	 * Can overridden this method to change the data storage location.
	 *
	 * @var $arSettings array settings application
	 * @return boolean is successes save data for setSettingData()
	 */
	protected static function setSettingData($arSettings): bool
	{
		static::$auth = $arSettings;

		return true;
	}
}