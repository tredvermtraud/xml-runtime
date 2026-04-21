# XML Runtime

[![License: MIT](https://img.shields.io/badge/License-MIT-forestgreen.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3%7C%5E8.4-blue.svg)](https://php.net/)
![AI Assisted](https://img.shields.io/badge/AI-Assisted-firebrick?logo=githubcopilot)

A lightweight PHP library for XML serialization and deserialization using attribute-based mapping. This runtime allows you to easily convert PHP objects to XML and back, with support for namespaces, attributes, and complex object graphs.

## Features

- **Attribute-based Mapping**: Use PHP 8.3+ attributes to define XML structure
- **Namespace Support**: Full support for XML namespaces and prefixes
- **Type Safety**: Automatic type casting for primitive types (int, float, bool, string)
- **Array Handling**: Support for lists and collections
- **Configurable**: Customizable XML output (version, encoding, formatting)
- **Composer Ready**: Easy installation via Composer

## Installation

Install via Composer:

```bash
composer require ermtraud/xml-runtime
```

## Requirements

- PHP 8.3 or 8.4
- `ext-dom` extension
- `ext-libxml` extension

## Usage

### Basic Serialization

Define your PHP classes with XML mapping attributes:

```php
use Ermtraud\XsdToPhp\Xml\Attributes\XmlRoot;
use Ermtraud\XsdToPhp\Xml\Attributes\XmlElement;
use Ermtraud\XsdToPhp\Xml\XmlObjectMapper;

#[XmlRoot('person')]
class Person
{
    #[XmlElement('name')]
    public string $name;

    #[XmlElement('age')]
    public int $age;

    #[XmlElement('email')]
    public ?string $email = null;
}

// Create and serialize an object
$person = new Person();
$person->name = 'John Doe';
$person->age = 30;
$person->email = 'john@example.com';

$mapper = new XmlObjectMapper();
$xml = $mapper->serialize($person);

echo $xml;
// Output:
// <?xml version="1.0" encoding="UTF-8"?>
// <person>
//   <name>John Doe</name>
//   <age>30</age>
//   <email>john@example.com</email>
// </person>
```

### Deserialization

```php
$xml = '<person><name>Jane Doe</name><age>25</age></person>';

$mapper = new XmlObjectMapper();
$person = $mapper->deserialize($xml, Person::class);

echo $person->name; // Jane Doe
echo $person->age;  // 25 (int)
```

### XML Attributes

Use `isAttribute: true` to map properties to XML attributes:

```php
#[XmlRoot('book')]
class Book
{
    #[XmlElement('title')]
    public string $title;

    #[XmlElement('isbn', isAttribute: true)]
    public string $isbn;
}

// XML output:
// <book isbn="978-1234567890">
//   <title>Sample Book</title>
// </book>
```

### Arrays and Lists

Handle collections with `isList: true`:

```php
#[XmlRoot('library')]
class Library
{
    #[XmlElement('book', isList: true, itemType: Book::class)]
    public array $books = [];
}
```

### Namespaces

Support for XML namespaces:

```php
#[XmlRoot('message', namespace: 'urn:test:message', namespaces: [
    'msg' => 'urn:test:message',
    'aux' => 'urn:test:aux',
])]
class NamespacedMessage
{
    #[XmlElement('payload', namespace: 'urn:test:aux')]
    public string $payload;
}
```

## Configuration

Customize XML output with `XmlConfig`:

```php
use Ermtraud\XsdToPhp\Config\XmlConfig;

$config = XmlConfig::fromArray([
    'version' => '1.0',
    'encoding' => 'UTF-8',
    'format_output' => true,
    'write_root_namespaces' => false,
    'fallback_namespace' => 'parent',
]);

$mapper = new XmlObjectMapper($config);
```

Configuration options:
- `version`: XML version (default: "1.0")
- `encoding`: Character encoding (default: "UTF-8")
- `format_output`: Pretty-print XML (default: true)
- `write_root_namespaces`: Include namespace declarations in root element (default: false)
- `fallback_namespace`: Namespace fallback for elements without an explicit `namespace`; use `parent`, `root`, or `none` (default: `parent`)

`fallback_namespace` affects serialization only and controls which namespace or element prefix is used when an `XmlElement` does not declare its own `namespace`.

## API Reference

### XmlObjectMapper

The main class for serialization and deserialization.

```php
public function __construct(?XmlConfig $config = null)
public function serialize(object $object): string
public function deserialize(string $xml, string $className): object
```

### Attributes

#### XmlRoot
Applied to classes to define the root XML element.

```php
#[XmlRoot(string $name, ?string $namespace = null, array $namespaces = [])]
```

#### XmlElement
Applied to properties to define XML elements or attributes.

```php
#[XmlElement(
    string $name,
    bool $isAttribute = false,
    bool $isList = false,
    ?string $itemType = null,
    ?string $namespace = null
)]
```

### Exceptions

- `XmlMappingException`: Thrown when serialization or deserialization fails
- `InvalidConfiguration`: Thrown when configuration is invalid

## Testing

Run tests with PHPUnit:

```bash
composer test
```

GitHub Actions runs the `CI` workflow on every push to `main` and on pull requests targeting `main`. The workflow validates `composer.json`, installs dependencies, and runs the test suite on PHP `8.3` and `8.4`.

## Release Process

This repository uses `Release Please` on `main`. Releases are prepared from merged changes on `main`; there is no separate `staging` prerelease branch and no manual tag-push release flow.

### Automated Release Flow

1. Open a pull request against `main`.
2. Let the `CI` workflow pass.
3. Merge the pull request into `main`.
4. The `Release` workflow runs on that push and uses Release Please to open or update a release pull request.
5. Review and merge the generated release pull request when you are ready to publish.
6. After the release pull request is merged, Release Please creates the GitHub release and version tag from `main`.

### Commit Message Conventions

Release Please derives the version bump and release notes from Conventional Commits on `main`.

- `fix:` triggers a patch release
- `feat:` triggers a minor release
- `feat!:` or a `BREAKING CHANGE:` footer triggers a major release

If no releasable changes are detected, Release Please will not open a release pull request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome. Please open a pull request against `main` and prefer Conventional Commit messages so automated release notes and versioning stay accurate.
