<?php
namespace Symfony\Component\Security\Http\EntryPoint;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
class BasicAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private $realmName;
    public function __construct($realmName)
    {
        $this->realmName = $realmName;
    }
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $response = new Response();
        $response
            ->headers
            ->set('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realmName));
        $response->setStatusCode(401);
        return $response;
    }
}

