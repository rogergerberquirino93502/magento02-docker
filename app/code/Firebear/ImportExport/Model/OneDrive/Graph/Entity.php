<?php

namespace Firebear\ImportExport\Model\OneDrive\Graph;

use Microsoft\Graph\Core\Enum;

/**
 * Class Entity
 * @package Firebear\ImportExport\Model\OneDrive\Graph
 */
class Entity implements \JsonSerializable
{
    /**
     * @var array
     */
    protected $properties;

    /**
     * Entity constructor.
     * @param array $properties
     */
    public function __construct($properties = [])
    {
        if (!is_array($properties)) {
            $properties = [];
        }
        $this->properties = $properties;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return mixed|null
     */
    public function getId()
    {
        if (array_key_exists("id", $this->properties)) {
            return $this->properties["id"];
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $serializableProperties = $this->getProperties();
        foreach ($serializableProperties as $property => $val) {
            if (is_a($val, \DateTime::class)) {
                $serializableProperties[$property] = $val->format(\DateTimeInterface::RFC3339);
            } elseif (is_a($val, Enum::class)) {
                $serializableProperties[$property] = $val->value();
            } elseif (is_a($val, Entity::class)) {
                $serializableProperties[$property] = $val->jsonSerialize();
            }
        }
        return $serializableProperties;
    }

    /**
     * @return mixed|null
     */
    public function getUploadUrl()
    {
        if (array_key_exists("uploadUrl", $this->properties)) {
            return $this->properties["uploadUrl"];
        } else {
            return null;
        }
    }

    /**
     * @return mixed|null
     */
    public function getName()
    {
        if (array_key_exists("name", $this->properties)) {
            return $this->properties["name"];
        } else {
            return null;
        }
    }

    /**
     * @param $val
     * @return $this
     */
    public function setName($val)
    {
        $this->properties["name"] = $val;
        return $this;
    }

    /**
     * @param $val
     * @return $this
     */
    public function setFileSize($val)
    {
        $this->properties["fileSize"] = $val;
        return $this;
    }
}
