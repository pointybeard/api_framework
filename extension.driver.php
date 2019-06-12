<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use Symphony\Extensions\ApiFramework;
use pointybeard\Helpers\Functions\Arrays;
use pointybeard\Symphony\SectionBuilder;

// This file is included automatically in the composer autoloader, however,
// Symphony might try to include it again which would cause a fatal error.
// Check if the class already exists before declaring it again.
if (!class_exists('\\Extension_API_Framework')) {
    class Extension_API_Framework extends Extension
    {
        const CACHE_DURATION_MINUTE = 'minute';
        const CACHE_DURATION_DAY = 'day';
        const CACHE_DURATION_HOUR = 'hour';
        const CACHE_DURATION_WEEK = 'week';

        const CACHE_ENABLED = 'yes';
        const CACHE_DISABLED = 'no';

        public static function init()
        {
        }

        public function install()
        {
            $this->createCacheSection();

            return true;
        }

        public function update($previousVersion = false): bool
        {
            return $this->install();
        }

        public function enable(): bool
        {
            return $this->install();
        }

        private function createCacheSection(): void
        {
            $pageCacheSection = SectionBuilder\Models\Section::loadFromHandle(
                'page-cache'
            );
            if (!($pageCacheSection instanceof SectionBuilder\Models\Section)) {
                SectionBuilder\Import::fromJsonFile(
                    __DIR__.'/src/Install/section-page_cache.json',
                    SectionBuilder\Import::FLAG_SKIP_ORDERING
                );
            }

            return;
        }

        public function getSubscribedDelegates(): array
        {
            return [
                [
                    'page' => '/all/',
                    'delegate' => 'ModifySymphonyLauncher',
                    'callback' => 'setJSONLauncher',
                ],
                [
                    'page' => '/frontend/',
                    'delegate' => 'APIFrameworkJSONRendererAppendTransformations',
                    'callback' => 'appendTransformations',
                ],
                [
                    'page' => '/system/preferences/',
                    'delegate' => 'AddCustomPreferenceFieldsets',
                    'callback' => 'appendPreferences',
                ],
                [
                    'page' => '/system/preferences/',
                    'delegate' => 'Save',
                    'callback' => 'savePreferences',
                ],
                [
                    'page' => '/blueprints/pages/',
                    'delegate' => 'AppendPageContent',
                    'callback' => 'appendCacheablePageType',
                ],
            ];
        }

        /**
         * Append API Framework page cache preferences.
         *
         * @param array $context
         *                       delegate context
         */
        public function appendPreferences(array &$context): void
        {
            // Create preference group
            $group = new XMLElement('fieldset');
            $group->setAttribute('class', 'settings');
            $group->appendChild(new XMLElement('legend', __('API Framework')));

            // Append enable cache
            $label = Widget::Label();
            $input = Widget::Input('settings[api_framework][enable_caching]', self::CACHE_ENABLED, 'checkbox');

            if (self::isCacheEnabled()) {
                $input->setAttribute('checked', 'checked');
            }

            $label->setValue($input->generate().' '.__('Enable caching'));
            $group->appendChild($label);

            // Append help
            $group->appendChild(new XMLElement('p', __('Rendered page content for pages with type \'cacheable\' will have their content stored and reused for subsequent loads.'), ['class' => 'help']));

            // Cache lifetime
            $label = Widget::Label();
            $input = Widget::Input(
                'settings[api_framework][cache_lifetime]',
                self::getCacheLifetime(),
                null,
                ['size' => '6']
        );
            $selected = self::getCacheDuration();
            $options = [
            [
                self::CACHE_DURATION_MINUTE,
                (self::CACHE_DURATION_MINUTE == $selected),
                'minute(s)',
            ],
            [
                self::CACHE_DURATION_HOUR,
                (self::CACHE_DURATION_HOUR == $selected),
                'hour(s)',
            ],
            [
                self::CACHE_DURATION_DAY,
                (self::CACHE_DURATION_DAY == $selected),
                'day(s)',
            ],
            [
                self::CACHE_DURATION_WEEK,
                (self::CACHE_DURATION_WEEK == $selected),
                'week(s)',
            ],
        ];
            $select = Widget::Select('settings[api_framework][cache_duration]', $options, ['class' => 'inline', 'style' => 'display: inline; width: auto;']);

            $label->setValue(__('Refresh page cache every %s %s', [$input->generate(false), $select->generate(false)]));
            $group->appendChild($label);

            // Append help
            $group->appendChild(new XMLElement('p', __('Once page cache expires, the next time that page is loaded any existing cache data will be replaced.'), ['class' => 'help']));

            // Append disable cleanup
            $label = Widget::Label();
            $input = Widget::Input('settings[api_framework][cache_disable_cleanup]', 'yes', 'checkbox');

            if (!self::isCacheCleanupEnabled()) {
                $input->setAttribute('checked', 'checked');
            }

            $label->setValue($input->generate().' '.__('Disable Cleanup'));
            $group->appendChild($label);

            // Append help
            $group->appendChild(new XMLElement('p', __('By default, any expired cache entries are automatically checked for and removed each time a cacheable page is rendered. If there the site has a large volume of cached content, you may wish to disable this to reduce load.'), ['class' => 'help']));

            // Append new preference group
            $context['wrapper']->appendChild($group);
        }

        /**
         * Save preferences.
         *
         * @param array $context
         *                       delegate context
         */
        public function savePreferences(array &$context): void
        {
            if (!is_array($context['settings'])) {
                // Disable caching by default
                $context['settings'] = [
                'api_framework' => [
                    'enable_caching' => self::CACHE_DISABLED,
                    'cache_lifetime' => 1,
                    'cache_duration' => self::CACHE_DURATION_HOUR,
                    'cache_disable_cleanup' => 'no',
                ],
            ];
            } else {
                if (!isset($context['settings']['api_framework']['enable_caching'])) {
                    // Disable caching if it has not been checked
                    $context['settings']['api_framework']['enable_caching'] = self::CACHE_DISABLED;
                }

                if (!isset($context['settings']['api_framework']['cache_disable_cleanup'])) {
                    // Disable cache cheanup had been checked
                    $context['settings']['api_framework']['cache_disable_cleanup'] = 'no';
                }

                $context['settings']['api_framework']['cache_lifetime'] = max(1, (int) $context['settings']['api_framework']['cache_lifetime']);
            }
        }

        /**
         * Check if cache is enabled.
         */
        public static function isCacheEnabled(): bool
        {
            return
            self::CACHE_ENABLED == Symphony::Configuration()->get('enable_caching', 'api_framework')
                ? true
                : false
        ;
        }

        /**
         * Check if cache cleanup is enabled.
         */
        public static function isCacheCleanupEnabled(): bool
        {
            return
            'yes' != Symphony::Configuration()->get('cache_disable_cleanup', 'api_framework')
                ? true
                : false
        ;
        }

        /**
         * Convienence method for getting the cache_lifetime setting.
         */
        public static function getCacheLifetime(): ?int
        {
            return (int) Symphony::Configuration()->get('cache_lifetime', 'api_framework');
        }

        /**
         * Convienence method for getting the cache_duration setting.
         */
        public static function getCacheDuration(): ?string
        {
            return Symphony::Configuration()->get('cache_duration', 'api_framework');
        }

        /**
         * Takes the cache lifetime and duration and calculate when cache should
         * expire.
         *
         * @returns timestamp
         */
        public static function calculateNextCacheExpiryTime(): int
        {
            return strtotime(sprintf(
                '+%s %s',
                self::getCacheLifetime(),
                self::getCacheDuration()
            ));
        }

        /**
         * Converts cache lifetime and cache duration into seconds. This is used
         * when calculating the cache expiry time.
         *
         * @returns integer
         */
        public static function cacheLifetimeReal(): int
        {
            $value = self::getCacheLifetime();

            switch (self::getCacheDuration()) {
            case self::DURATION_WEEK:
                $value *= 7;

                // no break
            case self::DURATION_DAY:
                $value *= 24;

                // no break
            case self::DURATION_HOUR:
                $value *= 60;
                break;
        }

            return $value;
        }

        /**
         * Append type for cacheable pages to page editor.
         *
         * @param array $context
         *                       delegate context
         */
        public function appendCacheablePageType(array &$context): void
        {
            // Find page types
            $elements = $context['form']->getChildren();
            $fieldset = $elements[0]->getChildren();
            $group = $fieldset[2]->getChildren();
            $div = $group[1]->getChildren();
            $types = $div[2]->getChildren();

            // Search for existing cacheable type
            $cacheableTypeAlreadyExists = false;
            foreach ($types as $type) {
                if ('cacheable' == $type->getValue()) {
                    $cacheableTypeAlreadyExists = true;
                    break;
                }
            }

            // Append cacheable type
            if (!$cacheableTypeAlreadyExists) {
                $div[2]->appendChild(new XMLElement('li', 'cacheable'));
            }
        }

        public function appendTransformations(array &$context): void
        {
            // Add the @jsonForceArray transformation
            $context['transformer']->append(new ApiFramework\Transformation(
                function (array $input, array $attributes = []) {
                    // First make sure there is an attributes array
                    if (empty($attributes)) {
                        return false;
                    }
                    // Only looking at the jsonForceArray property
                    elseif (!isset($attributes['jsonForceArray']) || 'true' !== $attributes['jsonForceArray']) {
                        return false;
                    }
                    // This is already an indexed array.
                    elseif (!Arrays\array_is_assoc($input)) {
                        return false;
                    }
                    // jsonForceArray is set, and it's true
                    return true;
                },
                function (array $input, array $attributes = []) {
                    $result = [];
                    // Encapsulate everything in an array
                    foreach ($input as $key => $value) {
                        $result[$key] = $value;
                        unset($input[$key]);
                    }
                    $input[] = $result;

                    return $input;
                }
            ));

            // Add the @convertEmptyElementsToString transformation
            // Render empty string values instead of empty arrays
            // i.e. <banana></banana>, normally converted to
            // array(0) {} and thus banana: [], becomes banana: ""
            $context['transformer']->append(new ApiFramework\Transformation(
                function (array $input, array $attributes = []) {
                    if (isset($attributes['convertEmptyElementsToString'])) {
                        return true;
                    }

                    return false;
                },
                function (array $input, array $attributes = []) {
                    foreach ($input as $key => $value) {
                        if (empty($value)) {
                            $input[$key] = '';
                        }
                    }

                    return $input;
                }
            ));
        }

        public function setJSONLauncher(array &$context): void
        {
            if ('administration' == $_REQUEST['mode']) {
                return;
            }
            define('SYMPHONY_LAUNCHER', 'Symphony\\ApiFramework\\ApiFramework\\renderer_json');
        }
    }
}
