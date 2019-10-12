<?php
 namespace Symfony\Polyfill\Util; if (extension_loaded('mbstring')) { class Binary extends BinaryOnFuncOverload {} } else { class Binary extends BinaryNoFuncOverload {} } 