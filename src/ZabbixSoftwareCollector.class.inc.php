<?php

require_once(APPROOT.'collectors/src/ZabbixCsvCollector.class.inc.php');

class ZabbixSoftwareCollector extends ZabbixCsvCollector
{
	protected $idx;
	
	protected static $sCsvSourceFilePath = null;
	protected static $aHeaderColumns = null;
	protected static $aJsonToCsv = null;
	protected static $bCsvSourceFileExits = false;
	protected static $bHasStaticBeenInitialized = false;

	/**
	 * @inheritdoc
	 */
	public function Init(): void
	{
		parent::Init();

		if (!static::$bHasStaticBeenInitialized) {
			// Init variables
			static::$sCsvSourceFilePath = static::GetCsvSourceFilePath();
			static::$aHeaderColumns = static::GetCsvSourceFileHeader();
			static::$aJsonToCsv = static::GetJsonToCsv();

			// Init CSV source file
			static::$bCsvSourceFileExits = static::InitCsvSourceFile(static::$sCsvSourceFilePath, static::$aHeaderColumns);
			static::$bHasStaticBeenInitialized = true;

			Utils::Log(LOG_INFO, '['.get_class($this).'] CSV file '.$this->sCIClass.'.csv has been created.');
		}
	}

	/**
	 * Register a new line into the CSV source file
	 *
	 * @param $aData
	 * @return bool
	 * @throws Exception
	 */
	public static function RegisterLine($aData): bool
	{
		if (static::$bCsvSourceFileExits) {
			return parent::AddLineToCsvSourceFile($aData, static::$sCsvSourceFilePath, static::$aJsonToCsv);
		} else {
			return false;
		}

	}

    /**
	 * @inheritdoc
	 */
	public function Prepare()
	{
		[$bSucceed, $aResults] = parent::Post([
			'id' => '2',
			'jsonrpc' => '2.0',
			'method' => 'item.get',
			'params' => [
				'output' => ['lastvalue'],
				'search'=> [
					'name'=> Utils::GetConfigurationValue("zabbix_api_software_item_name")
				]
			]
		]);

		if ($bSucceed){
			$aIndex = array();
			foreach( $aResults['result'] as $aResult ) 
			{
				if ($aResult['lastvalue'] == "")
					continue;

				$aLastValues = json_decode($aResult['lastvalue']);

				foreach( $aLastValues as $aLastValue ) {

					if (! isset($aLastValue->Vendor) || is_null($aLastValue->Vendor) || $aLastValue->Vendor == "")
						$aLastValue->Vendor = "Undefined";

					if (! isset($aLastValue->Name) || is_null($aLastValue->Name) || $aLastValue->Name == "")
						continue;
	
					if (! isset($aLastValue->Version) || is_null($aLastValue->Version) || $aLastValue->Version == "")
						continue;

					$_sIndex = $aLastValue->Vendor."::".$aLastValue->Name."::".$aLastValue->Version;
					if ( in_array($_sIndex, $aIndex) ) {
						continue;
					}

					$aIndex[] = $_sIndex;
					$aData = array(
						"primary_key" => hash('sha256', $_sIndex),
						"type" => "OtherSoftware",
						"vendor" => $aLastValue->Vendor,
						"name" => $aLastValue->Name,
						"version" => $aLastValue->Version
					);
					$this->RegisterLine($aData);
				}
			}
		}

		return parent::Prepare();
	}

	/**
	 * @inheritdoc
	 */
	public function Collect($iMaxChunkSize = 0): bool
	{
		Utils::Log(LOG_INFO, '----------------');
		return parent::Collect($iMaxChunkSize);
	}
}