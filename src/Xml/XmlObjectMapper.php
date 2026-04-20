<?php

declare(strict_types=1);

namespace Ermtraud\XsdToPhp\Xml;

use DOMDocument;
use DOMElement;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Ermtraud\XsdToPhp\Config\XmlConfig;
use Ermtraud\XsdToPhp\Contract\XmlCodecInterface;
use Ermtraud\XsdToPhp\Exception\XmlMappingException;
use Ermtraud\XsdToPhp\Xml\Attributes\XmlElement;
use Ermtraud\XsdToPhp\Xml\Attributes\XmlRoot;

/**
 * Serializes annotated objects to XML and hydrates them back from XML strings.
 */
final class XmlObjectMapper implements XmlCodecInterface
{
    private const XMLNS_NAMESPACE = 'http://www.w3.org/2000/xmlns/';
    private readonly XmlConfig $config;

    public function __construct(
        ?XmlConfig $config = null,
    ) {
        $this->config = $config ?? XmlConfig::fromArray([]);
    }

    /**
     * Serializes a mapped object into XML.
     */
    public function serialize(object $object): string
    {
        $reflection = new ReflectionClass($object);
        $rootMetadata = $this->readRootMetadata($reflection);
        $config = $this->config;

        $document = new DOMDocument(
            $config->version,
            $config->encoding,
        );
        $document->formatOutput = $config->formatOutput;
        $namespaceDeclarations = $config->writeRootNamespaces
            ? $rootMetadata->namespaces
            : [];

        $rootPrefix = $this->preferredPrefixForNamespace($namespaceDeclarations, $rootMetadata->namespace);
        $rootQualifiedName = $this->qualifyElementName($rootMetadata->name, $rootPrefix);

        $root = $rootMetadata->namespace !== null
            ? $document->createElementNS($rootMetadata->namespace, $rootQualifiedName)
            : $document->createElement($rootMetadata->name);

        $document->appendChild($root);
        $this->writeNamespaceDeclarations($root, $namespaceDeclarations);
        $this->writeObject($document, $root, $object, $namespaceDeclarations);

        return $document->saveXML() ?: '';
    }

    /**
     * Hydrates a mapped object from an XML string.
     */
    public function deserialize(string $xml, string $className): object
    {
        $document = new DOMDocument();
        $loaded = $document->loadXML($xml);

        if ($loaded === false || $document->documentElement === null) {
            throw new XmlMappingException('Unable to parse XML input.');
        }

        return $this->hydrateObject($document->documentElement, $className);
    }

    /**
     * @param array<string, string> $namespaceDeclarations
     */
    private function writeObject(DOMDocument $document, DOMElement $element, object $object, array $namespaceDeclarations = []): void
    {
        $reflection = new ReflectionClass($object);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isInitialized($object)) {
                continue;
            }

            $value = $property->getValue($object);
            if ($value === null) {
                continue;
            }

            $metadata = $this->readElementMetadata($property);

            if ($metadata->isAttribute) {
                $element->setAttribute($metadata->name, (string) $value);
                continue;
            }

            if ($metadata->isList && is_array($value)) {
                foreach ($value as $item) {
                    $child = $this->createChildElement($document, $metadata, $element, $namespaceDeclarations);
                    $element->appendChild($child);
                    $this->writeValue($document, $child, $item, $namespaceDeclarations);
                }

                continue;
            }

