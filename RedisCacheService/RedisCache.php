<?php

namespace Tube\Bundle\MainBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\Event\FilterResponseEvent,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\HttpFoundation\Response,
    Application\Sonata\UserBundle\Entity\User;

/**
 * RedisCache
 */
class RedisCache
{

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var Object
     */
    protected $redis;

    /**
     * @var array
     */
    protected $cfg = array(
        'debug' => false,
        'enabled' => false,
        'prefix' => 'tube',
        'rules' => array(),
    );

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->redis = $this->container->get('snc_redis.default');
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param integer $ttl
     */
    public function store($key, $value, $ttl = false)
    {
        $this->redis = $this->container->get('snc_redis.default');
        $value = gzdeflate($value, 6);

        if ($ttl) {
            $this->redis->setex($key, $ttl, $value);
        } else {
            $this->redis->set($key, $value);
        }
    }

    public function getInstanceForWrite()
    {
        return $this->container->get('snc_redis.default');
    }

    public function getInstanceForRead()
    {
        return $this->container->get('snc_redis.slave');
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $this->redis = $this->container->get('snc_redis.slave');

        return gzinflate($this->redis->get($key));
    }

    /**
     *
     * @param type $key
     * @return boolean
     */
    public function exists($key)
    {
        $this->redis = $this->container->get('snc_redis.slave');

        return $this->redis->exists($key);
    }

    /**
     * @param string $uri
     * @param mixed $value
     * @param integer $ttl
     */
    public function storeUri($uri, $value, $ttl = false)
    {
        if ($ttl) {
            $this->store("{$this->getPrefix()}:{$uri}", $ttl, $value);
        } else {
            $this->store("{$this->getPrefix()}:{$uri}", $value);
        }
    }

    /**
     * @param string $key
     */
    public function delete($key)
    {
        $this->redis = $this->container->get('snc_redis.slave');
        $keys = $this->redis->keys($key);
        $this->redis = $this->container->get('snc_redis.default');

        if (is_array($keys) && count($keys) > 0) {
            foreach ($keys as $key) {
                $this->redis->del($key);
            }
        }
    }

    /**
     * @param string $uri
     */
    public function deleteUri($uri)
    {
        $this->delete("{$this->getPrefix()}:{$uri}");
    }

    /**
     * @param boolean $e
     *
     * @return \Tube\Bundle\MainBundle\Service\RedisCache
     */
    public function setEnabled($e)
    {
        $this->cfg['enabled'] = (bool) $e;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->cfg['enabled'];
    }

    /**
     * @param boolean $d
     * @return \Tube\Bundle\MainBundle\Service\RedisCache
     */
    public function setDebug($d)
    {
        $this->cfg['debug'] = (bool) $d;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getDebug()
    {
        return $this->cfg['debug'];
    }

    /**
     * @param string $p
     *
     * @return \Tube\Bundle\MainBundle\Service\RedisCache
     */
    public function setPrefix($p)
    {
        $this->cfg['prefix'] = $p;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        if (in_array($this->container->get('kernel')->getEnvironment(), array('mob', 'mobdev'))) {
            $prefix = $this->container->getParameter('redis_prefix_mob');
        } else {
            $prefix = $this->container->getParameter('redis_prefix');
        }

        return $prefix;
    }

    /**
     * @param string $location
     * @param string $pattern
     * @param integer $ttl
     * @param bollean $enabled
     */
    public function addRule($location, $pattern, $ttl, $enabled)
    {
        $this->cfg['rules'][$pattern] = array(
            'location' => $location,
            'ttl' => $ttl,
            'enabled' => $enabled,
        );
    }

    /**
     * @param string $uri
     * @return boolean
     */
    public function getMatch($uri)
    {
        foreach ($this->cfg['rules'] as $pattern => $options) {
            if (preg_match("#{$pattern}#", $uri)) {
                if ($this->getDebug()) {
                    $logger = $this->container->get('logger');
                    $logger->info("Matched location: <{$options['location']}>, uri: <{$uri}>. TTL: <{$options['ttl']}>. Caching " . ($options['enabled'] ? 'enabled' : 'disabled'));
                }

                return $options;
            }
        }

        return false;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->escapeLoggedInCache()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if ('post' === strtolower($request->getMethod()) || !$this->getEnabled()) {
            return;
        }

        $uri = $this->container->get('request')->server->get('REQUEST_URI');

        if (($options = $this->getMatch($uri)) !== false) {
            if ($options['enabled']) {
                $this->store("{$this->getPrefix()}:{$uri}", $response->getContent(), $options['ttl']);
            }
        }

        return;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->escapeLoggedInCache()) {
            return;
        }

        $request = $event->getRequest();

        if ('post' === strtolower($request->getMethod()) || !$this->getEnabled()) {
            return;
        }

        $uri = $this->container->get('request')->server->get('REQUEST_URI');

        if (($options = $this->getMatch($uri)) !== false) {
            if($options['enabled'] && $this->exists("{$this->getPrefix()}:{$uri}")) {
                //$event->setResponse(new Response($this->get("{$this->getPrefix()}:{$uri}")));
                die($this->get("{$this->getPrefix()}:{$uri}"));
            }
        }
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     * @return boolean
     */
    public function escapeLoggedInCache(){
        if (!is_null($this->container->get('security.context')->getToken())) {
            $user =  $this->container->get('security.context')->getToken()->getUser();

            if ($user instanceof User){
                $getReq = $this->container->get('request')->get('_route');

                if ('video_show' === @$getReq) {
                    return true;
                }
            }
        }

        return false;
    }
}
