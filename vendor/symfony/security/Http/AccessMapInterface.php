<?php
 namespace Symfony\Component\Security\Http; use Symfony\Component\HttpFoundation\Request; interface AccessMapInterface { public function getPatterns(Request $request); } 