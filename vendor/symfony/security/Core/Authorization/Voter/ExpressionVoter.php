<?php
 namespace Symfony\Component\Security\Core\Authorization\Voter; use Symfony\Component\Security\Core\Authentication\Token\TokenInterface; use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface; use Symfony\Component\Security\Core\Authorization\ExpressionLanguage; use Symfony\Component\Security\Core\Role\RoleHierarchyInterface; use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface; use Symfony\Component\ExpressionLanguage\Expression; use Symfony\Component\HttpFoundation\Request; class ExpressionVoter implements VoterInterface { private $expressionLanguage; private $trustResolver; private $roleHierarchy; public function __construct(ExpressionLanguage $expressionLanguage, AuthenticationTrustResolverInterface $trustResolver, RoleHierarchyInterface $roleHierarchy = null) { $this->expressionLanguage = $expressionLanguage; $this->trustResolver = $trustResolver; $this->roleHierarchy = $roleHierarchy; } public function addExpressionLanguageProvider(ExpressionFunctionProviderInterface $provider) { $this->expressionLanguage->registerProvider($provider); } public function vote(TokenInterface $token, $subject, array $attributes) { $result = VoterInterface::ACCESS_ABSTAIN; $variables = null; foreach ($attributes as $attribute) { if (!$attribute instanceof Expression) { continue; } if (null === $variables) { $variables = $this->getVariables($token, $subject); } $result = VoterInterface::ACCESS_DENIED; if ($this->expressionLanguage->evaluate($attribute, $variables)) { return VoterInterface::ACCESS_GRANTED; } } return $result; } private function getVariables(TokenInterface $token, $subject) { if (null !== $this->roleHierarchy) { $roles = $this->roleHierarchy->getReachableRoles($token->getRoles()); } else { $roles = $token->getRoles(); } $variables = array( 'token' => $token, 'user' => $token->getUser(), 'object' => $subject, 'subject' => $subject, 'roles' => array_map(function ($role) { return $role->getRole(); }, $roles), 'trust_resolver' => $this->trustResolver, ); if ($subject instanceof Request) { $variables['request'] = $subject; } return $variables; } } 