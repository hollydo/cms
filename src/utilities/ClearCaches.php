<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\events\RegisterCacheOptionsEvent;
use yii\base\Event;
use yii\base\Exception;

/**
 * ClearCaches represents a ClearCaches dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ClearCaches extends Utility
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterCacheOptionsEvent The event that is triggered when registering cache options.
     */
    const EVENT_REGISTER_CACHE_OPTIONS = 'registerCacheOptions';

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Clear Caches');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'clear-caches';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        $iconPath = Craft::getAlias('@app/icons/trash.svg');

        if ($iconPath === false) {
            throw new Exception('There was a problem getting the icon path.');
        }

        return $iconPath;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $options = [];

        foreach (self::cacheOptions() as $cacheOption) {
            $options[] = [
                'label' => $cacheOption['label'],
                'value' => $cacheOption['key']
            ];
        }

        $view = Craft::$app->getView();

        $view->registerJsResource('js/ClearCachesUtility.js');
        $view->registerJs('new Craft.ClearCachesUtility(\'clear-caches\');');

        return $view->renderTemplate('_components/utilities/ClearCaches', [
            'options' => $options,
        ]);
    }

    /**
     * Returns all cache options
     *
     * @return array
     */
    public static function cacheOptions(): array
    {
        $runtimePath = Craft::$app->getPath()->getRuntimePath();

        $options = [
            [
                'key' => 'data',
                'label' => Craft::t('app', 'Data caches'),
                'action' => [Craft::$app->getCache(), 'flush']
            ],
            [
                'key' => 'asset',
                'label' => Craft::t('app', 'Asset caches'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'cache'
            ],
            [
                'key' => 'rss',
                'label' => Craft::t('app', 'RSS caches'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'cache'
            ],
            [
                'key' => 'compiled-templates',
                'label' => Craft::t('app', 'Compiled templates'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'compiled_templates'
            ],
            [
                'key' => 'temp-files',
                'label' => Craft::t('app', 'Temp files'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'temp'
            ],
            [
                'key' => 'transform-indexes',
                'label' => Craft::t('app', 'Asset transform index'),
                'action' => function() {
                    Craft::$app->getDb()->createCommand()
                        ->truncateTable('{{%assettransformindex}}')
                        ->execute();
                }
            ],
            [
                'key' => 'asset-indexing-data',
                'label' => Craft::t('app', 'Asset indexing data'),
                'action' => function() {
                    Craft::$app->getDb()->createCommand()
                        ->truncateTable('{{%assetindexdata}}')
                        ->execute();
                }
            ],
            [
                'key' => 'template-caches',
                'label' => Craft::t('app', 'Template caches'),
                'action' => [Craft::$app->getTemplateCaches(), 'deleteAllCaches']
            ],
        ];

        $event = new RegisterCacheOptionsEvent([
            'options' => $options
        ]);

        Event::trigger(self::class, self::EVENT_REGISTER_CACHE_OPTIONS, $event);

        return $options;
    }
}