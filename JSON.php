<?php

namespace Aziraphale\json;

use RuntimeException;

class JSON
{
    /**
     * Returns the input string but truncated to the specified maximum length, appending the $appendIfTruncated value
     *  if set (taking into account the length of $appendIfTruncated, so the resulting string will never be longer
     *  than $maxLength). $wasTruncated gets set accordingly, so can be passed as an reference if required.
     *
     * @param string $input
     * @param int    $maxLength
     * @param string $appendIfTruncated
     * @param bool   $wasTruncated
     * @return string
     */
    private static function truncateIfLong($input, $maxLength = 100, $appendIfTruncated = '…', &$wasTruncated = false)
    {
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength - strlen($appendIfTruncated)) . $appendIfTruncated;
            $wasTruncated = true;
        }
        return $input;
    }

    /**
     * Returns the output of running var_dump() on $input as a string instead of outputting it directly to STDOUT
     *  (e.g. the browser or commandline). Sort of a bit like print_r(), but with more verbose, useful information.
     *
     * @param mixed $input
     * @return string
     */
    private static function var_dump_r($input)
    {
        ob_start();
        var_dump($input);
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    /**
     * Combines json_decode() with file_get_contents()
     *
     * @param string $filename
     * @param bool   $assoc
     * @param int    $depth
     * @param int    $options
     * @return mixed
     * @throws \RuntimeException
     */
    public static function getContents($filename, $assoc = false, $depth = 512, $options = 0)
    {
        $encodedJson = file_get_contents($filename, false);
        if ($encodedJson === false) {
            throw new RuntimeException(
                sprintf(
                    'Failed to read supplied JSON file `%s`.',
                    $filename
                )
            );
        }

        $jsonObject = json_decode($encodedJson, $assoc, $depth, $options);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                sprintf(
                    'json_decode() returned an error: `%s` while attempting to decode the contents of `%s`.',
                    json_last_error_msg(),
                    $filename
                )
            );
        }

        return $jsonObject;
    }

    /**
     * Wraps json_encode() and file_put_contents() to encode a variable as JSON and immediately write it to a file,
     *  throwing a RuntimeException if either the encoding or the file-writing process fails.
     *
     * @param mixed  $inputValue
     * @param string $filename
     * @param int    $jsonOptions
     * @param int    $depth
     */
    public static function putContents($inputValue, $filename, $jsonOptions = null, $depth = 512)
    {
        $encodedJson = static::encode($inputValue, $jsonOptions, $depth);

        if (!file_put_contents($filename, $encodedJson)) {
            throw new RuntimeException("Failed to write the encoded JSON to file `$filename`.");
        }
    }

    /**
     * Wraps json_decode() together with some error-handling: a RuntimeException will be thrown if decoding fails
     *
     * @param string $jsonString
     * @param bool   $assoc
     * @param int    $depth
     * @param int    $options
     * @return mixed
     * @throws \RuntimeException
     */
    public static function decode($jsonString, $assoc = false, $depth = 512, $options = 0)
    {
        $jsonObject = json_decode($jsonString, $assoc, $depth, $options);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                sprintf(
                    'json_decode() returned an error: `%s` while attempting to decode string `%s`.',
                    json_last_error_msg(),
                    static::truncateIfLong($jsonString, 100, '…')
                )
            );
        }

        return $jsonObject;
    }

    /**
     * A wrapper for json_encode() which defaults to a lot of very sensible options:
     *  - An empty [root] array will be encoded as an empty object,
     *  - Bigints will be encoded as strings,
     *  - Forward-slashes won't be escaped,
     *  - Unicode won't be escaped (we return a UTF-8 string, after all!),
     *  - Floats with a zero 'fractional part' will still be encoded as floats rather than ints (PHP 5.6.6+)
     * Also includes minor error-handling: when encoding fails, a RuntimeException will be thrown.
     *
     * Note that if you set ANY options in the $options argument, ALL of the default options will be unselected; you must
     *  re-select them yourself, if desired, using the JSON_FORCE_OBJECT, JSON_BIGINT_AS_STRING, JSON_UNESCAPED_SLASHES,
     *  JSON_UNESCAPED_UNICODE and JSON_PRESERVE_ZERO_FRACTION constants.
     *
     * @param mixed $inputValue
     * @param int   $jsonOptions
     * @param int   $depth
     * @return string
     * @throws \RuntimeException
     */
    public static function encode($inputValue, $jsonOptions = null, $depth = 512)
    {
        $jsonOptions = $jsonOptions ?: // if $options is already set, use that - otherwise...
            (
                JSON_FORCE_OBJECT |
                // Outputs an object rather than an array when a non-associative array is used.
                //  Especially useful when the recipient of the output is expecting an object
                //  and the array is empty.
                JSON_BIGINT_AS_STRING |     // Encodes large integers as their original string value.
                JSON_UNESCAPED_SLASHES |    // Don't escape "/".
                JSON_UNESCAPED_UNICODE |
                // Encode multibyte Unicode characters literally (we're using UTF-8); default is to escape as \uXXXX

                // "Ensures that float values are always encoded as a float value." - ensures that a PHP float
                //  with no value after the decimal point is encoded as `<whatever>.0` instead of simply `<whatever>`,
                //  which would result in it being decoded back as an int. Also note that, as expected, this only
                //  affects floats - it doesn't encode ints as floats or anything awful.
                (defined('JSON_PRESERVE_ZERO_FRACTION') ? JSON_PRESERVE_ZERO_FRACTION : 0)
            );

        $jsonString = json_encode($inputValue, $jsonOptions, $depth);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                sprintf(
                    'json_encode() returned an error: `%s` while attempting to encode: %s',
                    json_last_error_msg(),
                    static::truncateIfLong(static::var_dump_r($inputValue), 100, '…')
                )
            );
        }

        return $jsonString;
    }
}
