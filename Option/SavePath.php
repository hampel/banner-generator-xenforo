<?php namespace Hampel\BannerGenerator\Option;

use XF\Option\AbstractOption;

class SavePath extends AbstractOption
{
	/**
	 * @return string|null
	 */
	public static function get()
	{
		return \XF::options()->hampelBannerGeneratorSavePath;
	}
}