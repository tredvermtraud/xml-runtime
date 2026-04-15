<?php

declare(strict_types=1);

namespace Ermtraud\XsdToPhp\Xml\Attributes;

use Attribute;

/**
 * Declares how a public property maps to an XML element or attribute.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class XmlElement
{
    /**
     * @param string|null $itemType Explicit list item type for array properties.
     */
    public function __construct(
        public string $name,
        public bool $isAttribute = false,
        public bool $isList = false,
        public ?string $itemType = null,
        public ?string $namespace = null,
    ) {
    }
}
