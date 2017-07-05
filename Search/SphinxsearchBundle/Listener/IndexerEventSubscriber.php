<?php

namespace Search\SphinxsearchBundle\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Search\SphinxsearchBundle\Listener\IndexerEventSubscriber
 */
class IndexerEventSubscriber implements EventSubscriber
{

    /**
     *
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
	$this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
	return array(
	    Events::postUpdate,
	    Events::preRemove,
	    Events::postPersist,
	);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Doctrine\Common\EventArgs $args
     *
     * @return void
     */
    public function postUpdate(EventArgs $args)
    {
	$entity = $args->getEntity();
	$this->container->get('search.sphinxsearch.rtindexer')->replaceEntity($entity);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Doctrine\Common\EventArgs $args
     *
     * @return void
     */
    public function preRemove(EventArgs $args)
    {
	$entity = $args->getEntity();
	$this->container->get('search.sphinxsearch.rtindexer')->deleteEntity($entity);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Doctrine\Common\EventArgs $args
     *
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
	$entity = $args->getEntity();
	$this->container->get('search.sphinxsearch.rtindexer')->replaceEntity($entity);
    }

}
