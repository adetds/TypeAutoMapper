<?php

namespace Tds\TypeAutoMapper;

use Jasny\PhpdocParser\PhpdocParser;
use Jasny\PhpdocParser\Set\PhpDocumentor;

/**
 * Generic Type Mapper
 *
 */
class TypeMapper
{
    public const API_DATE_FORMAT = 'Y-m-d\TH:i:s';

    /**
     * Map an object (stdClass) to another type
     *
     * Properties of the input object are checked against the target class and copied over, with types if available,
     * into a new instance of the target class.
     *
     * @param string $targetClass
     * @param object $inputObject
     * @param null $unpackFunction
     * @return mixed
     */
    public function mapObjectToType(string $targetClass, object $inputObject, $unpackFunction = null) : mixed
    {
        $mappedInstance = new $targetClass;

        // Parse type using reflection & cache it
        $index = $this->parseTypeClass($mappedInstance);

        // Loop through object - property types will be an array, std object or simple
        foreach($inputObject as $key => $value) {
            if (array_key_exists($key, $index)) {
                $mappedInstance->$key = $this->mapValue($index[$key], $value);
            }
        }

        return $mappedInstance;
    }


    /**
     * Map a value to a type. Recursion for arrays and classes
     *
     * Mapping function looks for specific scalars or types that exist anything else
     * will be ignored.
     *
     * @param string $type
     * @param $value
     * @return array|bool|\DateTime|int|mixed|string
     */
    private function mapValue(string $type, $value)
    {
        $mappedValue = null;

        switch ($type):
            case "array":
                if (is_array($value)) {
                    $mappedValue = [];
                    foreach ($value as $k => $v) {
                        if (gettype($v) == 'object' || gettype($v) == 'array') {
                            $mappedValue[] = $this->mapValue(gettype($v), $v);
                        } else {
                            $mappedValue[] = $v;
                        }
                    }
                }
                break;
            case "string":
                $mappedValue = (is_scalar($value) || $value === null) ? (string) $value : null;
                break;
            case "int":
                $mappedValue = (is_scalar($value) || $value === null) ? (int) $value : null;
                break;
            case "DateTime":
                $mappedValue = \DateTime::createFromFormat(self::API_DATE_FORMAT, $value);
                break;
            default:
                // 1 - This is an array of types - recurse
                if (str_ends_with($type, '[]')) {
                    $type = substr($type,0, -2);
                    $type = str_starts_with($type,'\\') ? $type : '\\' . $type;
                    $mappedValue = [];
                    foreach($value as $k => $v) {
                        $mappedValue[] = $this->mapObjectToType($type, $v);
                    }
                    break;
                }

                // 2- Straight Type
                $type = str_starts_with($type,'\\') ? $type : '\\' . $type;
                if (class_exists($type)) {
                    $mappedValue = $this->mapObjectToType($type, $value);
                } else {
                    $mappedValue = (string) $value;
                }
                break;
        endswitch;

        return $mappedValue;


    }


    /**
     * Analyse class and build index of properties and their types
     *
     * index can be cached for future cycles
     *
     * @param object $type
     * @return array [name] = type
     */
    private function parseTypeClass(object $type)
    {
        $index = [];
        $classInfo = new \ReflectionClass($type);

        foreach ($classInfo->getProperties() as $reflectionProperty) {

            // Index
            if ($reflectionProperty->getType()) {
                //var_dump($reflectionProperty->getType()->getName());
                $index[$reflectionProperty->getName()] = $reflectionProperty->getType()->getName();

            } else {

                // No Type detected so use the docComments to see if one is declared
                $doc = $reflectionProperty->getDocComment();
                $tags = PhpDocumentor::tags();
                $parser = new PhpdocParser($tags);
                $meta = $parser->parse($doc);
                
                if (array_key_exists('var', $meta)) {
                    if (str_ends_with($meta['var']['type'], '[]')) {
                        $class = substr($meta['var']['type'],0,-2);
                        $class = str_starts_with($class,'\\') ? $class : '\\' . $class;
                        if (class_exists($class)) {
                            $index[$reflectionProperty->getName()] = $class . '[]';
                        }
                    } else {
                        if (class_exists($meta['var']['type'])) {
                            $index[$reflectionProperty->getName()] = $meta['var']['type'];
                        }
                    }
                } else {
                    // Default string
                    $index[$reflectionProperty->getName()]  = 'string';
                }
            }
        }

        return $index;
    }

}