            $child = $this->createChildElement($document, $metadata, $element, $namespaceDeclarations);
            $element->appendChild($child);
            $this->writeValue($document, $child, $value, $namespaceDeclarations);
        }
    }

    /**
     * @param array<string, string> $namespaceDeclarations
     */
    private function writeValue(DOMDocument $document, DOMElement $element, mixed $value, array $namespaceDeclarations = []): void
    {
        if (is_scalar($value)) {
            if(is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $element->appendChild($document->createTextNode((string) $value));
            return;
        }

        if (is_object($value)) {
            $this->writeObject($document, $element, $value, $namespaceDeclarations);
            return;
        }

        throw new XmlMappingException(sprintf('Unsupported value type "%s" during XML serialization.', get_debug_type($value)));
    }

    private function hydrateObject(DOMElement $element, string $className): object
    {
        if (!class_exists($className)) {
            throw new XmlMappingException(sprintf('Cannot hydrate missing class "%s".', $className));
        }

        $reflection = new ReflectionClass($className);
        $object = $reflection->newInstanceWithoutConstructor();

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $metadata = $this->readElementMetadata($property);

            if ($metadata->isAttribute) {
                if ($element->hasAttribute($metadata->name)) {
                    $property->setValue($object, $this->castValue($element->getAttribute($metadata->name), $property));
                }

                continue;
            }

            if ($metadata->isList) {
                $items = [];

                foreach ($this->findChildElements($element, $metadata->name, $metadata->namespace) as $child) {
                    $items[] = $this->hydrateNodeValue($child, $metadata->itemType, $property);
                }

                $property->setValue($object, $items);
                continue;
            }

            $child = $this->findFirstChildElement($element, $metadata->name, $metadata->namespace);
            if ($child === null) {
                continue;
            }

            $property->setValue($object, $this->hydrateNodeValue($child, null, $property));
        }

        return $object;
    }

    private function hydrateNodeValue(DOMElement $element, ?string $itemType, ReflectionProperty $property): mixed
    {
        if ($itemType !== null && class_exists($itemType)) {
            return $this->hydrateObject($element, $itemType);
        }

        if ($itemType !== null && $this->isBuiltinType($itemType)) {
            return $this->castRawValue($element->textContent, $itemType);
        }

        $type = $property->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->hydrateObject($element, $type->getName());
        }

        if ($type instanceof ReflectionNamedType && $type->getName() === 'array') {
            return $element->textContent;
        }

        return $this->castValue($element->textContent, $property);
    }

    private function castValue(string $value, ReflectionProperty $property): mixed
    {
        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        return $this->castRawValue($value, $type->getName());
    }

    private function castRawValue(string $value, string $typeName): mixed
    {
        return match ($typeName) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false,
            'string' => $value,
            default => $value,
        };
    }

    private function readRootMetadata(ReflectionClass $reflection): XmlRoot
    {
        $attributes = $reflection->getAttributes(XmlRoot::class);
        if ($attributes === []) {
            return new XmlRoot($reflection->getShortName());
        }

        return $attributes[0]->newInstance();
    }

    private function readElementMetadata(ReflectionProperty $property): XmlElement
    {
        $attributes = $property->getAttributes(XmlElement::class);
        if ($attributes === []) {
            return new XmlElement($property->getName());
        }

        return $attributes[0]->newInstance();
    }

    /**
     * @param array<string, string> $namespaceDeclarations
     */
    private function createChildElement(DOMDocument $document, XmlElement $metadata, DOMElement $parent, array $namespaceDeclarations = []): DOMElement
    {
        if ($metadata->namespace !== null) {
            $prefix = $this->preferredPrefixForNamespace($namespaceDeclarations, $metadata->namespace);
            if ($prefix !== null && $metadata->namespace !== $parent->namespaceURI) {
                return $document->createElementNS($metadata->namespace, $this->qualifyElementName($metadata->name, $prefix));
            }

            return $document->createElementNS($metadata->namespace, $metadata->name);
        }

        [$fallbackNamespace, $fallbackPrefix] = $this->resolveFallbackNamespace($parent);

        if ($fallbackNamespace !== null) {
            if ($fallbackPrefix !== null) {
                return $document->createElementNS($fallbackNamespace, $this->qualifyElementName($metadata->name, $fallbackPrefix));
            }

            return $document->createElementNS($fallbackNamespace, $metadata->name);
        }

        return $document->createElement($metadata->name);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveFallbackNamespace(DOMElement $parent): array
    {
        return match ($this->config->fallbackNamespace) {
            XmlConfig::FALLBACK_NAMESPACE_PARENT => $this->namespaceContextFromElement($parent),
            XmlConfig::FALLBACK_NAMESPACE_ROOT => $this->namespaceContextFromRoot($parent),
            XmlConfig::FALLBACK_NAMESPACE_NONE => [null, null],
        };
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function namespaceContextFromRoot(DOMElement $parent): array
    {
        $root = $parent->ownerDocument?->documentElement;
        if (!$root instanceof DOMElement) {
            return [null, null];
        }

        return $this->namespaceContextFromElement($root);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function namespaceContextFromElement(DOMElement $element): array
    {
        $namespace = $element->namespaceURI;
        if ($namespace === null || $namespace === '') {
            return [null, null];
        }

        $prefix = $element->prefix;
        if ($prefix === '') {
            $prefix = null;
        }

        return [$namespace, $prefix];
    }

    /**
     * @param array<string, string> $namespaceDeclarations
     */
    private function writeNamespaceDeclarations(DOMElement $root, array $namespaceDeclarations): void
    {
        foreach ($namespaceDeclarations as $prefix => $namespace) {
            if ($prefix === '' || $namespace === '') {
                continue;
            }

            $root->setAttributeNS(self::XMLNS_NAMESPACE, 'xmlns:' . $prefix, $namespace);
        }
    }

    /**
     * @param array<string, string> $namespaceDeclarations
     */
    private function preferredPrefixForNamespace(array $namespaceDeclarations, string $namespace): ?string
    {
        foreach ($namespaceDeclarations as $prefix => $candidateNamespace) {
            if ($prefix !== '' && $candidateNamespace === $namespace) {
                return $prefix;
            }
        }

        return null;
    }

    private function qualifyElementName(string $name, ?string $prefix): string
    {
        if ($prefix === null || str_contains($name, ':')) {
            return $name;
        }

        return $prefix . ':' . $name;
    }

    /**
     * @return list<DOMElement>
     */
    private function findChildElements(DOMElement $parent, string $name, ?string $namespace): array
    {
        $elements = [];

        foreach ($parent->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($this->elementMatches($childNode, $name, $namespace)) {
                $elements[] = $childNode;
            }
        }

        return $elements;
    }

    private function findFirstChildElement(DOMElement $parent, string $name, ?string $namespace): ?DOMElement
    {
        foreach ($parent->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && $this->elementMatches($childNode, $name, $namespace)) {
                return $childNode;
            }
        }

        return null;
    }

    private function elementMatches(DOMElement $element, string $name, ?string $namespace): bool
    {
        if ($element->localName !== $name && $element->nodeName !== $name) {
            return false;
        }

        if ($namespace === null) {
            return true;
        }

        return $element->namespaceURI === $namespace;
    }

    private function isBuiltinType(string $typeName): bool
    {
        return in_array($typeName, ['int', 'float', 'bool', 'string'], true);
    }
}
