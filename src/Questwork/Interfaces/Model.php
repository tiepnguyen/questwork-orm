<?php
namespace Questwork\Interfaces;

interface Model
{
	public function load($fields);

	public function save();

	public function delete();

	public function connect();

	public function getAttribute($key);

	public function getTable();

	public function getPrimary();

	public function isLoaded();

	public function toArray();

	public static function instance($data);

	public static function collection($attr);
}