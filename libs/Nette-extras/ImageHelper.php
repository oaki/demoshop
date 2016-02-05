<?php

/**
 * Format class
 *
 * @author Pavol Bincik
 */
final class ImageHelper
{

	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new LogicException("Cannot instantiate static class " . get_class($this));
	}

	/**
	 * Loads static helper
	 * Register with Template in for example BasePresenter->createTemplate()
	 *
	 * @param string $helper
	 * @return NCallback|null
	 */
	public static function loadHelper($helper)
    {
        $callback = callback(__CLASS__, $helper);
        return ($callback->isCallable()) ? $callback : NULL;
    }

	/**
	 * Formats and splits given string according to $lengths and $functions
	 *
	 * @param string $subject
	 * @param array|int $lengths
	 * @param string $delimiter
	 * @param array $functions
	 * @return string
	 */
	static public function img($image_array, $width, $height, $flags = 0, $dir = 'dir', $mode = 'full_path') {
		return Files::gURL($image_array['src'], $image_array['ext'], $width, $height, $flags, $dir, $mode);
	}
}