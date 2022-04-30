<?php namespace peroks\plugin_customer\plugin_package;
/**
 * Implements the singleton pattern.
 *
 * @author Per Egil Roksvaag
 */
trait Singleton {
	/**
	 * @var static[] The class singletons.
	 */
	private static array $inst;

	/**
	 * @return static The class singleton.
	 */
	public static function instance(): object {
		$class = apply_filters( Plugin::FILTER_CLASS_CREATE, static::class );

		if ( empty( self::$inst[ $class ] ) ) {
			self::$inst[ $class ] = apply_filters( Plugin::FILTER_CLASS_CREATED, new $class(), $class, static::class );
		}
		return self::$inst[ $class ];
	}

	/**
	 * Protect constructor.
	 */
	protected function __construct() {}
}