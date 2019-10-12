<?php
 namespace Symfony\Component\HttpKernel\HttpCache; use Symfony\Component\HttpFoundation\Response; class ResponseCacheStrategy implements ResponseCacheStrategyInterface { private $cacheable = true; private $embeddedResponses = 0; private $ttls = array(); private $maxAges = array(); private $isNotCacheableResponseEmbedded = false; public function add(Response $response) { if (!$response->isFresh() || !$response->isCacheable()) { $this->cacheable = false; } else { $maxAge = $response->getMaxAge(); $this->ttls[] = $response->getTtl(); $this->maxAges[] = $maxAge; if (null === $maxAge) { $this->isNotCacheableResponseEmbedded = true; } } ++$this->embeddedResponses; } public function update(Response $response) { if (0 === $this->embeddedResponses) { return; } if ($response->isValidateable()) { $response->setEtag(null); $response->setLastModified(null); } if (!$response->isFresh()) { $this->cacheable = false; } if (!$this->cacheable) { $response->headers->set('Cache-Control', 'no-cache, must-revalidate'); return; } $this->ttls[] = $response->getTtl(); $this->maxAges[] = $response->getMaxAge(); if ($this->isNotCacheableResponseEmbedded) { $response->headers->removeCacheControlDirective('s-maxage'); } elseif (null !== $maxAge = min($this->maxAges)) { $response->setSharedMaxAge($maxAge); $response->headers->set('Age', $maxAge - min($this->ttls)); } $response->setMaxAge(0); } } 