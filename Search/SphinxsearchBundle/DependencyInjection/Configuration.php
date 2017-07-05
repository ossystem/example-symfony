<?php

namespace Search\SphinxsearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Search\SphinxsearchBundle\DependencyInjection\Configuration
 */
class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
	$treeBuilder = new TreeBuilder();
	$rootNode = $treeBuilder->root('sphinxsearch');

	$this->addIndexerSection($rootNode);
	$this->addIndexesSection($rootNode);
	$this->addSearchdSection($rootNode);
	$this->addQlSection($rootNode);
	$this->addMappingSection($rootNode);
	return $treeBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addIndexerSection(ArrayNodeDefinition $node)
    {
	$node
		->children()
		->arrayNode('indexer')
		->addDefaultsIfNotSet()
		->children()
		->scalarNode('bin')->defaultValue('/usr/bin/indexer')->end()
		->end()
		->end()
		->end();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addIndexesSection(ArrayNodeDefinition $node)
    {

	$node
		->children()
		->arrayNode('indexes')
		->useAttributeAsKey('name')
		->prototype('array')
		->children()
		->scalarNode('name')->end()
		->arrayNode('field_weights')
		->useAttributeAsKey('key')
		->prototype('scalar')
		->end()
		->end()
		->arrayNode('index')->prototype('scalar')->end()->end()
		->end()
		->end();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addSearchdSection(ArrayNodeDefinition $node)
    {
	$node
		->children()
		->arrayNode('searchd')
		->addDefaultsIfNotSet()
		->children()
		->scalarNode('host')->defaultValue('localhost')->end()
		->scalarNode('port')->defaultValue('9312')->end()
		->scalarNode('socket')->defaultNull()->end()
		->end()
		->end()
		->end();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addQlSection(ArrayNodeDefinition $node)
    {
	$node
		->children()
		->arrayNode('ql')
		->addDefaultsIfNotSet()
		->children()
		->scalarNode('host')->defaultValue('localhost')->end()
		->scalarNode('port')->defaultValue('9312')->end()
		->scalarNode('socket')->defaultNull()->end()
		->end()
		->end()
		->end();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addMappingSection(ArrayNodeDefinition $node)
    {
	$node
		->children()
		->arrayNode('mapping')
		->isRequired()
		->useAttributeAsKey('key')
		->prototype('array')
		->children()
		->scalarNode('repository')->isRequired()->end()
		->scalarNode('parameter')->isRequired()->end()
		->scalarNode('value')->isRequired()->end()
		->scalarNode('delete_attr')->defaultNull()->end()
		->scalarNode('rt_name')->defaultNull()->end()
		->arrayNode('rt_fields')
		->useAttributeAsKey('rt_field_name')
		->prototype('array')
		->children()
		->scalarNode('type')->isRequired()->defaultValue('field')->end()
		->scalarNode('map')->isRequired()->end()
		->end()
		->end()
		->end()
		->end()
		->end()
		->end()
		->end();
    }

}
