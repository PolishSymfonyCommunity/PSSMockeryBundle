PSSMockeryBundle
================

Symfony2 Mockery integration bundle. Currently it supports service mocking.

Usage
-----

Add a namespace and replace base container class for test environment in `app/AppKernel.php`:

.. code-block:: php

    /**
     * @return null
     */
    public function registerBundles()
    {
        $loader->registerNamespaces(array(
            // ...
            'PSS' => __DIR__.'/../vendor/bundles'
        ));
    }

    /**
     * @return string
     */
    protected function getContainerBaseClass()
    {
        if ('test' == $this->environment) {
            return '\PSS\Bundle\MockeryBundle\DependencyInjection\MockerContainer';
        }

        return parent::getContainerBaseClass();
    }

If you want to use it with Behat enable sub-context in your `FeatureContext` class:

.. code-block:: php

    /**
     * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
     *
     * @return null
     */
    public function __construct(HttpKernelInterface $kernel)
    {
        parent::__construct($kernel);

        $this->useContext('container', new \PSS\Bundle\MockeryBundle\Behat\Context\MockerContainerContext($kernel));
    }

Example story:

.. code-block::

    Feature: Submitting sales request form
      As a Visitor
      I want to contact sales
      In order to receive more information

      Scenario: Submitting the form
        When I go to "/contact-us"
         And I complete the contact us form with following information
           |First name|Last name|Email                |
           |Jakub     |Zalas    |jzalas+spam@gmail.com|
         And CRM API is available
         And I submit the contact us form
        Then the contact request should be sent to the CRM

Step definitions:

.. code-block:: php

    /**
     * @Given /^CRM API is available$/
     *
     * @return null
     */
    public function crmApiIsAvailable()
    {
        $this->getMainContext()->getSubContext('container')
            ->mockService('crm.client', 'PSS\Crm\Client')
            ->shouldReceive('send')->once()->andReturn(true);
    }

    /**
     * @Given /^(the )?contact request should be sent to (the )?CRM$/
     *
     * @return null
     */
    public function theContactRequestShouldBeSentToCrm()
    {
        return new Then(sprintf('the "%s" service should meet my expectations', 'crm.client'));
    }

To discuss
----------

* Does it have to be a bundle? Currently it's rather a Symfony independent library.
* Is this the right approach/implementation?
* Do we need more features?
