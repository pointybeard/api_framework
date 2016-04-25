<?php

include __DIR__ . '/vendor/autoload.php';

Class extension_symphony_api extends Extension {
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

        ];
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
