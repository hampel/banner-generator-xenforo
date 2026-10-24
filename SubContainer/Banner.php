<?php namespace Hampel\BannerGenerator\SubContainer;

use Hampel\BannerGenerator\Option\DefaultColour;
use Hampel\BannerGenerator\Option\SavePath;
use XF\SubContainer\AbstractSubContainer;
use XF\Util\File;

/**
 * Types are declared by docblock only: the class is extendable through extendClass(), and a native
 * type added here would be a fatal error in a subclass already written without it.
 *
 * @phpstan-type Rgb array{int<0, 255>, int<0, 255>, int<0, 255>}
 * @phpstan-type Colour array{background: Rgb, text: Rgb, phrase: string}
 */
class Banner extends AbstractSubContainer
{
	/**
	 * @return void
	 */
	public function initialize()
	{
		$container = $this->container;

		$container['colours'] = function($c)
		{
			return [
				'black' => [
					'background' => [0, 0, 0],
					'text' => [255, 255, 255],
					'phrase' => 'hampel_bannergenerator_colour_black'
				],
				'red' => [
					'background' => [255, 0, 0],
					'text' => [255, 255, 255],
					'phrase' => 'hampel_bannergenerator_colour_red'
				],
				'green' => [
					'background' => [0, 255, 0],
					'text' => [0, 0, 0],
					'phrase' => 'hampel_bannergenerator_colour_green'
				],
				'blue' => [
					'background' => [0, 0, 255],
					'text' => [255, 255, 255],
					'phrase' => 'hampel_bannergenerator_colour_blue'
				],
				'yellow' => [
					'background' => [255, 255, 0],
					'text' => [0, 0, 0],
					'phrase' => 'hampel_bannergenerator_colour_yellow'
				],
				'cyan' => [
					'background' => [0, 255, 255],
					'text' => [0, 0, 0],
					'phrase' => 'hampel_bannergenerator_colour_cyan'
				],
				'magenta' => [
					'background' => [255, 0, 255],
					'text' => [0, 0, 0],
					'phrase' => 'hampel_bannergenerator_colour_magenta'
				],
				'grey' => [
					'background' => [127, 127, 127],
					'text' => [255, 255, 255],
					'phrase' => 'hampel_bannergenerator_colour_grey'
				],
				'white' => [
					'background' => [255, 255, 255],
					'text' => [0, 0, 0],
					'phrase' => 'hampel_bannergenerator_colour_white'
				],
			];
		};
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @param string|null $colourKey the default colour when empty
	 * @param bool $force
	 *
	 * @return string|null the path written, "" when the banner already exists, null on error
	 */
	public function generateBanner($width, $height, $colourKey = '', $force = false)
	{
		if (!$this->isValidSize($width, $height))
		{
			\XF::logError("Invalid dimensions specified for banner test: {$width}x{$height}");
			return null;
		}

		if (empty($colourKey))
		{
			$colourKey = DefaultColour::get();
		}

		if (!$colour = $this->getColour($colourKey))
		{
			\XF::logError("Invalid colour specified for banner test: {$colourKey}");
			return null;
		}

		$fs = $this->app->fs();

		$destImage = $this->getAbstractedDataPath($width, $height, $colourKey);

		if ($fs->has($destImage) && !$force)
		{
			return ""; // already have a banner generated
		}

		return $this->_generateBanner($width, $height, $colour, $destImage);
	}

	/**
	 * @param int<1, max> $width
	 * @param int<1, max> $height
	 * @param Colour $colour
	 * @param string $dest
	 *
	 * @return string|null
	 */
	protected function _generateBanner($width, $height, array $colour, $dest)
	{
		$im = imagecreate($width, $height);
		if ($im === false)
		{
			\XF::logError("Could not generate image for banner test - imagecreate returned false");
			return null;
		}

		$background_color = imagecolorallocate($im, $colour['background'][0], $colour['background'][1], $colour['background'][2]);
		$text_color = imagecolorallocate($im, $colour['text'][0], $colour['text'][1], $colour['text'][2]);
		if ($background_color === false || $text_color === false)
		{
			\XF::logError("Could not generate image for banner test - imagecolorallocate returned false");
			return null;
		}

		imagestring($im, 5, 5, 5,  sprintf("%dx%d", $width, $height), $text_color);

		/** @var string $tempFile core declares no return type */
		$tempFile = File::getTempFile();

		imagepng($im, $tempFile);

		if (PHP_VERSION_ID < 80000)
		{
			imagedestroy($im); // a no-op since PHP 8.0, and deprecated in 8.5
		}

		File::copyFileToAbstractedPath($tempFile, $dest);

		return $dest;
	}

	/**
	 * @param int|string $width
	 * @param int|string $height
	 *
	 * @return bool
	 *
	 * @phpstan-assert-if-true int<1, max>|non-empty-string $width
	 * @phpstan-assert-if-true int<1, max>|non-empty-string $height
	 */
	public function isValidSize($width, $height)
	{
		return intval($width) > 0 && intval($height) > 0;
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @param string|null $colour the default colour when empty
	 *
	 * @return string
	 */
	public function getAbstractedDataPath($width, $height, $colour = '')
	{
		if (empty($colour))
		{
			$colour = DefaultColour::get();
		}

		return sprintf('data://%s/%dx%d-%s.png', SavePath::get(), $width, $height, $colour);
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @param string|null $colour the default colour when empty
	 *
	 * @return string
	 */
	public function getBannerUrl($width, $height, $colour = '')
	{
		if (empty($colour))
		{
			$colour = DefaultColour::get();
		}

		/** @var string $url core declares no return type */
		$url = \XF::app()->applyExternalDataUrl(sprintf('%s/%dx%d-%s.png', SavePath::get(), $width, $height, $colour));

		return $url;
	}

	/**
	 * @return array<string, Colour>
	 */
	public function getColours()
	{
		/** @var array<string, Colour> $colours */
		$colours = $this->container['colours'];

		return $colours;
	}

	/**
	 * @return array<string, \XF\Phrase>
	 */
	public function getColoursForSelect()
	{
		$choices = [];
		foreach ($this->getColours() as $key => $colour)
		{
			$choices[$key] = \XF::phrase($colour['phrase']);
		}

		return $choices;
	}

	/**
	 * @return list<string>
	 */
	public function getColourKeys()
	{
		return array_keys($this->getColours());
	}

	/**
	 * @param string|int|null $colour
	 *
	 * @return Colour|null
	 */
	public function getColour($colour)
	{
		return $this->getColours()[$colour] ?? null;
	}
}
