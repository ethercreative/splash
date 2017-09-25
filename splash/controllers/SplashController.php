<?php

namespace Craft;

class SplashController extends BaseController {

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
		$this->requireAjaxRequest();
		$this->requirePostRequest();

		$image = craft()->request->getRequiredPost("image");
		$author = craft()->request->getRequiredPost("author");
		$authorUrl = craft()->request->getRequiredPost("authorUrl");
		$color = craft()->request->getRequiredPost("color");

		$this->returnErrorJson("NOPE");
	}

}
