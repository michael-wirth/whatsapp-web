<?php
 namespace Symfony\Component\Security\Core\Exception; class NonceExpiredException extends AuthenticationException { public function getMessageKey() { return 'Digest nonce has expired.'; } } 