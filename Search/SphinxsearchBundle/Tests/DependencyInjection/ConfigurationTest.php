<?php

namespace Search\SphinxsearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @dataProvider dataForProcessedConfiguration
	 */
	public function testProcessedConfiguration($configs, $expectedConfig)
	{
		$processor = new Processor();
		$configuration = new Configuration();
		$config = $processor->processConfiguration($configuration, $configs);
		$this->assertEquals($expectedConfig, $config);
	}

	/**
	 *
	 * @return array
	 */
	public function dataForProcessedConfiguration()
	{
		return array(
			array(
				// Only 'indexes' is set, with ONE index
				array(
					null,
					array(
						'indexes' => array(
							'Articles' => array(
								'article' => array(
									'Name' => '5',
									'Description' => '1',
								),
							),
						)
					),
					null,
				),
				array(
					'indexer' => array(
						'bin' => '/usr/bin/indexer',
					),
					'indexes' => array(
						'Articles' => array(
							'article' => array(
								'Name' => '5',
								'Description' => '1',
							),
						),
					),
					'searchd' => array(
						'host' => 'localhost',
						'port' => '9312',
						'socket' => null,
					),
				)
			),
			// All set, with ONE index
			array(
				array(
					array(
						'indexer' => array(
							'bin' => '/usr/bin/indexer',
						)
					),
					array(
						'indexes' => array(
							'Articles' => array(
								'article' => array(
									'Name' => '5',
									'Description' => '1',
								),
							),
						)
					),
					array(
						'searchd' => array(
							'host' => 'localhost',
							'port' => '9312',
							'socket' => null,
						),
					),
				),
				array(
					'indexer' => array(
						'bin' => '/usr/bin/indexer',
					),
					'indexes' => array(
						'Articles' => array(
							'article' => array(
								'Name' => '5',
								'Description' => '1',
							),
						),
					),
					'searchd' => array(
						'host' => 'localhost',
						'port' => '9312',
						'socket' => null,
					),
				)
			),
			// All 'indexes' and 'searchd' set
			array(
				array(
					null,
					array(
						'indexes' => array(
							'Articles' => array(
								'article' => array(
									'Name' => '5',
									'Description' => '1',
								),
							),
						)
					),
					array(
						'searchd' => array(
							'host' => 'localhost',
							'port' => '9312',
							'socket' => null,
						),
					),
				),
				array(
					'indexer' => array(
						'bin' => '/usr/bin/indexer',
					),
					'indexes' => array(
						'Articles' => array(
							'article' => array(
								'Name' => '5',
								'Description' => '1',
							),
						),
					),
					'searchd' => array(
						'host' => 'localhost',
						'port' => '9312',
						'socket' => null,
					),
				)
			),
		);
	}

}
