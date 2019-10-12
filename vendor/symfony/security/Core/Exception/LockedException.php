<?php
 namespace Symfony\Component\Security\Core\Exception; class LockedException extends AccountStatusException { public function getMessageKey() { return 'Account is locked.'; } } 