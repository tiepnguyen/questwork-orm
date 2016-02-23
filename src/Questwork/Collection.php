<?php
namespace Questwork;

use ArrayIterator;
use IteratorAggregate;

class Collection implements IteratorAggregate, Interfaces\Collection
{
	protected $property;

	protected $instance;

	protected $data = [];

	public function __construct($class, $property)
	{
		$this->property = $property;
		$this->instance = $class;
	}

	public function __set($key, $value)
	{
		$this->property[$key] = $value;
	}

	public function getIterator()
	{
		return new ArrayIterator($this->data);
    }

	public function load($fields = '*', $order = NULL, $limit = NULL)
	{
		$attr = $this->attribute();
		$result = $this->connect()->select($attr['table'], $fields, $this->property, $order, $limit);
		$class = get_class($this->instance);
		foreach ($result as $item) {
			array_push($this->data, new $class($item, TRUE));
		}
		return $this;
	}

	public function update($data = [])
	{
		$attr = $this->attribute();
		$update = [];
		$command = 'UPDATE ' . $attr['table'];
		foreach ($data as $key => $value) {
			$updateKey = "_$key";
			$data[$key] = "$key = :$updateKey";
			$update[$updateKey] = $value;
		}
		$command .= "\nSET " . implode(', ', $data);
		$command .= $this->connect()->parseCondition($this->property);
		$result = $this->connect()->prepare($command, array_merge($update, $this->property));

		return $result->rowCount();
	}

	public function delete()
	{
		$attr = $this>attribute();
		$result = $this->connect()->delete($attr['table'], $this->property);
		return $result;
	}

	public function toArray($deep = FALSE)
	{
		return $deep? json_decode(json_encode($this->data), TRUE) : $this->data;
	}

	public function count()
	{
		return sizeof($this->data);
	}

	public function connect()
	{
		return $this->instance->connect();
	}

	public function attribute($key = NULL)
	{
		return $this->instance->getAttribute($key);
	}
}