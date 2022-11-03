<?php
$path = $argv[1];
$property = $argv[2];

/**
 * Parse a json file and returns the content as a string.
 */
function parseJson(string $jsonFilePath): array
{
    $jsonContent = trim(file_get_contents($jsonFilePath));
    $jsonContent = (array)json_decode($jsonContent);

    return $jsonContent;
}

/**
 * Return a property from an array
 */
function getProperty(array $data, string $property): string
{
    $propertyList = explode('.', $property);
    $property = array_shift($propertyList);
    $nextProperties = implode('.', $propertyList);
    $value = 'null';

    if (isset($data[$property])) {
        $value = $data[$property];

        if (is_object($value)) {
            $value = (array)$value;
        }

        if (is_array($value)) {
            if (! empty($nextProperties)) {
                $value = getProperty($value, $nextProperties);
            } else {
                $value = implode(', ', $value);
            }
        }

        $value = (string)$value;
    }

    return $value;
}

$jsonContent = parseJson($path, $property);
echo getProperty($jsonContent, $property);
