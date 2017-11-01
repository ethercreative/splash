<?php

namespace ether\splash;

use craft\base\FlysystemVolume;

class Volume extends FlysystemVolume {

	// Static
	// =========================================================================
	
	public static function displayName () : string
	{
		return 'Unsplash';
	}

	// Variables
	// =========================================================================

	protected $isVolumeLocal = false;
	
	// Public
	// =========================================================================

	/**
	 * Creates and returns a Flysystem adapter instance based on the stored settings.
	 *
	 * @return \League\Flysystem\AdapterInterface The Flysystem adapter.
	 */
	protected function createAdapter ()
	{
		// TODO: Implement createAdapter() method.
	}

	/**
	 * Returns the URL to the source, if it’s accessible via HTTP traffic.
	 *
	 * @return string|false The root URL, or `false` if there isn’t one
	 */
	public function getRootUrl ()
	{
		// TODO: Implement getRootUrl() method.
	}

}