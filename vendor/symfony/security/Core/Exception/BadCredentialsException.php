<?php
 namespace Symfony\Component\Security\Core\Exception; class BadCredentialsException extends AuthenticationException { public function getMessageKey() { return 'Invalid credentials.'; } } 