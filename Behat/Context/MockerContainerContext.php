<?php

namespace PSS\Bundle\MockeryBundle\Behat\Context;

use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Symfony2Extension\Context\KernelAwareInterface;
use Behat\Symfony2Extension\Driver\KernelDriver;
use Mockery\CountValidator\Exception as CountValidatorException;
use PSS\Bundle\MockeryBundle\DependencyInjection\MockerContainer;
use Symfony\Component\HttpKernel\KernelInterface;

class MockerContainerContext extends RawMinkContext implements KernelAwareInterface
{
    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface $kernel
     */
    private $kernel = null;

    /**
     * @return \Mockery\Mock
     */
    public function mockService()
    {
        return call_user_func_array(array($this->getMockerContainer(), 'mock'), func_get_args());
    }

    /**
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
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
        if (!$this->isKernelDriverUsed()) {
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
        } catch (CountValidatorException $exception) {
            throw new ExpectationException('One of the expected services was not called', $this->getSession(), $exception);
        }
    }

    /**
     * @throws \LogicException when used with not supporteddriver or container cannot create mocks
     *
     * @return \Symfony\Component\DependencyInjection\Container
     */
    protected function getMockerContainer()
    {
        if ($this->isKernelDriverUsed()) {
            $container = $this->kernel->getContainer();

            if (!$container instanceof MockerContainer) {
                throw new \LogicException('Container is not able to mock the services');
            }

            return $container;
        }

        throw new \LogicException('Session has no access to client container');
    }

    /**
     * @return boolean
     */
    protected function isKernelDriverUsed()
    {
        $driver = $this->getSession()->getDriver();

        return $driver instanceof KernelDriver;
    }
}
