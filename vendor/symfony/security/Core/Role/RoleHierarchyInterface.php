<?php
 namespace Symfony\Component\Security\Core\Role; interface RoleHierarchyInterface { public function getReachableRoles(array $roles); } 