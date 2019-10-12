<?php
 namespace Symfony\Component\Routing\Matcher\Dumper; use Symfony\Component\Routing\Route; use Symfony\Component\Routing\RouteCollection; use Symfony\Component\ExpressionLanguage\ExpressionLanguage; use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface; class PhpMatcherDumper extends MatcherDumper { private $expressionLanguage; private $expressionLanguageProviders = array(); public function dump(array $options = array()) { $options = array_replace(array( 'class' => 'ProjectUrlMatcher', 'base_class' => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher', ), $options); $interfaces = class_implements($options['base_class']); $supportsRedirections = isset($interfaces['Symfony\\Component\\Routing\\Matcher\\RedirectableUrlMatcherInterface']); return <<<EOF
<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * {$options['class']}.
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class {$options['class']} extends {$options['base_class']}
{
    /**
     * Constructor.
     */
    public function __construct(RequestContext \$context)
    {
        \$this->context = \$context;
    }

{$this->generateMatchMethod($supportsRedirections)}
}

EOF;
} public function addExpressionLanguageProvider(ExpressionFunctionProviderInterface $provider) { $this->expressionLanguageProviders[] = $provider; } private function generateMatchMethod($supportsRedirections) { $code = rtrim($this->compileRoutes($this->getRoutes(), $supportsRedirections), "\n"); return <<<EOF
    public function match(\$pathinfo)
    {
        \$allow = array();
        \$pathinfo = rawurldecode(\$pathinfo);
        \$trimmedPathinfo = rtrim(\$pathinfo, '/');
        \$context = \$this->context;
        \$request = \$this->request;
        \$requestMethod = \$canonicalMethod = \$context->getMethod();
        \$scheme = \$context->getScheme();

        if ('HEAD' === \$requestMethod) {
            \$canonicalMethod = 'GET';
        }


$code

        throw 0 < count(\$allow) ? new MethodNotAllowedException(array_unique(\$allow)) : new ResourceNotFoundException();
    }
EOF;
} private function compileRoutes(RouteCollection $routes, $supportsRedirections) { $fetchedHost = false; $groups = $this->groupRoutesByHostRegex($routes); $code = ''; foreach ($groups as $collection) { if (null !== $regex = $collection->getAttribute('host_regex')) { if (!$fetchedHost) { $code .= "        \$host = \$context->getHost();\n\n"; $fetchedHost = true; } $code .= sprintf("        if (preg_match(%s, \$host, \$hostMatches)) {\n", var_export($regex, true)); } $tree = $this->buildStaticPrefixCollection($collection); $groupCode = $this->compileStaticPrefixRoutes($tree, $supportsRedirections); if (null !== $regex) { $groupCode = preg_replace('/^.{2,}$/m', '    $0', $groupCode); $code .= $groupCode; $code .= "        }\n\n"; } else { $code .= $groupCode; } } return $code; } private function buildStaticPrefixCollection(DumperCollection $collection) { $prefixCollection = new StaticPrefixCollection(); foreach ($collection as $dumperRoute) { $prefix = $dumperRoute->getRoute()->compile()->getStaticPrefix(); $prefixCollection->addRoute($prefix, $dumperRoute); } $prefixCollection->optimizeGroups(); return $prefixCollection; } private function compileStaticPrefixRoutes(StaticPrefixCollection $collection, $supportsRedirections, $ifOrElseIf = 'if') { $code = ''; $prefix = $collection->getPrefix(); if (!empty($prefix) && '/' !== $prefix) { $code .= sprintf("    %s (0 === strpos(\$pathinfo, %s)) {\n", $ifOrElseIf, var_export($prefix, true)); } $ifOrElseIf = 'if'; foreach ($collection->getItems() as $route) { if ($route instanceof StaticPrefixCollection) { $code .= $this->compileStaticPrefixRoutes($route, $supportsRedirections, $ifOrElseIf); $ifOrElseIf = 'elseif'; } else { $code .= $this->compileRoute($route[1]->getRoute(), $route[1]->getName(), $supportsRedirections, $prefix)."\n"; $ifOrElseIf = 'if'; } } if (!empty($prefix) && '/' !== $prefix) { $code .= "    }\n\n"; $code = preg_replace('/^.{2,}$/m', '    $0', $code); } return $code; } private function compileRoute(Route $route, $name, $supportsRedirections, $parentPrefix = null) { $code = ''; $compiledRoute = $route->compile(); $conditions = array(); $hasTrailingSlash = false; $matches = false; $hostMatches = false; $methods = $route->getMethods(); $supportsTrailingSlash = $supportsRedirections && (!$methods || in_array('HEAD', $methods) || in_array('GET', $methods)); $regex = $compiledRoute->getRegex(); if (!count($compiledRoute->getPathVariables()) && false !== preg_match('#^(.)\^(?P<url>.*?)\$\1#'.(substr($regex, -1) === 'u' ? 'u' : ''), $regex, $m)) { if ($supportsTrailingSlash && substr($m['url'], -1) === '/') { $conditions[] = sprintf('%s === $trimmedPathinfo', var_export(rtrim(str_replace('\\', '', $m['url']), '/'), true)); $hasTrailingSlash = true; } else { $conditions[] = sprintf('%s === $pathinfo', var_export(str_replace('\\', '', $m['url']), true)); } } else { if ($compiledRoute->getStaticPrefix() && $compiledRoute->getStaticPrefix() !== $parentPrefix) { $conditions[] = sprintf('0 === strpos($pathinfo, %s)', var_export($compiledRoute->getStaticPrefix(), true)); } if ($supportsTrailingSlash && $pos = strpos($regex, '/$')) { $regex = substr($regex, 0, $pos).'/?$'.substr($regex, $pos + 2); $hasTrailingSlash = true; } $conditions[] = sprintf('preg_match(%s, $pathinfo, $matches)', var_export($regex, true)); $matches = true; } if ($compiledRoute->getHostVariables()) { $hostMatches = true; } if ($route->getCondition()) { $conditions[] = $this->getExpressionLanguage()->compile($route->getCondition(), array('context', 'request')); } $conditions = implode(' && ', $conditions); $code .= <<<EOF
        // $name
        if ($conditions) {

EOF;
$gotoname = 'not_'.preg_replace('/[^A-Za-z0-9_]/', '', $name); if ($methods) { if (1 === count($methods)) { if ($methods[0] === 'HEAD') { $code .= <<<EOF
            if ('HEAD' !== \$requestMethod) {
                \$allow[] = 'HEAD';
                goto $gotoname;
            }


EOF;
} else { $code .= <<<EOF
            if ('$methods[0]' !== \$canonicalMethod) {
                \$allow[] = '$methods[0]';
                goto $gotoname;
            }


EOF;
} } else { $methodVariable = 'requestMethod'; if (in_array('GET', $methods)) { $methodVariable = 'canonicalMethod'; $methods = array_values(array_filter($methods, function ($method) { return 'HEAD' !== $method; })); } if (1 === count($methods)) { $code .= <<<EOF
            if ('$methods[0]' !== \$$methodVariable) {
                \$allow[] = '$methods[0]';
                goto $gotoname;
            }


EOF;
} else { $methods = implode("', '", $methods); $code .= <<<EOF
            if (!in_array(\$$methodVariable, array('$methods'))) {
                \$allow = array_merge(\$allow, array('$methods'));
                goto $gotoname;
            }


EOF;
} } } if ($hasTrailingSlash) { $code .= <<<EOF
            if (substr(\$pathinfo, -1) !== '/') {
                return \$this->redirect(\$pathinfo.'/', '$name');
            }


EOF;
} if ($schemes = $route->getSchemes()) { if (!$supportsRedirections) { throw new \LogicException('The "schemes" requirement is only supported for URL matchers that implement RedirectableUrlMatcherInterface.'); } $schemes = str_replace("\n", '', var_export(array_flip($schemes), true)); $code .= <<<EOF
            \$requiredSchemes = $schemes;
            if (!isset(\$requiredSchemes[\$scheme])) {
                return \$this->redirect(\$pathinfo, '$name', key(\$requiredSchemes));
            }


EOF;
} if ($matches || $hostMatches) { $vars = array(); if ($hostMatches) { $vars[] = '$hostMatches'; } if ($matches) { $vars[] = '$matches'; } $vars[] = "array('_route' => '$name')"; $code .= sprintf( "            return \$this->mergeDefaults(array_replace(%s), %s);\n", implode(', ', $vars), str_replace("\n", '', var_export($route->getDefaults(), true)) ); } elseif ($route->getDefaults()) { $code .= sprintf("            return %s;\n", str_replace("\n", '', var_export(array_replace($route->getDefaults(), array('_route' => $name)), true))); } else { $code .= sprintf("            return array('_route' => '%s');\n", $name); } $code .= "        }\n"; if ($methods) { $code .= "        $gotoname:\n"; } return $code; } private function groupRoutesByHostRegex(RouteCollection $routes) { $groups = new DumperCollection(); $currentGroup = new DumperCollection(); $currentGroup->setAttribute('host_regex', null); $groups->add($currentGroup); foreach ($routes as $name => $route) { $hostRegex = $route->compile()->getHostRegex(); if ($currentGroup->getAttribute('host_regex') !== $hostRegex) { $currentGroup = new DumperCollection(); $currentGroup->setAttribute('host_regex', $hostRegex); $groups->add($currentGroup); } $currentGroup->add(new DumperRoute($name, $route)); } return $groups; } private function getExpressionLanguage() { if (null === $this->expressionLanguage) { if (!class_exists('Symfony\Component\ExpressionLanguage\ExpressionLanguage')) { throw new \RuntimeException('Unable to use expressions as the Symfony ExpressionLanguage component is not installed.'); } $this->expressionLanguage = new ExpressionLanguage(null, $this->expressionLanguageProviders); } return $this->expressionLanguage; } } 