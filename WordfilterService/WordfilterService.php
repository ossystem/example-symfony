<?php

namespace Tube\Bundle\MainBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * WordfilterService
 */
class WordfilterService
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var boolean
     */
    protected $extensionLoaded;

    /**
     * @var boolean
     */
    protected $enabled;

    /**
     * @throws \InvalidArgumentException
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->extensionLoaded = extension_loaded('wordfilter');
        $em = $this->container->get('doctrine')->getManager();
        $keyBadWords = $this->container->getParameter('bad_words_name');
        $ttl = $this->container->getParameter('ttl_bad_bords');

        if (false === $this->container->get('tube.redis')->exists($keyBadWords) && true === $this->extensionLoaded) {
            $blackList = $em->getRepository('TubeMainBundle:Badwords')->findAllToArray();
            $whiteList = $em->getRepository('TubeMainBundle:GoodWords')->findAllToArray();
            $worldlen = wordfilter_get_max_word_length(); //default 32

            foreach ($blackList as $word) {
                if (strlen($word) > $worldlen) {
                    throw new \InvalidArgumentException(sprintf(
                        'Badword length can not be more than "%d".',
                        $worldlen
                    ));
                }
            }

            $wordlist = wordfilter_compile_wordlist($blackList, $whiteList);
            $this->container->get('tube.redis')->store($keyBadWords, $wordlist, $ttl);
        }
    }

    /**
     * @param boolean $e
     *
     * @return \Tube\Bundle\MainBundle\Service\WordfilterService
     */
    public function setEnabled($e)
    {
        $this->enabled = (bool) $e;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Filters the text
     *
     * @param string $text
     */
    public function filtering(&$text)
    {
        $keyBadWords = $this->container->getParameter('bad_words_name');

        if (true === $this->container->get('tube.redis')->exists($keyBadWords) && true === $this->extensionLoaded) {
            $badWordList = $this->container->get('tube.redis')->get($keyBadWords);
        } else {
            $em = $this->container->get('doctrine')->getManager();
            $blackList = $em->getRepository('TubeMainBundle:Badwords')->findAllToArray();
            $whiteList = $em->getRepository('TubeMainBundle:GoodWords')->findAllToArray();
            $badWordList = wordfilter_compile_wordlist($blackList, $whiteList);
        }

        wordfilter($text, $badWordList, true);
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->getEnabled() || HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestUriPath = $request->getPathInfo();

        if ('post' === strtolower($request->getMethod()) || !$this->extensionLoaded || strpos($requestUriPath, 'admin/')) {
            return;
        }

        $responseText = $response->getContent();
        $em = $this->container->get('doctrine')->getManager();
        $keyBadUri = $this->container->getParameter('bad_uri_name');
        $ttl = $this->container->getParameter('ttl_bad_bords');

        if (true === $this->container->get('tube.redis')->exists($keyBadUri)) {
            $baduriList = unserialize($this->container->get('tube.redis')->get($keyBadUri));
        } else {
            $baduriList = $em->getRepository('TubeMainBundle:BadUri')->findAllToArray();
            $this->container->get('tube.redis')->store($keyBadUri, serialize($baduriList), $ttl);
        }

        $uriPathWithoutEndSlash = rtrim($requestUriPath, '/');

        if (!$this->matchUrlException($uriPathWithoutEndSlash, $baduriList)) {
            $this->filtering($responseText);
            $response->setContent($responseText);
        }

        return;
    }

    /**
     * Get match exception for url
     *
     * @param string $uri
     * @param array  $patterns
     *
     * @return bool
     */
    public function matchUrlException($uri, $patterns)
    {
        foreach ($patterns as $pattern) {
            if (preg_match("#{$pattern}#", $uri)) {
                return true;
            }
        }

        return false;
    }
}
