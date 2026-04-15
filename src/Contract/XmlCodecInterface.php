<?php

declare(strict_types=1);

namespace Ermtraud\XsdToPhp\Contract;

/**
 * Contract for serializing PHP objects to XML and hydrating them back.
 */
interface XmlCodecInterface
{
    /**
     * Serializes an annotated PHP object into XML.
     */
    public function serialize(object $object): string;

    /**
     * Deserializes XML into an annotated PHP object.
     */
    public function deserialize(string $xml, string $className): object;
}
