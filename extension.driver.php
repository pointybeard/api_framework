<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symphony\ApiFramework\Lib;

Class extension_api_framework extends Extension
{
    public function getSubscribedDelegates(){
        return[
            [
                'page' => '/all/',
                'delegate' => 'ModifySymphonyLauncher',
                'callback' => 'setJSONLauncher'
            ],
            [
                'page' => '/frontend/',
                'delegate' => 'FrontendOutputPreGenerate',
                'callback' => 'setBoilerplateXSL'
            ],
            [
                'page' => '/frontend/',
                'delegate' => 'APIFrameworkJSONRendererAppendTransformations',
                'callback' => 'appendTransformations'
            ],
        ];
    }

    public function appendTransformations($context) {

      // Add the @jsonForceArray transformation
      $context['transformer']->append(
        new Lib\Transformation(
          function(array $input, array $attributes=[]){
              // First make sure there is an attributes array
              if(empty($attributes)) {
                  return false;
              }
              // Only looking at the jsonForceArray property
              elseif(!isset($attributes['jsonForceArray']) || $attributes['jsonForceArray'] !== "true") {
                  return false;
              }
              // This is already an indexed array.
              elseif(!Lib\array_is_assoc($input)) {
                  return false;
              }
              // jsonForceArray is set, and it's true
              return true;
          },
          function(array $input, array $attributes=[]){
              $result = [];
              // Encapsulate everything in an array
              foreach($input as $key => $value) {
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
        if($_REQUEST['mode'] == 'administration') {
            return;
        }
        define('SYMPHONY_LAUNCHER', 'renderer_json');

        include __DIR__ . '/src/Includes/JsonRendererLauncher.php';
    }

    public function setBoilerplateXSL($context)
    {
        // @todo: This should only be available if logged in
        if(!isset($_GET['boilerplate-xsl']) && (!in_array('JSON', Frontend::Page()->pageData()['type'])
            || !in_array('boilerplate-xsl', Frontend::Page()->pageData()['type']))) {
            return;
        }

        $context['xsl'] = <<<'XSL'
<?xml version="1.0" encoding="UTF-8"?><xsl:stylesheet version="1.0"
 xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="node()|@*">
      <xsl:copy>
        <xsl:apply-templates select="node()|@*"/>
      </xsl:copy>
    </xsl:template>
</xsl:stylesheet>
XSL;
    }
}
