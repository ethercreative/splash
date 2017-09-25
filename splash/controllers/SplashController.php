<?php

namespace Craft;

class SplashController extends BaseController {

	// Variables
	// =========================================================================

	private $_settings;

	// Actions
	// =========================================================================

	public function actionIndex ()
	{
		craft()->templates->includeCssResource("splash/css/splash.css");
		craft()->templates->includeJsResource("splash/js/splash.min.js");
		craft()->templates->includeJs("new Splash();");

		return $this->renderTemplate("splash/index");
	}

	public function actionUn ()
	{
		$this->requireAjaxRequest();
		$this->requirePostRequest();

		$page = craft()->request->getRequiredPost("page");
		$query = mb_strtolower(craft()->request->getRequiredPost("query"));
		$per_page = 30;
		$clientId = "a0c361d345497721f4a72ec4f8582ce0ab5c8328aed22b80f77aef3dc467180c";

		$endPoint = $query ? "search/photos" : "photos";
		$params = http_build_query(
			compact("page", "query", "per_page")
		);

		$cacheName = "splash!" . $page . preg_replace("/\s+/", "", $query);

		if ($r = craft()->cache->get($cacheName)) {
			$this->returnJson($r);
			return;
		}

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

		$images = JsonHelper::decode($images);
		if (is_array($images) && array_key_exists("results", $images)) {
			$images = $images["results"];
		}

		if ($images == null) {
			SplashPlugin::log("https://api.unsplash.com/" . $endPoint . "?" . $params);
			SplashPlugin::log(print_r($response, true));
		}

		$r = compact("images", "totalPages");
		if (count($images)) craft()->cache->set($cacheName, $r);
		$this->returnJson($r);
	}

	public function actionDl ()
	{
		$this->getSettings();
		$this->requireAjaxRequest();
		$this->requirePostRequest();

		$id = craft()->request->getRequiredPost("id");
		$image = craft()->request->getRequiredPost("image");
		$author = craft()->request->getRequiredPost("author");
		$authorUrl = craft()->request->getRequiredPost("authorUrl");
		$color = craft()->request->getRequiredPost("color");

		$sourceId = $this->_settings["source"];
		$authorField = $this->_settings["authorField"];
		$authorUrlField = $this->_settings["authorUrlField"];
		$colorField = $this->_settings["colorField"];

		if (!$sourceId) {
			$this->returnErrorJson("Missing Upload source (see plugin settings).");
		}

		$folderId = craft()->assets->getRootFolderBySourceId($sourceId)->id;
		$fileName = $id . ".jpg";
		$tempPath = craft()->path->getTempUploadsPath();
		$tempLocation = $tempPath . $fileName;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $image);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$image = curl_exec($ch);
		curl_close($ch);

		file_put_contents($tempLocation, $image);
		$asset = craft()->assets->insertFileByLocalPath(
			$tempLocation,
			$fileName,
			$folderId,
			AssetConflictResolution::Replace
		);

		if ($asset->hasErrors()) {
			$this->removeTemp($tempLocation);
			$this->returnJson([
				"errors" => $asset->getErrors(),
			]);
		}

		$asset = craft()->assets->getFileById($asset->getDataItem('fileId'));

		$content = [];

		if ($authorField) $content[$authorField] = $author;
		if ($authorUrlField) $content[$authorUrlField] = $authorUrl;
		if ($colorField) $content[$colorField] = $color;

		$asset->setContentFromPost($content);

		if (craft()->assets->storeFile($asset)) {
			$this->removeTemp($tempLocation);
			$this->returnJson(["success" => true]);
		} else {
			$this->removeTemp($tempLocation);
			$this->returnJson([
				"errors" => $asset->getErrors(),
			]);
		}
	}

	// Helpers
	// =========================================================================

	private function getSettings ()
	{
		$this->_settings = craft()->plugins->getPlugin("splash")->getSettings();
	}

	private function removeTemp ($tempFile)
	{
		if (file_exists($tempFile)) unlink($tempFile);
	}

}
