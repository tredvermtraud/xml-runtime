# XML Runtime

[![License: MIT](https://img.shields.io/badge/License-MIT-forestgreen.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3%7C%5E8.4-blue.svg)](https://php.net/)
![AI Assisted](https://img.shields.io/badge/AI-Assisted-firebrick?logo=githubcopilot)

A lightweight PHP library for XML serialization and deserialization using attribute-based mapping. This runtime allows you to easily convert PHP objects to XML and back, with support for namespaces, attributes, and complex object graphs.

## Features

- **Attribute-based Mapping**: Use PHP 8.1+ attributes to define XML structure
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

## Release Process

This repository uses a tag-based release model with a stable `main` branch and a continuously testable `staging` branch.

- `main`: production-ready history only
- `staging`: release-candidate integration branch
- `feature/*`: feature work merged into `staging`
- `hotfix/*`: urgent fixes branched from `main`, merged back to `main`, then back-merged into `staging`

### Prereleases From `staging`

Every push to `staging` runs CI and, if it passes, publishes a GitHub prerelease with an auto-managed `staging-<short-sha>` tag. Older auto-generated staging prereleases are cleaned up first so the repository keeps one current prerelease instead of accumulating noise.

Developer flow:

1. Merge feature branches into `staging`.
2. Push `staging`.
3. Wait for the `Prerelease On Staging` workflow to pass.
4. Test the generated GitHub prerelease artifacts and notes.

### Production Releases From `main`

Production releases are created only from SemVer tags such as `v1.2.0` or `v2.0.1`. The release workflow verifies that the tagged commit is reachable from `main`, reruns CI, packages the library, and creates a full GitHub Release.

Developer flow:

1. Merge the validated release candidate from `staging` into `main`.
2. Create and push a production tag from `main`, for example:

```bash
git checkout main
git pull --ff-only
git tag v1.2.0
git push origin v1.2.0
```

3. Wait for the `Release On Version Tag` workflow to publish the official GitHub Release.

### Hotfix Flow

Hotfixes branch from `main`, not `staging`.

1. Create `hotfix/<name>` from `main`.
2. Merge the hotfix into `main`.
3. Tag the merge commit on `main` with the next production version and push the tag.
4. Back-merge the hotfix changes into `staging` so the next prerelease includes them.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
