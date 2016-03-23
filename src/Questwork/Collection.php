<?php
namespace Questwork;

use ArrayIterator;
use IteratorAggregate;

class Collection implements IteratorAggregate, Interfaces\Collection
{
	protected $property;

	protected $instance;

	protected $data = [];

	public $length;

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

	public function load($fields = NULL, $order = NULL, $limit = NULL)
	{
		if (is_null($fields)) {
			$fields = $this->instance->getPrimary();
		}
		$attr = $this->attribute();
		$result = $this->connect()->select($fields, $attr['table'], $this->property, $order, $limit);
		$class = get_class($this->instance);
		foreach ($result as $item) {
			array_push($this->data, new $class($item, TRUE));
		}
		$this->length = count($this->data);
		return $this;
	}

	public function save($data = [])
	{
		/*
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
		$result = $this->connect()->query($command, array_merge($update, $this->property));
		*/
		$attr = $this->attribute();
		$fields = array_keys($this->first()->toArray());
		\ChromePhp::log($this->toArray(TRUE));
		$this->connect()->insert($attr['table'], $fields, $this->toArray(TRUE), TRUE);

		return $this;
	}

	public function delete()
	{
		$attr = $this->attribute();
		$result = $this->connect()->delete($attr['table'], $this->property);
		return $result;
	}

	public function toArray($deep = FALSE)
	{
		return $deep? json_decode(json_encode($this->data), TRUE) : $this->data;
	}

	public function find($condition)
	{
		if (!is_array($condition)) {
			$primary = $this->instance->getPrimary();
		}
		foreach ($this->data as $key => $value) {
			if (!is_array($condition) && $value->{$primary} == $condition) {
				return $value;
			}
		}
	}

	public function first()
	{
		return $this->data[0];
	}

	public function last()
	{
		return $this->data[$this->count()-1];
	}

	public function count()
	{
		return sizeof($this->data);
	}

	public function data()
	{
		return $this->data;
	}

	public function connect()
	{
		return $this->instance->connect();
	}

	public function attribute($key = NULL)
	{
		return $this->instance->getAttribute($key);
	}

	public function fromArray($dataSet)
	{
		$this->data = [];
		if (!empty($dataSet)) {
			$instance = $this->instance;
			foreach ($dataSet as $data) {
				array_push($this->data, new $instance($data));
			}
		}
		$this->length = $this->count();
		return $this;
	}
}