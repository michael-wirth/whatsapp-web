<?php
 namespace Silex\Provider\Validator; use Pimple\Container; use Symfony\Component\Validator\Constraint; use Symfony\Component\Validator\ConstraintValidatorFactory as BaseConstraintValidatorFactory; class ConstraintValidatorFactory extends BaseConstraintValidatorFactory { protected $container; protected $serviceNames; public function __construct(Container $container, array $serviceNames = array(), $propertyAccessor = null) { parent::__construct($propertyAccessor); $this->container = $container; $this->serviceNames = $serviceNames; } public function getInstance(Constraint $constraint) { $name = $constraint->validatedBy(); if (isset($this->serviceNames[$name])) { return $this->container[$this->serviceNames[$name]]; } return parent::getInstance($constraint); } } 