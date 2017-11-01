<?php

namespace ether\splash\models;

use craft\base\Model;

class Settings extends Model {

	/**
	 * @var int
	 */
	public $volume;

	/**
	 * @var string|null
	 */
	public $authorField;

	/**
	 * @var string|null
	 */
	public $authorUrlField;

	/**
	 * @var string|null
	 */
	public $colorField;

	/**
	 * @return array
	 */
	public function rules () : array
	{
		return [
			['volume', 'required'],
			['volume', 'number'],
			[['authorField', 'authorUrlField', 'colorField'], 'string'],
		];
	}

}