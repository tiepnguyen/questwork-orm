<?php
namespace Questwork;

class Model implements Interfaces\Model
{
    protected static $table;

    protected static $primary;

    private $_loaded = FALSE;

    public function __construct($data = [], $loaded = FALSE)
    {
        $this->_loaded = $loaded;
        if (!static::$table) {
            $className = get_class($this);
            static::$table = strtolower(substr($className, strrpos($className, '\\') +1));
        }
        if (!static::$primary) {
            static::$primary = 'id';
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        } else {
            $this->{static::$primary} = $data;
        }
    }

    public function __toString()
    {
        return (string) $this->{static::$primary};
    }

    public function property($key = NULL, $value = NULL)
    {
        if (is_array($key)) {
            foreach ($key as $prop => $value) {
                $this->{$prop} = $value;
            }
        } else if (is_string($key)) {
            if (is_null($value)) {
                return isset($this->{$key}) ? $this->{$key} : NULL;
            } else {
                $this->{$key} = $value;
            }
        }
        else {
            return get_object_vars($this);
        }
        return $this;
    }

    public function load($fields = NULL)
    {
        if (is_null($fields)) {
            $fields = static::$primary;
        } elseif (is_array($fields)) {
            $fields = implode(', ', $fields);
        }
        $command = "
            SELECT $fields FROM
            " . static::$table;
        if ($this->{static::$primary}) {
            $command .= " WHERE " . static::$primary . " = :" . static::$primary;
            $result = $this->connect()->query($command, [static::$primary => $this->{static::$primary}]);
        } elseif (!empty($properties = $this->toArray($this))) {
            $condition = [];
            foreach ($properties as $key => $value) {
                array_push($condition, $key . " = :" . $key);
            }
            $command .= " WHERE " . implode(' AND ', $condition);
            $result = $this->connect()->query($command, $properties);
        }
        if ($result->rowCount()) {
            $this->_loaded = TRUE;
            $result->setFetchMode(\PDO::FETCH_INTO, $this);
            $result->fetch();
        }
        return $this;
    }

    public function save()
    {
        if ($this->isLoaded()) {
            $updateData = $this->toArray();
            unset($updateData[static::$primary]);
            $result = $this->connect()->update(static::$table, $updateData, [static::$primary => $this->{static::$primary}]);
        } else {
            $result = $this->connect()->insert(static::$table, $this->toArray());
        }
        return $result;
    }

    public function delete()
    {
        $result = $this->connect()->delete(static::$table, [static::$primary => $this->{static::$primary}]);
        return $result;
    }

    public function connect()
    {
        trigger_error('Connect method of ' . get_called_class() . ' is not implemented');
        throw new Exception('Connect method of ' . get_called_class() . ' is not implemented');
    }

    public function getAttribute($key = NULL)
    {
        return is_null($key) ? ['table' => static::$table, 'primary' => static::$primary] : static::$attribute[$key];
    }

    public function getTable()
    {
        return static::$table;
    }

    public function getPrimary()
    {
        return static::$primary;
    }

    public function primary()
    {
        return $this->connect()->primary(static::$table);
    }

    public function isLoaded()
    {
        return $this->_loaded;
    }

    public function toArray()
    {
        return json_decode(json_encode($this), TRUE);
    }

    public static function instance($data = [])
    {
        $className = get_called_class();
        return new $className($data);
    }

    public static function collection($property = [])
    {
        $className = get_called_class();
        return new Collection(new $className, $property);
    }

    public static function find($condition = [], $order = NULL, $limit = NULL)
    {
        return self::collection($condition)->load(NULL, $order, $limit)->toArray();
    }

    public static function findOne($condition)
    {
        $result = self::instance($condition)->load();
        if ($result->isLoaded()) {
            return $result;
        } else {
            return NULL;
        }
    }
}
