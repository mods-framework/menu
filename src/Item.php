<?php

namespace Mods\Menu;

use Request;

class Item
{

	/**
	 * @var \Mods\Menu\Menu
	 */
	protected $menu;

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $slug;

	/**
	 * @var array
	 */
	public $divider = array();

	/**
	 * @var int
	 */
	public $parent;

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * @var array
	 */
	public $attributes = array();

	/**
	 * @var array
	 */
	protected $reserved = ['route', 'action', 'url', 'parent', 'secure'];

	/**
	 * Constructor.
	 *
	 * @param  \Mods\Menu\Menu  		  $menu
	 * @param  int                        $id
	 * @param  string                     $title
	 * @param  array|string               $options
	 */
	public function __construct($menu, $id, $uuid, $title, $options )
	{
		$this->menu    	  = $menu;
		$this->id         = $id;
		$this->title      = $title;
		$this->slug       = camel_case(str_slug($uuid, ' '));
		$this->attributes = $this->extractAttributes($options);
		$this->parent     = (is_array($options) and isset($options['parent'])) ? $options['parent'] : null;
		$this->configureLink($options);

		return $this;
	}


	/**
	 * Configures the link for the menu item.
	 *
	 * @param  array|string  $options
	 * @return null
	 */
	public function configureLink($options)
	{
		if (! is_array($options)) {
			$path = ['url' => $options];
		} else {
			$path = array_only($options, ['url', 'route', 'action', 'secure']);
		}		
		$this->link = new Link($path);
		$this->checkActiveStatus();
	}

	/**
	 * Adds a sub item to the menu.
	 * 
	 * @param  string        $uuid
	 * @param  string        $title
	 * @param  array|string  $options
	 * @return \Mods\Menu\Item
	 */
	public function add($uuid, $title, $options = '')
	{
		if (! is_array($options)) {
			$url  = $options;
			$options = [
				'url' => $url
			];
		}
		$options['parent'] = $this->id;
		return $this->menu->add($uuid, $title, $options);
	}


	/**
	 * Decide if the item should be active.
	 *
	 * @return null
	 */
	public function checkActiveStatus()
	{
		if ($this->url() == Request::url() || $this->url() == \URL::secure(Request::path())) {
			$this->activate();
		}
	}


	/**
	 * Generates a valid URL for the menu item.
	 *
	 * @return string
	 */
	public function url()
	{
		if (! is_null($this->link)) {
			return $this->menu->dispatch($this->link->path);
		}
	}

	/**
	 * Prepends HTML to the item.
	 *
	 * @param  string $html
	 * @return \Mods\Menu\Item
	 */
	public function prepend($html)
	{
		$this->title = $html.' '.$this->title;
		return $this;
	}

	/**
	 * Appends HTML to the item.
	 *
	 * @param  string $html
	 * @return \Mods\Menu\Item
	 */
	public function append($html)
	{
		$this->title = $this->title.' '.$html;
		return $this;
	}

	/**
	 * Insert a divider after the item.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function divide($attributes = array())
	{
		$attributes['class'] = $this->menu->formatGroupClass($attributes, ['class' => 'divider']);
		$this->divider = $attributes;
		return $this;
	}

	/**
	 * Determines if the menu item has children.
	 *
	 * @return bool
	 */
	public function hasChildren()
	{
		return count($this->menu->where('parent', $this->id)) or false;
	}

	/**
	 * Returns all children underneath the menu item.
	 *
	 * @return \Collection
	 */
	public function children()
	{
		return $this->menu->where('parent', $this->id);
	}


	/**
	 * Make given menu item active.
	 *
	 * @param Item|null $item
	 *
	 * @return Void
	 */
	public function activate(Item $item = null)
	{
		$item = (is_null($item)) ? $this : $item;
		$this->attributes['class'] = $this->menu->formatGroupClass(['class' => 'active'], $this->attributes);
		if (! is_null($this->link)) {
			$item->link->active();
		}
		$item->data('active', true);
		if ($item->parent) {
            $parent = $this->menu->where('id', $item->parent)->first();
            $parent->attributes['class'] = $parent->menu->formatGroupClass(['class' => 'opened'], $parent->attributes);
			$this->activate($parent);
		}
	}

	/**
	 * Activated pattern against other URI segments.
	 *
	 * @param string $pattern
	 *
	 * @return Item
	 */
	public function active($pattern = null)
	{
		if (! is_null($pattern)) {
			$pattern = ltrim(preg_replace('/\/\*/', '(/.*)?', $pattern), '/');
			if (preg_match("@{$pattern}\z@", Request::path())) {
				$this->activate();
			}
		}		
		return $this;
	}

	/**
	 * Fetch the formatted attributes for the item in HTML.
	 *
	 * @return string
	 */
	public function attributes()
	{
		return $this->menu->attributes($this->attributes);
	}

	/**
	 * Assign or fetch the desired attribute.
	 *
	 * @param  array|string  $attribute
	 * @return mixed
	 */
	public function attribute($attribute)
	{
		if (isset($attribute) and is_array($attribute)) {
			if (array_key_exists('class', $attribute)) {
				$this->attributes['class'] = $this->menu->formatGroupClass(['class' => $attribute['class']], $this->attributes);
				unset ($attribute['class']);
			}
			$this->attributes = array_merge($this->attributes, $attribute);
			return $this;
		}
		return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
	}


	/**
	 * Extract the valid attributes from the passed options.
	 *
	 * @param mixed $options
	 *
	 * @return array
	 */
	public function extractAttributes($options)
	{
		if (is_array($options)) {
			return array_except($options, $this->reserved);
		}
		return array();
	}

	/**
	 * Set or get an item's metadata.
	 *
	 * @param  mixed
	 * @return string|\Mods\Menu\Item
	 */
	public function data()
	{
		$args = func_get_args();
		if (isset($args[0]) and is_array($args[0])) {
			$this->data = array_merge($this->data, array_change_key_case($args[0]));
			return $this;
		} elseif (isset($args[0]) and isset($args[1])) {
			$this->data[strtolower($args[0])] = $args[1];
			return $this;
		} elseif (isset($args[0])) {
			return isset($this->data[$args[0]]) ? $this->data[$args[0]] : null;
		}
		return $this->data;
	}
}