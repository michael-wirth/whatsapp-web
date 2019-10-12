<?php
 namespace Symfony\Component\Security\Core\Exception; class DisabledException extends AccountStatusException { public function getMessageKey() { return 'Account is disabled.'; } } 