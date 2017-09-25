<?php

namespace Craft;

class SplashPlugin extends BasePlugin {

	// Details
	// =========================================================================

	public function getName ()
	{
		return "Splash!";
	}

	public function getDescription ()
	{
		return "Quickly and easily get beautiful Unsplash images in Craft!";
	}

	public function getVersion ()
	{
		return "0.0.1";
	}

	public function getSchemaVersion ()
	{
		return "0.0.1";
	}

	public function getDeveloper ()
	{
		return "Ether Creative";
	}

	public function getDeveloperUrl ()
	{
		return "https://ethercreative.co.uk";
	}

	public function getReleaseFeedUrl ()
	{
		return "https://raw.githubusercontent.com/ethercreative/splash/master/releases.json";
	}

	public function onAfterInstall ()
	{
		craft()->request->redirect(
			UrlHelper::getCpUrl("settings/plugins/splash")
		);
	}

	// Settings
	// =========================================================================

	protected function defineSettings ()
	{
		return [
			"source" => AttributeType::Number,
			"authorField" => AttributeType::String,
			"authorUrlField" => AttributeType::String,
			"colorField" => AttributeType::String,
		];
	}

	public function getSettingsHtml ()
	{
		$settings = $this->getSettings();
		$validFields = ["PlainText", "RichText", "Color"];

		$sources = [];
		/** @var AssetSourceModel $source */
		foreach (craft()->assetSources->getAllSources() as $source)
		{
			$s = ["label" => $source->name, "value" => $source->id];

			$fields = [];
			/** @var FieldLayoutModel $layout */
			$layout = craft()->fields->getLayoutById($source->fieldLayoutId);
			/** @var FieldLayoutFieldModel $field */
			foreach ($layout->getFields() as $field)
			{
				$field = $field->getField();
				/** @var FieldModel $field */

				if (in_array($field->getFieldType()->getClassHandle(), $validFields))
				{
					$fields[] = [
						"label" => $field->name,
						"value" => $field->handle,
						"type" => $field->getFieldType()->getClassHandle(),
					];
				}
			}

			$s["fields"] = $fields;

			$sources[] = $s;
		}

		return craft()->templates->render(
			"splash/settings",
			compact("settings", "sources")
		);
	}

	// Routes
	// =========================================================================

	public function hasCpSection ()
	{
		return true;
	}

	public function registerCpRoutes ()
	{
		return [
			"splash" => ["action" => "splash/index"],
		];
	}

}
