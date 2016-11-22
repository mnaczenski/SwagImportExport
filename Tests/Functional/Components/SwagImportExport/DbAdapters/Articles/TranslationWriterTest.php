<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\DbAdapters\Articles\TranslationWriter;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class TranslationWriterTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    private function createTranslationWriter()
    {
        return new TranslationWriter();
    }

    public function test_write_should_throw_exception_if_language_id_is_not_available()
    {
        $articleId = 273;
        $variantId = 273;
        $mainDetailId = 273;
        $translations = [
            [
                'languageId' => 3
            ]
        ];

        $translationWriter = $this->createTranslationWriter();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Shop mit ID 3 nicht gefunden');
        $translationWriter->write($articleId, $variantId, $mainDetailId, $translations);
    }

    public function test_write_should_create_translations()
    {
        $articleId = 273;
        $variantId = 273;
        $mainDetailId = 273;
        $translations = [
            [
                'name' => 'translatedName',
                'description' => 'Translated descritpion',
                'metaTitle' => 'Translated meta title',
                'keywords' => 'translated,keywords',
                'additionalText' => 'Translated additional text',
                'packUnit' => 'translated pack unit',
                'languageId' => '2'
            ]
        ];

        $translationsWriter = $this->createTranslationWriter();
        $translationsWriter->write($articleId, $variantId, $mainDetailId, $translations);

        $result = Shopware()->Container()->get('dbal_connection')->executeQuery('SELECT * FROM s_core_translations WHERE objecttype="article" AND objectkey=273')->fetchAll();
        $importedTranslation = unserialize($result[0]['objectdata']);

        $this->assertEquals($translations[0]['name'], $importedTranslation['txtArtikel']);
        $this->assertEquals($translations[0]['description'], $importedTranslation['txtshortdescription']);
        $this->assertEquals($translations[0]['additionalText'], $importedTranslation['txtzusatztxt']);
        $this->assertEquals($translations[0]['packUnit'], $importedTranslation['txtpackunit']);
    }

    public function test_write_should_create_variant_translation()
    {
        $articleId = 273;
        $variantId = 1053;
        $mainDetailId = 273;
        $translations = [
            [
                'name' => 'translatedName',
                'description' => 'Translated descritpion',
                'metaTitle' => 'Translated meta title',
                'keywords' => 'translated,keywords',
                'additionalText' => 'Translated additional text',
                'packUnit' => 'translated pack unit',
                'languageId' => '2'
            ]
        ];

        $translationWriter = $this->createTranslationWriter();
        $translationWriter->write($articleId, $variantId, $mainDetailId, $translations);

        $result = Shopware()->Container()->get('dbal_connection')->executeQuery('SELECT * FROM s_core_translations WHERE objecttype="variant" AND objectkey=1053')->fetchAll();
        $importedTranslation = unserialize($result[0]['objectdata']);

        $this->assertEquals('Translated additional text', $importedTranslation['txtzusatztxt']);
        $this->assertEquals('translated pack unit', $importedTranslation['txtpackunit']);
    }
}
