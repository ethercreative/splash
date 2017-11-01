<?php

namespace ether\splash;

use craft\base\FieldInterface;
use craft\base\FlysystemVolume;
use craft\events\RegisterUrlRulesEvent;
use craft\models\FieldLayout;
use craft\web\UrlManager;
use ether\splash\models\Settings;
use yii\base\Event;

class Plugin extends \craft\base\Plugin {

	/**
	 * @var Plugin
	 */
	public static $i;

	public $controllerNamespace = 'ether\\splash\\controllers';
	public $hasCpSection  = true;
	public $hasCpSettings = true;

	/**
	 * Initialize
	 */
	public function init ()
	{
		parent::init();

		self::$i = self::getInstance();

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			function (RegisterUrlRulesEvent $event) {
				$event->rules['splash'] = 'splash/splash/index';
				$event->rules['POST splash/un'] = 'splash/splash/un';
				$event->rules['POST splash/dl'] = 'splash/splash/dl';
			}
		);
	}

	/**
	 * @return Settings
	 */
	protected function createSettingsModel () : Settings
	{
		return new Settings();
	}

	protected function settingsHtml () : string
	{
		// Get and pre-validate the settings
		$settings = $this->getSettings();
		$settings->validate();

		$validFields = ["PlainText", "RichText", "Color"];

		$volumes = [];
		/** @var FlysystemVolume $volume */
		foreach (\Craft::$app->volumes->getAllVolumes() as $volume)
		{
			$s = ["label" => $volume->name, "value" => $volume->id];

			$fields = [];

			/** @var FieldLayout $layout */
			$layout = \Craft::$app->fields->getLayoutById($volume->fieldLayoutId);

			/** @var FieldInterface $field */
			foreach ($layout->getFields() as $field)
			{
				$fieldTypeClass = explode('\\', get_class($field));
				$fieldTypeClass = end($fieldTypeClass);
				if (in_array($fieldTypeClass, $validFields))
				{
					$fields[] = [
						"label" => $field->name,
						"value" => $field->handle,
						"type"  => $fieldTypeClass,
					];
				}
			}

			$s["fields"] = $fields;

			$volumes[] = $s;
		}

		return \Craft::$app->view->renderTemplate('splash/settings', [
			'settings' => $this->getSettings(),
			'volumes' => $volumes,
		]);
	}

}