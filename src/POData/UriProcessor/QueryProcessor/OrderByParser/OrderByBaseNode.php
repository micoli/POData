<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\OrderByParser;

use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;

/**
 * Class OrderByBaseNode.
 *
 * Base type for nodes in OrderByTree, a node in 'OrderBy Tree'
 * represents a sub path segment.
 */
abstract class OrderByBaseNode
{
    /**
     * Name of the property that corresponds to the sub path segment represented by this node.
     *
     * @var string
     */
    protected $propertyName;

    /**
     * Th resource property of the property that corresponds to the sub path segment represented by this node.
     *
     * @var ResourceProperty
     */
    protected $resourceProperty;

    /**
     * Construct a new instance of OrderByBaseNode.
     *
     * @param string|null           $propertyName     Name of the property that corresponds to the sub path segment
     *                                                represented by this node, this parameter will be null if this
     *                                                node is root
     * @param ResourceProperty|null $resourceProperty Resource property that corresponds to the sub path segment
     *                                                represented by this node, this parameter will be null if this
     *                                                node is root
     */
    public function __construct(?string $propertyName, ?ResourceProperty $resourceProperty)
    {
        $this->propertyName     = $propertyName;
        $this->resourceProperty = $resourceProperty;
    }

    /**
     * Gets resource type of the property that corresponds to the sub path segment represented by this node.
     *
     * @return ResourceType
     */
    abstract public function getResourceType(): ResourceType;

    /**
     * Free resource used by this node.
     *
     * @return void
     */
    abstract public function free(): void;

    /**
     * Gets the name of the property that corresponds to the sub path segment represented by this node.
     *
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * Gets the resource property of property that corresponds to the sub path segment represented by this node.
     *
     * @return ResourceProperty
     */
    public function getResourceProperty(): ResourceProperty
    {
        return $this->resourceProperty;
    }
}
