<?php

declare(strict_types=1);

namespace Ermtraud\XsdToPhp\Tests;

use Ermtraud\XsdToPhp\Config\XmlConfig;
use Ermtraud\XsdToPhp\Xml\Attributes\XmlElement;
use Ermtraud\XsdToPhp\Xml\Attributes\XmlRoot;
use Ermtraud\XsdToPhp\Xml\XmlObjectMapper;
use PHPUnit\Framework\TestCase;

final class XmlObjectMapperTest extends TestCase
{
    public function testItWritesConfiguredRootNamespacesAndUsesTheirPrefixesForChildElements(): void
    {
        $config = XmlConfig::fromArray([
            'format_output' => false,
            'write_root_namespaces' => true,
        ]);

        $message = new NamespacedMessage();
        $message->payload = 'hello';

        $xml = (new XmlObjectMapper($config))->serialize($message);

        self::assertStringContainsString('xmlns:aux="urn:test:aux"', $xml);
        self::assertStringContainsString('xmlns:msg="urn:test:message"', $xml);
        self::assertStringContainsString('<aux:payload>hello</aux:payload>', $xml);
    }
}

#[XmlRoot('message', namespace: 'urn:test:message', namespaces: [
    'msg' => 'urn:test:message',
    'aux' => 'urn:test:aux',
])]
final class NamespacedMessage
{
    #[XmlElement('payload', namespace: 'urn:test:aux')]
    public ?string $payload = null;
}
