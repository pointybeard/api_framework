<?php

declare(strict_types=1);

/*
 * This file is part of the "RESTful API Framework Extension for Symphony CMS" repository.
 *
 * Copyright 2017-2021 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\Extensions\Api_Framework;

use pointybeard\Helpers\Functions\Arrays;

/**
 * Transformer
 * Modifies an array with various transformations.
 */
class Transformer
{
    private $transformations = [];

    public function append(Transformation $transformation): self
    {
        $this->transformations[] = $transformation;

        return $this;
    }

    public function transformations(): array
    {
        return $this->transformations;
    }

    public function run(array $input): array
    {
        $totalTransformantionCount = count($this->transformations);
        $preserveAttributes = true;

        foreach ($this->transformations as $index => $t) {
            // #17 - Check if there are any more transformers remaining. If no, we can
            // trigger a cleanup by setting $preserveAttributes to false. This will
            // remove all @attribute arrays during the next recursion.
            if ($index >= $totalTransformantionCount - 1) {
                $preserveAttributes = false;
            }

            // Run the transformation.
            $input = $this->recursiveApplyTransformationToArray($input, $t, $preserveAttributes);
        }

        return $input;
    }

    private function recursiveApplyTransformationToArray(array $input, Transformation $transformation, bool $preserveAttributes = true): array
    {
        $result = [];

        $attributes = isset($input['@attributes']) ? $input['@attributes'] : [];

        // Strip out the attributes.
        unset($input['@attributes']);

        // Run the input against the transformation test. If it passes, run
        // the actual transformation
        if (true === $transformation->test($input, $attributes)) {
            $input = $transformation->action($input, $attributes);
        }

        // Are we dealing with an associative array, or a sequential indexed array
        $isAssoc = Arrays\array_is_assoc($input);

        // Iterate over each element in the array and decide if we need to move deeper
        foreach ($input as $key => $value) {
            $next = (
                is_array($value)
            // It's an array, so go deeper.
            ? $this->recursiveApplyTransformationToArray($value, $transformation, $preserveAttributes)
            // Non-array, just append it back in and return
            : $value
        );

            // Preserve array indexes
            // #16 - Using $next instead of $value.
            $isAssoc ? $result[$key] = $next : array_push($result, $next);
        }

        // #15 & #17 - Unless it's an empty array or we deliberately ask for it to be
        // left out, put attributes back. They may be required by subsequent
        // transformers. The last transformer in the chain will trigger a cleanup
        // anyway.
        if (true == $preserveAttributes && !empty($attributes)) {
            $result['@attributes'] = $attributes;
        }

        return $result;
    }
}
