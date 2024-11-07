<?php

namespace MaxBrennemann\PhpUtilities; 

function insertTemplate($path, array $parameters = null)
{
    if ($parameters == null) {
        $parameters = [];
    }

	if (file_exists($path)) {
		extract($parameters);
		include $path;
	}
}
