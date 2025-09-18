<?php

namespace MaxBrennemann\PhpUtilities;

class TemplateHelper
{

	public static function insertTemplate(string $path, ?array $parameters = null): void
	{
		if ($parameters == null) {
			$parameters = [];
		}

		if (file_exists($path)) {
			extract($parameters);
			include $path;
		}
	}
}
