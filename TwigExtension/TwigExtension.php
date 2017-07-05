<?php

namespace Tube\Bundle\MainBundle\Twig;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Application\Sonata\MediaBundle\Entity\Media;
use Application\Sonata\MediaBundle\Entity\Image;

/**
 * Extention for Twig
 */
class TubeExtension extends \Twig_Extension
{
	/**
	 *
	 * @var \Symfony\Component\DependencyInjection\ContainerInterface
	 */
	protected $container;

	/**
	 *
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFunctions()
	{
		return array(
			'router_params' => new \Twig_Function_Method($this, 'routerParams'),
			'staticblock' => new \Twig_Function_Method($this, 'staticBlock', array('is_safe' => array('html'))),
			'bannerads' => new \Twig_Function_Method($this, 'bannerAds', array('is_safe' => array('html'))),
			'cutstring' => new \Twig_Function_Method($this, 'cutString'),
			'set_user_style' => new \Twig_Function_Method($this, 'setUserStyle', array('is_safe' => array('html'))),
			'counter' => new \Twig_Function_Method($this, 'counter', array('is_safe' => array('html'))),
			'generate_meta' => new \Twig_Function_Method($this, 'generateMeta', array('is_safe' => array('html'))),
			'generate_meta_title' => new \Twig_Function_Method($this, 'generateMetaTitle', array('is_safe' => array('html'))),
			'generate_meta_description' => new \Twig_Function_Method($this, 'generateMetaDescription', array('is_safe' => array('html'))),
			'generate_meta_keywords' => new \Twig_Function_Method($this, 'generateMetaKeywords', array('is_safe' => array('html'))),
			'var_dump' => new \Twig_Function_Method($this, 'varDump', array('is_safe' => array('html'))),
			'get_geoip_country_code' => new \Twig_Function_Method($this, 'getGeoipCountryCode', array('is_safe' => array('html'))),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFilters()
	{
		return array(
			'floor' => new \Twig_Filter_Method($this, 'floor'),
			'ceil' => new \Twig_Filter_Method($this, 'ceil'),
			'ucfirst' => new \Twig_Filter_Method($this, 'ucfirst'),
			'first' => new \Twig_Filter_Method($this, 'first'),
			'last' => new \Twig_Filter_Method($this, 'last'),
			'shuffle' => new \Twig_Filter_Method($this, 'shuffle'),
			'url_decode' => new \Twig_Filter_Method($this, 'urlDecode'),
			'sort_by_attribute'   => new \Twig_Filter_Method($this, 'twig_sort_by_attribute_filter'),
		);
	}

	/**
	* Sorts an array.
	* Allows to sort an array of objects by a specified object property.
	* Allows to sort an array of arrays by a specified index.
	* Allows to sort hybrid arrays of objects, string, numbers
	*
	* $options accepted values:
	* 	'case_sensitive': true|false(default)
	*
	* @param array $array An array
	* @param string $attribute An object property or an array index
	* @param array $options An array of options
	*/
	public function twig_sort_by_attribute_filter($array, $attribute = null, $options = array())
	{
		if (null !== $attribute) {
			/*
			 * builds a temp array to be sorted
			 *
			 * $arrToSort keys		= $array keys
			 * $arrToSort values	= values of each object's attribute or values of $array at specified index
			 */
			$arrToSort = $array;

			foreach ($array as $k => $v) {
				if ($v instanceof ArrayAccess && isset($v[$attribute])) {
					$v = $v[$attribute];
				} elseif (is_object($v)) {
					$getter = preg_replace("/[^a-zA-Z0-9]/", "", "get".$attribute);

					if (method_exists($v, $getter)) {
						$v = $v->$getter();
					}
				} elseif (is_array($v)) {
					$v = $v[$attribute];
				}

				if (!(isset($options['case_sensitive']) && true === $options['case_sensitive'])) {
					$v = strtolower($v);
				}

				// $v is now te value the user wants to sort the $array by
				$arrToSort[$k] = $v;
			}

			asort($arrToSort);

			// replaces the $arrToSort values with the original $array values
			foreach ($arrToSort as $k => $v) {
				$arrToSort[$k] = $array[$k];
			}

			return $arrToSort;
		}

		throw new InvalidArgumentException('You must specify an object attribute or array index you want to sort the array by');
	}

