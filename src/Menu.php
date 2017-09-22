<?php

namespace Mods\Menu;

use Illuminate\Support\Collection;
use Illuminate\Routing\UrlGenerator;

class Menu
{
	/**
	 * @var \Illuminate\Routing\UrlGenerator
	 */
	protected $url;

	/**
	 * @var \Illuminate\Support\Collection
	 */
	protected $items;

	/**
	 * @var int
	 */
	protected $lastId;
	
	/**
	 * Constructor.
	 *
	 * @param  \Illuminate\Routing\UrlGenerator  $url
	 */

	public function __construct(UrlGenerator $url)
	{
		$this->url = $url;
		$this->items  = new Collection;
	}

	/**
	 * Create a new menu instance.
	 *
	 * @param  string    $name
	 * @param  callable  $callback
	 * @return \Mods\Menu\Menu
	 */
	public function make()
	{
		return new static($this->url);
	}

	/**
	 * Add an item to the defined menu.
	 *
	 * @param  string       $uuid
	 * @param  string       $title
	 * @param  array|string $options
	 *
	 * @return Item
	 */
	public function add($uuid, $title, $options = '')
	{
		$item = new Item($this, $this->id(), $uuid, $title, $options);
		$this->items->push($item);
		$this->lastId = $item->id;
		return $item;
	}

	/**
	 * Generate a unique ID for every item added to the menu.
	 *
	 * @return int
	 */
	protected function id()
	{
		return $this->lastId + 1;
	}

	/**
     * Build an HTML attribute string from an array.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function attributes($attributes)
    {
        $html = [];
        foreach ((array) $attributes as $key => $value) {
            $element = $this->attributeElement($key, $value);
            if (! is_null($element)) {
                $html[] = $element;
            }
        }
        return count($html) > 0 ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Build a single attribute element.
     *
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    protected function attributeElement($key, $value)
    {
        // For numeric keys we will assume that the value is a boolean attribute
        // where the presence of the attribute represents a true value and the
        // absence represents a false value.
        // This will convert HTML attributes such as "required" to a correct
        // form instead of using incorrect numerics.
        if (is_numeric($key)) {
            return $value;
        }
        // Treat boolean attributes as HTML properties
        if (is_bool($value) && $key != 'value') {
            return $value ? $key : '';
        }
        if (! is_null($value)) {
            return $key . '="' . e($value) . '"';
        }
    }

    /**
	 * Format the groups class.
	 *
	 * @return mixed
	 */
	public static function formatGroupClass($new, $old)
	{
		if (isset($new['class'])) {
			$classes = trim(trim(array_get($old, 'class')).' '.trim(array_get($new, 'class')));
			return implode(' ', array_unique(explode(' ', $classes)));
		}
		return array_get($old, 'class');
	}

	/**
	 * Fetches and returns a menu item by it's slug.
	 *
	 * @param string $slug
	 *
	 * @return Item
	 */
	public function item($slug)
	{
		return $this->where('slug', $slug)->first();
	}

	/**
	 * Fetches and returns all menu items.
	 *
	 * @return Collection
	 */
	public function all()
	{
		return $this->items;
	}

	/**
	 * Fetches and returns the first menu item.
	 *
	 * @return Item
	 */
	public function first()
	{
		return $this->items->first();
	}

	/**
	 * Fetches and returns the last menu item.
	 *
	 * @return Item
	 */
	public function last()
	{
		return $this->items->last();
	}

	/**
	 * Fetches and returns all active state menu items.
	 *
	 * @return Collection
	 */
	public function active()
	{
		$activeItems = array();
		foreach ($this->items as $item) {
			if ($item->data('active')) {
				$activeItems[] = $item;
			}
		}
		return $activeItems;
	}


	/**
	 * Get the action type from the options.
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function dispatch($options)
	{
		if (isset($options['url'])) {
			return $this->getUrl($options);
		} elseif (isset($options['route'])) {
			return $this->getRoute($options['route']);
		} elseif (isset($options['action'])) {
			return $this->getAction($options['action']);
		}
		return null;
	}

	/**
	 * Get the action for a "url" option.
	 *
	 * @param array|string $options
	 *
	 * @return string
	 */
	protected function getUrl($options)
	{
		$url = $options['url'];
		if (self::isAbsolute($url)) {
			return $url;
		}
		$secure = (isset($options['secure']) and $options['secure'] === true) ? true : false;
		return $this->url->to($url, array(), $secure);
	}

	/**
	 * Get the route action for a "route" option.
	 *
	 * @param array|string $route
	 *
	 * @return string
	 */
	protected function getRoute($route)
	{
		if (is_array($route)) {
			return $this->url->route($route[0], array_slice($route, 1));
		}
		return $this->url->route($route);
	}

	/**
	 * Get the controller action for a "action" option.
	 *
	 * @param array|string $action
	 *
	 * @return string
	 */
	protected function getAction($action)
	{
		if (is_array($action)) {
			return $this->url->action($action[0], array_slice($action, 1));
		}
		return $this->url->action($action);
	}

	/**
	 * Determines if the given URL is absolute.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function isAbsolute($url)
	{
		return parse_url($url, PHP_URL_SCHEME) or false;
	}

	/**
	 * Fetches and returns all active state menu items.
	 *
	 * @param string  $attribute
	 * @param mixed  $value
	 * @return Collection
	 */
	public function where($attribute, $value)
	{
		return $this->items->filter(function($item) use ($attribute, $value) {
				if (isset($item->data[$attribute]) && $item->data[$attribute] == $value) {
					return true;
				}
				
				if (! property_exists($item, $attribute)) {
					return false;
				}
				if ($item->$attribute == $value) {
					return true;
				}
				return false;
		});
	}

	/**
	 * Renders the menu as an unordered list.
	 *
	 * @param  array  $attributes
	 * @return string
	 */
	public function asUl($attributes = array())
	{
		return "<ul{$this->attributes($attributes)}>{$this->render('ul')}</ul>";
	}

	/**
	 * Generate the menu items as list items, recursively.
	 *
	 * @param  string  $type
	 * @param  int     $parent
	 * @return string
	 */
	protected function render($type = 'ul', $parent = null)
	{
		$items   = '';
		$itemTag = in_array($type, ['ul', 'ol']) ? 'li' : $type;
		foreach ($this->where('parent', $parent) as $item) {
			$items .= "<{$itemTag}{$item->attributes()}>";
			if ($item->link) {
				$items .= "<a{$this->attributes($item->link->attr())} href=\"{$item->url()}\">{$item->title}</a>";
			} else {
				$items .= $item->title;
			}
			if ($item->hasChildren()) {
				$items .= "<{$type}>";
				$items .= $this->render($type, $item->id);
				$items .= "</{$type}>";
			}
			$items .= "</{$itemTag}>";
			if ($item->divider) {
				$items .= "<{$itemTag}{$this->attributes($item->divider)}></{$itemTag}>";
			}
		}
		return $items;
	}

	/**
	 * Filter menu items through a callback.
	 *
	 * Since menu items are stored as a collection, this will
	 * simply forward the callback to the Laravel Collection
	 * filter() method and return the results.
	 *
	 * @param callable $callback
	 *
	 * @return Builder
	 */
	public function filter($callback)
	{
		if (is_callable($callback)) {
			$this->items = $this->items->filter($callback);
		}
		return $this;
	}

	/**
	 * Returns menu item by name.
	 *
	 * @param string $property
	 *
	 * @return Item
	 */
	public function __get($property)
	{
		if (property_exists($this, $property)) {
			return $this->$property;
		}
		return $this->where('slug', $property)->first();
	}

}