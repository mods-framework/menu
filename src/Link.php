<?php

namespace Mods\Menu;

class Link
{
	/**
	 * @var array
	 */
	protected $path = array();

	/**
	 * @var string
	 */
	protected $href;

	/**
	 * @var array
	 */
	public $attributes = array();

	/**
	 * Constructor.
	 *
	 * @param array  $path
	 */
	public function __construct($path = array())
	{
		$this->path = $path;
	}

	/**
	 * Make the link active.
	 *
	 * @return \Mods\Menu\Link
	 */
	public function active()
	{
		$this->attributes['class'] = Menu::formatGroupClass(['class' => 'active'], $this->attributes);
		return $this;
	}

	/**
	 * Add attributes to the link.
	 *
	 * @param  mixed
	 * @return \Mods\Menu\Link|string
	 */
	public function attr()
	{
		$args = func_get_args();
		if (isset($args[0]) and is_array($args[0])) {
			$this->attributes = array_merge($this->attributes, $args[0]);
			return $this;
		} elseif (isset($args[0]) and isset($args[1])) {
			$this->attributes[$args[0]] = $args[1];
			return $this;
		} elseif (isset($args[0])) {
			return isset($this->attributes[$args[0]]) ? $this->attributes[$args[0]] : null;
		}
		return $this->attributes;
	}

	/**
	 * Dynamically retrieve property value.
	 *
	 * @param  string  $property
	 * @return mixed
	 */
	public function __get($property)
	{	
		if (property_exists($this, $property)) {
			return $this->$property;
		}	
		return $this->attr($property);
	}
}