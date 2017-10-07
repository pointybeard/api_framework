<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symphony\ApiFramework\Lib;

class extension_api_framework extends Extension
{
    public function getSubscribedDelegates()
    {
        return[
            [
                'page' => '/all/',
                'delegate' => 'ModifySymphonyLauncher',
                'callback' => 'setJSONLauncher'
            ],
            [
                'page' => '/frontend/',
                'delegate' => 'APIFrameworkJSONRendererAppendTransformations',
                'callback' => 'appendTransformations'
            ],
        ];
    }

    public function appendTransformations($context)
    {

      // Add the @jsonForceArray transformation
      $context['transformer']->append(
        new Lib\Transformation(
          function (array $input, array $attributes=[]) {
              // First make sure there is an attributes array
              if (empty($attributes)) {
                  return false;
              }
              // Only looking at the jsonForceArray property
              elseif (!isset($attributes['jsonForceArray']) || $attributes['jsonForceArray'] !== "true") {
                  return false;
              }
              // This is already an indexed array.
              elseif (!Lib\array_is_assoc($input)) {
                  return false;
              }
              // jsonForceArray is set, and it's true
              return true;
          },
          function (array $input, array $attributes=[]) {
              $result = [];
              // Encapsulate everything in an array
              foreach ($input as $key => $value) {
                  $result[$key] = $value;
                  unset($input[$key]);
              }
              $input[] = $result;
              return $input;
          }
        )
      );
    }

    public function setJSONLauncher($context)
    {
        if ($_REQUEST['mode'] == 'administration') {
            return;
        }
        define('SYMPHONY_LAUNCHER', 'renderer_json');

        include __DIR__ . '/src/Includes/JsonRendererLauncher.php';
    }
}
