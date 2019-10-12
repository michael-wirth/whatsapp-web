<?php
 namespace Symfony\Component\Security\Http\Firewall; use Symfony\Component\HttpKernel\Event\GetResponseEvent; interface ListenerInterface { public function handle(GetResponseEvent $event); } 