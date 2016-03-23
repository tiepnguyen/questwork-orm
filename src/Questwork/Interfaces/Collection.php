<?php
namespace Questwork\Interfaces;

interface Collection
{
	public function getIterator();

	public function load($fields, $order, $limit);

	public function save($data);

	public function delete();

	public function toArray($deep);

	public function count();

	public function connect();

	public function attribute($key);
}