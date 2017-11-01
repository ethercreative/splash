<?php

namespace ether\splash\controllers;

use craft\elements\Asset;
use craft\web\AssetManager;
use craft\web\Controller;
use ether\splash\Plugin;
use ether\splash\resources\SplashAssets;
use function Sodium\crypto_aead_aes256gcm_decrypt;
use yii\helpers\Json;

class SplashController extends Controller {

	// Variables
	// =========================================================================

	private $_settings;

	// Actions
	// =========================================================================

	public function actionIndex ()
	{
		$this->view->registerAssetBundle(SplashAssets::class);
		$this->view->registerJs('new Splash();');
		$this->renderTemplate('splash/index');
	}

	public function actionUn ()
	{
		$request = \Craft::$app->request;
		$page = $request->getRequiredBodyParam('page');
		$query = mb_strtolower($request->getBodyParam('query', ''));
		$per_page = 30;
		$clientId = 'a0c361d345497721f4a72ec4f8582ce0ab5c8328aed22b80f77aef3dc467180c';

		$endPoint = $query ? 'search/photos' : 'photos';
		$params = http_build_query(
			compact('page', 'query', 'per_page')
		);

		$cacheName = 'splash!' . $page . preg_replace('/\s+/', '', $query);

		if ($r = \Craft::$app->cache->get($cacheName))
			return $this->asJson($r);

		$ch = curl_init();
		curl_setopt(
			$ch,
			CURLOPT_URL,
			"https://api.unsplash.com/" . $endPoint . "?" . $params
		);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Accept-Version: v1",
			"Authorization: Client-ID " . $clientId,
		]);
		$response = curl_exec($ch);
		curl_close($ch);

		list($headers, $images) = explode("\r\n\r\n", $response, 2);

		$headers = explode("\n", $headers);
		$total = 0;
		foreach($headers as $header) {
			if (stripos($header, 'X-Total:') !== false) {
				$total = (int) str_replace("X-Total: ", "", $header);
				break;
			}
		}
		$totalPages = ceil($total / $per_page);

		$images = Json::decode($images);
		if (is_array($images) && array_key_exists("results", $images)) {
			$images = $images["results"];
		}

		if ($images == null) {
			\Craft::info("https://api.unsplash.com/$endPoint?$params", 'Splash');
			\Craft::info(print_r($response, true), 'Splash');
		}

		$r = compact("images", "totalPages");
		if (count($images)) \Craft::$app->cache->set($cacheName, $r);
		return $this->asJson($r);
	}

	public function actionDl ()
	{
		$this->getSettings();
		$request = \Craft::$app->request;

		$id = $request->getRequiredBodyParam('id');
		$image = $request->getRequiredBodyParam('image');
		$author = $request->getRequiredBodyParam('author');
		$authorUrl = $request->getRequiredBodyParam('authorUrl');
		$color = $request->getRequiredBodyParam('color');
		$query = $request->getRequiredBodyParam('query');

		$volumeId = $this->_settings['volume'];
		$authorField = $this->_settings['authorField'];
		$authorUrlField = $this->_settings['authorUrlField'];
		$colorField = $this->_settings['colorField'];

		if (!$volumeId)
			return $this->asErrorJson(
				'Missing Upload volume (see plugin settings).'
			);

		$folder = \Craft::$app->assets->getRootFolderByVolumeId($volumeId);
		$fileName = $id . ".jpg";
		$tempPath = \Craft::$app->path->getTempPath();
		$tempLocation = $tempPath . $fileName;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $image);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$image = curl_exec($ch);
		curl_close($ch);

		file_put_contents($tempLocation, $image);

		$asset = new Asset();
		$asset->tempFilePath = $tempLocation;
		$asset->filename = $fileName;
		$asset->newFolderId = $folder->id;
		$asset->volumeId = $volumeId;
		$asset->avoidFilenameConflicts = true;
		$asset->setScenario(Asset::SCENARIO_CREATE);

		$result = \Craft::$app->getElements()->saveElement($asset);
	}

	// Helpers
	// =========================================================================

	private function getSettings ()
	{
		$this->_settings = Plugin::$i->getSettings();
	}

	private function removeTemp ($tempFile)
	{
		if (file_exists($tempFile))
			unlink($tempFile);
	}

}