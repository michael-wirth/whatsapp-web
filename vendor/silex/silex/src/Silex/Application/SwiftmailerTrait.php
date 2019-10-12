<?php
 namespace Silex\Application; trait SwiftmailerTrait { public function mail(\Swift_Message $message, &$failedRecipients = null) { return $this['mailer']->send($message, $failedRecipients); } } 