<?php

namespace PSS\Bundle\MockeryBundle\Behat\Context;

use Behat\Behat\Context\BehatContext;
use Behat\Mink\Exception\ExpectationException;

class MockerContainerContext extends BehatContext
{
    /**
     * @return \Mockery\Mock
     */
    public function mockService()
    {
        return call_user_func_array(array($this->getMockerContainer(), 'mock'), func_get_args());
    }

    /**
     * @param \Behat\Behat\Event\ScenarioEvent|\Behat\Behat\Event\OutlineExampleEvent $event
     *
     * @AfterScenario
     *
     * @return null
     */
    public function verifyPendingExpectations($event)
    {
        if (!$this->isClientContainerAvailable()) {
            return;
        }

        $container = $this->getMockerContainer();
        $mockedServices = $container->getMockedServices();

        foreach ($mockedServices as $id => $service) {
            $this->verifyService($service);
            $container->unmock($id);
        }
    }

    /**
     * This step is not meant to be used directly in your scenarios.
     * You should rather build your steps upon it with \Behat\Behat\Context\Step\Then class:
     *
     *     return new \Behat\Behat\Context\Step\Then('the "user.service" should meet my expectations');
     *
     * @Given /^(the )?"(?P<serviceId>(?:[^"])*)" service should meet my expectations$/
     *
     * @return null
     */
    public function theServiceShouldMeetMyExpectations($serviceId)
    {
        $container = $this->getMockerContainer();
        $service = $container->get($serviceId);

        $this->verifyService($service);
    }

    /**
     * @param object $service
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     *
     * @return null
     */
    protected function verifyService($service)
    {
        try {
            $service->mockery_verify();
        } catch (\Mockery\CountValidator\Exception $exception) {
            throw new ExpectationException('One of the expected services was not called', $this->getMainContext()->getSession(), $exception);
        }
    }

    /**
     * @throws \LogicException when used with not supporteddriver or container cannot create mocks
     *
     * @return \Symfony\Component\DependencyInjection\Container
     */
    protected function getMockerContainer()
    {
        if ($this->isClientContainerAvailable()) {
            $driver = $this->getMainContext()->getSession()->getDriver();
            $container = $driver->getClient()->getContainer();

            if (!$container instanceof \PSS\Bundle\MockeryBundle\DependencyInjection\MockerContainer) {
                throw new \LogicException('Container is not able to mock the services');
            }

            return $container;
        }

        throw new \LogicException('Session has no access to client container');
    }

    /**
     * @return boolean
     */
    protected function isClientContainerAvailable()
    {
        $driver = $this->getMainContext()->getSession()->getDriver();

        return $driver instanceof \Behat\MinkBundle\Driver\SymfonyDriver;
    }
}
