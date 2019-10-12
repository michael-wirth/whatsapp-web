<?php
 namespace Symfony\Component\Security\Http; use Symfony\Component\HttpFoundation\Request; interface FirewallMapInterface { public function getListeners(Request $request); } 