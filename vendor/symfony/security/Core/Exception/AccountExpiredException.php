<?php
 namespace Symfony\Component\Security\Core\Exception; class AccountExpiredException extends AccountStatusException { public function getMessageKey() { return 'Account has expired.'; } } 