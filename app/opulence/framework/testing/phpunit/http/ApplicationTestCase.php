<?php
/**
 * Copyright (C) 2015 David Young
 *
 * Defines the HTTP application test case
 */
namespace Opulence\Framework\Testing\PHPUnit\HTTP;

use LogicException;
use Monolog\Logger;
use Opulence\Applications\Environments\Environment;
use Opulence\Framework\HTTP\Kernel;
use Opulence\Framework\Testing\PHPUnit\ApplicationTestCase as BaseApplicationTestCase;
use Opulence\HTTP\Requests\Request;
use Opulence\HTTP\Responses\RedirectResponse;
use Opulence\HTTP\Responses\Response;
use Opulence\HTTP\Responses\ResponseHeaders;
use Opulence\Routing\Router;
use Opulence\Routing\Controller;

abstract class ApplicationTestCase extends BaseApplicationTestCase
{
    /** @var Router The router */
    protected $router = null;
    /** @var Request The default request */
    protected $defaultRequest = null;
    /** @var Kernel The HTTP kernel */
    protected $kernel = null;
    /** @var Response The response from the last route */
    protected $response = null;

    /**
     * Asserts that the response redirects to a URL
     *
     * @param string $url The expected URL
     */
    public function assertRedirectsTo($url)
    {
        $this->checkResponseIsSet();
        $this->assertTrue($this->response instanceof RedirectResponse && $this->response->getTargetURL() == $url);
    }

    /**
     * Asserts that the response's contents match the input
     *
     * @param mixed $expected The expected value
     */
    public function assertResponseContentEquals($expected)
    {
        $this->checkResponseIsSet();
        $this->assertEquals($expected, $this->response->getContent());
    }

    /**
     * Asserts that the response's cookie's value equals the input
     *
     * @param string $name The name of the cookie to search for
     * @param mixed $expected The expected value
     */
    public function assertResponseCookieValueEquals($name, $expected)
    {
        $this->checkResponseIsSet();
        $cookies = $this->response->getHeaders()->getCookies();
        $cookieValue = null;

        foreach ($cookies as $cookie) {
            if ($cookie->getName() == $name) {
                $cookieValue = $cookie->getValue();

                break;
            }
        }

        $this->assertEquals($expected, $cookieValue);
    }

    /**
     * Asserts that the response has a cookie
     *
     * @param string $name The name of the cookie to search for
     */
    public function assertResponseHasCookie($name)
    {
        $this->checkResponseIsSet();
        $cookies = $this->response->getHeaders()->getCookies();
        $wasFound = false;

        foreach ($cookies as $cookie) {
            if ($cookie->getName() == $name) {
                $wasFound = true;

                break;
            }
        }

        $this->assertTrue($wasFound);
    }

    /**
     * Asserts that the response has a header
     *
     * @param string $name The name of the header to search for
     */
    public function assertResponseHasHeader($name)
    {
        $this->checkResponseIsSet();
        $this->assertTrue($this->response->getHeaders()->has($name));
    }

    /**
     * Asserts that the response's header's value equals the input
     *
     * @param string $name The name of the header to search for
     * @param mixed $expected The expected value
     */
    public function assertResponseHeaderEquals($name, $expected)
    {
        $this->checkResponseIsSet();
        $this->assertEquals($expected, $this->response->getHeaders()->get($name));
    }

    /**
     * Asserts that the response is an internal server error
     */
    public function assertResponseIsInternalServerError()
    {
        $this->checkResponseIsSet();
        $this->assertEquals(ResponseHeaders::HTTP_INTERNAL_SERVER_ERROR, $this->response->getStatusCode());
    }

    /**
     * Asserts that the response is not found
     */
    public function assertResponseIsNotFound()
    {
        $this->checkResponseIsSet();
        $this->assertEquals(ResponseHeaders::HTTP_NOT_FOUND, $this->response->getStatusCode());
    }

    /**
     * Asserts that the response is OK
     */
    public function assertResponseIsOK()
    {
        $this->checkResponseIsSet();
        $this->assertEquals(ResponseHeaders::HTTP_OK, $this->response->getStatusCode());
    }

    /**
     * Asserts that the response is unauthorized
     */
    public function assertResponseIsUnauthorized()
    {
        $this->checkResponseIsSet();
        $this->assertEquals(ResponseHeaders::HTTP_UNAUTHORIZED, $this->response->getStatusCode());
    }

    /**
     * Asserts that the response status code equals a particular value
     *
     * @param int $statusCode The expected status code
     */
    public function assertResponseStatusCodeEquals($statusCode)
    {
        $this->checkResponseIsSet();
        $this->assertEquals($statusCode, $this->response->getStatusCode());
    }

    /**
     * Asserts that the view has a variable
     *
     * @param string $name The name of the variable to search for
     * @throws LogicException Thrown if the controller does not extend the base controller
     */
    public function assertViewHasVar($name)
    {
        $this->checkResponseIsSet();

        if (!$this->router->getMatchedController() instanceof Controller) {
            throw new LogicException("Controller does not extend " . Controller::class);
        }

        $this->assertNotNull($this->router->getMatchedController()->getView()->getVar($name));
    }

    /**
     * Asserts that the view has a variable with a certain value
     *
     * @param string $name The name of the tag to search for
     * @param mixed $expected The expected value
     * @throws LogicException Thrown if the controller does not extend the base controller
     */
    public function assertViewVarEquals($name, $expected)
    {
        $this->checkResponseIsSet();

        if (!$this->router->getMatchedController() instanceof Controller) {
            throw new LogicException("Controller does not extend " . Controller::class);
        }

        $this->assertEquals($expected, $this->router->getMatchedController()->getView()->getVar($name));
    }

    /**
     * @return Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Simulates a route for use in testing
     *
     * @param string $method The HTTP method to use
     * @param string $url The URL to route
     * @param Request|null $request The request to use
     * @return Response The response
     */
    public function route($method, $url, Request $request = null)
    {
        if ($request === null) {
            $request = $this->defaultRequest;
        }

        $parsedURL = parse_url($url);
        $request->setPath($parsedURL["path"]);
        $request->setMethod(strtoupper($method));
        $request->getHeaders()->set("HOST", isset($parsedURL["host"]) ? $parsedURL["host"] : "");
        $this->response = $this->kernel->handle($request);

        return $this->response;
    }

    /**
     * Sets up the tests
     */
    public function setUp()
    {
        $this->setApplication();
        $this->application->getEnvironment()->setName(Environment::TESTING);
        $this->application->start();
        $container = $this->application->getIoCContainer();
        $container->bind(Logger::class, $this->getKernelLogger());
        $this->router = $container->makeShared(Router::class);
        $this->kernel = $container->makeShared(Kernel::class);
        $this->kernel->addMiddleware($this->getGlobalMiddleware());
        $this->defaultRequest = new Request([], [], [], [], [], []);
    }

    /**
     * Gets the list of global middleware
     *
     * @return array The list of global middleware classes
     */
    abstract protected function getGlobalMiddleware();

    /**
     * Checks if the response was set
     * Useful for making sure the response was set before making any assertions on it
     */
    private function checkResponseIsSet()
    {
        if ($this->response === null) {
            $this->fail("Must call route() before assertions");
        }
    }
}