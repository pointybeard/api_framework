<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Traits;

use pointybeard\Symphony\Extensions\Api_Framework\JsonRequest;

/**
 * This trait provides a standarised way to include the input data in
 * output coming from an exception. Exceptions that use this must
 * implement ModifiesExceptionOutputInterface as well.
 */
trait HasIncludeInputDataInExceptionOutputTrait
{
    public function modifyOutput(array $output): array
    {

        $output = parent::modifyOutput($output);

        $request = JsonRequest::createFromGlobals();

        // (guard) request->json and request->request->all are empty
        if((false == isset($request->json) || true == empty($request->json->all())) && true == empty($request->request->all())) {
            return $output;
        }

        $output["data"] = [];

        if (true == isset($request->json)) {
            foreach ($request->json->all() as $key => $value) {
                if (false == array_key_exists($key, $output['data'])) {
                    $output['data'][$key] = $value;
                }
            }
        }

        foreach ($request->request->all() as $key => $value) {
            if (false == array_key_exists($key, $output['data'])) {
                $output['data'][$key] = $value;
            }
        }

        return $output;
    }
}
