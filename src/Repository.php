<?php

namespace DealNews\DataMapper;

use \DealNews\DataMapper\Interfaces\Mapper;

/**
 * DataMapper Repository
 *
 * Serves as a base repository for DataMappers
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     DataMapper
 */
class Repository extends \DealNews\Repository\Repository {

    /**
     * Keeps the list of mappers
     * @var array
     */
    protected $mappers = [];

    /**
     * Keeps the list of classes
     * @var array
     */
    protected $classes = [];

    /**
     * Creates the repository
     * @param array $mappers Array of mappers to add when creating the object
     */
    public function __construct(array $mappers = []) {
        if (!empty($mappers)) {
            foreach ($mappers as $name => $mapper) {
                $this->add_mapper($name, $mapper);
            }
        }
    }

    /**
     * Returns a new object of type $name
     *
     * @param string         $name   Mapped Object name
     */
    public function new(string $name) {
        if (!isset($this->classes[$name])) {
            throw new \LogicException("There is no class registered for `$name`", 1);
        }
        $class_name = $this->classes[$name];
        return new $class_name();
    }

    /**
     * Deletes an object from the repository and underlying storage via the mapper
     *
     * @param string         $name   Mapped Object name
     * @param string|int     $id     Unique identifier
     *
     * @return boolean
     */
    public function delete(string $name, $id) {
        if (!isset($this->classes[$name])) {
            throw new \LogicException("There is no class registered for `$name`", 2);
        }
        $class_name = $this->classes[$name];
        $mapper = $this->mappers[trim($class_name, '\\')];

        if (
            array_key_exists($name, $this->storage) &&
            array_key_exists($id, $this->storage[$name])
        ) {
            unset($this->storage[$name][$id]);
        }

        return $mapper->delete($id);
    }

    /**
     * Returns objects matching the filters
     *
     * @param  string $name    Mapped Object name
     * @param  array  $filters Array of filters. See \DealNews\DataMapper\AbstractMapper::find()
     *
     * @return boolean|array
     */
    public function find(string $name, array $filters) {
        if (!isset($this->classes[$name])) {
            throw new \LogicException("There is no class registered for `$name`", 3);
        }
        $class_name = $this->classes[$name];
        $mapper = $this->mappers[trim($class_name, '\\')];
        return $mapper->find($filters);
    }

    /**
     * Adds a mapper to the Repository
     *
     * @param string         $name   Mapped Object name
     * @param AbstractMapper $mapper Mapper object
     */
    public function add_mapper(string $name, Mapper $mapper) {
        $class_name = trim($mapper::get_mapped_class(), '\\');

        if (!class_exists($class_name)) {
            throw new \LogicException("Class `$class_name` not found for `$name`", 4);
        }

        $this->mappers[$class_name] = $mapper;
        $this->classes[$name] = $class_name;
        $this->register($name, [$mapper, "load_multi"], [$this, "mapper_save"]);
    }

    /**
     * Internal function to handle mapper saves
     *
     * @param  object $object The object to save
     * @return array|bool
     */
    protected function mapper_save($object) {
        $return = false;
        $class = trim(get_class($object), '\\');
        if (isset($this->mappers[$class])) {
            $object = $this->mappers[$class]->save($object);
            if ($object) {
                $return = [
                    $this->mappers[$class]->get_primary_key() => $object
                ];
            }
        }
        return $return;
    }
}
