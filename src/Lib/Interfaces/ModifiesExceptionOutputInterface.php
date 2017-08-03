<?php

namespace Symphony\ApiFramework\Lib\Interfaces;

interface ModifiesExceptionOutputInterface
{
	public function modifyOutput(array $output);
}
