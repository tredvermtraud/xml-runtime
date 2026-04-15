<?php

declare(strict_types=1);

namespace Ermtraud\XsdToPhp\Xml\Attributes;

use Attribute;

/**
 * Declares the XML root element metadata for a mapped class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class XmlRoot
{
    /**
     * @param array<string, string> $namespaces
     */
    public function __construct(
        public string $name,
        public ?string $namespace = null,
        public array $namespaces = [],
    ) {
    }
}
