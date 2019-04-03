<?php

namespace ether\splash;

use Craft;
use craft\base\FieldInterface;
use craft\base\FlysystemVolume;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\UrlManager;
use ether\splash\models\Settings;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;

class Splash extends Plugin {

	/**
	 * @var Splash
	 */
	public static $i;

	public $controllerNamespace = 'ether\\splash\\controllers';
	public $hasCpSection  = true;
	public $hasCpSettings = true;

	public $changelogUrl = 'https://raw.githubusercontent.com/ethercreative/splash/v3/CHANGELOG.md';
	public $downloadUrl = 'https://github.com/ethercreative/splash/archive/v3.zip';

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
			[$this, 'onRegisterCPUrlRules']
		);
	}

	/**
	 * @return Settings
	 */
	protected function createSettingsModel () : Settings
	{
		return new Settings();
	}

	/**
	 * @return string
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
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

			if ($volume->fieldLayoutId !== null)
			{
				/** @var FieldLayout $layout */
				$layout =
					\Craft::$app->fields->getLayoutById($volume->fieldLayoutId);

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
			}

			$s["fields"] = $fields;

			$volumes[] = $s;
		}

		return \Craft::$app->view->renderTemplate('splash/settings', [
			'settings' => $this->getSettings(),
			'volumes' => $volumes,
		]);
	}

	public function afterInstall ()
	{
		parent::afterInstall();

		if (Craft::$app->getRequest()->getIsConsoleRequest())
			return;

		Craft::$app->getResponse()->redirect(
			UrlHelper::cpUrl('settings/plugins/splash')
		)->send();
	}

	// Events
	// =========================================================================

	public function onRegisterCPUrlRules (RegisterUrlRulesEvent $event)
	{
		$event->rules['splash'] = 'splash/splash/index';
		$event->rules['POST splash/un'] = 'splash/splash/un';
		$event->rules['POST splash/dl'] = 'splash/splash/dl';
	}

}