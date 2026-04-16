<?php

declare(strict_types=1);

namespace Ermtraud\XsdToPhp\Tests;

use Ermtraud\XsdToPhp\Config\XmlConfig;
use Ermtraud\XsdToPhp\Exception\InvalidConfiguration;
use Ermtraud\XsdToPhp\Xml\Attributes\XmlElement;
use Ermtraud\XsdToPhp\Xml\Attributes\XmlRoot;
use Ermtraud\XsdToPhp\Xml\XmlObjectMapper;
use PHPUnit\Framework\TestCase;

final class XmlObjectMapperTest extends TestCase
{
    public function testItUsesParentNamespaceFallbackByDefaultForElementsWithoutAnExplicitNamespace(): void
    {
        $config = XmlConfig::fromArray([
            'format_output' => false,
            'write_root_namespaces' => true,
        ]);

        $message = $this->createNestedMessage();

        $xml = (new XmlObjectMapper($config))->serialize($message);

        self::assertStringContainsString('<aux:value>hello</aux:value>', $xml);
    }

    public function testItCanUseTheRootNamespaceFallbackForElementsWithoutAnExplicitNamespace(): void
    {
        $config = XmlConfig::fromArray([
            'format_output' => false,
            'write_root_namespaces' => true,
            'fallback_namespace' => 'root',
        ]);

        $message = $this->createNestedMessage();

        $xml = (new XmlObjectMapper($config))->serialize($message);

        self::assertStringContainsString('<msg:value>hello</msg:value>', $xml);
    }

    public function testItCanDisableNamespaceFallbackForElementsWithoutAnExplicitNamespace(): void
    {
        $config = XmlConfig::fromArray([
            'format_output' => false,
            'write_root_namespaces' => true,
            'fallback_namespace' => 'none',
        ]);

        $message = $this->createNestedMessage();

        $xml = (new XmlObjectMapper($config))->serialize($message);

        self::assertStringContainsString('<value>hello</value>', $xml);
        self::assertStringNotContainsString('<aux:value>hello</aux:value>', $xml);
        self::assertStringNotContainsString('<msg:value>hello</msg:value>', $xml);
    }

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

    public function testItRejectsUnknownNamespaceFallbackValues(): void
    {
        $this->expectException(InvalidConfiguration::class);

        XmlConfig::fromArray([
            'fallback_namespace' => 'child',
        ]);
    }

    private function createNestedMessage(): NestedNamespacedMessage
    {
        $message = new NestedNamespacedMessage();
        $message->body = new NestedNamespacedBody();
        $message->body->value = 'hello';

        return $message;
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

#[XmlRoot('msg:message', namespace: 'urn:test:message', namespaces: [
    'msg' => 'urn:test:message',
    'aux' => 'urn:test:aux',
])]
final class NestedNamespacedMessage
{
    #[XmlElement('body', namespace: 'urn:test:aux')]
    public ?NestedNamespacedBody $body = null;
}

final class NestedNamespacedBody
{
    #[XmlElement('value')]
    public ?string $value = null;
}
