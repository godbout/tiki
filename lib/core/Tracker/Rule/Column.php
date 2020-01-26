<?php


namespace Tiki\Lib\core\Tracker\Rule;

abstract class Column
{
	/** @var string */
	protected $label;
	/** @var string */
	protected $type;

	/**
	 * Column constructor.
	 *
	 * @param string $label
	 * @param string $type
	 */
	public function __construct($label, $type)
	{
		$this->label = $label;
		$this->type  = $type;
	}

	/**
	 * Returns the class name as the id
	 *
	 * @return false|string
	 */
	public function getId() {
		try {
			$reflection = new \ReflectionClass($this);
			$name = $reflection->getName();
		} catch (\ReflectionException $e) {
			\Feedback::error(tr('Rules reflection error: %0', $e->getMessage()));
			$name = 'error';
		}
		return substr($name, strrpos($name, '\\') + 1);
	}

	abstract public function get();
}
