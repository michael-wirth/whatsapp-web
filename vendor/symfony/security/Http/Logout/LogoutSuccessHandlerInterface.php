<?php
 namespace Symfony\Component\Security\Http\Logout; use Symfony\Component\HttpFoundation\Request; use Symfony\Component\HttpFoundation\Response; interface LogoutSuccessHandlerInterface { public function onLogoutSuccess(Request $request); } 