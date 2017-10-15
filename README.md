JSON
====

This is a simple wrapper around PHP's native `json_encode()` and `json_decode()` functions, plus some methods that combine the `json_*` functions with `file_get_contents()` and `file_put_contents()`.

This wrapper simplifies JSON encoding/decoding by providing more sensible defaults than PHP does automatically; by combining file reading/writing with JSON decoding/encoding for single-call reading-and-decoding or encoding-and-writing of JSON files; and by throwing `\RuntimeException`s when something goes wrong, allowing simplified error-handling in your application.

Installation
------------

### Composer
```
composer require aziraphale/json
```

This package defines a PSR-4 autoload spec that Composer should recognise and automatically make available for you, so by using `use Aziraphale\json\JSON;` at the top of your file(s) this JSON class will be made available to you in that file.

### Manual
Simply copy the `JSON.php` file from this repository into whichever directory you use to store third-party classes and libraries, then include `JSON.php` in whichever files need it. But seriously, use Composer, it's much easier.

Usage
-----
All supplied methods are static:

#### JSON::getContents()
Combines `json_decode()` with `file_get_contents()`.
```
getContents(string $filename, bool $assoc = false, int $depth = 512, int $options = 0) : mixed
```

#### JSON::putContents()
Wraps `json_encode()` and `file_put_contents()` to encode a variable as JSON and immediately write it to a file,
throwing a `\RuntimeException` if either the encoding or the file-writing process fails.
```
putContents(mixed $inputValue, string $filename, int $jsonOptions = null, int $depth = 512) : void
```

#### JSON::decode()
Wraps `json_decode()` together with some error-handling: a `\RuntimeException` will be thrown if decoding fails.
```
decode(string $jsonString, bool $assoc = false, int $depth = 512, int $options = 0) : mixed
```

#### JSON::encode()
A wrapper for `json_encode()` which defaults to a lot of very sensible options:

 - An empty (root) array will be encoded as an empty object,
 - Bigints will be encoded as strings,
 - Forward-slashes won't be escaped,
 - Unicode won't be escaped (we return a UTF-8 string, after all!),
 - Floats with a zero 'fractional part' will still be encoded as floats rather than ints (PHP 5.6.6+)

Also includes minor error-handling: when encoding fails, a `\RuntimeException` will be thrown.
```
encode(mixed $inputValue, int $jsonOptions = null, int $depth = 512) : string
```