	/**
	 * Round fractions down
	 *
	 * @param float $v
	 * @return float
	 */
	public function floor($v)
	{
		return floor($v);
	}

	/**
	 * Round fractions up
	 *
	 * @param float $v
	 * @return float
	 */
	public function ceil($v)
	{
		return ceil($v);
	}

	/**
	 * Make a string's first character uppercase
	 *
	 * @param string $v
	 * @return string
	 */
	public function ucfirst($v)
	{
		return ucfirst($v);
	}

	/**
	 * Return router params
	 *
	 * @return array
	 */
	public function routerParams()
	{
		$router = $this->container->get('router');
		$request = $this->container->get('request');
		$routeName = $request->attributes->get('_route');
		$routeParams = $request->query->all();
		if ($router->getRouteCollection()->get($routeName)) {
			foreach ($router->getRouteCollection()->get($routeName)->compile()->getVariables() as $variable) {
				$routeParams[$variable] = $request->attributes->get($variable);
			}
		}

		return $routeParams;
	}

	/**
	 * Call counter
	 *
	 * @return void | \Symfony\Component\HttpFoundation\Response
	 */
	public function counter()
	{
		if (!$this->container->getParameter('syslog-ng')) {
			return $this->container->get('templating')->render('TubeMainBundle:Counter:counter.html.twig', array());
		}

		return;
	}

	/**
	 * Render static block
	 *
	 * @param string $blockname
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function staticBlock($blockname)
	{
		return $this->container->get('tube.page')->pageblock($blockname);
	}

	/**
	 * Render banner
	 *
	 * @param string $blockname
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function bannerAds($blockname)
	{
		return $this->container->get('tube.ads')->bannerads($blockname);
	}

	/**
	 * Cut string
	 *
	 * @param string $str
	 * @param number $quan
	 * @param number $cutWord //1 is yes; 0 is no
	 * @return string
	 */
	public function cutString($str, $quan, $cutWord = false)
	{
		if (mb_strlen($str) <= $quan) {
			return $str;
		}

		$string = $str;
		$string = implode(array_slice(explode('<br>', wordwrap($string, $quan, '<br>', $cutWord)), 0, 1));

		return $string . '...';
	}

	/**
	 * Set User style
	 *
	 * @param User $user
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function setUserStyle($user)
	{
		$view = $this->container->get('user.style')->getUserBackground($user);

		return $view;
	}

	/**
	 * Generate meta
	 *
	 * @return string
	 */
	public function generateMeta()
	{
		$request = $this->container->get('request');

		return $this->container->get('tube.seo')->getMeta($request->getPathInfo());
	}

	/**
	 * Generate meta
	 *
	 * @return string
	 */
	public function generateMetaTitle()
	{
		$request = $this->container->get('request');

		return $this->container->get('tube.seo')->getMetaTitle(rtrim($request->getRequestUri(),'?'));
	}

	/**
	 * Generate meta
	 *
	 * @return string
	 */
	public function generateMetaDescription()
	{
		$request = $this->container->get('request');

		return $this->container->get('tube.seo')->getMetaDescription(rtrim($request->getRequestUri(),'?'));
	}

	/**
	 * Generate meta
	 *
	 * @return string
	 */
	public function generateMetaKeywords()
	{
		$request = $this->container->get('request');

		return $this->container->get('tube.seo')->getMetaKeywords(rtrim($request->getRequestUri(),'?'));
	}

	/**
	 * var_dump
	 *
	 * @param mixed $v
	 * @return void
	 */
	public function varDump($v)
	{
		return var_dump($v, true);
	}

	/**
	 * Shuffle an array
	 *
	 * @param array $v
	 * @return array
	 */
	public function shuffle($v)
	{
		shuffle($v);

		return $v;
	}

	/**
	 * urldecode
	 *
	 * @param string $url
	 * @return string
	 */
	public function urlDecode($url)
	{
		return urldecode($url);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'tube_extension';
	}

	/**
	 * Get GEOIP_COUNTRY_CODE
	 *
	 * @return string
	 */
	public function getGeoipCountryCode()
	{
		return isset($_SERVER['GEOIP_COUNTRY_CODE'])?$_SERVER['GEOIP_COUNTRY_CODE']:'';
	}
}