<?php
 namespace Symfony\Component\Security\Core\Exception; class InvalidCsrfTokenException extends AuthenticationException { public function getMessageKey() { return 'Invalid CSRF token.'; } } 