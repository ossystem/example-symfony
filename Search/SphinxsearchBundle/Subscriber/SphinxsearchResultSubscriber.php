<?php

namespace Search\SphinxsearchBundle\Subscriber;

use Symfony\Component\Finder\Finder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\AfterEvent;
use Search\SphinxsearchBundle\Services\Search\SearchResultInterface;

/**
 * Sphinxsearch result subscriber
 */
class SphinxsearchResultSubscriber implements EventSubscriberInterface
{

	private $hook = null;

	/**
	 * Determines search results
	 *
	 * @param \Knp\Component\Pager\Event\ItemsEvent $event
	 */
	public function items(ItemsEvent $event)
	{
		if ($event->target instanceof SearchResultInterface) {
			$event->count = $event->target->getTotalFound();
			$event->items = $event->target->getMappedGroupedMatches();
			$this->page = $event->target->getPagerPage();
			$this->limit = $event->target->getPagerLimit();
			$this->hook = true;
			$event->stopPropagation();
		}
	}

	/**
	 * Get events
	 *
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return array(
			'knp_pager.items' => array('items', 1/* increased priority to override any internal */),
			'knp_pager.after' => array('after', 1/* increased priority to override any internal */)
		);
	}

	/**
	 * Post search operations
	 *
	 * @param \Knp\Component\Pager\Event\AfterEvent $event
	 */
	public function after(AfterEvent $event)
	{
		if ($this->hook) {
			$Query = $event->getPaginationView()->getQuery();
			if (isset($Query[$event->getPaginationView()->getPaginatorOption('pageParameterName')])){
				$event->getPaginationView()->setCurrentPageNumber($Query[$event->getPaginationView()->getPaginatorOption('pageParameterName')]); //page
			}else{
				$event->getPaginationView()->setCurrentPageNumber($this->page);
			}

			$event->getPaginationView()->setItemNumberPerPage($this->limit);		//limit
			$this->hook = false;
		}
	}

}