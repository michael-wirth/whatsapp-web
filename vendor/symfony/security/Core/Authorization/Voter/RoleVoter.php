<?php
 namespace Symfony\Component\Security\Core\Authorization\Voter; use Symfony\Component\Security\Core\Authentication\Token\TokenInterface; use Symfony\Component\Security\Core\Role\RoleInterface; class RoleVoter implements VoterInterface { private $prefix; public function __construct($prefix = 'ROLE_') { $this->prefix = $prefix; } public function vote(TokenInterface $token, $subject, array $attributes) { $result = VoterInterface::ACCESS_ABSTAIN; $roles = $this->extractRoles($token); foreach ($attributes as $attribute) { if ($attribute instanceof RoleInterface) { $attribute = $attribute->getRole(); } if (!is_string($attribute) || 0 !== strpos($attribute, $this->prefix)) { continue; } $result = VoterInterface::ACCESS_DENIED; foreach ($roles as $role) { if ($attribute === $role->getRole()) { return VoterInterface::ACCESS_GRANTED; } } } return $result; } protected function extractRoles(TokenInterface $token) { return $token->getRoles(); } } 