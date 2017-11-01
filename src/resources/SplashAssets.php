<?php

namespace ether\splash\resources;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class SplashAssets extends AssetBundle
{

	public function init ()
	{
		$this->sourcePath = '@ether/splash/resources';

		$this->depends = [
			CpAsset::class,
		];

		$this->js = [
			'js/splash.min.js',
		];

		$this->css = [
			'css/splash.css',
		];

		parent::init();
	}

}